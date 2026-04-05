<?php
$filters = $this->data['filters'] ?? [];
$restaurante = $this->data['restaurante'] ?? null;
$operacoes = $this->data['operacoes'] ?? [];
$stats = $this->data['stats'] ?? [];
$recentes = $this->data['recentes'] ?? [];
$tematicoMode = (bool)($this->data['tematico_mode'] ?? false);
$tematicoStats = $this->data['tematico_stats'] ?? [];
$tematicoTurnos = $this->data['tematico_turnos'] ?? [];
$tematicoRecentes = $this->data['tematico_recentes'] ?? [];
$showOperacaoFilter = !empty($this->data['show_operacao_filter']);

$totalAcessos = (int)($stats['total_acessos'] ?? 0);
$duplicados = (int)($stats['duplicados'] ?? 0);
$foraHorario = (int)($stats['fora_horario'] ?? 0);
$multiplos = (int)($stats['multiplos'] ?? 0);
$dupPercent = $totalAcessos > 0 ? round(($duplicados / $totalAcessos) * 100) : 0;
$foraPercent = $totalAcessos > 0 ? round(($foraHorario / $totalAcessos) * 100) : 0;
$totalPax = array_sum(array_map(static fn($r) => (int)($r['total_pax'] ?? 0), $stats['totais_operacao'] ?? []));
$alertasAtivos = $duplicados + $foraHorario + $multiplos;

$paxReservadas = (int)($tematicoStats['pax_reservadas'] ?? 0);
$paxComparecidas = (int)($tematicoStats['pax_comparecidas'] ?? 0);
$paxNaoComparecidas = (int)($tematicoStats['pax_nao_comparecidas'] ?? max(0, $paxReservadas - $paxComparecidas));
$taxaComparecimento = $paxReservadas > 0 ? round(($paxComparecidas / $paxReservadas) * 100) : 0;
?>

<div class="saas-page dashboard-restaurant-page">
    <section class="saas-hero-card">
        <div class="saas-headline mb-3 d-flex flex-wrap gap-3 align-items-start justify-content-between">
            <div>
                <div class="saas-label">Painel Operacional</div>
                <h3 class="saas-title mb-1">Dashboard do Restaurante</h3>
                <p class="saas-subtitle mb-2">Visao tatico-estrategica com foco no restaurante selecionado.</p>
                <span class="tag <?= restaurant_badge_class($restaurante['nome'] ?? '') ?>"><?= h($restaurante['nome'] ?? 'Restaurante') ?></span>
            </div>
            <a class="btn btn-outline-primary" href="/?r=dashboard/index">
                <i class="bi bi-arrow-left me-1"></i>Voltar ao geral
            </a>
        </div>

        <form class="row g-3 saas-filter-grid" method="get" action="/">
            <input type="hidden" name="r" value="dashboard/restaurant">
            <input type="hidden" name="id" value="<?= (int)($filters['restaurante_id'] ?? 0) ?>">

            <div class="col-12 col-md-4">
                <label class="form-label">Data unica</label>
                <input type="date" class="form-control input-xl" name="data" value="<?= h($filters['data'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Data inicio</label>
                <input type="date" class="form-control input-xl" name="data_inicio" value="<?= h($filters['data_inicio'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Data fim</label>
                <input type="date" class="form-control input-xl" name="data_fim" value="<?= h($filters['data_fim'] ?? '') ?>">
            </div>

            <?php if ($showOperacaoFilter): ?>
                <div class="col-12 col-md-4">
                    <label class="form-label">Operacao</label>
                    <select class="form-select input-xl" name="operacao_id">
                        <option value="">Todas</option>
                        <?php foreach ($operacoes as $item): ?>
                            <option value="<?= (int)$item['id'] ?>" <?= ($filters['operacao_id'] ?? '') == $item['id'] ? 'selected' : '' ?>>
                                <?= h(normalize_mojibake((string)$item['nome'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="col-12 col-md-4">
                <label class="form-label">Status</label>
                <select class="form-select input-xl" name="status">
                    <option value="">Todos</option>
                    <option value="ok" <?= ($filters['status'] ?? '') === 'ok' ? 'selected' : '' ?>>OK</option>
                    <option value="duplicado" <?= ($filters['status'] ?? '') === 'duplicado' ? 'selected' : '' ?>>Duplicado</option>
                    <option value="fora_horario" <?= ($filters['status'] ?? '') === 'fora_horario' ? 'selected' : '' ?>>Fora do horario</option>
                    <option value="multiplo" <?= ($filters['status'] ?? '') === 'multiplo' ? 'selected' : '' ?>>Multiplo acesso</option>
                    <option value="nao_informado" <?= ($filters['status'] ?? '') === 'nao_informado' ? 'selected' : '' ?>>Nao informado</option>
                    <option value="day_use" <?= ($filters['status'] ?? '') === 'day_use' ? 'selected' : '' ?>>Day use</option>
                </select>
            </div>

            <div class="col-12 saas-toolbar">
                <button class="btn btn-outline-primary" type="button" data-range="1">Ontem</button>
                <button class="btn btn-outline-primary" type="button" data-range="7">Ultimos 7 dias</button>
                <button class="btn btn-outline-primary" type="button" data-range="30">Ultimos 30 dias</button>
                <button class="btn btn-primary btn-xl">Aplicar filtros</button>
                <a class="btn btn-primary btn-xl" href="/?r=dashboard/restaurant&id=<?= (int)($filters['restaurante_id'] ?? 0) ?>">Remover filtro</a>
            </div>
        </form>
    </section>

    <?php if ($tematicoMode): ?>
        <section class="saas-kpi-grid">
            <div class="saas-stat-card">
                <div class="small text-muted">Reservas</div>
                <div class="saas-stat-value"><?= (int)($tematicoStats['total_reservas'] ?? 0) ?></div>
                <span class="stat-chip mt-2"><i class="bi bi-calendar2-check"></i>Total no periodo</span>
            </div>
            <div class="saas-stat-card">
                <div class="small text-muted">PAX reservadas</div>
                <div class="saas-stat-value"><?= $paxReservadas ?></div>
                <span class="stat-chip mt-2"><i class="bi bi-people"></i>Base de demanda</span>
            </div>
            <div class="saas-stat-card">
                <div class="small text-muted">PAX comparecidas</div>
                <div class="saas-stat-value status-success"><?= $paxComparecidas ?></div>
                <span class="stat-chip mt-2"><i class="bi bi-check2-circle"></i>Real confirmado</span>
            </div>
            <div class="saas-stat-card">
                <div class="small text-muted">PAX faltantes</div>
                <div class="saas-stat-value status-danger"><?= $paxNaoComparecidas ?></div>
                <span class="stat-chip mt-2"><i class="bi bi-person-x"></i>No-show parcial</span>
            </div>
            <div class="saas-stat-card">
                <div class="small text-muted">Finalizadas</div>
                <div class="saas-stat-value status-success"><?= (int)($tematicoStats['finalizadas'] ?? 0) ?></div>
                <span class="stat-chip mt-2"><i class="bi bi-patch-check"></i>Reserva concluida</span>
            </div>
            <div class="saas-stat-card">
                <div class="small text-muted">No-show</div>
                <div class="saas-stat-value status-danger"><?= (int)($tematicoStats['no_shows'] ?? 0) ?></div>
                <span class="stat-chip mt-2"><i class="bi bi-exclamation-triangle"></i>Ausencias</span>
            </div>
            <div class="saas-stat-card">
                <div class="small text-muted">Canceladas</div>
                <div class="saas-stat-value"><?= (int)($tematicoStats['canceladas'] ?? 0) ?></div>
                <span class="stat-chip mt-2"><i class="bi bi-x-circle"></i>Slot liberado</span>
            </div>
            <div class="saas-stat-card">
                <div class="small text-muted">Taxa de comparecimento</div>
                <div class="saas-stat-value"><?= $taxaComparecimento ?>%</div>
                <span class="stat-chip mt-2"><i class="bi bi-speedometer2"></i>Efetividade</span>
            </div>
        </section>

        <div class="row g-3">
            <div class="col-12 col-xl-6">
                <div class="saas-table-card h-100">
                    <div class="saas-table-head">
                        <h5>Distribuicao por turno</h5>
                        <span class="badge badge-soft">Capacidade</span>
                    </div>
                    <div class="saas-table-scroll">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Turno</th>
                                    <th>Reservas</th>
                                    <th>Finalizadas</th>
                                    <th>No-show</th>
                                    <th>PAX reservadas</th>
                                    <th>PAX comparecidas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tematicoTurnos as $row): ?>
                                    <tr>
                                        <td><span class="tag badge-soft"><?= h((string)($row['turno'] ?? '--:--')) ?></span></td>
                                        <td><?= (int)($row['total'] ?? 0) ?></td>
                                        <td><?= (int)($row['finalizadas'] ?? 0) ?></td>
                                        <td><?= (int)($row['no_shows'] ?? 0) ?></td>
                                        <td><?= (int)($row['pax_reservadas'] ?? 0) ?></td>
                                        <td><?= (int)($row['pax_comparecidas'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($tematicoTurnos)): ?>
                                    <tr><td colspan="6" class="text-muted">Sem dados para o periodo.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="saas-table-card h-100">
                    <div class="saas-table-head">
                        <h5>Ultimas reservas</h5>
                        <span class="badge badge-soft">Monitoramento</span>
                    </div>
                    <div class="saas-table-scroll">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Turno</th>
                                    <th>UH</th>
                                    <th>PAX</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tematicoRecentes as $item): ?>
                                    <?php $statusTxt = normalize_mojibake((string)($item['status'] ?? '')); ?>
                                    <tr>
                                        <td><?= h((string)($item['data_reserva'] ?? '-')) ?></td>
                                        <td><span class="tag badge-soft"><?= h((string)($item['turno_hora'] ?? '--:--')) ?></span></td>
                                        <td><span class="uh-badge <?= uh_badge_class((string)($item['uh_numero'] ?? '')) ?>"><?= h(uh_label((string)($item['uh_numero'] ?? '-'))) ?></span></td>
                                        <td><?= (int)($item['pax'] ?? 0) ?></td>
                                        <td>
                                            <?php if (mb_stripos($statusTxt, 'finaliz', 0, 'UTF-8') !== false): ?>
                                                <span class="badge badge-success">Finalizada</span>
                                            <?php elseif (mb_stripos($statusTxt, 'compareceu', 0, 'UTF-8') !== false): ?>
                                                <span class="badge badge-danger">Nao compareceu</span>
                                            <?php elseif (mb_stripos($statusTxt, 'cancel', 0, 'UTF-8') !== false): ?>
                                                <span class="badge badge-warning">Cancelada</span>
                                            <?php else: ?>
                                                <span class="badge badge-soft"><?= h($statusTxt !== '' ? $statusTxt : 'Reservada') ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($tematicoRecentes)): ?>
                                    <tr><td colspan="5" class="text-muted">Sem reservas no periodo.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <section class="saas-kpi-grid">
            <div class="saas-stat-card">
                <div class="small text-muted">PAX do restaurante</div>
                <div class="saas-stat-value"><?= $totalPax ?></div>
                <span class="stat-chip mt-2"><i class="bi bi-people"></i>Consumo no periodo</span>
            </div>
            <div class="saas-stat-card">
                <div class="small text-muted">Registros</div>
                <div class="saas-stat-value"><?= $totalAcessos ?></div>
                <span class="stat-chip mt-2"><i class="bi bi-journal-check"></i>Lancamentos</span>
            </div>
            <div class="saas-stat-card">
                <div class="small text-muted">Duplicados</div>
                <div class="saas-stat-value status-warning"><?= $duplicados ?></div>
                <span class="stat-chip mt-2"><i class="bi bi-exclamation-circle"></i><?= $dupPercent ?>% do total</span>
            </div>
            <div class="saas-stat-card">
                <div class="small text-muted">Fora do horario</div>
                <div class="saas-stat-value status-danger"><?= $foraHorario ?></div>
                <span class="stat-chip mt-2"><i class="bi bi-clock-history"></i><?= $foraPercent ?>% do total</span>
            </div>
            <div class="saas-stat-card">
                <div class="small text-muted">Multiplo acesso</div>
                <div class="saas-stat-value"><?= $multiplos ?></div>
                <span class="stat-chip mt-2"><i class="bi bi-arrow-repeat"></i>Repetencia de UH</span>
            </div>
            <div class="saas-stat-card">
                <div class="small text-muted">Nao informado</div>
                <div class="saas-stat-value"><?= (int)($stats['nao_informado_acessos'] ?? 0) ?></div>
                <span class="stat-chip mt-2"><i class="bi bi-question-circle"></i>PAX <?= (int)($stats['nao_informado_pax'] ?? 0) ?></span>
            </div>
            <div class="saas-stat-card">
                <div class="small text-muted">Day use</div>
                <div class="saas-stat-value"><?= (int)($stats['day_use_acessos'] ?? 0) ?></div>
                <span class="stat-chip mt-2"><i class="bi bi-sun"></i>PAX <?= (int)($stats['day_use_pax'] ?? 0) ?></span>
            </div>
            <div class="saas-stat-card">
                <div class="small text-muted">VIP Premium</div>
                <div class="saas-stat-value"><?= (int)($stats['vip_premium_acessos'] ?? 0) ?></div>
                <span class="stat-chip mt-2"><i class="bi bi-gem"></i>PAX <?= (int)($stats['vip_premium_pax'] ?? 0) ?></span>
            </div>
        </section>

        <div class="row g-3">
            <div class="col-12 col-xl-6">
                <div class="saas-table-card h-100">
                    <div class="saas-table-head">
                        <h5>Total de PAX por operacao</h5>
                        <span class="badge badge-soft">Distribuicao</span>
                    </div>
                    <div class="saas-table-scroll">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Operacao</th><th>Total</th></tr></thead>
                            <tbody>
                                <?php foreach ($stats['totais_operacao'] ?? [] as $row): ?>
                                    <tr>
                                        <td><span class="tag <?= operation_badge_class((string)($row['nome'] ?? '')) ?>"><?= h(normalize_mojibake((string)($row['nome'] ?? '-'))) ?></span></td>
                                        <td><?= (int)($row['total_pax'] ?? 0) ?></td>
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
                <div class="saas-table-card h-100">
                    <div class="saas-table-head">
                        <h5>Fluxo por horario</h5>
                        <span class="badge badge-soft">Picos</span>
                    </div>
                    <div class="saas-table-scroll">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Hora</th><th>Total</th></tr></thead>
                            <tbody>
                                <?php foreach ($stats['fluxo_horario'] ?? [] as $row): ?>
                                    <tr>
                                        <td><?= h((string)($row['hora'] ?? '--:--')) ?></td>
                                        <td><?= (int)($row['total_pax'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($stats['fluxo_horario'])): ?>
                                    <tr><td colspan="2" class="text-muted">Sem dados.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="saas-table-card mt-3">
            <div class="saas-table-head">
                <h5>Ultimos acessos do restaurante</h5>
                <span class="badge badge-soft">Alertas ativos: <?= $alertasAtivos ?></span>
            </div>
            <div class="saas-table-scroll">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>UH</th>
                            <th>PAX</th>
                            <th>Operacao</th>
                            <th>Usuario</th>
                            <th>Horario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentes as $item): ?>
                            <?php
                            $statusNorm = mb_strtolower(normalize_mojibake((string)($item['status_operacional'] ?? '')), 'UTF-8');
                            ?>
                            <tr>
                                <td>
                                    <?php if (strpos($statusNorm, 'duplic') !== false): ?>
                                        <span class="badge badge-warning">Duplicado</span>
                                    <?php elseif (strpos($statusNorm, 'fora') !== false): ?>
                                        <span class="badge badge-danger">Fora do horario</span>
                                    <?php elseif (strpos($statusNorm, 'multip') !== false || !empty($item['multiplo_acesso'])): ?>
                                        <span class="badge badge-soft">Multiplo acesso</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">OK</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="uh-badge <?= uh_badge_class((string)($item['uh_numero'] ?? '')) ?>"><?= h(uh_label((string)($item['uh_numero'] ?? '-'))) ?></span></td>
                                <td><?= (int)($item['pax'] ?? 0) ?></td>
                                <td><span class="tag <?= operation_badge_class((string)($item['operacao'] ?? '')) ?>"><?= h(normalize_mojibake((string)($item['operacao'] ?? '-'))) ?></span></td>
                                <td><?= h((string)($item['usuario'] ?? '-')) ?></td>
                                <td><?= h((string)($item['criado_em'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentes)): ?>
                            <tr><td colspan="6" class="text-muted">Sem registros para o periodo.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .dashboard-restaurant-page {
        min-width: 0;
        max-width: 100%;
        overflow-x: hidden;
    }
    .dashboard-restaurant-page .saas-toolbar .btn {
        max-width: 100%;
    }
    .dashboard-restaurant-page .saas-table-head {
        flex-wrap: wrap;
    }
    .dashboard-restaurant-page .row {
        margin-left: 0;
        margin-right: 0;
    }
    .dashboard-restaurant-page .row > [class*="col-"] {
        min-width: 0;
        max-width: 100%;
    }
    .dashboard-restaurant-page .saas-table-scroll,
    .dashboard-restaurant-page .table-responsive {
        max-width: 100%;
        overflow-x: auto;
    }
    .dashboard-restaurant-page .stat-chip {
        white-space: normal;
        text-wrap: balance;
    }
    @media (max-width: 768px) {
        .dashboard-restaurant-page .saas-toolbar .btn {
            flex: 1 1 calc(50% - 0.25rem);
        }
    }
    @media (max-width: 576px) {
        .dashboard-restaurant-page .saas-kpi-grid {
            grid-template-columns: 1fr !important;
        }
        .dashboard-restaurant-page .saas-toolbar .btn {
            flex: 1 1 100%;
        }
        .dashboard-restaurant-page .stat-chip {
            white-space: normal;
            text-align: center;
            justify-content: center;
        }
    }
</style>

<script>
(() => {
    const start = document.querySelector('input[name="data_inicio"]');
    const end = document.querySelector('input[name="data_fim"]');
    if (!start || !end) return;

    document.querySelectorAll('[data-range]').forEach((btn) => {
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
