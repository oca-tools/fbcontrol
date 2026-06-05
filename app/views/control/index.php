<?php
$today = $this->data['today'] ?? '';
$activeShifts = $this->data['active_shifts'] ?? [];
$activeRestaurants = $this->data['active_restaurants'] ?? [];
$stats = $this->data['stats_today'] ?? [];
$recentes = $this->data['recentes'] ?? [];
$page = (int)($this->data['page'] ?? 1);
$totalPages = (int)($this->data['total_pages'] ?? 1);
$totalRegistros = (int)($this->data['total_registros'] ?? count($recentes));
$paginationPages = static function (int $current, int $total): array {
    if ($total <= 1) {
        return [];
    }
    $current = max(1, min($current, $total));
    $visible = [1, $total, $current, $current - 1, $current + 1];
    if ($current <= 4) {
        $visible = array_merge($visible, range(2, min(5, $total)));
    }
    if ($current >= $total - 3) {
        $visible = array_merge($visible, range(max(2, $total - 4), $total - 1));
    }
    $visible = array_values(array_unique(array_filter($visible, static fn($item) => $item >= 1 && $item <= $total)));
    sort($visible);

    $pages = [];
    $previous = 0;
    foreach ($visible as $item) {
        if ($previous > 0 && $item - $previous > 1) {
            $pages[] = null;
        }
        $pages[] = $item;
        $previous = $item;
    }
    return $pages;
};
?>

<div class="saas-page control-page">
    <section class="saas-hero-card">
        <div class="saas-headline d-flex flex-wrap gap-3 align-items-start justify-content-between">
            <div>
                <div class="saas-label">Centro de Controle</div>
                <h3 class="saas-title mb-1">Operação em tempo real</h3>
                <p class="saas-subtitle mb-0">Acompanhe turnos ativos e os últimos registros operacionais do dia.</p>
            </div>
            <div class="d-flex flex-column align-items-end gap-2">
                <span class="turno-pill"><i class="bi bi-activity"></i> Monitoramento ativo</span>
                <span class="stat-chip"><i class="bi bi-calendar2-day"></i>Dia <?= h($today) ?></span>
            </div>
        </div>
    </section>

    <section class="saas-kpi-grid control-kpis">
        <div class="saas-stat-card text-center">
            <div class="small text-muted"><i class="bi bi-clipboard-data me-1"></i>Registros hoje</div>
            <div class="saas-stat-value"><?= (int)($stats['total_acessos'] ?? 0) ?></div>
            <span class="stat-chip mt-2"><i class="bi bi-journal-check"></i>Fluxo do dia</span>
        </div>
        <div class="saas-stat-card text-center">
            <div class="small text-muted"><i class="bi bi-people me-1"></i>PAX hoje</div>
            <div class="saas-stat-value"><?= (int)($stats['total_pax'] ?? 0) ?></div>
            <span class="stat-chip mt-2"><i class="bi bi-graph-up"></i>Consumo total</span>
        </div>
        <div class="saas-stat-card text-center">
            <div class="small text-muted"><i class="bi bi-exclamation-triangle me-1"></i>Duplicados</div>
            <div class="saas-stat-value status-warning"><?= (int)($stats['duplicados'] ?? 0) ?></div>
            <span class="stat-chip mt-2"><i class="bi bi-exclamation-circle"></i>Revisar entradas</span>
        </div>
        <div class="saas-stat-card text-center">
            <div class="small text-muted"><i class="bi bi-clock-history me-1"></i>Fora do horário</div>
            <div class="saas-stat-value status-danger"><?= (int)($stats['fora_horario'] ?? 0) ?></div>
            <span class="stat-chip mt-2"><i class="bi bi-bell"></i>Atenção operacional</span>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-12 col-xl-5">
            <div class="saas-table-card h-100">
                <div class="saas-table-head">
                    <h5>Restaurantes ativos</h5>
                    <span class="badge badge-soft">Abertos agora</span>
                </div>
                <div class="d-flex flex-wrap gap-2 pt-1">
                    <?php foreach ($activeRestaurants as $rest): ?>
                        <a class="btn btn-outline-primary" href="/?r=dashboard/restaurant&id=<?= (int)$rest['id'] ?>">
                            <span class="tag <?= restaurant_badge_class((string)$rest['nome']) ?>"><?= h((string)$rest['nome']) ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (empty($activeRestaurants)): ?>
                        <span class="text-muted">Nenhum restaurante com turno ativo.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="saas-table-card h-100">
                <div class="saas-table-head">
                    <h5>Turnos abertos</h5>
                    <span class="badge badge-soft">Equipe em operação</span>
                </div>
                <div class="saas-table-scroll">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Restaurante</th>
                                <th>Operação</th>
                                <th>Usuário</th>
                                <th>Início</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeShifts as $shift): ?>
                                <tr>
                                    <td data-label="Restaurante"><span class="tag <?= restaurant_badge_class((string)$shift['restaurante']) ?>"><?= h((string)$shift['restaurante']) ?></span></td>
                                    <td data-label="Operação"><span class="tag <?= operation_badge_class((string)$shift['operacao']) ?>"><?= h((string)$shift['operacao']) ?></span></td>
                                    <td data-label="Usuário"><?= h((string)$shift['usuario']) ?></td>
                                    <td data-label="Início"><?= h((string)$shift['inicio_em']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($activeShifts)): ?>
                                <tr><td colspan="4" class="text-muted">Sem turnos abertos.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <section class="saas-table-card">
        <div class="saas-table-head">
            <h5>Últimos registros do dia</h5>
            <span class="text-muted small">Mostrando 20 por página (total: <?= $totalRegistros ?>)</span>
        </div>

        <div class="saas-table-scroll">
            <table class="table table-sm align-middle mb-0" data-no-auto-pagination="1">
                <thead>
                    <tr>
                        <th>Restaurante</th>
                        <th>UH</th>
                        <th>PAX</th>
                        <th>Operação</th>
                        <th>Usuário</th>
                        <th>Horário</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentes as $item): ?>
                        <tr>
                            <td data-label="Restaurante"><span class="tag <?= restaurant_badge_class((string)$item['restaurante']) ?>"><?= h((string)$item['restaurante']) ?></span></td>
                            <td data-label="UH"><span class="uh-badge <?= uh_badge_class((string)$item['uh_numero']) ?>"><?= h(uh_label((string)$item['uh_numero'])) ?></span></td>
                            <td data-label="PAX"><?= h((string)$item['pax']) ?></td>
                            <td data-label="Operação"><span class="tag <?= operation_badge_class((string)$item['operacao']) ?>"><?= h((string)$item['operacao']) ?></span></td>
                            <td data-label="Usuário"><?= h((string)$item['usuario']) ?></td>
                            <td data-label="Horário"><?= h((string)$item['criado_em']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentes)): ?>
                        <tr><td colspan="6" class="text-muted">Sem registros.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-3" aria-label="Paginação centro de controle">
                <ul class="pagination mb-0 control-pagination">
                    <?php foreach ($paginationPages($page, $totalPages) as $i): ?>
                        <?php if ($i === null): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php continue; ?>
                        <?php endif; ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="/?r=control/index&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </section>
</div>

<style>
.control-page {
    gap: 1.65rem;
    width: 100%;
    max-width: 1380px;
    margin-inline: auto;
}
.control-page .saas-hero-card,
.control-page .saas-table-card {
    padding: 1.35rem 1.4rem;
}
.control-page .saas-hero-card {
    border-radius: 26px;
}
.control-page .saas-table-card {
    border-radius: 24px;
}
.control-page .saas-table-scroll {
    max-width: 100%;
}
.control-page .saas-table-head {
    margin-bottom: 1rem;
}
.control-page .row.g-4 {
    --bs-gutter-x: 1.2rem;
    --bs-gutter-y: 1.2rem;
}
.control-page .control-kpis {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1.05rem;
}
.control-page .control-kpis .saas-stat-card {
    min-height: 168px;
    padding: 1.12rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 0.38rem;
}
.control-page .control-kpis .stat-chip {
    align-self: center;
}
.control-page .control-pagination {
    gap: 0.35rem;
    flex-wrap: wrap;
}
.control-page .control-pagination .page-link {
    border-radius: 10px;
    border: 1px solid color-mix(in srgb, var(--ab-border) 82%, transparent);
    min-width: 36px;
    text-align: center;
    font-weight: 650;
}
@media (min-width: 1200px) {
    .control-page {
        gap: 1.8rem;
    }
    .control-page .saas-hero-card,
    .control-page .saas-table-card {
        padding: 1.45rem 1.5rem;
    }
}
@media (min-width: 1440px) {
    .control-page {
        max-width: 1520px;
    }
    .control-page .control-kpis {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}
@media (max-width: 768px) {
    .control-page {
        gap: 1.2rem;
        max-width: 100%;
    }
    .control-page .saas-hero-card,
    .control-page .saas-table-card {
        padding: 1rem;
    }
    .control-page .control-kpis {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .control-page .control-kpis .saas-stat-card {
        min-height: 130px;
        padding: 0.85rem;
    }
    .control-page .saas-headline .d-flex.flex-column {
        width: 100%;
        align-items: flex-start !important;
    }
    .control-page .saas-subtitle {
        display: none;
    }
    .control-page .saas-table-head {
        align-items: flex-start;
        gap: .5rem;
    }
}
@media (max-width: 575.98px) {
    .control-page .saas-title {
        font-size: 1.35rem;
    }
    .control-page .control-kpis {
        gap: .75rem;
    }
    .control-page .control-kpis .saas-stat-card {
        min-height: 118px;
        padding: .8rem;
    }
    .control-page .control-kpis .small {
        min-height: 2.1em;
        line-height: 1.15;
    }
    .control-page .control-kpis .saas-stat-value {
        font-size: 1.45rem;
        line-height: 1.1;
    }
    .control-page .control-kpis .stat-chip {
        width: 100%;
        font-size: .72rem;
        line-height: 1.15;
        white-space: normal;
        padding-inline: .55rem;
    }
    .control-page .saas-table-scroll {
        overflow: visible;
    }
    .control-page .saas-table-scroll table,
    .control-page .saas-table-scroll tbody,
    .control-page .saas-table-scroll tr,
    .control-page .saas-table-scroll td {
        display: block;
        width: 100%;
    }
    .control-page .saas-table-scroll thead {
        display: none;
    }
    .control-page .saas-table-scroll tr {
        border: 1px solid var(--ab-border);
        border-radius: 16px;
        background: var(--ab-card);
        padding: .85rem;
        margin-bottom: .75rem;
        box-shadow: 0 10px 22px rgba(15, 23, 42, .06);
    }
    .control-page .saas-table-scroll td {
        border: 0;
        padding: .35rem 0 !important;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        text-align: right;
        overflow-wrap: anywhere;
    }
    .control-page .saas-table-scroll td::before {
        content: attr(data-label);
        color: var(--ab-muted);
        font-size: .72rem;
        font-weight: 800;
        text-transform: uppercase;
        text-align: left;
    }
    .control-page .saas-table-scroll td .tag,
    .control-page .saas-table-scroll td .uh-badge,
    .control-page .saas-table-scroll td .badge {
        max-width: 62%;
        white-space: normal;
        text-align: center;
    }
    .control-page .saas-table-scroll tr td[colspan] {
        display: block;
        text-align: left;
    }
    .control-page .saas-table-scroll tr td[colspan]::before {
        content: "";
        display: none;
    }
    .control-page .control-pagination {
        justify-content: flex-end;
    }
}
</style>
