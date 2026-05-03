<?php
class KpisController extends Controller
{
    private const STATUS_FILTERS = ['duplicado', 'fora_horario', 'multiplo', 'ok', 'nao_informado', 'day_use'];

    private function normalizeDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }
        $ts = strtotime($value);
        return $ts ? date('Y-m-d', $ts) : '';
    }

    private function readFilters(): array
    {
        return $this->normalizeFilterPeriod([
            'data' => $this->normalizeDate((string)($_GET['data'] ?? '')),
            'data_inicio' => $this->normalizeDate((string)($_GET['data_inicio'] ?? '')),
            'data_fim' => $this->normalizeDate((string)($_GET['data_fim'] ?? '')),
            'restaurante_id' => sanitize_int_param($_GET['restaurante_id'] ?? ''),
            'operacao_id' => sanitize_int_param($_GET['operacao_id'] ?? ''),
            'status' => sanitize_enum_param($_GET['status'] ?? '', self::STATUS_FILTERS),
        ]);
    }

    private function normalizeFilterPeriod(array $filters): array
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

    private function readFlowFilters(array $filters): array
    {
        $flowData = $this->normalizeDate((string)($_GET['flow_data'] ?? ($_GET['candle_data'] ?? '')));
        $flowInicio = $this->normalizeDate((string)($_GET['flow_data_inicio'] ?? ($_GET['candle_data_inicio'] ?? '')));
        $flowFim = $this->normalizeDate((string)($_GET['flow_data_fim'] ?? ($_GET['candle_data_fim'] ?? '')));

        $hasOwnFlowFilter = array_key_exists('flow_data', $_GET)
            || array_key_exists('flow_data_inicio', $_GET)
            || array_key_exists('flow_data_fim', $_GET)
            || array_key_exists('candle_data', $_GET)
            || array_key_exists('candle_data_inicio', $_GET)
            || array_key_exists('candle_data_fim', $_GET);

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
            $flowInicio = date('Y-m-d', strtotime('-6 days'));
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
            'restaurante_id' => sanitize_int_param($_GET['flow_restaurante_id'] ?? ($_GET['candle_restaurante_id'] ?? '')),
            'operacao_id' => sanitize_int_param($_GET['flow_operacao_id'] ?? ($_GET['candle_operacao_id'] ?? '')),
        ];
    }

    private function resolveRange(array $filters): array
    {
        if ($filters['data'] !== '') {
            return ['inicio' => $filters['data'], 'fim' => $filters['data']];
        }

        $inicio = $filters['data_inicio'] !== '' ? $filters['data_inicio'] : date('Y-m-d', strtotime('-6 days'));
        $fim = $filters['data_fim'] !== '' ? $filters['data_fim'] : date('Y-m-d');

        if ($inicio > $fim) {
            [$inicio, $fim] = [$fim, $inicio];
        }

        return ['inicio' => $inicio, 'fim' => $fim];
    }

    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'gerente']);

        $filters = $this->readFilters();
        if ($filters['data'] === '' && $filters['data_inicio'] === '' && $filters['data_fim'] === '') {
            $filters['data_inicio'] = date('Y-m-d', strtotime('-6 days'));
            $filters['data_fim'] = date('Y-m-d');
        }

        $accessModel = new AccessModel();
        $reservaTematicaModel = new ReservaTematicaModel();
        $restaurantModel = new RestaurantModel();
        $operationModel = new OperationModel();
        $occupancyModel = new KpiOccupancyModel();

        $summary = $accessModel->kpiSummary($filters);
        $operatorRanking = $accessModel->kpiOperatorRanking($filters, 10);
        $operationMix = $accessModel->kpiOperationMix($filters);
        $restaurantMix = $accessModel->kpiRestaurantMix($filters);
        $flowFilters = $this->readFlowFilters($filters);
        $hourlyOperationFlow = $accessModel->kpiHourlyOperationFlow($flowFilters);
        $tematicos = $reservaTematicaModel->dashboardStats($filters);

        $activeTematicas = (int)($tematicos['finalizadas'] ?? 0) + (int)($tematicos['no_shows'] ?? 0);
        $taxaNoShow = $activeTematicas > 0
            ? round(((int)($tematicos['no_shows'] ?? 0) / $activeTematicas) * 100, 2)
            : 0.0;
        $taxaComparecimentoTematico = (int)($tematicos['pax_reservadas'] ?? 0) > 0
            ? round(((int)($tematicos['pax_comparecidas'] ?? 0) / (int)$tematicos['pax_reservadas']) * 100, 2)
            : 0.0;

        $occupancyDate = $this->normalizeDate((string)($_GET['ocupacao_data'] ?? ''));
        if ($occupancyDate === '') {
            $rangeForOccupancy = $this->resolveRange($filters);
            $occupancyDate = $filters['data'] !== '' ? $filters['data'] : $rangeForOccupancy['fim'];
        }

        $occupancy = $occupancyModel->getByDate($occupancyDate);
        $buffetPaxDia = $accessModel->kpiBuffetPax(['data' => $occupancyDate]);
        $ocupacaoPaxDia = (int)($occupancy['ocupacao_pax'] ?? 0);
        $taxaBuffetSobreOcupacao = $ocupacaoPaxDia > 0
            ? round(($buffetPaxDia / $ocupacaoPaxDia) * 100, 2)
            : null;

        $range = $this->resolveRange($filters);
        $occupancyHistoryRows = $occupancyModel->history($range['inicio'], $range['fim'], 120);
        $buffetHistoryRows = $accessModel->kpiBuffetDailyOperationRange($range['inicio'], $range['fim']);

        $occMap = [];
        foreach ($occupancyHistoryRows as $row) {
            $occMap[$row['data_ref']] = [
                'ocupacao_uh' => $row['ocupacao_uh'],
                'ocupacao_pax' => $row['ocupacao_pax'],
            ];
        }

        $buffetMap = [];
        foreach ($buffetHistoryRows as $row) {
            $dateKey = (string)($row['data_ref'] ?? '');
            $operationKey = (string)($row['operacao'] ?? '');
            if ($dateKey === '' || $operationKey === '') {
                continue;
            }
            if (!isset($buffetMap[$dateKey])) {
                $buffetMap[$dateKey] = [
                    'Café' => 0,
                    'Almoço' => 0,
                    'Jantar' => 0,
                ];
            }
            if (isset($buffetMap[$dateKey][$operationKey])) {
                $buffetMap[$dateKey][$operationKey] += (int)($row['total_pax'] ?? 0);
            }
        }

        $timeline = [];
        $cursor = new DateTimeImmutable($range['inicio']);
        $end = new DateTimeImmutable($range['fim']);
        while ($cursor <= $end) {
            $key = $cursor->format('Y-m-d');
            $timeline[] = [
                'data_ref' => $key,
                'ocupacao_uh' => $occMap[$key]['ocupacao_uh'] ?? null,
                'ocupacao_pax' => $occMap[$key]['ocupacao_pax'] ?? null,
                'cafe_pax' => $buffetMap[$key]['Café'] ?? 0,
                'almoco_pax' => $buffetMap[$key]['Almoço'] ?? 0,
                'jantar_pax' => $buffetMap[$key]['Jantar'] ?? 0,
            ];
            $cursor = $cursor->modify('+1 day');
        }

        $insights = $this->buildInsights(
            $summary,
            $taxaNoShow,
            $taxaComparecimentoTematico,
            $taxaBuffetSobreOcupacao
        );

        $this->view('kpis/index', [
            'filters' => $filters,
            'flow_filters' => $flowFilters,
            'summary' => $summary,
            'operator_ranking' => $operatorRanking,
            'operation_mix' => $operationMix,
            'restaurant_mix' => $restaurantMix,
            'hourly_operation_flow' => $hourlyOperationFlow,
            'tematicos' => $tematicos,
            'taxa_no_show' => $taxaNoShow,
            'taxa_comparecimento_tematico' => $taxaComparecimentoTematico,
            'insights' => $insights,
            'restaurantes' => $restaurantModel->all(),
            'operacoes' => $operationModel->all(),
            'occupancy_date' => $occupancyDate,
            'occupancy' => $occupancy,
            'buffet_pax_dia' => $buffetPaxDia,
            'taxa_buffet_ocupacao' => $taxaBuffetSobreOcupacao,
            'occupancy_timeline' => $timeline,
            'can_edit_ocupacao' => in_array((string)(Auth::user()['perfil'] ?? ''), ['admin'], true),
            'flash' => get_flash(),
        ]);
    }

    public function saveOcupacao(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=kpis/index');
        }

        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=kpis/index');
        }

        $dataRef = $this->normalizeDate((string)($_POST['data_ref'] ?? ''));
        if ($dataRef === '') {
            set_flash('danger', 'Data da ocupação inválida.');
            $this->redirect('/?r=kpis/index');
        }

        $rawUhs = trim((string)($_POST['ocupacao_uh'] ?? ''));
        $rawPax = trim((string)($_POST['ocupacao_pax'] ?? ''));
        $observacao = trim((string)($_POST['observacao'] ?? ''));

        $ocupacaoUhs = $rawUhs === '' ? null : max(0, (int)$rawUhs);
        $ocupacaoPax = $rawPax === '' ? null : max(0, (int)$rawPax);

        if ($ocupacaoUhs === null && $ocupacaoPax === null) {
            set_flash('warning', 'Informe ao menos um campo de ocupação (UH ou PAX).');
            $this->redirect('/?r=kpis/index&ocupacao_data=' . urlencode($dataRef));
        }

        $ok = (new KpiOccupancyModel())->upsert($dataRef, $ocupacaoUhs, $ocupacaoPax, $observacao, (int)Auth::user()['id']);
        if ($ok) {
            set_flash('success', 'Ocupação diária salva com sucesso.');
        } else {
            set_flash('danger', 'Não foi possível salvar a ocupação diária.');
        }

        $params = ['r' => 'kpis/index', 'ocupacao_data' => $dataRef];
        $map = [
            'f_data' => 'data',
            'f_data_inicio' => 'data_inicio',
            'f_data_fim' => 'data_fim',
            'f_restaurante_id' => 'restaurante_id',
            'f_operacao_id' => 'operacao_id',
            'f_status' => 'status',
        ];
        foreach ($map as $input => $key) {
            $value = trim((string)($_POST[$input] ?? ''));
            if ($value !== '') {
                if (in_array($key, ['data', 'data_inicio', 'data_fim'], true)) {
                    $params[$key] = $this->normalizeDate($value);
                } elseif (in_array($key, ['restaurante_id', 'operacao_id'], true)) {
                    $params[$key] = sanitize_int_param($value);
                } elseif ($key === 'status') {
                    $params[$key] = sanitize_enum_param($value, self::STATUS_FILTERS);
                }
            }
        }

        $this->redirect('/?' . http_build_query($params));
    }

    public function exportTrend(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'gerente']);

        $filters = $this->readFilters();
        $rows = (new AccessModel())->kpiDailyTrend($filters);
        (new SecurityLogModel())->log('export_kpis_tendencia', (int)(Auth::user()['id'] ?? 0), [
            'rows' => count($rows),
            'filters' => $filters,
        ]);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="kpis_tendencia.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['data', 'registros', 'pax_total', 'uhs_unicas', 'duplicados', 'fora_horario']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['data_ref'],
                $r['registros'],
                $r['pax_total'],
                $r['uhs_unicas'],
                $r['duplicados'],
                $r['fora_horario'],
            ]);
        }
        fclose($out);
        exit;
    }

    private function buildInsights(array $summary, float $taxaNoShow, float $taxaComparecimentoTematico, ?float $taxaBuffetOcupacao): array
    {
        $insights = [];

        if (($summary['taxa_nao_informado'] ?? 0) >= 5) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Reduzir UH não informada',
                'text' => 'A taxa de UH não informada está acima de 5%. Reforce o protocolo de identificação no atendimento.',
            ];
        }
        if (($summary['taxa_alertas'] ?? 0) >= 12) {
            $insights[] = [
                'type' => 'danger',
                'title' => 'Atenção para qualidade operacional',
                'text' => 'O percentual de alertas operacionais está alto. Vale revisar distribuição de equipe e checklist de turno.',
            ];
        }
        if ($taxaNoShow >= 10) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'No-show temático elevado',
                'text' => 'No-show acima de 10% no período. Recomenda-se confirmação ativa de reservas antes do jantar.',
            ];
        }
        if ($taxaComparecimentoTematico >= 90) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Conversão temática excelente',
                'text' => 'A taxa de comparecimento temático está em nível de excelência no período filtrado.',
            ];
        }
        if ($taxaBuffetOcupacao !== null && $taxaBuffetOcupacao > 115) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Consumo buffet acima da ocupação informada',
                'text' => 'A relação PAX buffet/ocupação está acima de 115%. Valide se houve day use elevado ou subnotificação de ocupação.',
            ];
        }
        if (empty($insights)) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Operação estável',
                'text' => 'Sem alertas críticos no período. Continue monitorando tendência diária e ranking de operadores.',
            ];
        }

        return $insights;
    }
}
