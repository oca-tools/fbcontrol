<?php
$filters = $this->data['filters'] ?? [];
$restaurante = $this->data['restaurante'] ?? null;
$operacoes = $this->data['operacoes'] ?? [];
$stats = $this->data['stats'] ?? [];
$recentes = $this->data['recentes'] ?? [];
$tematicoMode = $this->data['tematico_mode'] ?? false;
$tematicoStats = $this->data['tematico_stats'] ?? [];
$tematicoTurnos = $this->data['tematico_turnos'] ?? [];
$tematicoRecentes = $this->data['tematico_recentes'] ?? [];
$totalAcessos = (int)($stats['total_acessos'] ?? 0);
$dupPercent = $totalAcessos > 0 ? round(($stats['duplicados'] / $totalAcessos) * 100) : 0;
$foraPercent = $totalAcessos > 0 ? round(($stats['fora_horario'] / $totalAcessos) * 100) : 0;
?>
<div class="card card-soft p-4 mb-4">
    <div class="d-flex justify-content-between align-items-start">
        <div class="section-title">
            <div class="icon"><i class="bi bi-shop-window"></i></div>
            <div>
                <div class="text-uppercase text-muted small">Painel do restaurante</div>
                <h3 class="fw-bold mb-0">Dashboard do Restaurante</h3>
                <div class="text-muted">
                    <span class="tag <?= restaurant_badge_class($restaurante['nome'] ?? '') ?>"><?= h($restaurante['nome'] ?? 'Restaurante') ?></span>
                </div>
            </div>
        </div>
        <a class="btn btn-outline-primary" href="/?r=dashboard/index"><i class="bi bi-arrow-left"></i> Voltar ao geral</a>
    </div>

    <form class="row g-3 align-items-end mt-2" method="get" action="/">
        <input type="hidden" name="r" value="dashboard/restaurant">
        <input type="hidden" name="id" value="<?= (int)($filters['restaurante_id'] ?? 0) ?>">
        <div class="col-12 col-md-4">
            <label class="form-label">Data (única)</label>
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
        <?php if (!empty($this->data['show_operacao_filter'])): ?>
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
        <?php endif; ?>
        <div class="col-12 col-md-4">
            <label class="form-label">Status</label>
            <select class="form-select input-xl" name="status">
                <option value="">Todas</option>
                <option value="ok" <?= ($filters['status'] ?? '') === 'ok' ? 'selected' : '' ?>>OK</option>
                <option value="duplicado" <?= ($filters['status'] ?? '') === 'duplicado' ? 'selected' : '' ?>>Duplicado</option>
                <option value="fora_horario" <?= ($filters['status'] ?? '') === 'fora_horario' ? 'selected' : '' ?>>Fora do horário</option>
                <option value="multiplo" <?= ($filters['status'] ?? '') === 'multiplo' ? 'selected' : '' ?>>Múltiplo acesso</option>
                <option value="nao_informado" <?= ($filters['status'] ?? '') === 'nao_informado' ? 'selected' : '' ?>>Não informado</option>
                <option value="day_use" <?= ($filters['status'] ?? '') === 'day_use' ? 'selected' : '' ?>>Day use</option>
            </select>
        </div>
        <div class="col-12 d-flex flex-wrap gap-2">
            <button class="btn btn-outline-primary" type="button" data-range="1">Ontem</button>
            <button class="btn btn-outline-primary" type="button" data-range="7">Últimos 7 dias</button>
            <button class="btn btn-outline-primary" type="button" data-range="30">Últimos 30 dias</button>
            <button class="btn btn-primary btn-xl">Aplicar filtros</button>
            <a class="btn btn-primary btn-xl" href="/?r=dashboard/restaurant&id=<?= (int)($filters['restaurante_id'] ?? 0) ?>">Remover filtro</a>
        </div>
    </form>
</div>

<?php if ($tematicoMode): ?>
<div class="row g-4 mb-4">
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-calendar-check"></i></div>
                <div>
                    <div class="text-muted small">Reservas</div>
                    <div class="display-6 fw-bold"><?= (int)($tematicoStats['total_reservas'] ?? 0) ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-activity"></i>Total do período</span>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-people"></i></div>
                <div>
                    <div class="text-muted small">PAX confirmado</div>
                    <div class="display-6 fw-bold"><?= (int)($tematicoStats['pax_total'] ?? 0) ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-graph-up"></i>Sem canceladas</span>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="text-muted small">Finalizadas</div>
                    <div class="display-6 fw-bold status-success"><?= (int)($tematicoStats['finalizadas'] ?? 0) ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-check2"></i>Comparecimento</span>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-person-x"></i></div>
                <div>
                    <div class="text-muted small">No-show</div>
                    <div class="display-6 fw-bold status-danger"><?= (int)($tematicoStats['no_shows'] ?? 0) ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-exclamation-triangle"></i>Detalhe temático</span>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-lg-6">
        <div class="card p-4">
            <div class="section-title mb-3">
                <div class="icon"><i class="bi bi-clock-history"></i></div>
                <div>
                    <div class="text-uppercase text-muted small">Distribuição</div>
                    <h5 class="fw-bold mb-0">Reservas por turno</h5>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Turno</th><th>Total</th><th>Finalizadas</th><th>No-show</th></tr></thead>
                    <tbody>
                        <?php foreach ($tematicoTurnos as $row): ?>
                            <tr>
                                <td><span class="tag badge-soft"><?= h($row['turno']) ?></span></td>
                                <td><?= (int)$row['total'] ?></td>
                                <td><?= (int)$row['finalizadas'] ?></td>
                                <td><?= (int)$row['no_shows'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($tematicoTurnos)): ?>
                            <tr><td colspan="4" class="text-muted">Sem dados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card p-4">
            <div class="section-title mb-3">
                <div class="icon"><i class="bi bi-clock-history"></i></div>
                <div>
                    <div class="text-uppercase text-muted small">Monitoramento</div>
                    <h5 class="fw-bold mb-0">Últimas reservas</h5>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
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
                            <tr>
                                <td><?= h($item['data_reserva']) ?></td>
                                <td><span class="tag badge-soft"><?= h($item['turno_hora']) ?></span></td>
                                <td><span class="uh-badge <?= uh_badge_class($item['uh_numero']) ?>"><?= h(uh_label($item['uh_numero'])) ?></span></td>
                                <td><?= h($item['pax']) ?></td>
                                <td><span class="badge badge-soft"><?= h($item['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($tematicoRecentes)): ?>
                            <tr><td colspan="5" class="text-muted">Sem reservas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="row g-4 mb-4">
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-exclamation-triangle"></i></div>
                <div>
                    <div class="text-muted small">Duplicados</div>
                    <div class="h2 fw-bold status-warning mb-0"><?= (int)($stats['duplicados'] ?? 0) ?></div>
                </div>
            </div>
            <div class="progress mt-3" style="height:6px;">
                <div class="progress-bar bg-warning" style="width: <?= $dupPercent ?>%"></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-clock-history"></i></div>
                <div>
                    <div class="text-muted small">Fora do horário</div>
                    <div class="h2 fw-bold status-danger mb-0"><?= (int)($stats['fora_horario'] ?? 0) ?></div>
                </div>
            </div>
            <div class="progress mt-3" style="height:6px;">
                <div class="progress-bar bg-danger" style="width: <?= $foraPercent ?>%"></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-people"></i></div>
                <div>
                    <div class="text-muted small">PAX do restaurante</div>
                    <div class="display-6 fw-bold"><?= array_sum(array_map(fn($r) => (int)$r['total_pax'], $stats['totais_operacao'] ?? [])) ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-graph-up"></i> Hoje</span>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-clipboard-data"></i></div>
                <div>
                    <div class="text-muted small">Operações ativas</div>
                    <div class="display-6 fw-bold"><?= count($stats['totais_operacao'] ?? []) ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-lightning"></i> Hoje</span>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-question-circle"></i></div>
                <div>
                    <div class="text-muted small">Não informado</div>
                    <div class="display-6 fw-bold"><?= (int)($stats['nao_informado_acessos'] ?? 0) ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-people"></i> PAX <?= (int)($stats['nao_informado_pax'] ?? 0) ?></span>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-sun"></i></div>
                <div>
                    <div class="text-muted small">Day use</div>
                    <div class="display-6 fw-bold"><?= (int)($stats['day_use_acessos'] ?? 0) ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-people"></i> PAX <?= (int)($stats['day_use_pax'] ?? 0) ?></span>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-lg-6">
        <div class="card p-4">
            <div class="section-title mb-3">
                <div class="icon"><i class="bi bi-collection"></i></div>
                <div>
                    <div class="text-uppercase text-muted small">Distribuição</div>
                    <h5 class="fw-bold mb-0">Total de PAX por operação</h5>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm">
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
    <div class="col-12 col-lg-6">
        <div class="card p-4">
            <div class="section-title mb-3">
                <div class="icon"><i class="bi bi-graph-up"></i></div>
                <div>
                    <div class="text-uppercase text-muted small">Picos</div>
                    <h5 class="fw-bold mb-0">Fluxo por horário</h5>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm">
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
</div>

<div class="card p-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-clock-history"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Monitoramento</div>
            <h5 class="fw-bold mb-0">Últimos acessos (restaurante)</h5>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Status</th>
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
                        <td>
                            <?php if (!empty($item['alerta_duplicidade'])): ?>
                                <span class="badge badge-warning">Duplicado</span>
                            <?php elseif (!empty($item['fora_do_horario'])): ?>
                                <span class="badge badge-danger">Fora do horário</span>
                            <?php else: ?>
                                <span class="badge badge-success">OK</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="uh-badge <?= uh_badge_class($item['uh_numero']) ?>"><?= h(uh_label($item['uh_numero'])) ?></span></td>
                        <td><?= h($item['pax']) ?></td>
                        <td><span class="tag <?= operation_badge_class($item['operacao']) ?>"><?= h($item['operacao']) ?></span></td>
                        <td><?= h($item['usuario']) ?></td>
                        <td><?= h($item['criado_em']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($recentes)): ?>
                    <tr><td colspan="6" class="text-muted">Sem registros.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

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

