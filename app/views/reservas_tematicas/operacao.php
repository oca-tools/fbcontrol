<?php
$flash = $this->data['flash'] ?? null;
$restaurantes = $this->data['restaurantes'] ?? [];
$turnos = $this->data['turnos'] ?? [];
$reservas = $this->data['reservas'] ?? [];
$filters = $this->data['filters'] ?? [];
$closed = $this->data['closed'] ?? false;
$user = $this->data['user'] ?? Auth::user();
$restrictedRestaurant = $this->data['restricted_restaurant'] ?? null;
$finalStatuses = ['Finalizada', 'Não compareceu', 'Cancelada'];
$isAdmin = in_array(($user['perfil'] ?? ''), ['admin', 'supervisor'], true);

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
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div class="section-title">
            <div class="icon"><i class="bi bi-clipboard-data"></i></div>
            <div>
                <div class="text-uppercase text-muted small">Reservas Temáticas</div>
                <h3 class="fw-bold mb-1">Ambiente de Operação</h3>
                <div class="text-muted">Acompanhe, confira e finalize as reservas do turno.</div>
            </div>
        </div>
        <span class="badge badge-soft">Jornada 2</span>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= h($flash['type']) ?> mt-3"><?= h($flash['message']) ?></div>
    <?php endif; ?>

    <?php if ($closed): ?>
        <div class="alert alert-warning mt-3">Este turno está encerrado. Apenas supervisão pode alterar.</div>
    <?php endif; ?>
</div>

<?php if ($flash): ?>
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;">
        <div id="statusToast" class="toast align-items-center text-bg-<?= h($flash['type']) ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <?= h($flash['message']) ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fechar"></button>
            </div>
        </div>
    </div>
    <script>
        (() => {
            const toastEl = document.getElementById('statusToast');
            if (!toastEl) return;
            const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
            toast.show();
        })();
    </script>
<?php endif; ?>

<div class="card p-4 mb-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-funnel"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Filtros operacionais</div>
            <h5 class="fw-bold mb-0">Selecione o turno para conferência</h5>
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
            <label class="form-label">UH</label>
            <input type="text" class="form-control input-xl" name="uh_numero" value="<?= h($filters['uh_numero'] ?? '') ?>">
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
        <div class="col-12 col-md-3">
            <label class="form-label">Ordenar por</label>
            <select class="form-select input-xl" name="order">
                <option value="">Horário</option>
                <option value="status" <?= ($filters['order'] ?? '') === 'status' ? 'selected' : '' ?>>Status</option>
            </select>
        </div>
        <div class="col-12 d-flex flex-wrap gap-2">
            <button class="btn btn-primary btn-xl">Aplicar filtros</button>
                    <a class="btn btn-primary btn-xl" href="/?r=reservasTematicas/operacao">Remover filtro</a>
            <a class="btn btn-outline-primary btn-xl" href="/?r=reservasTematicas/print&tipo=detalhada&data=<?= h($filters['data']) ?>&restaurante_id=<?= h($filters['restaurante_id']) ?>&turno_id=<?= h($filters['turno_id']) ?>&status=<?= h($filters['status']) ?>&order=<?= h($filters['order']) ?>" target="_blank">
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
                            <button class="btn btn-outline-danger" <?= $closed ? 'disabled' : '' ?> onclick="return confirm('Confirma encerramento do turno temático?');"><i class="bi bi-lock"></i> Encerrar turno</button>
        </form>
    <?php endif; ?>
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
                    <?php $rowFinal = in_array($row['status'], $finalStatuses, true); ?>
                    <tr>
                        <td>
                            <span class="badge badge-soft"><?= h($row['status']) ?></span>
                            <?php if (!empty($row['excedente'])): ?>
                                <span class="badge badge-warning">Excedente</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="uh-badge <?= uh_badge_class($row['uh_numero']) ?>"><?= h($row['uh_numero']) ?></span></td>
                        <td><?= h($row['pax']) ?></td>
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
                                value="<?= (int)($row['pax_real'] ?? $row['pax']) ?>"
                                form="op-form-<?= (int)$row['id'] ?>"
                                <?= ((($rowFinal && !$isAdmin)) || ($closed && !$isAdmin)) ? 'disabled' : '' ?>
                            >
                        </td>
                        <td>
                            <form method="post" action="/?r=reservasTematicas/operacao" class="d-flex flex-column gap-2" id="op-form-<?= (int)$row['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                <input type="hidden" name="confirm_final" value="0">
                                <select class="form-select form-select-sm" name="status" <?= ((($rowFinal && !$isAdmin)) || ($closed && !$isAdmin)) ? 'disabled' : '' ?>>
                                    <?php foreach ($statuses as $status): ?>
                                        <?php
                                            $isFinal = in_array($status, $finalStatuses, true);
                                            $disabled = '';
                                        ?>
                                        <option value="<?= h($status) ?>" <?= $row['status'] === $status ? 'selected' : '' ?> <?= $disabled ?>>
                                            <?= h($status) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <textarea class="form-control form-control-sm" name="observacao_operacao" rows="2" placeholder="Observação operacional" <?= ((($rowFinal && !$isAdmin)) || ($closed && !$isAdmin)) ? 'disabled' : '' ?>><?= h($row['observacao_operacao'] ?? '') ?></textarea>
                                <?php if ($closed && $isAdmin): ?>
                                    <input type="text" class="form-control form-control-sm" name="justificativa" placeholder="Justificativa da alteração" required>
                                <?php endif; ?>
                                <button class="btn btn-outline-primary btn-sm js-status-btn" data-status-current="<?= h($row['status']) ?>" <?= ((($rowFinal && !$isAdmin)) || ($closed && !$isAdmin)) ? 'disabled' : '' ?>><i class="bi bi-check2"></i> Atualizar</button>
                                <?php if ($rowFinal && !$isAdmin): ?>
                                    <div class="text-muted small">Status definitivo para hostess.</div>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($reservas)): ?>
                    <tr><td colspan="9" class="text-muted">Nenhuma reserva encontrada para este período.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(() => {
    const finalStatuses = ['Finalizada', 'Não compareceu', 'Cancelada'];
    document.querySelectorAll('form[action=\"/?r=reservasTematicas/operacao\"] .js-status-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const form = btn.closest('form');
            if (!form) return;
            const select = form.querySelector('select[name=\"status\"]');
            if (!select) return;
            const selected = select.value;
            if (finalStatuses.includes(selected)) {
                const ok = confirm('Esse status é definitivo. Deseja confirmar?');
                if (!ok) {
                    e.preventDefault();
                    return;
                }
                const confirmInput = form.querySelector('input[name="confirm_final"]');
                if (confirmInput) {
                    confirmInput.value = '1';
                }
            }
        });
    });
})();

// evita duplo envio em formulários operacionais
document.querySelectorAll('form[action="/?r=reservasTematicas/operacao"]').forEach((f) => {
    f.addEventListener('submit', () => {
        const btn = f.querySelector('button[type="submit"], button:not([type])');
        if (btn) {
            btn.setAttribute('disabled', 'disabled');
            setTimeout(() => btn.removeAttribute('disabled'), 5000);
        }
    });
});
</script>

