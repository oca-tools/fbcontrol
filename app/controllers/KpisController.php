<?php
declare(strict_types=1);

class KpisController extends Controller
{
    /**
     * Normaliza a data usada nos filtros de KPIs para manter comparações consistentes.
     */
    private function normalizeDate(string $value): string
    {
        return (new KpiOperacionalService())->normalizarData($value);
    }

    /**
     * Exibe os indicadores gerenciais de ocupação, fluxo, alertas e performance de reservas temáticas.
     */
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'gerente']);

        $this->view('kpis/index', (new KpiOperacionalService())->montarPainelKpis($_GET, Auth::user()));
    }

    /**
     * Registra a ocupação diária informada pela gerência para cruzar PAX consumido contra ocupação do hotel.
     */
    public function saveOcupacao(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'gerente']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(InteligenciaOperacionalConstants::ROUTE_KPIS_INDEX);
        }

        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash(InteligenciaOperacionalConstants::FLASH_DANGER, InteligenciaOperacionalConstants::MESSAGE_TOKEN_INVALIDO);
            $this->redirect(InteligenciaOperacionalConstants::ROUTE_KPIS_INDEX);
        }

        $dataRef = $this->normalizeDate((string)($_POST['data_ref'] ?? ''));
        if ($dataRef === '') {
            set_flash(InteligenciaOperacionalConstants::FLASH_DANGER, InteligenciaOperacionalConstants::MESSAGE_DATA_OCUPACAO_INVALIDA);
            $this->redirect(InteligenciaOperacionalConstants::ROUTE_KPIS_INDEX);
        }

        $rawUhs = trim((string)($_POST['ocupacao_uh'] ?? ''));
        $rawPax = trim((string)($_POST['ocupacao_pax'] ?? ''));
        $observacao = trim((string)($_POST['observacao'] ?? ''));

        $ocupacaoUhs = $rawUhs === '' ? null : max(0, (int)$rawUhs);
        $ocupacaoPax = $rawPax === '' ? null : max(0, (int)$rawPax);

        if ($ocupacaoUhs === null && $ocupacaoPax === null) {
            set_flash(InteligenciaOperacionalConstants::FLASH_WARNING, InteligenciaOperacionalConstants::MESSAGE_OCUPACAO_OBRIGATORIA);
            $this->redirect(InteligenciaOperacionalConstants::ROUTE_KPIS_INDEX . '&ocupacao_data=' . urlencode($dataRef));
        }

        $ok = (new KpiOccupancyModel())->upsert($dataRef, $ocupacaoUhs, $ocupacaoPax, $observacao, (int)Auth::user()['id']);
        if ($ok) {
            set_flash(InteligenciaOperacionalConstants::FLASH_SUCCESS, InteligenciaOperacionalConstants::MESSAGE_OCUPACAO_SALVA);
        } else {
            set_flash(InteligenciaOperacionalConstants::FLASH_DANGER, InteligenciaOperacionalConstants::MESSAGE_OCUPACAO_NAO_SALVA);
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
                    $params[$key] = sanitize_enum_param($value, KpiOperacionalService::STATUS_FILTERS);
                }
            }
        }

        $this->redirect('/?' . http_build_query($params));
    }

    /**
     * Exporta a série diária de KPIs para análise externa de tendência operacional.
     */
    public function exportTrend(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'gerente']);

        $exportacao = (new KpiOperacionalService())->exportarTendenciaDiaria($_GET);
        $filters = $exportacao['filters'];
        $rows = $exportacao['rows'];
        (new SecurityLogModel())->log(InteligenciaOperacionalConstants::AUDIT_EXPORT_KPIS_TENDENCIA, (int)(Auth::user()['id'] ?? 0), [
            'rows' => count($rows),
            'filters' => $filters,
        ]);
        (new TabularExportService())->download(
            InteligenciaOperacionalConstants::EXPORT_KPIS_TENDENCIA_FILENAME,
            InteligenciaOperacionalConstants::FORMAT_CSV,
            InteligenciaOperacionalConstants::HEADERS_KPIS_TENDENCIA,
            static function (callable $writeRow) use ($rows): int {
                foreach ($rows as $r) {
                    $writeRow([
                        $r['data_ref'],
                        $r['registros'],
                        $r['pax_total'],
                        $r['uhs_unicas'],
                        $r['duplicados'],
                        $r['fora_horario'],
                    ]);
                }
                return count($rows);
            },
            [
                'title' => InteligenciaOperacionalConstants::EXPORT_KPIS_TENDENCIA_TITLE,
                'subtitle' => InteligenciaOperacionalConstants::EXPORT_KPIS_TENDENCIA_SUBTITLE,
                'sheet_name' => InteligenciaOperacionalConstants::EXPORT_KPIS_TENDENCIA_SHEET,
                'meta' => [
                    'Periodo' => (!empty($filters['data_inicio']) || !empty($filters['data_fim']))
                        ? ((string)($filters['data_inicio'] ?: '-')) . ' a ' . ((string)($filters['data_fim'] ?: '-'))
                        : format_date_br((string)($filters['data'] ?? date('Y-m-d'))),
                ],
            ]
        );
        exit;
    }
}
