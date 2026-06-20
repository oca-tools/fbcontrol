<?php
declare(strict_types=1);

class VouchersController extends Controller
{
    /**
     * Exibe a fila de vouchers do dia e registra comprovantes de venda/compensacao da operacao.
     */
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'hostess', 'gerente']);

        $usuario = Auth::user();
        $shiftModel = new ShiftModel();
        $shift = $shiftModel->getActiveByUser((int)$usuario['id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($_POST === [] && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
                $resultado = (new RegistrarVoucherService())->executar(new RegistrarVoucherCommand([
                    'usuario' => $usuario,
                    'turno' => $shift ?? [],
                    'post' => $_POST,
                    'files' => $_FILES,
                    'server' => $_SERVER,
                ]));
                set_flash(ConsumosEVouchersConstants::FLASH_DANGER, $resultado->message());
                $this->redirect(ConsumosEVouchersConstants::ROUTE_VOUCHERS_INDEX);
            }

            if (!csrf_validate($_POST['csrf_token'] ?? '')) {
                set_flash(ConsumosEVouchersConstants::FLASH_DANGER, ConsumosEVouchersConstants::MESSAGE_TOKEN_INVALIDO);
                $this->redirect(ConsumosEVouchersConstants::ROUTE_VOUCHERS_INDEX);
            }

            $resultado = (new RegistrarVoucherService())->executar(new RegistrarVoucherCommand([
                'usuario' => $usuario,
                'turno' => $shift ?? [],
                'post' => $_POST,
                'files' => $_FILES,
                'server' => $_SERVER,
            ]));

            set_flash(
                $resultado->isSuccess() ? ConsumosEVouchersConstants::FLASH_SUCCESS : ConsumosEVouchersConstants::FLASH_DANGER,
                $resultado->message()
            );
            $this->redirect(ConsumosEVouchersConstants::ROUTE_VOUCHERS_INDEX);
        }

        $restaurantModel = new RestaurantModel();
        $operationModel = new OperationModel();
        $filters = [
            'data' => date('Y-m-d'),
            'restaurante_id' => $shift['restaurante_id'] ?? '',
            'operacao_id' => $shift['operacao_id'] ?? '',
        ];
        $vouchers = (new VoucherRepository())->listarVouchers($filters);

        $this->view('vouchers/index', [
            'shift' => $shift,
            'vouchers' => $vouchers,
            'restaurantes' => $restaurantModel->all(),
            'operacoes' => $operationModel->all(),
            'voucher_receive_limit_bytes' => upload_limit_bytes(ConsumosEVouchersConstants::VOUCHER_RECEIVE_BYTES),
            'voucher_receive_limit_label' => format_bytes_ptbr(upload_limit_bytes(ConsumosEVouchersConstants::VOUCHER_RECEIVE_BYTES)),
            'voucher_target_limit_bytes' => ConsumosEVouchersConstants::VOUCHER_TARGET_BYTES,
            'voucher_target_limit_label' => format_bytes_ptbr(ConsumosEVouchersConstants::VOUCHER_TARGET_BYTES),
            'flash' => get_flash(),
        ]);
    }

    /**
     * Entrega o comprovante do voucher com validacao de permissao, tipo e caminho seguro.
     */
    public function attachment(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'hostess', 'gerente']);

        $id = (int)($_GET['id'] ?? 0);
        $voucher = $id > 0 ? (new VoucherRepository())->buscarVoucher($id) : null;
        $publicPath = safe_public_upload_url(
            (string)($voucher['voucher_anexo_path'] ?? ''),
            ConsumosEVouchersConstants::VOUCHER_UPLOAD_PUBLIC_DIR
        );
        if (!$voucher || $publicPath === '') {
            http_response_code(404);
            echo ConsumosEVouchersConstants::MESSAGE_ANEXO_NAO_ENCONTRADO;
            return;
        }

        $uploadRoot = realpath(dirname(__DIR__, 2) . ConsumosEVouchersConstants::VOUCHER_UPLOAD_ABSOLUTE_DIR);
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
            echo ConsumosEVouchersConstants::MESSAGE_ANEXO_NAO_ENCONTRADO;
            return;
        }

        $extension = strtolower((string)pathinfo($fullPath, PATHINFO_EXTENSION));
        $mime = ConsumosEVouchersConstants::ALLOWED_ATTACHMENT_MIME_BY_EXTENSION[$extension] ?? '';
        if (class_exists('finfo')) {
            $detected = (new finfo(FILEINFO_MIME_TYPE))->file($fullPath);
            if (is_string($detected) && in_array($detected, ConsumosEVouchersConstants::ALLOWED_ATTACHMENT_MIMES, true)) {
                $mime = $detected;
            } else {
                $mime = '';
            }
        }
        if ($mime === '') {
            http_response_code(415);
            echo ConsumosEVouchersConstants::MESSAGE_FORMATO_ANEXO_NAO_PERMITIDO;
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        $filename = safe_download_filename(basename($fullPath), ConsumosEVouchersConstants::VOUCHER_DOWNLOAD_FALLBACK_NAME);
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
            echo fread($handle, ConsumosEVouchersConstants::VOUCHER_STREAM_CHUNK_BYTES);
            flush();
        }
        fclose($handle);
    }
}
