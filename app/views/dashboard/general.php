<?php
$filters = $this->data['filters'] ?? [];
$flowFilters = $this->data['flow_filters'] ?? $filters;
$restaurantes = $this->data['restaurantes'] ?? [];
$operacoes = $this->data['operacoes'] ?? [];
$flowOperacoes = $this->data['flow_operacoes'] ?? $operacoes;
$stats = $this->data['stats'] ?? [];
$recentes = $this->data['recentes'] ?? [];

$totalAcessos = (int)($stats['total_acessos'] ?? 0);
$dupCount = (int)($stats['duplicados'] ?? 0);
$foraCount = (int)($stats['fora_horario'] ?? 0);
$multiploCount = (int)($stats['multiplos'] ?? 0);
$dupPercent = $totalAcessos > 0 ? round(($dupCount / $totalAcessos) * 100) : 0;
$foraPercent = $totalAcessos > 0 ? round(($foraCount / $totalAcessos) * 100) : 0;
$totalPaxDia = array_sum(array_map(static fn($r) => (int)($r['total_pax'] ?? 0), $stats['totais_restaurante'] ?? []));
$alertasAtivos = $dupCount + $foraCount + $multiploCount;
?>

<div class="saas-page dashboard-general-page">
    <div class="saas-grid-top">
        <section class="saas-hero-card">
            <div class="saas-headline mb-3">
                <div>
                    <div class="saas-label">Centro Analítico</div>
                    <h3 class="saas-title">Dashboard Geral</h3>
                    <p class="saas-subtitle">Visão consolidada da operação por período, status e restaurante.</p>
                </div>
                <span class="badge badge-soft">Tempo real</span>
            </div>

            <form class="row g-3 saas-filter-grid" method="get" action="/" data-ajax-filter data-ajax-target=".app-content">
                <input type="hidden" name="r" value="dashboard/index">
                <input type="hidden" name="fluxo_restaurante_id" value="<?= h($flowFilters['restaurante_id'] ?? '') ?>">
                <input type="hidden" name="fluxo_operacao_id" value="<?= h($flowFilters['operacao_id'] ?? '') ?>">

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
                <div class="col-12 col-md-4">
                    <label class="form-label">Atalho restaurante</label>
                    <select class="form-select input-xl" onchange="if(this.value){window.location='/?r=dashboard/restaurant&id='+this.value}">
                        <option value="">Selecione</option>
                        <?php foreach ($restaurantes as $item): ?>
                            <option value="<?= (int)$item['id'] ?>"><?= h($item['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 saas-toolbar">
                    <button class="btn btn-outline-primary" type="button" data-range="1">Ontem</button>
                    <button class="btn btn-outline-primary" type="button" data-range="7">Últimos 7 dias</button>
                    <button class="btn btn-outline-primary" type="button" data-range="30">Últimos 30 dias</button>
                    <button class="btn btn-primary btn-xl">Aplicar filtros</button>
                    <a class="btn btn-primary btn-xl" href="/?r=dashboard/index" data-ajax-link data-ajax-target=".app-content">Remover filtro</a>
                </div>
            </form>

            <div class="saas-divider"></div>

            <div class="saas-label mb-2">Atalhos por restaurante</div>
            <div class="saas-chip-row">
                <?php foreach ($restaurantes as $item): ?>
                    <a class="btn btn-outline-primary" href="/?r=dashboard/restaurant&id=<?= (int)$item['id'] ?>">
                        <span class="tag <?= restaurant_badge_class($item['nome']) ?>"><?= h($item['nome']) ?></span>
                    </a>
                <?php endforeach; ?>
                <?php if (empty($restaurantes)): ?>
                    <span class="text-muted">Sem restaurantes cadastrados.</span>
                <?php endif; ?>
            </div>
        </section>

        <section class="saas-hero-card">
            <div class="saas-headline mb-3">
                <div>
                    <div class="saas-label">Qualidade operacional</div>
                    <h3 class="saas-title">Alertas e consistência</h3>
                    <p class="saas-subtitle">Acompanhamento de ocorrências que exigem atuação rápida.</p>
                </div>
                <span class="stat-chip"><i class="bi bi-activity"></i> Alertas: <?= $alertasAtivos ?></span>
            </div>

            <div class="saas-mini-card mb-2 d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-muted"><span class="saas-status-dot warn"></span>Duplicados</div>
                    <div class="small text-muted">Impacto: <?= $dupPercent ?>%</div>
                </div>
                <div class="saas-stat-value status-warning"><?= $dupCount ?></div>
            </div>

            <div class="saas-mini-card mb-2 d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-muted"><span class="saas-status-dot err"></span>Fora do horário</div>
                    <div class="small text-muted">Impacto: <?= $foraPercent ?>%</div>
                </div>
                <div class="saas-stat-value status-danger"><?= $foraCount ?></div>
            </div>

            <div class="saas-mini-card d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-muted"><span class="saas-status-dot info"></span>Múltiplo acesso</div>
                    <div class="small text-muted">UH repetente no período</div>
                </div>
                <div class="saas-stat-value"><?= $multiploCount ?></div>
            </div>
        </section>
    </div>

    <section class="saas-kpi-grid">
        <div class="saas-stat-card">
            <div class="small text-muted">PAX do dia</div>
            <div class="saas-stat-value"><?= $totalPaxDia ?></div>
            <span class="stat-chip mt-2"><i class="bi bi-people"></i> Fluxo total</span>
        </div>
        <div class="saas-stat-card">
            <div class="small text-muted">Privileged</div>
            <div class="saas-stat-value"><?= (int)($stats['privileged_acessos'] ?? 0) ?></div>
            <span class="stat-chip mt-2"><i class="bi bi-person"></i> PAX <?= (int)($stats['privileged_pax'] ?? 0) ?></span>
        </div>
        <div class="saas-stat-card">
            <div class="small text-muted">Não informado</div>
            <div class="saas-stat-value"><?= (int)($stats['nao_informado_acessos'] ?? 0) ?></div>
            <span class="stat-chip mt-2"><i class="bi bi-question-circle"></i> PAX <?= (int)($stats['nao_informado_pax'] ?? 0) ?></span>
        </div>
        <div class="saas-stat-card">
            <div class="small text-muted">Day use</div>
            <div class="saas-stat-value"><?= (int)($stats['day_use_acessos'] ?? 0) ?></div>
            <span class="stat-chip mt-2"><i class="bi bi-sun"></i> PAX <?= (int)($stats['day_use_pax'] ?? 0) ?></span>
        </div>
        <div class="saas-stat-card">
            <div class="small text-muted">VIP Premium</div>
            <div class="saas-stat-value"><?= (int)($stats['vip_premium_acessos'] ?? 0) ?></div>
            <span class="stat-chip mt-2"><i class="bi bi-gem"></i> PAX <?= (int)($stats['vip_premium_pax'] ?? 0) ?></span>
        </div>
    </section>

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="saas-table-card">
                <div class="saas-table-head">
                    <h5>Total de PAX por operação</h5>
                    <span class="badge badge-soft">Distribuição</span>
                </div>
                <div class="saas-table-scroll">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Operação</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php foreach ($stats['totais_operacao'] ?? [] as $row): ?>
                                <tr>
                                    <td><span class="tag <?= operation_badge_class($row['nome']) ?>"><?= h($row['nome']) ?></span></td>
                                    <td><?= h($row['total_pax']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($stats['totais_operacao'])): ?>
                                <tr><td colspan="2" class="text-muted">Sem dados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="saas-table-card">
                <div class="saas-table-head">
                    <h5>Total de PAX por restaurante</h5>
                    <span class="badge badge-soft">Distribuição</span>
                </div>
                <div class="saas-table-scroll">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Restaurante</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php foreach ($stats['totais_restaurante'] ?? [] as $row): ?>
                                <tr>
                                    <td><span class="tag <?= restaurant_badge_class($row['nome']) ?>"><?= h($row['nome']) ?></span></td>
                                    <td><?= h($row['total_pax']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($stats['totais_restaurante'])): ?>
                                <tr><td colspan="2" class="text-muted">Sem dados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-5">
            <div class="saas-table-card h-100">
                <div class="saas-table-head">
                    <h5>Fluxo por horário</h5>
                    <span class="badge badge-soft">Picos</span>
                </div>
                <form class="row g-2 align-items-end mb-3" method="get" action="/" data-ajax-filter data-ajax-target=".app-content" data-ajax-preserve-scroll="1">
                    <input type="hidden" name="r" value="dashboard/index">
                    <input type="hidden" name="data" value="<?= h($filters['data'] ?? '') ?>">
                    <input type="hidden" name="data_inicio" value="<?= h($filters['data_inicio'] ?? '') ?>">
                    <input type="hidden" name="data_fim" value="<?= h($filters['data_fim'] ?? '') ?>">
                    <input type="hidden" name="restaurante_id" value="<?= h($filters['restaurante_id'] ?? '') ?>">
                    <input type="hidden" name="operacao_id" value="<?= h($filters['operacao_id'] ?? '') ?>">
                    <input type="hidden" name="status" value="<?= h($filters['status'] ?? '') ?>">
                    <div class="col-12 col-md-5">
                        <label class="form-label">Restaurante do fluxo</label>
                        <select class="form-select" name="fluxo_restaurante_id">
                            <option value="">Todos</option>
                            <?php foreach ($restaurantes as $item): ?>
                                <option value="<?= (int)$item['id'] ?>" <?= ($flowFilters['restaurante_id'] ?? '') == $item['id'] ? 'selected' : '' ?>>
                                    <?= h($item['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label">Operação do fluxo</label>
                        <select class="form-select" name="fluxo_operacao_id">
                            <option value="">Todas</option>
                            <?php foreach ($flowOperacoes as $item): ?>
                                <option value="<?= (int)$item['id'] ?>" <?= ($flowFilters['operacao_id'] ?? '') == $item['id'] ? 'selected' : '' ?>>
                                    <?= h($item['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-grid">
                        <button class="btn btn-outline-primary">Filtrar</button>
                    </div>
                </form>
                <div class="saas-table-scroll">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Hora</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php foreach ($stats['fluxo_horario'] ?? [] as $row): ?>
                                <tr><td><?= h($row['hora']) ?></td><td><?= h($row['total_pax']) ?></td></tr>
                            <?php endforeach; ?>
                            <?php if (empty($stats['fluxo_horario'])): ?>
                                <tr><td colspan="2" class="text-muted">Sem dados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="saas-table-card h-100">
                <div class="saas-table-head">
                    <h5>Últimos acessos (geral)</h5>
                    <span class="badge badge-soft">Monitoramento</span>
                </div>
                <div class="saas-table-scroll">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Status</th>
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
                                <?php $status = normalize_mojibake((string)($item['status_operacional'] ?? '')); ?>
                                <tr>
                                    <td>
                                        <?php if ($status === 'Duplicado'): ?>
                                            <span class="badge badge-warning">Duplicado</span>
                                        <?php elseif ($status === 'Fora do Horário' || $status === 'Fora do Horario'): ?>
                                            <span class="badge badge-danger">Fora do horário</span>
                                        <?php elseif ($status === 'Múltiplo Acesso' || $status === 'Multiplo Acesso'): ?>
                                            <span class="badge badge-soft">Múltiplo acesso</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">OK</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="tag <?= restaurant_badge_class((string)$item['restaurante']) ?>"><?= h((string)$item['restaurante']) ?></span></td>
                                    <td><span class="uh-badge <?= uh_badge_class((string)$item['uh_numero']) ?>"><?= h(uh_label((string)$item['uh_numero'])) ?></span></td>
                                    <td><?= h((string)$item['pax']) ?></td>
                                    <td><span class="tag <?= operation_badge_class((string)$item['operacao']) ?>"><?= h((string)$item['operacao']) ?></span></td>
                                    <td><?= h((string)$item['usuario']) ?></td>
                                    <td><?= h((string)$item['criado_em']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentes)): ?>
                                <tr><td colspan="7" class="text-muted">Sem registros.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .dashboard-general-page {
        position: relative;
        min-width: 0;
        max-width: 100%;
        overflow-x: hidden;
    }
    .app-content.is-ajax-loading,
    .dashboard-general-page.is-ajax-loading {
        pointer-events: none;
    }
    .app-content.is-ajax-loading::after,
    .dashboard-general-page.is-ajax-loading::after {
        content: "Atualizando...";
        position: fixed;
        right: 1.25rem;
        bottom: 1.25rem;
        z-index: 1050;
        padding: 0.72rem 0.95rem;
        border-radius: 999px;
        background: #111827;
        color: #fff;
        font-weight: 700;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.24);
    }
    .dashboard-general-page > * {
        min-width: 0;
        max-width: 100%;
    }
    .dashboard-general-page .saas-chip-row .btn,
    .dashboard-general-page .saas-chip-row .btn:visited {
        max-width: 100%;
        background: #fff !important;
        border-color: #fbbf24 !important;
        color: #9a3412 !important;
        justify-content: center;
    }
    .dashboard-general-page .saas-chip-row .tag {
        display: inline-block;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: middle;
        background: transparent !important;
        box-shadow: none !important;
        border: 0 !important;
        color: inherit !important;
        -webkit-text-fill-color: currentColor !important;
        padding: 0 !important;
    }
    .dashboard-general-page .saas-chip-row .btn *,
    .dashboard-general-page .saas-chip-row .btn:visited * {
        color: inherit !important;
        -webkit-text-fill-color: currentColor !important;
    }
    .dashboard-general-page .saas-chip-row .btn:hover,
    .dashboard-general-page .saas-chip-row .btn:focus,
    .dashboard-general-page .saas-chip-row .btn:active {
        background: #fff7ed !important;
        border-color: #fb923c !important;
        color: #7c2d12 !important;
    }
    .dashboard-general-page .saas-chip-row .btn:hover *,
    .dashboard-general-page .saas-chip-row .btn:focus *,
    .dashboard-general-page .saas-chip-row .btn:active * {
        color: inherit !important;
        -webkit-text-fill-color: currentColor !important;
    }
    .dashboard-general-page .saas-table-head {
        flex-wrap: wrap;
    }
    .dashboard-general-page .saas-table-head h5 {
        min-width: 0;
    }
    .dashboard-general-page .saas-stat-card .stat-chip {
        white-space: normal;
        text-wrap: balance;
    }
    .dashboard-general-page .saas-table-scroll,
    .dashboard-general-page .table-responsive {
        max-width: 100%;
    }
    @media (max-width: 992px) {
        .dashboard-general-page .row {
            margin-left: 0;
            margin-right: 0;
            --bs-gutter-x: 0.8rem;
        }
        .dashboard-general-page .row > [class*="col-"] {
            min-width: 0;
            max-width: 100%;
            padding-left: calc(var(--bs-gutter-x) * 0.5);
            padding-right: calc(var(--bs-gutter-x) * 0.5);
        }
        .dashboard-general-page .saas-stat-card .stat-chip {
            white-space: normal;
        }
    }
    @media (max-width: 768px) {
        .dashboard-general-page .saas-toolbar .btn {
            flex: 1 1 100%;
        }
        .dashboard-general-page .saas-chip-row .btn {
            width: 100%;
            justify-content: flex-start;
        }
        .dashboard-general-page .saas-table-scroll,
        .dashboard-general-page .table-responsive {
            max-width: 100%;
            overflow-x: auto;
        }
    }
    @media (max-width: 576px) {
        .dashboard-general-page .saas-kpi-grid {
            grid-template-columns: 1fr !important;
        }
        .dashboard-general-page .stat-chip {
            white-space: normal;
            text-align: center;
            justify-content: center;
        }
        .dashboard-general-page .saas-toolbar .btn {
            flex: 1 1 100%;
        }
    }
</style>

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

