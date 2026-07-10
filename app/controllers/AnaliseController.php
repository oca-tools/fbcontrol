<?php
declare(strict_types=1);

class AnaliseController extends Controller
{
    private const ABAS = ['visao', 'tematicos', 'kpis'];

    /**
     * Hub de análise (remake visual, etapa 5): Visão geral, Temáticos e KPIs em uma tela,
     * com filtro próprio por aba. Absorve Dashboard Geral, Relatórios Temáticos e KPIs.
     */
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $aba = strtolower(trim((string)($_GET['aba'] ?? 'visao')));
        if (!in_array($aba, self::ABAS, true)) {
            $aba = 'visao';
        }
        $perfil = (string)(Auth::user()['perfil'] ?? '');
        if ($aba === 'kpis' && !in_array($perfil, ['admin', 'gerente'], true)) {
            $aba = 'visao';
        }

        if ($aba === 'tematicos') {
            $dados = $this->montarAbaTematicos();
        } elseif ($aba === 'kpis') {
            $dados = (new KpiOperacionalService())->montarPainelKpis($_GET, Auth::user());
        } else {
            $dados = (new DashboardOperacionalService())->montarDashboardGeral($_GET);
        }

        $dados['aba'] = $aba;
        $this->view('analise/index', $dados);
    }

    /**
     * Monta a aba Temáticos espelhando a antiga tela de Relatórios Temáticos
     * (cuja rota agora redireciona para cá; a exportação continua no controller legado).
     */
    private function montarAbaTematicos(): array
    {
        $reservaModel = new ReservaTematicaModel();
        $turnoModel = new ReservaTematicaTurnoModel();
        $tematicos = (new TematicAccessService())->allTematicRestaurants();

        $filters = TematicAccessService::lerFiltrosRelatorio($tematicos, true);

        $perPage = 20;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $total = $reservaModel->countByFilters($filters);
        $totalPages = max(1, (int)ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $periodDays = $this->diasDoRecorteTematico($filters);
        $timelineGranularity = $this->granularidadeLinhaTematica($periodDays);
        $isLargeQuery = $total > 250 || $periodDays > 31;

        $summary = $reservaModel->summary($filters);
        $base = (int)($summary['pax_reservadas'] ?? 0);
        $taxaComparecimento = $base > 0 ? round(((int)($summary['pax_comparecidas'] ?? 0) / $base) * 100, 1) : 0;

        return [
            'filters' => $filters,
            'summary' => $summary,
            'by_restaurant' => $reservaModel->totalsByRestaurant($filters),
            'by_turno' => $reservaModel->totalsByTurno($filters),
            'by_day' => $reservaModel->totalsByDay($filters, $timelineGranularity),
            'list' => $reservaModel->listByFilters($filters, $perPage, ($page - 1) * $perPage),
            'list_page' => $page,
            'list_total_pages' => $totalPages,
            'list_total' => $total,
            'period_days' => $periodDays,
            'timeline_granularity' => $timelineGranularity,
            'is_large_query' => $isLargeQuery,
            'taxa_comparecimento' => $taxaComparecimento,
            'restaurantes' => $tematicos,
            'turnos' => $turnoModel->all(),
        ];
    }

    private function diasDoRecorteTematico(array $filters): int
    {
        $inicio = (string)($filters['data_inicio'] ?? '');
        $fim = (string)($filters['data_fim'] ?? '');
        if ($inicio !== '' && $fim !== '') {
            $start = DateTimeImmutable::createFromFormat('Y-m-d', $inicio);
            $end = DateTimeImmutable::createFromFormat('Y-m-d', $fim);
            if ($start instanceof DateTimeImmutable && $end instanceof DateTimeImmutable) {
                return max(1, ((int)$start->diff($end)->format('%r%a')) + 1);
            }
        }

        return (string)($filters['data'] ?? '') !== '' ? 1 : 0;
    }

    private function granularidadeLinhaTematica(int $periodDays): string
    {
        if ($periodDays > 180) {
            return 'month';
        }
        if ($periodDays > 45) {
            return 'week';
        }
        return 'day';
    }

}
