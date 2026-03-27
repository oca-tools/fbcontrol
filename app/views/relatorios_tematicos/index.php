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

$statuses = [
    'Reservada',
    'Conferida',
    'Em atendimento',
    'Finalizada',
    'Não compareceu',
    'Cancelada',
    'Divergência',
    'Excedente',
];
?>

<div class="card card-soft p-4 mb-4">
    <div class="section-title">
        <div class="icon"><i class="bi bi-clipboard-data"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Relatórios Temáticos</div>
            <h3 class="fw-bold mb-0">Relatórios das Reservas Temáticas</h3>
            <div class="text-muted">Acompanhe reservas, comparecimentos e no-shows.</div>
        </div>
    </div>
</div>

<div class="card p-4 mb-4">
    <div class="d-flex justify-content-end gap-2 mb-3">
        <a class="btn btn-outline-primary" href="/?r=relatoriosTematicos/export&type=csv&data=<?= h($filters['data']) ?>&data_inicio=<?= h($filters['data_inicio']) ?>&data_fim=<?= h($filters['data_fim']) ?>&restaurante_id=<?= h($filters['restaurante_id']) ?>&turno_id=<?= h($filters['turno_id']) ?>&status=<?= h($filters['status']) ?>">
            <i class="bi bi-download me-1"></i>Exportar CSV
        </a>
        <a class="btn btn-primary" href="/?r=relatoriosTematicos/export&type=xlsx&data=<?= h($filters['data']) ?>&data_inicio=<?= h($filters['data_inicio']) ?>&data_fim=<?= h($filters['data_fim']) ?>&restaurante_id=<?= h($filters['restaurante_id']) ?>&turno_id=<?= h($filters['turno_id']) ?>&status=<?= h($filters['status']) ?>">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Exportar Excel
        </a>
    </div>
    <form class="row g-3 align-items-end" method="get" action="/">
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
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= h($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>>
                        <?= h($status) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 d-flex flex-wrap gap-2">
            <button class="btn btn-outline-primary" type="button" data-range="1">Ontem</button>
            <button class="btn btn-outline-primary" type="button" data-range="7">Últimos 7 dias</button>
            <button class="btn btn-outline-primary" type="button" data-range="30">Últimos 30 dias</button>
            <button class="btn btn-primary btn-xl">Aplicar filtros</button>
                    <a class="btn btn-primary btn-xl" href="/?r=relatoriosTematicos/index">Remover filtro</a>
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

<div class="row g-4 mb-4">
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-calendar-check"></i></div>
                <div>
                    <div class="text-muted small">Reservas</div>
                    <div class="display-6 fw-bold"><?= (int)($summary['total_reservas'] ?? 0) ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-activity"></i>Total geral</span>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="text-muted small">PAX reservadas</div>
                    <div class="display-6 fw-bold"><?= (int)($summary['pax_reservadas'] ?? 0) ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-graph-up"></i>Base da reserva</span>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-people"></i></div>
                <div>
                    <div class="text-muted small">PAX comparecidas</div>
                    <div class="display-6 fw-bold status-success"><?= (int)($summary['pax_comparecidas'] ?? 0) ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-check2-circle"></i>Taxa <?= h($taxaComparecimento) ?>%</span>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-person-x"></i></div>
                <div>
                    <div class="text-muted small">PAX não comparecidas</div>
                    <div class="display-6 fw-bold status-danger"><?= (int)($summary['pax_nao_comparecidas'] ?? 0) ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-exclamation-triangle"></i>Faltante</span>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-lg-6">
        <div class="card p-4">
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
                            <th>Finalizadas</th>
                            <th>No-show</th>
                            <th>Canceladas</th>
                            <th>PAX reservadas</th>
                            <th>PAX comparecidas</th>
                            <th>PAX faltantes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($byRestaurant as $row): ?>
                            <tr>
                                <td><span class="tag <?= restaurant_badge_class($row['restaurante']) ?>"><?= h($row['restaurante']) ?></span></td>
                                <td><?= (int)$row['total'] ?></td>
                                <td><?= (int)$row['finalizadas'] ?></td>
                                <td><?= (int)$row['no_shows'] ?></td>
                                <td><?= (int)$row['canceladas'] ?></td>
                                <td><?= (int)$row['pax_reservadas'] ?></td>
                                <td><?= (int)$row['pax_comparecidas'] ?></td>
                                <td><?= max(0, (int)$row['pax_reservadas'] - (int)$row['pax_comparecidas']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($byRestaurant)): ?>
                            <tr><td colspan="8" class="text-muted">Sem dados.</td></tr>
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
                    <div class="text-uppercase text-muted small">Por turno</div>
                    <h5 class="fw-bold mb-0">Fluxo por horário</h5>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Turno</th>
                            <th>Total</th>
                            <th>Finalizadas</th>
                            <th>No-show</th>
                            <th>Canceladas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($byTurno as $row): ?>
                            <tr>
                                <td><span class="tag badge-soft"><?= h($row['turno']) ?></span></td>
                                <td><?= (int)$row['total'] ?></td>
                                <td><?= (int)$row['finalizadas'] ?></td>
                                <td><?= (int)$row['no_shows'] ?></td>
                                <td><?= (int)$row['canceladas'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($byTurno)): ?>
                            <tr><td colspan="8" class="text-muted">Sem dados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card p-4 mb-4">
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
                    <th>Finalizadas</th>
                    <th>No-show</th>
                    <th>Canceladas</th>
                    <th>PAX reservadas</th>
                    <th>PAX comparecidas</th>
                    <th>PAX faltantes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($byDay as $row): ?>
                    <tr>
                        <td><?= h($row['data']) ?></td>
                        <td><?= (int)$row['total'] ?></td>
                        <td><?= (int)$row['finalizadas'] ?></td>
                        <td><?= (int)$row['no_shows'] ?></td>
                        <td><?= (int)$row['canceladas'] ?></td>
                        <td><?= (int)$row['pax_reservadas'] ?></td>
                        <td><?= (int)$row['pax_comparecidas'] ?></td>
                        <td><?= max(0, (int)$row['pax_reservadas'] - (int)$row['pax_comparecidas']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($byDay)): ?>
                    <tr><td colspan="8" class="text-muted">Sem dados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card p-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-list-check"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Base detalhada</div>
            <h5 class="fw-bold mb-0">Reservas temáticas</h5>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Restaurante</th>
                    <th>Turno</th>
                    <th>UH</th>
                    <th>PAX reservada</th>
                    <th>PAX real</th>
                    <th>Status</th>
                    <th>Observação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $row): ?>
                    <tr>
                        <td><?= h($row['data_reserva']) ?></td>
                        <td><span class="tag <?= restaurant_badge_class($row['restaurante']) ?>"><?= h($row['restaurante']) ?></span></td>
                        <td><span class="tag badge-soft"><?= h($row['turno_hora']) ?></span></td>
                        <td><span class="uh-badge <?= uh_badge_class($row['uh_numero']) ?>"><?= h($row['uh_numero']) ?></span></td>
                        <td><?= h($row['pax']) ?></td>
                        <td><?= h($row['pax_real'] ?? '-') ?></td>
                        <td><span class="badge badge-soft"><?= h($row['status']) ?></span></td>
                        <td><?= h($row['observacao_reserva'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($list)): ?>
                    <tr><td colspan="8" class="text-muted">Sem reservas para o filtro atual.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

