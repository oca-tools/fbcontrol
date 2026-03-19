<?php
$today = $this->data['today'] ?? '';
$activeShifts = $this->data['active_shifts'] ?? [];
$activeRestaurants = $this->data['active_restaurants'] ?? [];
$stats = $this->data['stats_today'] ?? [];
$recentes = $this->data['recentes'] ?? [];
?>
<div class="card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <div class="text-uppercase text-muted small">Centro de controle</div>
            <h3 class="fw-bold mb-1">Operação em tempo real</h3>
            <div class="text-muted">Dia <?= h($today) ?></div>
        </div>
        <div class="turno-pill"><i class="bi bi-activity"></i> Monitoramento ativo</div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card p-3 text-center">
            <div class="text-muted"><i class="bi bi-clipboard-data me-1"></i>Acessos hoje</div>
            <div class="display-6 fw-bold"><?= (int)($stats['total_acessos'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card p-3 text-center">
            <div class="text-muted"><i class="bi bi-people me-1"></i>PAX hoje</div>
            <div class="display-6 fw-bold"><?= (int)($stats['total_pax'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card p-3 text-center">
            <div class="text-muted"><i class="bi bi-exclamation-triangle me-1"></i>Duplicados</div>
            <div class="display-6 fw-bold status-warning"><?= (int)($stats['duplicados'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card p-3 text-center">
            <div class="text-muted"><i class="bi bi-clock-history me-1"></i>Fora do horário</div>
            <div class="display-6 fw-bold status-danger"><?= (int)($stats['fora_horario'] ?? 0) ?></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-lg-5">
        <div class="card p-4">
    <h5 class="fw-bold mb-3">Restaurantes ativos</h5>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($activeRestaurants as $rest): ?>
                    <a class="btn btn-outline-primary" href="/?r=dashboard/restaurant&id=<?= (int)$rest['id'] ?>">
                        <span class="tag <?= restaurant_badge_class($rest['nome']) ?>"><?= h($rest['nome']) ?></span>
                    </a>
                <?php endforeach; ?>
                <?php if (empty($activeRestaurants)): ?>
                    <span class="text-muted">Nenhum restaurante com turno ativo.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-7">
        <div class="card p-4">
    <h5 class="fw-bold mb-3">Turnos abertos</h5>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Restaurante</th>
                            <th>Operação</th>
                            <th>Usuario</th>
                            <th>Início</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activeShifts as $shift): ?>
                            <tr>
                                <td><span class="tag <?= restaurant_badge_class($shift['restaurante']) ?>"><?= h($shift['restaurante']) ?></span></td>
                                <td><span class="tag <?= operation_badge_class($shift['operacao']) ?>"><?= h($shift['operacao']) ?></span></td>
                                <td><?= h($shift['usuario']) ?></td>
                                <td><?= h($shift['inicio_em']) ?></td>
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

<div class="card p-4">
    <h5 class="fw-bold">Últimos registros do dia</h5>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Restaurante</th>
                    <th>UH</th>
                    <th>PAX</th>
                    <th>Operação</th>
                    <th>Usuario</th>
                    <th>horário</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentes as $item): ?>
                    <tr>
                        <td><span class="tag <?= restaurant_badge_class($item['restaurante']) ?>"><?= h($item['restaurante']) ?></span></td>
                        <td><span class="uh-badge <?= uh_badge_class($item['uh_numero']) ?>"><?= h($item['uh_numero']) ?></span></td>
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
