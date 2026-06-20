<?php
declare(strict_types=1);

final class ControlDashboardService
{
    private const MINUTOS_TOLERANCIA_FECHAMENTO_TURNO = 10;
    private const LIMITE_REGISTROS_RECENTES = 2000;
    private const REGISTROS_POR_PAGINA = 20;

    private ShiftModel $turnosBuffet;
    private AccessModel $acessosBuffet;
    private ReservaTematicaModel $reservasTematicas;
    private SpecialShiftModel $turnosEspeciais;

    public function __construct(
        ?ShiftModel $turnosBuffet = null,
        ?AccessModel $acessosBuffet = null,
        ?ReservaTematicaModel $reservasTematicas = null,
        ?SpecialShiftModel $turnosEspeciais = null
    ) {
        $this->turnosBuffet = $turnosBuffet ?? new ShiftModel();
        $this->acessosBuffet = $acessosBuffet ?? new AccessModel();
        $this->reservasTematicas = $reservasTematicas ?? new ReservaTematicaModel();
        $this->turnosEspeciais = $turnosEspeciais ?? new SpecialShiftModel();
    }

    public function montarPainelOperacao(string $dataOperacao, int $paginaSolicitada): array
    {
        $this->fecharTurnosExpiradosDaOperacao();

        $turnosAbertosBuffet = $this->turnosBuffet->listActive();
        $restaurantesEmOperacao = $this->listarRestaurantesEmOperacao();
        $indicadoresDoDia = $this->calcularIndicadoresDoDia($dataOperacao);
        $registrosRecentes = $this->listarRegistrosRecentesDoDia($dataOperacao);
        $paginacaoRegistros = $this->paginarRegistrosOperacionais($registrosRecentes, $paginaSolicitada);

        return [
            'today' => $dataOperacao,
            'active_shifts' => $turnosAbertosBuffet,
            'active_restaurants' => $restaurantesEmOperacao,
            'stats_today' => $indicadoresDoDia,
            'recentes' => $paginacaoRegistros['registros_da_pagina'],
            'page' => $paginacaoRegistros['pagina_atual'],
            'total_pages' => $paginacaoRegistros['total_paginas'],
            'total_registros' => $paginacaoRegistros['total_registros'],
        ];
    }

    private function fecharTurnosExpiradosDaOperacao(): void
    {
        // O centro de controle precisa corrigir turnos vencidos mesmo quando a hostess nao acessa o sistema.
        $this->turnosBuffet->autoCloseExpired(self::MINUTOS_TOLERANCIA_FECHAMENTO_TURNO, null);
        $this->turnosEspeciais->autoCloseExpired(self::MINUTOS_TOLERANCIA_FECHAMENTO_TURNO, null);
    }

    private function listarRestaurantesEmOperacao(): array
    {
        $restaurantesPorId = [];

        foreach ($this->turnosBuffet->activeRestaurants() as $restauranteAtivo) {
            $restaurantesPorId[(int)$restauranteAtivo['id']] = $restauranteAtivo;
        }

        return array_values($restaurantesPorId);
    }

    private function calcularIndicadoresDoDia(string $dataOperacao): array
    {
        $indicadoresBuffet = $this->acessosBuffet->statsForDate($dataOperacao);
        $resumoTematico = $this->reservasTematicas->dashboardFinalizadasResumo(['data' => $dataOperacao]);

        return $this->somarReservasTematicasAosIndicadores($indicadoresBuffet, $resumoTematico);
    }

    private function somarReservasTematicasAosIndicadores(array $indicadoresBuffet, array $resumoTematico): array
    {
        $indicadoresBuffet['total_pax'] = (int)($indicadoresBuffet['total_pax'] ?? 0)
            + (int)($resumoTematico['total_pax'] ?? 0);

        $indicadoresBuffet['total_acessos'] = (int)($indicadoresBuffet['total_acessos'] ?? 0)
            + (int)($resumoTematico['total_finalizadas'] ?? 0);

        return $indicadoresBuffet;
    }

    private function listarRegistrosRecentesDoDia(string $dataOperacao): array
    {
        $registrosBuffet = $this->acessosBuffet->recentAll(self::LIMITE_REGISTROS_RECENTES, $dataOperacao);
        $registrosTematicos = $this->reservasTematicas->dashboardFinalizadasRecent(
            self::LIMITE_REGISTROS_RECENTES,
            ['data' => $dataOperacao]
        );

        return $this->ordenarRegistrosMaisRecentesPrimeiro(array_merge($registrosBuffet, $registrosTematicos));
    }

    private function ordenarRegistrosMaisRecentesPrimeiro(array $registrosOperacionais): array
    {
        usort($registrosOperacionais, static function (array $registroAtual, array $proximoRegistro): int {
            return strcmp(
                (string)($proximoRegistro['criado_em'] ?? ''),
                (string)($registroAtual['criado_em'] ?? '')
            );
        });

        return $registrosOperacionais;
    }

    private function paginarRegistrosOperacionais(array $registrosOperacionais, int $paginaSolicitada): array
    {
        $totalRegistros = count($registrosOperacionais);
        $totalPaginas = max(1, (int)ceil($totalRegistros / self::REGISTROS_POR_PAGINA));
        $paginaAtual = min(max(1, $paginaSolicitada), $totalPaginas);
        $posicaoInicial = ($paginaAtual - 1) * self::REGISTROS_POR_PAGINA;

        return [
            'registros_da_pagina' => array_slice($registrosOperacionais, $posicaoInicial, self::REGISTROS_POR_PAGINA),
            'pagina_atual' => $paginaAtual,
            'total_paginas' => $totalPaginas,
            'total_registros' => $totalRegistros,
        ];
    }
}
