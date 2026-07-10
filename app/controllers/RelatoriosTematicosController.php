<?php
class RelatoriosTematicosController extends Controller
{
    /* Filtros de relatório unificados em TematicAccessService::lerFiltrosRelatorio (faxina, etapa 9). */

    private function buildExportType(): string
    {
        $type = strtolower(trim((string)($_GET['type'] ?? 'csv')));
        return $type === 'xlsx' ? 'xlsx' : 'csv';
    }

    private function exportDocument(array $config, string $type, callable $producer): int
    {
        return (new TabularExportService())->download(
            (string)($config['filename'] ?? 'relatorio_tematicos'),
            $type,
            $config['headers'] ?? [],
            $producer,
            [
                'title' => $config['title'] ?? 'Exportacao tematica',
                'subtitle' => $config['subtitle'] ?? 'Base gerada pelo sistema.',
                'sheet_name' => $config['sheet_name'] ?? 'Tematicos',
                'meta' => $config['meta'] ?? [],
            ]
        );
    }

    private function filtersMeta(array $filters, array $restaurantes, array $turnos): array
    {
        $meta = [];
        if (!empty($filters['data'])) {
            $meta['Data'] = format_date_br((string)$filters['data']);
        }
        if (!empty($filters['data_inicio']) || !empty($filters['data_fim'])) {
            $inicio = !empty($filters['data_inicio']) ? format_date_br((string)$filters['data_inicio']) : '-';
            $fim = !empty($filters['data_fim']) ? format_date_br((string)$filters['data_fim']) : '-';
            $meta['Periodo'] = $inicio . ' a ' . $fim;
        }
        if (!empty($filters['restaurante_id'])) {
            foreach ($restaurantes as $restaurante) {
                if ((int)$restaurante['id'] === (int)$filters['restaurante_id']) {
                    $meta['Restaurante'] = normalize_mojibake((string)$restaurante['nome']);
                    break;
                }
            }
        }
        if (!empty($filters['turno_id'])) {
            foreach ($turnos as $turno) {
                if ((int)$turno['id'] === (int)$filters['turno_id']) {
                    $meta['Turno'] = normalize_mojibake((string)($turno['hora'] ?? ''));
                    break;
                }
            }
        }
        if (!empty($filters['status'])) {
            $meta['Status'] = normalize_mojibake((string)$filters['status']);
        }
        if (!empty($filters['grupo_nome'])) {
            $meta['Grupo'] = normalize_mojibake((string)$filters['grupo_nome']);
        }
        if (!empty($filters['q'])) {
            $meta['Pesquisa'] = normalize_mojibake((string)$filters['q']);
        }
        return $meta;
    }

    /**
     * Rota legada dos relatórios temáticos: redireciona para a aba Temáticos do hub Análise
     * preservando o filtro. A exportação (export) permanece neste controller.
     */
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $query = $_GET;
        unset($query['r'], $query['aba']);
        $destino = '/?r=analise/index&aba=tematicos' . ($query !== [] ? '&' . http_build_query($query) : '');
        $this->redirect($destino);
    }

    public function historico(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $reservaId = sanitize_int_param($_GET['id'] ?? '');
        if ($reservaId <= 0) {
            json_response([
                'ok' => false,
                'message' => 'Informe uma reserva válida para consultar o histórico.',
            ], 422);
        }

        $historico = (new ReservaTematicaHistoricoService())->obter($reservaId);
        if ($historico === null) {
            json_response([
                'ok' => false,
                'message' => 'A reserva informada não foi localizada.',
            ], 404);
        }

        json_response([
            'ok' => true,
            'historico' => $historico,
        ]);
    }

    public function export(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $reservaModel = new ReservaTematicaModel();
        $tematicos = (new TematicAccessService())->allTematicRestaurants();
        $turnos = (new ReservaTematicaTurnoModel())->all();

        $filters = TematicAccessService::lerFiltrosRelatorio($tematicos, false);

        $type = $this->buildExportType();
        $totalRows = $reservaModel->countByFilters($filters);
        (new SecurityLogModel())->log('export_relatorios_tematicos', (int)(Auth::user()['id'] ?? 0), [
            'type' => $type,
            'rows' => $totalRows,
            'filters' => $filters,
        ]);
        $this->exportDocument([
            'filename' => 'relatorio_tematicos',
            'title' => 'Reservas tematicas',
            'subtitle' => 'Base detalhada para operacao, supervisao e auditoria.',
            'sheet_name' => 'Tematicos',
            'meta' => $this->filtersMeta($filters, $tematicos, $turnos),
            'headers' => [
                'data_reserva','turno','restaurante','grupo_id','grupo_nome','responsavel_grupo','uh','titular','pax_adulto','pax_chd','qtd_chd','pax_reservada','pax_real','status',
                'obs_reserva','tags','obs_operacao','usuario','criado_em'
            ],
        ], $type, static function (callable $writeRow) use ($reservaModel, $filters): int {
            return $reservaModel->exportByFilters($filters, static function (array $r) use ($writeRow): void {
                $writeRow([
                    $r['data_reserva'],
                    $r['turno_hora'],
                    $r['restaurante'],
                    $r['grupo_id'] ?? '',
                    $r['grupo_nome_display'] ?? $r['grupo_nome'] ?? '',
                    $r['grupo_responsavel'] ?? '',
                    (string)($r['status'] ?? '') === ReservasTematicasConstants::STATUS_PRE_RESERVA ? 'Pendente' : $r['uh_numero'],
                    $r['titular_nome_display'] ?? $r['titular_nome'] ?? '',
                    $r['pax_adulto_calc'] ?? '',
                    $r['pax_chd_calc'] ?? '',
                    $r['qtd_chd_calc'] ?? '',
                    $r['pax'],
                    $r['pax_real'] ?? '',
                    (string)($r['status'] ?? '') === ReservasTematicasConstants::STATUS_PRE_RESERVA ? 'Pré-reserva' : $r['status'],
                    $r['observacao_reserva'] ?? '',
                    $r['observacao_tags'] ?? '',
                    $r['observacao_operacao'] ?? '',
                    $r['usuario'],
                    $r['criado_em'],
                ]);
            });
        });
        exit;
    }
}
