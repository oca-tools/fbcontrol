<?php
class RelatoriosTematicosController extends Controller
{
    private function buildFilters(array $tematicos, bool $defaultDate = false): array
    {
        $status = normalize_mojibake(trim((string)($_GET['status'] ?? '')));
        if (mb_strlen($status, 'UTF-8') > 40) {
            $status = mb_substr($status, 0, 40, 'UTF-8');
        }
        $grupoNome = normalize_mojibake(trim((string)($_GET['grupo_nome'] ?? '')));
        if (mb_strlen($grupoNome, 'UTF-8') > 120) {
            $grupoNome = mb_substr($grupoNome, 0, 120, 'UTF-8');
        }

        return [
            'data' => sanitize_date_param($_GET['data'] ?? '', $defaultDate ? date('Y-m-d') : ''),
            'data_inicio' => sanitize_date_param($_GET['data_inicio'] ?? ''),
            'data_fim' => sanitize_date_param($_GET['data_fim'] ?? ''),
            'restaurante_id' => sanitize_int_param($_GET['restaurante_id'] ?? ''),
            'turno_id' => sanitize_int_param($_GET['turno_id'] ?? ''),
            'status' => $status,
            'grupo_nome' => $grupoNome,
            'restaurante_ids' => array_map(fn($r) => (int)$r['id'], $tematicos),
        ];
    }

    private function buildExportType(): string
    {
        $type = strtolower(trim((string)($_GET['type'] ?? 'csv')));
        return $type === 'xlsx' ? 'xlsx' : 'csv';
    }

    private function exportDocument(array $config, string $type, callable $producer): int
    {
        return (new TabularExportService())->download(
            (string)($config['filename'] ?? 'relatorio_tematicos'),
            $type,
            $config['headers'] ?? [],
            $producer,
            [
                'title' => $config['title'] ?? 'Exportacao tematica',
                'subtitle' => $config['subtitle'] ?? 'Base gerada pelo sistema.',
                'sheet_name' => $config['sheet_name'] ?? 'Tematicos',
                'meta' => $config['meta'] ?? [],
            ]
        );
    }

    private function filtersMeta(array $filters, array $restaurantes, array $turnos): array
    {
        $meta = [];
        if (!empty($filters['data'])) {
            $meta['Data'] = format_date_br((string)$filters['data']);
        }
        if (!empty($filters['data_inicio']) || !empty($filters['data_fim'])) {
            $inicio = !empty($filters['data_inicio']) ? format_date_br((string)$filters['data_inicio']) : '-';
            $fim = !empty($filters['data_fim']) ? format_date_br((string)$filters['data_fim']) : '-';
            $meta['Periodo'] = $inicio . ' a ' . $fim;
        }
        if (!empty($filters['restaurante_id'])) {
            foreach ($restaurantes as $restaurante) {
                if ((int)$restaurante['id'] === (int)$filters['restaurante_id']) {
                    $meta['Restaurante'] = normalize_mojibake((string)$restaurante['nome']);
                    break;
                }
            }
        }
        if (!empty($filters['turno_id'])) {
            foreach ($turnos as $turno) {
                if ((int)$turno['id'] === (int)$filters['turno_id']) {
                    $meta['Turno'] = normalize_mojibake((string)($turno['hora'] ?? ''));
                    break;
                }
            }
        }
        if (!empty($filters['status'])) {
            $meta['Status'] = normalize_mojibake((string)$filters['status']);
        }
        if (!empty($filters['grupo_nome'])) {
            $meta['Grupo'] = normalize_mojibake((string)$filters['grupo_nome']);
        }
        return $meta;
    }

    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $reservaModel = new ReservaTematicaModel();
        $turnoModel = new ReservaTematicaTurnoModel();

        $tematicos = (new TematicAccessService())->allTematicRestaurants();

        $filters = $this->buildFilters($tematicos, true);

        $summary = $reservaModel->summary($filters);
        $byRestaurant = $reservaModel->totalsByRestaurant($filters);
        $byTurno = $reservaModel->totalsByTurno($filters);
        $byDay = $reservaModel->totalsByDay($filters);
        $perPage = 20;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $total = $reservaModel->countByFilters($filters);
        $totalPages = max(1, (int)ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $list = $reservaModel->listByFilters($filters, $perPage, ($page - 1) * $perPage);

        $base = (int)($summary['pax_reservadas'] ?? 0);
        $taxaComparecimento = $base > 0 ? round(((int)($summary['pax_comparecidas'] ?? 0) / $base) * 100, 1) : 0;

        $this->view('relatorios_tematicos/index', [
            'filters' => $filters,
            'summary' => $summary,
            'by_restaurant' => $byRestaurant,
            'by_turno' => $byTurno,
            'by_day' => $byDay,
            'list' => $list,
            'list_page' => $page,
            'list_total_pages' => $totalPages,
            'list_total' => $total,
            'taxa_comparecimento' => $taxaComparecimento,
            'restaurantes' => $tematicos,
            'turnos' => $turnoModel->all(),
        ]);
    }

    public function export(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $reservaModel = new ReservaTematicaModel();
        $tematicos = (new TematicAccessService())->allTematicRestaurants();
        $turnos = (new ReservaTematicaTurnoModel())->all();

        $filters = $this->buildFilters($tematicos, false);

        $type = $this->buildExportType();
        $totalRows = $reservaModel->countByFilters($filters);
        (new SecurityLogModel())->log('export_relatorios_tematicos', (int)(Auth::user()['id'] ?? 0), [
            'type' => $type,
            'rows' => $totalRows,
            'filters' => $filters,
        ]);
        $this->exportDocument([
            'filename' => 'relatorio_tematicos',
            'title' => 'Reservas tematicas',
            'subtitle' => 'Base detalhada para operacao, supervisao e auditoria.',
            'sheet_name' => 'Tematicos',
            'meta' => $this->filtersMeta($filters, $tematicos, $turnos),
            'headers' => [
                'data_reserva','turno','restaurante','grupo_id','grupo_nome','responsavel_grupo','uh','titular','pax_adulto','pax_chd','qtd_chd','pax_reservada','pax_real','status',
                'obs_reserva','tags','obs_operacao','usuario','criado_em'
            ],
        ], $type, static function (callable $writeRow) use ($reservaModel, $filters): int {
            return $reservaModel->exportByFilters($filters, static function (array $r) use ($writeRow): void {
                $writeRow([
                    $r['data_reserva'],
                    $r['turno_hora'],
                    $r['restaurante'],
                    $r['grupo_id'] ?? '',
                    $r['grupo_nome_display'] ?? $r['grupo_nome'] ?? '',
                    $r['grupo_responsavel'] ?? '',
                    $r['uh_numero'],
                    $r['titular_nome_display'] ?? $r['titular_nome'] ?? '',
                    $r['pax_adulto_calc'] ?? '',
                    $r['pax_chd_calc'] ?? '',
                    $r['qtd_chd_calc'] ?? '',
                    $r['pax'],
                    $r['pax_real'] ?? '',
                    $r['status'],
                    $r['observacao_reserva'] ?? '',
                    $r['observacao_tags'] ?? '',
                    $r['observacao_operacao'] ?? '',
                    $r['usuario'],
                    $r['criado_em'],
                ]);
            });
        });
        exit;
    }
}
