<?php
class RelatoriosController extends Controller
{
    private const STATUS_FILTERS = ['duplicado', 'fora_horario', 'multiplo', 'ok', 'nao_informado', 'day_use'];

    private function buildFilters(bool $defaultDate = false): array
    {
        return [
            'data' => sanitize_date_param($_GET['data'] ?? '', $defaultDate ? date('Y-m-d') : ''),
            'data_inicio' => sanitize_date_param($_GET['data_inicio'] ?? ''),
            'data_fim' => sanitize_date_param($_GET['data_fim'] ?? ''),
            'uh_numero' => sanitize_uh_param($_GET['uh_numero'] ?? ''),
            'restaurante_id' => sanitize_int_param($_GET['restaurante_id'] ?? ''),
            'operacao_id' => sanitize_int_param($_GET['operacao_id'] ?? ''),
            'status' => sanitize_enum_param($_GET['status'] ?? '', self::STATUS_FILTERS),
        ];
    }

    private function buildBiFilters(array $baseFilters): array
    {
        $biFilters = $baseFilters;
        $biFilters['restaurante_id'] = '';
        $biFilters['operacao_id'] = '';
        if (array_key_exists('bi_restaurante_id', $_GET)) {
            $biFilters['restaurante_id'] = sanitize_int_param($_GET['bi_restaurante_id'] ?? '');
        }
        if (array_key_exists('bi_operacao_id', $_GET)) {
            $biFilters['operacao_id'] = sanitize_int_param($_GET['bi_operacao_id'] ?? '');
        }
        return $biFilters;
    }

    private function buildExportType(): string
    {
        $type = strtolower(trim((string)($_GET['type'] ?? 'csv')));
        return $type === 'xlsx' ? 'xlsx' : 'csv';
    }

    private function auditExport(string $exportName, array $filters, string $type, int $rows): void
    {
        (new SecurityLogModel())->log('export_' . $exportName, (int)(Auth::user()['id'] ?? 0), [
            'type' => $type,
            'rows' => $rows,
            'filters' => $filters,
        ]);
    }

    private function resolveVoucherPdfDate(): string
    {
        $data = sanitize_date_param($_GET['data'] ?? '');
        $inicio = sanitize_date_param($_GET['data_inicio'] ?? '');
        $fim = sanitize_date_param($_GET['data_fim'] ?? '');

        if ($data !== '' && ($inicio === '' || $fim === '' || $inicio === $fim)) {
            return $data;
        }
        if ($inicio !== '' && $fim !== '' && $inicio === $fim) {
            return $inicio;
        }
        if ($data === '' && $inicio === '' && $fim === '') {
            return date('Y-m-d');
        }
        return '';
    }

    private function isAsyncExportRequest(): bool
    {
        $requestedWith = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        return $requestedWith === 'fetch' || strpos($accept, 'application/json') !== false;
    }

    private function writeJpegBlobAsPdf(string $jpegBlob, int $width, int $height, string $pdfPath): bool
    {
        if ($jpegBlob === '' || $width <= 0 || $height <= 0) {
            return false;
        }

        $pageWidth = max(1, $width);
        $pageHeight = max(1, $height);
        $content = sprintf("q\n%d 0 0 %d 0 0 cm\n/Im0 Do\nQ\n", $pageWidth, $pageHeight);
        $objects = [
            "<< /Type /Catalog /Pages 2 0 R >>",
            "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            sprintf(
                "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %d %d] /Resources << /XObject << /Im0 4 0 R >> >> /Contents 5 0 R >>",
                $pageWidth,
                $pageHeight
            ),
            sprintf(
                "<< /Type /XObject /Subtype /Image /Width %d /Height %d /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length %d >>\nstream\n%s\nendstream",
                $width,
                $height,
                strlen($jpegBlob),
                $jpegBlob
            ),
            sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($content), $content),
        ];

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < count($offsets); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF\n";

        return file_put_contents($pdfPath, $pdf) !== false;
    }

    private function convertVoucherImageToPdf(string $imagePath, string $safeBaseName): ?array
    {
        if (!class_exists('Imagick')) {
            return null;
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'voucher_img_pdf_');
        if ($tmpPath === false) {
            return null;
        }
        $pdfPath = $tmpPath . '.pdf';
        @rename($tmpPath, $pdfPath);

        try {
            $image = new Imagick();
            $image->readImage($imagePath);
            $image->setImageBackgroundColor('white');
            $image = $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            $image->setImageColorspace(Imagick::COLORSPACE_RGB);
            $width = $image->getImageWidth();
            $height = $image->getImageHeight();
            $image->setImageFormat('jpeg');
            $image->setImageCompressionQuality(90);
            $jpegBlob = $image->getImagesBlob();
            $image->clear();
            $image->destroy();

            if (!$this->writeJpegBlobAsPdf($jpegBlob, $width, $height, $pdfPath)) {
                @unlink($pdfPath);
                return null;
            }
        } catch (Throwable $e) {
            @unlink($pdfPath);
            return null;
        }

        if (!is_file($pdfPath) || filesize($pdfPath) <= 0) {
            @unlink($pdfPath);
            return null;
        }

        return [
            'path' => $pdfPath,
            'name' => preg_replace('/\.[^.]+$/', '.pdf', $safeBaseName),
            'temporary' => true,
        ];
    }

    private function voucherExportAttachmentsForDate(string $dateRef, array $filters = []): array
    {
        $rows = (new VoucherModel())->listByFilters(array_merge($filters, [
            'data' => $dateRef,
            'data_inicio' => '',
            'data_fim' => '',
        ]));

        $uploadRoot = realpath(dirname(__DIR__, 2) . '/public/uploads/vouchers');
        if ($uploadRoot === false) {
            return [
                'files' => [],
                'stats' => [
                    'pdfs' => 0,
                    'images' => 0,
                    'images_converted' => 0,
                    'images_skipped' => 0,
                ],
            ];
        }

        $files = [];
        $stats = [
            'pdfs' => 0,
            'images' => 0,
            'images_converted' => 0,
            'images_skipped' => 0,
        ];
        $allowedImages = ['jpg', 'jpeg', 'png', 'webp'];
        foreach ($rows as $row) {
            $publicPath = (string)($row['voucher_anexo_path'] ?? '');
            if ($publicPath === '') {
                continue;
            }

            $fullPath = realpath(dirname(__DIR__, 2) . '/public' . $publicPath);
            if ($fullPath === false || strpos($fullPath, $uploadRoot) !== 0 || !is_file($fullPath)) {
                continue;
            }

            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', basename($fullPath)) ?: ('voucher_' . count($files) . '.' . $ext);
            if ($ext === 'pdf') {
                $stats['pdfs']++;
                $files[] = [
                    'path' => $fullPath,
                    'name' => sprintf('%02d_%s', count($files) + 1, $safeName),
                    'temporary' => false,
                ];
                continue;
            }

            if (in_array($ext, $allowedImages, true)) {
                $stats['images']++;
                $converted = $this->convertVoucherImageToPdf($fullPath, $safeName);
                if ($converted) {
                    $stats['images_converted']++;
                    $converted['name'] = sprintf('%02d_%s', count($files) + 1, $converted['name']);
                    $files[] = $converted;
                } else {
                    $stats['images_skipped']++;
                }
            }
        }

        return [
            'files' => $files,
            'stats' => $stats,
        ];
    }

    private function cleanupTemporaryVoucherFiles(array $files): void
    {
        foreach ($files as $file) {
            if (!empty($file['temporary']) && !empty($file['path'])) {
                @unlink((string)$file['path']);
            }
        }
    }

    private function streamDownload(string $path, string $filename, string $contentType): void
    {
        if (!is_file($path) || !is_readable($path)) {
            http_response_code(404);
            echo 'Arquivo não encontrado.';
            return;
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        ignore_user_abort(true);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        $safeFilename = str_replace(['"', "\r", "\n"], '', $filename);
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Content-Length: ' . filesize($path));
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: public');
        header('X-Content-Type-Options: nosniff');

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return;
        }

        while (!feof($handle)) {
            echo fread($handle, 1048576);
            flush();
        }
        fclose($handle);
    }

    private function zipDosTimestamp(int $timestamp): array
    {
        $parts = getdate($timestamp);
        $year = max(1980, (int)$parts['year']);
        $dosTime = ((int)$parts['hours'] << 11) | ((int)$parts['minutes'] << 5) | ((int)floor((int)$parts['seconds'] / 2));
        $dosDate = (($year - 1980) << 9) | ((int)$parts['mon'] << 5) | (int)$parts['mday'];
        return [$dosTime, $dosDate];
    }

    private function createStoredZip(array $files, string $zipFile): bool
    {
        $out = fopen($zipFile, 'wb');
        if ($out === false) {
            return false;
        }

        $central = [];
        foreach ($files as $file) {
            $path = (string)($file['path'] ?? '');
            $name = str_replace('\\', '/', (string)($file['name'] ?? basename($path)));
            if ($path === '' || $name === '' || !is_file($path) || !is_readable($path)) {
                continue;
            }

            $data = file_get_contents($path);
            if ($data === false) {
                continue;
            }

            $offset = ftell($out);
            $size = strlen($data);
            $crc = (int)hexdec(hash('crc32b', $data));
            [$dosTime, $dosDate] = $this->zipDosTimestamp((int)filemtime($path));
            $nameLen = strlen($name);

            fwrite($out, pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLen, 0));
            fwrite($out, $name);
            fwrite($out, $data);

            $central[] = [
                'name' => $name,
                'crc' => $crc,
                'size' => $size,
                'time' => $dosTime,
                'date' => $dosDate,
                'offset' => $offset,
            ];
        }

        $centralOffset = ftell($out);
        foreach ($central as $entry) {
            $name = $entry['name'];
            $nameLen = strlen($name);
            fwrite($out, pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0,
                0,
                $entry['time'],
                $entry['date'],
                $entry['crc'],
                $entry['size'],
                $entry['size'],
                $nameLen,
                0,
                0,
                0,
                0,
                0,
                $entry['offset']
            ));
            fwrite($out, $name);
        }
        $centralSize = ftell($out) - $centralOffset;
        $entryCount = count($central);
        fwrite($out, pack('VvvvvVVv', 0x06054b50, 0, 0, $entryCount, $entryCount, $centralSize, $centralOffset, 0));
        fclose($out);

        return $entryCount > 0 && is_file($zipFile) && filesize($zipFile) > 0;
    }

    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $filters = $this->buildFilters(true);

        $restaurantModel = new RestaurantModel();
        $operationModel = new OperationModel();
        $accessModel = new AccessModel();
        $colabModel = new CollaboratorMealModel();
        $voucherModel = new VoucherModel();

        $biFilters = $this->buildBiFilters($filters);
        $biGroupedMultiple = ($biFilters['status'] ?? '') === 'multiplo';
        $insights = $accessModel->kpiSummary($filters);
        $tematicosResumo = (new ReservaTematicaModel())->dashboardStats($filters);

        $journey = [];
        $summary = [];
        $dailyMap = [];
        if ($filters['uh_numero'] !== '') {
            $journey = $accessModel->uhJourney($filters['uh_numero'], $filters['data'], $filters['data_inicio'], $filters['data_fim']);
            $summary = $accessModel->uhSummary($filters['uh_numero'], $filters['data'], $filters['data_inicio'], $filters['data_fim']);
        }
        if ($filters['data'] !== '' && !(!empty($filters['data_inicio']) && !empty($filters['data_fim']) && $filters['data_inicio'] !== $filters['data_fim'])) {
            $dailyMap = $accessModel->dailyMap($filters['data']);
        }

        $perPageMap = 20;
        $mapPage = max(1, (int)($_GET['map_page'] ?? 1));
        $mapTotal = count($dailyMap);
        $mapTotalPages = max(1, (int)ceil($mapTotal / $perPageMap));
        if ($mapPage > $mapTotalPages) {
            $mapPage = $mapTotalPages;
        }
        $dailyMapPaged = array_slice($dailyMap, ($mapPage - 1) * $perPageMap, $perPageMap);

        $perPageBi = 20;
        $biPage = max(1, (int)($_GET['bi_page'] ?? 1));
        $biTotal = $biGroupedMultiple
            ? $accessModel->reportMultipleAccessGroupsCount($biFilters)
            : $accessModel->reportListCount($biFilters);
        $biTotalPages = max(1, (int)ceil($biTotal / $perPageBi));
        if ($biPage > $biTotalPages) {
            $biPage = $biTotalPages;
        }
        $listPaged = $biGroupedMultiple
            ? $accessModel->reportMultipleAccessGroups($biFilters, $perPageBi, ($biPage - 1) * $perPageBi)
            : $accessModel->reportList($biFilters, $perPageBi, ($biPage - 1) * $perPageBi);

        $perPageColab = 20;
        $colabPage = max(1, (int)($_GET['colab_page'] ?? 1));
        $colabTotal = $colabModel->countByFilters($filters);
        $colabTotalPages = max(1, (int)ceil($colabTotal / $perPageColab));
        if ($colabPage > $colabTotalPages) {
            $colabPage = $colabTotalPages;
        }
        $colaboradoresPaged = $colabModel->listByFilters($filters, $perPageColab, ($colabPage - 1) * $perPageColab);

        $perPageVoucher = 20;
        $voucherPage = max(1, (int)($_GET['voucher_page'] ?? 1));
        $voucherTotal = $voucherModel->countByFilters($filters);
        $voucherTotalPages = max(1, (int)ceil($voucherTotal / $perPageVoucher));
        if ($voucherPage > $voucherTotalPages) {
            $voucherPage = $voucherTotalPages;
        }
        $vouchersPaged = $voucherModel->listByFilters($filters, $perPageVoucher, ($voucherPage - 1) * $perPageVoucher);

        $this->view('reports/index', [
            'filters' => $filters,
            'restaurantes' => $restaurantModel->all(),
            'operacoes' => $operationModel->all(),
            'list' => [],
            'list_paged' => $listPaged,
            'bi_filters' => $biFilters,
            'bi_grouped_multiple' => $biGroupedMultiple,
            'bi_page' => $biPage,
            'bi_total_pages' => $biTotalPages,
            'bi_total' => $biTotal,
            'journey' => $journey,
            'summary' => $summary,
            'daily_map' => $dailyMap,
            'daily_map_paged' => $dailyMapPaged,
            'map_page' => $mapPage,
            'map_total_pages' => $mapTotalPages,
            'map_total' => $mapTotal,
            'colaboradores' => [],
            'colaboradores_paged' => $colaboradoresPaged,
            'colab_page' => $colabPage,
            'colab_total_pages' => $colabTotalPages,
            'colab_total' => $colabTotal,
            'vouchers' => [],
            'vouchers_paged' => $vouchersPaged,
            'voucher_page' => $voucherPage,
            'voucher_total_pages' => $voucherTotalPages,
            'voucher_total' => $voucherTotal,
            'insights' => $insights,
            'tematicos_resumo' => $tematicosResumo,
        ]);
    }

    public function export(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $filters = $this->buildBiFilters($this->buildFilters(false));

        $accessModel = new AccessModel();
        $colabModel = new CollaboratorMealModel();
        $voucherModel = new VoucherModel();
        $rows = $accessModel->reportList($filters);

        $colabRows = $colabModel->listByFilters($filters);
        $voucherRows = $voucherModel->listByFilters($filters);

        $type = $this->buildExportType();
        $this->auditExport('relatorios', $filters, $type, count($rows) + count($colabRows) + count($voucherRows));
        if ($type === 'xlsx') {
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="relatorio_acessos.xls"');
        } else {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="relatorio_acessos.csv"');
        }
        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'tipo_registro','data_hora','uh','pax','restaurante','operação','porta','usuário',
            'duplicado','fora_horario','colaborador','qtd_refeicoes',
            'hospede','data_estadia','numero_reserva','servico_upselling','assinatura','data_venda'
        ]);
        foreach ($rows as $r) {
            fputcsv($out, [
                'acesso',
                $r['criado_em'],
                $r['uh_numero'],
                $r['pax'],
                $r['restaurante'],
                $r['operacao'],
                $r['porta'] ?? '',
                $r['usuario'],
                $r['alerta_duplicidade'] ? 'sim' : 'não',
                $r['fora_do_horario'] ? 'sim' : 'não',
                '', '',
                '', '', '', '', '', ''
            ]);
        }
        foreach ($colabRows as $r) {
            fputcsv($out, [
                'colaborador',
                $r['criado_em'],
                '',
                '',
                $r['restaurante'],
                $r['operacao'],
                '',
                $r['usuario'],
                '', '',
                $r['nome_colaborador'],
                $r['quantidade'],
                '', '', '', '', '', ''
            ]);
        }
        foreach ($voucherRows as $r) {
            fputcsv($out, [
                'voucher',
                $r['criado_em'],
                '',
                '',
                $r['restaurante'],
                $r['operacao'],
                '',
                $r['usuario'],
                '', '',
                '',
                '',
                $r['nome_hospede'],
                $r['data_estadia'],
                $r['numero_reserva'],
                $r['servico_upselling'],
                $r['assinatura'],
                $r['data_venda']
            ]);
        }
        fclose($out);
        exit;
    }

    public function export_mapa(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $data = sanitize_date_param($_GET['data'] ?? '', date('Y-m-d'));

        $accessModel = new AccessModel();
        $rows = $accessModel->dailyMap($data);

        $type = $this->buildExportType();
        $this->auditExport('mapa', ['data' => $data], $type, count($rows));
        if ($type === 'xlsx') {
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="mapa_diario_uh.xls"');
        } else {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="mapa_diario_uh.csv"');
        }
        $out = fopen('php://output', 'w');
        fputcsv($out, ['uh','cafe','almoco','jantar','tematico','privileged','vip_premium']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['uh_numero'],
                $r['cafe'] ? 'sim' : 'não',
                $r['almoco'] ? 'sim' : 'não',
                $r['jantar'] ? 'sim' : 'não',
                $r['tematico'] ? 'sim' : 'não',
                $r['privileged'] ? 'sim' : 'não',
                !empty($r['vip_premium']) ? 'sim' : 'não',
            ]);
        }
        fclose($out);
        exit;
    }

    public function export_bi(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $filters = $this->buildBiFilters($this->buildFilters(false));

        $groupedMultiple = ($filters['status'] ?? '') === 'multiplo';
        $accessModel = new AccessModel();
        $rows = $groupedMultiple
            ? $accessModel->reportMultipleAccessGroups($filters)
            : $accessModel->reportList($filters);
        $type = $this->buildExportType();
        $this->auditExport('bi', $filters, $type, count($rows));
        if ($type === 'xlsx') {
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="base_bi.xls"');
        } else {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="base_bi.csv"');
        }
        $out = fopen('php://output', 'w');
        if ($groupedMultiple) {
            fputcsv($out, ['status', 'uh', 'primeira_passagem', 'ultima_passagem', 'acessos', 'pax_total', 'pax_min', 'pax_max', 'dias', 'restaurantes', 'operacoes', 'portas', 'usuarios']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['status_operacional'] ?? 'Múltiplo Acesso',
                    $r['uh_numero'],
                    $r['primeira_passagem'],
                    $r['ultima_passagem'],
                    $r['total_acessos'],
                    $r['total_pax'],
                    $r['menor_pax'],
                    $r['maior_pax'],
                    $r['dias'],
                    $r['restaurantes'],
                    $r['operacoes'],
                    $r['portas'],
                    $r['usuarios'],
                ]);
            }
            fclose($out);
            exit;
        }

        fputcsv($out, ['status', 'data_hora', 'uh', 'pax', 'restaurante', 'operacao', 'porta', 'usuario']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['status_operacional'] ?? 'OK',
                $r['criado_em'],
                $r['uh_numero'],
                $r['pax'],
                $r['restaurante'],
                $r['operacao'],
                $r['porta'] ?? '',
                $r['usuario'],
            ]);
        }
        fclose($out);
        exit;
    }

    public function export_colaboradores(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $filters = [
            'data' => sanitize_date_param($_GET['data'] ?? ''),
            'data_inicio' => sanitize_date_param($_GET['data_inicio'] ?? ''),
            'data_fim' => sanitize_date_param($_GET['data_fim'] ?? ''),
            'restaurante_id' => sanitize_int_param($_GET['restaurante_id'] ?? ''),
            'operacao_id' => sanitize_int_param($_GET['operacao_id'] ?? ''),
        ];

        $rows = (new CollaboratorMealModel())->listByFilters($filters);
        $type = $this->buildExportType();
        $this->auditExport('colaboradores', $filters, $type, count($rows));
        if ($type === 'xlsx') {
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="colaboradores_refeicoes.xls"');
        } else {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="colaboradores_refeicoes.csv"');
        }
        $out = fopen('php://output', 'w');
        fputcsv($out, ['data_hora', 'colaborador', 'quantidade', 'restaurante', 'operacao', 'usuario']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['criado_em'],
                $r['nome_colaborador'],
                $r['quantidade'],
                $r['restaurante'],
                $r['operacao'],
                $r['usuario'],
            ]);
        }
        fclose($out);
        exit;
    }

    public function export_vouchers(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $filters = [
            'data' => sanitize_date_param($_GET['data'] ?? ''),
            'data_inicio' => sanitize_date_param($_GET['data_inicio'] ?? ''),
            'data_fim' => sanitize_date_param($_GET['data_fim'] ?? ''),
            'restaurante_id' => sanitize_int_param($_GET['restaurante_id'] ?? ''),
            'operacao_id' => sanitize_int_param($_GET['operacao_id'] ?? ''),
        ];

        $rows = (new VoucherModel())->listByFilters($filters);
        $type = $this->buildExportType();
        $this->auditExport('vouchers', $filters, $type, count($rows));
        if ($type === 'xlsx') {
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="vouchers_registrados.xls"');
        } else {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="vouchers_registrados.csv"');
        }
        $out = fopen('php://output', 'w');
        fputcsv($out, ['data_hora', 'hospede', 'estadia', 'reserva', 'servico', 'assinatura', 'data_venda', 'anexo', 'restaurante', 'operacao', 'usuario']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['criado_em'],
                $r['nome_hospede'],
                $r['data_estadia'],
                $r['numero_reserva'],
                $r['servico_upselling'],
                $r['assinatura'],
                $r['data_venda'],
                $r['voucher_anexo_path'] ?? '',
                $r['restaurante'],
                $r['operacao'],
                $r['usuario'],
            ]);
        }
        fclose($out);
        exit;
    }

    public function export_voucher_pdfs(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $dateRef = $this->resolveVoucherPdfDate();
        if ($dateRef === '') {
            if ($this->isAsyncExportRequest()) {
                json_response(['ok' => false, 'message' => 'Informe uma data única para baixar os PDFs dos vouchers.'], 422);
            }
            set_flash('warning', 'Informe uma data única para baixar os PDFs dos vouchers.');
            $this->redirect('/?r=relatorios/index');
        }

        $filters = [
            'restaurante_id' => sanitize_int_param($_GET['restaurante_id'] ?? ''),
            'operacao_id' => sanitize_int_param($_GET['operacao_id'] ?? ''),
        ];
        $attachmentBundle = $this->voucherExportAttachmentsForDate($dateRef, $filters);
        $files = $attachmentBundle['files'];
        $stats = $attachmentBundle['stats'];
        $this->auditExport('vouchers_pdfs', array_merge($filters, ['data' => $dateRef]), count($files) > 1 ? 'zip' : 'pdf', count($files));

        if (empty($files)) {
            $message = 'Não há PDFs de vouchers para a data selecionada.';
            if (($stats['images'] ?? 0) > 0 && !class_exists('Imagick')) {
                $message = 'Há imagens de vouchers, mas a extensão Imagick não está disponível para convertê-las em PDF.';
            } elseif (($stats['images_skipped'] ?? 0) > 0) {
                $message = 'Há imagens de vouchers, mas não foi possível convertê-las em PDF.';
            }
            if ($this->isAsyncExportRequest()) {
                json_response(['ok' => false, 'message' => $message], 404);
            }
            set_flash('warning', $message);
            $query = http_build_query(array_merge($filters, ['r' => 'relatorios/index', 'data' => $dateRef]));
            $this->redirect('/?' . $query);
        }

        if (count($files) === 1) {
            $file = $files[0];
            $this->streamDownload($file['path'], $file['name'], 'application/pdf');
            $this->cleanupTemporaryVoucherFiles($files);
            exit;
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'vouchers_pdfs_');
        if ($zipPath === false) {
            set_flash('danger', 'Não foi possível preparar o arquivo ZIP dos vouchers.');
            $query = http_build_query(array_merge($filters, ['r' => 'relatorios/index', 'data' => $dateRef]));
            $this->redirect('/?' . $query);
        }
        $zipFile = $zipPath . '.zip';
        rename($zipPath, $zipFile);

        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                @unlink($zipFile);
                $this->cleanupTemporaryVoucherFiles($files);
                set_flash('danger', 'Não foi possível criar o arquivo ZIP dos vouchers.');
                $query = http_build_query(array_merge($filters, ['r' => 'relatorios/index', 'data' => $dateRef]));
                $this->redirect('/?' . $query);
            }

            foreach ($files as $file) {
                $zip->addFile($file['path'], $file['name']);
            }
            if (!$zip->close() || !is_file($zipFile) || filesize($zipFile) <= 0) {
                @unlink($zipFile);
                $this->cleanupTemporaryVoucherFiles($files);
                set_flash('danger', 'Não foi possível finalizar o arquivo ZIP dos vouchers.');
                $query = http_build_query(array_merge($filters, ['r' => 'relatorios/index', 'data' => $dateRef]));
                $this->redirect('/?' . $query);
            }
        } elseif (!$this->createStoredZip($files, $zipFile)) {
            @unlink($zipFile);
            $this->cleanupTemporaryVoucherFiles($files);
            set_flash('danger', 'Não foi possível criar o arquivo ZIP dos vouchers.');
            $query = http_build_query(array_merge($filters, ['r' => 'relatorios/index', 'data' => $dateRef]));
            $this->redirect('/?' . $query);
        }

        $this->streamDownload($zipFile, 'vouchers_pdfs_' . $dateRef . '.zip', 'application/zip');
        @unlink($zipFile);
        $this->cleanupTemporaryVoucherFiles($files);
        exit;
    }
}
