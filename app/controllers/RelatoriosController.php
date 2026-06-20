<?php
declare(strict_types=1);

class RelatoriosController extends Controller
{
    /**
     * Lê os filtros do relatório consolidado para preservar a mesma visão entre tela e exportação.
     */
    private function buildFilters(bool $defaultDate = false): array
    {
        return (new RelatorioGerencialService())->buildFilters($_GET, $defaultDate);
    }

    private function buildBiFilters(array $baseFilters): array
    {
        return (new RelatorioGerencialService())->buildBiFilters($_GET, $baseFilters);
    }

    private function buildExportType(): string
    {
        return (new RelatorioGerencialService())->buildExportType($_GET);
    }

    private function auditExport(string $exportName, array $filters, string $type, int $rows): void
    {
        (new SecurityLogModel())->log('export_' . $exportName, (int)(Auth::user()['id'] ?? 0), [
            'type' => $type,
            'rows' => $rows,
            'filters' => $filters,
        ]);
    }

    private function exportDocument(array $config, string $type, callable $producer): int
    {
        return (new TabularExportService())->download(
            (string)($config['filename'] ?? 'relatorio'),
            $type,
            $config['headers'] ?? [],
            $producer,
            [
                'title' => $config['title'] ?? 'Exportacao FBControl',
                'subtitle' => $config['subtitle'] ?? 'Base gerada pelo sistema.',
                'sheet_name' => $config['sheet_name'] ?? 'Exportacao',
                'meta' => $config['meta'] ?? [],
            ]
        );
    }

    private function filtersMeta(array $filters, array $restaurantes, array $operacoes, array $labels = []): array
    {
        return (new RelatorioGerencialService())->filtersMeta($filters, $restaurantes, $operacoes, $labels);
    }

    private function resolveVoucherPdfFilters(): array
    {
        $data = sanitize_date_param($_GET['data'] ?? '');
        $inicio = sanitize_date_param($_GET['data_inicio'] ?? '');
        $fim = sanitize_date_param($_GET['data_fim'] ?? '');
        $filters = [
            'data' => '',
            'data_inicio' => '',
            'data_fim' => '',
            'restaurante_id' => sanitize_int_param($_GET['restaurante_id'] ?? ''),
            'operacao_id' => sanitize_int_param($_GET['operacao_id'] ?? ''),
        ];

        if ($inicio !== '' || $fim !== '') {
            if ($inicio === '') {
                $inicio = $fim;
            }
            if ($fim === '') {
                $fim = $inicio;
            }
            if ($inicio > $fim) {
                return [];
            }
            $filters['data_inicio'] = $inicio;
            $filters['data_fim'] = $fim;
            return $filters;
        }

        if ($data === '') {
            $data = date('Y-m-d');
        }
        $filters['data'] = $data;
        return $filters;
    }

    private function voucherPdfPeriodLabel(array $filters): string
    {
        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            return (string)$filters['data_inicio'] . '_a_' . (string)$filters['data_fim'];
        }

        return (string)($filters['data'] ?? date('Y-m-d'));
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

    private function voucherExportAttachments(array $filters = []): array
    {
        $rows = (new RelatorioGerencialService())->listarVouchersParaAnexos($filters);

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
        $uploadRootPrefix = rtrim($uploadRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

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
            if ($fullPath === false || strpos($fullPath, $uploadRootPrefix) !== 0 || !is_file($fullPath)) {
                continue;
            }

            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', basename($fullPath)) ?: ('voucher_' . count($files) . '.' . $ext);
            if ($ext === InteligenciaOperacionalConstants::FORMAT_PDF) {
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
            echo InteligenciaOperacionalConstants::MESSAGE_ARQUIVO_NAO_ENCONTRADO;
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

        $safeFilename = safe_download_filename($filename, 'download');
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

    /**
     * Exibe a central de relatórios para auditoria de acessos, consumo de colaboradores, vouchers e BI.
     */
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $this->view('reports/index', (new RelatorioGerencialService())->montarTelaRelatorios($_GET));
    }

    /**
     * Exporta a base consolidada de acessos, refeições e vouchers para conferência gerencial.
     */
    public function export(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $filters = $this->buildBiFilters($this->buildFilters(false));
        $relatorioGerencialService = new RelatorioGerencialService();
        $restaurantes = $relatorioGerencialService->restaurantes();
        $operacoes = $relatorioGerencialService->operacoes();
        $type = $this->buildExportType();
        $totalRows = $relatorioGerencialService->totalRegistrosConsolidados($filters);
        $this->auditExport('relatorios', $filters, $type, $totalRows);
        $this->exportDocument([
            'filename' => InteligenciaOperacionalConstants::EXPORT_RELATORIO_ACESSOS_FILENAME,
            'title' => 'Relatorio operacional consolidado',
            'subtitle' => 'Acessos, refeicoes de colaboradores e vouchers no mesmo arquivo.',
            'sheet_name' => 'Operacional',
            'meta' => $this->filtersMeta($filters, $restaurantes, $operacoes),
            'headers' => InteligenciaOperacionalConstants::HEADERS_RELATORIO_ACESSOS,
        ], $type, static function (callable $writeRow) use ($relatorioGerencialService, $filters): int {
            $processed = 0;
            $processed += $relatorioGerencialService->exportarAcessos($filters, static function (array $r) use ($writeRow): void {
                $writeRow([
                    'acesso',
                    $r['criado_em'],
                    $r['uh_numero'],
                    $r['pax'],
                    $r['restaurante'],
                    $r['operacao'],
                    $r['porta'] ?? '',
                    $r['usuario'],
                    $r['alerta_duplicidade'] ? 'sim' : 'nao',
                    $r['fora_do_horario'] ? 'sim' : 'nao',
                    '', '',
                    '', '', '', '', '', ''
                ]);
            });
            $processed += $relatorioGerencialService->exportarRefeicoesColaboradores($filters, static function (array $r) use ($writeRow): void {
                $writeRow([
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
            });
            $processed += $relatorioGerencialService->exportarVouchers($filters, static function (array $r) use ($writeRow): void {
                $writeRow([
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
            });
            return $processed;
        });
        exit;
    }

    /**
     * Exporta o mapa diário por UH para validar presença nos serviços e benefícios associados.
     */
    public function export_mapa(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $data = sanitize_date_param($_GET['data'] ?? '', date('Y-m-d'));

        $relatorioGerencialService = new RelatorioGerencialService();
        $rows = $relatorioGerencialService->mapaDiario($data);
        $restaurantes = $relatorioGerencialService->restaurantes();
        $operacoes = $relatorioGerencialService->operacoes();

        $type = $this->buildExportType();
        $this->auditExport('mapa', ['data' => $data], $type, count($rows));
        $this->exportDocument([
            'filename' => InteligenciaOperacionalConstants::EXPORT_MAPA_DIARIO_UH_FILENAME,
            'title' => 'Mapa diario de UH',
            'subtitle' => 'Consolidado de presencas por unidade habitacional.',
            'sheet_name' => 'Mapa UH',
            'meta' => $this->filtersMeta(['data' => $data], $restaurantes, $operacoes),
            'headers' => InteligenciaOperacionalConstants::HEADERS_MAPA_DIARIO_UH,
        ], $type, static function (callable $writeRow) use ($rows): int {
            foreach ($rows as $r) {
                $writeRow([
                    $r['uh_numero'],
                    $r['cafe'] ? 'sim' : 'nao',
                    $r['almoco'] ? 'sim' : 'nao',
                    $r['jantar'] ? 'sim' : 'nao',
                    $r['tematico'] ? 'sim' : 'nao',
                    $r['privileged'] ? 'sim' : 'nao',
                    !empty($r['vip_premium']) ? 'sim' : 'nao',
                ]);
            }
            return count($rows);
        });
        exit;
    }

    /**
     * Exporta a base analítica de BI para investigar padrões de passagem, duplicidade e alertas.
     */
    public function export_bi(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $filters = $this->buildBiFilters($this->buildFilters(false));

        $groupedMultiple = ($filters['status'] ?? '') === 'multiplo';
        $relatorioGerencialService = new RelatorioGerencialService();
        $restaurantes = $relatorioGerencialService->restaurantes();
        $operacoes = $relatorioGerencialService->operacoes();
        $type = $this->buildExportType();
        $totalRows = $relatorioGerencialService->totalRegistrosBi($filters);
        $this->auditExport('bi', $filters, $type, $totalRows);
        if ($groupedMultiple) {
            $this->exportDocument([
                'filename' => InteligenciaOperacionalConstants::EXPORT_BASE_BI_FILENAME,
                'title' => 'Base completa para BI',
                'subtitle' => 'Agrupamentos de multiplos acessos para analise operacional.',
                'sheet_name' => 'BI Multiplo',
                'meta' => $this->filtersMeta($filters, $restaurantes, $operacoes),
                'headers' => InteligenciaOperacionalConstants::HEADERS_BI_MULTIPLO,
            ], $type, static function (callable $writeRow) use ($relatorioGerencialService, $filters): int {
                return $relatorioGerencialService->exportarBiMultiplosAcessos($filters, static function (array $r) use ($writeRow): void {
                    $writeRow([
                        $r['status_operacional'] ?? 'Multiplo Acesso',
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
                });
            });
            exit;
        }

        $this->exportDocument([
            'filename' => InteligenciaOperacionalConstants::EXPORT_BASE_BI_FILENAME,
            'title' => 'Base completa para BI',
            'subtitle' => 'Eventos detalhados do periodo filtrado.',
            'sheet_name' => 'BI Detalhado',
            'meta' => $this->filtersMeta($filters, $restaurantes, $operacoes),
            'headers' => InteligenciaOperacionalConstants::HEADERS_BI_DETALHADO,
        ], $type, static function (callable $writeRow) use ($relatorioGerencialService, $filters): int {
            return $relatorioGerencialService->exportarBiDetalhado($filters, static function (array $r) use ($writeRow): void {
                $writeRow([
                    $r['status_operacional'] ?? 'OK',
                    $r['criado_em'],
                    $r['uh_numero'],
                    $r['pax'],
                    $r['restaurante'],
                    $r['operacao'],
                    $r['porta'] ?? '',
                    $r['usuario'],
                ]);
            });
        });
        exit;
    }

    /**
     * Exporta refeições de colaboradores para controle operacional e conciliação com contratos internos.
     */
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

        $relatorioGerencialService = new RelatorioGerencialService();
        $restaurantes = $relatorioGerencialService->restaurantes();
        $operacoes = $relatorioGerencialService->operacoes();
        $type = $this->buildExportType();
        $totalRows = $relatorioGerencialService->contarRefeicoesColaboradores($filters);
        $this->auditExport('colaboradores', $filters, $type, $totalRows);
        $this->exportDocument([
            'filename' => InteligenciaOperacionalConstants::EXPORT_COLABORADORES_FILENAME,
            'title' => 'Refeicoes de colaboradores',
            'subtitle' => 'Historico operacional por restaurante e operacao.',
            'sheet_name' => 'Colaboradores',
            'meta' => $this->filtersMeta($filters, $restaurantes, $operacoes),
            'headers' => InteligenciaOperacionalConstants::HEADERS_COLABORADORES,
        ], $type, static function (callable $writeRow) use ($relatorioGerencialService, $filters): int {
            return $relatorioGerencialService->exportarRefeicoesColaboradores($filters, static function (array $r) use ($writeRow): void {
                $writeRow([
                    $r['criado_em'],
                    $r['nome_colaborador'],
                    $r['quantidade'],
                    $r['restaurante'],
                    $r['operacao'],
                    $r['usuario'],
                ]);
            });
        });
        exit;
    }

    /**
     * Exporta vouchers registrados para conferência de upselling e evidências de atendimento.
     */
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

        $relatorioGerencialService = new RelatorioGerencialService();
        $restaurantes = $relatorioGerencialService->restaurantes();
        $operacoes = $relatorioGerencialService->operacoes();
        $type = $this->buildExportType();
        $totalRows = $relatorioGerencialService->contarVouchers($filters);
        $this->auditExport('vouchers', $filters, $type, $totalRows);
        $this->exportDocument([
            'filename' => InteligenciaOperacionalConstants::EXPORT_VOUCHERS_FILENAME,
            'title' => 'Vouchers registrados',
            'subtitle' => 'Exportacao tabular para conferencia de vouchers e upselling.',
            'sheet_name' => 'Vouchers',
            'meta' => $this->filtersMeta($filters, $restaurantes, $operacoes),
            'headers' => InteligenciaOperacionalConstants::HEADERS_VOUCHERS,
        ], $type, static function (callable $writeRow) use ($relatorioGerencialService, $filters): int {
            return $relatorioGerencialService->exportarVouchers($filters, static function (array $r) use ($writeRow): void {
                $writeRow([
                    $r['criado_em'],
                    $r['nome_hospede'],
                    $r['data_estadia'],
                    $r['numero_reserva'],
                    $r['servico_upselling'],
                    $r['assinatura'],
                    $r['data_venda'],
                    $relatorioGerencialService->voucherPossuiAnexo($r) ? 'sim' : 'nao',
                    $r['restaurante'],
                    $r['operacao'],
                    $r['usuario'],
                ]);
            });
        });
        exit;
    }

    /**
     * Agrupa evidências de vouchers em PDF ou ZIP para auditoria documental do período.
     */
    public function export_voucher_pdfs(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $filters = $this->resolveVoucherPdfFilters();
        if (empty($filters)) {
            if ($this->isAsyncExportRequest()) {
                json_response(['ok' => false, 'message' => InteligenciaOperacionalConstants::MESSAGE_INTERVALO_VOUCHER_INVALIDO], 422);
            }
            set_flash(InteligenciaOperacionalConstants::FLASH_WARNING, InteligenciaOperacionalConstants::MESSAGE_INTERVALO_VOUCHER_INVALIDO);
            $this->redirect(InteligenciaOperacionalConstants::ROUTE_RELATORIOS_INDEX);
        }

        $periodLabel = $this->voucherPdfPeriodLabel($filters);
        $attachmentBundle = $this->voucherExportAttachments($filters);
        $files = $attachmentBundle['files'];
        $stats = $attachmentBundle['stats'];
        $this->auditExport(
            'vouchers_pdfs',
            $filters,
            count($files) > 1 ? InteligenciaOperacionalConstants::FORMAT_ZIP : InteligenciaOperacionalConstants::FORMAT_PDF,
            count($files)
        );

        if (empty($files)) {
            $message = InteligenciaOperacionalConstants::MESSAGE_SEM_PDFS_VOUCHERS;
            if (($stats['images'] ?? 0) > 0 && !class_exists('Imagick')) {
                $message = InteligenciaOperacionalConstants::MESSAGE_IMAGICK_INDISPONIVEL;
            } elseif (($stats['images_skipped'] ?? 0) > 0) {
                $message = InteligenciaOperacionalConstants::MESSAGE_IMAGEM_VOUCHER_NAO_CONVERTIDA;
            }
            if ($this->isAsyncExportRequest()) {
                json_response(['ok' => false, 'message' => $message], 404);
            }
            set_flash('warning', $message);
            $query = http_build_query(array_merge($filters, ['r' => 'relatorios/index']));
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
            set_flash(InteligenciaOperacionalConstants::FLASH_DANGER, InteligenciaOperacionalConstants::MESSAGE_ZIP_PREPARO_FALHOU);
            $query = http_build_query(array_merge($filters, ['r' => 'relatorios/index']));
            $this->redirect('/?' . $query);
        }
        $zipFile = $zipPath . '.zip';
        rename($zipPath, $zipFile);

        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                @unlink($zipFile);
                $this->cleanupTemporaryVoucherFiles($files);
                set_flash(InteligenciaOperacionalConstants::FLASH_DANGER, InteligenciaOperacionalConstants::MESSAGE_ZIP_CRIACAO_FALHOU);
                $query = http_build_query(array_merge($filters, ['r' => 'relatorios/index']));
                $this->redirect('/?' . $query);
            }

            foreach ($files as $file) {
                $zip->addFile($file['path'], $file['name']);
            }
            if (!$zip->close() || !is_file($zipFile) || filesize($zipFile) <= 0) {
                @unlink($zipFile);
                $this->cleanupTemporaryVoucherFiles($files);
                set_flash(InteligenciaOperacionalConstants::FLASH_DANGER, InteligenciaOperacionalConstants::MESSAGE_ZIP_FINALIZACAO_FALHOU);
                $query = http_build_query(array_merge($filters, ['r' => 'relatorios/index']));
                $this->redirect('/?' . $query);
            }
        } elseif (!$this->createStoredZip($files, $zipFile)) {
            @unlink($zipFile);
            $this->cleanupTemporaryVoucherFiles($files);
            set_flash(InteligenciaOperacionalConstants::FLASH_DANGER, InteligenciaOperacionalConstants::MESSAGE_ZIP_CRIACAO_FALHOU);
            $query = http_build_query(array_merge($filters, ['r' => 'relatorios/index']));
            $this->redirect('/?' . $query);
        }

        $this->streamDownload($zipFile, InteligenciaOperacionalConstants::EXPORT_VOUCHER_PDFS_FILENAME_PREFIX . $periodLabel . '.zip', 'application/zip');
        @unlink($zipFile);
        $this->cleanupTemporaryVoucherFiles($files);
        exit;
    }
}
