<?php
class VouchersController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'hostess', 'gerente']);

        $shiftModel = new ShiftModel();
        $shift = $shiftModel->getActiveByUser(Auth::user()['id']);

        $restaurantModel = new RestaurantModel();
        $operationModel = new OperationModel();
        $voucherModel = new VoucherModel();
        $filters = [
            'data' => date('Y-m-d'),
            'restaurante_id' => $shift['restaurante_id'] ?? '',
            'operacao_id' => $shift['operacao_id'] ?? '',
        ];
        $vouchers = $voucherModel->listByFilters($filters);

        $this->view('vouchers/index', [
            'shift' => $shift,
            'vouchers' => $vouchers,
            'restaurantes' => $restaurantModel->all(),
            'operacoes' => $operationModel->all(),
            'voucher_receive_limit_bytes' => upload_limit_bytes(10 * 1024 * 1024),
            'voucher_receive_limit_label' => format_bytes_ptbr(upload_limit_bytes(10 * 1024 * 1024)),
            'voucher_target_limit_bytes' => 5 * 1024 * 1024,
            'voucher_target_limit_label' => format_bytes_ptbr(5 * 1024 * 1024),
            'flash' => get_flash(),
        ]);
    }

    public function attachment(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'hostess', 'gerente']);

        $id = (int)($_GET['id'] ?? 0);
        $voucher = $id > 0 ? (new VoucherModel())->find($id) : null;
        $publicPath = safe_public_upload_url((string)($voucher['voucher_anexo_path'] ?? ''), 'vouchers');
        if (!$voucher || $publicPath === '') {
            http_response_code(404);
            echo 'Anexo não encontrado.';
            return;
        }

        $uploadRoot = realpath(dirname(__DIR__, 2) . '/public/uploads/vouchers');
        $fullPath = realpath(dirname(__DIR__, 2) . '/public' . $publicPath);
        $rootPrefix = $uploadRoot === false ? '' : rtrim($uploadRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (
            $uploadRoot === false
            || $fullPath === false
            || strpos($fullPath, $rootPrefix) !== 0
            || !is_file($fullPath)
            || !is_readable($fullPath)
        ) {
            http_response_code(404);
            echo 'Anexo não encontrado.';
            return;
        }

        $allowedMimeByExtension = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
        ];
        $extension = strtolower((string)pathinfo($fullPath, PATHINFO_EXTENSION));
        $mime = $allowedMimeByExtension[$extension] ?? '';
        if (class_exists('finfo')) {
            $detected = (new finfo(FILEINFO_MIME_TYPE))->file($fullPath);
            if (is_string($detected) && in_array($detected, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true)) {
                $mime = $detected;
            } else {
                $mime = '';
            }
        }
        if ($mime === '') {
            http_response_code(415);
            echo 'Formato de anexo não permitido.';
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        $filename = safe_download_filename(basename($fullPath), 'voucher');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($fullPath));
        header('Cache-Control: private, no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');

        $handle = fopen($fullPath, 'rb');
        if ($handle === false) {
            http_response_code(404);
            return;
        }
        while (!feof($handle)) {
            echo fread($handle, 1048576);
            flush();
        }
        fclose($handle);
    }
}
