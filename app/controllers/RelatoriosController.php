<?php
class RelatoriosController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $filters = [
            'data' => $_GET['data'] ?? date('Y-m-d'),
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? '',
            'uh_numero' => trim($_GET['uh_numero'] ?? ''),
            'restaurante_id' => $_GET['restaurante_id'] ?? '',
            'operacao_id' => $_GET['operacao_id'] ?? '',
        ];

        $restaurantModel = new RestaurantModel();
        $operationModel = new OperationModel();
        $accessModel = new AccessModel();
        $colabModel = new CollaboratorMealModel();
        $voucherModel = new VoucherModel();

        $list = $accessModel->reportList($filters);

        $colaboradores = $colabModel->listByFilters($filters);
        $vouchers = $voucherModel->listByFilters($filters);

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

        $this->view('reports/index', [
            'filters' => $filters,
            'restaurantes' => $restaurantModel->all(),
            'operacoes' => $operationModel->all(),
            'list' => $list,
            'journey' => $journey,
            'summary' => $summary,
            'daily_map' => $dailyMap,
            'colaboradores' => $colaboradores,
            'vouchers' => $vouchers,
        ]);
    }

    public function export(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $filters = [
            'data' => $_GET['data'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? '',
            'uh_numero' => trim($_GET['uh_numero'] ?? ''),
            'restaurante_id' => $_GET['restaurante_id'] ?? '',
            'operacao_id' => $_GET['operacao_id'] ?? '',
        ];

        $accessModel = new AccessModel();
        $colabModel = new CollaboratorMealModel();
        $voucherModel = new VoucherModel();
        $rows = $accessModel->reportList($filters);

        $colabRows = $colabModel->listByFilters($filters);
        $voucherRows = $voucherModel->listByFilters($filters);

        $type = $_GET['type'] ?? 'csv';
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

        $data = $_GET['data'] ?? '';
        if ($data === '') {
            $data = date('Y-m-d');
        }

        $accessModel = new AccessModel();
        $rows = $accessModel->dailyMap($data);

        $type = $_GET['type'] ?? 'csv';
        if ($type === 'xlsx') {
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="mapa_diario_uh.xls"');
        } else {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="mapa_diario_uh.csv"');
        }
        $out = fopen('php://output', 'w');
        fputcsv($out, ['uh','cafe','almoco','jantar','tematico','privileged']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['uh_numero'],
                $r['cafe'] ? 'sim' : 'não',
                $r['almoco'] ? 'sim' : 'não',
                $r['jantar'] ? 'sim' : 'não',
                $r['tematico'] ? 'sim' : 'não',
                $r['privileged'] ? 'sim' : 'não',
            ]);
        }
        fclose($out);
        exit;
    }
}
