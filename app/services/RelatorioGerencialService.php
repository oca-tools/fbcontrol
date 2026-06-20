<?php
declare(strict_types=1);

final class RelatorioGerencialService
{
    public const STATUS_FILTERS = ['duplicado', 'fora_horario', 'multiplo', 'ok', 'nao_informado', 'day_use'];

    private OperacaoReadModelRepository $operacaoReadModelRepository;

    public function __construct(?OperacaoReadModelRepository $operacaoReadModelRepository = null)
    {
        $this->operacaoReadModelRepository = $operacaoReadModelRepository ?? new OperacaoReadModelRepository();
    }

    /**
     * Monta a tela de relatórios gerenciais cruzando jornada de UH, mapa diário, BI e bases de consumo.
     *
     * @return array{
     *     filters: array<string, mixed>,
     *     restaurantes: array<int, array<string, mixed>>,
     *     operacoes: array<int, array<string, mixed>>,
     *     list_paged: array<int, array<string, mixed>>,
     *     bi_filters: array<string, mixed>,
     *     bi_grouped_multiple: bool,
     *     journey: array<int, array<string, mixed>>,
     *     summary: array<string, mixed>,
     *     daily_map: array<int, array<string, mixed>>,
     *     insights: array<string, mixed>,
     *     tematicos_resumo: array<string, mixed>
     * }
     */
    public function montarTelaRelatorios(array $query): array
    {
        $filters = $this->buildFilters($query, true);
        $biFilters = $this->buildBiFilters($query, $filters);
        $biGroupedMultiple = ($biFilters['status'] ?? '') === 'multiplo';

        $dailyMap = [];
        $journey = [];
        $summary = [];
        if ($filters['uh_numero'] !== '') {
            $journey = $this->operacaoReadModelRepository->jornadaUh((string)$filters['uh_numero'], $filters);
            $summary = $this->operacaoReadModelRepository->resumoUh((string)$filters['uh_numero'], $filters);
        }
        if ($this->deveExibirMapaDiario($filters)) {
            $dailyMap = $this->operacaoReadModelRepository->mapaDiarioUh((string)$filters['data']);
        }

        $mapaPaginado = $this->paginarLista(
            $dailyMap,
            (int)($query['map_page'] ?? 1),
            InteligenciaOperacionalConstants::DEFAULT_REPORT_PAGE_SIZE
        );
        $biPage = max(1, (int)($query['bi_page'] ?? 1));
        $biTotal = $this->operacaoReadModelRepository->contarRegistrosBi($biFilters);
        $biTotalPages = max(1, (int)ceil($biTotal / InteligenciaOperacionalConstants::DEFAULT_REPORT_PAGE_SIZE));
        $biPage = min($biPage, $biTotalPages);
        $listPaged = $this->operacaoReadModelRepository->listarRegistrosBi(
            $biFilters,
            InteligenciaOperacionalConstants::DEFAULT_REPORT_PAGE_SIZE,
            ($biPage - 1) * InteligenciaOperacionalConstants::DEFAULT_REPORT_PAGE_SIZE
        );

        $colabPage = max(1, (int)($query['colab_page'] ?? 1));
        $colabTotal = $this->operacaoReadModelRepository->contarRefeicoesColaboradores($filters);
        $colabTotalPages = max(1, (int)ceil($colabTotal / InteligenciaOperacionalConstants::DEFAULT_REPORT_PAGE_SIZE));
        $colabPage = min($colabPage, $colabTotalPages);

        $voucherPage = max(1, (int)($query['voucher_page'] ?? 1));
        $voucherTotal = $this->operacaoReadModelRepository->contarVouchers($filters);
        $voucherTotalPages = max(1, (int)ceil($voucherTotal / InteligenciaOperacionalConstants::DEFAULT_REPORT_PAGE_SIZE));
        $voucherPage = min($voucherPage, $voucherTotalPages);

        return [
            'filters' => $filters,
            'restaurantes' => $this->operacaoReadModelRepository->listarRestaurantes(),
            'operacoes' => $this->operacaoReadModelRepository->listarOperacoes(),
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
            'daily_map_paged' => $mapaPaginado['rows'],
            'map_page' => $mapaPaginado['page'],
            'map_total_pages' => $mapaPaginado['total_pages'],
            'map_total' => $mapaPaginado['total'],
            'colaboradores' => [],
            'colaboradores_paged' => $this->operacaoReadModelRepository->listarRefeicoesColaboradores(
                $filters,
                InteligenciaOperacionalConstants::DEFAULT_REPORT_PAGE_SIZE,
                ($colabPage - 1) * InteligenciaOperacionalConstants::DEFAULT_REPORT_PAGE_SIZE
            ),
            'colab_page' => $colabPage,
            'colab_total_pages' => $colabTotalPages,
            'colab_total' => $colabTotal,
            'vouchers' => [],
            'vouchers_paged' => $this->operacaoReadModelRepository->listarVouchers(
                $filters,
                InteligenciaOperacionalConstants::DEFAULT_REPORT_PAGE_SIZE,
                ($voucherPage - 1) * InteligenciaOperacionalConstants::DEFAULT_REPORT_PAGE_SIZE
            ),
            'voucher_page' => $voucherPage,
            'voucher_total_pages' => $voucherTotalPages,
            'voucher_total' => $voucherTotal,
            'insights' => $this->operacaoReadModelRepository->resumoKpi($filters),
            'tematicos_resumo' => $this->operacaoReadModelRepository->indicadoresTematicos($filters),
        ];
    }

    /**
     * Normaliza filtros da tela para que relatórios e exportações leiam o mesmo recorte operacional.
     *
     * @return array{data: string, data_inicio: string, data_fim: string, uh_numero: string, restaurante_id: mixed, operacao_id: mixed, status: string}
     */
    public function buildFilters(array $query, bool $defaultDate = false): array
    {
        return [
            'data' => sanitize_date_param($query['data'] ?? '', $defaultDate ? date('Y-m-d') : ''),
            'data_inicio' => sanitize_date_param($query['data_inicio'] ?? ''),
            'data_fim' => sanitize_date_param($query['data_fim'] ?? ''),
            'uh_numero' => sanitize_uh_param($query['uh_numero'] ?? ''),
            'restaurante_id' => sanitize_int_param($query['restaurante_id'] ?? ''),
            'operacao_id' => sanitize_int_param($query['operacao_id'] ?? ''),
            'status' => sanitize_enum_param($query['status'] ?? '', self::STATUS_FILTERS),
        ];
    }

    public function buildBiFilters(array $query, array $baseFilters): array
    {
        $biFilters = $baseFilters;
        $biFilters['restaurante_id'] = '';
        $biFilters['operacao_id'] = '';
        if (array_key_exists('bi_restaurante_id', $query)) {
            $biFilters['restaurante_id'] = sanitize_int_param($query['bi_restaurante_id'] ?? '');
        }
        if (array_key_exists('bi_operacao_id', $query)) {
            $biFilters['operacao_id'] = sanitize_int_param($query['bi_operacao_id'] ?? '');
        }
        return $biFilters;
    }

    public function buildExportType(array $query): string
    {
        $type = strtolower(trim((string)($query['type'] ?? InteligenciaOperacionalConstants::FORMAT_CSV)));
        return $type === InteligenciaOperacionalConstants::FORMAT_XLSX
            ? InteligenciaOperacionalConstants::FORMAT_XLSX
            : InteligenciaOperacionalConstants::FORMAT_CSV;
    }

    /**
     * Traduz filtros técnicos em metadados legíveis no cabeçalho de planilhas gerenciais.
     *
     * @return array<string, string>
     */
    public function filtersMeta(array $filters, array $restaurantes, array $operacoes, array $labels = []): array
    {
        $meta = [];
        if (!empty($filters['data'])) {
            $meta[$labels['data'] ?? 'Data'] = format_date_br((string)$filters['data']);
        }
        if (!empty($filters['data_inicio']) || !empty($filters['data_fim'])) {
            $inicio = !empty($filters['data_inicio']) ? format_date_br((string)$filters['data_inicio']) : '-';
            $fim = !empty($filters['data_fim']) ? format_date_br((string)$filters['data_fim']) : '-';
            $meta[$labels['periodo'] ?? 'Periodo'] = $inicio . ' a ' . $fim;
        }
        if (!empty($filters['uh_numero'])) {
            $meta[$labels['uh'] ?? 'UH'] = (string)$filters['uh_numero'];
        }
        if (!empty($filters['restaurante_id'])) {
            foreach ($restaurantes as $restaurante) {
                if ((int)$restaurante['id'] === (int)$filters['restaurante_id']) {
                    $meta[$labels['restaurante'] ?? 'Restaurante'] = normalize_mojibake((string)$restaurante['nome']);
                    break;
                }
            }
        }
        if (!empty($filters['operacao_id'])) {
            foreach ($operacoes as $operacao) {
                if ((int)$operacao['id'] === (int)$filters['operacao_id']) {
                    $meta[$labels['operacao'] ?? 'Operacao'] = normalize_mojibake((string)$operacao['nome']);
                    break;
                }
            }
        }
        if (!empty($filters['status'])) {
            $meta[$labels['status'] ?? 'Status'] = normalize_mojibake((string)$filters['status']);
        }
        return $meta;
    }

    public function restaurantes(): array
    {
        return $this->operacaoReadModelRepository->listarRestaurantes();
    }

    public function operacoes(): array
    {
        return $this->operacaoReadModelRepository->listarOperacoes();
    }

    public function totalRegistrosConsolidados(array $filters): int
    {
        return $this->operacaoReadModelRepository->contarAcessosRelatorio($filters)
            + $this->operacaoReadModelRepository->contarRefeicoesColaboradores($filters)
            + $this->operacaoReadModelRepository->contarVouchers($filters);
    }

    public function exportarAcessos(array $filters, callable $callback): int
    {
        return $this->operacaoReadModelRepository->exportarAcessosRelatorio($filters, $callback);
    }

    public function exportarRefeicoesColaboradores(array $filters, callable $callback): int
    {
        return $this->operacaoReadModelRepository->exportarRefeicoesColaboradores($filters, $callback);
    }

    public function exportarVouchers(array $filters, callable $callback): int
    {
        return $this->operacaoReadModelRepository->exportarVouchers($filters, $callback);
    }

    public function totalRegistrosBi(array $filters): int
    {
        return ($filters['status'] ?? '') === 'multiplo'
            ? $this->operacaoReadModelRepository->contarGruposMultiplosAcessos($filters)
            : $this->operacaoReadModelRepository->contarAcessosRelatorio($filters);
    }

    public function exportarBiDetalhado(array $filters, callable $callback): int
    {
        return $this->operacaoReadModelRepository->exportarAcessosRelatorio($filters, $callback);
    }

    public function exportarBiMultiplosAcessos(array $filters, callable $callback): int
    {
        return $this->operacaoReadModelRepository->exportarGruposMultiplosAcessos($filters, $callback);
    }

    public function contarRefeicoesColaboradores(array $filters): int
    {
        return $this->operacaoReadModelRepository->contarRefeicoesColaboradores($filters);
    }

    public function contarVouchers(array $filters): int
    {
        return $this->operacaoReadModelRepository->contarVouchers($filters);
    }

    public function listarVouchersParaAnexos(array $filters): array
    {
        return $this->operacaoReadModelRepository->listarVouchersParaAnexos($filters);
    }

    /**
     * Indica se o voucher possui evidência anexada sem expor o caminho físico no controller/exportação.
     */
    public function voucherPossuiAnexo(array $voucher): bool
    {
        return safe_public_upload_url((string)($voucher['voucher_anexo_path'] ?? ''), 'vouchers') !== '';
    }

    public function mapaDiario(string $data): array
    {
        return $this->operacaoReadModelRepository->mapaDiarioUh($data);
    }

    private function deveExibirMapaDiario(array $filters): bool
    {
        return $filters['data'] !== ''
            && !(!empty($filters['data_inicio']) && !empty($filters['data_fim']) && $filters['data_inicio'] !== $filters['data_fim']);
    }

    private function paginarLista(array $rows, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $total = count($rows);
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $totalPages);
        return [
            'rows' => array_slice($rows, ($page - 1) * $perPage, $perPage),
            'page' => $page,
            'total_pages' => $totalPages,
            'total' => $total,
        ];
    }
}
