<?php
declare(strict_types=1);

final class DashboardOperacionalService
{
    public const STATUS_FILTERS = FiltroOperacionalService::STATUS_FILTERS;

    private OperacaoReadModelRepository $operacaoReadModelRepository;
    private PerfilRestauranteService $perfilRestaurante;

    public function __construct(?OperacaoReadModelRepository $operacaoReadModelRepository = null)
    {
        $this->operacaoReadModelRepository = $operacaoReadModelRepository ?? new OperacaoReadModelRepository();
        $this->perfilRestaurante = new PerfilRestauranteService($this->operacaoReadModelRepository);
    }

    /**
     * Agrupa totais, fluxo por horário e últimos atendimentos para leitura executiva da operação de salão.
     *
     * @return array{
     *     filters: array<string, mixed>,
     *     flow_filters: array<string, mixed>,
     *     restaurantes: array<int, array<string, mixed>>,
     *     operacoes: array<int, array<string, mixed>>,
     *     flow_operacoes: array<int, array<string, mixed>>,
     *     stats: array<string, mixed>,
     *     recentes: array<int, array<string, mixed>>
     * }
     */
    public function montarDashboardGeral(array $query): array
    {
        $filters = $this->lerFiltrosDoPainel($query, true);
        $flowFilters = $this->lerFiltrosDeFluxo($query, $filters);
        $operacoesDisponiveis = $this->operacoesDisponiveisParaFiltro($filters);

        $indicadoresDePerformance = $this->operacaoReadModelRepository->indicadoresDashboard($filters);
        $indicadoresDePerformance['fluxo_horario'] = $this->operacaoReadModelRepository->fluxoPorHorario($flowFilters);

        $includeTematico = $this->deveSomarOperacaoTematica($filters);
        if ($includeTematico) {
            $this->somarPaxTematicoAosIndicadores($indicadoresDePerformance, $filters);
        }

        if ($this->deveSomarOperacaoTematica($flowFilters)) {
            $indicadoresDePerformance['fluxo_horario'] = $this->mergeTotalsByName(
                $indicadoresDePerformance['fluxo_horario'] ?? [],
                $this->operacaoReadModelRepository->fluxoTematicoFinalizado($flowFilters),
                'hora'
            );
        }

        $ultimosAtendimentos = $this->operacaoReadModelRepository->acessosRecentes(
            $filters,
            InteligenciaOperacionalConstants::DEFAULT_RECENT_LIMIT
        );
        if ($includeTematico) {
            $ultimosAtendimentos = $this->mergeRecentes(
                $ultimosAtendimentos,
                $this->recentesTematicosPermitidosPeloFiltro($filters),
                InteligenciaOperacionalConstants::DEFAULT_RECENT_LIMIT
            );
        }

        return [
            'filters' => $filters,
            'flow_filters' => $flowFilters,
            'restaurantes' => $this->operacaoReadModelRepository->listarRestaurantes(),
            'operacoes' => $operacoesDisponiveis,
            'flow_operacoes' => $this->operacaoReadModelRepository->listarOperacoes(),
            'stats' => $indicadoresDePerformance,
            'recentes' => $ultimosAtendimentos,
        ];
    }

    /**
     * Monta a visão de restaurante para identificar gargalos, concentração de PAX e reservas temáticas do turno.
     */
    public function montarDashboardRestaurante(int $restauranteId, array $query): ServiceResult
    {
        if ($restauranteId <= 0) {
            return ServiceResult::failure('restaurante_invalido', 'Restaurante inválido.');
        }

        $filters = $this->lerFiltrosDoPainel(array_merge($query, ['restaurante_id' => $restauranteId]), true);
        $filters['restaurante_id'] = $restauranteId;

        $restaurante = $this->operacaoReadModelRepository->buscarRestaurante($restauranteId);
        $podeFiltrarOperacao = $this->perfilRestaurante->permiteFiltroOperacao($restaurante);
        $operacoesDisponiveis = $podeFiltrarOperacao
            ? $this->perfilRestaurante->operacoesParaPainelRestaurante($restaurante)
            : [];
        if (!$podeFiltrarOperacao) {
            $filters['operacao_id'] = '';
        }

        $operacaoSelecionada = !empty($filters['operacao_id'])
            ? $this->operacaoReadModelRepository->buscarOperacao((int)$filters['operacao_id'])
            : null;
        $modoTematico = $this->perfilRestaurante->operaComoTematico($restaurante, $operacaoSelecionada);
        $tematicoFilters = [
            'data' => $filters['data'],
            'data_inicio' => $filters['data_inicio'],
            'data_fim' => $filters['data_fim'],
            'restaurante_id' => $restauranteId,
        ];

        return ServiceResult::success('Dashboard de restaurante montado.', [
            'filters' => $filters,
            'restaurante' => $restaurante,
            'operacoes' => $operacoesDisponiveis,
            'show_operacao_filter' => $podeFiltrarOperacao,
            'stats' => $this->operacaoReadModelRepository->indicadoresDashboard($filters),
            'recentes' => $this->operacaoReadModelRepository->acessosRecentesPorRestaurante(
                $restauranteId,
                $filters,
                InteligenciaOperacionalConstants::DEFAULT_RECENT_LIMIT
            ),
            'tematico_mode' => $modoTematico,
            'tematico_stats' => $modoTematico ? $this->operacaoReadModelRepository->indicadoresTematicos($tematicoFilters) : [],
            'tematico_turnos' => $modoTematico ? $this->operacaoReadModelRepository->totaisTematicosPorTurno($tematicoFilters) : [],
            'tematico_recentes' => $modoTematico
                ? $this->operacaoReadModelRepository->recentesTematicosPorRestaurante(
                    $restauranteId,
                    $filters,
                    InteligenciaOperacionalConstants::DEFAULT_RECENT_LIMIT
                )
                : [],
        ]);
    }

    private function lerFiltrosDoPainel(array $query, bool $dataPadrao): array
    {
        $filters = FiltroOperacionalService::lerFiltrosBase($query);
        if ($dataPadrao) {
            $filters = FiltroOperacionalService::aplicarDataPadraoSeVazio($filters, date('Y-m-d'));
        }
        return $filters;
    }

    private function lerFiltrosDeFluxo(array $query, array $filters): array
    {
        $hasFlowFilterRequest = array_key_exists('fluxo_restaurante_id', $query)
            || array_key_exists('fluxo_operacao_id', $query);
        $flowFilters = $filters;
        $flowFilters['restaurante_id'] = $hasFlowFilterRequest
            ? sanitize_int_param($query['fluxo_restaurante_id'] ?? '')
            : $filters['restaurante_id'];
        $flowFilters['operacao_id'] = $hasFlowFilterRequest
            ? sanitize_int_param($query['fluxo_operacao_id'] ?? '')
            : $filters['operacao_id'];
        return $flowFilters;
    }

    private function operacoesDisponiveisParaFiltro(array $filters): array
    {
        $restaurante = !empty($filters['restaurante_id'])
            ? $this->operacaoReadModelRepository->buscarRestaurante((int)$filters['restaurante_id'])
            : null;

        return $this->perfilRestaurante->operacoesParaFiltroGeral($restaurante);
    }

    private function deveSomarOperacaoTematica(array $filters): bool
    {
        if (in_array($filters['status'] ?? '', ['nao_informado', 'day_use'], true)) {
            return false;
        }

        if (!empty($filters['operacao_id'])) {
            $operacao = $this->operacaoReadModelRepository->buscarOperacao((int)$filters['operacao_id']);
            return $this->perfilRestaurante->operacaoEhTematica($operacao);
        }

        return true;
    }

    private function somarPaxTematicoAosIndicadores(array &$indicadoresDePerformance, array $filters): void
    {
        $contribuicaoTematica = $this->operacaoReadModelRepository->paxTematicoFinalizado($filters);
        $totalPaxTematico = (int)($contribuicaoTematica['total_pax'] ?? 0);
        $indicadoresDePerformance['totais_restaurante'] = $this->mergeTotalsByName(
            $indicadoresDePerformance['totais_restaurante'] ?? [],
            $contribuicaoTematica['by_restaurante'] ?? []
        );
        if ($totalPaxTematico > 0) {
            $indicadoresDePerformance['totais_operacao'] = $this->mergeTotalsByName(
                $indicadoresDePerformance['totais_operacao'] ?? [],
                [['nome' => 'Temático', 'total_pax' => $totalPaxTematico]]
            );
        }
        $indicadoresDePerformance['total_pax'] = (int)($indicadoresDePerformance['total_pax'] ?? 0) + $totalPaxTematico;
    }

    private function recentesTematicosPermitidosPeloFiltro(array $filters): array
    {
        $statusFilter = (string)($filters['status'] ?? '');
        if ($statusFilter !== '' && $statusFilter !== 'ok') {
            return [];
        }

        return $this->operacaoReadModelRepository->recentesTematicosFinalizados(
            $filters,
            InteligenciaOperacionalConstants::DEFAULT_RECENT_LIMIT
        );
    }

    private function mergeTotalsByName(array $base, array $extra, string $nameKey = 'nome'): array
    {
        $map = [];
        foreach ($base as $row) {
            $name = $this->normalizeAggregateName((string)($row[$nameKey] ?? ''), $nameKey);
            if ($name === '') {
                continue;
            }
            $map[$name] = [$nameKey => $name, 'total_pax' => (int)($row['total_pax'] ?? 0)];
        }

        foreach ($extra as $row) {
            $name = $this->normalizeAggregateName((string)($row[$nameKey] ?? ''), $nameKey);
            if ($name === '') {
                continue;
            }
            if (!isset($map[$name])) {
                $map[$name] = [$nameKey => $name, 'total_pax' => 0];
            }
            $map[$name]['total_pax'] += (int)($row['total_pax'] ?? 0);
        }

        $result = array_values($map);
        usort($result, static fn($a, $b): int => (int)$b['total_pax'] <=> (int)$a['total_pax']);
        return $result;
    }

    private function normalizeAggregateName(string $name, string $nameKey): string
    {
        $clean = trim(normalize_mojibake($name));
        if ($clean === '' || $nameKey !== 'nome') {
            return $clean;
        }

        $low = mb_strtolower($clean, 'UTF-8');
        $flat = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $low);
        $flat = is_string($flat) ? preg_replace('/[^a-z0-9 ]/', '', $flat) : $low;
        $flat = trim((string)$flat);

        if (strpos($flat, 'tematic') !== false) {
            return 'Temático';
        }
        if (strpos($flat, 'cafe') !== false) {
            return 'Café';
        }
        if (strpos($flat, 'almoco') !== false) {
            return 'Almoço';
        }
        return $clean;
    }

    private function mergeRecentes(array $base, array $extra, int $limit): array
    {
        $rows = array_merge($base, $extra);
        usort($rows, static fn($a, $b): int => strcmp((string)($b['criado_em'] ?? ''), (string)($a['criado_em'] ?? '')));
        return array_slice($rows, 0, $limit);
    }
}
