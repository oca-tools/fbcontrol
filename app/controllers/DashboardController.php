<?php
class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $filters = [
            'data' => $_GET['data'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? '',
            'restaurante_id' => $_GET['restaurante_id'] ?? '',
            'operacao_id' => $_GET['operacao_id'] ?? '',
            'status' => $_GET['status'] ?? '',
        ];
        if ($filters['data'] === '' && $filters['data_inicio'] === '' && $filters['data_fim'] === '') {
            $filters['data'] = date('Y-m-d');
        }

        $restaurantModel = new RestaurantModel();
        $operationModel = new OperationModel();
        $accessModel = new AccessModel();
        $reservaTematicaModel = new ReservaTematicaModel();
        $operacoes = $operationModel->all();
        if (!empty($filters['restaurante_id'])) {
            $rest = $restaurantModel->find((int)$filters['restaurante_id']);
            if ($rest && stripos($rest['nome'], 'Corais') !== false) {
                $operacoes = $operationModel->allBuffet();
            }
        }

        $stats = $accessModel->dashboard($filters);

        $includeTematico = !in_array($filters['status'] ?? '', ['nao_informado', 'day_use'], true);
        if (!empty($filters['operacao_id'])) {
            $selectedOp = $operationModel->find((int)$filters['operacao_id']);
            $selectedName = mb_strtolower($selectedOp['nome'] ?? '', 'UTF-8');
            $isTematico = strpos($selectedName, 'temático') !== false || strpos($selectedName, 'tematico') !== false;
            $includeTematico = $isTematico;
        }

        if ($includeTematico) {
            $tematicoContribution = $reservaTematicaModel->dashboardFinalizadasPax($filters);
            $tematicoTotalPax = (int)($tematicoContribution['total_pax'] ?? 0);
            $stats['totais_restaurante'] = $this->mergeTotalsByName(
                $stats['totais_restaurante'] ?? [],
                $tematicoContribution['by_restaurante'] ?? []
            );
            if ($tematicoTotalPax > 0) {
                $stats['totais_operacao'] = $this->mergeTotalsByName(
                    $stats['totais_operacao'] ?? [],
                    [['nome' => 'Tematico', 'total_pax' => $tematicoTotalPax]]
                );
            }
            $stats['total_pax'] = (int)($stats['total_pax'] ?? 0) + $tematicoTotalPax;

            $stats['fluxo_horario'] = $this->mergeTotalsByName(
                $stats['fluxo_horario'] ?? [],
                $reservaTematicaModel->dashboardFinalizadasFluxo($filters),
                'hora'
            );
        }

        $recentes = $accessModel->recentAll(
            15,
            $filters['data'],
            $filters['data_inicio'],
            $filters['data_fim'],
            (string)$filters['status']
        );
        if ($includeTematico) {
            $recentesTematicos = $reservaTematicaModel->dashboardFinalizadasRecent(
                15,
                $filters
            );
            $statusFilter = (string)($filters['status'] ?? '');
            if ($statusFilter !== '' && $statusFilter !== 'ok') {
                $recentesTematicos = [];
            }
            $recentes = $this->mergeRecentes($recentes, $recentesTematicos, 15);
        }

        $this->view('dashboard/general', [
            'filters' => $filters,
            'restaurantes' => $restaurantModel->all(),
            'operacoes' => $operacoes,
            'stats' => $stats,
            'recentes' => $recentes,
        ]);
    }

    public function restaurant(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $restauranteId = (int)($_GET['id'] ?? 0);
        if ($restauranteId <= 0) {
            $this->redirect('/?r=dashboard/index');
        }

        $filters = [
            'data' => $_GET['data'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? '',
            'restaurante_id' => $restauranteId,
            'operacao_id' => $_GET['operacao_id'] ?? '',
            'status' => $_GET['status'] ?? '',
        ];
        if ($filters['data'] === '' && $filters['data_inicio'] === '' && $filters['data_fim'] === '') {
            $filters['data'] = date('Y-m-d');
        }

        $restaurantModel = new RestaurantModel();
        $operationModel = new OperationModel();
        $accessModel = new AccessModel();
        $restaurante = $restaurantModel->find($restauranteId);
        $restName = $restaurante['nome'] ?? '';
        $allowOperacaoFilter = ($restName === 'Restaurante Corais' || stripos($restName, 'La Brasa') !== false);
        $operacoes = [];
        if ($allowOperacaoFilter) {
            if ($restName === 'Restaurante Corais') {
                $operacoes = $operationModel->allBuffet();
            } elseif (stripos($restName, 'La Brasa') !== false) {
                $operacoes = array_filter($operationModel->all(), static function ($op) {
                    $name = mb_strtolower($op['nome'] ?? '', 'UTF-8');
                    return strpos($name, 'almoço') !== false || strpos($name, 'almoco') !== false || strpos($name, 'temático') !== false || strpos($name, 'tematico') !== false;
                });
                $operacoes = array_values($operacoes);
            } else {
                $operacoes = $operationModel->all();
            }
        } else {
            $filters['operacao_id'] = '';
        }

        $tematicoMode = false;
        $tematicoStats = [];
        $tematicoTurnos = [];
        $tematicoRecentes = [];
        $operationInfo = null;
        if (!empty($filters['operacao_id'])) {
            $operationInfo = $operationModel->find((int)$filters['operacao_id']);
        }
        if (stripos($restName, 'Giardino') !== false || stripos($restName, 'IX') !== false) {
            $tematicoMode = true;
        }
        if (stripos($restName, 'La Brasa') !== false && $operationInfo) {
            $opName = mb_strtolower($operationInfo['nome'] ?? '', 'UTF-8');
            if (strpos($opName, 'temático') !== false || strpos($opName, 'tematico') !== false) {
                $tematicoMode = true;
            }
        }

        if ($tematicoMode) {
            $reservaModel = new ReservaTematicaModel();
            $temFilters = [
                'data' => $filters['data'],
                'data_inicio' => $filters['data_inicio'],
                'data_fim' => $filters['data_fim'],
                'restaurante_id' => $restauranteId,
            ];
            $tematicoStats = $reservaModel->dashboardStats($temFilters);
            $tematicoTurnos = $reservaModel->totalsByTurno($temFilters);
            $tematicoRecentes = $reservaModel->recentByRestaurant($restauranteId, $filters['data'], $filters['data_inicio'], $filters['data_fim'], 15);
        }

        $this->view('dashboard/restaurant', [
            'filters' => $filters,
            'restaurante' => $restaurante,
            'operacoes' => $operacoes,
            'show_operacao_filter' => $allowOperacaoFilter,
            'stats' => $accessModel->dashboard($filters),
            'recentes' => $accessModel->recentByRestaurant(
                $restauranteId,
                15,
                $filters['data'],
                $filters['data_inicio'],
                $filters['data_fim'],
                (string)$filters['status']
            ),
            'tematico_mode' => $tematicoMode,
            'tematico_stats' => $tematicoStats,
            'tematico_turnos' => $tematicoTurnos,
            'tematico_recentes' => $tematicoRecentes,
        ]);
    }

    private function mergeTotalsByName(array $base, array $extra, string $nameKey = 'nome'): array
    {
        $map = [];
        foreach ($base as $row) {
            $name = (string)($row[$nameKey] ?? '');
            if ($name === '') {
                continue;
            }
            $map[$name] = [
                $nameKey => $name,
                'total_pax' => (int)($row['total_pax'] ?? 0),
            ];
        }

        foreach ($extra as $row) {
            $name = (string)($row[$nameKey] ?? '');
            if ($name === '') {
                continue;
            }
            if (!isset($map[$name])) {
                $map[$name] = [$nameKey => $name, 'total_pax' => 0];
            }
            $map[$name]['total_pax'] += (int)($row['total_pax'] ?? 0);
        }

        $result = array_values($map);
        usort($result, static function ($a, $b) {
            return (int)$b['total_pax'] <=> (int)$a['total_pax'];
        });
        return $result;
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
