<?php
$flash = $this->data['flash'] ?? null;
$restaurantes = $this->data['restaurantes'] ?? [];
$turnos = $this->data['turnos'] ?? [];
$periodos = $this->data['periodos'] ?? [];
$availability = $this->data['availability'] ?? [];
$filters = $this->data['filters'] ?? [];
$canReserve = $this->data['can_reserve'] ?? false;
$editItem = $this->data['edit_item'] ?? null;
$isHostess = $this->data['is_hostess'] ?? false;
$user = Auth::user();

$tagsPadrao = [
    'Cortesia',
    'Aniversário',
    'Cupcake',
    'Reclamação',
    'Atenção especial',
    'VIP',
    'Restrição alimentar',
];
$selectedTags = [];
if ($editItem && !empty($editItem['observacao_tags'])) {
    $selectedTags = array_map('trim', explode(',', $editItem['observacao_tags']));
}
$hasOptionalDetails = $editItem && (
    !empty($editItem['grupo_nome'])
    || !empty($editItem['chd_idades'])
    || !empty($editItem['observacao_reserva'])
    || !empty($selectedTags)
);
$availabilityDate = $filters['data'] ?? date('Y-m-d');
$quickDates = [
    ['label' => 'Hoje', 'date' => date('Y-m-d')],
    ['label' => 'Amanhã', 'date' => date('Y-m-d', strtotime('+1 day'))],
];
$availabilityTotals = [];
$dayTotalCapacidade = 0;
$dayTotalReservado = 0;
$dayTotalRestante = 0;
$dayClosedSlots = 0;
$dayTotalSlots = max(1, count($restaurantes) * count($turnos));
foreach ($restaurantes as $rest) {
    $restId = (int)$rest['id'];
    $totalCapacidade = 0;
    $totalReservado = 0;
    $totalRestante = 0;
    $fechado = false;
    foreach ($turnos as $turno) {
        $info = $availability[$restId][(int)$turno['id']] ?? ['capacidade' => 0, 'reservado' => 0, 'restante' => 0, 'fechado' => false];
        $totalCapacidade += (int)($info['capacidade'] ?? 0);
        $totalReservado += (int)($info['reservado'] ?? 0);
        $totalRestante += (int)($info['restante'] ?? 0);
        $fechado = $fechado || !empty($info['fechado']);
        $dayClosedSlots += !empty($info['fechado']) ? 1 : 0;
    }
    $dayTotalCapacidade += $totalCapacidade;
    $dayTotalReservado += $totalReservado;
    $dayTotalRestante += $totalRestante;
    $availabilityTotals[$restId] = [
        'capacidade' => $totalCapacidade,
        'reservado' => $totalReservado,
        'restante' => $totalRestante,
        'fechado' => $fechado,
        'percentual' => $totalCapacidade > 0 ? min(100, (int)round(($totalReservado / $totalCapacidade) * 100)) : 0,
    ];
}
$dayPercentual = $dayTotalCapacidade > 0 ? min(100, (int)round(($dayTotalReservado / $dayTotalCapacidade) * 100)) : 0;
?>

<div class="saas-page reservas-tematicas-page reserva-redesign">
<section class="reserva-hero mb-4">
    <div class="reserva-hero-main">
        <div class="section-title mb-0">
            <div class="icon"><i class="bi bi-calendar-heart"></i></div>
            <div>
                <div class="text-uppercase text-muted small">Reservas Temáticas</div>
                <h3 class="fw-bold mb-1">Mapa de reservas</h3>
                <div class="text-muted">Escolha a data, veja disponibilidade e cadastre sem sair do fluxo.</div>
            </div>
        </div>
        <div class="reserva-date-strip" aria-label="Datas rápidas">
            <?php foreach ($quickDates as $quick): ?>
                <?php $isActive = ($availabilityDate === $quick['date']); ?>
                <button
                    type="button"
                    class="reserva-date-pill <?= $isActive ? 'active' : '' ?> js-quick-date"
                    data-date="<?= h($quick['date']) ?>"
                >
                    <span><?= h($quick['label']) ?></span>
                    <strong><?= h(date('d/m', strtotime($quick['date']))) ?></strong>
                </button>
            <?php endforeach; ?>
            <div class="reserva-date-picker">
                <input type="date" class="form-control form-control-sm js-availability-date-input" id="availabilityDateInput" value="<?= h($availabilityDate) ?>">
                <button class="btn btn-outline-primary btn-sm js-availability-go" type="button" id="btnAvailabilityGo" aria-label="Carregar data"><i class="bi bi-arrow-right"></i></button>
            </div>
        </div>
        <div class="reserva-flow-steps" aria-label="Fluxo sugerido">
            <div class="reserva-flow-step active" data-flow-step="date">
                <span>1</span>
                <strong>Escolha a data</strong>
            </div>
            <div class="reserva-flow-step" data-flow-step="slot">
                <span>2</span>
                <strong>Selecione o turno</strong>
            </div>
            <div class="reserva-flow-step" data-flow-step="guest">
                <span>3</span>
                <strong>Cadastre a UH</strong>
            </div>
        </div>
    </div>

    <div class="reserva-hero-side">
        <div class="reserva-status-card <?= ($isHostess && !$canReserve) ? 'blocked' : 'open' ?>">
            <div class="text-uppercase small"><?= ($isHostess && !$canReserve) ? 'Criação bloqueada' : 'Criação ativa' ?></div>
            <strong><?= h(date('d/m/Y', strtotime($availabilityDate))) ?></strong>
            <span class="js-availability-date-label">Data em análise</span>
        </div>
        <?php if (!empty($periodos)): ?>
            <div class="reserva-window-card">
                <i class="bi bi-clock-history"></i>
                <div>
                    <div class="text-uppercase small">Janela de reserva</div>
                    <span>
                        <?php foreach ($periodos as $idx => $p): ?><?= $idx > 0 ? ' · ' : '' ?><?= h(substr((string)$p['hora_inicio'], 0, 5)) ?>-<?= h(substr((string)$p['hora_fim'], 0, 5)) ?><?php endforeach; ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($isHostess && !$canReserve): ?>
        <script type="application/json" data-app-alert="1"><?= json_for_html([
            'type' => 'warning',
            'message' => 'Fora do horário permitido para reservas. A criação está bloqueada para hostess.',
            'modal' => true,
            'buttonText' => 'Entendi',
        ]) ?></script>
    <?php endif; ?>
</section>

<style>
.reserva-redesign {
    --reservation-muted-bg: color-mix(in srgb, var(--ab-soft-bg) 78%, var(--ab-card) 22%);
}

.reserva-hero,
.reserva-planner,
.reservation-compose-card {
    border: 1px solid var(--ab-border);
    border-radius: 18px;
    background: color-mix(in srgb, var(--ab-card) 94%, var(--ab-soft-bg) 6%);
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.07);
    min-width: 0;
}

.reserva-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(280px, 0.42fr);
    gap: 1rem;
    padding: 1.1rem;
}

.reserva-hero-main,
.reserva-hero-side {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.reserva-date-strip {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    flex-wrap: wrap;
}

.reserva-date-pill {
    border: 1px solid var(--ab-border);
    border-radius: 14px;
    background: var(--reservation-muted-bg);
    color: var(--ab-ink);
    min-width: 92px;
    min-height: 54px;
    padding: 0.45rem 0.7rem;
    display: inline-flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
    transition: .16s ease;
}

.reserva-date-pill span {
    color: var(--ab-muted);
    font-size: 0.72rem;
    text-transform: uppercase;
    font-weight: 800;
}

.reserva-date-pill strong {
    font-size: 1.02rem;
}

.reserva-date-pill.active,
.reserva-date-pill:hover {
    border-color: color-mix(in srgb, var(--ab-accent) 70%, var(--ab-border) 30%);
    background: color-mix(in srgb, var(--ab-accent) 14%, var(--ab-card) 86%);
    transform: translateY(-1px);
}

.reserva-date-picker {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    min-width: min(100%, 228px);
}

.reserva-date-picker .form-control {
    min-height: 42px;
}

.reserva-flow-steps {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.5rem;
}

.reserva-flow-step {
    border: 1px solid var(--ab-border);
    border-radius: 14px;
    background: var(--reservation-muted-bg);
    padding: 0.58rem 0.62rem;
    display: flex;
    align-items: center;
    gap: 0.52rem;
    min-width: 0;
}

.reserva-flow-step span {
    width: 28px;
    height: 28px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    background: color-mix(in srgb, var(--ab-accent) 13%, var(--ab-card) 87%);
    color: var(--ab-accent);
    font-weight: 850;
}

.reserva-flow-step strong {
    min-width: 0;
    color: var(--ab-ink);
    font-size: 0.82rem;
    line-height: 1.15;
}

.reserva-flow-step.active {
    border-color: color-mix(in srgb, var(--ab-accent) 42%, var(--ab-border) 58%);
    background: color-mix(in srgb, var(--ab-accent) 10%, var(--ab-card) 90%);
}

.reserva-flow-step.completed {
    border-color: color-mix(in srgb, #16a34a 42%, var(--ab-border) 58%);
    background: color-mix(in srgb, #16a34a 10%, var(--ab-card) 90%);
}

.reserva-flow-step.completed span {
    background: color-mix(in srgb, #16a34a 18%, var(--ab-card) 82%);
    color: #15803d;
}

.reserva-status-card,
.reserva-window-card {
    border-radius: 16px;
    border: 1px solid var(--ab-border);
    background: var(--reservation-muted-bg);
    padding: 0.85rem;
}

.reserva-status-card {
    border-left: 5px solid #16a34a;
}

.reserva-status-card.blocked {
    border-left-color: #dc2626;
}

.reserva-status-card .small,
.reserva-window-card .small {
    color: var(--ab-muted);
    font-weight: 800;
    letter-spacing: 0.04em;
}

.reserva-status-card strong {
    display: block;
    font-size: 1.15rem;
}

.reserva-status-card span,
.reserva-window-card span {
    color: var(--ab-muted);
    font-size: 0.88rem;
}

.reserva-window-card {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.reserva-window-card i {
    width: 36px;
    height: 36px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: color-mix(in srgb, var(--ab-accent) 16%, transparent);
    color: var(--ab-accent);
}

.reserva-planner {
    padding: 1rem;
}

.reserva-planner-head {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    align-items: flex-start;
    margin-bottom: 0.85rem;
}

.reserva-planner-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: flex-end;
}

.reserva-board {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.85rem;
}

.reserva-day-overview {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.65rem;
    margin-bottom: 0.9rem;
}

.reserva-day-metric {
    border: 1px solid color-mix(in srgb, var(--ab-border) 82%, transparent);
    border-radius: 14px;
    background: color-mix(in srgb, var(--ab-card) 86%, var(--ab-soft-bg) 14%);
    padding: 0.68rem 0.75rem;
    min-width: 0;
}

.reserva-day-metric > span {
    display: block;
    color: var(--ab-muted);
    font-size: 0.68rem;
    font-weight: 850;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.reserva-day-metric strong {
    display: block;
    margin-top: 0.18rem;
    color: var(--ab-ink);
    font-size: 1.04rem;
    line-height: 1.15;
}

.reserva-day-metric strong span {
    display: inline;
}

.reserva-day-meter {
    grid-column: 1 / -1;
    height: 9px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--ab-border) 62%, transparent);
    overflow: hidden;
}

.reserva-day-meter span {
    display: block;
    height: 100%;
    width: var(--reservation-day-progress, 0%);
    background: linear-gradient(90deg, #16a34a, #f97316);
}

.reserva-rest-card {
    border: 1px solid var(--ab-border);
    border-radius: 16px;
    background: var(--reservation-muted-bg);
    padding: 0.85rem;
    min-width: 0;
    overflow: hidden;
}

.reserva-rest-head {
    display: flex;
    justify-content: space-between;
    gap: 0.65rem;
    align-items: center;
    margin-bottom: 0.7rem;
}

.reserva-rest-progress {
    height: 8px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--ab-border) 58%, transparent);
    overflow: hidden;
    margin: 0.65rem 0;
}

.reserva-rest-progress span {
    display: block;
    height: 100%;
    width: var(--reservation-progress, 0%);
    background: linear-gradient(90deg, #16a34a, #f97316);
}

.reserva-turnos-inline {
    display: grid;
    gap: 0.5rem;
}

.reserva-turno-chip {
    border: 1px solid var(--ab-border);
    border-radius: 12px;
    background: color-mix(in srgb, var(--ab-card) 88%, transparent);
    padding: 0.52rem 0.6rem;
    display: grid;
    gap: 0.42rem;
    align-items: stretch;
}

.reserva-turno-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.reserva-turno-chip.is-closed {
    opacity: 0.72;
}

.reserva-turno-chip.is-full:not(.is-closed) {
    border-color: color-mix(in srgb, #dc2626 38%, var(--ab-border) 62%);
    background: color-mix(in srgb, #dc2626 7%, var(--ab-card) 93%);
}

.reserva-turno-chip.is-full .reserva-turno-action,
.reserva-turno-chip.is-closed .reserva-turno-action {
    color: var(--ab-muted);
    cursor: not-allowed;
}

.reserva-turno-chip.is-selected,
.availability-turno-tile.is-selected {
    border-color: color-mix(in srgb, var(--ab-accent) 62%, var(--ab-border) 38%);
    background: color-mix(in srgb, var(--ab-accent) 11%, var(--ab-card) 89%);
    box-shadow: 0 12px 26px color-mix(in srgb, var(--ab-accent) 18%, transparent);
}

.reserva-turno-hour {
    font-weight: 850;
    font-size: 0.98rem;
    line-height: 1;
    color: var(--ab-ink);
    white-space: nowrap;
}

.reserva-turno-meta {
    color: var(--ab-muted);
    font-size: 0.74rem;
    min-width: 0;
    display: grid;
    gap: 0.14rem;
}

.reserva-turno-meta strong {
    display: block;
    color: var(--ab-ink);
    font-size: 0.82rem;
    line-height: 1.1;
}

.reserva-turno-action {
    border: 1px solid color-mix(in srgb, var(--ab-accent) 34%, var(--ab-border) 66%);
    border-radius: 10px;
    background: color-mix(in srgb, var(--ab-accent) 8%, var(--ab-card) 92%);
    color: var(--ab-accent);
    font-weight: 800;
    padding: 0.34rem 0.6rem;
    min-height: 32px;
    white-space: nowrap;
    flex: 0 0 auto;
}

.reservation-workspace {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
    align-items: start;
}

.reservation-compose-card {
    padding: 1rem;
}

.reservation-sticky-panel {
    position: static;
}

.selected-slot-preview {
    border: 1px solid color-mix(in srgb, var(--ab-accent) 25%, var(--ab-border) 75%);
    border-radius: 16px;
    background: color-mix(in srgb, var(--ab-accent) 8%, var(--ab-card) 92%);
    padding: 0.72rem;
    display: grid;
    grid-template-columns: 36px minmax(0, 1fr);
    gap: 0.66rem;
    align-items: center;
    margin-bottom: 0.85rem;
}

@media (min-width: 1701px) {
    .reserva-board {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .reservation-workspace {
        grid-template-columns: minmax(0, 0.82fr) minmax(320px, 0.44fr);
    }

    .reservation-sticky-panel {
        position: sticky;
        top: 6.7rem;
    }
}

.selected-slot-preview i {
    width: 36px;
    height: 36px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--ab-accent);
    background: color-mix(in srgb, var(--ab-accent) 14%, var(--ab-card) 86%);
}

.selected-slot-preview .slot-kicker {
    color: var(--ab-muted);
    font-size: 0.68rem;
    font-weight: 850;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.selected-slot-preview .slot-title {
    color: var(--ab-ink);
    font-weight: 850;
    line-height: 1.18;
}

.selected-slot-preview .slot-meta {
    color: var(--ab-muted);
    font-size: 0.8rem;
}

.form-step-label {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    color: var(--ab-muted);
    font-weight: 800;
    text-transform: uppercase;
    font-size: 0.72rem;
    letter-spacing: 0.04em;
}

.form-step-label span {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: color-mix(in srgb, var(--ab-accent) 18%, transparent);
    color: var(--ab-accent);
}

.mode-switch {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.5rem;
    padding: 0.35rem;
    border: 1px solid var(--ab-border);
    border-radius: 16px;
    background: var(--reservation-muted-bg);
}

.mode-switch .btn {
    border-radius: 12px !important;
}

.reservation-compose-card .form-label {
    color: var(--ab-ink);
    font-size: 0.82rem;
    font-weight: 760;
}

.reservation-compose-card .form-control,
.reservation-compose-card .form-select {
    border-color: color-mix(in srgb, var(--ab-border) 78%, transparent);
    background: color-mix(in srgb, var(--ab-card) 96%, var(--ab-soft-bg) 4%);
}

.reservation-compose-card .form-control:focus,
.reservation-compose-card .form-select:focus {
    border-color: color-mix(in srgb, var(--ab-accent) 58%, var(--ab-border) 42%);
    box-shadow: 0 0 0 0.18rem color-mix(in srgb, var(--ab-accent) 16%, transparent);
}

.reservation-person-panel {
    border-left: 3px solid color-mix(in srgb, var(--ab-accent) 62%, var(--ab-border) 38%);
    padding-left: 0.85rem;
}

.reservation-person-panel .mb-3:last-child {
    margin-bottom: 0 !important;
}

.reservation-form-divider {
    height: 1px;
    margin: 0.85rem 0;
    background: color-mix(in srgb, var(--ab-border) 72%, transparent);
}

.reservation-optional-panel {
    border: 1px solid color-mix(in srgb, var(--ab-border) 78%, transparent);
    border-radius: 16px;
    background: color-mix(in srgb, var(--ab-soft-bg) 66%, var(--ab-card) 34%);
    margin-top: 0.85rem;
    margin-bottom: 0.85rem;
    overflow: hidden;
}

.reservation-optional-panel summary {
    list-style: none;
    min-height: 48px;
    padding: 0.72rem 0.85rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    cursor: pointer;
    color: var(--ab-ink);
    font-weight: 820;
}

.reservation-optional-panel summary::-webkit-details-marker {
    display: none;
}

.reservation-optional-panel summary::after {
    content: "+";
    width: 28px;
    height: 28px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    background: color-mix(in srgb, var(--ab-accent) 13%, var(--ab-card) 87%);
    color: var(--ab-accent);
    font-size: 1.15rem;
    line-height: 1;
}

.reservation-optional-panel[open] summary::after {
    content: "-";
}

.reservation-optional-body {
    padding: 0 0.85rem 0.85rem;
}

.tag-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
}

.tag-choice {
    position: relative;
}

.tag-choice input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.tag-choice label {
    display: inline-flex;
    align-items: center;
    gap: 0.38rem;
    min-height: 34px;
    border: 1px solid color-mix(in srgb, var(--ab-border) 78%, transparent);
    border-radius: 999px;
    padding: 0.36rem 0.62rem;
    background: color-mix(in srgb, var(--ab-card) 92%, var(--ab-soft-bg) 8%);
    color: var(--ab-muted);
    cursor: pointer;
    font-size: 0.78rem;
    font-weight: 720;
    transition: 0.16s ease;
}

.tag-choice input:checked + label {
    border-color: color-mix(in srgb, var(--ab-accent) 58%, var(--ab-border) 42%);
    background: color-mix(in srgb, var(--ab-accent) 13%, var(--ab-card) 87%);
    color: var(--ab-ink);
}

.tag-choice label:hover {
    transform: translateY(-1px);
}

.reservation-batch-panel {
    border: 1px solid color-mix(in srgb, var(--ab-border) 78%, transparent) !important;
    border-radius: 16px;
    background: color-mix(in srgb, var(--ab-soft-bg) 70%, var(--ab-card) 30%) !important;
    box-shadow: none;
}

.reservation-submit-bar {
    display: grid;
    gap: 0.5rem;
    margin-top: 0.85rem;
}

.reservation-submit-bar .btn {
    min-height: 48px;
}

.availability-modal-content {
    border: 0;
    border-radius: 22px;
    overflow: hidden;
    box-shadow: 0 24px 80px rgba(15, 23, 42, 0.28);
}

.availability-modal-content .modal-header {
    border-bottom: 1px solid color-mix(in srgb, var(--ab-border) 68%, transparent);
    background: color-mix(in srgb, var(--ab-accent) 8%, var(--ab-card) 92%);
}

.availability-vertical-grid {
    display: grid;
    gap: 1rem;
}

.availability-restaurant-card {
    border: 1px solid var(--border-soft, rgba(15, 23, 42, 0.08));
    border-radius: 1rem;
    padding: 0.95rem;
    background: var(--card-surface, rgba(255, 255, 255, 0.75));
}

.availability-turnos-grid {
    display: grid;
    gap: 0.65rem;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    margin-top: 0.7rem;
}

.availability-turno-tile {
    border: 1px solid var(--border-soft, rgba(15, 23, 42, 0.08));
    border-radius: 0.85rem;
    padding: 0.6rem;
    background: var(--surface-muted, rgba(248, 250, 252, 0.85));
    text-align: center;
}

.availability-turno-time {
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    color: var(--text-muted, #6b7280);
}

.availability-turno-values {
    margin-top: 0.45rem;
}

.availability-turno-values .badge {
    font-size: 0.95rem;
    min-width: 42px;
}

.availability-turno-ratio {
    margin-top: 0.35rem;
    font-size: 0.8rem;
    color: var(--text-muted, #6b7280);
}

.availability-detail-list {
    display: grid;
    gap: 0.65rem;
    max-height: min(52vh, 520px);
    overflow-y: auto;
    padding-right: 0.15rem;
}

.availability-detail-item {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 0.65rem;
    align-items: center;
    border: 1px solid color-mix(in srgb, var(--ab-border) 80%, transparent);
    border-radius: 16px;
    padding: 0.78rem;
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--ab-accent) 4%, transparent), transparent 62%),
        color-mix(in srgb, var(--ab-card) 96%, var(--ab-soft-bg) 4%);
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
}

.reserva-planner,
.reservation-compose-card,
.selected-slot-preview,
.batch-row-wrap,
.availability-detail-list,
.availability-detail-item {
    min-width: 0;
}

.availability-detail-title {
    font-weight: 850;
    color: var(--ab-ink);
    line-height: 1.18;
}

.availability-detail-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.42rem;
    margin-top: 0.48rem;
}

.availability-detail-meta .detail-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.28rem;
    border-radius: 999px;
    border: 1px solid color-mix(in srgb, var(--ab-border) 54%, transparent);
    background: color-mix(in srgb, var(--ab-soft-bg) 76%, var(--ab-card) 24%);
    color: var(--ab-ink);
    font-size: 0.74rem;
    font-weight: 820;
    line-height: 1;
    padding: 0.36rem 0.58rem;
    white-space: nowrap;
}

.availability-detail-meta .detail-badge::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 999px;
    background: currentColor;
    opacity: 0.65;
}

.availability-detail-meta .detail-badge.is-uh {
    color: #0f766e;
    background: color-mix(in srgb, #ccfbf1 74%, var(--ab-card) 26%);
    border-color: color-mix(in srgb, #0f766e 22%, transparent);
}

.availability-detail-meta .detail-badge.is-pax {
    color: #1d4ed8;
    background: color-mix(in srgb, #dbeafe 76%, var(--ab-card) 24%);
    border-color: color-mix(in srgb, #1d4ed8 20%, transparent);
}

.availability-detail-meta .detail-badge.is-chd {
    color: #7c3aed;
    background: color-mix(in srgb, #ede9fe 78%, var(--ab-card) 22%);
    border-color: color-mix(in srgb, #7c3aed 18%, transparent);
}

.availability-detail-meta .detail-badge.is-status {
    color: #c2410c;
    background: color-mix(in srgb, #ffedd5 80%, var(--ab-card) 20%);
    border-color: color-mix(in srgb, #f97316 24%, transparent);
}

.availability-detail-meta .detail-badge.is-user {
    color: #475569;
    background: color-mix(in srgb, #f1f5f9 86%, var(--ab-card) 14%);
    border-color: color-mix(in srgb, #64748b 18%, transparent);
}

.availability-detail-action {
    min-width: 72px;
    border-radius: 999px;
    font-weight: 760;
}

.availability-detail-empty {
    border: 1px dashed color-mix(in srgb, var(--ab-border) 86%, transparent);
    border-radius: 14px;
    padding: 1rem;
    color: var(--ab-muted);
    text-align: center;
    background: color-mix(in srgb, var(--ab-soft-bg) 62%, transparent);
}

html[data-theme='dark'] .availability-detail-item {
    background: rgba(15, 23, 42, 0.58);
    box-shadow: none;
}

html[data-theme='dark'] .availability-detail-meta .detail-badge {
    background: rgba(30, 41, 59, 0.78);
    border-color: rgba(148, 163, 184, 0.18);
    color: #e2e8f0;
}

.batch-rows {
    display: grid;
    gap: 0.55rem;
}

.batch-row-wrap {
    border: 1px solid var(--ab-border);
    border-radius: 0.8rem;
    padding: 0.55rem;
    overflow-x: auto;
    background: color-mix(in srgb, var(--ab-card) 86%, transparent);
}

.batch-row-grid {
    display: grid;
    grid-template-columns: 92px 110px minmax(140px, 1fr) 44px;
    gap: 0.5rem;
    align-items: end;
    min-width: 420px;
}

.batch-row-grid .form-label {
    font-size: 0.76rem;
    margin-bottom: 0.2rem;
}

.batch-row-grid .js-remove-batch-row {
    min-height: 38px;
    border-radius: 12px;
}

.batch-hint {
    font-size: 0.82rem;
    color: var(--text-muted, #6b7280);
}

@media (max-width: 1400px) {
    .reserva-hero,
    .reservation-workspace {
        grid-template-columns: 1fr;
    }

    .reserva-hero-side {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .reserva-planner-head {
        flex-direction: column;
    }

    .reserva-planner-actions {
        width: 100%;
        justify-content: stretch;
    }

    .reserva-planner-actions .btn {
        flex: 1 1 200px;
    }

    .reserva-day-overview {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .reserva-turnos-inline {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .selected-slot-preview {
        grid-template-columns: 36px minmax(0, 1fr);
    }

    .reserva-planner-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .reserva-planner-actions .btn {
        width: 100%;
    }
}

@media (max-width: 991px) {
    .reserva-hero,
    .reservation-workspace {
        grid-template-columns: 1fr;
    }

    .reserva-hero-side {
        grid-template-columns: 1fr;
    }

    .reserva-flow-steps {
        grid-template-columns: 1fr;
    }

    .reservation-sticky-panel {
        position: static;
    }

    .reserva-board {
        grid-template-columns: 1fr;
    }

    .reserva-day-overview {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .reserva-planner-head {
        flex-direction: column;
    }

    .reserva-planner-actions {
        width: 100%;
        justify-content: stretch;
    }

    .reserva-planner-actions .btn {
        flex: 1 1 150px;
    }

    .reserva-turno-chip {
        gap: 0.36rem;
    }

    .reserva-turno-action {
        justify-self: auto;
    }

    .batch-row-grid {
        min-width: 100%;
        grid-template-columns: 1fr 1fr;
    }

    .batch-row-grid > div:last-child {
        grid-column: span 2;
    }

    .reservation-submit-bar {
        position: sticky;
        bottom: 0.55rem;
        z-index: 12;
        margin-left: -0.2rem;
        margin-right: -0.2rem;
        padding: 0.55rem;
        border: 1px solid color-mix(in srgb, var(--ab-border) 70%, transparent);
        border-radius: 16px;
        background: color-mix(in srgb, var(--ab-card) 94%, var(--ab-soft-bg) 6%);
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.18);
    }
}

@media (max-width: 576px) {
    .reserva-hero {
        gap: 0.75rem;
        padding: 0.85rem;
        margin-bottom: 0.85rem !important;
    }

    .reserva-hero .section-title {
        align-items: flex-start;
        gap: 0.55rem;
    }

    .reserva-hero .section-title .icon {
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
    }

    .reserva-hero h3 {
        font-size: 1.08rem;
        line-height: 1.18;
        margin-bottom: 0 !important;
    }

    .reserva-hero-main > .section-title .text-muted:not(.small) {
        display: none;
    }

    .reserva-date-strip {
        gap: 0.45rem;
    }

    .reserva-date-pill {
        flex: 1 1 calc(50% - 0.45rem);
        min-width: 0;
        padding: 0.52rem;
    }

    .reserva-date-picker {
        flex: 1 1 100%;
    }

    .reserva-flow-steps {
        display: none;
    }

    .reserva-hero-side {
        display: flex;
        gap: 0.5rem;
    }

    .reserva-status-card,
    .reserva-window-card {
        padding: 0.65rem;
    }

    .reserva-planner {
        padding: 0.85rem;
    }

    .reserva-planner-head {
        gap: 0.75rem;
    }

    .reserva-planner-head h5,
    .reservation-compose-card h5 {
        font-size: 1rem;
    }

    .reserva-planner-actions {
        grid-template-columns: 1fr;
    }

    .reserva-rest-card {
        padding: 0.72rem;
    }

    .reserva-day-overview {
        grid-template-columns: 1fr;
    }

    .reserva-turnos-inline {
        grid-template-columns: 1fr;
        gap: 0.45rem;
    }

    .reserva-turno-chip {
        grid-template-columns: 1fr;
        gap: 0.28rem;
        padding: 0.52rem;
    }

    .reserva-turno-top {
        align-items: flex-start;
        flex-direction: column;
    }

    .reserva-turno-hour {
        font-size: 0.98rem;
    }

    .reserva-turno-meta {
        font-size: 0.72rem;
    }

    .reserva-turno-meta strong {
        display: block;
        line-height: 1.12;
    }

    .reserva-turno-action {
        width: 100%;
        min-height: 30px;
        font-size: 0.78rem;
    }

    .tag-grid {
        display: grid;
        grid-template-columns: 1fr;
    }

    .tag-choice label {
        justify-content: center;
        width: 100%;
    }

    .availability-modal-content {
        border-radius: 18px;
    }

    .availability-modal-content .modal-header {
        padding: 0.75rem 0.9rem;
    }

    .availability-modal-content .modal-body {
        padding: 0.8rem;
    }

    .availability-modal-content .modal-footer {
        position: sticky;
        bottom: 0;
        z-index: 2;
        padding: 0.7rem;
        background: color-mix(in srgb, var(--ab-card) 94%, var(--ab-soft-bg) 6%);
    }

    .availability-modal-content .modal-footer .btn {
        width: 100%;
    }

    .availability-detail-item {
        grid-template-columns: 1fr;
        align-items: stretch;
        gap: 0.7rem;
    }

    .availability-detail-action {
        width: 100%;
        justify-content: center;
    }

    .reservation-person-panel {
        border-left: 0;
        padding-left: 0;
    }

    .reservation-compose-card .row.g-2 > [class*="col-"] {
        min-width: 0;
    }

    .batch-row-grid {
        grid-template-columns: 1fr;
    }

    .batch-row-grid > div:last-child {
        grid-column: auto;
    }
}
</style>

<div class="reservation-workspace mb-4">
    <section class="reserva-planner">
        <div class="reserva-planner-head">
            <div>
                <div class="text-uppercase text-muted small">Capacidade e escolha rápida</div>
                <h5 class="fw-bold mb-1">Disponibilidade por restaurante</h5>
                <div class="text-muted small">Clique em um turno para carregar restaurante/horário no cadastro.</div>
            </div>
            <div class="reserva-planner-actions">
                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#availabilityModal">
                    <i class="bi bi-arrows-fullscreen me-1"></i>Ver detalhes
                </button>
                <button type="button" class="btn btn-primary btn-sm js-scroll-compose">
                    <i class="bi bi-pencil-square me-1"></i>Ir para cadastro
                </button>
            </div>
        </div>

        <div class="reserva-day-overview">
            <div class="reserva-day-metric">
                <span>Disponíveis</span>
                <strong><span class="js-day-restante"><?= (int)$dayTotalRestante ?></span></strong>
            </div>
            <div class="reserva-day-metric">
                <span>Reservados</span>
                <strong><span class="js-day-reservado"><?= (int)$dayTotalReservado ?></span></strong>
            </div>
            <div class="reserva-day-metric">
                <span>Capacidade</span>
                <strong><span class="js-day-capacidade"><?= (int)$dayTotalCapacidade ?></span></strong>
            </div>
            <div class="reserva-day-metric">
                <span>Fechados</span>
                <strong><span class="js-day-fechados"><?= (int)$dayClosedSlots ?></span>/<span class="js-day-turnos"><?= (int)$dayTotalSlots ?></span></strong>
            </div>
            <div class="reserva-day-meter" style="--reservation-day-progress: <?= (int)$dayPercentual ?>%;">
                <span class="js-day-progress"></span>
            </div>
        </div>

        <div class="reserva-board">
            <?php foreach ($restaurantes as $rest): ?>
                <?php
                    $restId = (int)$rest['id'];
                    $totais = $availabilityTotals[$restId] ?? ['capacidade' => 0, 'reservado' => 0, 'restante' => 0, 'fechado' => false, 'percentual' => 0];
                ?>
                <article class="reserva-rest-card js-rest-card" data-rest-id="<?= $restId ?>">
                    <div class="reserva-rest-head">
                        <span class="tag <?= restaurant_badge_class($rest['nome']) ?>"><?= h($rest['nome']) ?></span>
                        <span class="badge <?= ((int)$totais['restante'] > 0) ? 'badge-success' : 'badge-danger' ?> js-rest-total-badge">
                            <?= !empty($totais['fechado']) && (int)$totais['capacidade'] === 0 ? 'Fechado' : (int)$totais['restante'] . ' disp.' ?>
                        </span>
                    </div>
                    <div class="reserva-rest-progress js-rest-progress" style="--reservation-progress: <?= (int)$totais['percentual'] ?>%;">
                        <span></span>
                    </div>
                    <div class="d-flex justify-content-between text-muted small mb-2">
                        <span><span class="js-rest-reservado"><?= (int)$totais['reservado'] ?></span> reservados</span>
                        <span><span class="js-rest-capacidade"><?= (int)$totais['capacidade'] ?></span> lugares</span>
                    </div>
                    <div class="reserva-turnos-inline">
                        <?php foreach ($turnos as $turno): ?>
                            <?php
                                $info = $availability[$restId][(int)$turno['id']] ?? ['capacidade' => 0, 'reservado' => 0, 'restante' => 0, 'fechado' => false];
                                $fechado = !empty($info['fechado']);
                                $restante = (int)($info['restante'] ?? 0);
                                $capacidade = (int)($info['capacidade'] ?? 0);
                                $reservado = (int)($info['reservado'] ?? 0);
                            ?>
                            <div
                                class="reserva-turno-chip js-availability-cell <?= $fechado ? 'is-closed' : '' ?> <?= (!$fechado && $capacidade > 0 && $restante <= 0) ? 'is-full' : '' ?>"
                                data-rest-id="<?= $restId ?>"
                                data-turno-id="<?= (int)$turno['id'] ?>"
                                data-rest-nome="<?= h($rest['nome']) ?>"
                                data-turno-hora="<?= h($turno['hora']) ?>"
                                data-restante="<?= $restante ?>"
                                data-reservado="<?= $reservado ?>"
                                data-capacidade="<?= $capacidade ?>"
                                data-fechado="<?= $fechado ? '1' : '0' ?>"
                            >
                                <div class="reserva-turno-top">
                                    <div class="reserva-turno-hour"><?= h(substr((string)$turno['hora'], 0, 5)) ?></div>
                                    <button type="button" class="reserva-turno-action js-pick-slot" data-rest-id="<?= $restId ?>" data-turno-id="<?= (int)$turno['id'] ?>" <?= ($fechado || ($capacidade > 0 && $restante <= 0)) ? 'disabled' : '' ?>>
                                        <?= (!$fechado && $capacidade > 0 && $restante <= 0) ? 'Lotado' : 'Selecionar' ?>
                                    </button>
                                </div>
                                <div class="reserva-turno-meta">
                                    <strong class="js-availability-restante"><?= $fechado ? 'Fechado' : $restante . ' livres' ?></strong>
                                    <span class="d-block js-availability-rc"><span class="js-availability-reservado"><?= $reservado ?></span><?= $fechado ? ' reservas' : '/' . $capacidade . ' ocupados' ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="reservation-compose-card reservation-sticky-panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="text-uppercase text-muted small"><?= $editItem ? 'Editar reserva' : 'Nova reserva' ?></div>
                    <h5 class="fw-bold mb-0">Cadastro assistido</h5>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <?php if ($isHostess && !$canReserve): ?>
                        <span class="badge badge-danger">Inativo</span>
                    <?php else: ?>
                        <span class="badge badge-success">Ativo</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="selected-slot-preview" id="selectedSlotPreview">
                <i class="bi bi-calendar2-check"></i>
                <div>
                    <div class="slot-kicker">Seleção atual</div>
                    <div class="slot-title" id="selectedSlotTitle">Escolha um restaurante e turno</div>
                    <div class="slot-meta" id="selectedSlotMeta">Clique em um turno disponível no mapa para preencher automaticamente.</div>
                </div>
            </div>

            <form method="post" action="/?r=reservasTematicas/reservas" data-no-lock="1" data-ajax-alerts="1">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" id="reservaActionInput" value="<?= $editItem ? 'update' : 'create' ?>">
                <?php if ($editItem): ?>
                    <input type="hidden" name="id" value="<?= (int)$editItem['id'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label form-step-label"><span>1</span>Data da reserva</label>
                    <input type="date" class="form-control input-xl" name="data_reserva" value="<?= h($editItem['data_reserva'] ?? $filters['data'] ?? date('Y-m-d')) ?>" required>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label form-step-label"><span>2</span>Restaurante</label>
                        <select class="form-select input-xl" name="restaurante_id" required>
                            <option value="">Selecione</option>
                            <?php foreach ($restaurantes as $rest): ?>
                                <option value="<?= (int)$rest['id'] ?>" <?= ($editItem['restaurante_id'] ?? '') == $rest['id'] ? 'selected' : '' ?>>
                                    <?= h($rest['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label form-step-label"><span>3</span>Turno</label>
                        <select class="form-select input-xl" name="turno_id" required>
                            <option value="">Selecione</option>
                            <?php foreach ($turnos as $turno): ?>
                                <option value="<?= (int)$turno['id'] ?>" <?= ($editItem['turno_id'] ?? '') == $turno['id'] ? 'selected' : '' ?>>
                                    <?= h($turno['hora']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <?php if (!$editItem): ?>
                    <div class="mb-3">
                        <label class="form-label form-step-label"><span>4</span>Formato da reserva</label>
                        <div class="mode-switch" role="group" aria-label="Tipo de reserva">
                            <button type="button" class="btn btn-primary" id="btnModeSingle"><i class="bi bi-person me-1"></i>Individual</button>
                            <button type="button" class="btn btn-outline-primary" id="btnModeBatch"><i class="bi bi-people me-1"></i>Grupo</button>
                        </div>
                    </div>
                <?php endif; ?>

                <div id="singleReservationPanel" class="reservation-person-panel">
                    <div class="mb-3">
                        <label class="form-label">UH</label>
                        <input type="text" class="form-control input-xl" name="uh_numero" inputmode="numeric" value="<?= h($editItem['uh_numero'] ?? '') ?>" placeholder="Ex: 402" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Titular da reserva</label>
                        <input type="text" class="form-control input-xl" name="titular_nome" value="<?= h($editItem['titular_nome'] ?? '') ?>" placeholder="Nome e sobrenome" required>
                    </div>

                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label">PAX</label>
                            <input type="number" class="form-control input-xl text-center" min="1" name="pax" value="<?= h($editItem['pax'] ?? 1) ?>" required>
                        </div>
                    </div>

                    <details class="reservation-optional-panel" <?= $hasOptionalDetails ? 'open' : '' ?>>
                        <summary>
                            <span>Detalhes opcionais</span>
                            <small class="text-muted fw-semibold">grupo, CHD, marcadores e observações</small>
                        </summary>
                        <div class="reservation-optional-body">
                            <div class="mb-3">
                                <label class="form-label">Grupo (opcional)</label>
                                <input type="text" class="form-control input-xl" name="grupo_nome" value="<?= h($editItem['grupo_nome'] ?? '') ?>" maxlength="120" placeholder="Ex: Famtour ABAV, Família Silva, Evento XYZ">
                                <div class="text-muted small mt-1">Use para identificar grupos comerciais/famílias, separado do cadastro individual.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Idades CHD (opcional)</label>
                                <input type="text" class="form-control input-xl" name="chd_idades" value="<?= h($editItem['chd_idades'] ?? '') ?>" placeholder="Ex: 3y7y">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Marcadores rápidos</label>
                                <div class="tag-grid">
                                    <?php foreach ($tagsPadrao as $tag): ?>
                                        <?php $tagId = 'tag_' . md5($tag); ?>
                                        <div class="tag-choice">
                                            <input type="checkbox" id="<?= h($tagId) ?>" name="observacao_tags[]" value="<?= h($tag) ?>" <?= in_array($tag, $selectedTags, true) ? 'checked' : '' ?>>
                                            <label for="<?= h($tagId) ?>"><i class="bi bi-bookmark-star"></i><?= h($tag) ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="mb-0">
                                <label class="form-label">Observações</label>
                                <textarea class="form-control" name="observacao_reserva" rows="3" placeholder="Observações gerais..."><?= h($editItem['observacao_reserva'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </details>
                </div>

                <?php if (!$editItem): ?>
                    <div id="batchReservationPanel" class="card reservation-batch-panel p-3 mb-3 d-none">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                            <div>
                                <div class="text-uppercase text-muted small">Reserva em grupo</div>
                                <div class="fw-semibold">Múltiplas UHs vinculadas a um titular</div>
                            </div>
                            <span class="badge badge-soft">Grupo</span>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Titular</label>
                            <input type="text" class="form-control input-xl" name="grupo_responsavel" id="batchDefaultTitular" placeholder="Nome do titular do grupo" required>
                            <div class="batch-hint mt-1">Esse titular será usado em todas as UHs do grupo.</div>
                        </div>
                        <div id="batchContainer">
                            <div class="batch-hint mb-2">UHs, PAX e CHD vinculados ao mesmo titular.</div>
                            <div id="batchRows" class="batch-rows"></div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="btnAddBatchRow">
                                <i class="bi bi-plus-circle me-1"></i>Adicionar UH
                            </button>
                        </div>
                        <details class="reservation-optional-panel">
                            <summary>
                                <span>Detalhes opcionais</span>
                                <small class="text-muted fw-semibold">grupo, marcadores e observações</small>
                            </summary>
                            <div class="reservation-optional-body">
                                <div class="mb-3">
                                    <label class="form-label">Grupo (opcional)</label>
                                    <input type="text" class="form-control" name="grupo_nome" maxlength="120" placeholder="Ex: Famtour ABAV, Família Silva, Evento XYZ">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Marcadores rápidos</label>
                                    <div class="tag-grid">
                                        <?php foreach ($tagsPadrao as $tag): ?>
                                            <?php $tagId = 'batch_tag_' . md5($tag); ?>
                                            <div class="tag-choice">
                                                <input type="checkbox" id="<?= h($tagId) ?>" name="observacao_tags[]" value="<?= h($tag) ?>">
                                                <label for="<?= h($tagId) ?>"><i class="bi bi-bookmark-star"></i><?= h($tag) ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Observações</label>
                                    <textarea class="form-control" name="observacao_reserva" rows="3" placeholder="Observações gerais para o grupo..."></textarea>
                                </div>
                            </div>
                        </details>
                    </div>
                <?php endif; ?>

                <div class="reservation-submit-bar">
                    <button class="btn btn-primary btn-xl w-100" <?= !$canReserve ? 'disabled' : '' ?>>
                        <i class="bi bi-check2-circle me-1"></i><?= $editItem ? 'Salvar alterações' : 'Registrar reserva' ?>
                    </button>
                    <?php if ($editItem): ?>
                        <a class="btn btn-outline-primary btn-xl w-100" href="/?r=reservasTematicas/reservas">Cancelar edição</a>
                    <?php endif; ?>
                </div>
            </form>
    </section>

</div>

<div class="modal fade" id="availabilityModal" tabindex="-1" aria-labelledby="availabilityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content availability-modal-content">
            <div class="modal-header">
                <div>
                    <div class="text-uppercase text-muted small">Disponibilidade</div>
                    <h5 class="fw-bold mb-0" id="availabilityModalLabel">Capacidade por restaurante e turno</h5>
                    <div class="text-muted small mt-1 js-availability-date-label">Data: <?= h(date('d/m/Y', strtotime($availabilityDate))) ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <?php foreach ($quickDates as $quick): ?>
                        <?php $isActive = ($availabilityDate === $quick['date']); ?>
                        <button
                            type="button"
                            class="btn <?= $isActive ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm js-quick-date"
                            data-date="<?= h($quick['date']) ?>"
                        >
                            <?= h($quick['label']) ?>
                        </button>
                    <?php endforeach; ?>
                    <div class="d-flex align-items-center gap-2">
                        <input type="date" class="form-control form-control-sm js-availability-date-input" value="<?= h($availabilityDate) ?>">
                        <button class="btn btn-outline-primary btn-sm js-availability-go" type="button">Ir</button>
                    </div>
                </div>

                <div class="availability-vertical-grid">
                    <?php foreach ($restaurantes as $rest): ?>
                        <div class="availability-restaurant-card">
                            <span class="tag <?= restaurant_badge_class($rest['nome']) ?>"><?= h($rest['nome']) ?></span>
                            <div class="availability-turnos-grid">
                                <?php foreach ($turnos as $turno): ?>
                                    <?php
                                        $info = $availability[$rest['id']][$turno['id']] ?? ['capacidade' => 0, 'reservado' => 0, 'restante' => 0];
                                        $fechado = !empty($info['fechado']);
                                        $status = !$fechado && $info['restante'] > 0 ? 'badge-success' : 'badge-danger';
                                    ?>
                                    <div
                                        class="availability-turno-tile js-availability-cell"
                                        data-rest-id="<?= (int)$rest['id'] ?>"
                                        data-turno-id="<?= (int)$turno['id'] ?>"
                                        data-rest-nome="<?= h($rest['nome']) ?>"
                                        data-turno-hora="<?= h($turno['hora']) ?>"
                                    >
                                        <div class="availability-turno-time"><?= h($turno['hora']) ?></div>
                                        <div class="availability-turno-values">
                                            <span class="badge <?= $status ?> js-availability-restante" role="button" title="Clique para ver os detalhes do turno"><?= $fechado ? 'Fechado' : (int)$info['restante'] ?></span>
                                        </div>
                                        <div class="availability-turno-ratio js-availability-rc"><?php if ($fechado): ?>Reservas: <?php endif; ?><span class="js-availability-reservado" role="button" title="Clique para ver as reservas preenchidas do turno"><?= (int)$info['reservado'] ?></span><?= $fechado ? '' : '/' . (int)$info['capacidade'] ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

</div>

<script>
(() => {
    const dateLabels = Array.from(document.querySelectorAll('.js-availability-date-label'));
    const dateInput = document.getElementById('availabilityDateInput');
    const dateInputs = Array.from(document.querySelectorAll('.js-availability-date-input'));
    const goBtns = Array.from(document.querySelectorAll('.js-availability-go'));
    const quickBtns = Array.from(document.querySelectorAll('.js-quick-date'));
    const reservaDateInput = document.querySelector('input[name="data_reserva"]');
    const restauranteSelect = document.querySelector('select[name="restaurante_id"]');
    const turnoSelect = document.querySelector('select[name="turno_id"]');
    const selectedSlotTitle = document.getElementById('selectedSlotTitle');
    const selectedSlotMeta = document.getElementById('selectedSlotMeta');
    const availabilityCache = {};
    const selectedText = (select) => {
        if (!select || !select.value) return '';
        const opt = select.options[select.selectedIndex];
        return opt ? opt.textContent.trim() : '';
    };
    const updateFlowState = () => {
        const restSelected = !!(restauranteSelect && restauranteSelect.value);
        const turnoSelected = !!(turnoSelect && turnoSelect.value);
        document.querySelectorAll('[data-flow-step]').forEach((step) => {
            const key = step.getAttribute('data-flow-step');
            step.classList.toggle('completed', key === 'date' || (key === 'slot' && turnoSelected));
            step.classList.toggle('active', (key === 'slot' && !turnoSelected) || (key === 'guest' && turnoSelected));
            if (key === 'guest' && !restSelected) {
                step.classList.remove('active');
            }
        });
        document.querySelectorAll('.js-availability-cell[data-rest-id][data-turno-id]').forEach((cell) => {
            const selected = restSelected
                && turnoSelected
                && cell.getAttribute('data-rest-id') === restauranteSelect.value
                && cell.getAttribute('data-turno-id') === turnoSelect.value;
            cell.classList.toggle('is-selected', selected);
        });
    };
    const updateSelectedSlot = () => {
        if (!selectedSlotTitle || !selectedSlotMeta) return;
        const restName = selectedText(restauranteSelect);
        const turnoName = selectedText(turnoSelect);
        const date = reservaDateInput?.value || dateInput?.value || '';
        if (restName && turnoName) {
            selectedSlotTitle.textContent = `${restName} · ${turnoName}`;
            selectedSlotMeta.textContent = `Data da reserva: ${fmtBr(date)}`;
            updateFlowState();
            return;
        }
        if (restName) {
            selectedSlotTitle.textContent = restName;
            selectedSlotMeta.textContent = 'Agora selecione um turno disponível.';
            updateFlowState();
            return;
        }
        selectedSlotTitle.textContent = 'Escolha um restaurante e turno';
        selectedSlotMeta.textContent = 'Clique em um turno disponível no mapa para preencher automaticamente.';
        updateFlowState();
    };
    const setTurnoSequentialState = () => {
        if (!restauranteSelect || !turnoSelect) return;
        const hasRestaurant = restauranteSelect.value !== '';
        turnoSelect.disabled = !hasRestaurant;
        if (!hasRestaurant) {
            turnoSelect.value = '';
        }
        updateSelectedSlot();
    };

    const fmtBr = (iso) => {
        if (!iso || !/^\d{4}-\d{2}-\d{2}$/.test(iso)) return '--/--/----';
        const [y,m,d] = iso.split('-');
        return `${d}/${m}/${y}`;
    };
    const escapeHtml = (value) => (value ?? '')
        .toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    const paintAvailability = (payload) => {
        const data = payload?.availability || {};
        availabilityCache[payload?.date || ''] = data;
        const restTotals = {};
        let dayCapacidade = 0;
        let dayReservado = 0;
        let dayRestante = 0;
        let dayFechados = 0;
        let dayTurnos = 0;

        Object.entries(data).forEach(([restId, turnosData]) => {
            const totals = { capacidade: 0, reservado: 0, restante: 0, fechado: 0, turnos: 0 };
            Object.values(turnosData || {}).forEach((info) => {
                const fechado = !!info?.fechado;
                totals.capacidade += parseInt(info?.capacidade || 0, 10);
                totals.reservado += parseInt(info?.reservado || 0, 10);
                totals.restante += parseInt(info?.restante || 0, 10);
                totals.fechado += fechado ? 1 : 0;
                totals.turnos += 1;
            });
            restTotals[restId] = totals;
            dayCapacidade += totals.capacidade;
            dayReservado += totals.reservado;
            dayRestante += totals.restante;
            dayFechados += totals.fechado;
            dayTurnos += totals.turnos;
        });

        document.querySelectorAll('.js-availability-cell[data-rest-id][data-turno-id]').forEach((cell) => {
            const restId = cell.getAttribute('data-rest-id');
            const turnoId = cell.getAttribute('data-turno-id');
            const info = data?.[restId]?.[turnoId] || { capacidade: 0, reservado: 0, restante: 0 };
            const fechado = !!info.fechado;
            const restante = parseInt(info.restante || 0, 10);
            const reservado = parseInt(info.reservado || 0, 10);
            const capacidade = parseInt(info.capacidade || 0, 10);
            const lotado = !fechado && capacidade > 0 && restante <= 0;
            cell.dataset.restante = String(restante);
            cell.dataset.reservado = String(reservado);
            cell.dataset.capacidade = String(capacidade);
            cell.dataset.fechado = fechado ? '1' : '0';
            cell.classList.toggle('is-closed', fechado);
            cell.classList.toggle('is-full', lotado);
            const badge = cell.querySelector('.js-availability-restante');
            const rc = cell.querySelector('.js-availability-rc');
            const isInlineChip = cell.classList.contains('reserva-turno-chip');
            if (badge) {
                badge.textContent = fechado
                    ? 'Fechado'
                    : (isInlineChip ? `${restante} livres` : String(restante));
                badge.classList.remove('badge-success', 'badge-danger');
                if (!isInlineChip) {
                    badge.classList.add(!fechado && restante > 0 ? 'badge-success' : 'badge-danger');
                }
            }
            if (rc) {
                if (isInlineChip) {
                    rc.innerHTML = fechado
                        ? `<span class="js-availability-reservado" role="button" title="Clique para ver as reservas preenchidas do turno">${reservado}</span> reservas`
                        : `<span class="js-availability-reservado" role="button" title="Clique para ver as reservas preenchidas do turno">${reservado}</span>/${capacidade} ocupados`;
                } else {
                    rc.innerHTML = fechado
                        ? `Reservas: <span class="js-availability-reservado" role="button" title="Clique para ver as reservas preenchidas do turno">${reservado}</span>`
                        : `<span class="js-availability-reservado" role="button" title="Clique para ver as reservas preenchidas do turno">${reservado}</span>/${capacidade}`;
                }
            }
            const pickButton = cell.querySelector('.js-pick-slot');
            if (pickButton) {
                pickButton.disabled = fechado || lotado;
                pickButton.textContent = lotado ? 'Lotado' : 'Selecionar';
            }
        });
        Object.entries(restTotals).forEach(([restId, totals]) => {
            const card = document.querySelector(`.js-rest-card[data-rest-id="${restId}"]`);
            if (!card) return;
            const percentual = totals.capacidade > 0 ? Math.min(100, Math.round((totals.reservado / totals.capacidade) * 100)) : 0;
            const fechadoTotal = totals.fechado > 0 && totals.capacidade <= 0;
            const badge = card.querySelector('.js-rest-total-badge');
            if (badge) {
                badge.textContent = fechadoTotal ? 'Fechado' : `${totals.restante} disp.`;
                badge.classList.toggle('badge-success', !fechadoTotal && totals.restante > 0);
                badge.classList.toggle('badge-danger', fechadoTotal || totals.restante <= 0);
            }
            card.querySelector('.js-rest-progress')?.style.setProperty('--reservation-progress', `${percentual}%`);
            const reservadoEl = card.querySelector('.js-rest-reservado');
            const capacidadeEl = card.querySelector('.js-rest-capacidade');
            if (reservadoEl) reservadoEl.textContent = String(totals.reservado);
            if (capacidadeEl) capacidadeEl.textContent = String(totals.capacidade);
        });
        const dayPercentual = dayCapacidade > 0 ? Math.min(100, Math.round((dayReservado / dayCapacidade) * 100)) : 0;
        const setText = (selector, value) => {
            const el = document.querySelector(selector);
            if (el) el.textContent = String(value);
        };
        setText('.js-day-restante', dayRestante);
        setText('.js-day-reservado', dayReservado);
        setText('.js-day-capacidade', dayCapacidade);
        setText('.js-day-fechados', dayFechados);
        setText('.js-day-turnos', Math.max(1, dayTurnos));
        document.querySelector('.reserva-day-meter')?.style.setProperty('--reservation-day-progress', `${dayPercentual}%`);
        updateFlowState();
    };

    const setQuickActive = (date) => {
        quickBtns.forEach((btn) => {
            const isActive = btn.dataset.date === date;
            btn.classList.toggle('active', isActive);
            if (btn.classList.contains('btn')) {
                btn.classList.toggle('btn-primary', isActive);
                btn.classList.toggle('btn-outline-primary', !isActive);
            }
        });
    };

    const fetchAvailability = async (date) => {
        if (!date) return;
        if (availabilityCache[date]) {
            return { ok: true, date, availability: availabilityCache[date] };
        }
        const url = `/?r=reservasTematicas/reservas&ajax=availability&data=${encodeURIComponent(date)}`;
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (!res.ok) return null;
        const payload = await res.json();
        if (!payload?.ok) return null;
        return payload;
    };

    const applyTurnoAvailability = async () => {
        if (!restauranteSelect || !turnoSelect || !reservaDateInput) return;
        const restId = restauranteSelect.value;
        const date = reservaDateInput.value || dateInput?.value || '';
        if (!restId || !date) {
            Array.from(turnoSelect.options).forEach((opt) => {
                if (!opt.value) return;
                opt.hidden = false;
                opt.disabled = false;
            });
            return;
        }
        const payload = await fetchAvailability(date);
        if (!payload?.ok) return;
        const availability = payload.availability || {};
        const byTurno = availability?.[restId] || {};
        let selectedBlocked = false;

        Array.from(turnoSelect.options).forEach((opt) => {
            if (!opt.value) return;
            const info = byTurno?.[opt.value] || { capacidade: 0, restante: 0 };
            const capacidade = parseInt(info.capacidade || 0, 10);
            const restante = parseInt(info.restante || 0, 10);
            const fechado = !!info.fechado;
            const lotado = capacidade > 0 && restante <= 0;
            const blocked = fechado || lotado;
            opt.hidden = blocked;
            opt.disabled = blocked;
            if (blocked && opt.selected) {
                selectedBlocked = true;
            }
        });

        if (selectedBlocked) {
            turnoSelect.value = '';
            window.fbAlerts?.info('Esse turno não aceita reservas para a data e restaurante selecionados.', {
                title: 'Turno indisponível',
                modal: true,
                buttonText: 'Escolher outro'
            });
        }
    };

    const loadAvailability = async (date) => {
        if (!date) return;
        const payload = await fetchAvailability(date);
        if (!payload?.ok) return;
        paintAvailability(payload);
        dateLabels.forEach((label) => {
            label.textContent = `Data: ${fmtBr(payload.date || date)}`;
        });
        dateInputs.forEach((input) => {
            input.value = payload.date || date;
        });
        setQuickActive(payload.date || date);
        if (reservaDateInput && !reservaDateInput.value) {
            reservaDateInput.value = payload.date || date;
        }
        await applyTurnoAvailability();
    };

    quickBtns.forEach((btn) => btn.addEventListener('click', () => loadAvailability(btn.dataset.date || '')));
    goBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            const localInput = btn.closest('.reserva-date-picker, .d-flex')?.querySelector('.js-availability-date-input');
            loadAvailability(localInput?.value || dateInput?.value || '');
        });
    });
    reservaDateInput?.addEventListener('change', applyTurnoAvailability);
    restauranteSelect?.addEventListener('change', () => {
        setTurnoSequentialState();
        applyTurnoAvailability();
    });
    turnoSelect?.addEventListener('change', updateSelectedSlot);

    const showTurnoPopup = (html) => {
        if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            let modalEl = document.getElementById('availabilityDetailModal');
            if (!modalEl) {
                modalEl = document.createElement('div');
                modalEl.id = 'availabilityDetailModal';
                modalEl.className = 'modal fade';
                modalEl.tabIndex = -1;
                modalEl.innerHTML = `
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                        <div class="modal-content availability-modal-content">
                            <div class="modal-header">
                                <div>
                                    <div class="text-uppercase text-muted small">Reservas do turno</div>
                                    <h5 class="modal-title fw-bold mb-0">Detalhes de ocupação</h5>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                            </div>
                            <div class="modal-body" id="availabilityDetailModalBody"></div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Fechar</button>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modalEl);
            }
            const bodyEl = modalEl.querySelector('#availabilityDetailModalBody');
            if (bodyEl) bodyEl.innerHTML = html;
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
            return;
        }

        const textFallback = document.createElement('div');
        textFallback.innerHTML = html;
        window.fbAlerts?.modal({
            type: 'info',
            title: 'Detalhes do turno',
            message: textFallback.textContent || 'Detalhes do turno',
            buttonText: 'Fechar'
        });
    };

    const openAvailabilityDetail = async (triggerEl) => {
        const cell = triggerEl?.closest('.js-availability-cell[data-rest-id][data-turno-id]');
        if (!cell) return;
        const restId = cell.getAttribute('data-rest-id');
        const turnoId = cell.getAttribute('data-turno-id');
        const restNome = cell.getAttribute('data-rest-nome') || 'Restaurante';
        const turnoHora = cell.getAttribute('data-turno-hora') || '--:--';
        const date = dateInput?.value || reservaDateInput?.value || '';
        if (!date) return;

        try {
            const url = `/?r=reservasTematicas/reservas&ajax=availability_detail&data=${encodeURIComponent(date)}&restaurante_id=${encodeURIComponent(restId)}&turno_id=${encodeURIComponent(turnoId)}`;
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error('Falha ao buscar detalhes do turno.');
            const payload = await res.json();
            if (!payload?.ok) throw new Error(payload?.message || 'Não foi possível carregar os detalhes.');

            const rows = (payload.items || []).map((item) => `
                <div class="availability-detail-item">
                    <div>
                        <div class="availability-detail-title">${escapeHtml(item.titular_nome || '-')}</div>
                        <div class="availability-detail-meta">
                            <span class="detail-badge is-uh">UH ${escapeHtml(item.uh_numero || '-')}</span>
                            <span class="detail-badge is-pax">${escapeHtml(String(item.pax ?? 0))} PAX</span>
                            <span class="detail-badge is-chd">${escapeHtml(String(item.qtd_chd ?? 0))} CHD</span>
                            <span class="detail-badge is-status">${escapeHtml(item.status || 'Reservada')}</span>
                            <span class="detail-badge is-user">Criado por ${escapeHtml(item.usuario || '-')}</span>
                        </div>
                    </div>
                    ${item.edit_url ? `<a class="btn btn-outline-primary btn-sm availability-detail-action" href="${escapeHtml(item.edit_url)}">Editar</a>` : '<span class="badge badge-soft availability-detail-action">Somente autor</span>'}
                </div>
            `).join('');
        const restante = parseInt(String(payload.restante ?? cell.dataset.restante ?? '0'), 10) || 0;
        const reservado = parseInt(String(payload.reservado ?? cell.dataset.reservado ?? '0'), 10) || 0;
        const capacidade = parseInt(String(payload.capacidade ?? cell.dataset.capacidade ?? '0'), 10) || 0;
            const html = `
                <div class="text-start">
                    <div class="small text-muted mb-2">Data ${escapeHtml(fmtBr(payload.date || date))} · ${escapeHtml(restNome)} · ${escapeHtml(turnoHora)}</div>
                    <div class="availability-detail-list mb-2">${rows || '<div class="availability-detail-empty">Sem reservas neste turno.</div>'}</div>
                    <div class="fw-semibold">Disponíveis: ${escapeHtml(String(restante))} · Preenchidas: ${escapeHtml(String(reservado))}/${escapeHtml(String(capacidade))}</div>
                    <div class="small text-muted">Total de reservas: ${escapeHtml(String(payload.count || 0))} · Total de PAX: ${escapeHtml(String(payload.total_pax || 0))} · Total CHD: ${escapeHtml(String(payload.total_chd || 0))}</div>
                </div>
            `;
            showTurnoPopup(html);
        } catch (err) {
            const msg = err?.message || 'Erro ao carregar detalhes.';
            window.fbAlerts?.error(msg, 'Não foi possível abrir');
        }
    };

    document.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target : null;
        if (!target) return;
        const scrollCompose = target.closest('.js-scroll-compose');
        if (scrollCompose) {
            document.querySelector('.reservation-compose-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }
        const pick = target.closest('.js-pick-slot');
        if (pick && restauranteSelect && turnoSelect) {
            restauranteSelect.value = pick.getAttribute('data-rest-id') || '';
            setTurnoSequentialState();
            turnoSelect.value = pick.getAttribute('data-turno-id') || '';
            updateSelectedSlot();
            applyTurnoAvailability();
            document.querySelector('.reservation-compose-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }
        const trigger = target.closest('.js-availability-restante, .js-availability-reservado');
        if (!trigger) return;
        openAvailabilityDetail(trigger);
    });
    const btnModeSingle = document.getElementById('btnModeSingle');
    const btnModeBatch = document.getElementById('btnModeBatch');
    const singleReservationPanel = document.getElementById('singleReservationPanel');
    const batchReservationPanel = document.getElementById('batchReservationPanel');
    const addBatchRowBtn = document.getElementById('btnAddBatchRow');
    const batchRows = document.getElementById('batchRows');
    const batchDefaultTitular = document.getElementById('batchDefaultTitular');
    const actionInput = document.getElementById('reservaActionInput');
    const reservaForm = document.querySelector('form[action="/?r=reservasTematicas/reservas"]');
    const singleFields = [
        document.querySelector('input[name=\"uh_numero\"]'),
        document.querySelector('input[name=\"titular_nome\"]'),
        document.querySelector('input[name=\"pax\"]')
    ];

    const batchTemplate = () => {
        const wrap = document.createElement('div');
        wrap.className = 'batch-row-wrap';
        wrap.innerHTML = `
            <div class="batch-row-grid">
                <div><label class="form-label">UH</label><input class="form-control" name="batch_uh_numero[]" inputmode="numeric" required></div>
                <div><label class="form-label">PAX</label><input type="number" class="form-control" min="1" name="batch_pax[]" value="1" required></div>
                <div><label class="form-label">CHD</label><input class="form-control" name="batch_chd_idades[]" placeholder="Ex: 3y7y"></div>
                <div class="d-grid"><button type="button" class="btn btn-outline-danger btn-sm js-remove-batch-row" aria-label="Remover UH"><i class="bi bi-dash-lg"></i></button></div>
            </div>
        `;
        wrap.querySelector('.js-remove-batch-row')?.addEventListener('click', () => wrap.remove());
        return wrap;
    };

    const setBatchEnabled = (enabled) => {
        if (!batchRows) return;
        batchRows.querySelectorAll('input').forEach((input) => {
            input.disabled = !enabled;
        });
    };

    const setPanelEnabled = (panel, enabled) => {
        if (!panel) return;
        panel.querySelectorAll('input, select, textarea, button').forEach((el) => {
            el.disabled = !enabled;
        });
    };

    const setReservaMode = (mode) => {
        if (!actionInput || !singleReservationPanel || !batchReservationPanel) return;
        const isBatch = mode === 'batch';
        actionInput.value = isBatch ? 'create_batch' : 'create';

        singleReservationPanel.classList.toggle('d-none', isBatch);
        batchReservationPanel.classList.toggle('d-none', !isBatch);
        setPanelEnabled(singleReservationPanel, !isBatch);
        setPanelEnabled(batchReservationPanel, isBatch);

        btnModeSingle?.classList.toggle('btn-primary', !isBatch);
        btnModeSingle?.classList.toggle('btn-outline-primary', isBatch);
        btnModeBatch?.classList.toggle('btn-primary', isBatch);
        btnModeBatch?.classList.toggle('btn-outline-primary', !isBatch);

        singleFields.forEach((el) => {
            if (!el) return;
            el.required = !isBatch;
        });

        if (isBatch) {
            if (batchRows && batchRows.children.length === 0) {
                batchRows.appendChild(batchTemplate());
            }
            setBatchEnabled(true);
        } else {
            setBatchEnabled(false);
        }
    };

    addBatchRowBtn?.addEventListener('click', () => {
        if (!batchRows) return;
        const node = batchTemplate();
        if (batchReservationPanel && !batchReservationPanel.classList.contains('d-none')) {
            node.querySelectorAll('input').forEach((input) => (input.disabled = false));
        } else {
            node.querySelectorAll('input').forEach((input) => (input.disabled = true));
        }
        batchRows.appendChild(node);
    });

    btnModeSingle?.addEventListener('click', () => setReservaMode('single'));
    btnModeBatch?.addEventListener('click', () => setReservaMode('batch'));

    let reservaSubmitting = false;
    const setReservaSubmitting = (submitting) => {
        reservaSubmitting = submitting;
        reservaForm?.querySelectorAll('button[type="submit"], button:not([type])').forEach((btn) => {
            if (submitting) {
                btn.setAttribute('disabled', 'disabled');
            } else {
                btn.removeAttribute('disabled');
            }
        });
    };

    const extractReservaAlertFromHtml = (html) => {
        if (!html || !window.DOMParser) return {};
        try {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const flashNode = doc.getElementById('appFlashPayload');
            if (flashNode && flashNode.textContent) {
                const flash = JSON.parse(flashNode.textContent);
                if (flash && flash.message) {
                    return {
                        type: flash.type || 'info',
                        message: String(flash.message)
                    };
                }
            }

            const appAlert = doc.querySelector('script[type="application/json"][data-app-alert="1"]');
            if (appAlert && appAlert.textContent) {
                const payload = JSON.parse(appAlert.textContent);
                if (payload && payload.message) {
                    return {
                        type: payload.type || 'info',
                        message: String(payload.message)
                    };
                }
            }

            const legacyAlert = doc.querySelector('.alert, .app-inline-note, .app-alert-modal-message');
            return legacyAlert ? {
                type: legacyAlert.classList.contains('is-warning') || legacyAlert.className.includes('warning') ? 'warning' : 'danger',
                message: (legacyAlert.textContent || '').replace(/\s+/g, ' ').trim()
            } : {};
        } catch (error) {
            return {};
        }
    };

    const reservaMessagesByCode = {
        capacidade_turno_atingida: 'Limite de reservas excedido para este turno. Ajuste o horário, quantidade de pessoas ou escolha outro turno.',
        capacidade_nao_configurada: 'Capacidade não configurada para este turno. Configure a capacidade antes de registrar reservas.',
        capacidade_destino_atingida: 'Limite de reservas excedido no turno de destino. Escolha outro turno ou ajuste a capacidade.',
        capacidade_destino_nao_configurada: 'Capacidade não configurada para o turno de destino. Configure a capacidade antes de mover a reserva.',
        restaurante_fechado: 'Este restaurante está fechado na data selecionada. Escolha outro restaurante ou outra data.',
        fora_janela_reserva: 'Fora do horário permitido para reservas.',
        uh_invalida: 'UH não encontrada. Confira o número da habitação antes de salvar.',
        uh_obrigatoria: 'Informe a UH da reserva.',
        titular_obrigatorio: 'Informe o titular da reserva.',
        pax_invalido: 'Informe uma quantidade válida de PAX.',
        pax_grupo_invalido: 'Preencha a quantidade de PAX em todas as UHs do grupo.',
        grupo_sem_titular: 'Informe o titular do grupo.',
        grupo_sem_uh: 'Adicione ao menos uma UH no grupo.',
        reserva_duplicada_uh: 'Esta UH já possui reserva nesse restaurante, data e turno.',
        chd_maior_que_pax: 'As idades de CHD não podem exceder a quantidade total de PAX.',
        chd_grupo_maior_que_pax: 'A quantidade de CHD não pode ser maior que o PAX da UH.',
        uh_duplicada_grupo: 'Há UHs repetidas no grupo. Remova a duplicidade antes de salvar.',
        uh_grupo_invalida: 'Uma ou mais UHs do grupo não foram encontradas. Confira os números informados.',
        idades_chd_invalidas: 'Revise o formato das idades CHD. Use o padrão 3y7y, por exemplo.'
    };

    const decodeJsonFragment = (value) => {
        if (!value) return '';
        try {
            return JSON.parse(`"${String(value).replace(/"/g, '\\"')}"`);
        } catch (error) {
            return String(value).replace(/\\u([0-9a-f]{4})/gi, (_, hex) => String.fromCharCode(parseInt(hex, 16))).replace(/\\"/g, '"').replace(/\\n/g, ' ');
        }
    };

    const extractReservaJsonField = (text, field) => {
        if (!text) return '';
        const pattern = new RegExp(`"${field}"\\s*:\\s*"((?:\\\\.|[^"\\\\])*)"`, 'i');
        const match = String(text).match(pattern);
        return match ? decodeJsonFragment(match[1]).trim() : '';
    };

    const buildReservaSmartMessage = (code, payload) => {
        if (!code || !payload || typeof payload !== 'object') return '';
        const intValue = (field) => {
            const value = Number.parseInt(String(payload[field] ?? '0'), 10);
            return Number.isFinite(value) ? Math.max(0, value) : 0;
        };
        if (code === 'capacidade_turno_atingida' || code === 'capacidade_destino_atingida') {
            const disponivel = intValue('pax_disponivel');
            const tentativa = intValue('pax_tentativa');
            const capacidade = intValue('capacidade');
            const reservado = intValue('pax_reservado');
            const prefix = code === 'capacidade_destino_atingida'
                ? 'Limite excedido no turno de destino.'
                : 'Limite de reservas excedido para este turno.';
            return `${prefix} Disponíveis: ${disponivel} vaga(s). Tentativa: ${tentativa} PAX. Capacidade: ${capacidade} PAX, já reservados: ${reservado} PAX.`;
        }
        if (code === 'capacidade_nao_configurada' || code === 'capacidade_destino_nao_configurada') {
            const tentativa = intValue('pax_tentativa');
            const base = reservaMessagesByCode[code] || '';
            return tentativa > 0 ? `${base} Tentativa atual: ${tentativa} PAX.` : base;
        }
        return '';
    };

    const normalizeReservaResponsePayload = (payload, rawText = '', response = null) => {
        const normalized = (payload && typeof payload === 'object') ? payload : {};
        const code = String(normalized.code || extractReservaJsonField(rawText, 'code') || '').trim();
        const rawMessage = String(normalized.message || extractReservaJsonField(rawText, 'message') || '').replace(/\s+/g, ' ').trim();
        const isGenericFailure = rawMessage === '' || rawMessage.includes('Revise os dados e tente novamente');
        const smartMessage = buildReservaSmartMessage(code, normalized.payload);
        const message = smartMessage
            || (code && reservaMessagesByCode[code] && isGenericFailure ? reservaMessagesByCode[code] : '')
            || rawMessage
            || reservaMessagesByCode[code]
            || '';

        return {
            ...normalized,
            ok: normalized.ok === undefined ? response?.ok === true : normalized.ok,
            type: normalized.type || (response?.ok ? 'success' : 'danger'),
            code,
            message: message.trim() || (response && response.status === 404
                ? 'A rota de envio da reserva não foi encontrada pelo servidor. Atualize a página e tente novamente; se persistir, acione a administração.'
                : response && response.status >= 500
                ? 'O servidor encontrou um erro ao salvar a reserva. Tente novamente ou acione a administração.'
                : '')
        };
    };

    const parseReservaResponse = async (response) => {
        const contentType = (response.headers.get('Content-Type') || '').toLowerCase();
        const text = await response.text();
        if (contentType.includes('application/json') || text.trim().startsWith('{')) {
            try {
                return normalizeReservaResponsePayload(JSON.parse(text), text, response);
            } catch (error) {
                return normalizeReservaResponsePayload({ ok: false, type: 'danger' }, text, response);
            }
        }

        const alert = extractReservaAlertFromHtml(text);
        const type = alert.type || (response.ok ? 'success' : 'danger');
        const ok = response.ok && !['danger', 'warning'].includes(type);
        return normalizeReservaResponsePayload({
            ok,
            type,
            message: alert.message || '',
            redirected: response.redirected,
            redirect: response.url || ''
        }, text, response);
    };

    reservaForm?.addEventListener('submit', async (event) => {
        if (!window.fetch || reservaSubmitting) {
            if (reservaSubmitting) event.preventDefault();
            return;
        }

        if (actionInput && actionInput.value === 'create_batch') {
            const defaultName = (batchDefaultTitular?.value || '').trim();
            if (defaultName === '') {
                event.preventDefault();
                window.fbAlerts?.warning('Informe o titular do grupo.', { modal: true, buttonText: 'Corrigir' });
                return;
            }
        }

        event.preventDefault();
        setReservaSubmitting(true);
        try {
            const submitUrl = new URL('/?r=reservasTematicas/reservas', window.location.origin);
            const response = await fetch(submitUrl.toString(), {
                method: 'POST',
                body: new FormData(reservaForm),
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'fetch'
                }
            });
            const payload = await parseReservaResponse(response);
            if (!response.ok || payload.ok === false) {
                await (window.fbAlerts?.show({
                    type: payload.type || 'danger',
                    message: payload.message || 'Não foi possível salvar a reserva. Revise os dados e tente novamente.',
                    modal: true,
                    buttonText: 'Corrigir'
                }) || Promise.resolve());
                return;
            }

            const successMessage = payload.message || 'Reserva salva com sucesso.';
            const showAfterRedirect = window.fbAlerts?.afterRedirect
                ? window.fbAlerts.afterRedirect(successMessage, { type: 'success', title: 'Reserva confirmada' })
                : false;
            if (!showAfterRedirect) {
                window.fbAlerts?.success(successMessage, 'Reserva confirmada');
            }
            window.fbAlerts?.clearSavedForms?.();
            const redirect = payload.redirect || '/?r=reservasTematicas/reservas';
            setTimeout(() => {
                window.location.assign(redirect);
            }, showAfterRedirect ? 80 : 900);
        } catch (error) {
            await (window.fbAlerts?.error('Falha de comunicação ao salvar. Verifique sua conexão e tente novamente.') || Promise.resolve());
        } finally {
            setReservaSubmitting(false);
        }
    });

    if (btnModeSingle && btnModeBatch) {
        setReservaMode('single');
    }
    setTurnoSequentialState();
    updateSelectedSlot();
    applyTurnoAvailability();
})();
</script>
