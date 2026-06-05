<?php
$flash = $this->data['flash'] ?? null;
$restaurantes = $this->data['restaurantes'] ?? [];
$configs = $this->data['configs'] ?? [];
$turnos = $this->data['turnos'] ?? [];
$periodos = $this->data['periodos'] ?? [];
$turnosConfig = $this->data['turnos_config'] ?? [];
$turnosConfigData = $this->data['turnos_config_data'] ?? [];
$capacidadeData = $this->data['capacidade_data'] ?? date('Y-m-d');
$bloqueiosData = $this->data['bloqueios_data'] ?? [];
$bloqueiosSemanais = $this->data['bloqueios_semanais'] ?? [];
$canManageBloqueios = !empty($this->data['can_manage_bloqueios']);
$diasSemana = [
    0 => 'Domingo',
    1 => 'Segunda-feira',
    2 => 'Terça-feira',
    3 => 'Quarta-feira',
    4 => 'Quinta-feira',
    5 => 'Sexta-feira',
    6 => 'Sábado',
];

$configMap = [];
foreach ($configs as $cfg) {
    $configMap[$cfg['restaurante_id']] = $cfg;
}

?>

<style>
    .tematic-admin-page {
        min-width: 0;
    }
    .tematic-admin-page .config-hero {
        overflow: hidden;
        border: 1px solid rgba(249, 115, 22, 0.16);
        background:
            linear-gradient(135deg, rgba(255, 247, 237, 0.96), rgba(255, 255, 255, 0.94)),
            var(--ab-card);
    }
    html[data-theme='dark'] .tematic-admin-page .config-hero {
        background:
            linear-gradient(135deg, rgba(67, 56, 202, 0.16), rgba(15, 23, 42, 0.96)),
            var(--ab-card);
    }
    .tematic-admin-page .config-card {
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 24px;
        background: var(--ab-card);
        box-shadow: var(--ab-shadow-soft);
    }
    .tematic-admin-page .config-form-panel {
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 18px;
        padding: 0.9rem;
        background: rgba(248, 250, 252, 0.78);
    }
    html[data-theme='dark'] .tematic-admin-page .config-form-panel {
        background: rgba(15, 23, 42, 0.5);
    }
    .tematic-admin-page .closure-card {
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 16px;
        background: color-mix(in srgb, var(--ab-card) 86%, var(--ab-soft-bg) 14%);
        padding: 0.75rem;
        min-width: 0;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
    }
    html[data-theme='dark'] .tematic-admin-page .closure-card {
        background: rgba(15, 23, 42, 0.44);
    }
    .tematic-admin-page .closure-workspace {
        display: grid;
        grid-template-columns: minmax(0, 1.1fr) minmax(280px, 0.9fr);
        gap: 1rem;
        align-items: stretch;
    }
    .tematic-admin-page .closure-panel {
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 18px;
        background: color-mix(in srgb, var(--ab-card) 92%, var(--ab-soft-bg) 8%);
        padding: 0.95rem;
        min-width: 0;
    }
    .tematic-admin-page .closure-panel-muted {
        background: color-mix(in srgb, var(--ab-soft-bg) 68%, var(--ab-card) 32%);
    }
    .tematic-admin-page .closure-panel-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.8rem;
        color: var(--ab-ink);
        font-weight: 850;
    }
    .tematic-admin-page .closure-panel-title i {
        width: 34px;
        height: 34px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--ab-accent);
        background: color-mix(in srgb, var(--ab-accent) 13%, var(--ab-card) 87%);
    }
    html[data-theme='dark'] .tematic-admin-page .closure-panel-title i {
        color: #f8fafc;
    }
    .tematic-admin-page .closure-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
    }
    .tematic-admin-page .closure-form-grid .span-2 {
        grid-column: 1 / -1;
    }
    .tematic-admin-page .closure-list {
        display: grid;
        gap: 0.65rem;
    }
    .tematic-admin-page .closure-list-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.65rem;
    }
    .tematic-admin-page .closure-main {
        min-width: 0;
    }
    .tematic-admin-page .closure-main .tag {
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: middle;
    }
    .tematic-admin-page .closure-empty {
        border: 1px dashed rgba(148, 163, 184, 0.35);
        border-radius: 16px;
        padding: 1rem;
        color: var(--ab-muted);
        background: color-mix(in srgb, var(--ab-card) 68%, transparent);
    }
    .tematic-admin-page .closure-card .btn {
        white-space: nowrap;
    }
    .tematic-admin-page .config-save-row {
        padding: 0.9rem;
        border-top: 1px solid rgba(148, 163, 184, 0.18);
    }
    .tematic-admin-page .col-mini {
        width: 112px;
    }
    .tematic-admin-page .capacity-settings-form {
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 20px;
        background: color-mix(in srgb, var(--ab-card) 90%, var(--ab-soft-bg) 10%);
        overflow: hidden;
    }
    .tematic-admin-page .capacity-card-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 0.85rem;
        padding: 1rem;
    }
    .tematic-admin-page .capacity-card {
        position: relative;
        min-width: 0;
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 16px;
        background:
            linear-gradient(180deg, color-mix(in srgb, var(--ab-card) 92%, var(--ab-soft-bg) 8%), var(--ab-card)),
            var(--ab-card);
        padding: 0.85rem;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.055);
        overflow: hidden;
    }
    .tematic-admin-page .capacity-card::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 4px;
        background: linear-gradient(180deg, var(--ab-accent), color-mix(in srgb, var(--ab-accent) 35%, transparent));
    }
    .tematic-admin-page .capacity-card-head {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 88px;
        gap: 0.7rem;
        align-items: center;
        margin-bottom: 0.7rem;
        padding-left: 0.15rem;
    }
    .tematic-admin-page .capacity-title-stack {
        min-width: 0;
    }
    .tematic-admin-page .capacity-title-stack .rest-tag {
        max-width: 100%;
    }
    .tematic-admin-page .capacity-subtitle {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        margin-top: 0.45rem;
    }
    .tematic-admin-page .capacity-mini-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 999px;
        background: color-mix(in srgb, var(--ab-soft-bg) 76%, var(--ab-card) 24%);
        color: var(--ab-muted);
        padding: 0.2rem 0.5rem;
        font-size: 0.68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .tematic-admin-page .capacity-total-box {
        width: 88px;
        min-width: 88px;
        max-width: 88px;
        border: 1px dashed rgba(148, 163, 184, 0.5);
        border-radius: 12px;
        background: color-mix(in srgb, var(--ab-soft-bg) 78%, var(--ab-card) 22%);
        padding: 0.4rem 0.5rem;
        text-align: center;
    }
    .tematic-admin-page .capacity-total-box .capacity-total-label {
        color: var(--ab-muted);
        font-size: 0.62rem;
        font-weight: 850;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        line-height: 1;
        margin-bottom: 0.15rem;
    }
    .tematic-admin-page .capacity-total-box .form-control {
        border: 0;
        background: transparent;
        padding: 0;
        min-height: 0;
        text-align: center;
        font-size: 1.15rem;
        font-weight: 850;
        color: var(--ab-ink);
        box-shadow: none;
    }
    .tematic-admin-page .capacity-meta-grid {
        margin-bottom: 0.55rem;
    }
    .tematic-admin-page .capacity-meta-grid > div {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.6rem;
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 12px;
        background: color-mix(in srgb, var(--ab-soft-bg) 60%, var(--ab-card) 40%);
        padding: 0.4rem 0.5rem;
    }
    .tematic-admin-page .capacity-turnos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(56px, 1fr));
        gap: 0.35rem;
    }
    .tematic-admin-page .capacity-turno-item {
        border: 1px solid rgba(148, 163, 184, 0.2);
        border-radius: 11px;
        padding: 0.32rem;
        background: color-mix(in srgb, var(--ab-soft-bg) 65%, var(--ab-card) 35%);
    }
    .tematic-admin-page .capacity-turno-item .form-label,
    .tematic-admin-page .capacity-meta-grid .form-label {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.25rem;
        margin-bottom: 0.2rem;
        color: var(--ab-muted);
        font-size: 0.64rem;
        font-weight: 850;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .tematic-admin-page .capacity-meta-grid .form-label {
        justify-content: flex-start;
        margin-bottom: 0;
        white-space: nowrap;
    }
    .tematic-admin-page .capacity-turno-item .form-control,
    .tematic-admin-page .capacity-meta-grid .form-control {
        text-align: center;
        min-height: 32px;
        padding: 0.24rem 0.35rem;
        font-weight: 800;
    }
    .tematic-admin-page .capacity-meta-grid .form-control {
        width: 76px;
        min-width: 76px;
        margin: 0;
    }
    html[data-theme='dark'] .tematic-admin-page .capacity-card {
        background:
            linear-gradient(180deg, rgba(30, 41, 59, 0.94), rgba(15, 23, 42, 0.88)),
            var(--ab-card);
    }
    html[data-theme='dark'] .tematic-admin-page .capacity-turno-item,
    html[data-theme='dark'] .tematic-admin-page .capacity-total-box,
    html[data-theme='dark'] .tematic-admin-page .capacity-mini-badge {
        background: rgba(15, 23, 42, 0.44);
    }
    .tematic-admin-page .schedule-editor-form {
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 20px;
        background: color-mix(in srgb, var(--ab-card) 90%, var(--ab-soft-bg) 10%);
        overflow: hidden;
    }
    .tematic-admin-page .schedule-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 0.75rem;
        padding: 1rem;
    }
    .tematic-admin-page .schedule-card {
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 16px;
        background: var(--ab-card);
        padding: 0.8rem;
        min-width: 0;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
    }
    .tematic-admin-page .schedule-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.6rem;
        margin-bottom: 0.7rem;
    }
    .tematic-admin-page .schedule-time-preview {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        color: var(--ab-ink);
        font-size: 1.05rem;
        font-weight: 850;
        min-width: 0;
    }
    .tematic-admin-page .schedule-status {
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 999px;
        padding: 0.18rem 0.5rem;
        font-size: 0.68rem;
        font-weight: 850;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--ab-muted);
        background: color-mix(in srgb, var(--ab-soft-bg) 72%, var(--ab-card) 28%);
        white-space: nowrap;
    }
    .tematic-admin-page .schedule-status.is-active {
        color: #166534;
        background: rgba(22, 163, 74, 0.12);
        border-color: rgba(22, 163, 74, 0.22);
    }
    html[data-theme='dark'] .tematic-admin-page .schedule-status.is-active {
        color: #bbf7d0;
    }
    .tematic-admin-page .schedule-fields {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.55rem;
    }
    .tematic-admin-page .schedule-fields .span-2 {
        grid-column: 1 / -1;
    }
    .tematic-admin-page .schedule-fields .form-label,
    .tematic-admin-page .schedule-add-form .form-label {
        margin-bottom: 0.25rem;
        color: var(--ab-muted);
        font-size: 0.68rem;
        font-weight: 850;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .tematic-admin-page .schedule-fields .form-control,
    .tematic-admin-page .schedule-fields .form-select,
    .tematic-admin-page .schedule-add-form .form-control,
    .tematic-admin-page .schedule-add-form .form-select {
        min-height: 40px;
    }
    .tematic-admin-page .schedule-add-form {
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 18px;
        background: color-mix(in srgb, var(--ab-soft-bg) 65%, var(--ab-card) 35%);
        padding: 0.9rem;
    }
    html[data-theme='dark'] .tematic-admin-page .schedule-card,
    html[data-theme='dark'] .tematic-admin-page .schedule-add-form {
        background: rgba(15, 23, 42, 0.44);
    }
    .tematic-admin-page .capacity-card .rest-tag {
        display: inline-flex;
        max-width: 166px;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: middle;
    }
    @media (max-width: 1200px) {
        .tematic-admin-page .capacity-card-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 768px) {
        .tematic-admin-page .capacity-card-grid {
            grid-template-columns: 1fr;
            padding: 0.7rem;
            gap: 0.7rem;
        }
        .tematic-admin-page .capacity-card {
            padding: 0.75rem;
        }
        .tematic-admin-page .schedule-grid {
            grid-template-columns: 1fr;
            padding: 0.7rem;
        }
        .tematic-admin-page .schedule-card {
            padding: 0.75rem;
        }
        .tematic-admin-page .capacity-turnos-grid {
            grid-template-columns: repeat(auto-fit, minmax(56px, 1fr));
        }
        .tematic-admin-page .config-hero,
        .tematic-admin-page .config-card {
            border-radius: 18px;
            padding: 1rem !important;
        }
        .tematic-admin-page .config-form-panel {
            padding: 0.8rem;
        }
        .tematic-admin-page .closure-card .btn {
            width: 100%;
        }
        .tematic-admin-page .closure-workspace,
        .tematic-admin-page .closure-list-grid {
            grid-template-columns: 1fr;
        }
        .tematic-admin-page .closure-form-grid {
            grid-template-columns: 1fr;
        }
        .tematic-admin-page .closure-card {
            display: grid !important;
            gap: 0.65rem;
        }
    }
    @media (max-width: 480px) {
        .tematic-admin-page .capacity-card-head {
            grid-template-columns: minmax(0, 1fr) 82px;
            align-items: flex-start;
        }
        .tematic-admin-page .capacity-turnos-grid {
            grid-template-columns: repeat(auto-fit, minmax(54px, 1fr));
        }
        .tematic-admin-page .capacity-total-box {
            width: 82px;
            min-width: 82px;
            max-width: 82px;
        }
    }
</style>

<div class="tematic-admin-page">

<div class="card card-soft config-hero p-4 mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div class="section-title">
            <div class="icon"><i class="bi bi-sliders"></i></div>
            <div>
                <div class="text-uppercase text-muted small">Reservas Temáticas</div>
                <h3 class="fw-bold mb-1">Central de Configurações Temáticas</h3>
                <div class="text-muted">Ajuste disponibilidade, capacidades, turnos e períodos de reserva.</div>
            </div>
        </div>
        <span class="badge badge-soft">Administração</span>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= h($flash['type']) ?> mt-3"><?= h($flash['message']) ?></div>
    <?php endif; ?>
</div>

<?php if ($canManageBloqueios): ?>
<div class="card config-card p-4 mb-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-calendar-x"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Rotina por ocupação</div>
            <h5 class="fw-bold mb-0">Fechamento de restaurante por data</h5>
            <div class="text-muted small">Bloqueia novas reservas no temático selecionado sem apagar reservas já registradas.</div>
        </div>
    </div>

    <div class="closure-workspace">
        <div class="closure-panel">
            <div class="closure-panel-title"><i class="bi bi-plus-circle"></i><span>Novo fechamento pontual</span></div>
            <form method="post" action="/?r=reservasTematicas/admin" class="closure-form-grid">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="bloqueio_data">
                <input type="hidden" name="fechar" value="1">
                <div>
                    <label class="form-label mb-1">Data</label>
                    <input type="date" class="form-control input-xl" name="data_bloqueio" value="<?= h($capacidadeData) ?>" required>
                </div>
                <div>
                    <label class="form-label mb-1">Restaurante</label>
                    <select class="form-select input-xl" name="restaurante_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($restaurantes as $rest): ?>
                            <option value="<?= (int)$rest['id'] ?>"><?= h($rest['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="span-2">
                    <label class="form-label mb-1">Motivo</label>
                    <input type="text" class="form-control input-xl" maxlength="255" name="motivo" placeholder="Ex.: baixa ocupação prevista" required>
                </div>
                <div class="span-2 d-grid">
                    <button class="btn btn-outline-danger btn-xl"><i class="bi bi-x-circle me-1"></i>Fechar restaurante</button>
                </div>
            </form>
        </div>

        <div class="closure-panel closure-panel-muted">
            <div class="closure-panel-title"><i class="bi bi-calendar-check"></i><span>Fechados em <?= h(date('d/m/Y', strtotime($capacidadeData))) ?></span></div>
            <?php if (!empty($bloqueiosData)): ?>
                <div class="closure-list">
                    <?php foreach ($bloqueiosData as $bloqueio): ?>
                        <form method="post" action="/?r=reservasTematicas/admin" class="closure-card d-flex justify-content-between align-items-center gap-2">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="action" value="bloqueio_data">
                            <input type="hidden" name="fechar" value="0">
                            <input type="hidden" name="data_bloqueio" value="<?= h($capacidadeData) ?>">
                            <input type="hidden" name="restaurante_id" value="<?= (int)$bloqueio['restaurante_id'] ?>">
                            <input type="hidden" name="motivo" value="<?= h((string)($bloqueio['motivo'] ?? '')) ?>">
                            <div class="closure-main">
                                <span class="tag <?= restaurant_badge_class($bloqueio['restaurante']) ?>"><?= h($bloqueio['restaurante']) ?></span>
                                <div class="text-muted small mt-1"><?= h((string)($bloqueio['motivo'] ?? 'Sem motivo informado')) ?></div>
                            </div>
                            <button class="btn btn-outline-primary btn-sm" data-confirm="Reabrir este restaurante para novas reservas nesta data?">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Reabrir
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="closure-empty">Nenhum restaurante fechado nesta data.</div>
            <?php endif; ?>
        </div>
    </div>

    <hr class="my-4">

    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-calendar2-week"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Cronograma fixo</div>
            <h5 class="fw-bold mb-0">Fechamento semanal por restaurante</h5>
            <div class="text-muted small">Define dias fixos em que um temático não recebe novas reservas.</div>
        </div>
    </div>

    <div class="closure-workspace">
        <div class="closure-panel">
            <div class="closure-panel-title"><i class="bi bi-calendar-plus"></i><span>Nova regra semanal</span></div>
            <form method="post" action="/?r=reservasTematicas/admin" class="closure-form-grid">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="bloqueio_semanal">
                <input type="hidden" name="fechar" value="1">
                <div>
                    <label class="form-label mb-1">Restaurante</label>
                    <select class="form-select input-xl" name="restaurante_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($restaurantes as $rest): ?>
                            <option value="<?= (int)$rest['id'] ?>"><?= h($rest['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label mb-1">Dia fechado</label>
                    <select class="form-select input-xl" name="dia_semana" required>
                        <?php foreach ($diasSemana as $diaKey => $diaLabel): ?>
                            <option value="<?= (int)$diaKey ?>"><?= h($diaLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="span-2">
                    <label class="form-label mb-1">Motivo</label>
                    <input type="text" class="form-control input-xl" maxlength="255" name="motivo" placeholder="Ex.: rotina de baixa ocupação" required>
                </div>
                <div class="span-2 d-grid">
                    <button class="btn btn-outline-danger btn-xl"><i class="bi bi-calendar-minus me-1"></i>Salvar regra</button>
                </div>
            </form>
        </div>

        <div class="closure-panel closure-panel-muted">
            <div class="closure-panel-title"><i class="bi bi-calendar2-check"></i><span>Regras semanais ativas</span></div>
            <?php if (!empty($bloqueiosSemanais)): ?>
                <div class="closure-list-grid">
                    <?php foreach ($bloqueiosSemanais as $bloqueio): ?>
                        <?php $diaSemana = (int)($bloqueio['dia_semana'] ?? -1); ?>
                        <form method="post" action="/?r=reservasTematicas/admin" class="closure-card d-flex justify-content-between align-items-center gap-2">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="action" value="bloqueio_semanal">
                            <input type="hidden" name="fechar" value="0">
                            <input type="hidden" name="restaurante_id" value="<?= (int)$bloqueio['restaurante_id'] ?>">
                            <input type="hidden" name="dia_semana" value="<?= $diaSemana ?>">
                            <input type="hidden" name="motivo" value="<?= h((string)($bloqueio['motivo'] ?? '')) ?>">
                            <div class="closure-main">
                                <span class="tag <?= restaurant_badge_class($bloqueio['restaurante']) ?>"><?= h($bloqueio['restaurante']) ?></span>
                                <span class="badge badge-soft ms-1"><?= h($diasSemana[$diaSemana] ?? 'Dia inválido') ?></span>
                                <div class="text-muted small mt-1"><?= h((string)($bloqueio['motivo'] ?? 'Sem motivo informado')) ?></div>
                            </div>
                            <button class="btn btn-outline-primary btn-sm" data-confirm="Remover este fechamento semanal?">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Remover
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="closure-empty">Nenhuma regra semanal configurada.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card config-card p-4 mb-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-people"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Capacidade por restaurante</div>
            <h5 class="fw-bold mb-0">Distribuição por turno</h5>
            <div class="text-muted small">Informe a capacidade total do restaurante. O sistema divide automaticamente entre os turnos ativos.</div>
        </div>
    </div>

    <form method="post" action="/?r=reservasTematicas/admin" class="capacity-settings-form">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="config_capacidade">
        <div class="capacity-card-grid">
            <?php foreach ($restaurantes as $rest): ?>
                <?php
                    $restId = (int)$rest['id'];
                    $cfg = $configMap[$restId] ?? ['capacidade_total' => 0];
                    $turnoConfigById = [];
                    $turnoTotal = 0;
                    foreach ($turnosConfig[$restId] ?? [] as $turnoCfg) {
                        $turnoConfigById[(int)$turnoCfg['turno_id']] = $turnoCfg;
                        $turnoTotal += (int)($turnoCfg['capacidade'] ?? 0);
                    }
                    $configTotal = (int)($cfg['capacidade_total'] ?? 0);
                    $displayTotal = $configTotal > 0 ? $configTotal : $turnoTotal;
                ?>
                <section class="capacity-card" data-capacity-card="<?= $restId ?>">
                    <div class="capacity-card-head">
                        <div class="capacity-title-stack">
                            <span class="tag rest-tag <?= restaurant_badge_class($rest['nome']) ?>" title="<?= h($rest['nome']) ?>">
                                <?= h($rest['nome']) ?>
                            </span>
                            <div class="capacity-subtitle">
                                <span class="capacity-mini-badge"><i class="bi bi-diagram-3"></i>Distribuição automática</span>
                                <span class="capacity-mini-badge"><i class="bi bi-clock"></i><?= count($turnos) ?> turnos ativos</span>
                            </div>
                        </div>
                        <div class="capacity-total-box">
                            <div class="capacity-total-label">Total</div>
                            <input
                                type="number"
                                min="0"
                                class="form-control"
                                name="capacidade_total[<?= $restId ?>]"
                                value="<?= h($displayTotal) ?>"
                                data-capacity-default-total="<?= $restId ?>"
                                title="Capacidade total distribuída automaticamente entre os turnos ativos"
                            >
                        </div>
                    </div>

                    <div class="capacity-meta-grid">
                        <div>
                            <label class="form-label" for="autoCancel<?= $restId ?>">
                                <i class="bi bi-hourglass-split"></i> Auto no-show (min)
                            </label>
                            <input
                                type="number"
                                class="form-control"
                                min="0"
                                max="240"
                                id="autoCancel<?= $restId ?>"
                                name="auto_cancel_no_show_min[<?= $restId ?>]"
                                value="<?= h($cfg['auto_cancel_no_show_min'] ?? 0) ?>"
                                title="Minutos após o horário do turno para marcar Não compareceu automaticamente"
                            >
                        </div>
                    </div>

                    <div class="capacity-turnos-grid">
                        <?php foreach ($turnos as $turno): ?>
                            <?php
                                $turnoId = (int)$turno['id'];
                                $turnoCfg = $turnoConfigById[$turnoId] ?? ['turno_id' => $turnoId, 'capacidade' => 0];
                            ?>
                            <div class="capacity-turno-item">
                                <label class="form-label" for="capacidadeTurno<?= $restId ?>_<?= $turnoId ?>">
                                    <i class="bi bi-clock"></i> <?= h(substr((string)$turno['hora'], 0, 5)) ?>
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    class="form-control"
                                    id="capacidadeTurno<?= $restId ?>_<?= $turnoId ?>"
                                    value="<?= h($turnoCfg['capacidade'] ?? 0) ?>"
                                    readonly
                                    tabindex="-1"
                                    data-capacity-default-preview="<?= $restId ?>"
                                >
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
        <div class="text-muted small px-3 pb-3">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Distribuição automática:</strong> se o total não dividir perfeitamente, as vagas extras ficam nos primeiros turnos ativos. O <strong>Auto no-show (min)</strong> move reservas ainda em <em>Reservada</em> para <em>Não compareceu</em>.
        </div>
        <div class="config-save-row">
            <button class="btn btn-primary btn-xl">Salvar capacidades</button>
        </div>
    </form>
</div>

<div class="card config-card p-4 mb-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-calendar2-week"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Capacidade por data futura</div>
            <h5 class="fw-bold mb-0">Exceções por dia</h5>
            <div class="text-muted small">Use quando um dia específico precisar de distribuição diferente. Sem exceção, vale o padrão acima.</div>
        </div>
    </div>

    <div class="row g-2 align-items-end mb-3 config-form-panel">
        <div class="col-12 col-md-3">
            <label class="form-label mb-1">Data da capacidade</label>
            <input type="date" class="form-control input-xl" id="capacidadeDataPicker" name="cap_data" value="<?= h($capacidadeData) ?>">
        </div>
        <div class="col-12 col-md-6">
            <div class="text-muted small" id="capacidadeDataStatus">
                Alterar a data carrega automaticamente as capacidades daquele dia.
            </div>
        </div>
    </div>

    <form method="post" action="/?r=reservasTematicas/admin" class="capacity-settings-form">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="config_capacidade_data">
        <input type="hidden" name="capacidade_data" id="capacidadeDataHidden" value="<?= h($capacidadeData) ?>">
        <div class="capacity-card-grid">
            <?php foreach ($restaurantes as $rest): ?>
                <?php
                    $restId = (int)$rest['id'];
                    $dataConfigs = $turnosConfigData[$restId] ?? [];
                    $dataConfigById = [];
                    foreach ($dataConfigs as $cfgData) {
                        $dataConfigById[(int)$cfgData['turno_id']] = $cfgData;
                    }
                    $dataTotal = 0;
                    foreach ($turnos as $turno) {
                        $dataTotal += (int)($dataConfigById[(int)$turno['id']]['capacidade'] ?? 0);
                    }
                ?>
                <section class="capacity-card" data-capacity-date-card="<?= $restId ?>">
                    <div class="capacity-card-head">
                        <div class="capacity-title-stack">
                            <span class="tag rest-tag <?= restaurant_badge_class($rest['nome']) ?>" title="<?= h($rest['nome']) ?>">
                                <?= h($rest['nome']) ?>
                            </span>
                            <div class="capacity-subtitle">
                                <span class="capacity-mini-badge"><i class="bi bi-calendar-event"></i>Exceção</span>
                                <span class="capacity-mini-badge"><i class="bi bi-clock"></i><?= count($turnos) ?> turnos</span>
                            </div>
                        </div>
                        <div class="capacity-total-box">
                            <div class="capacity-total-label">Total</div>
                            <input
                                type="number"
                                class="form-control"
                                value="<?= (int)$dataTotal ?>"
                                readonly
                                tabindex="-1"
                                data-capacity-date-total="<?= $restId ?>"
                                title="Total calculado automaticamente pela soma dos turnos"
                            >
                        </div>
                    </div>

                    <div class="capacity-turnos-grid">
                        <?php foreach ($turnos as $turno): ?>
                            <?php
                                $turnoId = (int)$turno['id'];
                                $turnoCfg = $dataConfigById[$turnoId] ?? ['turno_id' => $turnoId, 'capacidade' => 0];
                            ?>
                            <div class="capacity-turno-item">
                                <label class="form-label" for="capacidadeDataTurno<?= $restId ?>_<?= $turnoId ?>">
                                    <i class="bi bi-clock"></i> <?= h(substr((string)$turno['hora'], 0, 5)) ?>
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    class="form-control"
                                    id="capacidadeDataTurno<?= $restId ?>_<?= $turnoId ?>"
                                    name="capacidade_data_turno[<?= $restId ?>][<?= (int)$turnoCfg['turno_id'] ?>]"
                                    value="<?= h($turnoCfg['capacidade'] ?? 0) ?>"
                                    data-capacity-date-input="<?= $restId ?>"
                                >
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
        <div class="text-muted small px-3 pb-3">
            O total é calculado automaticamente pela soma dos turnos. Exemplo: total 130 com cinco turnos de 26, ou total 80 com cinco turnos de 16.
        </div>
        <div class="config-save-row">
            <button class="btn btn-primary btn-xl">Salvar capacidade desta data</button>
        </div>
    </form>
</div>

<script>
(() => {
    const parseCapacityValue = (value) => {
        const parsed = parseInt(String(value || '').replace(',', '.'), 10);
        return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
    };

    const recalcDefaultDistribution = (restId) => {
        const totalEl = document.querySelector(`[data-capacity-default-total="${restId}"]`);
        if (!totalEl) return;
        const previews = Array.from(document.querySelectorAll(`[data-capacity-default-preview="${restId}"]`));
        const turnosCount = Math.max(1, previews.length);
        const total = parseCapacityValue(totalEl.value);
        const base = Math.floor(total / turnosCount);
        let remainder = total % turnosCount;
        previews.forEach((input) => {
            const extra = remainder > 0 ? 1 : 0;
            input.value = String(base + extra);
            remainder -= extra;
        });
    };

    document.querySelectorAll('[data-capacity-default-total]').forEach((totalEl) => {
        totalEl.addEventListener('input', () => recalcDefaultDistribution(totalEl.getAttribute('data-capacity-default-total')));
        totalEl.addEventListener('change', () => recalcDefaultDistribution(totalEl.getAttribute('data-capacity-default-total')));
    });

    document.querySelectorAll('[data-capacity-default-total]').forEach((totalEl) => {
        recalcDefaultDistribution(totalEl.getAttribute('data-capacity-default-total'));
    });

    const picker = document.getElementById('capacidadeDataPicker');
    const hidden = document.getElementById('capacidadeDataHidden');
    const status = document.getElementById('capacidadeDataStatus');
    if (!picker || !hidden) return;

    const parseCapacity = (value) => {
        return parseCapacityValue(value);
    };

    const recalcTotal = (restId) => {
        const totalEl = document.querySelector(`[data-capacity-date-total="${restId}"]`);
        if (!totalEl) return;
        let total = 0;
        document.querySelectorAll(`[data-capacity-date-input="${restId}"]`).forEach((input) => {
            total += parseCapacity(input.value);
        });
        totalEl.value = String(total);
    };

    document.querySelectorAll('[data-capacity-date-input]').forEach((input) => {
        input.addEventListener('input', () => recalcTotal(input.getAttribute('data-capacity-date-input')));
        input.addEventListener('change', () => recalcTotal(input.getAttribute('data-capacity-date-input')));
    });

    document.querySelectorAll('[data-capacity-date-total]').forEach((totalEl) => {
        recalcTotal(totalEl.getAttribute('data-capacity-date-total'));
    });

    picker.addEventListener('change', async () => {
        const date = picker.value;
        if (!date) return;
        hidden.value = date;
        if (status) status.textContent = 'Carregando capacidades...';

        try {
            const res = await fetch(`/?r=reservasTematicas/admin&ajax=capacity_date&cap_data=${encodeURIComponent(date)}`, {
                headers: { 'Accept': 'application/json' }
            });
            const payload = await res.json();
            if (!payload.ok) throw new Error(payload.message || 'Falha ao carregar capacidades.');

            Object.entries(payload.restaurants || {}).forEach(([restId, info]) => {
                const totalEl = document.querySelector(`[data-capacity-date-total="${restId}"]`);
                if (totalEl) totalEl.value = String(info.total || 0);
                Object.entries(info.turnos || {}).forEach(([turnoId, capacidade]) => {
                    const input = document.querySelector(`input[name="capacidade_data_turno[${restId}][${turnoId}]"]`);
                    if (input) input.value = String(capacidade || 0);
                });
                recalcTotal(restId);
            });
            if (status) status.textContent = `Capacidades carregadas para ${date}.`;
        } catch (err) {
            if (status) status.textContent = 'Não foi possível carregar a data. Tente novamente.';
        }
    });
})();
</script>

<div class="card config-card p-4 mb-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-clock"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Turnos de operação</div>
            <h5 class="fw-bold mb-0">Horários noturnos</h5>
        </div>
    </div>

    <form method="post" action="/?r=reservasTematicas/admin" class="schedule-editor-form">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="config_turnos">
        <div class="schedule-grid">
            <?php foreach ($turnos as $turno): ?>
                <?php
                    $turnoId = (int)$turno['id'];
                    $turnoAtivo = (int)$turno['ativo'] === 1;
                ?>
                <section class="schedule-card">
                    <div class="schedule-card-head">
                        <div class="schedule-time-preview"><i class="bi bi-clock"></i><?= h(substr((string)$turno['hora'], 0, 5)) ?></div>
                        <span class="schedule-status <?= $turnoAtivo ? 'is-active' : '' ?>"><?= $turnoAtivo ? 'Ativo' : 'Inativo' ?></span>
                    </div>
                    <div class="schedule-fields">
                        <div>
                            <label class="form-label" for="turnoHora<?= $turnoId ?>">Hora</label>
                            <input type="time" class="form-control" id="turnoHora<?= $turnoId ?>" name="turnos[<?= $turnoId ?>][hora]" value="<?= h($turno['hora']) ?>">
                        </div>
                        <div>
                            <label class="form-label" for="turnoOrdem<?= $turnoId ?>">Ordem</label>
                            <input type="number" class="form-control" id="turnoOrdem<?= $turnoId ?>" name="turnos[<?= $turnoId ?>][ordem]" value="<?= h($turno['ordem']) ?>">
                        </div>
                        <div>
                            <label class="form-label" for="turnoAtivo<?= $turnoId ?>">Ativo</label>
                            <select class="form-select" id="turnoAtivo<?= $turnoId ?>" name="turnos[<?= $turnoId ?>][ativo]">
                                <option value="1" <?= $turnoAtivo ? 'selected' : '' ?>>Sim</option>
                                <option value="0" <?= !$turnoAtivo ? 'selected' : '' ?>>Não</option>
                            </select>
                        </div>
                        <div class="d-grid align-self-end">
                            <button
                                type="submit"
                                class="btn btn-outline-danger"
                                name="remove_turno_id"
                                value="<?= $turnoId ?>"
                                onclick="return confirm('Confirma remover este turno? Se já houver histórico, ele será apenas inativado.');"
                            >
                                Remover
                            </button>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
        <div class="config-save-row">
            <button class="btn btn-primary btn-xl">Salvar turnos</button>
        </div>
    </form>

    <hr class="my-4">
    <form method="post" action="/?r=reservasTematicas/admin" class="row g-2 align-items-end schedule-add-form">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add_turno">
        <div class="col-12 col-md-4 col-lg-3">
            <label class="form-label mb-1">Novo turno (hora)</label>
            <input type="time" class="form-control" name="novo_turno_hora" required>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label mb-1">Ordem</label>
            <input type="number" class="form-control" name="novo_turno_ordem" value="0">
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label mb-1">Ativo</label>
            <select class="form-select" name="novo_turno_ativo">
                <option value="1" selected>Sim</option>
                <option value="0">Não</option>
            </select>
        </div>
        <div class="col-12 col-md-2 col-lg-2">
            <button class="btn btn-outline-primary w-100">Adicionar turno</button>
        </div>
    </form>
</div>

<div class="card config-card p-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-calendar-range"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Períodos de reserva</div>
            <h5 class="fw-bold mb-0">Horários de operação das reservas</h5>
        </div>
    </div>

    <form method="post" action="/?r=reservasTematicas/admin" class="schedule-editor-form">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="config_periodos">
        <div class="schedule-grid">
            <?php foreach ($periodos as $periodo): ?>
                <?php
                    $periodoId = (int)$periodo['id'];
                    $periodoAtivo = (int)$periodo['ativo'] === 1;
                ?>
                <section class="schedule-card">
                    <div class="schedule-card-head">
                        <div class="schedule-time-preview">
                            <i class="bi bi-calendar-range"></i><?= h(substr((string)$periodo['hora_inicio'], 0, 5)) ?> - <?= h(substr((string)$periodo['hora_fim'], 0, 5)) ?>
                        </div>
                        <span class="schedule-status <?= $periodoAtivo ? 'is-active' : '' ?>"><?= $periodoAtivo ? 'Ativo' : 'Inativo' ?></span>
                    </div>
                    <div class="schedule-fields">
                        <div>
                            <label class="form-label" for="periodoInicio<?= $periodoId ?>">Início</label>
                            <input type="time" class="form-control" id="periodoInicio<?= $periodoId ?>" name="periodos[<?= $periodoId ?>][hora_inicio]" value="<?= h($periodo['hora_inicio']) ?>">
                        </div>
                        <div>
                            <label class="form-label" for="periodoFim<?= $periodoId ?>">Fim</label>
                            <input type="time" class="form-control" id="periodoFim<?= $periodoId ?>" name="periodos[<?= $periodoId ?>][hora_fim]" value="<?= h($periodo['hora_fim']) ?>">
                        </div>
                        <div>
                            <label class="form-label" for="periodoAtivo<?= $periodoId ?>">Ativo</label>
                            <select class="form-select" id="periodoAtivo<?= $periodoId ?>" name="periodos[<?= $periodoId ?>][ativo]">
                                <option value="1" <?= $periodoAtivo ? 'selected' : '' ?>>Sim</option>
                                <option value="0" <?= !$periodoAtivo ? 'selected' : '' ?>>Não</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="periodoOrdem<?= $periodoId ?>">Ordem</label>
                            <input type="number" class="form-control" id="periodoOrdem<?= $periodoId ?>" name="periodos[<?= $periodoId ?>][ordem]" value="<?= h($periodo['ordem']) ?>">
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
        <div class="config-save-row">
            <button class="btn btn-primary btn-xl">Salvar períodos</button>
        </div>
    </form>

    <hr class="my-4">
    <form method="post" action="/?r=reservasTematicas/admin" class="row g-2 align-items-end schedule-add-form">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add_periodo">
        <div class="col-12 col-md-3 col-lg-2">
            <label class="form-label mb-1">Início</label>
            <input type="time" class="form-control" name="novo_periodo_inicio" required>
        </div>
        <div class="col-12 col-md-3 col-lg-2">
            <label class="form-label mb-1">Fim</label>
            <input type="time" class="form-control" name="novo_periodo_fim" required>
        </div>
        <div class="col-6 col-md-2 col-lg-2">
            <label class="form-label mb-1">Ordem</label>
            <input type="number" class="form-control" name="novo_periodo_ordem" value="0">
        </div>
        <div class="col-6 col-md-2 col-lg-2">
            <label class="form-label mb-1">Ativo</label>
            <select class="form-select" name="novo_periodo_ativo">
                <option value="1" selected>Sim</option>
                <option value="0">Não</option>
            </select>
        </div>
        <div class="col-12 col-md-2 col-lg-2">
            <button class="btn btn-outline-primary w-100">Adicionar período</button>
        </div>
    </form>
</div>

</div>
