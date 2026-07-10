<?php
/* Hub Operação — monitor do dia (contrato: ControlDashboardService::montarPainelOperacao). */
$today = (string)($this->data['today'] ?? date('Y-m-d'));
$activeShifts = $this->data['active_shifts'] ?? [];
$activeRestaurants = $this->data['active_restaurants'] ?? [];
$stats = $this->data['stats_today'] ?? [];
$recentes = $this->data['recentes'] ?? [];
$page = (int)($this->data['page'] ?? 1);
$totalPages = (int)($this->data['total_pages'] ?? 1);
$totalRegistros = (int)($this->data['total_registros'] ?? count($recentes));

$alertasHoje = (int)($stats['duplicados'] ?? 0) + (int)($stats['fora_horario'] ?? 0);
$paginaLink = static fn(int $pagina): string => '/?' . http_build_query(['r' => 'operacao/index', 'page' => $pagina]);
?>
<div class="saas-page operacao-hub-page">
    <section class="saas-hero-card">
        <div class="saas-headline d-flex flex-wrap gap-3 align-items-start justify-content-between">
            <div>
                <div class="saas-label">Monitor do dia</div>
                <h3 class="saas-title mb-1">Operação</h3>
                <p class="saas-subtitle mb-0"><?= h(format_date_br($today)) ?> · turnos vencidos são encerrados automaticamente.</p>
            </div>
            <span class="fb-badge fb-badge--ok" style="align-self: center;"><span style="display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: var(--fb-ok); margin-right: 4px;"></span>ao vivo · atualiza a cada 60s</span>
        </div>
    </section>

    <div class="fb-metric-grid fb-mt">
        <div class="fb-metric">
            <p class="fb-metric__label">PAX hoje</p>
            <p class="fb-metric__value"><?= number_format((int)($stats['total_pax'] ?? 0), 0, ',', '.') ?></p>
            <p class="fb-metric__delta">inclui temáticos finalizados</p>
        </div>
        <div class="fb-metric">
            <p class="fb-metric__label">Registros hoje</p>
            <p class="fb-metric__value"><?= number_format((int)($stats['total_acessos'] ?? 0), 0, ',', '.') ?></p>
            <p class="fb-metric__delta"><?= number_format($totalRegistros, 0, ',', '.') ?> movimentos no dia</p>
        </div>
        <div class="fb-metric">
            <p class="fb-metric__label">Turnos abertos</p>
            <p class="fb-metric__value"><?= count($activeShifts) ?></p>
            <p class="fb-metric__delta"><?= count($activeRestaurants) ?> restaurante(s) ativo(s)</p>
        </div>
        <div class="fb-metric">
            <p class="fb-metric__label">Alertas hoje</p>
            <p class="fb-metric__value" <?= $alertasHoje > 0 ? 'style="color: var(--fb-danger);"' : '' ?>><?= number_format($alertasHoje, 0, ',', '.') ?></p>
            <p class="fb-metric__delta"><?= (int)($stats['duplicados'] ?? 0) ?> dup · <?= (int)($stats['fora_horario'] ?? 0) ?> fora de horário</p>
        </div>
    </div>

    <div class="row g-3 fb-mt">
        <div class="col-12 col-lg-5">
            <div class="fb-card h-100">
                <div class="fb-card__head">
                    <h5 class="fb-card__title">Restaurantes ativos</h5>
                    <span class="fb-muted" style="font-size: 0.78rem;">toque para detalhar</span>
                </div>
                <ul class="fb-list">
                    <?php foreach ($activeRestaurants as $rest): ?>
                        <?php $identidadeRest = restaurante_identidade($rest); ?>
                        <li class="fb-list__item" style="border-left: 3px solid <?= h($identidadeRest['cor']) ?>; padding-left: 10px;">
                            <a class="fb-grow" href="/?r=dashboard/restaurant&id=<?= (int)$rest['id'] ?>" style="text-decoration: none; color: var(--fb-ink); display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                                <span class="fb-row" style="gap: 9px;">
                                    <?= restaurante_selo($rest, 'icon') ?>
                                    <span style="font-size: 0.9rem; font-weight: 600;"><?= h(normalize_mojibake((string)$rest['nome'])) ?></span>
                                </span>
                                <span class="fb-row" style="gap: 8px;">
                                    <span class="fb-badge fb-badge--ok">em operação</span>
                                    <i class="bi bi-chevron-right fb-muted"></i>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($activeRestaurants)): ?>
                        <li class="fb-list__item fb-muted" style="font-size: 0.85rem;">Nenhum restaurante em operação agora.</li>
                    <?php endif; ?>
                </ul>

                <div class="fb-card__head fb-mt">
                    <h5 class="fb-card__title">Turnos abertos</h5>
                </div>
                <ul class="fb-list">
                    <?php foreach ($activeShifts as $shift): ?>
                        <li class="fb-list__item">
                            <div class="fb-grow">
                                <div style="font-size: 0.88rem; font-weight: 500;"><?= h(normalize_mojibake((string)($shift['restaurante'] ?? ''))) ?> · <?= h(normalize_mojibake((string)($shift['operacao'] ?? ''))) ?></div>
                                <div class="fb-muted" style="font-size: 0.78rem;"><?= h((string)($shift['usuario'] ?? '')) ?> · desde <?= h((string)($shift['inicio_em'] ?? '')) ?></div>
                            </div>
                            <span class="fb-badge fb-badge--day-use">aberto</span>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($activeShifts)): ?>
                        <li class="fb-list__item fb-muted" style="font-size: 0.85rem;">Nenhum turno aberto agora.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="col-12 col-lg-7">
            <div class="fb-card h-100">
                <div class="fb-card__head">
                    <h5 class="fb-card__title">Últimos registros do dia</h5>
                    <span class="fb-muted" style="font-size: 0.78rem;">página <?= $page ?> de <?= $totalPages ?> · <?= number_format($totalRegistros, 0, ',', '.') ?> registros</span>
                </div>
                <table class="fb-table">
                    <thead>
                        <tr><th>Restaurante</th><th>UH</th><th>PAX</th><th>Operação</th><th>Usuário</th><th>Horário</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentes as $item): ?>
                            <tr>
                                <td data-label="Restaurante"><?= restaurante_selo((string)($item['restaurante'] ?? '')) ?></td>
                                <td data-label="UH" class="fb-num"><?= h(uh_label((string)($item['uh_numero'] ?? ''))) ?></td>
                                <td data-label="PAX" class="fb-num"><?= (int)($item['pax'] ?? 0) ?></td>
                                <td data-label="Operação"><?= h(normalize_mojibake((string)($item['operacao'] ?? ''))) ?></td>
                                <td data-label="Usuário"><?= h((string)($item['usuario'] ?? '')) ?></td>
                                <td data-label="Horário" class="fb-num"><?= h((string)($item['criado_em'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (empty($recentes)): ?>
                    <div class="fb-empty">
                        <i class="bi bi-cup-hot"></i>
                        <p class="fb-empty__title">Salão tranquilo por enquanto</p>
                        <p style="margin: 0; font-size: 0.85rem;">Os registros do dia aparecem aqui em tempo real.</p>
                    </div>
                <?php endif; ?>
                <?php if ($totalPages > 1): ?>
                    <div class="fb-row fb-mt" style="justify-content: center;">
                        <?php if ($page > 1): ?>
                            <a class="fb-btn" href="<?= h($paginaLink($page - 1)) ?>"><i class="bi bi-chevron-left"></i> Anterior</a>
                        <?php endif; ?>
                        <?php if ($page < $totalPages): ?>
                            <a class="fb-btn" href="<?= h($paginaLink($page + 1)) ?>">Próxima <i class="bi bi-chevron-right"></i></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    // Auto-refresh do monitor: recarrega a cada 60s, só na primeira página e com a aba visível.
    var pagina = <?= (int)$page ?>;
    if (pagina !== 1) {
        return;
    }
    setInterval(function () {
        if (document.visibilityState === 'visible') {
            window.location.reload();
        }
    }, 60000);
})();
</script>
