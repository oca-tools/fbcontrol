<?php
declare(strict_types=1);

final class KpiOperacionalService
{
    public const STATUS_FILTERS = ['duplicado', 'fora_horario', 'multiplo', 'ok', 'nao_informado', 'day_use'];

    private OperacaoReadModelRepository $operacaoReadModelRepository;

    public function __construct(?OperacaoReadModelRepository $operacaoReadModelRepository = null)
    {
        $this->operacaoReadModelRepository = $operacaoReadModelRepository ?? new OperacaoReadModelRepository();
    }

    /**
     * Consolida KPIs de ocupação, mix operacional, fluxo horário e alertas para orientar a gestão de A&B.
     *
     * @return array{
     *     filters: array<string, mixed>,
     *     flow_filters: array<string, mixed>,
     *     summary: array<string, mixed>,
     *     operator_ranking: array<int, array<string, mixed>>,
     *     operation_mix: array<int, array<string, mixed>>,
     *     restaurant_mix: array<int, array<string, mixed>>,
     *     hourly_operation_flow: array<int, array<string, mixed>>,
     *     tematicos: array<string, mixed>,
     *     insights: array<int, array<string, string>>,
     *     occupancy_timeline: array<int, array<string, mixed>>
     * }
     */
    public function montarPainelKpis(array $query, array $usuario): array
    {
        $filters = $this->lerFiltros($query);
        if ($filters['data'] === '' && $filters['data_inicio'] === '' && $filters['data_fim'] === '') {
            $filters['data_inicio'] = date(
                'Y-m-d',
                strtotime('-' . InteligenciaOperacionalConstants::DEFAULT_KPI_RANGE_DAYS . ' days')
            );
            $filters['data_fim'] = date('Y-m-d');
        }

        $flowFilters = $this->lerFiltrosFluxo($query, $filters);
        $resumoOperacional = $this->operacaoReadModelRepository->resumoKpi($filters);
        $indicadoresTematicos = $this->operacaoReadModelRepository->indicadoresTematicos($filters);
        $taxaNoShow = $this->calcularTaxaNoShowTematico($indicadoresTematicos);
        $taxaComparecimentoTematico = $this->calcularTaxaComparecimentoTematico($indicadoresTematicos);

        $dataOcupacao = $this->resolverDataOcupacao($query, $filters);
        $ocupacao = $this->operacaoReadModelRepository->ocupacaoPorData($dataOcupacao);
        $paxBuffetDia = $this->operacaoReadModelRepository->paxBuffet(['data' => $dataOcupacao]);
        $paxOcupacaoDia = (int)($ocupacao['ocupacao_pax'] ?? 0);
        $taxaBuffetSobreOcupacao = $paxOcupacaoDia > 0 ? round(($paxBuffetDia / $paxOcupacaoDia) * 100, 2) : null;
        $range = $this->resolverPeriodo($filters);

        return [
            'filters' => $filters,
            'flow_filters' => $flowFilters,
            'summary' => $resumoOperacional,
            'operator_ranking' => $this->operacaoReadModelRepository->rankingOperadores($filters, 10),
            'operation_mix' => $this->operacaoReadModelRepository->mixOperacoes($filters),
            'restaurant_mix' => $this->operacaoReadModelRepository->mixRestaurantes($filters),
            'hourly_operation_flow' => $this->operacaoReadModelRepository->fluxoHorarioPorOperacao($flowFilters),
            'tematicos' => $indicadoresTematicos,
            'taxa_no_show' => $taxaNoShow,
            'taxa_comparecimento_tematico' => $taxaComparecimentoTematico,
            'insights' => $this->montarInsightsGerenciais($resumoOperacional, $taxaNoShow, $taxaComparecimentoTematico, $taxaBuffetSobreOcupacao),
            'restaurantes' => $this->operacaoReadModelRepository->listarRestaurantes(),
            'operacoes' => $this->operacaoReadModelRepository->listarOperacoes(),
            'occupancy_date' => $dataOcupacao,
            'occupancy' => $ocupacao,
            'buffet_pax_dia' => $paxBuffetDia,
            'taxa_buffet_ocupacao' => $taxaBuffetSobreOcupacao,
            'occupancy_timeline' => $this->montarLinhaDoTempoOcupacao($range['inicio'], $range['fim']),
            'can_edit_ocupacao' => in_array((string)($usuario['perfil'] ?? ''), ['admin', 'gerente'], true),
            'flash' => get_flash(),
        ];
    }

    /**
     * Sanitiza filtros de KPI para comparar períodos e recortes de restaurante/operação sem distorcer indicadores.
     *
     * @return array{data: string, data_inicio: string, data_fim: string, restaurante_id: mixed, operacao_id: mixed, status: string}
     */
    public function lerFiltros(array $query): array
    {
        return $this->normalizarPeriodo([
            'data' => $this->normalizarData((string)($query['data'] ?? '')),
            'data_inicio' => $this->normalizarData((string)($query['data_inicio'] ?? '')),
            'data_fim' => $this->normalizarData((string)($query['data_fim'] ?? '')),
            'restaurante_id' => sanitize_int_param($query['restaurante_id'] ?? ''),
            'operacao_id' => sanitize_int_param($query['operacao_id'] ?? ''),
            'status' => sanitize_enum_param($query['status'] ?? '', self::STATUS_FILTERS),
        ]);
    }

    /**
     * Normaliza datas de entrada para manter séries históricas comparáveis.
     */
    public function normalizarData(string $value): string
    {
        $value = trim($value);
        if ($value === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }
        $ts = strtotime($value);
        return $ts ? date('Y-m-d', $ts) : '';
    }

    /**
     * Resolve o período analisado quando a liderança informa data única ou intervalo.
     *
     * @return array{inicio: string, fim: string}
     */
    public function resolverPeriodo(array $filters): array
    {
        if (($filters['data'] ?? '') !== '') {
            return ['inicio' => $filters['data'], 'fim' => $filters['data']];
        }

        $inicio = ($filters['data_inicio'] ?? '') !== ''
            ? $filters['data_inicio']
            : date('Y-m-d', strtotime('-' . InteligenciaOperacionalConstants::DEFAULT_KPI_RANGE_DAYS . ' days'));
        $fim = ($filters['data_fim'] ?? '') !== '' ? $filters['data_fim'] : date('Y-m-d');
        if ($inicio > $fim) {
            [$inicio, $fim] = [$fim, $inicio];
        }
        return ['inicio' => $inicio, 'fim' => $fim];
    }

    /**
     * Retorna a série diária de KPIs usada na exportação de tendência operacional.
     *
     * @return array{filters: array<string, mixed>, rows: array<int, array<string, mixed>>}
     */
    public function exportarTendenciaDiaria(array $query): array
    {
        $filters = $this->lerFiltros($query);
        return [
            'filters' => $filters,
            'rows' => $this->operacaoReadModelRepository->tendenciaDiaria($filters),
        ];
    }

    private function normalizarPeriodo(array $filters): array
    {
        $inicio = (string)($filters['data_inicio'] ?? '');
        $fim = (string)($filters['data_fim'] ?? '');
        if ($inicio !== '' || $fim !== '') {
            if ($inicio === '') {
                $inicio = $fim;
            }
            if ($fim === '') {
                $fim = $inicio;
            }
            if ($inicio > $fim) {
                [$inicio, $fim] = [$fim, $inicio];
            }
            $filters['data'] = '';
            $filters['data_inicio'] = $inicio;
            $filters['data_fim'] = $fim;
        }
        return $filters;
    }

    private function lerFiltrosFluxo(array $query, array $filters): array
    {
        $flowData = $this->normalizarData((string)($query['flow_data'] ?? ($query['candle_data'] ?? '')));
        $flowInicio = $this->normalizarData((string)($query['flow_data_inicio'] ?? ($query['candle_data_inicio'] ?? '')));
        $flowFim = $this->normalizarData((string)($query['flow_data_fim'] ?? ($query['candle_data_fim'] ?? '')));
        $hasOwnFlowFilter = array_key_exists('flow_data', $query)
            || array_key_exists('flow_data_inicio', $query)
            || array_key_exists('flow_data_fim', $query)
            || array_key_exists('candle_data', $query)
            || array_key_exists('candle_data_inicio', $query)
            || array_key_exists('candle_data_fim', $query);

        if (!$hasOwnFlowFilter) {
            $flowData = (string)($filters['data'] ?? '');
            $flowInicio = (string)($filters['data_inicio'] ?? '');
            $flowFim = (string)($filters['data_fim'] ?? '');
        }
        if ($flowInicio !== '' || $flowFim !== '') {
            if ($flowInicio === '') {
                $flowInicio = $flowFim;
            }
            if ($flowFim === '') {
                $flowFim = $flowInicio;
            }
            $flowData = '';
        } elseif ($flowData === '') {
            $flowInicio = date(
                'Y-m-d',
                strtotime('-' . InteligenciaOperacionalConstants::DEFAULT_KPI_RANGE_DAYS . ' days')
            );
            $flowFim = date('Y-m-d');
        }
        if ($flowInicio !== '' && $flowFim !== '' && $flowInicio > $flowFim) {
            [$flowInicio, $flowFim] = [$flowFim, $flowInicio];
        }

        return [
            'data' => $flowData,
            'data_inicio' => $flowInicio,
            'data_fim' => $flowFim,
            'status' => $filters['status'] ?? '',
            'restaurante_id' => sanitize_int_param($query['flow_restaurante_id'] ?? ($query['candle_restaurante_id'] ?? '')),
            'operacao_id' => sanitize_int_param($query['flow_operacao_id'] ?? ($query['candle_operacao_id'] ?? '')),
        ];
    }

    private function resolverDataOcupacao(array $query, array $filters): string
    {
        $dataOcupacao = $this->normalizarData((string)($query['ocupacao_data'] ?? ''));
        if ($dataOcupacao !== '') {
            return $dataOcupacao;
        }
        $rangeForOccupancy = $this->resolverPeriodo($filters);
        return $filters['data'] !== '' ? $filters['data'] : $rangeForOccupancy['fim'];
    }

    private function calcularTaxaNoShowTematico(array $indicadoresTematicos): float
    {
        $reservasAtivas = (int)($indicadoresTematicos['finalizadas'] ?? 0) + (int)($indicadoresTematicos['no_shows'] ?? 0);
        return $reservasAtivas > 0 ? round(((int)($indicadoresTematicos['no_shows'] ?? 0) / $reservasAtivas) * 100, 2) : 0.0;
    }

    private function calcularTaxaComparecimentoTematico(array $indicadoresTematicos): float
    {
        $paxReservadas = (int)($indicadoresTematicos['pax_reservadas'] ?? 0);
        return $paxReservadas > 0 ? round(((int)($indicadoresTematicos['pax_comparecidas'] ?? 0) / $paxReservadas) * 100, 2) : 0.0;
    }

    private function montarLinhaDoTempoOcupacao(string $dataInicio, string $dataFim): array
    {
        $ocupacaoPorData = [];
        foreach ($this->operacaoReadModelRepository->historicoOcupacao(
            $dataInicio,
            $dataFim,
            InteligenciaOperacionalConstants::DEFAULT_OCCUPANCY_HISTORY_LIMIT
        ) as $row) {
            $ocupacaoPorData[$row['data_ref']] = [
                'ocupacao_uh' => $row['ocupacao_uh'],
                'ocupacao_pax' => $row['ocupacao_pax'],
            ];
        }

        $buffetPorData = [];
        foreach ($this->operacaoReadModelRepository->buffetDiarioPorOperacao($dataInicio, $dataFim) as $row) {
            $dataRef = (string)($row['data_ref'] ?? '');
            $nomeOperacao = (string)($row['operacao'] ?? '');
            if ($dataRef === '' || $nomeOperacao === '') {
                continue;
            }
            if (!isset($buffetPorData[$dataRef])) {
                $buffetPorData[$dataRef] = ['Café' => 0, 'Almoço' => 0, 'Jantar' => 0];
            }
            if (isset($buffetPorData[$dataRef][$nomeOperacao])) {
                $buffetPorData[$dataRef][$nomeOperacao] += (int)($row['total_pax'] ?? 0);
            }
        }

        $linhaDoTempo = [];
        $cursor = new DateTimeImmutable($dataInicio);
        $end = new DateTimeImmutable($dataFim);
        while ($cursor <= $end) {
            $dataRef = $cursor->format('Y-m-d');
            $linhaDoTempo[] = [
                'data_ref' => $dataRef,
                'ocupacao_uh' => $ocupacaoPorData[$dataRef]['ocupacao_uh'] ?? null,
                'ocupacao_pax' => $ocupacaoPorData[$dataRef]['ocupacao_pax'] ?? null,
                'cafe_pax' => $buffetPorData[$dataRef]['Café'] ?? 0,
                'almoco_pax' => $buffetPorData[$dataRef]['Almoço'] ?? 0,
                'jantar_pax' => $buffetPorData[$dataRef]['Jantar'] ?? 0,
            ];
            $cursor = $cursor->modify('+1 day');
        }
        return $linhaDoTempo;
    }

    private function montarInsightsGerenciais(array $summary, float $taxaNoShow, float $taxaComparecimentoTematico, ?float $taxaBuffetOcupacao): array
    {
        $insightsGerenciais = [];
        if (($summary['taxa_nao_informado'] ?? 0) >= 5) {
            $insightsGerenciais[] = ['type' => 'warning', 'title' => 'Reduzir UH não informada', 'text' => 'A taxa de UH não informada está acima de 5%. Reforce o protocolo de identificação no atendimento.'];
        }
        if (($summary['taxa_alertas'] ?? 0) >= 12) {
            $insightsGerenciais[] = ['type' => 'danger', 'title' => 'Atenção para qualidade operacional', 'text' => 'O percentual de alertas operacionais está alto. Vale revisar distribuição de equipe e checklist de turno.'];
        }
        if ($taxaNoShow >= 10) {
            $insightsGerenciais[] = ['type' => 'warning', 'title' => 'No-show temático elevado', 'text' => 'No-show acima de 10% no período. Recomenda-se confirmação ativa de reservas antes do jantar.'];
        }
        if ($taxaComparecimentoTematico >= 90) {
            $insightsGerenciais[] = ['type' => 'success', 'title' => 'Conversão temática excelente', 'text' => 'A taxa de comparecimento temático está em nível de excelência no período filtrado.'];
        }
        if ($taxaBuffetOcupacao !== null && $taxaBuffetOcupacao > 115) {
            $insightsGerenciais[] = ['type' => 'info', 'title' => 'Consumo buffet acima da ocupação informada', 'text' => 'A relação PAX buffet/ocupação está acima de 115%. Valide se houve day use elevado ou subnotificação de ocupação.'];
        }
        if ($insightsGerenciais === []) {
            $insightsGerenciais[] = ['type' => 'info', 'title' => 'Operação estável', 'text' => 'Sem alertas críticos no período. Continue monitorando tendência diária e ranking de operadores.'];
        }
        return $insightsGerenciais;
    }
}
