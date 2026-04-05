<?php
$today = $this->data['today'] ?? '';
$activeShifts = $this->data['active_shifts'] ?? [];
$activeRestaurants = $this->data['active_restaurants'] ?? [];
$stats = $this->data['stats_today'] ?? [];
$recentes = $this->data['recentes'] ?? [];
$page = (int)($this->data['page'] ?? 1);
$totalPages = (int)($this->data['total_pages'] ?? 1);
$totalRegistros = (int)($this->data['total_registros'] ?? count($recentes));
?>

<div class="saas-page control-page">
    <section class="saas-hero-card">
        <div class="saas-headline d-flex flex-wrap gap-3 align-items-start justify-content-between">
            <div>
                <div class="saas-label">Centro de Controle</div>
                <h3 class="saas-title mb-1">Operacao em tempo real</h3>
                <p class="saas-subtitle mb-0">Acompanhe turnos ativos e os ultimos registros operacionais do dia.</p>
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
            <div class="small text-muted"><i class="bi bi-clock-history me-1"></i>Fora do horario</div>
            <div class="saas-stat-value status-danger"><?= (int)($stats['fora_horario'] ?? 0) ?></div>
            <span class="stat-chip mt-2"><i class="bi bi-bell"></i>Atencao operacional</span>
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
                    <span class="badge badge-soft">Equipe em operacao</span>
                </div>
                <div class="saas-table-scroll">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Restaurante</th>
                                <th>Operacao</th>
                                <th>Usuario</th>
                                <th>Inicio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeShifts as $shift): ?>
                                <tr>
                                    <td><span class="tag <?= restaurant_badge_class((string)$shift['restaurante']) ?>"><?= h((string)$shift['restaurante']) ?></span></td>
                                    <td><span class="tag <?= operation_badge_class((string)$shift['operacao']) ?>"><?= h((string)$shift['operacao']) ?></span></td>
                                    <td><?= h((string)$shift['usuario']) ?></td>
                                    <td><?= h((string)$shift['inicio_em']) ?></td>
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
            <h5>Ultimos registros do dia</h5>
            <span class="text-muted small">Mostrando 20 por pagina (total: <?= $totalRegistros ?>)</span>
        </div>

        <div class="saas-table-scroll">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Restaurante</th>
                        <th>UH</th>
                        <th>PAX</th>
                        <th>Operacao</th>
                        <th>Usuario</th>
                        <th>Horario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentes as $item): ?>
                        <tr>
                            <td><span class="tag <?= restaurant_badge_class((string)$item['restaurante']) ?>"><?= h((string)$item['restaurante']) ?></span></td>
                            <td><span class="uh-badge <?= uh_badge_class((string)$item['uh_numero']) ?>"><?= h(uh_label((string)$item['uh_numero'])) ?></span></td>
                            <td><?= h((string)$item['pax']) ?></td>
                            <td><span class="tag <?= operation_badge_class((string)$item['operacao']) ?>"><?= h((string)$item['operacao']) ?></span></td>
                            <td><?= h((string)$item['usuario']) ?></td>
                            <td><?= h((string)$item['criado_em']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentes)): ?>
                        <tr><td colspan="6" class="text-muted">Sem registros.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-3" aria-label="Paginacao centro de controle">
                <ul class="pagination mb-0 control-pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="/?r=control/index&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
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
}
</style>
