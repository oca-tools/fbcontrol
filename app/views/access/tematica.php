<?php
$flash = $this->data['flash'] ?? null;
$turno = $this->data['turno'] ?? null;
$restOp = $this->data['restOp'] ?? null;
$reservas = $this->data['reservas'] ?? [];
$filters = $this->data['filters'] ?? [];
$toleranceAlert = $this->data['tolerance_alert'] ?? null;
$canCancel = (bool)($this->data['can_cancel'] ?? false);

$finalStatuses = ['Finalizada', 'Nao compareceu', 'Cancelada'];
$statusOptions = ['', 'Reservada', 'Finalizada', 'Nao compareceu', 'Cancelada', 'Divergencia', 'Excedente'];
$statusLabels = [
    '' => 'Todos',
    'Reservada' => 'Reservada',
    'Finalizada' => 'Finalizada',
    'Nao compareceu' => 'Não compareceu',
    'Cancelada' => 'Cancelada',
    'Divergencia' => 'Divergência',
    'Excedente' => 'Excedente',
];

$normalizeStatus = static function (?string $status): string {
    $status = normalize_mojibake(trim((string)$status));
    $map = [
        'Nao compareceu' => 'Nao compareceu',
        'Não compareceu' => 'Nao compareceu',
        'Não compareceu' => 'Nao compareceu',
        'Não compareceu' => 'Nao compareceu',
        'Divergencia' => 'Divergencia',
        'Divergência' => 'Divergencia',
        'Divergência' => 'Divergencia',
        'Divergência' => 'Divergencia',
        'Conferida' => 'Reservada',
        'Em atendimento' => 'Reservada',
    ];
    return $map[$status] ?? $status;
};
$statusLabel = static function (?string $status) use ($normalizeStatus, $statusLabels): string {
    $canon = $normalizeStatus($status);
    return $statusLabels[$canon] ?? $canon;
};
$rowStatus = static function (?string $status) use ($normalizeStatus): string {
    $canon = $normalizeStatus($status);
    return $canon !== '' ? $canon : 'Reservada';
};
$statusBadgeClass = static function (?string $status) use ($normalizeStatus): string {
    $canon = $normalizeStatus($status);
    if ($canon === 'Finalizada') {
        return 'badge-success';
    }
    if ($canon === 'Nao compareceu' || $canon === 'Cancelada') {
        return 'badge-warning';
    }
    if ($canon === 'Divergencia') {
        return 'badge-danger';
    }
    return 'badge-soft';
};
$rowHorario = static function ($hora): string {
    $hora = trim((string)$hora);
    if ($hora === '') {
        return '--:--';
    }
    return substr($hora, 0, 5);
};
?>

<style>
    .tematica-search-results {
        display: grid;
        gap: 0.65rem;
        margin-top: 0.75rem;
    }
    .tematica-suggestion {
        width: 100%;
        border: 1px solid var(--ab-border);
        border-radius: 16px;
        background: var(--ab-card);
        padding: 0.8rem 0.9rem;
        text-align: left;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        box-shadow: var(--ab-shadow-soft);
    }
    .tematica-suggestion:hover,
    .tematica-suggestion:focus {
        border-color: color-mix(in srgb, var(--ab-primary) 56%, var(--ab-border));
        outline: 0;
    }
    .tematica-suggestion.is-final {
        opacity: 0.72;
    }
    .tematica-suggestion-main {
        min-width: 0;
    }
    .tematica-suggestion-meta {
        color: var(--ab-muted);
        font-size: 0.86rem;
        white-space: nowrap;
    }
    .tematica-day-support .table-responsive {
        max-height: 70vh;
    }
    .tematica-details-wrap {
        position: fixed;
        inset: 0;
        z-index: 1350;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 16px;
        background: rgba(2, 6, 23, 0.38);
        backdrop-filter: blur(3px);
    }
    .tematica-details-wrap.is-open {
        display: flex;
    }
    .tematica-details-modal {
        width: min(94vw, 560px);
        border-radius: 22px;
        background: var(--ab-card);
        color: var(--ab-ink);
        border: 1px solid var(--ab-border);
        box-shadow: 0 30px 90px rgba(15, 23, 42, 0.34);
        padding: 1.1rem;
    }
    .tematica-details-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .tematica-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.65rem;
        margin-bottom: 1rem;
    }
    .tematica-detail-item {
        border: 1px solid var(--ab-border);
        border-radius: 14px;
        padding: 0.65rem 0.75rem;
        background: color-mix(in srgb, var(--ab-card) 94%, var(--ab-bg) 6%);
    }
    .tematica-detail-item span {
        display: block;
        color: var(--ab-muted);
        font-size: 0.74rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    .tematica-detail-item strong {
        display: block;
        margin-top: 0.1rem;
    }
    .tematica-mobile-meta {
        display: none;
    }
    @media (max-width: 767.98px) {
        .tematica-day-support .table-responsive {
            max-height: none !important;
            overflow: visible;
        }
        .tematica-day-support table {
            min-width: 0;
            display: block;
        }
        .tematica-day-support thead {
            display: none;
        }
        .tematica-day-support tbody {
            display: grid;
            gap: 0.75rem;
        }
        .tematica-day-support .js-reserva-row {
            cursor: pointer;
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.55rem 0.75rem;
            border: 1px solid var(--ab-border);
            border-radius: 14px;
            padding: 0.75rem;
            background: var(--ab-card);
        }
        .tematica-day-support .js-reserva-row td {
            border: 0;
            padding: 0 !important;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            min-width: 0;
        }
        .tematica-day-support .js-reserva-row td::before {
            content: attr(data-label);
            color: var(--ab-muted);
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            flex: 0 0 auto;
        }
        .tematica-day-support .js-reserva-row td:first-child {
            grid-column: 1 / -1;
            display: grid;
            justify-content: stretch;
            align-items: start;
            gap: 0.6rem;
        }
        .tematica-day-support .js-reserva-row td:nth-child(2),
        .tematica-day-support .js-reserva-row td:nth-child(3),
        .tematica-day-support .js-reserva-row td:nth-child(4) {
            display: none;
        }
        .tematica-mobile-meta {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.45rem;
            width: 100%;
        }
        .tematica-mobile-meta span {
            border: 1px solid var(--ab-border);
            border-radius: 12px;
            padding: 0.45rem 0.5rem;
            background: color-mix(in srgb, var(--ab-card) 92%, var(--ab-bg) 8%);
            color: var(--ab-ink);
            font-size: 0.86rem;
            font-weight: 700;
            min-width: 0;
        }
        .tematica-mobile-meta small {
            display: block;
            color: var(--ab-muted);
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 0.1rem;
        }
        .tematica-day-support .js-reserva-row td:first-child::before,
        .tematica-day-support .js-reserva-row td:last-child::before {
            content: "";
            display: none;
        }
        .tematica-day-support .js-reserva-row .badge,
        .tematica-day-support .js-reserva-row .tag {
            white-space: nowrap;
        }
        .tematica-day-support .js-select-reserva {
            display: none;
        }
        .tematica-detail-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="row g-4">
    <div class="col-12 col-lg-7">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="text-uppercase text-muted small">Registro Temático</div>
                    <h3 class="fw-bold mb-1">
                        <span class="tag <?= restaurant_badge_class($turno['restaurante'] ?? '') ?>"><?= h($turno['restaurante'] ?? 'Restaurante') ?></span>
                    </h3>
                    <div class="text-muted">Operação: <span class="tag <?= operation_badge_class($turno['operacao'] ?? '') ?>"><?= h($turno['operacao'] ?? 'Temático') ?></span></div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <form method="post" action="/?r=turnos/end">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <button class="btn btn-outline-danger" data-confirm="Confirma encerramento do turno?" data-confirm-title="Encerrar turno" data-confirm-type="danger">
                            <i class="bi bi-box-arrow-right me-1"></i>Encerrar turno
                        </button>
                    </form>
                    <?php if ($canCancel): ?>
                        <form method="post" action="/?r=turnos/cancel">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                            <button class="btn btn-outline-secondary" data-confirm="Confirma cancelamento do turno sem registros?" data-confirm-title="Cancelar turno">
                                <i class="bi bi-x-circle me-1"></i>Cancelar turno
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
            <?php endif; ?>
            <?php if ($toleranceAlert): ?>
                <div class="alert alert-warning"><?= h($toleranceAlert) ?></div>
            <?php endif; ?>

            <form method="get" action="/" class="row g-3 align-items-end mb-3">
                <input type="hidden" name="r" value="access/index">
                <div class="col-12 col-md-4">
                    <label class="form-label">Data</label>
                    <input type="date" class="form-control input-xl" name="data" value="<?= h($filters['data'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select input-xl" name="status">
                        <?php foreach ($statusOptions as $st): ?>
                            <option value="<?= h($st) ?>" <?= $normalizeStatus((string)($filters['status'] ?? '')) === $st ? 'selected' : '' ?>>
                                <?= h($statusLabels[$st] ?? $st) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4 d-flex gap-2">
                    <button class="btn btn-primary btn-xl w-100">Aplicar</button>
                    <a class="btn btn-primary btn-xl w-100" href="/?r=access/index">Hoje</a>
                </div>
            </form>

            <div class="mb-3">
                <label class="form-label">Localizar reserva por UH</label>
                <input type="text" class="form-control input-xl" id="filterUh" inputmode="numeric" autocomplete="off" placeholder="Digite a UH...">
                <div class="tematica-search-results" id="tematicaSearchResults"></div>
            </div>

            <div class="card p-3 bg-light border-0 mb-3" id="selectedReservaCard">
                <div class="text-uppercase text-muted small mb-1">Reserva selecionada</div>
                <div class="fw-semibold" id="selectedTitular">Nenhuma reserva selecionada.</div>
                <div class="small text-muted mt-1" id="selectedDetails">Selecione na lista para confirmar entrada ou marcar não comparecimento.</div>
            </div>

            <form method="post" action="/?r=access/register_tematica" id="formTematicaAction">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="reserva_id" id="selectedReservaId" value="">
                <input type="hidden" name="acao_tematica" id="selectedAction" value="">
                <input type="hidden" name="data_ref" value="<?= h($filters['data'] ?? date('Y-m-d')) ?>">
                <input type="hidden" name="q" value="<?= h($filters['q'] ?? '') ?>">
                <input type="hidden" name="status" value="<?= h($filters['status'] ?? '') ?>">

                <div class="row g-2">
                    <div class="col-12 col-md-4">
                        <label class="form-label">PAX real</label>
                        <input type="number" min="0" class="form-control input-xl" name="pax_real" id="selectedPaxReal" value="">
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label">Observação operacional</label>
                        <input type="text" class="form-control input-xl" name="observacao_operacao" placeholder="Opcional">
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-success btn-xl flex-grow-1" id="btnConfirmarReserva" disabled>
                            <i class="bi bi-check2-circle me-1"></i>Confirmar entrada (Finalizada)
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-xl flex-grow-1" id="btnCancelarReserva" disabled>
                            <i class="bi bi-person-x me-1"></i>Não compareceu (manual)
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card p-4 tematica-day-support">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0">Reservas do dia</h4>
                <span class="text-muted small"><?= count($reservas) ?> item(ns)</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>UH</th>
                            <th>Horário</th>
                            <th>PAX</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="reservasTableBody">
                        <?php foreach ($reservas as $row): ?>
                            <?php
                                $statusRaw = (string)($row['status_reserva'] ?? ($row['status'] ?? ''));
                                $canonStatus = $rowStatus($statusRaw);
                                $statusText = $statusLabel($canonStatus);
                                $horarioText = $rowHorario($row['turno_hora'] ?? '');
                                $isFinal = in_array($canonStatus, $finalStatuses, true);
                                $searchText = mb_strtolower(
                                    normalize_mojibake(trim((string)($row['uh_numero'] ?? ''))),
                                    'UTF-8'
                                );
                            ?>
                            <tr class="js-reserva-row"
                                data-search="<?= h($searchText) ?>"
                                data-id="<?= (int)$row['id'] ?>"
                                data-titular="<?= h((string)($row['titular_nome'] ?? '-')) ?>"
                                data-uh="<?= h((string)($row['uh_numero'] ?? '-')) ?>"
                                data-pax="<?= (int)($row['pax'] ?? 0) ?>"
                                data-turno="<?= h($horarioText) ?>"
                                data-status="<?= h($canonStatus) ?>"
                                data-status-label="<?= h($statusText) ?>"
                                data-final="<?= $isFinal ? '1' : '0' ?>">
                                <td data-label="UH">
                                    <button type="button" class="btn p-0 border-0 bg-transparent js-open-reserva-details"><span class="uh-badge <?= uh_badge_class((string)($row['uh_numero'] ?? '')) ?>"><?= h(uh_label((string)($row['uh_numero'] ?? '-'))) ?></span></button>
                                    <div class="tematica-mobile-meta" aria-hidden="true">
                                        <span><small>Horário</small><?= h($horarioText) ?></span>
                                        <span><small>PAX</small><?= (int)($row['pax'] ?? 0) ?></span>
                                        <span><small>Status</small><?= h($statusText) ?></span>
                                    </div>
                                </td>
                                <td data-label="Horário"><span class="tag badge-soft"><?= h($horarioText) ?></span></td>
                                <td data-label="PAX"><?= (int)($row['pax'] ?? 0) ?></td>
                                <td data-label="Status"><span class="badge <?= h($statusBadgeClass($canonStatus)) ?>"><?= h($statusText) ?></span></td>
                                <td>
                                    <button type="button" class="btn btn-outline-primary btn-sm js-select-reserva" <?= $isFinal ? 'disabled' : '' ?>>
                                        Selecionar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($reservas)): ?>
                            <tr><td colspan="5" class="text-muted">Nenhuma reserva encontrada.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="tematica-details-wrap" id="tematicaDetailsWrap" aria-hidden="true">
    <div class="tematica-details-modal" role="dialog" aria-modal="true" aria-labelledby="tematicaDetailsTitle">
        <div class="tematica-details-header">
            <div>
                <div class="text-uppercase text-muted small">Reserva temática</div>
                <h5 class="fw-bold mb-1" id="tematicaDetailsTitle">UH</h5>
                <div class="text-muted small" id="tematicaDetailsSubtitle"></div>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="tematicaDetailsClose" aria-label="Fechar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="tematica-detail-grid">
            <div class="tematica-detail-item"><span>PAX</span><strong id="tematicaDetailsPax">-</strong></div>
            <div class="tematica-detail-item"><span>Turno</span><strong id="tematicaDetailsTurno">-</strong></div>
            <div class="tematica-detail-item"><span>Status</span><strong id="tematicaDetailsStatus">-</strong></div>
            <div class="tematica-detail-item"><span>Titular</span><strong id="tematicaDetailsTitular">-</strong></div>
        </div>
        <div class="row g-2">
            <div class="col-12 col-md-4">
                <label class="form-label">PAX real</label>
                <input type="number" min="0" class="form-control input-xl" id="modalPaxReal">
            </div>
            <div class="col-12 col-md-8">
                <label class="form-label">Observação operacional</label>
                <input type="text" class="form-control input-xl" id="modalObservacaoOperacao" placeholder="Opcional">
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <button type="button" class="btn btn-success btn-xl flex-grow-1" id="modalConfirmarReserva">
                <i class="bi bi-check2-circle me-1"></i>Confirmar entrada
            </button>
            <button type="button" class="btn btn-outline-danger btn-xl flex-grow-1" id="modalCancelarReserva">
                <i class="bi bi-person-x me-1"></i>Não compareceu
            </button>
        </div>
    </div>
</div>

<script>
(() => {
    const filterInput = document.getElementById('filterUh');
    const suggestionsWrap = document.getElementById('tematicaSearchResults');
    const rows = Array.from(document.querySelectorAll('.js-reserva-row'));
    const btnSelect = document.querySelectorAll('.js-select-reserva');
    const selectedId = document.getElementById('selectedReservaId');
    const selectedAction = document.getElementById('selectedAction');
    const selectedTitular = document.getElementById('selectedTitular');
    const selectedDetails = document.getElementById('selectedDetails');
    const selectedPaxReal = document.getElementById('selectedPaxReal');
    const btnConfirmar = document.getElementById('btnConfirmarReserva');
    const btnCancelar = document.getElementById('btnCancelarReserva');
    const form = document.getElementById('formTematicaAction');
    const formObs = form?.querySelector('input[name="observacao_operacao"]');
    const detailsWrap = document.getElementById('tematicaDetailsWrap');
    const detailsClose = document.getElementById('tematicaDetailsClose');
    const detailsTitle = document.getElementById('tematicaDetailsTitle');
    const detailsSubtitle = document.getElementById('tematicaDetailsSubtitle');
    const detailsPax = document.getElementById('tematicaDetailsPax');
    const detailsTurno = document.getElementById('tematicaDetailsTurno');
    const detailsStatus = document.getElementById('tematicaDetailsStatus');
    const detailsTitular = document.getElementById('tematicaDetailsTitular');
    const modalPaxReal = document.getElementById('modalPaxReal');
    const modalObs = document.getElementById('modalObservacaoOperacao');
    const modalConfirmar = document.getElementById('modalConfirmarReserva');
    const modalCancelar = document.getElementById('modalCancelarReserva');
    let activeModalRow = null;

    const rowData = (row) => {
        const pax = parseInt(row.getAttribute('data-pax') || '0', 10);
        return {
            id: row.getAttribute('data-id') || '',
            titular: row.getAttribute('data-titular') || '-',
            uh: row.getAttribute('data-uh') || '-',
            pax: Number.isInteger(pax) ? pax : 0,
            turno: row.getAttribute('data-turno') || '--:--',
            status: row.getAttribute('data-status') || '',
            statusLabel: row.getAttribute('data-status-label') || '-',
            isFinal: row.getAttribute('data-final') === '1'
        };
    };

    const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));

    const selectReserva = (row, options = {}) => {
        if (!row) return;
        const data = rowData(row);
        selectedId.value = data.id;
        selectedTitular.textContent = `UH ${data.uh}`;
        selectedDetails.textContent = `UH ${data.uh} | PAX ${data.pax} | Turno ${data.turno} | Status ${data.statusLabel}`;
        selectedPaxReal.value = String(data.pax);
        selectedPaxReal.max = String(data.pax);
        btnConfirmar.disabled = data.isFinal;
        btnCancelar.disabled = data.isFinal;
        rows.forEach((r) => r.classList.remove('table-active'));
        row.classList.add('table-active');
        if (options.scroll) {
            document.getElementById('selectedReservaCard')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    };

    const renderSuggestions = () => {
        if (!suggestionsWrap) return;
        const q = (filterInput?.value || '').trim().toLowerCase();
        suggestionsWrap.innerHTML = '';
        if (q === '') return;

        const matches = rows
            .filter((row) => (row.getAttribute('data-uh') || '').toLowerCase().startsWith(q))
            .slice(0, 8);

        if (matches.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'text-muted small';
            empty.textContent = 'Nenhuma reserva encontrada para esta UH.';
            suggestionsWrap.appendChild(empty);
            return;
        }

        matches.forEach((row) => {
            const data = rowData(row);
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'tematica-suggestion' + (data.isFinal ? ' is-final' : '');
            button.innerHTML =
                '<span class="tematica-suggestion-main">' +
                    '<span class="uh-badge">' + escapeHtml(data.uh) + '</span> ' +
                    '<strong>' + escapeHtml(data.titular) + '</strong>' +
                    '<span class="d-block text-muted small">PAX ' + escapeHtml(data.pax) + ' | Turno ' + escapeHtml(data.turno) + ' | ' + escapeHtml(data.statusLabel) + '</span>' +
                '</span>' +
                '<span class="tematica-suggestion-meta">' + escapeHtml(data.statusLabel) + '</span>';
            button.addEventListener('click', () => {
                selectReserva(row, { scroll: false });
                openDetails(row);
            });
            suggestionsWrap.appendChild(button);
        });
    };

    filterInput?.addEventListener('input', renderSuggestions);
    renderSuggestions();

    btnSelect.forEach((btn) => {
        btn.addEventListener('click', () => selectReserva(btn.closest('.js-reserva-row'), { scroll: true }));
    });

    const openDetails = (row) => {
        if (!row || !detailsWrap) return;
        const data = rowData(row);
        activeModalRow = row;
        selectReserva(row);
        if (detailsTitle) detailsTitle.textContent = `UH ${data.uh}`;
        if (detailsSubtitle) detailsSubtitle.textContent = data.titular;
        if (detailsPax) detailsPax.textContent = String(data.pax);
        if (detailsTurno) detailsTurno.textContent = data.turno;
        if (detailsStatus) detailsStatus.textContent = data.statusLabel;
        if (detailsTitular) detailsTitular.textContent = data.titular;
        if (modalPaxReal) {
            modalPaxReal.value = String(data.pax);
            modalPaxReal.max = String(data.pax);
            modalPaxReal.disabled = data.isFinal;
        }
        if (modalObs) {
            modalObs.value = formObs?.value || '';
            modalObs.disabled = data.isFinal;
        }
        if (modalConfirmar) modalConfirmar.disabled = data.isFinal;
        if (modalCancelar) modalCancelar.disabled = data.isFinal;
        detailsWrap.classList.add('is-open');
        detailsWrap.setAttribute('aria-hidden', 'false');
    };

    const closeDetails = () => {
        detailsWrap?.classList.remove('is-open');
        detailsWrap?.setAttribute('aria-hidden', 'true');
        activeModalRow = null;
    };

    document.querySelectorAll('.js-open-reserva-details').forEach((btn) => {
        btn.addEventListener('click', () => openDetails(btn.closest('.js-reserva-row')));
    });
    rows.forEach((row) => {
        row.addEventListener('click', (event) => {
            if (!window.matchMedia('(max-width: 767.98px)').matches) return;
            if (event.target.closest('.js-select-reserva')) return;
            openDetails(row);
        });
    });
    detailsClose?.addEventListener('click', closeDetails);
    detailsWrap?.addEventListener('click', (event) => {
        if (event.target === detailsWrap) closeDetails();
    });

    const submitAction = async (actionName, confirmMsg, confirmType = 'default') => {
        if (!selectedId.value) return;
        let ok = true;
        if (typeof window.ocafConfirm === 'function') {
            ok = await window.ocafConfirm({
                title: 'Confirmar ação',
                message: confirmMsg,
                confirmText: 'Confirmar',
                cancelText: 'Cancelar',
                type: confirmType
            });
        }
        if (!ok) return;
        selectedAction.value = actionName;
        closeDetails();
        if (suggestionsWrap) suggestionsWrap.innerHTML = '';
        if (filterInput) filterInput.value = '';
        selectedTitular.textContent = 'Atualizando reserva...';
        selectedDetails.textContent = 'Aguarde, a página será atualizada.';
        btnConfirmar.disabled = true;
        btnCancelar.disabled = true;
        if (modalConfirmar) modalConfirmar.disabled = true;
        if (modalCancelar) modalCancelar.disabled = true;
        form.submit();
    };

    const submitModalAction = (actionName, confirmMsg, confirmType = 'default') => {
        if (activeModalRow) selectReserva(activeModalRow);
        if (modalPaxReal && selectedPaxReal) {
            selectedPaxReal.value = modalPaxReal.value;
        }
        if (modalObs && formObs) {
            formObs.value = modalObs.value;
        }
        submitAction(actionName, confirmMsg, confirmType);
    };

    btnConfirmar?.addEventListener('click', () => {
        submitAction('confirmar', 'Confirmar esta reserva como Finalizada?');
    });
    btnCancelar?.addEventListener('click', () => {
        submitAction('cancelar', 'Marcar esta reserva como Não compareceu?', 'danger');
    });
    modalConfirmar?.addEventListener('click', () => {
        submitModalAction('confirmar', 'Confirmar esta reserva como Finalizada?');
    });
    modalCancelar?.addEventListener('click', () => {
        submitModalAction('cancelar', 'Marcar esta reserva como Não compareceu?', 'danger');
    });
})();
</script>
