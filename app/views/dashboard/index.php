<?php
$filters = $this->data['filters'] ?? [];
$restaurantes = $this->data['restaurantes'] ?? [];
$operacoes = $this->data['operacoes'] ?? [];
$stats = $this->data['stats'] ?? [];
?>
<div class="card p-4 mb-4">
    <h3 class="fw-bold mb-3">Dashboard</h3>
    <form class="row g-3 align-items-end" method="get" action="/">
        <input type="hidden" name="r" value="dashboard/index">
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
        <div class="col-12">
            <button class="btn btn-primary btn-xl">Aplicar filtros</button>
        </div>
    </form>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card p-3 text-center">
            <div class="text-muted">Duplicados</div>
            <div class="display-6 fw-bold status-warning"><?= (int)$stats['duplicados'] ?></div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card p-3 text-center">
            <div class="text-muted">Fora do horário</div>
            <div class="display-6 fw-bold status-danger"><?= (int)$stats['fora_horario'] ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-6">
        <div class="card p-4">
            <h5 class="fw-bold">Total de PAX por Operação</h5>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Operação</th><th>Total</th></tr></thead>
                    <tbody>
                        <?php foreach ($stats['totais_operacao'] ?? [] as $row): ?>
                            <tr><td><span class="tag <?= operation_badge_class($row['nome']) ?>"><?= h($row['nome']) ?></span></td><td><?= h($row['total_pax']) ?></td></tr>
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
            <h5 class="fw-bold">Total de PAX por Restaurante</h5>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Restaurante</th><th>Total</th></tr></thead>
                    <tbody>
                        <?php foreach ($stats['totais_restaurante'] ?? [] as $row): ?>
                            <tr><td><span class="tag <?= restaurant_badge_class($row['nome']) ?>"><?= h($row['nome']) ?></span></td><td><?= h($row['total_pax']) ?></td></tr>
                        <?php endforeach; ?>
                        <?php if (empty($stats['totais_restaurante'])): ?>
                            <tr><td colspan="2" class="text-muted">Sem dados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card p-4">
            <h5 class="fw-bold">Fluxo por horário</h5>
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
