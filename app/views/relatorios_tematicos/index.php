<?php
$filters = $this->data['filters'] ?? [];
$summary = $this->data['summary'] ?? [];
$byRestaurant = $this->data['by_restaurant'] ?? [];
$byTurno = $this->data['by_turno'] ?? [];
$byDay = $this->data['by_day'] ?? [];
$list = $this->data['list'] ?? [];
$taxaComparecimento = $this->data['taxa_comparecimento'] ?? 0;
$restaurantes = $this->data['restaurantes'] ?? [];
$turnos = $this->data['turnos'] ?? [];
$listPage = (int)($this->data['list_page'] ?? 1);
$listTotalPages = (int)($this->data['list_total_pages'] ?? 1);
$listTotal = (int)($this->data['list_total'] ?? count($list));
$publicFilters = $filters;
unset($publicFilters['restaurante_ids']);

$statusOptions = [
    'Reservada' => 'Reservada',
    'Finalizada' => 'Finalizada',
    'Nao compareceu' => 'Não compareceu',
    'Cancelada' => 'Cancelada',
    'Divergencia' => 'Divergência',
];
$normalizeReportStatus = static function (?string $status): string {
    $status = normalize_mojibake(trim((string)$status));
    $map = [
        'Não compareceu' => 'Nao compareceu',
        'Nao compareceu' => 'Nao compareceu',
        'Divergência' => 'Divergencia',
        'Divergencia' => 'Divergencia',
        'Conferida' => 'Reservada',
        'Em atendimento' => 'Reservada',
    ];
    return $map[$status] ?? $status;
};
$labelReportStatus = static function (?string $status) use ($normalizeReportStatus, $statusOptions): string {
    $canon = $normalizeReportStatus($status);
    return $statusOptions[$canon] ?? $canon;
};
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
    $visible = array_values(array_unique(array_filter($visible, static fn($page) => $page >= 1 && $page <= $total)));
    sort($visible);

    $pages = [];
    $previous = 0;
    foreach ($visible as $page) {
        if ($previous > 0 && $page - $previous > 1) {
            $pages[] = null;
        }
        $pages[] = $page;
        $previous = $page;
    }
    return $pages;
};
?>

<div class="saas-page relatorios-tematicos-page">
<style>
.relatorios-tematicos-page {
    --rt-muted-bg: color-mix(in srgb, var(--ab-soft-bg) 78%, var(--ab-card) 22%);
}

.rt-hero,
.rt-filter-panel,
.rt-card {
    border: 1px solid var(--ab-border);
    border-radius: 18px;
    background: color-mix(in srgb, var(--ab-card) 94%, var(--ab-soft-bg) 6%);
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.07);
}

.rt-hero {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.1rem;
    margin-bottom: 1rem;
}

.rt-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: flex-end;
}

.rt-filter-panel {
    padding: 1rem;
    margin-bottom: 1rem;
}

.rt-range-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.rt-range-row .btn {
    min-height: 42px;
}

.rt-metric-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.85rem;
    margin-bottom: 1rem;
}

.rt-metric-card {
    border: 1px solid color-mix(in srgb, var(--ab-border) 82%, transparent);
    border-radius: 16px;
    background: var(--rt-muted-bg);
    padding: 0.95rem;
    min-width: 0;
}

.rt-metric-card .metric-icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: color-mix(in srgb, var(--ab-accent) 14%, var(--ab-card) 86%);
    color: var(--ab-accent);
}

.rt-support-strip {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    padding: 0.8rem;
    border: 1px solid var(--ab-border);
    border-radius: 16px;
    background: var(--rt-muted-bg);
    margin-bottom: 1rem;
}

.rt-table-card {
    padding: 1rem;
}

.rt-table-card .table-responsive {
    border: 1px solid color-mix(in srgb, var(--ab-border) 78%, transparent);
    border-radius: 14px;
}

.rt-table-card table {
    margin-bottom: 0;
}

.rt-table-card thead th {
    white-space: nowrap;
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--ab-muted);
    background: color-mix(in srgb, var(--ab-soft-bg) 74%, var(--ab-card) 26%);
}

.rt-pager {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    align-items: center;
    justify-content: flex-end;
    margin-top: 0.65rem;
}

.rt-pager .btn {
    min-width: 36px;
}

.rt-pager-dots {
    color: var(--ab-muted);
    padding: 0 0.2rem;
}

.rt-section-toggle {
    display: none;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
}

.rt-section-toggle .bi-chevron-down {
    transition: transform 0.18s ease;
}

.rt-section-toggle[aria-expanded='true'] .bi-chevron-down {
    transform: rotate(180deg);
}

.rt-collapsible-card.is-collapsed .rt-collapsible-body {
    display: none;
}

.rt-collapsed-hint {
    display: none;
    border: 1px dashed color-mix(in srgb, var(--ab-border) 82%, transparent);
    border-radius: 14px;
    color: var(--ab-muted);
    background: var(--rt-muted-bg);
    padding: 0.75rem;
}

.rt-collapsible-card.is-collapsed .rt-collapsed-hint {
    display: block;
}

@media (max-width: 991px) {
    .rt-hero {
        flex-direction: column;
    }

    .rt-actions {
        width: 100%;
        justify-content: stretch;
    }

    .rt-actions .btn {
        flex: 1 1 150px;
    }

    .rt-metric-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 576px) {
    .rt-hero {
        gap: 0.75rem;
        padding: 0.85rem;
        margin-bottom: 0.85rem;
    }

    .rt-hero .section-title {
        align-items: flex-start;
        gap: 0.55rem;
    }

    .rt-hero .section-title .icon {
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
    }

    .rt-hero h3 {
        font-size: 1.08rem;
        line-height: 1.18;
    }

    .rt-hero .section-title .text-muted:not(.small) {
        display: none;
    }

    .rt-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .rt-actions .btn {
        min-width: 0;
    }

    .rt-filter-panel {
        padding: 0.85rem;
    }

    .rt-filter-panel .row {
        --bs-gutter-x: 0.65rem;
        --bs-gutter-y: 0.65rem;
    }

    .rt-range-row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .rt-range-row .btn {
        min-width: 0;
        min-height: 38px;
        white-space: normal;
    }

    .rt-metric-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.55rem;
    }

    .rt-metric-card {
        padding: 0.72rem;
        border-radius: 14px;
    }

    .rt-metric-card .metric-icon {
        width: 34px;
        height: 34px;
        border-radius: 12px;
    }

    .rt-metric-card .display-6 {
        font-size: 1.45rem;
        line-height: 1.05;
    }

    .rt-metric-card .stat-chip {
        margin-top: 0.45rem !important;
        font-size: 0.7rem;
        line-height: 1.12;
    }

    .rt-table-card {
        padding: 0.85rem;
    }

    .rt-section-toggle {
        display: inline-flex;
        width: 100%;
        margin-top: 0.65rem;
    }

    .rt-table-card .table-responsive {
        border: 0;
        overflow: visible;
    }

    .rt-table-card table,
    .rt-table-card thead,
    .rt-table-card tbody,
    .rt-table-card tr,
    .rt-table-card td {
        display: block;
        width: 100%;
    }

    .rt-table-card thead {
        display: none;
    }

    .rt-table-card tbody tr {
        border: 1px solid color-mix(in srgb, var(--ab-border) 82%, transparent);
        border-radius: 14px;
        background: color-mix(in srgb, var(--ab-card) 94%, var(--ab-soft-bg) 6%);
        padding: 0.65rem;
        margin-bottom: 0.65rem;
    }

    .rt-table-card tbody td {
        display: grid;
        grid-template-columns: minmax(96px, 0.42fr) minmax(0, 1fr);
        gap: 0.65rem;
        align-items: center;
        border: 0;
        padding: 0.34rem 0;
        word-break: break-word;
    }

    .rt-table-card tbody td::before {
        content: attr(data-label);
        color: var(--ab-muted);
        font-size: 0.72rem;
        font-weight: 850;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .rt-table-card tbody td[colspan] {
        display: block;
    }

    .rt-table-card tbody td[colspan]::before {
        content: none;
    }
}
</style>

<div class="rt-hero">
    <div class="section-title mb-0">
        <div class="icon"><i class="bi bi-clipboard-data"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Relatórios Temáticos</div>
            <h3 class="fw-bold mb-0">Relatórios das Reservas Temáticas</h3>
            <div class="text-muted">Acompanhe reservas, comparecimentos e no-shows.</div>
        </div>
    </div>
    <div class="rt-actions">
        <a class="btn btn-outline-primary js-export-btn" data-toast="Exportado com sucesso. O download CSV foi iniciado." href="/?r=relatoriosTematicos/export&type=csv&data=<?= h($filters['data']) ?>&data_inicio=<?= h($filters['data_inicio']) ?>&data_fim=<?= h($filters['data_fim']) ?>&restaurante_id=<?= h($filters['restaurante_id']) ?>&turno_id=<?= h($filters['turno_id']) ?>&status=<?= h($filters['status']) ?>&grupo_nome=<?= h($filters['grupo_nome'] ?? '') ?>">
            <i class="bi bi-download me-1"></i>CSV
        </a>
        <a class="btn btn-primary js-export-btn" data-toast="Exportado com sucesso. O download Excel foi iniciado." href="/?r=relatoriosTematicos/export&type=xlsx&data=<?= h($filters['data']) ?>&data_inicio=<?= h($filters['data_inicio']) ?>&data_fim=<?= h($filters['data_fim']) ?>&restaurante_id=<?= h($filters['restaurante_id']) ?>&turno_id=<?= h($filters['turno_id']) ?>&status=<?= h($filters['status']) ?>&grupo_nome=<?= h($filters['grupo_nome'] ?? '') ?>">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel
        </a>
    </div>
</div>

<div class="rt-filter-panel">
    <form class="row g-3 align-items-end" method="get" action="/" data-ajax-filter data-ajax-target=".app-content">
        <input type="hidden" name="r" value="relatoriosTematicos/index">
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
            <label class="form-label">Restaurante</label>
            <select class="form-select input-xl" name="restaurante_id">
                <option value="">Todos</option>
                <?php foreach ($restaurantes as $rest): ?>
                    <option value="<?= (int)$rest['id'] ?>" <?= ($filters['restaurante_id'] ?? '') == $rest['id'] ? 'selected' : '' ?>>
                        <?= h($rest['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Turno</label>
            <select class="form-select input-xl" name="turno_id">
                <option value="">Todos</option>
                <?php foreach ($turnos as $turno): ?>
                    <option value="<?= (int)$turno['id'] ?>" <?= ($filters['turno_id'] ?? '') == $turno['id'] ? 'selected' : '' ?>>
                        <?= h($turno['hora']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select input-xl" name="status">
                <option value="">Todos</option>
                <?php foreach ($statusOptions as $statusValue => $statusLabel): ?>
                    <option value="<?= h($statusValue) ?>" <?= $normalizeReportStatus($filters['status'] ?? '') === $statusValue ? 'selected' : '' ?>>
                        <?= h($statusLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Grupo (nome)</label>
            <input type="text" class="form-control input-xl" name="grupo_nome" value="<?= h($filters['grupo_nome'] ?? '') ?>" placeholder="Ex: Famtour, Família, Evento...">
        </div>
        <div class="col-12 rt-range-row">
            <button class="btn btn-outline-primary" type="button" data-range="1">Ontem</button>
            <button class="btn btn-outline-primary" type="button" data-range="7">Últimos 7 dias</button>
            <button class="btn btn-outline-primary" type="button" data-range="30">Últimos 30 dias</button>
            <button class="btn btn-primary btn-xl">Aplicar filtros</button>
            <a class="btn btn-outline-primary btn-xl" href="/?r=relatoriosTematicos/index" data-ajax-link data-ajax-target=".app-content">Limpar</a>
        </div>
    </form>
</div>
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
</script>

<div class="rt-metric-grid">
        <div class="rt-metric-card">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-calendar-check"></i></div>
                <div>
                    <div class="text-muted small">Reservas</div>
                    <div class="display-6 fw-bold"><?= (int)($summary['total_reservas'] ?? 0) ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-activity"></i>Total geral</span>
        </div>
        <div class="rt-metric-card">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="text-muted small">PAX adulto (reservada)</div>
                    <div class="display-6 fw-bold"><?= (int)($summary['pax_adulto_reservadas'] ?? 0) ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-graph-up"></i>Total geral <?= (int)($summary['pax_reservadas'] ?? 0) ?></span>
        </div>
        <div class="rt-metric-card">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-people"></i></div>
                <div>
                    <div class="text-muted small">PAX comparecidas</div>
                    <div class="display-6 fw-bold status-success"><?= (int)($summary['pax_comparecidas'] ?? 0) ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-check2-circle"></i>Taxa <?= h($taxaComparecimento) ?>%</span>
        </div>
        <div class="rt-metric-card">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-person-x"></i></div>
                <div>
                    <div class="text-muted small">PAX CHD (reservada)</div>
                    <div class="display-6 fw-bold"><?= (int)($summary['pax_chd_reservadas'] ?? 0) ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-people"></i>Qtd CHD <?= (int)($summary['qtd_chd_reservadas'] ?? 0) ?></span>
        </div>
</div>

<div class="rt-support-strip">
        <span class="stat-chip"><i class="bi bi-diagram-3"></i>Reservas em grupo: <?= (int)($summary['total_lotes'] ?? $summary['total_grupos'] ?? 0) ?></span>
        <span class="stat-chip"><i class="bi bi-people"></i>Grupos nomeados: <?= (int)($summary['total_grupos_nomeados'] ?? 0) ?></span>
        <span class="stat-chip"><i class="bi bi-list-check"></i>Itens: <?= (int)($summary['total_reservas'] ?? 0) ?></span>
        <span class="stat-chip"><i class="bi bi-person-x"></i>PAX não comparecidas: <?= (int)($summary['pax_nao_comparecidas'] ?? 0) ?></span>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-lg-6">
        <div class="rt-card rt-table-card">
            <div class="section-title mb-3">
                <div class="icon"><i class="bi bi-shop-window"></i></div>
                <div>
                    <div class="text-uppercase text-muted small">Por restaurante</div>
                    <h5 class="fw-bold mb-0">Distribuição de reservas</h5>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Restaurante</th>
                            <th>Total</th>
                            <th>Grupos</th>
                            <th>Grupos nomeados</th>
                            <th>Finalizadas</th>
                            <th>No-show</th>
                            <th>Canceladas</th>
                            <th>PAX adulto</th>
                            <th>PAX CHD</th>
                            <th>PAX reservadas</th>
                            <th>PAX comparecidas</th>
                            <th>PAX faltantes</th>
                        </tr>
                    </thead>
                    <tbody id="rtByRestaurantBody" class="js-rt-paginated-body" data-page-size="8">
                        <?php foreach ($byRestaurant as $row): ?>
                            <tr>
                                <td data-label="Restaurante"><span class="tag <?= restaurant_badge_class($row['restaurante']) ?>"><?= h($row['restaurante']) ?></span></td>
                                <td data-label="Total"><?= (int)$row['total'] ?></td>
                                <td data-label="Grupos"><?= (int)($row['total_lotes'] ?? $row['total_grupos'] ?? 0) ?></td>
                                <td data-label="Grupos nomeados"><?= (int)($row['total_grupos_nomeados'] ?? 0) ?></td>
                                <td data-label="Finalizadas"><?= (int)$row['finalizadas'] ?></td>
                                <td data-label="No-show"><?= (int)$row['no_shows'] ?></td>
                                <td data-label="Canceladas"><?= (int)$row['canceladas'] ?></td>
                                <td data-label="PAX adulto"><?= (int)($row['pax_adulto_reservadas'] ?? 0) ?></td>
                                <td data-label="PAX CHD"><?= (int)($row['pax_chd_reservadas'] ?? 0) ?></td>
                                <td data-label="PAX reservadas"><?= (int)$row['pax_reservadas'] ?></td>
                                <td data-label="PAX comparecidas"><?= (int)$row['pax_comparecidas'] ?></td>
                                <td data-label="PAX faltantes"><?= max(0, (int)$row['pax_reservadas'] - (int)$row['pax_comparecidas']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($byRestaurant)): ?>
                            <tr><td colspan="12" class="text-muted">Sem dados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div id="rtByRestaurantPagination" class="rt-pager"></div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="rt-card rt-table-card">
            <div class="section-title mb-3">
                <div class="icon"><i class="bi bi-clock-history"></i></div>
                <div>
                    <div class="text-uppercase text-muted small">Por turno</div>
                    <h5 class="fw-bold mb-0">Fluxo por horário</h5>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Restaurante</th>
                            <th>Turno</th>
                            <th>Total</th>
                            <th>Grupos</th>
                            <th>Grupos nomeados</th>
                            <th>Finalizadas</th>
                            <th>No-show</th>
                            <th>Canceladas</th>
                            <th>PAX adulto</th>
                            <th>PAX CHD</th>
                        </tr>
                    </thead>
                    <tbody id="rtByTurnoBody" class="js-rt-paginated-body" data-page-size="8">
                        <?php foreach ($byTurno as $row): ?>
                            <tr>
                                <td data-label="Restaurante"><span class="tag <?= restaurant_badge_class($row['restaurante'] ?? '') ?>"><?= h($row['restaurante'] ?? '') ?></span></td>
                                <td data-label="Turno"><span class="tag badge-soft"><?= h($row['turno']) ?></span></td>
                                <td data-label="Total"><?= (int)$row['total'] ?></td>
                                <td data-label="Grupos"><?= (int)($row['total_lotes'] ?? $row['total_grupos'] ?? 0) ?></td>
                                <td data-label="Grupos nomeados"><?= (int)($row['total_grupos_nomeados'] ?? 0) ?></td>
                                <td data-label="Finalizadas"><?= (int)$row['finalizadas'] ?></td>
                                <td data-label="No-show"><?= (int)$row['no_shows'] ?></td>
                                <td data-label="Canceladas"><?= (int)$row['canceladas'] ?></td>
                                <td data-label="PAX adulto"><?= (int)($row['pax_adulto_reservadas'] ?? 0) ?></td>
                                <td data-label="PAX CHD"><?= (int)($row['pax_chd_reservadas'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($byTurno)): ?>
                            <tr><td colspan="10" class="text-muted">Sem dados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div id="rtByTurnoPagination" class="rt-pager"></div>
        </div>
    </div>
</div>

<div class="rt-card rt-table-card mb-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-calendar3"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Por data</div>
            <h5 class="fw-bold mb-0">Resumo diário</h5>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Total</th>
                    <th>Grupos</th>
                    <th>Grupos nomeados</th>
                    <th>Finalizadas</th>
                    <th>No-show</th>
                    <th>Canceladas</th>
                    <th>PAX adulto</th>
                    <th>PAX CHD</th>
                    <th>PAX reservadas</th>
                    <th>PAX comparecidas</th>
                    <th>PAX faltantes</th>
                </tr>
            </thead>
            <tbody id="rtByDayBody" class="js-rt-paginated-body" data-page-size="10">
                <?php foreach ($byDay as $row): ?>
                    <tr>
                        <td data-label="Data"><?= h($row['data']) ?></td>
                        <td data-label="Total"><?= (int)$row['total'] ?></td>
                        <td data-label="Grupos"><?= (int)($row['total_lotes'] ?? $row['total_grupos'] ?? 0) ?></td>
                        <td data-label="Grupos nomeados"><?= (int)($row['total_grupos_nomeados'] ?? 0) ?></td>
                        <td data-label="Finalizadas"><?= (int)$row['finalizadas'] ?></td>
                        <td data-label="No-show"><?= (int)$row['no_shows'] ?></td>
                        <td data-label="Canceladas"><?= (int)$row['canceladas'] ?></td>
                        <td data-label="PAX adulto"><?= (int)($row['pax_adulto_reservadas'] ?? 0) ?></td>
                        <td data-label="PAX CHD"><?= (int)($row['pax_chd_reservadas'] ?? 0) ?></td>
                        <td data-label="PAX reservadas"><?= (int)$row['pax_reservadas'] ?></td>
                        <td data-label="PAX comparecidas"><?= (int)$row['pax_comparecidas'] ?></td>
                        <td data-label="PAX faltantes"><?= max(0, (int)$row['pax_reservadas'] - (int)$row['pax_comparecidas']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($byDay)): ?>
                    <tr><td colspan="12" class="text-muted">Sem dados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div id="rtByDayPagination" class="rt-pager"></div>
</div>

<div class="rt-card rt-table-card rt-collapsible-card" id="rtDetailCard" data-mobile-collapsed="1">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div class="section-title mb-0">
            <div class="icon"><i class="bi bi-list-check"></i></div>
            <div>
                <div class="text-uppercase text-muted small">Base detalhada</div>
                <h5 class="fw-bold mb-0">Reservas temáticas</h5>
            </div>
        </div>
        <button type="button" class="btn btn-outline-primary rt-section-toggle" data-toggle-rt-section="#rtDetailCard" aria-expanded="true">
            <i class="bi bi-chevron-down"></i><span data-rt-toggle-label>Ocultar base</span>
        </button>
    </div>
    <div class="rt-collapsed-hint">Base detalhada recolhida para manter os relatórios rápidos no celular.</div>
    <div class="rt-collapsible-body">
        <div class="row g-2 mb-3">
            <div class="col-12 col-md-6">
                <label class="form-label mb-1">Filtro da tabela</label>
                <input type="text" class="form-control" id="rtDetailFilter" placeholder="Filtrar nesta página">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label mb-1">Status</label>
                <select class="form-select" id="rtDetailStatus">
                    <option value="">Todos</option>
                    <?php foreach ($statusOptions as $statusValue => $statusLabel): ?>
                        <option value="<?= h($statusValue) ?>"><?= h($statusLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label mb-1">Restaurante</label>
                <select class="form-select" id="rtDetailRestaurant">
                    <option value="">Todos</option>
                    <?php foreach ($restaurantes as $rest): ?>
                        <option value="<?= h(mb_strtolower((string)$rest['nome'], 'UTF-8')) ?>"><?= h($rest['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Restaurante</th>
                        <th>Turno</th>
                        <th>Grupo</th>
                        <th>UH</th>
                        <th>Titular</th>
                        <th>PAX adulto</th>
                        <th>PAX CHD</th>
                        <th>PAX reservada</th>
                        <th>PAX real</th>
                        <th>Status</th>
                        <th>Usuário</th>
                        <th>Observação</th>
                    </tr>
                </thead>
                <tbody id="rtDetailBody">
                    <?php foreach ($list as $row): ?>
                        <?php
                            $grupoDisplay = normalize_mojibake((string)($row['grupo_nome_display'] ?? $row['grupo_nome'] ?? $row['grupo_responsavel'] ?? ''));
                            if (trim($grupoDisplay) === '' || $grupoDisplay === '-') {
                                $grupoDisplay = '-';
                            }
                            $titularDisplay = normalize_mojibake((string)($row['titular_nome_display'] ?? $row['titular_nome'] ?? '-'));
                            $rowStatusValue = $normalizeReportStatus((string)($row['status'] ?? ''));
                            $rowStatusLabel = $labelReportStatus($rowStatusValue);
                            $searchStr = mb_strtolower(trim(implode(' ', [
                                (string)($row['data_reserva'] ?? ''),
                                normalize_mojibake((string)($row['restaurante'] ?? '')),
                                (string)($row['turno_hora'] ?? ''),
                                (string)$grupoDisplay,
                                (string)($row['uh_numero'] ?? ''),
                                (string)$titularDisplay,
                                (string)$rowStatusLabel,
                                normalize_mojibake((string)($row['usuario'] ?? '')),
                                normalize_mojibake((string)($row['observacao_reserva'] ?? '')),
                            ])), 'UTF-8');
                        ?>
                        <tr class="js-rt-detail-row"
                            data-search="<?= h($searchStr) ?>"
                            data-status="<?= h($rowStatusValue) ?>"
                            data-rest="<?= h(mb_strtolower(normalize_mojibake((string)($row['restaurante'] ?? '')), 'UTF-8')) ?>">
                            <td data-label="Data"><?= h($row['data_reserva']) ?></td>
                            <td data-label="Restaurante"><span class="tag <?= restaurant_badge_class($row['restaurante']) ?>"><?= h($row['restaurante']) ?></span></td>
                            <td data-label="Turno"><span class="tag badge-soft"><?= h($row['turno_hora']) ?></span></td>
                            <td data-label="Grupo">
                                <?php if ($grupoDisplay !== '-'): ?>
                                    <div><?= h($grupoDisplay) ?></div>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                                <?php if (!empty($row['grupo_id'])): ?>
                                    <div class="text-muted small">Grupo #<?= (int)$row['grupo_id'] ?></div>
                                <?php endif; ?>
                            </td>
                            <td data-label="UH"><span class="uh-badge <?= uh_badge_class($row['uh_numero']) ?>"><?= h($row['uh_numero']) ?></span></td>
                            <td data-label="Titular"><?= h($titularDisplay) ?></td>
                            <td data-label="PAX adulto"><?= h((string)($row['pax_adulto_calc'] ?? '-')) ?></td>
                            <td data-label="PAX CHD"><?= h((string)($row['pax_chd_calc'] ?? '-')) ?></td>
                            <td data-label="PAX reservada"><?= h($row['pax']) ?></td>
                            <td data-label="PAX real"><?= h($row['pax_real'] ?? '-') ?></td>
                            <td data-label="Status"><span class="badge badge-soft"><?= h($rowStatusLabel) ?></span></td>
                            <td data-label="Usuário"><?= h($row['usuario'] ?? '-') ?></td>
                            <td data-label="Observação"><?= h($row['observacao_reserva'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($list)): ?>
                        <tr><td colspan="13" class="text-muted">Sem reservas para o filtro atual.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
            <div class="text-muted small">
                Exibindo <?= count($list) ?> de <?= $listTotal ?> reservas
            </div>
            <?php if ($listTotalPages > 1): ?>
                <ul class="pagination pagination-sm mb-0">
                    <?php foreach ($paginationPages($listPage, $listTotalPages) as $i): ?>
                        <?php if ($i === null): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php continue; ?>
                        <?php endif; ?>
                        <?php $pageQuery = http_build_query(array_merge($publicFilters, ['r' => 'relatoriosTematicos/index', 'page' => $i])); ?>
                        <li class="page-item <?= $i === $listPage ? 'active' : '' ?>">
                            <a class="page-link" href="/?<?= h($pageQuery) ?>#rtDetailCard" data-ajax-filter data-ajax-target=".app-content"><?= $i ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>


<script>
(() => {
    const normalize = (value) => (value || '')
        .toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
    const isMobile = () => window.matchMedia('(max-width: 576px)').matches;
    const setRtSectionCollapsed = (section, collapsed) => {
        section.classList.toggle('is-collapsed', collapsed);
        const toggle = document.querySelector(`[data-toggle-rt-section="#${section.id}"]`);
        if (!toggle) return;
        toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        const label = toggle.querySelector('[data-rt-toggle-label]');
        if (label) label.textContent = collapsed ? 'Mostrar base' : 'Ocultar base';
    };
    document.querySelectorAll('[data-toggle-rt-section]').forEach((toggle) => {
        const target = toggle.getAttribute('data-toggle-rt-section');
        const section = target ? document.querySelector(target) : null;
        if (!section) return;
        setRtSectionCollapsed(section, section.dataset.mobileCollapsed === '1' && isMobile());
        toggle.addEventListener('click', () => {
            setRtSectionCollapsed(section, !section.classList.contains('is-collapsed'));
        });
    });

    const renderPager = (container, totalPages, currentPage, onSelect) => {
        if (!container) return;
        container.innerHTML = '';
        if (totalPages <= 1) return;

        const appendPageBtn = (page) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `btn btn-sm ${page === currentPage ? 'btn-primary' : 'btn-outline-primary'}`;
            btn.textContent = String(page);
            if (page === currentPage) {
                btn.setAttribute('aria-current', 'page');
            }
            btn.addEventListener('click', () => onSelect(page));
            container.appendChild(btn);
        };

        const appendDots = () => {
            const dots = document.createElement('span');
            dots.className = 'rt-pager-dots';
            dots.textContent = '...';
            container.appendChild(dots);
        };

        if (totalPages <= 9) {
            for (let i = 1; i <= totalPages; i++) {
                appendPageBtn(i);
            }
            return;
        }

        const visiblePages = new Set([1, totalPages, currentPage, currentPage - 1, currentPage + 1]);
        if (currentPage <= 4) {
            for (let i = 2; i <= 5 && i < totalPages; i++) visiblePages.add(i);
        }
        if (currentPage >= totalPages - 3) {
            for (let i = totalPages - 4; i < totalPages; i++) {
                if (i > 1) visiblePages.add(i);
            }
        }

        const orderedPages = Array.from(visiblePages)
            .filter((n) => n >= 1 && n <= totalPages)
            .sort((a, b) => a - b);

        let prev = 0;
        for (const page of orderedPages) {
            if (prev > 0 && page - prev > 1) {
                if (page - prev === 2) {
                    appendPageBtn(prev + 1);
                } else {
                    appendDots();
                }
            }
            appendPageBtn(page);
            prev = page;
        }
    };

    const paginateRows = (rows, page, pageSize) => {
        const totalPages = Math.max(1, Math.ceil(rows.length / pageSize));
        const current = Math.min(Math.max(1, page), totalPages);
        const start = (current - 1) * pageSize;
        const end = start + pageSize;
        rows.forEach((row, idx) => {
            row.style.display = (idx >= start && idx < end) ? '' : 'none';
        });
        return { totalPages, current };
    };

    const simpleTables = [
        ['rtByRestaurantBody', 'rtByRestaurantPagination'],
        ['rtByTurnoBody', 'rtByTurnoPagination'],
        ['rtByDayBody', 'rtByDayPagination'],
    ];
    simpleTables.forEach(([bodyId, pagerId]) => {
        const body = document.getElementById(bodyId);
        const pager = document.getElementById(pagerId);
        if (!body) return;
        const allRows = Array.from(body.querySelectorAll('tr')).filter((tr) => !tr.querySelector('td[colspan]'));
        if (allRows.length === 0) return;
        let page = 1;
        const pageSize = parseInt(body.getAttribute('data-page-size') || '10', 10) || 10;
        const paint = () => {
            const result = paginateRows(allRows, page, pageSize);
            page = result.current;
            renderPager(pager, result.totalPages, page, (next) => {
                page = next;
                paint();
            });
        };
        paint();
    });

    const detailBody = document.getElementById('rtDetailBody');
    if (detailBody) {
        const detailRows = Array.from(detailBody.querySelectorAll('tr.js-rt-detail-row'));
        const input = document.getElementById('rtDetailFilter');
        const status = document.getElementById('rtDetailStatus');
        const rest = document.getElementById('rtDetailRestaurant');

        const apply = () => {
            const term = normalize(input?.value || '');
            const st = (status?.value || '').trim();
            const rs = normalize(rest?.value || '');
            detailRows.forEach((row) => {
                const okTerm = !term || normalize(row.dataset.search || '').includes(term);
                const okStatus = !st || (row.dataset.status || '') === st;
                const okRest = !rs || normalize(row.dataset.rest || '') === rs;
                row.style.display = okTerm && okStatus && okRest ? '' : 'none';
            });
        };

        input?.addEventListener('input', apply);
        status?.addEventListener('change', apply);
        rest?.addEventListener('change', apply);
        apply();
    }
})();
</script>

</div>
