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
?>

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
                <label class="form-label">Buscar UH (filtro instantâneo)</label>
                <input type="text" class="form-control input-xl" id="filterUh" placeholder="Digite a UH...">
                <div class="small text-muted mt-1">A lista abaixo filtra automaticamente pelo número da UH.</div>
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
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0">Reservas do dia</h4>
                <span class="text-muted small"><?= count($reservas) ?> item(ns)</span>
            </div>
            <div class="table-responsive" style="max-height:70vh;">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>UH</th>
                            <th>PAX</th>
                            <th>Turno</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="reservasTableBody">
                        <?php foreach ($reservas as $row): ?>
                            <?php
                                $canonStatus = $normalizeStatus((string)($row['status'] ?? ''));
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
                                data-turno="<?= h((string)($row['turno_hora'] ?? '--:--')) ?>"
                                data-status="<?= h($canonStatus) ?>"
                                data-status-label="<?= h($statusLabel($canonStatus)) ?>">
                                <td><span class="uh-badge <?= uh_badge_class((string)($row['uh_numero'] ?? '')) ?>"><?= h(uh_label((string)($row['uh_numero'] ?? '-'))) ?></span></td>
                                <td><?= (int)($row['pax'] ?? 0) ?></td>
                                <td><span class="tag badge-soft"><?= h($row['turno_hora'] ?? '-') ?></span></td>
                                <td><span class="badge badge-soft"><?= h($statusLabel($canonStatus)) ?></span></td>
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

<script>
(() => {
    const filterInput = document.getElementById('filterUh');
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

    const applyFilter = () => {
        const q = (filterInput?.value || '').trim().toLowerCase();
        rows.forEach((row) => {
            const text = (row.getAttribute('data-search') || '').toLowerCase();
            row.style.display = (q === '' || text.includes(q)) ? '' : 'none';
        });
    };

    filterInput?.addEventListener('input', applyFilter);
    applyFilter();

    btnSelect.forEach((btn) => {
        btn.addEventListener('click', () => {
            const row = btn.closest('.js-reserva-row');
            if (!row) return;
            const id = row.getAttribute('data-id') || '';
            const uh = row.getAttribute('data-uh') || '-';
            const pax = parseInt(row.getAttribute('data-pax') || '0', 10);
            const turno = row.getAttribute('data-turno') || '--:--';
            const statusLabel = row.getAttribute('data-status-label') || '-';

            selectedId.value = id;
            selectedTitular.textContent = `UH ${uh}`;
            selectedDetails.textContent = `UH ${uh} | PAX ${pax} | Turno ${turno} | Status ${statusLabel}`;
            selectedPaxReal.value = Number.isInteger(pax) ? String(pax) : '';
            selectedPaxReal.max = Number.isInteger(pax) ? String(pax) : '';
            btnConfirmar.disabled = false;
            btnCancelar.disabled = false;

            rows.forEach((r) => r.classList.remove('table-active'));
            row.classList.add('table-active');
        });
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
        form.submit();
    };

    btnConfirmar?.addEventListener('click', () => {
        submitAction('confirmar', 'Confirmar esta reserva como Finalizada?');
    });
    btnCancelar?.addEventListener('click', () => {
        submitAction('cancelar', 'Marcar esta reserva como Não compareceu?', 'danger');
    });
})();
</script>


