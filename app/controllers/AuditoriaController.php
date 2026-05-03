<?php
class AuditoriaController extends Controller
{
    private function paginate(array $rows, string $param, int $perPage = 20): array
    {
        $page = max(1, (int)($_GET[$param] ?? 1));
        $total = count($rows);
        $totalPages = max(1, (int)ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        return [
            'rows' => array_slice($rows, ($page - 1) * $perPage, $perPage),
            'page' => $page,
            'total_pages' => $totalPages,
            'total' => $total,
            'param' => $param,
        ];
    }

    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);

        $filters = [
            'data' => sanitize_date_param($_GET['data'] ?? '', ''),
            'data_inicio' => sanitize_date_param($_GET['data_inicio'] ?? ''),
            'data_fim' => sanitize_date_param($_GET['data_fim'] ?? ''),
            'usuario_id' => sanitize_int_param($_GET['usuario_id'] ?? ''),
            'tabela' => trim((string)($_GET['tabela'] ?? '')),
        ];
        if ($filters['data'] === '' && $filters['data_inicio'] === '' && $filters['data_fim'] === '') {
            $filters['data'] = date('Y-m-d');
        }

        $model = new AuditLogModel();
        $generalLogs = $model->generalLogs($filters);
        $thematicLogs = $model->thematicLogs($filters);
        $shiftLogs = $model->shiftLogs($filters);

        $this->view('auditoria/index', [
            'filters' => $filters,
            'usuarios' => $model->users(),
            'general_logs' => $this->paginate($generalLogs, 'general_page'),
            'thematic_logs' => $this->paginate($thematicLogs, 'thematic_page'),
            'shift_logs' => $this->paginate($shiftLogs, 'shift_page'),
        ]);
    }
}
