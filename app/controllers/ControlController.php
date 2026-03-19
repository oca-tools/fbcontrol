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

        $today = date('Y-m-d');

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

        $recentes = $accessModel->recentAll(12, $today);
        $recentesTematicos = $reservaTematicaModel->dashboardFinalizadasRecent(12, ['data' => $today]);
        $recentes = $this->mergeRecentes($recentes, $recentesTematicos, 12);

        $this->view('control/index', [
            'today' => $today,
            'active_shifts' => $buffetShifts,
            'active_restaurants' => array_values($restMap),
            'stats_today' => $stats,
            'recentes' => $recentes,
        ]);
    }

    private function mergeRecentes(array $base, array $extra, int $limit): array
    {
        $rows = array_merge($base, $extra);
        usort($rows, static function ($a, $b) {
            return strcmp((string)($b['criado_em'] ?? ''), (string)($a['criado_em'] ?? ''));
        });
        return array_slice($rows, 0, $limit);
    }
}
