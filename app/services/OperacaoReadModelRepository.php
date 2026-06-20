<?php
declare(strict_types=1);

final class OperacaoReadModelRepository
{
    private AccessModel $acessos;
    private RestaurantModel $restaurantes;
    private OperationModel $operacoes;
    private ReservaTematicaModel $reservasTematicas;
    private CollaboratorMealModel $refeicoesColaboradores;
    private VoucherModel $vouchers;
    private KpiOccupancyModel $ocupacao;

    public function __construct()
    {
        $this->acessos = new AccessModel();
        $this->restaurantes = new RestaurantModel();
        $this->operacoes = new OperationModel();
        $this->reservasTematicas = new ReservaTematicaModel();
        $this->refeicoesColaboradores = new CollaboratorMealModel();
        $this->vouchers = new VoucherModel();
        $this->ocupacao = new KpiOccupancyModel();
    }

    public function listarRestaurantes(): array
    {
        return $this->restaurantes->all();
    }

    public function buscarRestaurante(int $restauranteId): ?array
    {
        return $this->restaurantes->find($restauranteId);
    }

    public function listarOperacoes(): array
    {
        return $this->operacoes->all();
    }

    public function listarOperacoesBuffet(): array
    {
        return $this->operacoes->allBuffet();
    }

    public function buscarOperacao(int $operacaoId): ?array
    {
        return $this->operacoes->find($operacaoId);
    }

    public function indicadoresDashboard(array $filters): array
    {
        return $this->acessos->dashboard($filters);
    }

    public function fluxoPorHorario(array $filters): array
    {
        return $this->acessos->dashboardFlow($filters);
    }

    public function acessosRecentes(array $filters, int $limite = 15): array
    {
        return $this->acessos->recentAll(
            $limite,
            (string)($filters['data'] ?? ''),
            (string)($filters['data_inicio'] ?? ''),
            (string)($filters['data_fim'] ?? ''),
            (string)($filters['status'] ?? '')
        );
    }

    public function acessosRecentesPorRestaurante(int $restauranteId, array $filters, int $limite = 15): array
    {
        return $this->acessos->recentByRestaurant(
            $restauranteId,
            $limite,
            (string)($filters['data'] ?? ''),
            (string)($filters['data_inicio'] ?? ''),
            (string)($filters['data_fim'] ?? ''),
            (string)($filters['status'] ?? '')
        );
    }

    public function paxTematicoFinalizado(array $filters): array
    {
        return $this->reservasTematicas->dashboardFinalizadasPax($filters);
    }

    public function fluxoTematicoFinalizado(array $filters): array
    {
        return $this->reservasTematicas->dashboardFinalizadasFluxo($filters);
    }

    public function recentesTematicosFinalizados(array $filters, int $limite = 15): array
    {
        return $this->reservasTematicas->dashboardFinalizadasRecent($limite, $filters);
    }

    public function indicadoresTematicos(array $filters): array
    {
        return $this->reservasTematicas->dashboardStats($filters);
    }

    public function totaisTematicosPorTurno(array $filters): array
    {
        return $this->reservasTematicas->totalsByTurno($filters);
    }

    public function recentesTematicosPorRestaurante(int $restauranteId, array $filters, int $limite = 15): array
    {
        return $this->reservasTematicas->recentByRestaurant(
            $restauranteId,
            (string)($filters['data'] ?? ''),
            (string)($filters['data_inicio'] ?? ''),
            (string)($filters['data_fim'] ?? ''),
            $limite
        );
    }

    public function resumoKpi(array $filters): array
    {
        return $this->acessos->kpiSummary($filters);
    }

    public function rankingOperadores(array $filters, int $limite = 10): array
    {
        return $this->acessos->kpiOperatorRanking($filters, $limite);
    }

    public function mixOperacoes(array $filters): array
    {
        return $this->acessos->kpiOperationMix($filters);
    }

    public function mixRestaurantes(array $filters): array
    {
        return $this->acessos->kpiRestaurantMix($filters);
    }

    public function fluxoHorarioPorOperacao(array $filters): array
    {
        return $this->acessos->kpiHourlyOperationFlow($filters);
    }

    public function paxBuffet(array $filters): int
    {
        return $this->acessos->kpiBuffetPax($filters);
    }

    public function buffetDiarioPorOperacao(string $dataInicio, string $dataFim): array
    {
        return $this->acessos->kpiBuffetDailyOperationRange($dataInicio, $dataFim);
    }

    public function ocupacaoPorData(string $data): ?array
    {
        return $this->ocupacao->getByDate($data);
    }

    public function historicoOcupacao(string $dataInicio, string $dataFim, int $limite = 120): array
    {
        return $this->ocupacao->history($dataInicio, $dataFim, $limite);
    }

    public function tendenciaDiaria(array $filters): array
    {
        return $this->acessos->kpiDailyTrend($filters);
    }

    public function jornadaUh(string $uhNumero, array $filters): array
    {
        return $this->acessos->uhJourney(
            $uhNumero,
            (string)($filters['data'] ?? ''),
            (string)($filters['data_inicio'] ?? ''),
            (string)($filters['data_fim'] ?? '')
        );
    }

    public function resumoUh(string $uhNumero, array $filters): array
    {
        return $this->acessos->uhSummary(
            $uhNumero,
            (string)($filters['data'] ?? ''),
            (string)($filters['data_inicio'] ?? ''),
            (string)($filters['data_fim'] ?? '')
        );
    }

    public function mapaDiarioUh(string $data): array
    {
        return $this->acessos->dailyMap($data);
    }

    public function contarRegistrosBi(array $filters): int
    {
        return ($filters['status'] ?? '') === 'multiplo'
            ? $this->acessos->reportMultipleAccessGroupsCount($filters)
            : $this->acessos->reportListCount($filters);
    }

    public function listarRegistrosBi(array $filters, int $limite, int $offset): array
    {
        return ($filters['status'] ?? '') === 'multiplo'
            ? $this->acessos->reportMultipleAccessGroups($filters, $limite, $offset)
            : $this->acessos->reportList($filters, $limite, $offset);
    }

    public function contarAcessosRelatorio(array $filters): int
    {
        return $this->acessos->reportListCount($filters);
    }

    public function exportarAcessosRelatorio(array $filters, callable $callback): int
    {
        return $this->acessos->exportReportRows($filters, $callback);
    }

    public function contarGruposMultiplosAcessos(array $filters): int
    {
        return $this->acessos->reportMultipleAccessGroupsCount($filters);
    }

    public function exportarGruposMultiplosAcessos(array $filters, callable $callback): int
    {
        return $this->acessos->exportMultipleAccessGroups($filters, $callback);
    }

    public function contarRefeicoesColaboradores(array $filters): int
    {
        return $this->refeicoesColaboradores->countByFilters($filters);
    }

    public function listarRefeicoesColaboradores(array $filters, int $limite, int $offset): array
    {
        return $this->refeicoesColaboradores->listByFilters($filters, $limite, $offset);
    }

    public function exportarRefeicoesColaboradores(array $filters, callable $callback): int
    {
        return $this->refeicoesColaboradores->exportByFilters($filters, $callback);
    }

    public function contarVouchers(array $filters): int
    {
        return $this->vouchers->countByFilters($filters);
    }

    public function listarVouchers(array $filters, int $limite, int $offset): array
    {
        return $this->vouchers->listByFilters($filters, $limite, $offset);
    }

    public function listarVouchersParaAnexos(array $filters): array
    {
        return $this->vouchers->listByFilters($filters);
    }

    public function exportarVouchers(array $filters, callable $callback): int
    {
        return $this->vouchers->exportByFilters($filters, $callback);
    }
}
