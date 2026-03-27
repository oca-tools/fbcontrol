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
            'status' => $_GET['status'] ?? '',
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
        $biTotal = count($list);
        $biTotalPages = max(1, (int)ceil($biTotal / $perPageBi));
        if ($biPage > $biTotalPages) {
            $biPage = $biTotalPages;
        }
        $listPaged = array_slice($list, ($biPage - 1) * $perPageBi, $perPageBi);

        $perPageColab = 20;
        $colabPage = max(1, (int)($_GET['colab_page'] ?? 1));
        $colabTotal = count($colaboradores);
        $colabTotalPages = max(1, (int)ceil($colabTotal / $perPageColab));
        if ($colabPage > $colabTotalPages) {
            $colabPage = $colabTotalPages;
        }
        $colaboradoresPaged = array_slice($colaboradores, ($colabPage - 1) * $perPageColab, $perPageColab);

        $perPageVoucher = 20;
        $voucherPage = max(1, (int)($_GET['voucher_page'] ?? 1));
        $voucherTotal = count($vouchers);
        $voucherTotalPages = max(1, (int)ceil($voucherTotal / $perPageVoucher));
        if ($voucherPage > $voucherTotalPages) {
            $voucherPage = $voucherTotalPages;
        }
        $vouchersPaged = array_slice($vouchers, ($voucherPage - 1) * $perPageVoucher, $perPageVoucher);

        $this->view('reports/index', [
            'filters' => $filters,
            'restaurantes' => $restaurantModel->all(),
            'operacoes' => $operationModel->all(),
            'list' => $list,
            'list_paged' => $listPaged,
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
            'colaboradores' => $colaboradores,
            'colaboradores_paged' => $colaboradoresPaged,
            'colab_page' => $colabPage,
            'colab_total_pages' => $colabTotalPages,
            'colab_total' => $colabTotal,
            'vouchers' => $vouchers,
            'vouchers_paged' => $vouchersPaged,
            'voucher_page' => $voucherPage,
            'voucher_total_pages' => $voucherTotalPages,
            'voucher_total' => $voucherTotal,
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
            'status' => $_GET['status'] ?? '',
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

        $filters = [
            'data' => $_GET['data'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? '',
            'uh_numero' => trim($_GET['uh_numero'] ?? ''),
            'restaurante_id' => $_GET['restaurante_id'] ?? '',
            'operacao_id' => $_GET['operacao_id'] ?? '',
            'status' => $_GET['status'] ?? '',
        ];

        $rows = (new AccessModel())->reportList($filters);
        $type = $_GET['type'] ?? 'csv';
        if ($type === 'xlsx') {
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="base_bi.xls"');
        } else {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="base_bi.csv"');
        }
        $out = fopen('php://output', 'w');
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
            'data' => $_GET['data'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? '',
            'restaurante_id' => $_GET['restaurante_id'] ?? '',
            'operacao_id' => $_GET['operacao_id'] ?? '',
        ];

        $rows = (new CollaboratorMealModel())->listByFilters($filters);
        $type = $_GET['type'] ?? 'csv';
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
            'data' => $_GET['data'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? '',
            'restaurante_id' => $_GET['restaurante_id'] ?? '',
            'operacao_id' => $_GET['operacao_id'] ?? '',
        ];

        $rows = (new VoucherModel())->listByFilters($filters);
        $type = $_GET['type'] ?? 'csv';
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
}
