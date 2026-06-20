<?php
$flash = $this->data['flash'] ?? null;
$restaurantes = $this->data['restaurantes'] ?? [];
$printRestaurantes = $this->data['print_restaurantes'] ?? $restaurantes;
$turnos = $this->data['turnos'] ?? [];
$reservas = $this->data['reservas'] ?? [];
$filters = $this->data['filters'] ?? [];
$user = $this->data['user'] ?? Auth::user();
$restrictedRestaurant = $this->data['restricted_restaurant'] ?? null;
$summary = $this->data['summary'] ?? [];

$statusOptions = ['Reservada', 'Finalizada', 'Nao compareceu', 'Cancelada', 'Divergencia'];
$statusLabels = [
    'Reservada' => 'Reservada',
    'Finalizada' => 'Finalizada',
    'Nao compareceu' => 'Não compareceu',
    'Cancelada' => 'Cancelada',
    'Divergencia' => 'Divergência',
];

$normalizeStatus = static function (?string $status): string {
    $status = normalize_mojibake(trim((string)$status));
    $map = [
        'Nao compareceu' => 'Nao compareceu',
        'Não compareceu' => 'Nao compareceu',
        'Divergencia' => 'Divergencia',
        'Divergência' => 'Divergencia',
        'Divergência' => 'Divergencia',
        'Conferida' => 'Reservada',
        'Em atendimento' => 'Reservada',
    ];
    return $map[$status] ?? $status;
};
$labelStatus = static function (?string $status) use ($normalizeStatus, $statusLabels): string {
    $canon = $normalizeStatus($status);
    return $statusLabels[$canon] ?? $canon;
};
$statusBadgeClass = static function (?string $status) use ($normalizeStatus): string {
    $canon = $normalizeStatus($status);
    $map = [
        'Reservada' => 'status-reserved',
        'Finalizada' => 'status-finished',
        'Nao compareceu' => 'status-noshow',
        'Cancelada' => 'status-canceled',
        'Divergencia' => 'status-warning',
    ];
    return $map[$canon] ?? 'status-muted';
};

$reservasOrdenadas = $reservas;
usort($reservasOrdenadas, static function (array $a, array $b) use ($normalizeStatus): int {
    $ta = (string)($a['turno_hora'] ?? '');
    $tb = (string)($b['turno_hora'] ?? '');
    if ($ta !== $tb) {
        return strcmp($ta, $tb);
    }
    $sa = $normalizeStatus((string)($a['status_reserva'] ?? ($a['status'] ?? '')));
    $sb = $normalizeStatus((string)($b['status_reserva'] ?? ($b['status'] ?? '')));
    if ($sa !== $sb) {
        return strcmp($sa, $sb);
    }
    return strcmp((string)($a['uh_numero'] ?? ''), (string)($b['uh_numero'] ?? ''));
});

?>

<style>
    .tematic-operacao-page {
        min-width: 0;
    }
    .tematic-operacao-page .summary-grid {
        display: grid;
        gap: 0.75rem;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    }
    .tematic-operacao-page .summary-grid .saas-stat-card {
        min-height: 116px;
    }
    .tematic-operacao-page .summary-grid .saas-stat-card .small {
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-size: 0.68rem;
    }
    .tematic-operacao-page .summary-grid .saas-stat-value {
        margin-top: 0.35rem;
    }
    .tematic-operacao-page .summary-grid::-webkit-scrollbar {
        height: 0;
    }
    .tematic-operacao-page .operation-hero {
        overflow: hidden;
        position: relative;
        border: 1px solid rgba(249, 115, 22, 0.16);
        background:
            linear-gradient(135deg, rgba(255, 247, 237, 0.96), rgba(255, 255, 255, 0.94)),
            var(--ab-card);
    }
    .tematic-operacao-page .operation-hero .section-title {
        min-width: 0;
    }
    .tematic-operacao-page .operation-hero h3 {
        letter-spacing: 0;
    }
    .tematic-operacao-page .operation-hero-copy {
        max-width: 760px;
    }
    html[data-theme='dark'] .tematic-operacao-page .operation-hero {
        background:
            linear-gradient(135deg, rgba(67, 56, 202, 0.18), rgba(15, 23, 42, 0.96)),
            var(--ab-card);
    }
    .tematic-operacao-page .section-block {
        border: 1px solid var(--ab-border);
        border-radius: 22px;
        padding: 1rem;
        background: var(--ab-card);
        box-shadow: var(--ab-shadow-soft);
    }
    .tematic-operacao-page .collapsible-section-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }
    .tematic-operacao-page .collapsible-section-head .section-title {
        margin-bottom: 0 !important;
    }
    .tematic-operacao-page .section-toggle-btn {
        min-height: 38px;
        white-space: nowrap;
    }
    .tematic-operacao-page .section-toggle-btn .bi-chevron-up {
        transition: transform 0.18s ease;
    }
    .tematic-operacao-page .section-block.is-collapsed .section-toggle-btn .bi-chevron-up {
        transform: rotate(180deg);
    }
    .tematic-operacao-page .section-block.is-collapsed .collapsible-section-body {
        display: none;
    }
    .tematic-operacao-page .section-collapsed-hint {
        display: none;
        border: 1px dashed rgba(148, 163, 184, 0.35);
        border-radius: 16px;
        padding: 0.85rem;
        color: var(--ab-muted);
        background: color-mix(in srgb, var(--ab-card) 78%, transparent);
    }
    .tematic-operacao-page .section-block.is-collapsed .section-collapsed-hint {
        display: block;
    }
    .tematic-operacao-page .reservation-filter-panel {
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 18px;
        padding: 0.85rem;
        background: rgba(248, 250, 252, 0.78);
    }
    html[data-theme='dark'] .tematic-operacao-page .reservation-filter-panel {
        background: rgba(15, 23, 42, 0.48);
    }
    .tematic-operacao-page .quick-advanced-filters {
        min-width: 0;
    }
    .tematic-operacao-page .quick-filter-toggle {
        display: none;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
    }
    .tematic-operacao-page .quick-filter-toggle .bi-chevron-down {
        transition: transform 0.18s ease;
    }
    .tematic-operacao-page .quick-filter-toggle[aria-expanded='true'] .bi-chevron-down {
        transform: rotate(180deg);
    }
    .tematic-operacao-page .reservation-table-shell {
        overflow: hidden;
        border: 1px solid rgba(148, 163, 184, 0.24);
        border-radius: 18px;
        background: var(--ab-card);
    }
    .tematic-operacao-page .reservation-card-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 0.75rem;
    }
    .tematic-operacao-page .reservation-op-card {
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 18px;
        background:
            linear-gradient(180deg, color-mix(in srgb, var(--ab-card) 94%, var(--ab-soft-bg) 6%), var(--ab-card)),
            var(--ab-card);
        padding: 0.9rem;
        cursor: pointer;
        min-width: 0;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
    }
    .tematic-operacao-page .reservation-op-card:hover {
        border-color: rgba(249, 115, 22, 0.35);
        box-shadow: 0 16px 32px rgba(15, 23, 42, 0.09);
        transform: translateY(-1px);
    }
    .tematic-operacao-page .reservation-op-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.65rem;
        margin-bottom: 0.7rem;
    }
    .tematic-operacao-page .reservation-op-title {
        min-width: 0;
    }
    .tematic-operacao-page .reservation-op-title strong {
        display: block;
        color: var(--ab-ink);
        font-size: 0.98rem;
        line-height: 1.2;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .tematic-operacao-page .reservation-op-title .small {
        display: block;
        margin-top: 0.2rem;
        color: var(--ab-muted);
    }
    .tematic-operacao-page .reservation-op-meta {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.45rem;
        margin-bottom: 0.65rem;
    }
    .tematic-operacao-page .reservation-op-metric {
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 13px;
        background: color-mix(in srgb, var(--ab-soft-bg) 68%, var(--ab-card) 32%);
        padding: 0.45rem;
        text-align: center;
        min-width: 0;
    }
    .tematic-operacao-page .reservation-op-metric .label {
        display: block;
        color: var(--ab-muted);
        font-size: 0.64rem;
        font-weight: 850;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .tematic-operacao-page .reservation-op-metric .value {
        display: block;
        margin-top: 0.12rem;
        color: var(--ab-ink);
        font-size: 0.94rem;
        font-weight: 850;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .tematic-operacao-page .reservation-op-foot {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
        min-width: 0;
    }
    .tematic-operacao-page .reservation-op-foot .tag {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .tematic-operacao-page .reservation-empty-card {
        border: 1px dashed rgba(148, 163, 184, 0.35);
        border-radius: 18px;
        padding: 1rem;
        text-align: center;
        color: var(--ab-muted);
        background: color-mix(in srgb, var(--ab-card) 78%, transparent);
    }
    html[data-theme='dark'] .tematic-operacao-page .reservation-op-card,
    html[data-theme='dark'] .tematic-operacao-page .reservation-op-metric {
        background: rgba(15, 23, 42, 0.5);
    }
    .tematic-operacao-page .reservation-list-table {
        margin-bottom: 0;
    }
    .tematic-operacao-page .reservation-list-table thead th {
        border-bottom: 1px solid rgba(148, 163, 184, 0.22);
        background: rgba(255, 247, 237, 0.68);
        color: #9a3412;
        font-size: 0.72rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    html[data-theme='dark'] .tematic-operacao-page .reservation-list-table thead th {
        background: rgba(30, 41, 59, 0.74);
        color: #f8fafc;
    }
    .tematic-operacao-page .reservation-list-table tbody tr {
        transition: background-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
    }
    .tematic-operacao-page .reservation-list-table tbody td {
        padding-top: 0.72rem;
        padding-bottom: 0.72rem;
        vertical-align: middle;
    }
    .tematic-operacao-page .reservation-status-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 28px;
        padding: 0.3rem 0.62rem;
        border-radius: 999px;
        border: 1px solid transparent;
        font-size: 0.78rem;
        font-weight: 750;
        white-space: nowrap;
    }
    .tematic-operacao-page .reservation-status-pill.status-reserved {
        color: #0f766e;
        border-color: rgba(20, 184, 166, 0.28);
        background: rgba(204, 251, 241, 0.78);
    }
    .tematic-operacao-page .reservation-status-pill.status-finished {
        color: #166534;
        border-color: rgba(34, 197, 94, 0.24);
        background: rgba(220, 252, 231, 0.82);
    }
    .tematic-operacao-page .reservation-status-pill.status-noshow,
    .tematic-operacao-page .reservation-status-pill.status-canceled {
        color: #991b1b;
        border-color: rgba(248, 113, 113, 0.26);
        background: rgba(254, 226, 226, 0.82);
    }
    .tematic-operacao-page .reservation-status-pill.status-warning {
        color: #92400e;
        border-color: rgba(251, 191, 36, 0.32);
        background: rgba(254, 243, 199, 0.84);
    }
    .tematic-operacao-page .reservation-status-pill.status-muted {
        color: #475569;
        border-color: rgba(148, 163, 184, 0.28);
        background: rgba(241, 245, 249, 0.86);
    }
    html[data-theme='dark'] .tematic-operacao-page .reservation-status-pill,
    html[data-theme='dark'] .reservation-detail-modal .reservation-status-pill {
        color: #f8fafc;
        background: rgba(30, 41, 59, 0.78);
        border-color: rgba(148, 163, 184, 0.26);
    }
    .tematic-operacao-page .reservation-pagination {
        align-items: center;
        justify-content: space-between;
        padding-top: 0.25rem;
        gap: 0.5rem;
    }
    .tematic-operacao-page .reservation-pagination .btn {
        min-width: 34px;
        min-height: 34px;
        padding: 0.25rem 0.55rem;
    }
    .tematic-operacao-page .reservation-pagination-info {
        color: var(--ab-muted);
        font-size: 0.78rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .tematic-operacao-page .reservation-pagination-controls {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 0.35rem;
    }
    .tematic-operacao-page .reservation-pagination-dots {
        align-self: center;
        color: var(--ab-muted);
        padding: 0 0.15rem;
    }
    .tematic-operacao-page .reservation-helper {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        color: var(--ab-muted);
    }
    .reservation-detail-modal {
        border: 0;
        border-radius: 26px;
        overflow: hidden;
        box-shadow: 0 30px 90px rgba(15, 23, 42, 0.32);
        background: var(--ab-card);
    }
    .modal-dialog-scrollable .reservation-detail-modal > form {
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - var(--bs-modal-margin) * 2);
        min-height: 0;
    }
    .reservation-detail-modal .modal-header {
        align-items: flex-start;
        gap: 1rem;
        padding: 1.05rem 1.15rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.18);
        background:
            linear-gradient(135deg, color-mix(in srgb, var(--ab-accent) 10%, transparent), transparent 62%),
            color-mix(in srgb, var(--ab-card) 92%, var(--ab-soft-bg) 8%);
    }
    .reservation-detail-modal .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        padding: 0;
        -webkit-overflow-scrolling: touch;
    }
    .reservation-detail-modal .modal-footer {
        border-top: 1px solid rgba(148, 163, 184, 0.2);
        padding: 0.9rem 1rem;
        background: color-mix(in srgb, var(--ab-card) 94%, var(--ab-soft-bg) 6%);
    }
    html[data-theme='dark'] .reservation-detail-modal .modal-header {
        background: rgba(30, 41, 59, 0.82);
    }
    .reservation-modal-kicker {
        color: var(--ab-muted);
        font-size: 0.68rem;
        font-weight: 850;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }
    .reservation-modal-subtitle {
        color: var(--ab-muted);
        font-size: 0.86rem;
        margin-top: 0.18rem;
    }
    .reservation-modal-summary {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 1rem;
        align-items: stretch;
        border: 1px solid rgba(148, 163, 184, 0.2);
        border-radius: 22px;
        padding: 1rem;
        margin-bottom: 1rem;
        background:
            linear-gradient(135deg, color-mix(in srgb, var(--ab-accent) 6%, transparent), transparent 56%),
            var(--ab-card);
    }
    .reservation-modal-layout {
        display: grid;
        grid-template-columns: minmax(260px, 0.42fr) minmax(0, 0.58fr);
        min-height: 520px;
    }
    .reservation-modal-profile {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        padding: 1.15rem;
        border-right: 1px solid rgba(148, 163, 184, 0.18);
        background:
            radial-gradient(circle at 18% 12%, color-mix(in srgb, var(--ab-accent) 14%, transparent), transparent 34%),
            linear-gradient(160deg, color-mix(in srgb, var(--ab-soft-bg) 70%, var(--ab-card) 30%), var(--ab-card));
    }
    .reservation-modal-editor {
        min-width: 0;
        padding: 1.15rem;
        background: color-mix(in srgb, var(--ab-card) 94%, var(--ab-soft-bg) 6%);
    }
    .reservation-profile-top {
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
    }
    .reservation-profile-avatar {
        width: 52px;
        height: 52px;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 52px;
        color: #fff;
        background: linear-gradient(135deg, #0f766e, #f97316);
        box-shadow: 0 14px 34px rgba(15, 118, 110, 0.24);
        font-weight: 900;
        letter-spacing: 0;
    }
    .reservation-profile-title {
        min-width: 0;
    }
    .reservation-profile-title strong {
        display: block;
        color: var(--ab-ink);
        font-size: 1.22rem;
        line-height: 1.1;
        overflow-wrap: anywhere;
    }
    .reservation-profile-title span {
        display: block;
        color: var(--ab-muted);
        font-size: 0.86rem;
        margin-top: 0.25rem;
    }
    .reservation-profile-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
    }
    .reservation-profile-stats {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.65rem;
    }
    .reservation-profile-stat {
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 18px;
        padding: 0.85rem;
        background: color-mix(in srgb, var(--ab-card) 72%, transparent);
    }
    .reservation-profile-stat span {
        display: block;
        color: var(--ab-muted);
        font-size: 0.68rem;
        font-weight: 850;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }
    .reservation-profile-stat strong {
        display: block;
        margin-top: 0.18rem;
        color: var(--ab-ink);
        font-size: 1.35rem;
        line-height: 1;
    }
    .reservation-profile-note {
        margin-top: auto;
        border: 1px dashed rgba(148, 163, 184, 0.32);
        border-radius: 18px;
        padding: 0.85rem;
        color: var(--ab-muted);
        font-size: 0.82rem;
        background: color-mix(in srgb, var(--ab-card) 56%, transparent);
    }
    .reservation-editor-grid {
        display: grid;
        gap: 0.85rem;
    }
    .reservation-modal-title {
        min-width: 0;
    }
    .reservation-modal-title strong {
        display: block;
        color: var(--ab-ink);
        font-size: 1.15rem;
        line-height: 1.2;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .reservation-modal-title span {
        display: block;
        color: var(--ab-muted);
        margin-top: 0.25rem;
        font-size: 0.82rem;
    }
    .reservation-modal-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-top: 0.55rem;
    }
    .reservation-modal-count {
        display: grid;
        grid-template-columns: repeat(2, minmax(70px, 1fr));
        gap: 0.55rem;
        min-width: 190px;
    }
    .reservation-modal-count .mini-stat {
        border: 1px solid rgba(148, 163, 184, 0.2);
        border-radius: 16px;
        background: color-mix(in srgb, var(--ab-soft-bg) 68%, var(--ab-card) 32%);
        padding: 0.65rem 0.7rem;
        text-align: center;
    }
    .reservation-modal-count .mini-stat span {
        display: block;
        color: var(--ab-muted);
        font-size: 0.64rem;
        font-weight: 850;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .reservation-modal-count .mini-stat strong {
        display: block;
        margin-top: 0.12rem;
        color: var(--ab-ink);
        font-size: 1.18rem;
    }
    .reservation-modal-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.85rem;
    }
    .reservation-form-section {
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 20px;
        padding: 0.95rem;
        background: color-mix(in srgb, var(--ab-soft-bg) 42%, var(--ab-card) 58%);
        margin-bottom: 0;
    }
    .reservation-form-section-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.55rem;
        color: var(--ab-ink);
        font-weight: 850;
        margin-bottom: 0.7rem;
    }
    .reservation-form-section-title span {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
    }
    .reservation-form-section-title span::before {
        content: "";
        width: 9px;
        height: 9px;
        border-radius: 999px;
        background: var(--ab-accent);
        box-shadow: 0 0 0 5px color-mix(in srgb, var(--ab-accent) 12%, transparent);
    }
    .reservation-form-section-title small {
        color: var(--ab-muted);
        font-size: 0.72rem;
        font-weight: 750;
        text-transform: uppercase;
    }
    .reservation-detail-modal .form-label {
        color: var(--ab-muted);
        font-size: 0.7rem;
        font-weight: 850;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .reservation-detail-modal .form-control,
    .reservation-detail-modal .form-select {
        min-height: 46px;
        border-radius: 14px;
        border-color: rgba(148, 163, 184, 0.28);
        background-color: var(--ab-card);
    }
    .reservation-detail-modal textarea.form-control {
        min-height: 92px;
    }
    .reservation-detail-modal .modal-status-actions,
    .reservation-detail-modal .modal-submit-actions {
        min-width: 0;
    }
    .reservation-detail-modal .pax-real-group .btn {
        min-width: 118px;
    }
    .reservation-detail-modal .modal-status-actions .btn {
        border-style: dashed;
    }
    .reservation-detail-modal .modal-submit-actions .btn-primary {
        box-shadow: 0 12px 24px rgba(249, 115, 22, 0.22);
    }
    html[data-theme='dark'] .reservation-modal-summary,
    html[data-theme='dark'] .reservation-modal-profile,
    html[data-theme='dark'] .reservation-modal-editor,
    html[data-theme='dark'] .reservation-form-section,
    html[data-theme='dark'] .reservation-modal-count .mini-stat,
    html[data-theme='dark'] .reservation-profile-stat,
    html[data-theme='dark'] .reservation-profile-note {
        background: rgba(15, 23, 42, 0.48);
    }
    .tematic-operacao-page .btn {
        border-radius: 12px;
        font-weight: 650;
        text-decoration: none !important;
        -webkit-text-fill-color: currentColor;
    }
    .tematic-operacao-page .btn span,
    .tematic-operacao-page .btn i {
        color: inherit !important;
        -webkit-text-fill-color: currentColor !important;
    }
    .tematic-operacao-page .btn.btn-xl {
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
    }
    .tematic-operacao-page .context-actions .context-btn {
        min-height: 38px;
        padding: 0.46rem 0.9rem;
        font-size: 0.9rem;
        line-height: 1.15;
        white-space: nowrap;
    }
    .tematic-operacao-page .context-actions .context-today {
        min-width: 82px;
        justify-content: center;
    }
    .tematic-operacao-page .btn-primary,
    .tematic-operacao-page .btn-primary:link,
    .tematic-operacao-page .btn-primary:visited {
        color: #fff !important;
        -webkit-text-fill-color: #fff !important;
        border-color: #ea580c !important;
        background: linear-gradient(135deg, #f97316 0%, #fb923c 100%) !important;
        box-shadow: 0 8px 20px rgba(249, 115, 22, 0.24);
    }
    .tematic-operacao-page .btn-primary:hover,
    .tematic-operacao-page .btn-primary:focus,
    .tematic-operacao-page .btn-primary:active {
        color: #fff !important;
        border-color: #c2410c !important;
        background: linear-gradient(135deg, #ea580c 0%, #f97316 100%) !important;
    }
    .tematic-operacao-page .btn-outline-primary,
    .tematic-operacao-page .btn-outline-primary:link,
    .tematic-operacao-page .btn-outline-primary:visited {
        color: #9a3412 !important;
        -webkit-text-fill-color: #9a3412 !important;
        border-color: #fb923c !important;
        background: #fff !important;
    }
    .tematic-operacao-page .btn-outline-primary:hover,
    .tematic-operacao-page .btn-outline-primary:focus,
    .tematic-operacao-page .btn-outline-primary:active {
        color: #fff !important;
        -webkit-text-fill-color: #fff !important;
        border-color: #f97316 !important;
        background: linear-gradient(135deg, #f97316 0%, #fb923c 100%) !important;
    }
    .tematic-operacao-page .btn-primary *,
    .tematic-operacao-page .btn-primary:link *,
    .tematic-operacao-page .btn-primary:visited *,
    .tematic-operacao-page .btn-primary:hover *,
    .tematic-operacao-page .btn-primary:focus *,
    .tematic-operacao-page .btn-primary:active *,
    .tematic-operacao-page .btn-outline-primary *,
    .tematic-operacao-page .btn-outline-primary:link *,
    .tematic-operacao-page .btn-outline-primary:visited * {
        color: inherit !important;
        -webkit-text-fill-color: currentColor !important;
    }
    .tematic-operacao-page .btn-outline-primary:disabled,
    .tematic-operacao-page .btn-outline-primary.disabled {
        color: #94a3b8 !important;
        border-color: #cbd5e1 !important;
        background: #f8fafc !important;
    }
    @media (max-width: 768px) {
        .tematic-operacao-page .row {
            margin-left: 0;
            margin-right: 0;
            --bs-gutter-x: 0.8rem;
        }
        .tematic-operacao-page .row > [class*="col-"] {
            min-width: 0;
            padding-left: calc(var(--bs-gutter-x) * 0.5);
            padding-right: calc(var(--bs-gutter-x) * 0.5);
        }
        .tematic-operacao-page .section-block {
            padding: 0.9rem;
        }
        .tematic-operacao-page .collapsible-section-head {
            align-items: stretch;
            flex-direction: column;
        }
        .tematic-operacao-page .section-toggle-btn {
            width: 100%;
            justify-content: center;
        }
        .tematic-operacao-page .operation-hero {
            padding: 0.85rem !important;
            margin-bottom: 0.75rem !important;
            border-radius: 18px;
        }
        .tematic-operacao-page .operation-hero > .d-flex {
            align-items: flex-start !important;
            flex-wrap: wrap !important;
            gap: 0.65rem !important;
        }
        .tematic-operacao-page .operation-hero .section-title {
            align-items: flex-start;
            gap: 0.55rem;
            min-width: 0;
            width: 100%;
        }
        .tematic-operacao-page .operation-hero .section-title .icon {
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
        }
        .tematic-operacao-page .operation-hero h3 {
            font-size: 1.02rem;
            line-height: 1.15;
            margin-bottom: 0 !important;
            white-space: normal;
        }
        .tematic-operacao-page .operation-hero .text-uppercase {
            font-size: 0.62rem;
            line-height: 1.1;
        }
        .tematic-operacao-page .operation-hero-copy {
            display: none;
        }
        .tematic-operacao-page .operation-hero .badge {
            display: none;
        }
        .tematic-operacao-page .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.55rem;
            overflow: visible;
            padding: 0;
        }
        .tematic-operacao-page .summary-grid .saas-stat-card {
            min-height: 72px;
            padding: 0.72rem;
        }
        .tematic-operacao-page .summary-grid .saas-stat-card .small {
            font-size: 0.58rem;
            letter-spacing: 0.03em;
            line-height: 1.15;
        }
        .tematic-operacao-page .summary-grid .saas-stat-value {
            margin-top: 0.2rem;
            font-size: 1.35rem;
            line-height: 1.05;
        }
        .tematic-operacao-page .summary-grid .saas-stat-card:first-child {
            grid-column: 1 / -1;
            min-height: 64px;
        }
        .tematic-operacao-page .section-block .d-flex.flex-wrap.gap-2 {
            min-width: 0;
            max-width: 100%;
        }
        .tematic-operacao-page .section-block .d-flex.flex-wrap.gap-2 > .btn {
            flex: 1 1 100%;
            min-width: 0;
            justify-content: center;
        }
        .tematic-operacao-page .quick-filter-toggle {
            display: inline-flex;
            width: 100%;
        }
        .tematic-operacao-page .quick-advanced-filters.is-collapsed {
            display: none;
        }
        .tematic-operacao-page .quick-advanced-filters .row {
            --bs-gutter-x: 0.65rem;
            --bs-gutter-y: 0.65rem;
        }
        .tematic-operacao-page .table-responsive {
            max-width: 100%;
            overflow-x: auto;
        }
        .tematic-operacao-page .reservation-card-grid {
            grid-template-columns: 1fr;
            gap: 0.48rem;
        }
        .tematic-operacao-page .reservation-op-card {
            border-radius: 15px;
            padding: 0.62rem 0.68rem;
            box-shadow: none;
        }
        .tematic-operacao-page .reservation-op-card:hover {
            transform: none;
        }
        .tematic-operacao-page .reservation-op-head {
            align-items: center;
            gap: 0.45rem;
            margin-bottom: 0.42rem;
        }
        .tematic-operacao-page .reservation-op-title strong {
            font-size: 0.92rem;
        }
        .tematic-operacao-page .reservation-op-title .small {
            display: none;
        }
        .tematic-operacao-page .reservation-op-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            margin-bottom: 0.42rem;
        }
        .tematic-operacao-page .reservation-op-metric {
            display: inline-flex;
            flex: 1 1 28%;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            border-radius: 999px;
            padding: 0.25rem 0.42rem;
        }
        .tematic-operacao-page .reservation-op-metric .label {
            font-size: 0.56rem;
            letter-spacing: 0;
        }
        .tematic-operacao-page .reservation-op-metric .value {
            margin-top: 0;
            font-size: 0.82rem;
        }
        .tematic-operacao-page .reservation-op-foot {
            min-height: 24px;
        }
        .tematic-operacao-page .reservation-status-pill {
            min-height: 24px;
            padding: 0.2rem 0.5rem;
            font-size: 0.7rem;
        }
        .tematic-operacao-page .reservation-op-foot .tag {
            max-width: calc(100% - 28px);
        }
        .tematic-operacao-page .reservation-table-shell {
            border: 0;
            background: transparent;
        }
        .tematic-operacao-page .reservation-list-table,
        .tematic-operacao-page .reservation-list-table thead,
        .tematic-operacao-page .reservation-list-table tbody,
        .tematic-operacao-page .reservation-list-table tr,
        .tematic-operacao-page .reservation-list-table td {
            display: block;
            width: 100%;
        }
        .tematic-operacao-page .reservation-list-table thead {
            display: none;
        }
        .tematic-operacao-page .reservation-list-table tbody tr {
            margin-bottom: 0.75rem;
            border: 1px solid rgba(148, 163, 184, 0.25);
            border-radius: 18px;
            background: var(--ab-card);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }
        .tematic-operacao-page .reservation-list-table tbody td {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border: 0;
            padding: 0.48rem 0.78rem;
            text-align: right;
        }
        .tematic-operacao-page .reservation-list-table tbody td:first-child {
            padding-top: 0.8rem;
        }
        .tematic-operacao-page .reservation-list-table tbody td:last-child {
            padding-bottom: 0.8rem;
        }
        .tematic-operacao-page .reservation-list-table tbody td::before {
            content: attr(data-label);
            flex: 0 0 auto;
            color: var(--ab-muted);
            font-size: 0.72rem;
            font-weight: 750;
            letter-spacing: 0.04em;
            text-align: left;
            text-transform: uppercase;
        }
        .tematic-operacao-page .reservation-list-table tbody td.empty-state {
            justify-content: center;
            text-align: center;
        }
        .tematic-operacao-page .reservation-list-table tbody td.empty-state::before {
            content: none;
        }
        .tematic-operacao-page .reservation-pagination {
            justify-content: center;
            flex-direction: column;
            align-items: stretch;
        }
        .tematic-operacao-page .reservation-pagination-info {
            text-align: center;
        }
        .tematic-operacao-page .reservation-pagination-controls {
            justify-content: center;
        }
        .reservation-detail-modal .modal-footer > div {
            width: 100%;
        }
        .reservation-detail-modal {
            border-radius: 18px;
        }
        .modal-dialog-scrollable .reservation-detail-modal > form {
            max-height: calc(100dvh - 1rem);
        }
        .reservation-detail-modal .modal-header {
            flex: 0 0 auto;
            padding: 0.85rem 0.95rem;
        }
        .reservation-detail-modal .modal-title {
            font-size: 1rem;
        }
        .reservation-modal-subtitle {
            font-size: 0.78rem;
        }
        .reservation-detail-modal .modal-body {
            flex: 1 1 auto;
            overflow-y: auto;
            padding: 0;
        }
        .reservation-detail-modal .modal-footer {
            flex: 0 0 auto;
            position: sticky;
            bottom: 0;
            z-index: 2;
            padding: 0.7rem;
        }
        .reservation-detail-modal .modal-footer .btn {
            flex: 1 1 auto;
            min-height: 38px;
            padding: 0.42rem 0.55rem;
            font-size: 0.82rem;
        }
        .reservation-modal-summary {
            grid-template-columns: 1fr;
            gap: 0.6rem;
            padding: 0.7rem;
            margin-bottom: 0.7rem;
            border-radius: 16px;
        }
        .reservation-modal-count {
            min-width: 0;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .reservation-modal-grid {
            grid-template-columns: 1fr;
            gap: 0.65rem;
        }
        .reservation-modal-layout {
            grid-template-columns: 1fr;
            min-height: 0;
        }
        .reservation-modal-profile {
            border-right: 0;
            border-bottom: 1px solid rgba(148, 163, 184, 0.18);
            padding: 0.9rem;
            gap: 0.75rem;
        }
        .reservation-profile-avatar {
            width: 44px;
            height: 44px;
            flex-basis: 44px;
            border-radius: 15px;
        }
        .reservation-profile-title strong {
            font-size: 1.05rem;
        }
        .reservation-profile-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.5rem;
        }
        .reservation-profile-stat {
            padding: 0.65rem;
            border-radius: 15px;
        }
        .reservation-profile-stat strong {
            font-size: 1.12rem;
        }
        .reservation-profile-note {
            display: none;
        }
        .reservation-modal-editor {
            padding: 0.8rem 0.8rem 1rem;
        }
        .reservation-modal-title strong {
            white-space: normal;
            font-size: 1rem;
        }
        .reservation-modal-title span {
            font-size: 0.78rem;
        }
        .reservation-form-section {
            border-radius: 15px;
            margin-bottom: 0.65rem;
            padding: 0.72rem;
        }
        .reservation-form-section-title {
            margin-bottom: 0.55rem;
            font-size: 0.9rem;
            align-items: flex-start;
        }
        .reservation-form-section-title small {
            font-size: 0.64rem;
            text-align: right;
        }
        .reservation-detail-modal .pax-real-group {
            flex-wrap: nowrap;
        }
        .reservation-detail-modal .pax-real-group .btn {
            min-width: 96px;
            padding-left: 0.45rem;
            padding-right: 0.45rem;
            font-size: 0.78rem;
        }
        .reservation-detail-modal .modal-status-actions,
        .reservation-detail-modal .modal-submit-actions {
            display: grid !important;
            gap: 0.45rem !important;
        }
        .reservation-detail-modal .modal-status-actions {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        .reservation-detail-modal .modal-submit-actions {
            grid-template-columns: minmax(0, 0.75fr) minmax(0, 1.25fr);
        }
        .reservation-detail-modal .modal-status-actions .btn,
        .reservation-detail-modal .modal-submit-actions .btn {
            width: 100%;
            min-width: 0;
            white-space: normal;
            line-height: 1.05;
        }
    }
    .tematic-operacao-page .js-row-clickable {
        cursor: pointer;
    }
    .tematic-operacao-page .js-row-clickable:hover {
        background: rgba(249, 115, 22, 0.08);
    }
</style>

<div class="saas-page tematic-operacao-page">
<div class="card card-soft operation-hero p-4 mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div class="section-title">
            <div class="icon"><i class="bi bi-printer"></i></div>
            <div>
                <div class="text-uppercase text-muted small">Reservas Temáticas</div>
                <h3 class="fw-bold mb-1">Ambiente de Conferência e Impressão</h3>
                <div class="text-muted operation-hero-copy">Consulta operacional para cozinha e liderança, com confirmação rápida de entrada, no-show e cancelamento.</div>
            </div>
        </div>
        <span class="badge badge-soft">Jornada 2</span>
    </div>

</div>

<div class="summary-grid mb-4" aria-label="Resumo das reservas do dia">
    <div class="saas-stat-card">
        <div class="text-muted small">Total</div>
        <div class="saas-stat-value"><?= (int)($summary['total'] ?? count($reservas)) ?></div>
    </div>
    <div class="saas-stat-card">
        <div class="text-muted small">Reservadas</div>
        <div class="saas-stat-value"><?= (int)($summary['reservada'] ?? 0) ?></div>
    </div>
    <div class="saas-stat-card">
        <div class="text-muted small">Finalizadas</div>
        <div class="saas-stat-value status-success"><?= (int)($summary['finalizada'] ?? 0) ?></div>
    </div>
    <div class="saas-stat-card">
        <div class="text-muted small">Não compareceu</div>
        <div class="saas-stat-value status-danger"><?= (int)($summary['nao_compareceu'] ?? 0) ?></div>
    </div>
    <div class="saas-stat-card">
        <div class="text-muted small">Canceladas</div>
        <div class="saas-stat-value"><?= (int)($summary['cancelada'] ?? 0) ?></div>
    </div>
</div>

<div class="section-block collapsible-section mb-4" data-mobile-collapsed="1" id="printKitchenSection">
    <div class="collapsible-section-head">
        <div class="section-title">
            <div class="icon"><i class="bi bi-file-earmark-break"></i></div>
            <div>
                <div class="text-uppercase text-muted small">Impressão para cozinha</div>
                <h5 class="fw-bold mb-0">Reservas com status Reservada por restaurante</h5>
                <div class="text-muted small">Abra apenas quando precisar gerar listas para a cozinha.</div>
            </div>
        </div>
        <button
            type="button"
            class="btn btn-outline-primary section-toggle-btn"
            data-toggle-section="#printKitchenSection"
            data-open-label="Ocultar impressão"
            data-closed-label="Mostrar impressão"
            aria-expanded="true"
        >
            <i class="bi bi-chevron-up me-1"></i><span data-toggle-label>Ocultar impressão</span>
        </button>
    </div>
    <div class="section-collapsed-hint">
        Impressão recolhida para priorizar a conferência das reservas.
    </div>
    <div class="collapsible-section-body">
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($printRestaurantes as $rest): ?>
                <a
                    class="btn btn-outline-primary btn-xl"
                    href="/?r=reservasTematicas/print&tipo=detalhada&data=<?= h($filters['data']) ?>&restaurante_id=<?= h($rest['id']) ?>&turno_id=<?= h($filters['turno_id']) ?>&status=Reservada&order=hora"
                    target="_blank"
                >
                    <i class="bi bi-printer"></i> <?= h($rest['nome']) ?>
                </a>
            <?php endforeach; ?>
            <?php if (!empty($filters['restaurante_id'])): ?>
                <a
                    class="btn btn-primary btn-xl"
                    href="/?r=reservasTematicas/print&tipo=detalhada&data=<?= h($filters['data']) ?>&restaurante_id=<?= h($filters['restaurante_id']) ?>&turno_id=<?= h($filters['turno_id']) ?>&status=Reservada&order=hora"
                    target="_blank"
                >
                    <i class="bi bi-printer-fill"></i> Imprimir Reservadas do restaurante selecionado
                </a>
            <?php endif; ?>
        </div>
        <div class="text-muted small mt-2">
            Esta área imprime apenas reservas em <strong>status Reservada</strong>, organizadas para envio à cozinha.
        </div>
    </div>
</div>

<div class="section-block mb-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-list-ul"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Operação em tempo real</div>
            <h5 class="fw-bold mb-0">Reservas do dia</h5>
            <div class="text-muted small">Filtre, ordene e toque em qualquer reserva para conferir ou ajustar status.</div>
        </div>
    </div>

    <form class="row g-2 mb-3 reservation-filter-panel" method="get" action="/">
        <input type="hidden" name="r" value="reservasTematicas/operacao">
        <div class="col-12 col-md-3">
            <label class="form-label mb-1">Data da operação</label>
            <input type="date" class="form-control" name="data" value="<?= h($filters['data'] ?? date('Y-m-d')) ?>">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label mb-1">Restaurante</label>
            <?php if ($restrictedRestaurant): ?>
                <input type="hidden" name="restaurante_id" value="<?= h($restrictedRestaurant['id']) ?>">
                <div class="form-control d-flex align-items-center">
                    <?= h($restrictedRestaurant['nome']) ?>
                </div>
            <?php else: ?>
                <select class="form-select" name="restaurante_id">
                    <option value="">Todos</option>
                    <?php foreach ($restaurantes as $rest): ?>
                        <option value="<?= (int)$rest['id'] ?>" <?= ((string)($filters['restaurante_id'] ?? '') === (string)$rest['id']) ? 'selected' : '' ?>>
                            <?= h($rest['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label mb-1">Turno</label>
            <select class="form-select" name="turno_id">
                <option value="">Todos</option>
                <?php foreach ($turnos as $turno): ?>
                    <option value="<?= (int)$turno['id'] ?>" <?= ((string)($filters['turno_id'] ?? '') === (string)$turno['id']) ? 'selected' : '' ?>>
                        <?= h($turno['hora']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-3 d-flex align-items-end gap-2 context-actions">
            <button class="btn btn-primary w-100 context-btn">Filtrar</button>
            <a class="btn btn-outline-primary context-btn context-today" href="/?r=reservasTematicas/operacao">Hoje</a>
        </div>
    </form>

    <div class="row g-2 mb-3 reservation-filter-panel">
        <div class="col-12 col-md-4">
            <label class="form-label mb-1">Filtro da tabela rápida</label>
            <input type="text" class="form-control" id="quickLocalFilter" placeholder="Digite nome, UH, turno, restaurante ou status">
        </div>
        <div class="col-12 d-md-none">
            <button type="button" class="btn btn-outline-primary quick-filter-toggle" data-toggle-quick-filters aria-expanded="false">
                <i class="bi bi-sliders"></i>
                <span data-quick-filter-label>Mais filtros</span>
                <i class="bi bi-chevron-down"></i>
            </button>
        </div>
        <div class="col-12 col-md-8 quick-advanced-filters" id="quickAdvancedFilters">
            <div class="row g-2">
                <div class="col-6 col-md-4">
                    <label class="form-label mb-1">Status</label>
                    <select class="form-select" id="quickStatusFilter">
                        <option value="">Todos</option>
                        <?php foreach ($statusOptions as $status): ?>
                            <option value="<?= h($status) ?>"><?= h($labelStatus($status)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-5">
                    <label class="form-label mb-1">Restaurante</label>
                    <select class="form-select" id="quickRestaurantFilter">
                        <option value="">Todos</option>
                        <?php foreach ($restaurantes as $rest): ?>
                            <option value="<?= h(mb_strtolower((string)$rest['nome'], 'UTF-8')) ?>"><?= h($rest['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label mb-1">Ordenação</label>
                    <select class="form-select" id="quickSort">
                        <option value="restaurante">Restaurante (A-Z)</option>
                        <option value="nome">Nome (A-Z)</option>
                        <option value="turno">Turno</option>
                        <option value="status">Status</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="reservation-card-grid" id="quickTableBody">
        <?php foreach ($reservasOrdenadas as $item): ?>
            <?php $status = $normalizeStatus((string)($item['status_reserva'] ?? ($item['status'] ?? ''))); ?>
            <?php
                $titularDisplay = normalize_mojibake((string)($item['titular_nome_display'] ?? $item['titular_nome'] ?? '-'));
                $restDisplay = normalize_mojibake((string)($item['restaurante'] ?? ''));
                $statusDisplay = $labelStatus($status);
                $turnoDisplay = substr((string)($item['turno_hora'] ?? '-'), 0, 5);
            ?>
            <?php
                $searchRow = mb_strtolower(trim(implode(' ', [
                    $titularDisplay,
                    (string)($item['uh_numero'] ?? ''),
                    (string)($item['turno_hora'] ?? ''),
                    (string)$statusDisplay,
                    $restDisplay,
                ])), 'UTF-8');
            ?>
            <article
                class="reservation-op-card js-quick-row js-open-reserva"
                data-search="<?= h($searchRow) ?>"
                data-status="<?= h($status) ?>"
                data-rest="<?= h(mb_strtolower($restDisplay, 'UTF-8')) ?>"
                data-sort-rest="<?= h(mb_strtolower($restDisplay, 'UTF-8')) ?>"
                data-sort-nome="<?= h(mb_strtolower($titularDisplay, 'UTF-8')) ?>"
                data-sort-turno="<?= h((string)($item['turno_hora'] ?? '')) ?>"
                data-sort-status="<?= h(mb_strtolower((string)$statusDisplay, 'UTF-8')) ?>"
                data-id="<?= (int)($item['id'] ?? 0) ?>"
                data-titular="<?= h($titularDisplay) ?>"
                data-uh="<?= h((string)($item['uh_numero'] ?? '')) ?>"
                data-pax="<?= h((string)($item['pax'] ?? 0)) ?>"
                data-pax-real="<?= h((string)($item['pax_real'] ?? '')) ?>"
                data-restaurante-id="<?= (int)($item['restaurante_id'] ?? 0) ?>"
                data-restaurante-nome="<?= h($restDisplay) ?>"
                data-turno-id="<?= (int)($item['turno_id'] ?? 0) ?>"
                data-turno-hora="<?= h((string)($item['turno_hora'] ?? '')) ?>"
                data-status-atual="<?= h($status) ?>"
                data-usuario="<?= h(normalize_mojibake((string)($item['usuario'] ?? ''))) ?>"
                data-obs-operacao="<?= h(normalize_mojibake((string)($item['observacao_operacao'] ?? ''))) ?>"
                role="button"
                tabindex="0"
            >
                <div class="reservation-op-head">
                    <div class="reservation-op-title">
                        <strong><?= h($titularDisplay) ?></strong>
                        <span class="small">Toque para conferir ou alterar status</span>
                    </div>
                    <span class="reservation-status-pill <?= h($statusBadgeClass($status)) ?>"><?= h($statusDisplay) ?></span>
                </div>
                <div class="reservation-op-meta">
                    <div class="reservation-op-metric">
                        <span class="label">UH</span>
                        <span class="value"><span class="uh-badge <?= uh_badge_class($item['uh_numero']) ?>"><?= h($item['uh_numero'] ?? '-') ?></span></span>
                    </div>
                    <div class="reservation-op-metric">
                        <span class="label">PAX</span>
                        <span class="value"><?= h((string)($item['pax'] ?? 0)) ?></span>
                    </div>
                    <div class="reservation-op-metric">
                        <span class="label">Turno</span>
                        <span class="value"><?= h($turnoDisplay) ?></span>
                    </div>
                </div>
                <div class="reservation-op-foot">
                    <span class="tag <?= restaurant_badge_class($restDisplay) ?>"><?= h($restDisplay) ?></span>
                    <span class="text-muted small"><i class="bi bi-chevron-right"></i></span>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (empty($reservasOrdenadas)): ?>
            <div class="reservation-empty-card">Nenhuma reserva encontrada para este período.</div>
        <?php endif; ?>
    </div>
    <div id="quickEmptyState" class="reservation-empty-card mt-2" hidden>Nenhuma reserva encontrada com os filtros aplicados.</div>
    <div id="quickPagination" class="reservation-pagination d-flex flex-wrap gap-2 mt-2"></div>
    <div class="reservation-helper small mt-2"><i class="bi bi-hand-index-thumb"></i> Toque em uma reserva para abrir detalhes e editar restaurante, turno e status.</div>
</div>
<div class="section-block collapsible-section" data-mobile-collapsed="1" id="detailedSection">
    <div class="collapsible-section-head">
        <div class="section-title">
            <div class="icon"><i class="bi bi-clipboard-check"></i></div>
            <div>
                <div class="text-uppercase text-muted small">Conferência</div>
                <h5 class="fw-bold mb-0">Base detalhada do período selecionado</h5>
                <div class="text-muted small">Use quando precisar auditar observações, PAX real e histórico completo.</div>
            </div>
        </div>
        <button type="button" class="btn btn-outline-primary section-toggle-btn" data-toggle-section="#detailedSection" aria-expanded="true">
            <i class="bi bi-chevron-up me-1"></i><span data-toggle-label>Ocultar base</span>
        </button>
    </div>
    <div class="section-collapsed-hint">
        Base detalhada recolhida para manter a operação mais rápida no celular.
    </div>
    <div class="collapsible-section-body">
        <div class="row g-2 mb-3 reservation-filter-panel">
            <div class="col-12 col-md-6">
                <label class="form-label mb-1">Filtro da base detalhada</label>
                <input type="text" class="form-control" id="detailedLocalFilter" placeholder="Nome, UH, observações, turno ou status">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label mb-1">Status</label>
                <select class="form-select" id="detailedStatusFilter">
                    <option value="">Todos</option>
                    <?php foreach ($statusOptions as $status): ?>
                        <option value="<?= h($status) ?>"><?= h($labelStatus($status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label mb-1">Restaurante</label>
                <select class="form-select" id="detailedRestaurantFilter">
                    <option value="">Todos</option>
                    <?php foreach ($restaurantes as $rest): ?>
                        <option value="<?= h(mb_strtolower((string)$rest['nome'], 'UTF-8')) ?>"><?= h($rest['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="table-responsive reservation-table-shell">
            <table class="table table-sm align-middle reservation-list-table" id="detailedTable">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Nome</th>
                        <th>UH</th>
                        <th>PAX reservada</th>
                        <th>PAX real</th>
                        <th>Restaurante</th>
                        <th>Turno</th>
                        <th>Criado por</th>
                        <th>Observação original</th>
                        <th>Observação operacional</th>
                    </tr>
                </thead>
                <tbody id="detailedTableBody">
                    <?php foreach ($reservas as $row): ?>
                        <?php $rowStatus = $normalizeStatus((string)($row['status_reserva'] ?? ($row['status'] ?? ''))); ?>
                        <?php
                            $rowTitular = normalize_mojibake((string)($row['titular_nome_display'] ?? $row['titular_nome'] ?? '-'));
                            $rowRest = normalize_mojibake((string)($row['restaurante'] ?? ''));
                            $rowSearch = mb_strtolower(trim(implode(' ', [
                                (string)$rowTitular,
                                (string)($row['uh_numero'] ?? ''),
                                (string)($row['turno_hora'] ?? ''),
                                (string)$labelStatus($rowStatus),
                                (string)$rowRest,
                                normalize_mojibake((string)($row['usuario'] ?? '')),
                                normalize_mojibake((string)($row['observacao_reserva'] ?? '')),
                                normalize_mojibake((string)($row['observacao_operacao'] ?? '')),
                            ])), 'UTF-8');
                        ?>
                        <tr class="js-detailed-row" data-search="<?= h($rowSearch) ?>" data-status="<?= h($rowStatus) ?>" data-rest="<?= h(mb_strtolower($rowRest, 'UTF-8')) ?>">
                            <td data-label="Status">
                                <span class="reservation-status-pill <?= h($statusBadgeClass($rowStatus)) ?>"><?= h($labelStatus($rowStatus)) ?></span>
                            </td>
                            <td data-label="Nome"><?= h($rowTitular) ?></td>
                            <td data-label="UH"><span class="uh-badge <?= uh_badge_class($row['uh_numero']) ?>"><?= h($row['uh_numero']) ?></span></td>
                            <td data-label="PAX reservada"><?= h((string)($row['pax'] ?? 0)) ?></td>
                            <td data-label="PAX real"><?= h((string)($row['pax_real'] ?? '-')) ?></td>
                            <td data-label="Restaurante"><span class="tag <?= restaurant_badge_class($rowRest) ?>"><?= h($rowRest) ?></span></td>
                            <td data-label="Turno"><span class="tag badge-soft"><?= h($row['turno_hora']) ?></span></td>
                            <td data-label="Criado por"><?= h(normalize_mojibake((string)($row['usuario'] ?? '-'))) ?></td>
                            <td data-label="Obs. original">
                                <?= h(normalize_mojibake((string)($row['observacao_reserva'] ?? '-'))) ?>
                                <?php if (!empty($row['observacao_tags'])): ?>
                                    <div class="text-muted small"><?= h(normalize_mojibake((string)$row['observacao_tags'])) ?></div>
                                <?php endif; ?>
                            </td>
                            <td data-label="Obs. operação"><?= h(normalize_mojibake((string)($row['observacao_operacao'] ?? '-'))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($reservas)): ?>
                        <tr><td colspan="10" class="text-muted empty-state">Nenhuma reserva encontrada para este período.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div id="detailedPagination" class="reservation-pagination d-flex flex-wrap gap-2 mt-2"></div>
    </div>
</div>
</div>

<div class="modal fade" id="reservaDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content reservation-detail-modal">
            <form method="post" action="/?r=reservasTematicas/operacao" id="reservaDetailForm">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="update_detail">
                <input type="hidden" name="id" id="modalReservaId" value="">
                <input type="hidden" name="confirm_final" id="modalConfirmFinal" value="0">
                <div class="modal-header">
                    <div>
                        <div class="reservation-modal-kicker">Operação temática</div>
                        <h5 class="modal-title mb-0">Detalhes da reserva</h5>
                        <div class="reservation-modal-subtitle" id="modalHeaderContext">Conferência e ajustes operacionais</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="reservation-modal-layout">
                        <aside class="reservation-modal-profile">
                            <div class="reservation-profile-top">
                                <div class="reservation-profile-avatar" id="modalAvatarDisplay">--</div>
                                <div class="reservation-profile-title">
                                    <strong id="modalTitularDisplay">-</strong>
                                    <span id="modalContextDisplay">Restaurante e turno</span>
                                </div>
                            </div>
                            <div class="reservation-profile-badges">
                                <span class="uh-badge" id="modalUhDisplay">UH -</span>
                                <span class="reservation-status-pill status-muted" id="modalStatusDisplay">Reservada</span>
                                <span class="tag badge-soft" id="modalUsuarioDisplay">Criado por -</span>
                            </div>
                            <div class="reservation-profile-stats">
                                <div class="reservation-profile-stat">
                                    <span>Reservada</span>
                                    <strong id="modalPaxDisplay">0</strong>
                                </div>
                                <div class="reservation-profile-stat">
                                    <span>Real</span>
                                    <strong id="modalPaxRealDisplay">-</strong>
                                </div>
                            </div>
                            <div class="reservation-profile-note">
                                Qualquer alteração salva aqui fica registrada em auditoria para administração e gerência.
                            </div>
                        </aside>

                        <section class="reservation-modal-editor">
                            <input class="form-control d-none" id="modalTitular" readonly>
                            <input class="form-control d-none" id="modalUh" readonly>
                            <input class="form-control d-none" id="modalPax" readonly>

                            <div class="reservation-editor-grid">
                                <div class="reservation-form-section">
                                    <div class="reservation-form-section-title"><span>Conferência</span><small>PAX e status</small></div>
                                    <div class="row g-2">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">PAX real</label>
                                            <div class="input-group pax-real-group">
                                                <input class="form-control" type="number" min="0" name="pax_real" id="modalPaxReal">
                                                <button class="btn btn-outline-primary" type="button" id="useReservedPaxBtn">Usar reservado</button>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Status</label>
                                            <select class="form-select" name="status" id="modalStatus" required>
                                                <?php foreach ($statusOptions as $status): ?>
                                                    <option value="<?= h($status) ?>"><?= h($labelStatus($status)) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="reservation-form-section">
                                    <div class="reservation-form-section-title"><span>Destino</span><small>Restaurante e turno</small></div>
                                    <div class="row g-2">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Restaurante</label>
                                            <select class="form-select" name="restaurante_id" id="modalRestaurante" required>
                                                <?php foreach ($restaurantes as $rest): ?>
                                                    <option value="<?= (int)$rest['id'] ?>"><?= h($rest['nome']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Turno</label>
                                            <select class="form-select" name="turno_id" id="modalTurno" required>
                                                <?php foreach ($turnos as $turno): ?>
                                                    <option value="<?= (int)$turno['id'] ?>"><?= h($turno['hora']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="reservation-form-section">
                                    <div class="reservation-form-section-title"><span>Observações</span><small>Auditoria</small></div>
                                    <div class="mb-2">
                                        <label class="form-label">Observação operacional</label>
                                        <textarea class="form-control" name="observacao_operacao" id="modalObsOperacao" rows="3"></textarea>
                                    </div>
                                    <div>
                                        <label class="form-label">Justificativa (obrigatória em turno encerrado)</label>
                                        <input class="form-control" type="text" name="justificativa" id="modalJustificativa" placeholder="Descreva o motivo da alteração">
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
                <div class="modal-footer d-flex flex-wrap gap-2 justify-content-between">
                    <div class="d-flex flex-wrap gap-2 modal-status-actions">
                        <button type="button" class="btn btn-outline-primary" data-final-status="Finalizada">Finalizar</button>
                        <button type="button" class="btn btn-outline-primary" data-final-status="Nao compareceu">Não compareceu</button>
                        <button type="button" class="btn btn-outline-primary" data-final-status="Cancelada">Cancelar</button>
                    </div>
                    <div class="d-flex gap-2 modal-submit-actions">
                        <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Fechar</button>
                        <button type="submit" class="btn btn-primary">Salvar alterações</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(() => {
    const normalize = (value) => (value || '')
        .toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
    const isMobileViewport = () => window.matchMedia('(max-width: 768px)').matches;
    const responsivePageSize = (kind) => {
        if (kind === 'quick') return isMobileViewport() ? 5 : 10;
        if (kind === 'detailed') return isMobileViewport() ? 6 : 12;
        return isMobileViewport() ? 5 : 12;
    };
    const setSectionCollapsed = (section, collapsed) => {
        section.classList.toggle('is-collapsed', collapsed);
        const toggle = document.querySelector(`[data-toggle-section="#${section.id}"]`);
        if (!toggle) return;

        toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        const label = toggle.querySelector('[data-toggle-label]');
        if (label) {
            label.textContent = collapsed
                ? (toggle.dataset.closedLabel || 'Mostrar base')
                : (toggle.dataset.openLabel || 'Ocultar base');
        }
    };
    document.querySelectorAll('[data-toggle-section]').forEach((toggle) => {
        const selector = toggle.getAttribute('data-toggle-section');
        const section = selector ? document.querySelector(selector) : null;
        if (!section) return;

        const startCollapsed = section.dataset.mobileCollapsed === '1' && isMobileViewport();
        setSectionCollapsed(section, startCollapsed);
        toggle.addEventListener('click', () => {
            setSectionCollapsed(section, !section.classList.contains('is-collapsed'));
        });
    });
    const quickAdvancedFilters = document.getElementById('quickAdvancedFilters');
    const quickFilterToggle = document.querySelector('[data-toggle-quick-filters]');
    const setQuickFiltersCollapsed = (collapsed) => {
        if (!quickAdvancedFilters || !quickFilterToggle) return;
        quickAdvancedFilters.classList.toggle('is-collapsed', collapsed);
        quickFilterToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        const label = quickFilterToggle.querySelector('[data-quick-filter-label]');
        if (label) label.textContent = collapsed ? 'Mais filtros' : 'Ocultar filtros';
    };
    if (quickAdvancedFilters && quickFilterToggle) {
        setQuickFiltersCollapsed(isMobileViewport());
        quickFilterToggle.addEventListener('click', () => {
            setQuickFiltersCollapsed(!quickAdvancedFilters.classList.contains('is-collapsed'));
        });
    }
    const pageWindow = (current, pages) => {
        if (pages <= 5) return Array.from({ length: pages }, (_, idx) => idx + 1);
        const items = [1];
        const start = Math.max(2, current - 1);
        const end = Math.min(pages - 1, current + 1);
        if (start > 2) items.push('...');
        for (let i = start; i <= end; i++) items.push(i);
        if (end < pages - 1) items.push('...');
        items.push(pages);
        return items;
    };
    const paginateRows = (rows, container, page = 1, perPage = 12) => {
        const total = rows.length;
        const pages = Math.max(1, Math.ceil(total / perPage));
        const current = Math.min(Math.max(1, page), pages);
        const start = (current - 1) * perPage;
        const end = start + perPage;
        rows.forEach((row, idx) => {
            row.style.display = (idx >= start && idx < end) ? '' : 'none';
        });
        if (!container) return current;
        container.innerHTML = '';
        if (total === 0) return current;

        const info = document.createElement('div');
        info.className = 'reservation-pagination-info';
        info.textContent = `Mostrando ${start + 1}-${Math.min(end, total)} de ${total}`;
        container.appendChild(info);

        if (pages <= 1) return current;

        const controls = document.createElement('div');
        controls.className = 'reservation-pagination-controls';

        const createButton = (label, targetPage, active = false, disabled = false) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `btn btn-sm ${active ? 'btn-primary' : 'btn-outline-primary'} js-page-btn`;
            btn.dataset.page = String(targetPage);
            btn.textContent = label;
            btn.disabled = disabled;
            return btn;
        };

        controls.appendChild(createButton('Anterior', current - 1, false, current <= 1));
        pageWindow(current, pages).forEach((item) => {
            if (item === '...') {
                const dots = document.createElement('span');
                dots.className = 'reservation-pagination-dots';
                dots.textContent = '...';
                controls.appendChild(dots);
                return;
            }
            controls.appendChild(createButton(String(item), item, item === current));
        });
        controls.appendChild(createButton('Próxima', current + 1, false, current >= pages));
        container.appendChild(controls);
        return current;
    };

    const quickRows = Array.from(document.querySelectorAll('.js-quick-row'));
    const quickInput = document.getElementById('quickLocalFilter');
    const quickStatus = document.getElementById('quickStatusFilter');
    const quickRestaurant = document.getElementById('quickRestaurantFilter');
    const quickSort = document.getElementById('quickSort');
    const quickPagination = document.getElementById('quickPagination');
    const quickBody = document.getElementById('quickTableBody');
    const quickEmptyState = document.getElementById('quickEmptyState');
    let quickPage = 1;

    const applyQuickFilters = (resetPage = true) => {
        if (resetPage) quickPage = 1;
        const term = normalize(quickInput?.value || '');
        const status = (quickStatus?.value || '').trim();
        const rest = normalize(quickRestaurant?.value || '');
        const sort = quickSort?.value || 'restaurante';

        let filtered = quickRows.filter((row) => {
            const okTerm = !term || normalize(row.dataset.search || '').includes(term);
            const okStatus = !status || (row.dataset.status || '') === status;
            const okRest = !rest || normalize(row.dataset.rest || '') === rest;
            return okTerm && okStatus && okRest;
        });

        const sortKeyMap = {
            restaurante: 'sortRest',
            nome: 'sortNome',
            turno: 'sortTurno',
            status: 'sortStatus',
        };
        const dsKey = sortKeyMap[sort] || 'sortRest';
        filtered.sort((a, b) => {
            const av = normalize(a.dataset[dsKey] || '');
            const bv = normalize(b.dataset[dsKey] || '');
            return av.localeCompare(bv, 'pt-BR');
        });

        if (quickBody) {
            quickRows.forEach((row) => { row.style.display = 'none'; });
            filtered.forEach((row) => quickBody.appendChild(row));
        }
        if (quickEmptyState) {
            quickEmptyState.hidden = filtered.length > 0;
        }
        quickPage = paginateRows(filtered, quickPagination, quickPage, responsivePageSize('quick'));
    };

    quickInput?.addEventListener('input', () => applyQuickFilters(true));
    quickStatus?.addEventListener('change', () => applyQuickFilters(true));
    quickRestaurant?.addEventListener('change', () => applyQuickFilters(true));
    quickSort?.addEventListener('change', () => applyQuickFilters(true));
    quickPagination?.addEventListener('click', (event) => {
        const btn = event.target.closest('.js-page-btn');
        if (!btn) return;
        quickPage = parseInt(btn.dataset.page || '1', 10) || 1;
        applyQuickFilters(false);
    });

    const detailedRows = Array.from(document.querySelectorAll('.js-detailed-row'));
    const detailedInput = document.getElementById('detailedLocalFilter');
    const detailedStatus = document.getElementById('detailedStatusFilter');
    const detailedRestaurant = document.getElementById('detailedRestaurantFilter');
    const detailedPagination = document.getElementById('detailedPagination');
    const detailedBody = document.getElementById('detailedTableBody');
    let detailedPage = 1;

    const applyDetailedFilters = (resetPage = true) => {
        if (resetPage) detailedPage = 1;
        const term = normalize(detailedInput?.value || '');
        const status = (detailedStatus?.value || '').trim();
        const rest = normalize(detailedRestaurant?.value || '');

        const filtered = detailedRows.filter((row) => {
            const okTerm = !term || normalize(row.dataset.search || '').includes(term);
            const okStatus = !status || (row.dataset.status || '') === status;
            const okRest = !rest || normalize(row.dataset.rest || '') === rest;
            return okTerm && okStatus && okRest;
        });

        if (detailedBody) {
            detailedRows.forEach((row) => { row.style.display = 'none'; });
            filtered.forEach((row) => detailedBody.appendChild(row));
        }
        detailedPage = paginateRows(filtered, detailedPagination, detailedPage, responsivePageSize('detailed'));
    };

    detailedInput?.addEventListener('input', () => applyDetailedFilters(true));
    detailedStatus?.addEventListener('change', () => applyDetailedFilters(true));
    detailedRestaurant?.addEventListener('change', () => applyDetailedFilters(true));
    detailedPagination?.addEventListener('click', (event) => {
        const btn = event.target.closest('.js-page-btn');
        if (!btn) return;
        detailedPage = parseInt(btn.dataset.page || '1', 10) || 1;
        applyDetailedFilters(false);
    });

    let resizeTimer = null;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            applyQuickFilters(false);
            applyDetailedFilters(false);
        }, 160);
    });

    const modalEl = document.getElementById('reservaDetailModal');
    let modal = null;
    const getModal = () => {
        if (!modalEl) return null;
        if (!modal && window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            modal = new window.bootstrap.Modal(modalEl);
        }
        return modal;
    };
    const modalId = document.getElementById('modalReservaId');
    const modalTitular = document.getElementById('modalTitular');
    const modalUh = document.getElementById('modalUh');
    const modalPax = document.getElementById('modalPax');
    const modalPaxReal = document.getElementById('modalPaxReal');
    const modalStatus = document.getElementById('modalStatus');
    const modalRest = document.getElementById('modalRestaurante');
    const modalTurno = document.getElementById('modalTurno');
    const modalObs = document.getElementById('modalObsOperacao');
    const modalConfirmFinal = document.getElementById('modalConfirmFinal');
    const modalTitularDisplay = document.getElementById('modalTitularDisplay');
    const modalContextDisplay = document.getElementById('modalContextDisplay');
    const modalUhDisplay = document.getElementById('modalUhDisplay');
    const modalStatusDisplay = document.getElementById('modalStatusDisplay');
    const modalPaxDisplay = document.getElementById('modalPaxDisplay');
    const modalPaxRealDisplay = document.getElementById('modalPaxRealDisplay');
    const modalUsuarioDisplay = document.getElementById('modalUsuarioDisplay');
    const modalHeaderContext = document.getElementById('modalHeaderContext');
    const modalAvatarDisplay = document.getElementById('modalAvatarDisplay');
    const useReservedPaxBtn = document.getElementById('useReservedPaxBtn');
    const detailForm = document.getElementById('reservaDetailForm');

    const statusLabelMap = {
        Reservada: 'Reservada',
        Finalizada: 'Finalizada',
        'Nao compareceu': 'Não compareceu',
        Cancelada: 'Cancelada',
        Divergencia: 'Divergência',
    };
    const statusClassMap = {
        Reservada: 'status-reserved',
        Finalizada: 'status-finished',
        'Nao compareceu': 'status-noshow',
        Cancelada: 'status-canceled',
        Divergencia: 'status-warning',
    };
    const syncModalSummary = () => {
        const status = modalStatus?.value || 'Reservada';
        const paxReal = modalPaxReal?.value || '';
        const titular = modalTitular?.value || '-';
        if (modalTitularDisplay) modalTitularDisplay.textContent = titular;
        if (modalAvatarDisplay) {
            const initials = titular
                .split(/\s+/)
                .filter(Boolean)
                .slice(0, 2)
                .map((part) => part.charAt(0).toUpperCase())
                .join('');
            modalAvatarDisplay.textContent = initials || '--';
        }
        if (modalContextDisplay) {
            const restText = modalRest?.selectedOptions?.[0]?.textContent?.trim() || '-';
            const turnoText = modalTurno?.selectedOptions?.[0]?.textContent?.trim() || '-';
            modalContextDisplay.textContent = `${restText} · ${turnoText}`;
            if (modalHeaderContext) modalHeaderContext.textContent = `${restText} · ${turnoText}`;
        }
        if (modalUhDisplay) modalUhDisplay.textContent = `UH ${modalUh?.value || '-'}`;
        if (modalPaxDisplay) modalPaxDisplay.textContent = modalPax?.value || '0';
        if (modalPaxRealDisplay) modalPaxRealDisplay.textContent = paxReal !== '' ? paxReal : '-';
        if (modalUsuarioDisplay) modalUsuarioDisplay.textContent = `Criado por ${modalUsuarioDisplay.dataset.usuario || '-'}`;
        if (modalStatusDisplay) {
            modalStatusDisplay.textContent = statusLabelMap[status] || status;
            modalStatusDisplay.className = `reservation-status-pill ${statusClassMap[status] || 'status-muted'}`;
        }
    };

    document.querySelectorAll('.js-open-reserva').forEach((row) => {
        row.classList.add('js-row-clickable');
        const openReserva = () => {
            const modalInstance = getModal();
            if (!modalInstance) return;
            modalId.value = row.dataset.id || '';
            modalTitular.value = row.dataset.titular || '-';
            modalUh.value = row.dataset.uh || '-';
            modalPax.value = row.dataset.pax || '0';
            modalPaxReal.value = row.dataset.paxReal || '';
            modalStatus.value = row.dataset.statusAtual || 'Reservada';
            modalRest.value = row.dataset.restauranteId || '';
            modalTurno.value = row.dataset.turnoId || '';
            modalObs.value = row.dataset.obsOperacao || '';
            if (modalUsuarioDisplay) modalUsuarioDisplay.dataset.usuario = row.dataset.usuario || '-';
            modalConfirmFinal.value = '0';
            syncModalSummary();
            modalInstance.show();
        };
        row.addEventListener('click', openReserva);
        row.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            openReserva();
        });
    });

    [modalStatus, modalRest, modalTurno, modalPaxReal].forEach((el) => {
        el?.addEventListener('change', syncModalSummary);
        el?.addEventListener('input', syncModalSummary);
    });
    useReservedPaxBtn?.addEventListener('click', () => {
        if (!modalPaxReal || !modalPax) return;
        modalPaxReal.value = modalPax.value || '0';
        syncModalSummary();
        modalPaxReal.focus();
    });

    detailForm?.addEventListener('submit', () => {
        const status = modalStatus?.value || '';
        if (['Finalizada', 'Nao compareceu', 'Cancelada'].includes(status)) {
            modalConfirmFinal.value = '1';
        } else {
            modalConfirmFinal.value = '0';
        }
    });

    document.querySelectorAll('[data-final-status]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const finalStatus = btn.getAttribute('data-final-status') || 'Finalizada';
            if (modalStatus) modalStatus.value = finalStatus;
            if (modalConfirmFinal) modalConfirmFinal.value = '1';
            detailForm?.requestSubmit();
        });
    });

    applyQuickFilters(true);
    applyDetailedFilters(true);
})();
</script>
