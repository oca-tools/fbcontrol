<?php
$filters = $this->data['filters'] ?? [];
$summary = $this->data['summary'] ?? [];
$dailyTrend = $this->data['daily_trend'] ?? [];
$operatorRanking = $this->data['operator_ranking'] ?? [];
$operationMix = $this->data['operation_mix'] ?? [];
$restaurantMix = $this->data['restaurant_mix'] ?? [];
$candleSeries = $this->data['candle_series'] ?? [];
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
    .kpi-ranking-table {
        max-height: 360px;
        overflow-y: auto;
    }
    @media (max-width: 1199.98px) {
        .kpi-ranking-table {
            max-height: none;
        }
    }
</style>

<div class="split-pane-layout">
    <div class="card card-soft p-4 mb-4 split-full">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div class="section-title">
                <div class="icon"><i class="bi bi-speedometer2"></i></div>
                <div>
                    <div class="text-uppercase text-muted small">Visão executiva 2.0</div>
                    <h3 class="fw-bold mb-1">KPIs Estratégicos</h3>
                    <div class="text-muted">Análises visuais para gestão operacional: tendência, desempenho e aderência à ocupação.</div>
                </div>
            </div>
            <a class="btn btn-primary js-export-btn"
               data-toast="Exportado com sucesso. Tendência diária em CSV."
               href="/?r=kpis/exportTrend&data=<?= h($filters['data'] ?? '') ?>&data_inicio=<?= h($filters['data_inicio'] ?? '') ?>&data_fim=<?= h($filters['data_fim'] ?? '') ?>&restaurante_id=<?= h($filters['restaurante_id'] ?? '') ?>&operacao_id=<?= h($filters['operacao_id'] ?? '') ?>&status=<?= h($filters['status'] ?? '') ?>">
                <i class="bi bi-download me-1"></i>Exportar tendência
            </a>
        </div>

        <form class="row g-3 align-items-end mt-2" method="get" action="/">
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
            <div class="col-12 d-flex flex-wrap gap-2">
                <button class="btn btn-outline-primary" type="button" data-range="1">Ontem</button>
                <button class="btn btn-outline-primary" type="button" data-range="7">Últimos 7 dias</button>
                <button class="btn btn-outline-primary" type="button" data-range="30">Últimos 30 dias</button>
                <button class="btn btn-primary btn-xl">Aplicar filtros</button>
                <a class="btn btn-primary btn-xl" href="/?r=kpis/index">Remover filtro</a>
            </div>
        </form>
    </div>

    <div class="row g-4 mb-4 split-full">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card metric-card p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="metric-icon"><i class="bi bi-shield-check"></i></div>
                    <div>
                        <div class="text-muted small">Índice de qualidade</div>
                        <div class="display-6 fw-bold"><?= h((string)($summary['indice_qualidade'] ?? 0)) ?>%</div>
                    </div>
                </div>
                <span class="stat-chip mt-3"><i class="bi bi-exclamation-triangle"></i>Alertas: <?= h((string)($summary['taxa_alertas'] ?? 0)) ?>%</span>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
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
        <div class="col-12 col-md-6 col-xl-3">
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
        <div class="col-12 col-md-6 col-xl-3">
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
                        <div class="text-uppercase text-muted small">Análise visual</div>
                        <h5 class="fw-bold mb-0">Candle operacional (fluxo por dia)</h5>
                        <div class="text-muted small">Abertura, pico, mínima e fechamento do fluxo de PAX por hora.</div>
                    </div>
                </div>
                <div id="kpiCandleChart" style="min-height: 320px;"></div>
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
                        <h5 class="fw-bold mb-0">Ocupação declarada x PAX buffet</h5>
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
                    <div class="icon"><i class="bi bi-graph-up"></i></div>
                    <div>
                        <div class="text-uppercase text-muted small">Tendência diária</div>
                        <h5 class="fw-bold mb-0">Evolução operacional</h5>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Registros</th>
                                <th>PAX</th>
                                <th>UHs</th>
                                <th>Alertas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dailyTrend as $row): ?>
                                <?php $alertas = (int)$row['duplicados'] + (int)$row['fora_horario']; ?>
                                <tr>
                                    <td><?= h($row['data_ref']) ?></td>
                                    <td><?= (int)$row['registros'] ?></td>
                                    <td><?= (int)$row['pax_total'] ?></td>
                                    <td><?= (int)$row['uhs_unicas'] ?></td>
                                    <td>
                                        <?php if ($alertas > 0): ?>
                                            <span class="badge badge-warning"><?= $alertas ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-success">0</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($dailyTrend)): ?>
                                <tr><td colspan="5" class="text-muted">Sem dados no período selecionado.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="card p-4 mb-4">
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

            <div class="card p-4">
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
    const axisColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? 'rgba(148,163,184,.14)' : 'rgba(100,116,139,.14)';
    const textColor = isDark ? '#e2e8f0' : '#0f172a';

    const candleRows = <?= json_encode($candleSeries, JSON_UNESCAPED_UNICODE) ?> || [];
    const opMix = <?= json_encode($operationMix, JSON_UNESCAPED_UNICODE) ?> || [];
    const restMix = <?= json_encode($restaurantMix, JSON_UNESCAPED_UNICODE) ?> || [];
    const timeline = <?= json_encode($occupancyTimeline, JSON_UNESCAPED_UNICODE) ?> || [];

    const candleEl = document.getElementById('kpiCandleChart');
    if (candleEl) {
        if (candleRows.length === 0) {
            candleEl.innerHTML = '<div class="text-muted small">Sem dados para o candle no período selecionado.</div>';
        } else {
            const candleData = candleRows.map((r) => ({
                x: new Date(r.data_ref).getTime(),
                y: [Number(r.open_pax || 0), Number(r.high_pax || 0), Number(r.low_pax || 0), Number(r.close_pax || 0)]
            }));

            new ApexCharts(candleEl, {
                chart: { type: 'candlestick', height: 320, toolbar: { show: false }, fontFamily: 'SF Pro Display, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif' },
                series: [{ data: candleData }],
                xaxis: { type: 'datetime', labels: { style: { colors: axisColor } } },
                yaxis: { labels: { style: { colors: axisColor } }, title: { text: 'PAX/hora', style: { color: axisColor } } },
                grid: { borderColor: gridColor },
                theme: { mode: isDark ? 'dark' : 'light' },
                tooltip: { theme: isDark ? 'dark' : 'light' }
            }).render();
        }
    }

    const opEl = document.getElementById('kpiOperationMixChart');
    if (opEl) {
        if (opMix.length === 0) {
            opEl.innerHTML = '<div class="text-muted small">Sem dados para distribuição por operação.</div>';
        } else {
            new ApexCharts(opEl, {
                chart: { type: 'donut', height: 220, fontFamily: 'SF Pro Display, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif' },
                series: opMix.map((r) => Number(r.total_pax || 0)),
                labels: opMix.map((r) => String(r.nome || 'Sem nome')),
                legend: { labels: { colors: textColor } },
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
                chart: { type: 'bar', height: 240, toolbar: { show: false }, fontFamily: 'SF Pro Display, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif' },
                series: [{ name: 'PAX', data: restMix.map((r) => Number(r.total_pax || 0)) }],
                xaxis: { categories: restMix.map((r) => String(r.nome || 'Sem nome')), labels: { style: { colors: axisColor } } },
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
        const serieBuffet = timeline.map((r) => Number(r.buffet_pax || 0));

        if (labels.length === 0) {
            occEl.innerHTML = '<div class="text-muted small">Sem dados de ocupação no período.</div>';
        } else {
            new ApexCharts(occEl, {
                chart: { type: 'line', height: 320, toolbar: { show: false }, fontFamily: 'SF Pro Display, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif' },
                stroke: { width: [3, 3], curve: 'smooth' },
                series: [
                    { name: 'Ocupação (PAX)', data: serieOcupacao },
                    { name: 'PAX Buffet', data: serieBuffet }
                ],
                xaxis: { categories: labels, labels: { style: { colors: axisColor } } },
                yaxis: { labels: { style: { colors: axisColor } } },
                colors: ['#0ea5e9', '#f97316'],
                grid: { borderColor: gridColor },
                tooltip: { shared: true, intersect: false, theme: isDark ? 'dark' : 'light' },
                theme: { mode: isDark ? 'dark' : 'light' }
            }).render();
        }
    }
})();
</script>
