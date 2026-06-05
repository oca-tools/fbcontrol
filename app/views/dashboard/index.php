<?php
$filters = $this->data['filters'] ?? [];
$restaurantes = $this->data['restaurantes'] ?? [];
$operacoes = $this->data['operacoes'] ?? [];
$stats = $this->data['stats'] ?? [];
?>
<div class="saas-page dashboard-legacy-page">
    <section class="saas-hero-card">
        <div class="saas-headline d-flex flex-wrap gap-3 align-items-start justify-content-between">
            <div>
                <div class="saas-label">Visão operacional</div>
                <h3 class="saas-title mb-1">Dashboard</h3>
                <p class="saas-subtitle mb-0">Consulta rápida de duplicidades, horários e totais operacionais.</p>
            </div>
            <span class="badge badge-soft"><i class="bi bi-speedometer2"></i> Resumo filtrável</span>
        </div>
    </section>

    <section class="saas-table-card">
        <div class="saas-table-head">
            <h5>Filtros</h5>
        </div>
        <form class="row g-3 align-items-end" method="get" action="/">
            <input type="hidden" name="r" value="dashboard/index">
            <div class="col-12 col-md-4">
                <label class="form-label">Data única</label>
                <input type="date" class="form-control input-xl" name="data" value="<?= h($filters['data'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Data início</label>
                <input type="date" class="form-control input-xl" name="data_inicio" value="<?= h($filters['data_inicio'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Data fim</label>
                <input type="date" class="form-control input-xl" name="data_fim" value="<?= h($filters['data_fim'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-4">
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
            <div class="col-12 col-md-4">
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
            <div class="col-12 col-md-4">
                <button class="btn btn-primary btn-xl w-100">Aplicar filtros</button>
            </div>
        </form>
    </section>

    <section class="saas-kpi-grid dashboard-legacy-kpis">
        <div class="saas-stat-card">
            <div class="small text-muted">Duplicados</div>
            <div class="saas-stat-value status-warning"><?= (int)$stats['duplicados'] ?></div>
        </div>
        <div class="saas-stat-card">
            <div class="small text-muted">Fora do horário</div>
            <div class="saas-stat-value status-danger"><?= (int)$stats['fora_horario'] ?></div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <section class="saas-table-card h-100">
                <div class="saas-table-head"><h5>Total de PAX por operação</h5></div>
                <div class="table-responsive">
                    <table class="table table-sm dashboard-legacy-table">
                        <thead><tr><th>Operação</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php foreach ($stats['totais_operacao'] ?? [] as $row): ?>
                                <tr>
                                    <td data-label="Operação"><span class="tag <?= operation_badge_class($row['nome']) ?>"><?= h($row['nome']) ?></span></td>
                                    <td data-label="Total"><?= h($row['total_pax']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($stats['totais_operacao'])): ?>
                                <tr><td colspan="2" class="text-muted">Sem dados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
        <div class="col-12 col-lg-6">
            <section class="saas-table-card h-100">
                <div class="saas-table-head"><h5>Total de PAX por restaurante</h5></div>
                <div class="table-responsive">
                    <table class="table table-sm dashboard-legacy-table">
                        <thead><tr><th>Restaurante</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php foreach ($stats['totais_restaurante'] ?? [] as $row): ?>
                                <tr>
                                    <td data-label="Restaurante"><span class="tag <?= restaurant_badge_class($row['nome']) ?>"><?= h($row['nome']) ?></span></td>
                                    <td data-label="Total"><?= h($row['total_pax']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($stats['totais_restaurante'])): ?>
                                <tr><td colspan="2" class="text-muted">Sem dados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
        <div class="col-12">
            <section class="saas-table-card">
                <div class="saas-table-head"><h5>Fluxo por horário</h5></div>
                <div class="table-responsive">
                    <table class="table table-sm dashboard-legacy-table">
                        <thead><tr><th>Hora</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php foreach ($stats['fluxo_horario'] ?? [] as $row): ?>
                                <tr>
                                    <td data-label="Hora"><?= h($row['hora']) ?></td>
                                    <td data-label="Total"><?= h($row['total_pax']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($stats['fluxo_horario'])): ?>
                                <tr><td colspan="2" class="text-muted">Sem dados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</div>

<style>
    .dashboard-legacy-page {
        min-width: 0;
        overflow-x: hidden;
    }
    .dashboard-legacy-page .row {
        margin-left: 0;
        margin-right: 0;
        --bs-gutter-x: 1rem;
    }
    .dashboard-legacy-page .row > [class*="col-"] {
        min-width: 0;
        padding-left: calc(var(--bs-gutter-x) * 0.5);
        padding-right: calc(var(--bs-gutter-x) * 0.5);
    }
    .dashboard-legacy-page .form-label {
        font-weight: 650;
    }
    .dashboard-legacy-kpis {
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }
    .dashboard-legacy-page .dashboard-legacy-table td[data-label] {
        vertical-align: middle;
    }
    @media (max-width: 768px) {
        .dashboard-legacy-page .saas-hero-card,
        .dashboard-legacy-page .saas-table-card {
            padding: 1rem;
            border-radius: 16px;
        }
        .dashboard-legacy-page .saas-headline .badge {
            width: 100%;
            justify-content: center;
        }
        .dashboard-legacy-kpis {
            grid-template-columns: 1fr;
        }
        .dashboard-legacy-page .table-responsive {
            overflow-x: visible;
        }
        .dashboard-legacy-page .dashboard-legacy-table {
            border-collapse: separate;
            border-spacing: 0 0.75rem;
        }
        .dashboard-legacy-page .dashboard-legacy-table thead {
            display: none;
        }
        .dashboard-legacy-page .dashboard-legacy-table,
        .dashboard-legacy-page .dashboard-legacy-table tbody,
        .dashboard-legacy-page .dashboard-legacy-table tr,
        .dashboard-legacy-page .dashboard-legacy-table td {
            display: block;
            width: 100%;
        }
        .dashboard-legacy-page .dashboard-legacy-table tr {
            border: 1px solid rgba(148, 163, 184, 0.24);
            border-radius: 14px;
            padding: 0.45rem 0.75rem;
            background: var(--surface, #fff);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }
        .dashboard-legacy-page .dashboard-legacy-table td {
            border: 0;
            padding: 0.55rem 0;
        }
        .dashboard-legacy-page .dashboard-legacy-table td[data-label] {
            display: grid;
            grid-template-columns: minmax(90px, 34%) minmax(0, 1fr);
            gap: 0.75rem;
            align-items: center;
        }
        .dashboard-legacy-page .dashboard-legacy-table td[data-label]::before {
            content: attr(data-label);
            color: var(--text-muted, #64748b);
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .dashboard-legacy-page .dashboard-legacy-table td[colspan] {
            text-align: center;
            padding: 1rem 0.5rem;
        }
    }
</style>
