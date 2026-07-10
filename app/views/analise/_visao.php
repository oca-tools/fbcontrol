<?php
/* Aba Visão geral do hub Análise (contrato: DashboardOperacionalService::montarDashboardGeral). */
$filters = $this->data['filters'] ?? [];
$restaurantes = $this->data['restaurantes'] ?? [];
$operacoes = $this->data['operacoes'] ?? [];
$stats = $this->data['stats'] ?? [];
$recentes = $this->data['recentes'] ?? [];

$totalAcessos = (int)($stats['total_acessos'] ?? 0);
$dupCount = (int)($stats['duplicados'] ?? 0);
$foraCount = (int)($stats['fora_horario'] ?? 0);
$multiploCount = (int)($stats['multiplos'] ?? 0);
$alertasAtivos = $dupCount + $foraCount + $multiploCount;
$totalPax = array_sum(array_map(static fn($r) => (int)($r['total_pax'] ?? 0), $stats['totais_restaurante'] ?? []));

$fluxo = $stats['fluxo_horario'] ?? [];
$fluxoMax = 0;
foreach ($fluxo as $fluxoRow) {
    $fluxoMax = max($fluxoMax, (int)($fluxoRow['total_pax'] ?? 0));
}

$maxOperacao = 0;
foreach ($stats['totais_operacao'] ?? [] as $opRow) {
    $maxOperacao = max($maxOperacao, (int)($opRow['total_pax'] ?? 0));
}
$maxRestaurante = 0;
foreach ($stats['totais_restaurante'] ?? [] as $restRow) {
    $maxRestaurante = max($maxRestaurante, (int)($restRow['total_pax'] ?? 0));
}
$restauranteIdPorNome = [];
foreach ($restaurantes as $rest) {
    $restauranteIdPorNome[normalize_mojibake((string)$rest['nome'])] = (int)$rest['id'];
}
$queryAtual = array_filter($filters, static fn($v) => $v !== '');
?>
    <section class="fb-card fb-mt">
        <form method="get" action="/" class="row g-3 align-items-end" data-fb-filters>
            <input type="hidden" name="r" value="analise/index">
            <input type="hidden" name="aba" value="visao">
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
                <a class="fb-btn fb-btn--ghost" href="/?r=analise/index">Hoje</a>
            </div>
        </form>
    </section>

    <div class="fb-metric-grid fb-mt">
        <div class="fb-metric">
            <p class="fb-metric__label">PAX no recorte</p>
            <p class="fb-metric__value"><?= number_format($totalPax, 0, ',', '.') ?></p>
            <p class="fb-metric__delta">inclui temáticos finalizados</p>
        </div>
        <div class="fb-metric">
            <p class="fb-metric__label">Acessos registrados</p>
            <p class="fb-metric__value"><?= number_format($totalAcessos, 0, ',', '.') ?></p>
            <p class="fb-metric__delta">lançamentos de salão</p>
        </div>
        <div class="fb-metric">
            <p class="fb-metric__label">Alertas</p>
            <p class="fb-metric__value" <?= $alertasAtivos > 0 ? 'style="color: var(--fb-danger);"' : '' ?>><?= number_format($alertasAtivos, 0, ',', '.') ?></p>
            <p class="fb-metric__delta"><?= $dupCount ?> dup · <?= $foraCount ?> fora · <?= $multiploCount ?> múlt</p>
        </div>
        <div class="fb-metric">
            <p class="fb-metric__label">Exceções (PAX)</p>
            <p class="fb-metric__value"><?= number_format((int)($stats['nao_informado_pax'] ?? 0) + (int)($stats['day_use_pax'] ?? 0), 0, ',', '.') ?></p>
            <p class="fb-metric__delta"><?= (int)($stats['nao_informado_pax'] ?? 0) ?> não inf. · <?= (int)($stats['day_use_pax'] ?? 0) ?> day use</p>
        </div>
    </div>

    <div class="fb-row fb-mt" style="gap: 8px;">
        <span class="fb-badge fb-badge--nao-informado">Privileged: <?= (int)($stats['privileged_acessos'] ?? 0) ?> acessos · <?= (int)($stats['privileged_pax'] ?? 0) ?> PAX</span>
        <span class="fb-badge fb-badge--nao-informado">VIP Premium: <?= (int)($stats['vip_premium_acessos'] ?? 0) ?> acessos · <?= (int)($stats['vip_premium_pax'] ?? 0) ?> PAX</span>
    </div>

    <div class="row g-3 fb-mt">
        <div class="col-12 col-lg-7">
            <div class="fb-card h-100">
                <div class="fb-card__head">
                    <h5 class="fb-card__title">Fluxo por horário</h5>
                    <span class="fb-muted" style="font-size: 0.78rem;">PAX por hora</span>
                </div>
                <?php if (!empty($fluxo) && $fluxoMax > 0): ?>
                    <div style="display: flex; align-items: flex-end; gap: 4px; height: 140px;">
                        <?php foreach ($fluxo as $fluxoRow): ?>
                            <?php $altura = max(3, (int)round(((int)($fluxoRow['total_pax'] ?? 0) / $fluxoMax) * 100)); ?>
                            <div style="flex: 1; min-width: 0; height: <?= $altura ?>%; border-radius: 3px 3px 0 0; background: <?= (int)($fluxoRow['total_pax'] ?? 0) === $fluxoMax ? 'var(--fb-brand-deep)' : 'var(--fb-brand)' ?>;" title="<?= h((string)($fluxoRow['hora'] ?? '')) ?>h · <?= (int)($fluxoRow['total_pax'] ?? 0) ?> PAX"></div>
                        <?php endforeach; ?>
                    </div>
                    <div style="display: flex; gap: 4px; margin-top: 4px;">
                        <?php foreach ($fluxo as $fluxoRow): ?>
                            <span style="flex: 1; min-width: 0; text-align: center; font-size: 0.62rem; color: var(--fb-muted); overflow: hidden;"><?= h((string)($fluxoRow['hora'] ?? '')) ?>h</span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="fb-empty">
                        <i class="bi bi-graph-up"></i>
                        <p class="fb-empty__title">Sem fluxo no recorte</p>
                        <p style="margin: 0; font-size: 0.85rem;">Ajuste o filtro ou aguarde os primeiros registros do dia.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-12 col-lg-5">
            <div class="fb-card h-100">
                <div class="fb-card__head">
                    <h5 class="fb-card__title">PAX por operação</h5>
                </div>
                <ul class="fb-list">
                    <?php foreach ($stats['totais_operacao'] ?? [] as $opRow): ?>
                        <li class="fb-list__item">
                            <div class="fb-grow">
                                <div class="fb-row" style="justify-content: space-between;">
                                    <span style="font-size: 0.88rem; font-weight: 500;"><?= h(normalize_mojibake((string)($opRow['nome'] ?? ''))) ?></span>
                                    <span class="fb-muted fb-num" style="font-size: 0.85rem;"><?= number_format((int)($opRow['total_pax'] ?? 0), 0, ',', '.') ?></span>
                                </div>
                                <div class="fb-progress" style="margin-top: 5px;"><div class="fb-progress__bar" style="width: <?= $maxOperacao > 0 ? (int)round(((int)($opRow['total_pax'] ?? 0) / $maxOperacao) * 100) : 0 ?>%;"></div></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($stats['totais_operacao'])): ?>
                        <li class="fb-list__item fb-muted" style="font-size: 0.85rem;">Sem dados no recorte.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="row g-3 fb-mt">
        <div class="col-12 col-lg-5">
            <div class="fb-card h-100">
                <div class="fb-card__head">
                    <h5 class="fb-card__title">PAX por restaurante</h5>
                    <span class="fb-muted" style="font-size: 0.78rem;">toque para detalhar</span>
                </div>
                <ul class="fb-list">
                    <?php foreach ($stats['totais_restaurante'] ?? [] as $restRow): ?>
                        <?php
                        $nomeRestaurante = normalize_mojibake((string)($restRow['nome'] ?? ''));
                        $idRestaurante = $restauranteIdPorNome[$nomeRestaurante] ?? 0;
                        ?>
                        <?php $identRestRow = restaurante_identidade($nomeRestaurante); ?>
                        <li class="fb-list__item">
                            <div class="fb-grow">
                                <div class="fb-row" style="justify-content: space-between;">
                                    <span class="fb-row" style="gap: 7px;">
                                        <i class="bi <?= h($identRestRow['icone']) ?>" style="color: <?= h($identRestRow['cor']) ?>; font-size: 0.95rem;" aria-hidden="true"></i>
                                        <?php if ($idRestaurante > 0): ?>
                                            <a href="/?<?= h(http_build_query(array_merge($queryAtual, ['r' => 'dashboard/restaurant', 'id' => $idRestaurante, 'restaurante_id' => $idRestaurante]))) ?>" style="font-size: 0.88rem; font-weight: 600; text-decoration: none; color: var(--fb-ink);"><?= h($nomeRestaurante) ?></a>
                                        <?php else: ?>
                                            <span style="font-size: 0.88rem; font-weight: 600;"><?= h($nomeRestaurante) ?></span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="fb-muted fb-num" style="font-size: 0.85rem;"><?= number_format((int)($restRow['total_pax'] ?? 0), 0, ',', '.') ?></span>
                                </div>
                                <div class="fb-progress" style="margin-top: 5px;"><div class="fb-progress__bar" style="width: <?= $maxRestaurante > 0 ? (int)round(((int)($restRow['total_pax'] ?? 0) / $maxRestaurante) * 100) : 0 ?>%; background: <?= h($identRestRow['cor']) ?>;"></div></div>
                            </div>
                            <?php if ($idRestaurante > 0): ?>
                                <a href="/?<?= h(http_build_query(array_merge($queryAtual, ['r' => 'dashboard/restaurant', 'id' => $idRestaurante, 'restaurante_id' => $idRestaurante]))) ?>" aria-label="Detalhar <?= h($nomeRestaurante) ?>"><i class="bi bi-chevron-right fb-muted"></i></a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($stats['totais_restaurante'])): ?>
                        <li class="fb-list__item fb-muted" style="font-size: 0.85rem;">Sem dados no recorte.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="col-12 col-lg-7">
            <div class="fb-card h-100">
                <div class="fb-card__head">
                    <h5 class="fb-card__title">Últimos acessos</h5>
                    <span class="fb-muted" style="font-size: 0.78rem;">ao vivo</span>
                </div>
                <table class="fb-table">
                    <thead>
                        <tr><th>Status</th><th>Restaurante</th><th>UH</th><th>PAX</th><th>Operação</th><th>Horário</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentes as $item): ?>
                            <?php $statusRecente = normalize_mojibake((string)($item['status_operacional'] ?? '')); ?>
                            <tr>
                                <td data-label="Status">
                                    <?php if ($statusRecente === 'Duplicado'): ?>
                                        <span class="fb-badge fb-badge--duplicado">Duplicado</span>
                                    <?php elseif ($statusRecente === 'Fora do Horário' || $statusRecente === 'Fora do Horario'): ?>
                                        <span class="fb-badge fb-badge--fora-horario">Fora do horário</span>
                                    <?php elseif ($statusRecente === 'Múltiplo Acesso' || $statusRecente === 'Multiplo Acesso'): ?>
                                        <span class="fb-badge fb-badge--multiplo">Múltiplo</span>
                                    <?php else: ?>
                                        <span class="fb-badge fb-badge--ok">OK</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Restaurante"><?= restaurante_selo((string)($item['restaurante'] ?? '')) ?></td>
                                <td data-label="UH" class="fb-num"><?= h(uh_label((string)($item['uh_numero'] ?? ''))) ?></td>
                                <td data-label="PAX" class="fb-num"><?= (int)($item['pax'] ?? 0) ?></td>
                                <td data-label="Operação"><?= h(normalize_mojibake((string)($item['operacao'] ?? ''))) ?></td>
                                <td data-label="Horário" class="fb-num"><?= h((string)($item['criado_em'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (empty($recentes)): ?>
                    <div class="fb-empty">
                        <i class="bi bi-cup-hot"></i>
                        <p class="fb-empty__title">Salão tranquilo por enquanto</p>
                        <p style="margin: 0; font-size: 0.85rem;">Os acessos aparecem aqui assim que a operação registrar.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
