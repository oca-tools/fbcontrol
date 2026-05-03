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

    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $reservaModel = new ReservaTematicaModel();
        $restauranteModel = new RestaurantModel();
        $turnoModel = new ReservaTematicaTurnoModel();

        $allRests = $restauranteModel->all();
        $tematicos = [];
        foreach ($allRests as $rest) {
            $name = mb_strtolower($rest['nome'], 'UTF-8');
            if (strpos($name, 'giardino') !== false) {
                $tematicos[] = $rest;
                continue;
            }
            if (strpos($name, 'la brasa') !== false) {
                $tematicos[] = $rest;
                continue;
            }
            if (strpos($name, 'ix') !== false || strpos($name, 'ixu') !== false) {
                $tematicos[] = $rest;
                continue;
            }
        }

        $filters = $this->buildFilters($tematicos, true);

        $summary = $reservaModel->summary($filters);
        $byRestaurant = $reservaModel->totalsByRestaurant($filters);
        $byTurno = $reservaModel->totalsByTurno($filters);
        $byDay = $reservaModel->totalsByDay($filters);
        $list = $reservaModel->listByFilters($filters);

        $base = (int)($summary['pax_reservadas'] ?? 0);
        $taxaComparecimento = $base > 0 ? round(((int)($summary['pax_comparecidas'] ?? 0) / $base) * 100, 1) : 0;

        $this->view('relatorios_tematicos/index', [
            'filters' => $filters,
            'summary' => $summary,
            'by_restaurant' => $byRestaurant,
            'by_turno' => $byTurno,
            'by_day' => $byDay,
            'list' => $list,
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
        $restauranteModel = new RestaurantModel();

        $allRests = $restauranteModel->all();
        $tematicos = [];
        foreach ($allRests as $rest) {
            $name = mb_strtolower($rest['nome'], 'UTF-8');
            if (strpos($name, 'giardino') !== false) {
                $tematicos[] = $rest;
                continue;
            }
            if (strpos($name, 'la brasa') !== false) {
                $tematicos[] = $rest;
                continue;
            }
            if (strpos($name, 'ix') !== false || strpos($name, 'ixu') !== false) {
                $tematicos[] = $rest;
                continue;
            }
        }

        $filters = $this->buildFilters($tematicos, false);

        $rows = $reservaModel->listByFilters($filters);
        $type = $this->buildExportType();
        (new SecurityLogModel())->log('export_relatorios_tematicos', (int)(Auth::user()['id'] ?? 0), [
            'type' => $type,
            'rows' => count($rows),
            'filters' => $filters,
        ]);
        if ($type === 'xlsx') {
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="relatorio_tematicos.xls"');
        } else {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="relatorio_tematicos.csv"');
        }
        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'data_reserva','turno','restaurante','lote_id','grupo_nome','responsavel_lote','uh','titular','pax_adulto','pax_chd','qtd_chd','pax_reservada','pax_real','status','excedente',
            'obs_reserva','tags','obs_operacao','usuario','criado_em'
        ]);
        foreach ($rows as $r) {
            fputcsv($out, [
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
                $r['excedente'] ? 'sim' : 'não',
                $r['observacao_reserva'] ?? '',
                $r['observacao_tags'] ?? '',
                $r['observacao_operacao'] ?? '',
                $r['usuario'],
                $r['criado_em'],
            ]);
        }
        fclose($out);
        exit;
    }
}
