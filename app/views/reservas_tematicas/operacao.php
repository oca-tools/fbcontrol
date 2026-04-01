<?php
$flash = $this->data['flash'] ?? null;
$restaurantes = $this->data['restaurantes'] ?? [];
$turnos = $this->data['turnos'] ?? [];
$reservas = $this->data['reservas'] ?? [];
$filters = $this->data['filters'] ?? [];
$closed = $this->data['closed'] ?? false;
$user = $this->data['user'] ?? Auth::user();
$restrictedRestaurant = $this->data['restricted_restaurant'] ?? null;
$isAdmin = in_array(($user['perfil'] ?? ''), ['admin', 'supervisor'], true);
$quickDisabled = $closed && !$isAdmin;

$finalStatuses = ['Finalizada', 'Nao compareceu', 'Cancelada'];
$statusOptions = ['Reservada', 'Finalizada', 'Nao compareceu', 'Cancelada', 'Divergencia', 'Excedente'];
$statusLabels = [
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
        'NÃ£o compareceu' => 'Nao compareceu',
        'NÃƒÂ£o compareceu' => 'Nao compareceu',
        'Divergencia' => 'Divergencia',
        'Divergência' => 'Divergencia',
        'DivergÃªncia' => 'Divergencia',
        'DivergÃƒÂªncia' => 'Divergencia',
        'Conferida' => 'Reservada',
        'Em atendimento' => 'Reservada',
    ];
    return $map[$status] ?? $status;
};
$labelStatus = static function (?string $status) use ($normalizeStatus, $statusLabels): string {
    $canon = $normalizeStatus($status);
    return $statusLabels[$canon] ?? $canon;
};

$reservasOrdenadas = $reservas;
usort($reservasOrdenadas, static function (array $a, array $b) use ($normalizeStatus): int {
    $ta = (string)($a['turno_hora'] ?? '');
    $tb = (string)($b['turno_hora'] ?? '');
    if ($ta !== $tb) {
        return strcmp($ta, $tb);
    }
    $sa = $normalizeStatus((string)($a['status'] ?? ''));
    $sb = $normalizeStatus((string)($b['status'] ?? ''));
    if ($sa !== $sb) {
        return strcmp($sa, $sb);
    }
    return strcmp((string)($a['titular_nome'] ?? ''), (string)($b['titular_nome'] ?? ''));
});

$reservasEntradaRapida = array_values(array_filter($reservasOrdenadas, static function (array $row) use ($normalizeStatus, $finalStatuses): bool {
    $status = $normalizeStatus((string)($row['status'] ?? ''));
    return !in_array($status, $finalStatuses, true);
}));
?>

<div class="card card-soft p-4 mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div class="section-title">
            <div class="icon"><i class="bi bi-clipboard2-check"></i></div>
            <div>
                <div class="text-uppercase text-muted small">Reservas Temáticas</div>
                <h3 class="fw-bold mb-1">Ambiente de Operação</h3>
                <div class="text-muted">Fluxo rápido para confirmação de entrada e atualização de status.</div>
            </div>
        </div>
        <span class="badge badge-soft">Jornada 2</span>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= h($flash['type']) ?> mt-3"><?= h($flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($closed): ?>
        <div class="alert alert-warning mt-3">Este turno está encerrado. Somente supervisão e administração podem alterar reservas.</div>
    <?php endif; ?>
</div>

<?php if ($flash): ?>
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;">
        <div id="statusToast" class="toast align-items-center text-bg-<?= h($flash['type']) ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body"><?= h($flash['message']) ?></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fechar"></button>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card p-4 mb-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-funnel"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Filtros operacionais</div>
            <h5 class="fw-bold mb-0">Selecione período e restaurante</h5>
        </div>
    </div>

    <form class="row g-3 align-items-end" method="get" action="/">
        <input type="hidden" name="r" value="reservasTematicas/operacao">
        <div class="col-12 col-md-3">
            <label class="form-label">Data</label>
            <input type="date" class="form-control input-xl" name="data" value="<?= h($filters['data'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Restaurante</label>
            <?php if ($restrictedRestaurant): ?>
                <input type="hidden" name="restaurante_id" value="<?= h($restrictedRestaurant['id']) ?>">
                <div class="form-control input-xl d-flex align-items-center gap-2">
                    <span class="tag <?= restaurant_badge_class($restrictedRestaurant['nome']) ?>"><?= h($restrictedRestaurant['nome']) ?></span>
                </div>
            <?php else: ?>
                <select class="form-select input-xl" name="restaurante_id">
                    <option value="">Todos</option>
                    <?php foreach ($restaurantes as $rest): ?>
                        <option value="<?= (int)$rest['id'] ?>" <?= ($filters['restaurante_id'] ?? '') == $rest['id'] ? 'selected' : '' ?>>
                            <?= h($rest['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>
        <div class="col-12 col-md-2">
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
        <div class="col-12 col-md-2">
            <label class="form-label">UH</label>
            <input type="text" class="form-control input-xl" name="uh_numero" value="<?= h($filters['uh_numero'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-2">
            <label class="form-label">Titular</label>
            <input type="text" class="form-control input-xl" name="titular" value="<?= h($filters['titular'] ?? '') ?>" placeholder="Nome do titular">
        </div>
        <div class="col-12 col-md-2">
            <label class="form-label">Status</label>
            <select class="form-select input-xl" name="status">
                <option value="">Todos</option>
                <?php foreach ($statusOptions as $status): ?>
                    <option value="<?= h($status) ?>" <?= $normalizeStatus($filters['status'] ?? '') === $status ? 'selected' : '' ?>>
                        <?= h($labelStatus($status)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-2">
            <label class="form-label">Ordenar por</label>
            <select class="form-select input-xl" name="order">
                <option value="">Horário</option>
                <option value="status" <?= ($filters['order'] ?? '') === 'status' ? 'selected' : '' ?>>Status</option>
            </select>
        </div>
        <div class="col-12 d-flex flex-wrap gap-2">
            <button class="btn btn-primary btn-xl">Aplicar filtros</button>
            <a class="btn btn-primary btn-xl" href="/?r=reservasTematicas/operacao">Remover filtro</a>
            <a class="btn btn-outline-primary btn-xl" href="/?r=reservasTematicas/print&tipo=detalhada&data=<?= h($filters['data']) ?>&restaurante_id=<?= h($filters['restaurante_id']) ?>&turno_id=<?= h($filters['turno_id']) ?>&titular=<?= urlencode((string)($filters['titular'] ?? '')) ?>&status=<?= h($filters['status']) ?>&order=<?= h($filters['order']) ?>" target="_blank">
                <i class="bi bi-printer"></i> Imprimir lista
            </a>
        </div>
    </form>

    <?php if (!empty($filters['restaurante_id']) && !empty($filters['turno_id'])): ?>
        <form method="post" action="/?r=reservasTematicas/operacao" class="mt-3">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="close_turno">
            <input type="hidden" name="restaurante_id" value="<?= h($filters['restaurante_id']) ?>">
            <input type="hidden" name="turno_id" value="<?= h($filters['turno_id']) ?>">
            <input type="hidden" name="data_reserva" value="<?= h($filters['data']) ?>">
            <button
                class="btn btn-outline-danger"
                <?= $closed ? 'disabled' : '' ?>
                data-confirm="Confirma encerramento do turno temático?"
                data-confirm-title="Encerrar turno"
                data-confirm-type="danger"
            >
                <i class="bi bi-lock"></i> Encerrar turno
            </button>
        </form>
    <?php endif; ?>
</div>

<div class="card p-4 mb-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-lightning-charge"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Operação rápida</div>
            <h5 class="fw-bold mb-0">Selecionar reserva e finalizar</h5>
        </div>
    </div>

    <div class="row g-3 align-items-end">
        <div class="col-12 col-lg-8">
            <label class="form-label">Reserva</label>
            <select class="form-select input-xl" id="quickReservaSelect" <?= $quickDisabled ? 'disabled' : '' ?>>
                <option value="">Selecione uma reserva do período</option>
                <?php foreach ($reservasEntradaRapida as $row): ?>
                    <?php
                        $rowStatus = $normalizeStatus((string)($row['status'] ?? ''));
                        $label = trim((string)($row['titular_nome'] ?? '')) !== '' ? (string)$row['titular_nome'] : 'Sem titular';
                        $label .= ' | UH ' . (string)($row['uh_numero'] ?? '-');
                        $label .= ' | ' . (string)($row['turno_hora'] ?? '--:--');
                        $label .= ' | ' . (int)($row['pax'] ?? 0) . ' PAX';
                        $label .= ' | ' . $labelStatus($rowStatus);
                    ?>
                    <option
                        value="<?= (int)$row['id'] ?>"
                        data-form-id="op-form-<?= (int)$row['id'] ?>"
                        data-pax="<?= (int)($row['pax'] ?? 0) ?>"
                        data-status="<?= h($rowStatus) ?>"
                    >
                        <?= h($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-lg-4">
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-success btn-xl flex-grow-1" id="quickFinalizar" disabled>
                    <i class="bi bi-check2-circle me-1"></i> Finalizar entrada
                </button>
                <button type="button" class="btn btn-outline-danger btn-xl flex-grow-1" id="quickNoShow" disabled>
                    <i class="bi bi-person-x me-1"></i> Não compareceu
                </button>
            </div>
        </div>
        <div class="col-12">
            <div class="d-flex flex-wrap gap-2 text-muted small">
                <span class="tag badge-soft" id="quickResumoStatus">Status: -</span>
                <span class="tag badge-soft" id="quickResumoPax">PAX: -</span>
            </div>
            <?php if ($quickDisabled): ?>
                <div class="text-muted small mt-2">Turno encerrado: operação rápida desabilitada para hostess.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card p-4 mb-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-list-ul"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Visualização rápida</div>
            <h5 class="fw-bold mb-0">Nome | PAX | Turno | Status</h5>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>PAX</th>
                    <th>Turno</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservasOrdenadas as $item): ?>
                    <?php $status = $normalizeStatus((string)($item['status'] ?? '')); ?>
                    <tr>
                        <td class="fw-semibold"><?= h($item['titular_nome'] ?? '-') ?></td>
                        <td><?= h((string)($item['pax'] ?? 0)) ?></td>
                        <td><span class="tag badge-soft"><?= h($item['turno_hora'] ?? '-') ?></span></td>
                        <td><span class="badge badge-soft"><?= h($labelStatus($status)) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($reservasOrdenadas)): ?>
                    <tr><td colspan="4" class="text-muted">Nenhuma reserva no período.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card p-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-list-check"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Conferência</div>
            <h5 class="fw-bold mb-0">Reservas do período selecionado</h5>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>UH</th>
                    <th>Titular</th>
                    <th>PAX reservada</th>
                    <th>Restaurante</th>
                    <th>Turno</th>
                    <th>Observação original</th>
                    <th>Observação operacional</th>
                    <th>PAX real</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservas as $row): ?>
                    <?php
                        $rowStatus = $normalizeStatus((string)($row['status'] ?? ''));
                        $rowFinal = in_array($rowStatus, $finalStatuses, true);
                        $blocked = (($rowFinal && !$isAdmin) || ($closed && !$isAdmin));
                    ?>
                    <tr>
                        <td>
                            <span class="badge badge-soft"><?= h($labelStatus($rowStatus)) ?></span>
                            <?php if (!empty($row['excedente'])): ?>
                                <span class="badge badge-warning">Excedente</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="uh-badge <?= uh_badge_class($row['uh_numero']) ?>"><?= h($row['uh_numero']) ?></span></td>
                        <td><?= h($row['titular_nome'] ?? '-') ?></td>
                        <td><?= h((string)$row['pax']) ?></td>
                        <td><span class="tag <?= restaurant_badge_class($row['restaurante']) ?>"><?= h($row['restaurante']) ?></span></td>
                        <td><span class="tag badge-soft"><?= h($row['turno_hora']) ?></span></td>
                        <td>
                            <?= h($row['observacao_reserva'] ?? '-') ?>
                            <?php if (!empty($row['observacao_tags'])): ?>
                                <div class="text-muted small"><?= h($row['observacao_tags']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= h($row['observacao_operacao'] ?? '-') ?></td>
                        <td>
                            <div class="mb-1 fw-semibold"><?= h((string)($row['pax_real'] ?? '-')) ?></div>
                            <input
                                type="number"
                                class="form-control form-control-sm"
                                name="pax_real"
                                min="0"
                                max="<?= (int)$row['pax'] ?>"
                                data-pax-reservado="<?= (int)$row['pax'] ?>"
                                value="<?= (int)($row['pax_real'] ?? $row['pax']) ?>"
                                form="op-form-<?= (int)$row['id'] ?>"
                                <?= $blocked ? 'disabled' : '' ?>
                            >
                        </td>
                        <td>
                            <form method="post" action="/?r=reservasTematicas/operacao" class="d-flex flex-column gap-2 js-op-form" id="op-form-<?= (int)$row['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                <input type="hidden" name="confirm_final" value="0">
                                <button type="button" class="btn btn-success btn-sm js-confirm-entrada" data-form="op-form-<?= (int)$row['id'] ?>" <?= $blocked ? 'disabled' : '' ?>>
                                    <i class="bi bi-check2-circle me-1"></i>Confirmar entrada
                                </button>
                                <select class="form-select form-select-sm js-status-select" name="status" <?= $blocked ? 'disabled' : '' ?>>
                                    <?php foreach ($statusOptions as $status): ?>
                                        <option value="<?= h($status) ?>" <?= $rowStatus === $status ? 'selected' : '' ?>>
                                            <?= h($labelStatus($status)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="d-flex flex-wrap gap-1">
                                    <button type="button" class="btn btn-outline-success btn-sm js-quick-status" data-form="op-form-<?= (int)$row['id'] ?>" data-status="Finalizada" <?= $blocked ? 'disabled' : '' ?>>Finalizada</button>
                                    <button type="button" class="btn btn-outline-danger btn-sm js-quick-status" data-form="op-form-<?= (int)$row['id'] ?>" data-status="Nao compareceu" <?= $blocked ? 'disabled' : '' ?>>Não compareceu</button>
                                    <button type="button" class="btn btn-outline-warning btn-sm js-quick-status" data-form="op-form-<?= (int)$row['id'] ?>" data-status="Cancelada" <?= $blocked ? 'disabled' : '' ?>>Cancelada</button>
                                </div>
                                <textarea class="form-control form-control-sm" name="observacao_operacao" rows="2" placeholder="Observação operacional" <?= $blocked ? 'disabled' : '' ?>><?= h($row['observacao_operacao'] ?? '') ?></textarea>
                                <?php if ($closed && $isAdmin): ?>
                                    <input type="text" class="form-control form-control-sm" name="justificativa" placeholder="Justificativa da alteração" required>
                                <?php endif; ?>
                                <button type="button" class="btn btn-outline-primary btn-sm js-status-submit" <?= $blocked ? 'disabled' : '' ?>><i class="bi bi-check2"></i> Atualizar</button>
                                <?php if ($rowFinal && !$isAdmin): ?>
                                    <div class="text-muted small">Status definitivo para hostess.</div>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($reservas)): ?>
                    <tr><td colspan="10" class="text-muted">Nenhuma reserva encontrada para este período.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(() => {
    const statusToast = document.getElementById('statusToast');
    if (statusToast && window.bootstrap && bootstrap.Toast) {
        bootstrap.Toast.getOrCreateInstance(statusToast, { delay: 3000 }).show();
    }

    const finalStatuses = ['Finalizada', 'Nao compareceu', 'Cancelada'];
    const statusLabels = {
        'Reservada': 'Reservada',
        'Finalizada': 'Finalizada',
        'Nao compareceu': 'Não compareceu',
        'Cancelada': 'Cancelada',
        'Divergencia': 'Divergência',
        'Excedente': 'Excedente'
    };

    const getStatusLabel = (status) => statusLabels[status] || status;

    const confirmFinalStatus = async (status) => {
        if (!finalStatuses.includes(status)) return true;
        if (typeof window.ocafConfirm !== 'function') return true;
        return window.ocafConfirm({
            title: 'Confirmar status definitivo',
            message: `Deseja realmente alterar para "${getStatusLabel(status)}"?`,
            confirmText: 'Confirmar',
            cancelText: 'Cancelar',
            type: status === 'Cancelada' ? 'danger' : 'default'
        });
    };

    const submitStatus = async (form, status, options = {}) => {
        if (!form) return;
        const statusSelect = form.querySelector('select[name="status"]');
        const confirmInput = form.querySelector('input[name="confirm_final"]');
        const paxInput = form.querySelector('input[name="pax_real"]');

        if (!statusSelect) return;
        statusSelect.value = status;

        if (paxInput) {
            const reservado = parseInt(paxInput.getAttribute('data-pax-reservado') || '0', 10);
            if (status === 'Finalizada' && Number.isInteger(reservado) && reservado >= 0) {
                paxInput.value = String(reservado);
            }
            if (status === 'Nao compareceu') {
                paxInput.value = '0';
            }
        }

        const ok = await confirmFinalStatus(status);
        if (!ok) return;

        if (confirmInput) {
            confirmInput.value = finalStatuses.includes(status) ? '1' : '0';
        }
        form.submit();
    };

    document.querySelectorAll('.js-status-submit').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const form = btn.closest('form');
            if (!form) return;
            const statusSelect = form.querySelector('select[name="status"]');
            const status = statusSelect ? statusSelect.value : 'Reservada';
            await submitStatus(form, status);
        });
    });

    document.querySelectorAll('.js-quick-status').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const formId = btn.getAttribute('data-form');
            const status = btn.getAttribute('data-status');
            if (!formId || !status) return;
            const form = document.getElementById(formId);
            await submitStatus(form, status);
        });
    });

    document.querySelectorAll('.js-confirm-entrada').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const formId = btn.getAttribute('data-form');
            if (!formId) return;
            const form = document.getElementById(formId);
            await submitStatus(form, 'Finalizada');
        });
    });

    const quickSelect = document.getElementById('quickReservaSelect');
    const quickFinalizar = document.getElementById('quickFinalizar');
    const quickNoShow = document.getElementById('quickNoShow');
    const quickResumoStatus = document.getElementById('quickResumoStatus');
    const quickResumoPax = document.getElementById('quickResumoPax');

    const updateQuickState = () => {
        if (!quickSelect) return;
        if (quickSelect.disabled) {
            if (quickFinalizar) quickFinalizar.disabled = true;
            if (quickNoShow) quickNoShow.disabled = true;
            return;
        }
        const option = quickSelect.selectedOptions[0];
        const hasReserva = option && option.value;
        if (quickFinalizar) quickFinalizar.disabled = !hasReserva;
        if (quickNoShow) quickNoShow.disabled = !hasReserva;
        if (!hasReserva) {
            if (quickResumoStatus) quickResumoStatus.textContent = 'Status: -';
            if (quickResumoPax) quickResumoPax.textContent = 'PAX: -';
            return;
        }
        const status = option.getAttribute('data-status') || '';
        const pax = option.getAttribute('data-pax') || '0';
        if (quickResumoStatus) quickResumoStatus.textContent = `Status: ${getStatusLabel(status)}`;
        if (quickResumoPax) quickResumoPax.textContent = `PAX: ${pax}`;
    };

    if (quickSelect) {
        quickSelect.addEventListener('change', updateQuickState);
        updateQuickState();
    }

    if (quickFinalizar) {
        quickFinalizar.addEventListener('click', async () => {
            const option = quickSelect?.selectedOptions?.[0];
            if (!option || !option.value) return;
            const formId = option.getAttribute('data-form-id');
            if (!formId) return;
            const form = document.getElementById(formId);
            await submitStatus(form, 'Finalizada', { quick: true });
        });
    }

    if (quickNoShow) {
        quickNoShow.addEventListener('click', async () => {
            const option = quickSelect?.selectedOptions?.[0];
            if (!option || !option.value) return;
            const formId = option.getAttribute('data-form-id');
            if (!formId) return;
            const form = document.getElementById(formId);
            await submitStatus(form, 'Nao compareceu', { quick: true });
        });
    }
})();
</script>
