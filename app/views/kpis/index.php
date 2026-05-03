<?php
$filters = $this->data['filters'] ?? [];
$flowFilters = $this->data['flow_filters'] ?? [];
$summary = $this->data['summary'] ?? [];
$operatorRanking = $this->data['operator_ranking'] ?? [];
$operationMix = $this->data['operation_mix'] ?? [];
$restaurantMix = $this->data['restaurant_mix'] ?? [];
$hourlyOperationFlow = $this->data['hourly_operation_flow'] ?? [];
$tematicos = $this->data['tematicos'] ?? [];
$taxaNoShow = (float)($this->data['taxa_no_show'] ?? 0);
$taxaComparecimentoTematico = (float)($this->data['taxa_comparecimento_tematico'] ?? 0);
$insights = $this->data['insights'] ?? [];
$restaurantes = $this->data['restaurantes'] ?? [];
$operacoes = $this->data['operacoes'] ?? [];
$occupancyDate = (string)($this->data['occupancy_date'] ?? date('Y-m-d'));
$occupancy = $this->data['occupancy'] ?? null;
$buffetPaxDia = (int)($this->data['buffet_pax_dia'] ?? 0);
$taxaBuffetOcupacao = $this->data['taxa_buffet_ocupacao'] ?? null;
$occupancyTimeline = $this->data['occupancy_timeline'] ?? [];
$canEditOcupacao = (bool)($this->data['can_edit_ocupacao'] ?? false);
?>

<style>
    .kpis-page,
    .kpis-page .row,
    .kpis-page [class*="col-"] {
        min-width: 0;
    }
    .kpis-page .card {
        overflow: hidden;
    }
    .kpis-page .table-responsive {
        max-width: 100%;
        overflow-x: auto;
    }
    .kpis-page .section-title {
        min-width: 0;
    }
    .kpis-page .section-title h3,
    .kpis-page .section-title h5,
    .kpis-page .section-title .text-muted {
        overflow-wrap: anywhere;
    }
    .kpis-page .kpi-filter-actions .btn {
        min-height: 42px;
    }
    .kpi-ranking-table {
        max-height: 360px;
        overflow-y: auto;
    }
    .kpi-flow-filter {
        background: var(--ab-soft-bg);
        border: 1px solid var(--ab-border);
        border-radius: 18px;
        padding: 1rem;
    }
    @media (max-width: 1199.98px) {
        .kpi-ranking-table {
            max-height: none;
        }
    }
    @media (max-width: 991.98px) {
        .kpis-page .card.p-4 {
            padding: 1rem !important;
        }
        .kpis-page .display-6 {
            font-size: clamp(1.45rem, 6vw, 2rem);
        }
        .kpis-page .kpi-filter-actions .btn {
            flex: 1 1 calc(50% - .5rem);
        }
        .kpis-page #kpiHourlyOperationChart,
        .kpis-page #kpiOperationMixChart,
        .kpis-page #kpiRestaurantMixChart,
        .kpis-page #kpiOccupancyVsBuffetChart {
            min-height: 260px !important;
        }
    }
    @media (max-width: 575.98px) {
        .kpis-page .kpi-filter-actions .btn {
            flex: 1 1 100%;
        }
        .kpis-page .table {
            font-size: .88rem;
        }
    }
</style>

<div class="split-pane-layout kpis-page">
    <div class="card card-soft p-4 mb-4 split-full">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div class="section-title">
                <div class="icon"><i class="bi bi-speedometer2"></i></div>
                <div>
                    <div class="text-uppercase text-muted small">Visão executiva 2.0</div>
                    <h3 class="fw-bold mb-1">KPIs Estratégicos</h3>
                    <div class="text-muted">Análises visuais para gestão operacional: desempenho, fluxo e aderência à ocupação.</div>
                </div>
            </div>
        </div>

        <form class="row g-3 align-items-end mt-2" method="get" action="/" data-ajax-filter data-ajax-target=".app-content">
            <input type="hidden" name="r" value="kpis/index">
            <div class="col-12 col-md-3">
                <label class="form-label">Data (única)</label>
                <input type="date" class="form-control input-xl" name="data" value="<?= h($filters['data'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label">Data início</label>
                <input type="date" class="form-control input-xl" name="data_inicio" value="<?= h($filters['data_inicio'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label">Data fim</label>
                <input type="date" class="form-control input-xl" name="data_fim" value="<?= h($filters['data_fim'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select input-xl" name="status">
                    <option value="">Todos</option>
                    <option value="ok" <?= ($filters['status'] ?? '') === 'ok' ? 'selected' : '' ?>>OK</option>
                    <option value="duplicado" <?= ($filters['status'] ?? '') === 'duplicado' ? 'selected' : '' ?>>Duplicado</option>
                    <option value="fora_horario" <?= ($filters['status'] ?? '') === 'fora_horario' ? 'selected' : '' ?>>Fora do horário</option>
                    <option value="multiplo" <?= ($filters['status'] ?? '') === 'multiplo' ? 'selected' : '' ?>>Múltiplo acesso</option>
                    <option value="nao_informado" <?= ($filters['status'] ?? '') === 'nao_informado' ? 'selected' : '' ?>>Não informado</option>
                    <option value="day_use" <?= ($filters['status'] ?? '') === 'day_use' ? 'selected' : '' ?>>Day use</option>
                </select>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Restaurante</label>
                <select class="form-select input-xl" name="restaurante_id">
                    <option value="">Todos</option>
                    <?php foreach ($restaurantes as $item): ?>
                        <option value="<?= (int)$item['id'] ?>" <?= ($filters['restaurante_id'] ?? '') == $item['id'] ? 'selected' : '' ?>>
                            <?= h($item['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Operação</label>
                <select class="form-select input-xl" name="operacao_id">
                    <option value="">Todas</option>
                    <?php foreach ($operacoes as $item): ?>
                        <option value="<?= (int)$item['id'] ?>" <?= ($filters['operacao_id'] ?? '') == $item['id'] ? 'selected' : '' ?>>
                            <?= h($item['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 d-flex flex-wrap gap-2 kpi-filter-actions">
                <button class="btn btn-outline-primary" type="button" data-range="1">Ontem</button>
                <button class="btn btn-outline-primary" type="button" data-range="7">Últimos 7 dias</button>
                <button class="btn btn-outline-primary" type="button" data-range="30">Últimos 30 dias</button>
                <button class="btn btn-primary btn-xl">Aplicar filtros</button>
                <a class="btn btn-primary btn-xl" href="/?r=kpis/index" data-ajax-link data-ajax-target=".app-content">Remover filtro</a>
            </div>
        </form>
    </div>

    <div class="row g-4 mb-4 split-full">
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card metric-card p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="metric-icon"><i class="bi bi-people"></i></div>
                    <div>
                        <div class="text-muted small">PAX total no período</div>
                        <div class="display-6 fw-bold"><?= (int)($summary['total_pax'] ?? 0) ?></div>
                    </div>
                </div>
                <span class="stat-chip mt-3"><i class="bi bi-clipboard-data"></i><?= (int)($summary['total_registros'] ?? 0) ?> registros</span>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card metric-card p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="metric-icon"><i class="bi bi-stars"></i></div>
                    <div>
                        <div class="text-muted small">Comparecimento temático</div>
                        <div class="display-6 fw-bold"><?= h((string)$taxaComparecimentoTematico) ?>%</div>
                    </div>
                </div>
                <span class="stat-chip mt-3"><i class="bi bi-person-x"></i>No-show: <?= h((string)$taxaNoShow) ?>%</span>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card metric-card p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="metric-icon"><i class="bi bi-house-check"></i></div>
                    <div>
                        <div class="text-muted small">Cobertura buffet x ocupação (dia)</div>
                        <div class="display-6 fw-bold">
                            <?= $taxaBuffetOcupacao === null ? '--' : h((string)$taxaBuffetOcupacao) . '%' ?>
                        </div>
                    </div>
                </div>
                <span class="stat-chip mt-3"><i class="bi bi-shop-window"></i>Buffet hoje: <?= (int)$buffetPaxDia ?> PAX</span>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4 split-full">
        <div class="col-12 col-xl-7">
            <div class="card p-4 h-100">
                <div class="section-title mb-3">
                    <div class="icon"><i class="bi bi-graph-up-arrow"></i></div>
                    <div>
                        <div class="text-uppercase text-muted small">Fluxo operacional</div>
                        <h5 class="fw-bold mb-0">PAX por horário e operação</h5>
                        <div class="text-muted small">Concentração real de PAX por faixa horária, separada por operação.</div>
                    </div>
                </div>
                <form class="row g-2 align-items-end mb-3 kpi-flow-filter" method="get" action="/" data-ajax-filter data-ajax-target=".app-content">
                    <input type="hidden" name="r" value="kpis/index">
                    <input type="hidden" name="data" value="<?= h($filters['data'] ?? '') ?>">
                    <input type="hidden" name="data_inicio" value="<?= h($filters['data_inicio'] ?? '') ?>">
                    <input type="hidden" name="data_fim" value="<?= h($filters['data_fim'] ?? '') ?>">
                    <input type="hidden" name="restaurante_id" value="<?= h($filters['restaurante_id'] ?? '') ?>">
                    <input type="hidden" name="operacao_id" value="<?= h($filters['operacao_id'] ?? '') ?>">
                    <input type="hidden" name="status" value="<?= h($filters['status'] ?? '') ?>">
                    <div class="col-12 col-md-4">
                        <label class="form-label small">Data única do gráfico</label>
                        <input type="date" class="form-control" name="flow_data" value="<?= h($flowFilters['data'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small">Início do gráfico</label>
                        <input type="date" class="form-control" name="flow_data_inicio" value="<?= h($flowFilters['data_inicio'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small">Fim do gráfico</label>
                        <input type="date" class="form-control" name="flow_data_fim" value="<?= h($flowFilters['data_fim'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small">Restaurante do gráfico</label>
                        <select class="form-select" name="flow_restaurante_id">
                            <option value="">Todos</option>
                            <?php foreach ($restaurantes as $item): ?>
                                <option value="<?= (int)$item['id'] ?>" <?= ($flowFilters['restaurante_id'] ?? '') == $item['id'] ? 'selected' : '' ?>>
                                    <?= h($item['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small">Operação do gráfico</label>
                        <select class="form-select" name="flow_operacao_id">
                            <option value="">Todas</option>
                            <?php foreach ($operacoes as $item): ?>
                                <option value="<?= (int)$item['id'] ?>" <?= ($flowFilters['operacao_id'] ?? '') == $item['id'] ? 'selected' : '' ?>>
                                    <?= h($item['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a class="btn btn-outline-primary" href="/?r=kpis/index&data=<?= h($filters['data'] ?? '') ?>&data_inicio=<?= h($filters['data_inicio'] ?? '') ?>&data_fim=<?= h($filters['data_fim'] ?? '') ?>&restaurante_id=<?= h($filters['restaurante_id'] ?? '') ?>&operacao_id=<?= h($filters['operacao_id'] ?? '') ?>&status=<?= h($filters['status'] ?? '') ?>" data-ajax-link data-ajax-target=".app-content">Limpar gráfico</a>
                        <button class="btn btn-primary">Aplicar no gráfico</button>
                    </div>
                </form>
                <div id="kpiHourlyOperationChart" style="min-height: 320px;"></div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="card p-4 h-100 mb-4">
                <div class="section-title mb-3">
                    <div class="icon"><i class="bi bi-pie-chart"></i></div>
                    <div>
                        <div class="text-uppercase text-muted small">Mix operacional</div>
                        <h5 class="fw-bold mb-0">Distribuição por operação</h5>
                    </div>
                </div>
                <div id="kpiOperationMixChart" style="min-height: 200px;"></div>
            </div>
            <div class="card p-4 h-100">
                <div class="section-title mb-3">
                    <div class="icon"><i class="bi bi-building"></i></div>
                    <div>
                        <div class="text-uppercase text-muted small">Mix de restaurante</div>
                        <h5 class="fw-bold mb-0">PAX por restaurante</h5>
                    </div>
                </div>
                <div id="kpiRestaurantMixChart" style="min-height: 200px;"></div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4 split-full">
        <div class="col-12 col-xl-6">
            <div class="card p-4 h-100">
                <div class="section-title mb-3">
                    <div class="icon"><i class="bi bi-calendar-check"></i></div>
                    <div>
                        <div class="text-uppercase text-muted small">Ocupação diária</div>
                        <h5 class="fw-bold mb-0">Comparativo com PAX buffet</h5>
                    </div>
                </div>

                <?php if ($canEditOcupacao): ?>
                    <form method="post" action="/?r=kpis/saveOcupacao" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="f_data" value="<?= h($filters['data'] ?? '') ?>">
                        <input type="hidden" name="f_data_inicio" value="<?= h($filters['data_inicio'] ?? '') ?>">
                        <input type="hidden" name="f_data_fim" value="<?= h($filters['data_fim'] ?? '') ?>">
                        <input type="hidden" name="f_restaurante_id" value="<?= h($filters['restaurante_id'] ?? '') ?>">
                        <input type="hidden" name="f_operacao_id" value="<?= h($filters['operacao_id'] ?? '') ?>">
                        <input type="hidden" name="f_status" value="<?= h($filters['status'] ?? '') ?>">

                        <div class="col-12 col-md-4">
                            <label class="form-label">Data</label>
                            <input type="date" name="data_ref" class="form-control input-xl" value="<?= h($occupancyDate) ?>" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Ocupação (UH)</label>
                            <input type="number" min="0" name="ocupacao_uh" class="form-control input-xl" value="<?= h((string)($occupancy['ocupacao_uh'] ?? '')) ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Ocupação (PAX)</label>
                            <input type="number" min="0" name="ocupacao_pax" class="form-control input-xl" value="<?= h((string)($occupancy['ocupacao_pax'] ?? '')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observação</label>
                            <input type="text" maxlength="255" name="observacao" class="form-control input-xl" value="<?= h((string)($occupancy['observacao'] ?? '')) ?>" placeholder="Ex.: ocupação parcial por manutenção em bloco.">
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Salvar ocupação</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info mb-3">Somente <strong>admin/supervisor</strong> pode editar ocupação diária.</div>
                <?php endif; ?>

                <div class="row g-3 mt-1">
                    <div class="col-12 col-md-6">
                        <div class="p-3 rounded-3" style="background:var(--ab-soft-bg); border:1px solid var(--ab-border);">
                            <div class="text-muted small">PAX buffet no dia</div>
                            <div class="h4 mb-0"><?= (int)$buffetPaxDia ?></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="p-3 rounded-3" style="background:var(--ab-soft-bg); border:1px solid var(--ab-border);">
                            <div class="text-muted small">Ocupação PAX informada</div>
                            <div class="h4 mb-0"><?= isset($occupancy['ocupacao_pax']) ? (int)$occupancy['ocupacao_pax'] : 0 ?></div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 rounded-3" style="background:var(--ab-soft-bg); border:1px solid var(--ab-border);">
                            <div class="text-muted small">Índice buffet/ocupação</div>
                            <div class="h5 mb-0"><?= $taxaBuffetOcupacao === null ? 'Sem base de ocupação para comparação.' : h((string)$taxaBuffetOcupacao) . '%' ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card p-4 h-100">
                <div class="section-title mb-3">
                    <div class="icon"><i class="bi bi-bar-chart-steps"></i></div>
                    <div>
                        <div class="text-uppercase text-muted small">Série temporal</div>
                        <h5 class="fw-bold mb-0">Ocupação x PAX por operação</h5>
                    </div>
                </div>
                <div id="kpiOccupancyVsBuffetChart" style="min-height: 320px;"></div>
            </div>
        </div>
    </div>

    <div class="row g-4 split-full">
        <div class="col-12 col-xl-7">
            <div class="card p-4 h-100">
                <div class="section-title mb-3">
                    <div class="icon"><i class="bi bi-person-badge"></i></div>
                    <div>
                        <div class="text-uppercase text-muted small">Equipe</div>
                        <h5 class="fw-bold mb-0">Ranking de operadores</h5>
                    </div>
                </div>
                <div class="table-responsive kpi-ranking-table">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Operador</th>
                                <th>Registros</th>
                                <th>PAX</th>
                                <th>Qualidade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($operatorRanking as $row): ?>
                                <tr>
                                    <td><?= h($row['nome']) ?></td>
                                    <td><?= (int)$row['registros'] ?></td>
                                    <td><?= (int)$row['pax_total'] ?></td>
                                    <td>
                                        <?php if ((float)$row['indice_qualidade'] >= 90): ?>
                                            <span class="badge badge-success"><?= h((string)$row['indice_qualidade']) ?>%</span>
                                        <?php elseif ((float)$row['indice_qualidade'] >= 75): ?>
                                            <span class="badge badge-warning"><?= h((string)$row['indice_qualidade']) ?>%</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger"><?= h((string)$row['indice_qualidade']) ?>%</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($operatorRanking)): ?>
                                <tr><td colspan="4" class="text-muted">Sem dados para ranking.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="card p-4 h-100">
                <div class="section-title mb-3">
                    <div class="icon"><i class="bi bi-lightbulb"></i></div>
                    <div>
                        <div class="text-uppercase text-muted small">Insights</div>
                        <h5 class="fw-bold mb-0">Ações sugeridas</h5>
                    </div>
                </div>
                <?php foreach ($insights as $insight): ?>
                    <div class="alert alert-<?= h($insight['type']) ?> py-2 px-3 mb-2">
                        <div class="fw-bold small"><?= h($insight['title']) ?></div>
                        <div class="small"><?= h($insight['text']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
(() => {
    const start = document.querySelector('input[name="data_inicio"]');
    const end = document.querySelector('input[name="data_fim"]');
    if (!start || !end) return;
    document.querySelectorAll('[data-range]').forEach(btn => {
        btn.addEventListener('click', () => {
            const fmt = (d) => d.toISOString().slice(0, 10);
            const days = parseInt(btn.dataset.range, 10);
            const today = new Date();
            const from = new Date();
            if (days === 1) {
                from.setDate(today.getDate() - 1);
                start.value = fmt(from);
                end.value = fmt(from);
                return;
            }
            from.setDate(today.getDate() - (days - 1));
            start.value = fmt(from);
            end.value = fmt(today);
        });
    });
})();

(() => {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const isMobile = window.matchMedia('(max-width: 767.98px)').matches;
    const axisColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? 'rgba(148,163,184,.14)' : 'rgba(100,116,139,.14)';
    const textColor = isDark ? '#e2e8f0' : '#0f172a';

    const hourlyFlowRows = <?= json_encode($hourlyOperationFlow, JSON_UNESCAPED_UNICODE) ?> || [];
    const opMix = <?= json_encode($operationMix, JSON_UNESCAPED_UNICODE) ?> || [];
    const restMix = <?= json_encode($restaurantMix, JSON_UNESCAPED_UNICODE) ?> || [];
    const timeline = <?= json_encode($occupancyTimeline, JSON_UNESCAPED_UNICODE) ?> || [];

    const flowEl = document.getElementById('kpiHourlyOperationChart');
    if (flowEl) {
        if (hourlyFlowRows.length === 0) {
            flowEl.innerHTML = '<div class="text-muted small">Sem dados de fluxo por horário no período selecionado.</div>';
        } else {
            const hours = Array.from(new Set(hourlyFlowRows.map((r) => String(r.hora || '')))).filter(Boolean).sort();
            const operations = Array.from(new Set(hourlyFlowRows.map((r) => String(r.operacao || 'Sem operação'))));
            const valueMap = new Map();
            hourlyFlowRows.forEach((row) => {
                valueMap.set(`${row.hora}|${row.operacao}`, Number(row.total_pax || 0));
            });
            const series = operations.map((operation) => ({
                name: operation,
                data: hours.map((hour) => valueMap.get(`${hour}|${operation}`) || 0),
            }));

            new ApexCharts(flowEl, {
                chart: { type: 'bar', stacked: true, height: isMobile ? 300 : 340, toolbar: { show: false }, fontFamily: 'SF Pro Display, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif' },
                series,
                plotOptions: { bar: { borderRadius: 6, columnWidth: isMobile ? '62%' : '48%' } },
                xaxis: { categories: hours, labels: { style: { colors: axisColor } } },
                yaxis: { labels: { style: { colors: axisColor } }, title: { text: 'PAX', style: { color: axisColor } } },
                legend: { position: 'bottom', horizontalAlign: 'left', labels: { colors: textColor }, itemMargin: { horizontal: 8, vertical: 4 } },
                grid: { borderColor: gridColor },
                colors: ['#f97316', '#0ea5e9', '#16a34a', '#8b5cf6', '#f59e0b', '#ef4444', '#14b8a6'],
                dataLabels: { enabled: false },
                theme: { mode: isDark ? 'dark' : 'light' },
                tooltip: {
                    shared: true,
                    intersect: false,
                    theme: isDark ? 'dark' : 'light',
                    custom: ({ dataPointIndex, w }) => {
                        const hour = w.globals.labels[dataPointIndex] || '';
                        const lines = w.config.series
                            .map((item) => ({ name: item.name, value: Number((item.data || [])[dataPointIndex] || 0) }))
                            .filter((item) => item.value > 0);
                        if (lines.length === 0) return '';
                        return `
                            <div class="px-3 py-2">
                                <div class="fw-bold mb-1">${hour}</div>
                                ${lines.map((item) => `<div>${item.name}: ${item.value} PAX</div>`).join('')}
                            </div>
                        `;
                    }
                },
            }).render();
        }
    }

    const opEl = document.getElementById('kpiOperationMixChart');
    if (opEl) {
        if (opMix.length === 0) {
            opEl.innerHTML = '<div class="text-muted small">Sem dados para distribuição por operação.</div>';
        } else {
            new ApexCharts(opEl, {
                chart: { type: 'donut', height: isMobile ? 260 : 220, fontFamily: 'SF Pro Display, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif' },
                series: opMix.map((r) => Number(r.total_pax || 0)),
                labels: opMix.map((r) => String(r.nome || 'Sem nome')),
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'left',
                    labels: { colors: textColor },
                    itemMargin: { horizontal: 8, vertical: 4 }
                },
                dataLabels: { enabled: true },
                theme: { mode: isDark ? 'dark' : 'light' }
            }).render();
        }
    }

    const restEl = document.getElementById('kpiRestaurantMixChart');
    if (restEl) {
        if (restMix.length === 0) {
            restEl.innerHTML = '<div class="text-muted small">Sem dados para PAX por restaurante.</div>';
        } else {
            new ApexCharts(restEl, {
                chart: { type: 'bar', height: isMobile ? 280 : 240, toolbar: { show: false }, fontFamily: 'SF Pro Display, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif' },
                series: [{ name: 'PAX', data: restMix.map((r) => Number(r.total_pax || 0)) }],
                xaxis: {
                    categories: restMix.map((r) => String(r.nome || 'Sem nome')),
                    labels: {
                        style: { colors: axisColor },
                        rotate: isMobile ? -30 : 0,
                        trim: true,
                        hideOverlappingLabels: true
                    }
                },
                yaxis: { labels: { style: { colors: axisColor } } },
                plotOptions: { bar: { borderRadius: 8, distributed: true } },
                grid: { borderColor: gridColor },
                colors: ['#f97316', '#fb923c', '#f59e0b', '#fbbf24', '#fdba74', '#ea580c'],
                dataLabels: { enabled: false },
                theme: { mode: isDark ? 'dark' : 'light' }
            }).render();
        }
    }

    const occEl = document.getElementById('kpiOccupancyVsBuffetChart');
    if (occEl) {
        const labels = timeline.map((r) => r.data_ref);
        const serieOcupacao = timeline.map((r) => r.ocupacao_pax === null ? null : Number(r.ocupacao_pax));
        const serieCafe = timeline.map((r) => Number(r.cafe_pax || 0));
        const serieAlmoco = timeline.map((r) => Number(r.almoco_pax || 0));
        const serieJantar = timeline.map((r) => Number(r.jantar_pax || 0));

        if (labels.length === 0) {
            occEl.innerHTML = '<div class="text-muted small">Sem dados de ocupação no período.</div>';
        } else {
            new ApexCharts(occEl, {
                chart: { type: 'line', height: isMobile ? 280 : 320, toolbar: { show: false }, fontFamily: 'SF Pro Display, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif' },
                stroke: { width: [3, 2, 2, 2], curve: 'smooth' },
                series: [
                    { name: 'Ocupação (PAX)', data: serieOcupacao },
                    { name: 'Café da manhã', data: serieCafe },
                    { name: 'Almoço', data: serieAlmoco },
                    { name: 'Jantar', data: serieJantar }
                ],
                xaxis: {
                    categories: labels,
                    labels: {
                        style: { colors: axisColor },
                        rotate: isMobile ? -35 : 0,
                        hideOverlappingLabels: true
                    }
                },
                yaxis: { labels: { style: { colors: axisColor } } },
                colors: ['#0ea5e9', '#f97316', '#16a34a', '#8b5cf6'],
                grid: { borderColor: gridColor },
                tooltip: { shared: true, intersect: false, theme: isDark ? 'dark' : 'light' },
                theme: { mode: isDark ? 'dark' : 'light' }
            }).render();
        }
    }
})();
</script>
