<?php
$flash = $this->data['flash'] ?? null;
$mode = $this->data['mode'] ?? 'register';
$turno = $this->data['turno'] ?? null;
$restOp = $this->data['restOp'] ?? null;
$recentes = $this->data['recentes'] ?? [];
$toleranceAlert = $this->data['tolerance_alert'] ?? null;
$restaurantes = $this->data['restaurantes'] ?? [];
$restOps = $this->data['restOps'] ?? [];
$doorsByRestaurant = $this->data['doorsByRestaurant'] ?? [];
$needConfirm = $this->data['need_confirm'] ?? false;
$preselect = $this->data['preselect'] ?? [];
$canCancel = $this->data['can_cancel'] ?? false;
?>

<?php if ($mode === 'start'): ?>
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-uppercase text-muted small">Turno operacional</div>
                        <h3 class="fw-bold mb-1">Iniciar turno</h3>
                        <p class="text-muted mb-0">Selecione o restaurante e a Operação do seu turno.</p>
                    </div>
                    <span class="turno-pill">Checklist rápido</span>
                </div>

                <?php if ($flash): ?>
                    <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
                <?php endif; ?>
                <?php if ($toleranceAlert): ?>
                    <div class="alert alert-warning"><?= h($toleranceAlert) ?></div>
                <?php endif; ?>

<?php if ($needConfirm): ?>
                    <div class="alert alert-warning">
                        O turno está sendo iniciado fora do horário. Confirme para continuar.
                    </div>

                    <div class="modal fade" id="earlyStartModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Início antes do horário</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                </div>
                                <div class="modal-body">
                                    Você está iniciando o turno fora do horário permitido. Confirme se deseja continuar.
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                                    <button type="button" class="btn btn-warning" id="confirmEarlyBtn">Confirmar início</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="post" action="/?r=access/start">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Restaurante</label>
                            <select class="form-select input-xl" name="restaurante_id" id="restaurante_id" required>
                                <option value="">Selecione</option>
                                <?php foreach ($restaurantes as $rest): ?>
                                    <option value="<?= (int)$rest['id'] ?>" <?= ($preselect['restaurante_id'] ?? '') == $rest['id'] ? 'selected' : '' ?>>
                                        <?= h($rest['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Operação</label>
                            <select class="form-select input-xl" name="operacao_id" id="operacao_id" required>
                                <option value="">Selecione</option>
                                <?php foreach ($restOps as $restId => $ops): ?>
                                    <?php foreach ($ops as $op): ?>
                                        <option value="<?= (int)$op['operacao_id'] ?>"
                                            data-rest="<?= (int)$restId ?>"
                                            <?= ($preselect['operacao_id'] ?? '') == $op['operacao_id'] ? 'selected' : '' ?>>
                                            <?= h($op['operacao']) ?> (<?= h($op['hora_inicio']) ?> - <?= h($op['hora_fim']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12" id="porta_wrapper" style="display:none;">
                            <label class="form-label">Porta</label>
                            <select class="form-select input-xl" name="porta_id" id="porta_id">
                                <option value="">Selecione</option>
                                <?php foreach ($doorsByRestaurant as $restId => $doors): ?>
                                    <?php foreach ($doors as $door): ?>
                                        <option value="<?= (int)$door['id'] ?>" data-rest="<?= (int)$restId ?>"
                                            <?= ($preselect['porta_id'] ?? '') == $door['id'] ? 'selected' : '' ?>>
                                            <?= h($door['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if ($needConfirm): ?>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="confirm_early" name="confirm_early" required>
                                    <label class="form-check-label" for="confirm_early">
                                    Confirmo o início fora do horário.
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <button class="btn btn-success btn-xl w-100" id="startShiftBtn">Iniciar turno</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    const draftKey = 'ab_turno_start_draft';
    const restauranteSelect = document.getElementById('restaurante_id');
    const operacaoSelect = document.getElementById('operacao_id');
    const portaSelect = document.getElementById('porta_id');
    const portaWrapper = document.getElementById('porta_wrapper');

    function filterOptions(select, restId) {
        Array.from(select.options).forEach((opt) => {
            if (!opt.dataset.rest) return;
            opt.style.display = opt.dataset.rest === restId ? 'block' : 'none';
        });
    }

    function updateFilters() {
        const restId = restauranteSelect.value;
        filterOptions(operacaoSelect, restId);
        filterOptions(portaSelect, restId);

        const hasPorta = Array.from(portaSelect.options).some(opt => opt.dataset.rest === restId);
        portaWrapper.style.display = hasPorta ? 'block' : 'none';
    }

    restauranteSelect.addEventListener('change', updateFilters);
    updateFilters();

    // restore draft
    const draft = JSON.parse(localStorage.getItem(draftKey) || '{}');
    if (draft.restaurante_id) restauranteSelect.value = draft.restaurante_id;
    updateFilters();
    if (draft.operacao_id) operacaoSelect.value = draft.operacao_id;
    if (draft.porta_id) portaSelect.value = draft.porta_id;

    function saveDraft() {
        localStorage.setItem(draftKey, JSON.stringify({
            restaurante_id: restauranteSelect.value,
            operacao_id: operacaoSelect.value,
            porta_id: portaSelect.value
        }));
    }
    restauranteSelect.addEventListener('change', saveDraft);
    operacaoSelect.addEventListener('change', saveDraft);
    portaSelect.addEventListener('change', saveDraft);

    let dirty = false;
    function markDirty() { dirty = true; }
    restauranteSelect.addEventListener('change', markDirty);
    operacaoSelect.addEventListener('change', markDirty);
    portaSelect.addEventListener('change', markDirty);

    window.addEventListener('beforeunload', (e) => {
        if (!dirty) return;
        e.preventDefault();
        e.returnValue = '';
    });

    // avoid warning on normal submit
    const startForm = document.querySelector('form[action=\"/?r=access/start\"]');
    if (startForm) {
        startForm.addEventListener('submit', () => { dirty = false; });
    }

    // highlight early start with modal
    const needConfirm = <?= $needConfirm ? 'true' : 'false' ?>;
    if (needConfirm) {
        const modal = new bootstrap.Modal(document.getElementById('earlyStartModal'));
        const startBtn = document.getElementById('startShiftBtn');
        const confirmEarly = document.getElementById('confirm_early');
        if (startBtn) {
            startBtn.addEventListener('click', (e) => {
                if (!confirmEarly || !confirmEarly.checked) {
                    e.preventDefault();
                    modal.show();
                }
            });
        }
        const confirmBtn = document.getElementById('confirmEarlyBtn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => {
                if (confirmEarly) confirmEarly.checked = true;
                modal.hide();
            });
        }
    }
    </script>
<?php else: ?>
    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <div class="card p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-uppercase text-muted small">Registro em tempo real</div>
                        <h3 class="fw-bold mb-1"><i class="bi bi-shop-window me-1"></i>
                            <span class="tag <?= restaurant_badge_class($turno['restaurante']) ?>"><?= h($turno['restaurante']) ?></span>
                        </h3>
                        <div class="text-muted">Operação: <span class="tag <?= operation_badge_class($turno['operacao']) ?>"><?= h($turno['operacao']) ?></span></div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <form method="post" action="/?r=turnos/end">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                            <button class="btn btn-outline-danger"><i class="bi bi-box-arrow-right me-1"></i>Encerrar turno</button>
                        </form>
                        <?php if ($canCancel): ?>
                            <form method="post" action="/?r=turnos/cancel">
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                <button class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>Cancelar turno</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($flash): ?>
                    <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
                <?php endif; ?>

                <form method="post" action="/?r=access/register">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <div class="mb-3">
                    <label class="form-label">Número da UH</label>
                        <input type="text" name="uh_numero" class="form-control input-xl" inputmode="numeric" required autofocus>
                    </div>

                    <?php if ($turno['exige_pax'] == 1): ?>
                        <div class="mb-3">
                            <label class="form-label">Quantidade de PAX</label>
                            <div class="d-flex gap-2 align-items-center">
                                <button class="btn btn-outline-secondary btn-xl" type="button" onclick="adjustPax(-1)">-</button>
                                <input type="number" min="1" name="pax" id="pax" class="form-control input-xl text-center" value="1" required>
                                <button class="btn btn-outline-secondary btn-xl" type="button" onclick="adjustPax(1)">+</button>
                            </div>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="pax" value="1">
                    <?php endif; ?>

                    <?php if (!empty($this->data['is_corais_jantar'])): ?>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="confirm_no_show" id="confirm_no_show" value="1">
                                <label class="form-check-label" for="confirm_no_show">
                                    Confirmar registro quando UH estiver com no-show no temático.
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-success btn-xl w-100"><i class="bi bi-check2-circle me-1"></i>Registrar</button>
                </form>
            </div>

            <?php if (!empty($this->data['is_corais'])): ?>
                <div class="card p-4 mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <div class="text-uppercase text-muted small">Registros adicionais</div>
                            <h5 class="fw-bold mb-0">Colaboradores</h5>
                        </div>
                        <span class="badge badge-soft">Exclusivo Corais</span>
                    </div>
                    <form method="post" action="/?r=access/register_colaborador" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <div class="col-12 col-md-8">
                            <label class="form-label">Nome do colaborador</label>
                            <input type="text" name="nome_colaborador" class="form-control input-xl" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Quantidade de refeições</label>
                            <input type="number" min="1" name="quantidade" class="form-control input-xl text-center" value="1" required>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-outline-primary btn-xl w-100">Registrar refeição</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0">Últimos acessos</h4>
                    <span class="text-muted small">Ao vivo</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>UH</th>
                                <th>PAX</th>
                                <th>Operação</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentes as $item): ?>
                                <tr>
                                    <td><span class="uh-badge <?= uh_badge_class($item['uh_numero']) ?>"><?= h($item['uh_numero']) ?></span></td>
                                    <td><?= h($item['pax']) ?></td>
                                    <td><span class="tag <?= operation_badge_class($item['operacao']) ?>"><?= h($item['operacao']) ?></span></td>
                                    <td>
                                        <?php if ($item['alerta_duplicidade']): ?>
                                            <span class="badge badge-warning">Duplicado</span>
                                        <?php endif; ?>
                                        <?php if ($item['fora_do_horario']): ?>
                                        <span class="badge badge-danger">Fora do horário</span>
                                        <?php endif; ?>
                                        <?php if (!$item['alerta_duplicidade'] && !$item['fora_do_horario']): ?>
                                            <span class="badge badge-success">OK</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentes)): ?>
                                <tr><td colspan="4" class="text-muted">Sem registros.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="endShiftModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Encerrar turno</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    O horário do turno terminou. Deseja encerrar agora?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Continuar</button>
                    <form method="post" action="/?r=turnos/end">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <button class="btn btn-danger"><i class="bi bi-box-arrow-right me-1"></i>Encerrar turno</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    const draftKey = 'ab_registro_draft';
    function adjustPax(delta) {
        const input = document.getElementById('pax');
        if (!input) return;
        const value = parseInt(input.value || '1', 10) + delta;
        input.value = Math.max(1, value);
    }

    const uhInput = document.querySelector('input[name="uh_numero"]');
    const paxInput = document.getElementById('pax');
    if (uhInput) {
        const draft = JSON.parse(localStorage.getItem(draftKey) || '{}');
        if (draft.uh_numero) uhInput.value = draft.uh_numero;
        if (paxInput && draft.pax) paxInput.value = draft.pax;
        uhInput.addEventListener('input', () => {
            localStorage.setItem(draftKey, JSON.stringify({
                uh_numero: uhInput.value,
                pax: paxInput ? paxInput.value : 1
            }));
        });
        if (paxInput) {
            paxInput.addEventListener('input', () => {
                localStorage.setItem(draftKey, JSON.stringify({
                    uh_numero: uhInput.value,
                    pax: paxInput.value
                }));
            });
        }
    }

    let dirty = false;
    function markDirty() { dirty = true; }
    if (uhInput) uhInput.addEventListener('input', markDirty);
    if (paxInput) paxInput.addEventListener('input', markDirty);

    window.addEventListener('beforeunload', (e) => {
        if (!dirty) return;
        e.preventDefault();
        e.returnValue = '';
    });

    // avoid warning on normal submit
    const registerForm = document.querySelector('form[action=\"/?r=access/register\"]');
    if (registerForm) {
        registerForm.addEventListener('submit', () => { dirty = false; });
    }

    // clean draft after submit success
    <?php if ($flash && $flash['type'] === 'success'): ?>
        localStorage.removeItem(draftKey);
        if (uhInput) uhInput.focus();
        if (uhInput) uhInput.value = '';
        if (paxInput) paxInput.value = 1;
        dirty = false;
    <?php endif; ?>

    <?php if ($restOp): ?>
    (function() {
        const endTime = '<?= h($restOp['hora_fim']) ?>';
        const now = new Date();
        const [h, m, s] = endTime.split(':').map(Number);
        const end = new Date();
        end.setHours(h, m, s || 0, 0);

        if (now >= end) {
            const modal = new bootstrap.Modal(document.getElementById('endShiftModal'));
            modal.show();
        }
    })();
    <?php endif; ?>
    </script>
<?php endif; ?>
