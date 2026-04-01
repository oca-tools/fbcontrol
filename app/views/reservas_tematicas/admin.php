<?php
$flash = $this->data['flash'] ?? null;
$restaurantes = $this->data['restaurantes'] ?? [];
$configs = $this->data['configs'] ?? [];
$turnos = $this->data['turnos'] ?? [];
$periodos = $this->data['periodos'] ?? [];
$turnosConfig = $this->data['turnos_config'] ?? [];

$configMap = [];
foreach ($configs as $cfg) {
    $configMap[$cfg['restaurante_id']] = $cfg;
}
?>

<style>
    .table-capacidade-turno {
        table-layout: fixed;
        width: 100%;
    }
    .table-capacidade-turno th,
    .table-capacidade-turno td {
        white-space: nowrap;
    }
    .table-capacidade-turno .col-rest {
        width: 180px;
    }
    .table-capacidade-turno .col-total {
        width: 92px;
    }
    .table-capacidade-turno .col-auto-cancel {
        width: 118px;
        text-align: center;
    }
    .table-capacidade-turno .col-turno {
        width: 72px;
        text-align: center;
    }
    .table-capacidade-turno .turno-input {
        min-width: 0 !important;
        width: 100%;
        max-width: 72px;
        margin: 0 auto;
        text-align: center;
        padding-left: 0.35rem;
        padding-right: 0.35rem;
    }
    .table-capacidade-turno .total-input {
        min-width: 0 !important;
        width: 100%;
        max-width: 86px;
        text-align: center;
        padding-left: 0.35rem;
        padding-right: 0.35rem;
    }
    .table-capacidade-turno .rest-tag {
        display: inline-flex;
        max-width: 166px;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: middle;
    }
    @media (max-width: 1200px) {
        .table-capacidade-turno .col-rest {
            width: 160px;
        }
        .table-capacidade-turno .col-turno {
            width: 68px;
        }
    }
</style>

<div class="card card-soft p-4 mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div class="section-title">
            <div class="icon"><i class="bi bi-sliders"></i></div>
            <div>
                <div class="text-uppercase text-muted small">Reservas Temáticas</div>
                <h3 class="fw-bold mb-1">Configurações e Supervisão</h3>
                <div class="text-muted">Ajuste capacidades, turnos e períodos de reserva.</div>
            </div>
        </div>
        <span class="badge badge-soft">Administração</span>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= h($flash['type']) ?> mt-3"><?= h($flash['message']) ?></div>
    <?php endif; ?>
</div>

<div class="card p-4 mb-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-people"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Capacidade por restaurante</div>
            <h5 class="fw-bold mb-0">Distribuição por turno</h5>
        </div>
    </div>

    <form method="post" action="/?r=reservasTematicas/admin" class="table-responsive">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="config_capacidade">
        <table class="table table-sm align-middle table-editor table-capacidade-turno">
            <thead>
                <tr>
                    <th class="col-rest">Restaurante</th>
                    <th class="col-total text-center">Total</th>
                    <th class="col-auto-cancel">Auto no-show (min)</th>
                    <?php foreach ($turnos as $turno): ?>
                        <th class="col-turno"><?= h(substr((string)$turno['hora'], 0, 5)) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($restaurantes as $rest): ?>
                    <?php $cfg = $configMap[$rest['id']] ?? ['capacidade_total' => 0]; ?>
                    <tr>
                        <td>
                            <span class="tag rest-tag <?= restaurant_badge_class($rest['nome']) ?>" title="<?= h($rest['nome']) ?>">
                                <?= h($rest['nome']) ?>
                            </span>
                        </td>
                        <td>
                            <input type="number" class="form-control total-input" name="capacidade_total[<?= (int)$rest['id'] ?>]" value="<?= h($cfg['capacidade_total'] ?? 0) ?>">
                        </td>
                        <td>
                            <input type="number" class="form-control total-input" min="0" max="240" name="auto_cancel_no_show_min[<?= (int)$rest['id'] ?>]" value="<?= h($cfg['auto_cancel_no_show_min'] ?? 0) ?>" title="Minutos após o horário do turno para marcar Não compareceu automaticamente">
                        </td>
                        <?php foreach ($turnosConfig[$rest['id']] ?? [] as $turnoCfg): ?>
                            <td>
                                <input type="number" class="form-control turno-input" name="capacidade_turno[<?= (int)$rest['id'] ?>][<?= (int)$turnoCfg['turno_id'] ?>]" value="<?= h($turnoCfg['capacidade'] ?? 0) ?>">
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="text-muted small mb-3">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Auto no-show (min):</strong> após esse tempo, reservas ainda em <em>Reservada</em> são movidas automaticamente para <em>Não compareceu</em>.
        </div>
        <button class="btn btn-primary btn-xl">Salvar capacidades</button>
    </form>
</div>

<div class="card p-4 mb-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-clock"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Turnos de operação</div>
            <h5 class="fw-bold mb-0">Horários noturnos</h5>
        </div>
    </div>

    <form method="post" action="/?r=reservasTematicas/admin" class="table-responsive">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="config_turnos">
        <table class="table table-sm align-middle table-editor">
            <thead>
                <tr>
                    <th>Hora</th>
                    <th class="col-mini">Ativo</th>
                    <th class="col-mini">Ordem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($turnos as $turno): ?>
                    <tr>
                        <td><input type="time" class="form-control" name="turnos[<?= (int)$turno['id'] ?>][hora]" value="<?= h($turno['hora']) ?>"></td>
                        <td>
                            <select class="form-select" name="turnos[<?= (int)$turno['id'] ?>][ativo]">
                                <option value="1" <?= (int)$turno['ativo'] === 1 ? 'selected' : '' ?>>Sim</option>
                                <option value="0" <?= (int)$turno['ativo'] === 0 ? 'selected' : '' ?>>Não</option>
                            </select>
                        </td>
                        <td><input type="number" class="form-control" name="turnos[<?= (int)$turno['id'] ?>][ordem]" value="<?= h($turno['ordem']) ?>"></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button class="btn btn-primary btn-xl">Salvar turnos</button>
    </form>
</div>

<div class="card p-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-calendar-range"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Períodos de reserva</div>
            <h5 class="fw-bold mb-0">Janelas de atendimento</h5>
        </div>
    </div>

    <form method="post" action="/?r=reservasTematicas/admin" class="table-responsive">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="config_periodos">
        <table class="table table-sm align-middle table-editor">
            <thead>
                <tr>
                    <th>Início</th>
                    <th>Fim</th>
                    <th class="col-mini">Ativo</th>
                    <th class="col-mini">Ordem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($periodos as $periodo): ?>
                    <tr>
                        <td><input type="time" class="form-control" name="periodos[<?= (int)$periodo['id'] ?>][hora_inicio]" value="<?= h($periodo['hora_inicio']) ?>"></td>
                        <td><input type="time" class="form-control" name="periodos[<?= (int)$periodo['id'] ?>][hora_fim]" value="<?= h($periodo['hora_fim']) ?>"></td>
                        <td>
                            <select class="form-select" name="periodos[<?= (int)$periodo['id'] ?>][ativo]">
                                <option value="1" <?= (int)$periodo['ativo'] === 1 ? 'selected' : '' ?>>Sim</option>
                                <option value="0" <?= (int)$periodo['ativo'] === 0 ? 'selected' : '' ?>>Não</option>
                            </select>
                        </td>
                        <td><input type="number" class="form-control" name="periodos[<?= (int)$periodo['id'] ?>][ordem]" value="<?= h($periodo['ordem']) ?>"></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button class="btn btn-primary btn-xl">Salvar períodos</button>
    </form>
</div>

