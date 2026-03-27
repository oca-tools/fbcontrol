<?php
class ControlController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $shiftModel = new ShiftModel();
        $accessModel = new AccessModel();
        $reservaTematicaModel = new ReservaTematicaModel();
        $specialShiftModel = new SpecialShiftModel();

        // Garante fechamento de turnos expirados independentemente do login da hostess.
        $graceMinutes = 10;
        $shiftModel->autoCloseExpired($graceMinutes, null);
        $specialShiftModel->autoCloseExpired($graceMinutes, null);

        $today = date('Y-m-d');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;

        $buffetShifts = $shiftModel->listActive();
        $activeRestaurants = $shiftModel->activeRestaurants();
        $restMap = [];
        foreach ($activeRestaurants as $r) {
            $restMap[$r['id']] = $r;
        }

        $stats = $accessModel->statsForDate($today);
        $temResumo = $reservaTematicaModel->dashboardFinalizadasResumo(['data' => $today]);
        $stats['total_pax'] = (int)($stats['total_pax'] ?? 0) + (int)($temResumo['total_pax'] ?? 0);
        $stats['total_acessos'] = (int)($stats['total_acessos'] ?? 0) + (int)($temResumo['total_finalizadas'] ?? 0);

        $recentesBuffet = $accessModel->recentAll(2000, $today);
        $recentesTematicos = $reservaTematicaModel->dashboardFinalizadasRecent(2000, ['data' => $today]);
        $recentesTodos = array_merge($recentesBuffet, $recentesTematicos);
        usort($recentesTodos, static function ($a, $b) {
            return strcmp((string)($b['criado_em'] ?? ''), (string)($a['criado_em'] ?? ''));
        });
        $totalRegistros = count($recentesTodos);
        $totalPages = max(1, (int)ceil($totalRegistros / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;
        $recentes = array_slice($recentesTodos, $offset, $perPage);

        $this->view('control/index', [
            'today' => $today,
            'active_shifts' => $buffetShifts,
            'active_restaurants' => array_values($restMap),
            'stats_today' => $stats,
            'recentes' => $recentes,
            'page' => $page,
            'total_pages' => $totalPages,
            'total_registros' => $totalRegistros,
        ]);
    }
}
