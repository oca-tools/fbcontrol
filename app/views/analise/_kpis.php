<?php
/* Aba KPIs do hub Análise (contrato: KpiOperacionalService::montarPainelKpis). */
$filters = $this->data['filters'] ?? [];
$summary = $this->data['summary'] ?? [];
$restaurantes = $this->data['restaurantes'] ?? [];
$operacoes = $this->data['operacoes'] ?? [];
$operatorRanking = $this->data['operator_ranking'] ?? [];
$operationMix = $this->data['operation_mix'] ?? [];
$restaurantMix = $this->data['restaurant_mix'] ?? [];
$tematicosKpi = $this->data['tematicos'] ?? [];
$taxaNoShow = $this->data['taxa_no_show'] ?? null;
$taxaComparecimentoTematico = $this->data['taxa_comparecimento_tematico'] ?? null;
$insights = $this->data['insights'] ?? [];
$occupancyDate = (string)($this->data['occupancy_date'] ?? date('Y-m-d'));
$occupancy = $this->data['occupancy'] ?? [];
$buffetPaxDia = (int)($this->data['buffet_pax_dia'] ?? 0);
$taxaBuffetOcupacao = $this->data['taxa_buffet_ocupacao'] ?? null;
$occupancyTimeline = $this->data['occupancy_timeline'] ?? [];
$canEditOcupacao = (bool)($this->data['can_edit_ocupacao'] ?? false);

$mixMax = static function (array $rows): int {
    $max = 0;
    foreach ($rows as $row) {
        $max = max($max, (int)($row['pax_total'] ?? $row['registros'] ?? 0));
    }
    return $max;
};
$maxOperacaoMix = $mixMax($operationMix);
$maxRestauranteMix = $mixMax($restaurantMix);
$badgeQualidade = static function ($indice): string {
    $valor = (float)$indice;
    if ($valor >= 90) {
        return 'fb-badge fb-badge--ok';
    }
    if ($valor >= 75) {
        return 'fb-badge fb-badge--warn';
    }
    return 'fb-badge fb-badge--danger';
};
$notaInsight = static function (string $tipo): string {
    $mapa = ['success' => 'ok', 'warning' => 'warn', 'danger' => 'danger', 'info' => 'info'];
    return $mapa[$tipo] ?? 'info';
};
$topLabel = static function ($valor): string {
    if (is_array($valor)) {
        $valor = $valor['nome'] ?? $valor['restaurante'] ?? $valor['operacao'] ?? '-';
    }
    $texto = trim((string)$valor);
    return normalize_mojibake($texto !== '' ? $texto : '-');
};
?>
    <section class="fb-card fb-mt">
        <form method="get" action="/" class="row g-3 align-items-end" data-fb-filters>
            <input type="hidden" name="r" value="analise/index">
            <input type="hidden" name="aba" value="kpis">
            <div class="col-6 col-md-3">
                <label class="fb-label">Data única</label>
                <input type="date" class="fb-input" name="data" value="<?= h($filters['data'] ?? '') ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="fb-label">Início</label>
                <input type="date" class="fb-input" name="data_inicio" value="<?= h($filters['data_inicio'] ?? '') ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="fb-label">Fim</label>
                <input type="date" class="fb-input" name="data_fim" value="<?= h($filters['data_fim'] ?? '') ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="fb-label">Status</label>
                <select class="fb-select" name="status">
                    <option value="">Todos</option>
                    <?php foreach (FiltroOperacionalService::STATUS_FILTERS as $statusOption): ?>
                        <option value="<?= h($statusOption) ?>" <?= ($filters['status'] ?? '') === $statusOption ? 'selected' : '' ?>><?= h(str_replace('_', ' ', $statusOption)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="fb-label">Restaurante</label>
                <select class="fb-select" name="restaurante_id">
                    <option value="">Todos</option>
                    <?php foreach ($restaurantes as $rest): ?>
                        <option value="<?= (int)$rest['id'] ?>" <?= ($filters['restaurante_id'] ?? '') == $rest['id'] ? 'selected' : '' ?>><?= h($rest['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="fb-label">Operação</label>
                <select class="fb-select" name="operacao_id">
                    <option value="">Todas</option>
                    <?php foreach ($operacoes as $op): ?>
                        <option value="<?= (int)$op['id'] ?>" <?= ($filters['operacao_id'] ?? '') == $op['id'] ? 'selected' : '' ?>><?= h($op['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="fb-btn fb-btn--primary fb-grow">Aplicar</button>
                <a class="fb-btn fb-btn--ghost" href="/?r=analise/index&aba=kpis">Últimos 30 dias</a>
            </div>
        </form>
    </section>

    <div class="fb-metric-grid fb-mt">
        <div class="fb-metric">
            <p class="fb-metric__label">PAX no período</p>
            <p class="fb-metric__value"><?= number_format((int)($summary['total_pax'] ?? 0), 0, ',', '.') ?></p>
            <p class="fb-metric__delta"><?= number_format((int)($summary['total_registros'] ?? 0), 0, ',', '.') ?> registros · <?= h((string)($summary['media_pax_registro'] ?? '-')) ?> PAX/registro</p>
        </div>
        <div class="fb-metric">
            <p class="fb-metric__label">Índice de qualidade</p>
            <p class="fb-metric__value"><?= h((string)($summary['indice_qualidade'] ?? '-')) ?>%</p>
            <p class="fb-metric__delta">taxa de alertas: <?= h((string)($summary['taxa_alertas'] ?? '-')) ?>%</p>
        </div>
        <div class="fb-metric">
            <p class="fb-metric__label">UHs únicas</p>
            <p class="fb-metric__value"><?= number_format((int)($summary['uhs_unicas'] ?? 0), 0, ',', '.') ?></p>
            <p class="fb-metric__delta"><?= h((string)($summary['pax_por_uh'] ?? '-')) ?> PAX por UH</p>
        </div>
        <div class="fb-metric">
            <p class="fb-metric__label">Comparecimento temático</p>
            <p class="fb-metric__value"><?= $taxaComparecimentoTematico !== null ? h((string)$taxaComparecimentoTematico) . '%' : '-' ?></p>
            <p class="fb-metric__delta">no-show: <?= $taxaNoShow !== null ? h((string)$taxaNoShow) . '%' : '-' ?></p>
        </div>
    </div>

    <div class="fb-row fb-mt" style="gap: 8px;">
        <span class="fb-badge fb-badge--nao-informado">Top restaurante: <?= h($topLabel($summary['top_restaurante'] ?? '-')) ?></span>
        <span class="fb-badge fb-badge--nao-informado">Top operação: <?= h($topLabel($summary['top_operacao'] ?? '-')) ?></span>
        <span class="fb-badge fb-badge--nao-informado">Operadores ativos: <?= (int)($summary['operadores_ativos'] ?? 0) ?></span>
        <span class="fb-badge fb-badge--nao-informado">Day use: <?= h((string)($summary['taxa_day_use'] ?? '-')) ?>%</span>
        <span class="fb-badge fb-badge--nao-informado">Não informado: <?= h((string)($summary['taxa_nao_informado'] ?? '-')) ?>%</span>
    </div>

    <?php if (!empty($insights)): ?>
        <div class="row g-2 fb-mt">
            <?php foreach ($insights as $insight): ?>
                <?php $nota = $notaInsight((string)($insight['type'] ?? '')); ?>
                <div class="col-12 col-md-6">
                    <div style="background: var(--fb-<?= $nota ?>-bg); color: var(--fb-<?= $nota ?>); border-radius: var(--fb-radius); padding: 10px 14px;">
                        <div style="font-weight: 600; font-size: 0.85rem;"><?= h((string)($insight['title'] ?? '')) ?></div>
                        <div style="font-size: 0.8rem;"><?= h((string)($insight['text'] ?? '')) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="row g-3 fb-mt">
        <div class="col-12 col-lg-6">
            <div class="fb-card h-100">
                <div class="fb-card__head">
                    <h5 class="fb-card__title">Distribuição por operação</h5>
                </div>
                <ul class="fb-list">
                    <?php foreach ($operationMix as $row): ?>
                        <?php $valorMix = (int)($row['pax_total'] ?? $row['registros'] ?? 0); ?>
                        <li class="fb-list__item">
                            <div class="fb-grow">
                                <div class="fb-row" style="justify-content: space-between;">
                                    <span style="font-size: 0.88rem; font-weight: 500;"><?= h(normalize_mojibake((string)($row['nome'] ?? ''))) ?></span>
                                    <span class="fb-muted fb-num" style="font-size: 0.85rem;"><?= number_format($valorMix, 0, ',', '.') ?> PAX</span>
                                </div>
                                <div class="fb-progress" style="margin-top: 5px;"><div class="fb-progress__bar" style="width: <?= $maxOperacaoMix > 0 ? (int)round(($valorMix / $maxOperacaoMix) * 100) : 0 ?>%;"></div></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($operationMix)): ?>
                        <li class="fb-list__item fb-muted" style="font-size: 0.85rem;">Sem dados no período.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="fb-card h-100">
                <div class="fb-card__head">
                    <h5 class="fb-card__title">Distribuição por restaurante</h5>
                </div>
                <ul class="fb-list">
                    <?php foreach ($restaurantMix as $row): ?>
                        <?php $valorMix = (int)($row['pax_total'] ?? $row['registros'] ?? 0); ?>
                        <li class="fb-list__item">
                            <div class="fb-grow">
                                <div class="fb-row" style="justify-content: space-between;">
                                    <span style="font-size: 0.88rem; font-weight: 500;"><?= h(normalize_mojibake((string)($row['nome'] ?? ''))) ?></span>
                                    <span class="fb-muted fb-num" style="font-size: 0.85rem;"><?= number_format($valorMix, 0, ',', '.') ?> PAX</span>
                                </div>
                                <div class="fb-progress" style="margin-top: 5px;"><div class="fb-progress__bar" style="width: <?= $maxRestauranteMix > 0 ? (int)round(($valorMix / $maxRestauranteMix) * 100) : 0 ?>%;"></div></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($restaurantMix)): ?>
                        <li class="fb-list__item fb-muted" style="font-size: 0.85rem;">Sem dados no período.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="row g-3 fb-mt">
        <div class="col-12 col-lg-7">
            <div class="fb-card h-100">
                <div class="fb-card__head">
                    <h5 class="fb-card__title">Ocupação × PAX buffet</h5>
                    <span class="fb-muted" style="font-size: 0.78rem;"><?= h(format_date_br($occupancyDate)) ?></span>
                </div>
                <div class="fb-metric-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
                    <div class="fb-metric">
                        <p class="fb-metric__label">Ocupação (PAX)</p>
                        <p class="fb-metric__value"><?= $occupancy !== [] && ($occupancy['ocupacao_pax'] ?? null) !== null ? number_format((int)$occupancy['ocupacao_pax'], 0, ',', '.') : '-' ?></p>
                        <p class="fb-metric__delta"><?= $occupancy !== [] && ($occupancy['ocupacao_uh'] ?? null) !== null ? (int)$occupancy['ocupacao_uh'] . ' UHs' : 'sem lançamento' ?></p>
                    </div>
                    <div class="fb-metric">
                        <p class="fb-metric__label">PAX buffet no dia</p>
                        <p class="fb-metric__value"><?= number_format($buffetPaxDia, 0, ',', '.') ?></p>
                        <p class="fb-metric__delta">consumo registrado</p>
                    </div>
                    <div class="fb-metric">
                        <p class="fb-metric__label">Captura</p>
                        <p class="fb-metric__value"><?= $taxaBuffetOcupacao !== null ? h((string)$taxaBuffetOcupacao) . '%' : '-' ?></p>
                        <p class="fb-metric__delta">buffet ÷ ocupação</p>
                    </div>
                </div>
                <?php if ($canEditOcupacao): ?>
                    <form method="post" action="/?r=kpis/saveOcupacao" class="row g-2 align-items-end fb-mt">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <div class="col-6 col-md-3">
                            <label class="fb-label">Data</label>
                            <input type="date" class="fb-input" name="data_ref" value="<?= h($occupancyDate) ?>" required>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="fb-label">UHs</label>
                            <input type="number" min="0" class="fb-input" name="ocupacao_uh" value="<?= h((string)($occupancy['ocupacao_uh'] ?? '')) ?>">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="fb-label">PAX</label>
                            <input type="number" min="0" class="fb-input" name="ocupacao_pax" value="<?= h((string)($occupancy['ocupacao_pax'] ?? '')) ?>">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="fb-label">Observação</label>
                            <input type="text" class="fb-input" name="observacao" value="<?= h((string)($occupancy['observacao'] ?? '')) ?>">
                        </div>
                        <div class="col-12 col-md-2">
                            <button type="submit" class="fb-btn fb-btn--primary w-100">Salvar</button>
                        </div>
                    </form>
                <?php endif; ?>
                <?php if (!empty($occupancyTimeline)): ?>
                    <table class="fb-table fb-mt">
                        <thead>
                            <tr><th>Data</th><th>UHs</th><th>Ocupação PAX</th><th>Café</th><th>Almoço</th><th>Jantar</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($occupancyTimeline as $linha): ?>
                                <tr>
                                    <td data-label="Data" class="fb-num"><?= h(format_date_br((string)($linha['data_ref'] ?? ''))) ?></td>
                                    <td data-label="UHs" class="fb-num"><?= ($linha['ocupacao_uh'] ?? null) !== null ? (int)$linha['ocupacao_uh'] : '-' ?></td>
                                    <td data-label="Ocupação PAX" class="fb-num"><?= ($linha['ocupacao_pax'] ?? null) !== null ? (int)$linha['ocupacao_pax'] : '-' ?></td>
                                    <td data-label="Café" class="fb-num"><?= (int)($linha['cafe_pax'] ?? 0) ?></td>
                                    <td data-label="Almoço" class="fb-num"><?= (int)($linha['almoco_pax'] ?? 0) ?></td>
                                    <td data-label="Jantar" class="fb-num"><?= (int)($linha['jantar_pax'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-12 col-lg-5">
            <div class="fb-card h-100">
                <div class="fb-card__head">
                    <h5 class="fb-card__title">Ranking de operadores</h5>
                    <span class="fb-muted" style="font-size: 0.78rem;">top 10</span>
                </div>
                <table class="fb-table">
                    <thead>
                        <tr><th>Operador</th><th>Registros</th><th>PAX</th><th>Qualidade</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($operatorRanking as $row): ?>
                            <tr>
                                <td data-label="Operador"><?= h(normalize_mojibake((string)($row['nome'] ?? ''))) ?></td>
                                <td data-label="Registros" class="fb-num"><?= (int)($row['registros'] ?? 0) ?></td>
                                <td data-label="PAX" class="fb-num"><?= (int)($row['pax_total'] ?? 0) ?></td>
                                <td data-label="Qualidade"><span class="<?= $badgeQualidade($row['indice_qualidade'] ?? 0) ?>"><?= h((string)($row['indice_qualidade'] ?? '-')) ?>%</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (empty($operatorRanking)): ?>
                    <p class="fb-muted mb-0" style="font-size: 0.85rem;">Sem registros no período.</p>
                <?php endif; ?>
                <div class="fb-row fb-mt" style="gap: 8px;">
                    <span class="fb-badge fb-badge--ok">Temáticos finalizados: <?= (int)($tematicosKpi['finalizadas'] ?? 0) ?></span>
                    <span class="fb-badge fb-badge--danger">No-shows: <?= (int)($tematicosKpi['no_shows'] ?? 0) ?></span>
                </div>
            </div>
        </div>
    </div>
