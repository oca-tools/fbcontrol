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
$lastEditableAccess = $this->data['last_editable_access'] ?? null;
$tematicaConflict = $this->data['tematica_conflict'] ?? null;
// Tutorial legado desativado: agora usamos o tutorial guiado global por página/perfil.
$allowHostessTutorial = false;
$showHostessTutorial = false;
?>

<style>
    .access-start-grid .saas-hero-card,
    .access-register-grid .saas-hero-card {
        border-radius: 24px;
        box-shadow: var(--ab-shadow-card);
    }
    .access-register-grid .section-block {
        border: 1px solid var(--ab-border);
        border-radius: 22px;
        padding: 1rem;
        background: var(--ab-card);
        box-shadow: var(--ab-shadow-soft);
    }
    .access-register-grid .recent-live-table td,
    .access-register-grid .recent-live-table th {
        white-space: nowrap;
    }
    .access-register-grid .quick-uh-wrap {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 0.5rem;
    }
</style>

<?php if ($mode === 'start'): ?>
    <div class="row justify-content-center access-start-grid">
        <div class="col-12 col-lg-8">
            <div class="card p-4 saas-hero-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-uppercase text-muted small">Turno operacional</div>
                        <h3 class="fw-bold mb-1">Iniciar turno</h3>
                        <p class="text-muted mb-0">Selecione o restaurante e a Operação do seu turno.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap justify-content-end">
                        <?php if ($allowHostessTutorial): ?>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="openHostessTutorialStart">
                                <i class="bi bi-mortarboard me-1"></i>Tutorial
                            </button>
                        <?php endif; ?>
                        <span class="turno-pill">Checklist rápido</span>
                    </div>
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
                <?php endif; ?>

                <form method="post" action="/?r=access/start">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="confirm_start" id="confirm_start" value="0">
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
                            <div class="alert alert-secondary mb-0">
                                <strong>Checklist:</strong> confirme restaurante, operação e porta antes de iniciar.
                            </div>
                        </div>

                        <div class="col-12">
                            <button class="btn btn-success btn-xl w-100" id="startShiftBtn" disabled>Iniciar turno</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <div id="startShiftConfirm" class="export-toast-wrap" style="display:none;">
        <div class="export-toast" style="max-width:min(92vw,640px);">
            <div class="ok-icon" style="background:linear-gradient(135deg,#f97316,#fb923c);">
                <i class="bi bi-play-fill"></i>
            </div>
            <div class="flex-grow-1">
                <div class="txt mb-1" id="startShiftConfirmTitle">Confirmar início do turno</div>
                <div class="small text-muted" id="startShiftConfirmBody"></div>
                <div class="mt-3 d-flex gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-secondary" id="startShiftNo">Não</button>
                    <button type="button" class="btn btn-primary" id="startShiftYes">Sim, iniciar turno</button>
                </div>
            </div>
        </div>
    </div>
    <?php if ($allowHostessTutorial): ?>
    <div class="modal fade" id="hostessTutorialModalStart" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-mortarboard me-1"></i>Tutorial rápido da Hostess</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="small text-muted mb-2">Passo <span id="tutorialStepNumStart">1</span> de 5</div>
                    <div class="card p-3 bg-light border-0">
                        <h6 class="fw-bold mb-1" id="tutorialStepTitleStart">Selecione restaurante, operação e porta</h6>
                        <p class="mb-0 text-muted" id="tutorialStepTextStart">Esse é o primeiro passo do turno: selecione restaurante e operação corretos. No Corais, confirme também a porta.</p>
                    </div>
                    <div class="row g-2 mt-2">
                        <div class="col-12 col-md-6">
                            <div class="alert alert-info mb-0 small">
                                <strong>Dica:</strong> confira o resumo antes de confirmar o início do turno.
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="alert alert-warning mb-0 small">
                                <strong>Atenção:</strong> fora do horário, o sistema solicita confirmação adicional.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" id="tutorialSkipStart">Pular por agora</button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary" id="tutorialPrevStart" disabled>Voltar</button>
                        <button type="button" class="btn btn-primary" id="tutorialNextStart">Próximo</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <script>
    const draftKey = 'ab_turno_start_draft';
    const restauranteSelect = document.getElementById('restaurante_id');
    const operacaoSelect = document.getElementById('operacao_id');
    const portaSelect = document.getElementById('porta_id');
    const portaWrapper = document.getElementById('porta_wrapper');
    const startBtn = document.getElementById('startShiftBtn');
    const needConfirm = <?= $needConfirm ? 'true' : 'false' ?>;
    const confirmEarly = document.getElementById('confirm_early');
    const startForm = document.querySelector("form[action='/?r=access/start']");
    const confirmStart = document.getElementById('confirm_start');
    const confirmBox = document.getElementById('startShiftConfirm');
    const confirmBody = document.getElementById('startShiftConfirmBody');
    const btnNo = document.getElementById('startShiftNo');
    const btnYes = document.getElementById('startShiftYes');
    let pendingSubmit = false;

    function filterOptions(select, restId) {
        Array.from(select.options).forEach((opt) => {
            if (!opt.dataset.rest) return;
            opt.style.display = opt.dataset.rest === restId ? 'block' : 'none';
        });
    }

    function canStartShift() {
        const hasRestaurant = restauranteSelect.value !== '';
        const hasOperacao = operacaoSelect.value !== '';
        const doorVisible = portaWrapper.style.display !== 'none';
        const hasDoor = !doorVisible || portaSelect.value !== '';
        return hasRestaurant && hasOperacao && hasDoor;
    }

    function updateStartButtonState() {
        if (startBtn) startBtn.disabled = !canStartShift();
    }

    function updateFilters() {
        const restId = restauranteSelect.value;
        filterOptions(operacaoSelect, restId);
        filterOptions(portaSelect, restId);

        const opSelected = operacaoSelect.options[operacaoSelect.selectedIndex];
        if (opSelected && opSelected.dataset.rest && opSelected.dataset.rest !== restId) {
            operacaoSelect.value = '';
        }
        const doorSelected = portaSelect.options[portaSelect.selectedIndex];
        if (doorSelected && doorSelected.dataset.rest && doorSelected.dataset.rest !== restId) {
            portaSelect.value = '';
        }

        const hasPorta = Array.from(portaSelect.options).some(opt => opt.dataset.rest === restId);
        portaWrapper.style.display = hasPorta ? 'block' : 'none';
        updateStartButtonState();
    }

    function buildStartSummary() {
        const userName = '<?= h(Auth::user()['nome'] ?? '') ?>';
        const restLabel = restauranteSelect?.selectedOptions?.[0]?.text || 'N/D';
        const opLabel = operacaoSelect?.selectedOptions?.[0]?.text || 'N/D';
        const doorLabel = (portaWrapper?.style?.display !== 'none' ? (portaSelect?.selectedOptions?.[0]?.text || 'N/D') : 'N/A');
        let html = ''
            + '<strong>Usuário:</strong> ' + userName + '<br>'
            + '<strong>Restaurante:</strong> ' + restLabel + '<br>'
            + '<strong>Operação:</strong> ' + opLabel + '<br>'
            + '<strong>Porta:</strong> ' + doorLabel;
        if (needConfirm) {
            html += '<br><span class="status-warning">Atenção: início fora do horário permitido.</span>';
        }
        return html;
    }

    function openStartConfirm() {
        if (!confirmBox || !confirmBody) return;
        confirmBody.innerHTML = buildStartSummary();
        confirmBox.style.display = 'flex';
    }

    function closeStartConfirm() {
        if (confirmBox) confirmBox.style.display = 'none';
    }

    restauranteSelect.addEventListener('change', updateFilters);
    operacaoSelect.addEventListener('change', updateStartButtonState);
    portaSelect.addEventListener('change', updateStartButtonState);
    updateFilters();

    const draft = JSON.parse(localStorage.getItem(draftKey) || '{}');
    if (draft.restaurante_id) restauranteSelect.value = draft.restaurante_id;
    updateFilters();
    if (draft.operacao_id) operacaoSelect.value = draft.operacao_id;
    if (draft.porta_id) portaSelect.value = draft.porta_id;
    updateStartButtonState();

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

    if (startForm) {
        startForm.addEventListener('submit', (e) => {
            if (pendingSubmit) {
                pendingSubmit = false;
                dirty = false;
                return;
            }
            if (!canStartShift()) {
                e.preventDefault();
                return;
            }
            e.preventDefault();
            openStartConfirm();
        });
    }

    if (btnNo) {
        btnNo.addEventListener('click', () => {
            pendingSubmit = false;
            closeStartConfirm();
        });
    }
    if (confirmBox) {
        confirmBox.addEventListener('click', (e) => {
            if (e.target === confirmBox) {
                pendingSubmit = false;
                closeStartConfirm();
            }
        });
    }
    if (btnYes) {
        btnYes.addEventListener('click', () => {
            if (needConfirm && confirmEarly) confirmEarly.checked = true;
            if (confirmStart) confirmStart.value = '1';
            pendingSubmit = true;
            closeStartConfirm();
            startForm?.requestSubmit();
        });
    }

    (function () {
        const modalEl = document.getElementById('hostessTutorialModalStart');
        if (!modalEl) return;
        let modal = null;
        function getModal() {
            if (!modal && window.bootstrap && window.bootstrap.Modal) {
                modal = new window.bootstrap.Modal(modalEl);
            }
            return modal;
        }
        const openBtn = document.getElementById('openHostessTutorialStart');
        const stepNum = document.getElementById('tutorialStepNumStart');
        const stepTitle = document.getElementById('tutorialStepTitleStart');
        const stepText = document.getElementById('tutorialStepTextStart');
        const btnPrev = document.getElementById('tutorialPrevStart');
        const btnNext = document.getElementById('tutorialNextStart');
        const btnSkip = document.getElementById('tutorialSkipStart');
        const csrf = '<?= h(csrf_token()) ?>';
        const autoOpen = <?= $showHostessTutorial ? 'true' : 'false' ?>;

        const steps = [
            {
                title: 'Selecione restaurante e operação',
                text: 'No início do turno, selecione o restaurante correto e a operação correspondente ao serviço atual.'
            },
            {
                title: 'Defina a porta (quando houver)',
                text: 'Quando o restaurante tiver controle por porta, selecione a entrada correta antes de iniciar.'
            },
            {
                title: 'Confirme o resumo do turno',
                text: 'Revise os dados no popup de confirmação. Fora do horário, valide o início com atenção.'
            },
            {
                title: 'Registro rápido de hóspedes',
                text: 'Após iniciar, registre UH e PAX. Use os atalhos 998 (Não informado) e 999 (Day use) quando necessário.'
            },
            {
                title: 'Fechamento e qualidade',
                text: 'Encerrar turno finaliza a operação. Se houver erro de digitação recente, use a correção rápida.'
            }
        ];
        let idx = 0;

        function renderStep() {
            if (!stepNum || !stepTitle || !stepText || !btnPrev || !btnNext) return;
            stepNum.textContent = String(idx + 1);
            stepTitle.textContent = steps[idx].title;
            stepText.textContent = steps[idx].text;
            btnPrev.disabled = idx === 0;
            btnNext.textContent = idx === (steps.length - 1) ? 'Concluir tutorial' : 'Próximo';
        }

        function post(url) {
            const body = new URLSearchParams();
            body.set('csrf_token', csrf);
            return fetch(url, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                body: body.toString()
            }).catch(() => null);
        }

        openBtn?.addEventListener('click', () => {
            idx = 0;
            renderStep();
            const m = getModal();
            if (!m) return;
            m.show();
            post('/?r=onboarding/hostessSeen');
        });

        btnPrev?.addEventListener('click', () => {
            idx = Math.max(0, idx - 1);
            renderStep();
        });

        btnNext?.addEventListener('click', async () => {
            if (idx < steps.length - 1) {
                idx++;
                renderStep();
                return;
            }
            await post('/?r=onboarding/hostessComplete');
            getModal()?.hide();
        });

        btnSkip?.addEventListener('click', async () => {
            await post('/?r=onboarding/hostessSeen');
            getModal()?.hide();
        });

        if (autoOpen) {
            setTimeout(() => {
                idx = 0;
                renderStep();
                const m = getModal();
                if (!m) return;
                m.show();
                post('/?r=onboarding/hostessSeen');
            }, 650);
        }
    })();

    document.querySelectorAll('form').forEach((f) => {
        f.addEventListener('submit', () => {
            const btn = f.querySelector('button[type="submit"], button:not([type])');
            if (btn) {
                btn.setAttribute('disabled', 'disabled');
                setTimeout(() => btn.removeAttribute('disabled'), 5000);
            }
        });
    });
    </script>
<?php else: ?>
    <div class="row g-4 access-register-grid">
        <div class="col-12 col-lg-7">
            <div class="card p-4 saas-hero-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-uppercase text-muted small">Registro em tempo real</div>
                        <h3 class="fw-bold mb-1"><i class="bi bi-shop-window me-1"></i>
                            <span class="tag <?= restaurant_badge_class($turno['restaurante']) ?>"><?= h($turno['restaurante']) ?></span>
                        </h3>
                        <div class="text-muted">Operação: <span class="tag <?= operation_badge_class($turno['operacao']) ?>"><?= h($turno['operacao']) ?></span></div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php if ($allowHostessTutorial): ?>
                            <button type="button" class="btn btn-outline-primary" id="openHostessTutorial">
                                <i class="bi bi-mortarboard me-1"></i>Tutorial
                            </button>
                        <?php endif; ?>
                        <form method="post" action="/?r=turnos/end">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                            <button class="btn btn-outline-danger" data-confirm="Confirma encerramento do turno?" data-confirm-title="Encerrar turno" data-confirm-type="danger"><i class="bi bi-box-arrow-right me-1"></i>Encerrar turno</button>
                        </form>
                        <?php if ($canCancel): ?>
                            <form method="post" action="/?r=turnos/cancel">
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                <button class="btn btn-outline-secondary" data-confirm="Confirma cancelamento do turno sem registros?" data-confirm-title="Cancelar turno"><i class="bi bi-x-circle me-1"></i>Cancelar turno</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($flash): ?>
                    <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
                <?php endif; ?>
                <?php if ($restOp): ?>
                    <div id="shiftCountdown" class="alert alert-info">
                        Tempo restante do turno: calculando...
                    </div>
                <?php endif; ?>

                <?php if (!empty($tematicaConflict)): ?>
                    <div class="alert alert-warning">
                        <div class="fw-semibold mb-1">Reserva temática encontrada para UH <?= h($tematicaConflict['uh_numero'] ?? '') ?>.</div>
                        <div class="small mb-2">
                            Restaurante: <strong><?= h($tematicaConflict['restaurante'] ?? '-') ?></strong>
                            <?php if (!empty($tematicaConflict['turno_hora'])): ?>
                                às <strong><?= h($tematicaConflict['turno_hora']) ?></strong>
                            <?php endif; ?>
                            | PAX reservado: <strong><?= (int)($tematicaConflict['pax_reservado'] ?? 0) ?></strong>
                        </div>
                        <form method="post" action="/?r=access/register" class="d-flex gap-2 align-items-end flex-wrap mb-0">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="uh_numero" value="<?= h((string)($tematicaConflict['uh_numero'] ?? '')) ?>">
                            <input type="hidden" name="pax" value="<?= (int)($tematicaConflict['pax_sugerido'] ?? 1) ?>">
                            <input type="hidden" name="tematica_reserva_id" value="<?= (int)($tematicaConflict['reserva_id'] ?? 0) ?>">
                            <button type="submit" name="tematica_action" value="cancelar" class="btn btn-outline-danger btn-sm">
                                Cancelar reserva temática e registrar buffet
                            </button>
                            <div class="input-group input-group-sm" style="width: 240px;">
                                <span class="input-group-text">PAX real</span>
                                <input type="number" min="0" max="<?= (int)($tematicaConflict['pax_reservado'] ?? 0) ?>" class="form-control" name="tematica_pax_real" value="<?= max(0, ((int)($tematicaConflict['pax_reservado'] ?? 0) - (int)($tematicaConflict['pax_sugerido'] ?? 0))) ?>">
                                <button type="submit" name="tematica_action" value="pax_real" class="btn btn-primary">
                                    Confirmar parcial
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <form method="post" action="/?r=access/register">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <div class="mb-3">
                    <label class="form-label">Número da UH</label>
                        <input type="text" name="uh_numero" class="form-control input-xl" inputmode="numeric" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Exceções rápidas</label>
                        <div class="quick-uh-wrap" id="uhQuickExceptions">
                            <button type="button" class="btn btn-outline-primary btn-sm js-quick-uh" data-uh="998" data-label="Não informado">
                                <i class="bi bi-question-circle me-1"></i>Não informado
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm js-quick-uh" data-uh="999" data-label="Day use">
                                <i class="bi bi-sun me-1"></i>Day use
                            </button>
                        </div>
                        <div class="small text-muted mt-1" id="uhQuickHint">Toque para preencher a UH automaticamente.</div>
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

                <?php if (!empty($lastEditableAccess)): ?>
                    <hr class="my-4">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="text-uppercase text-muted small">Correção rápida (2 min)</div>
                            <div class="small text-muted">
                                Último lançamento: UH <?= h(uh_label($lastEditableAccess['uh_numero'])) ?> - PAX atual <?= (int)$lastEditableAccess['pax'] ?>
                            </div>
                        </div>
                        <span class="badge badge-warning">Janela curta</span>
                    </div>
                    <form method="post" action="/?r=access/correct_last" class="row g-2 align-items-end">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <div class="col-7">
                            <label class="form-label mb-1">Novo PAX</label>
                            <input type="number" min="1" name="pax_corrigido" class="form-control input-xl" value="<?= (int)$lastEditableAccess['pax'] ?>" required>
                        </div>
                        <div class="col-5">
                            <button type="submit" class="btn btn-outline-primary btn-xl w-100" data-confirm="Confirmar correção do último lançamento?" data-confirm-title="Corrigir lançamento">
                                Corrigir
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (!empty($this->data['is_corais'])): ?>
                <div class="section-block mt-4">
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
            <div class="section-block">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0">Últimos acessos</h4>
                    <span class="text-muted small">Ao vivo</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle recent-live-table">
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
                                    <td><span class="uh-badge <?= uh_badge_class($item['uh_numero']) ?>"><?= h(uh_label($item['uh_numero'])) ?></span></td>
                                    <td><?= h($item['pax']) ?></td>
                                    <td><span class="tag <?= operation_badge_class($item['operacao']) ?>"><?= h($item['operacao']) ?></span></td>
                                    <td>
                                        <?php if (($item['status_operacional'] ?? '') === 'Duplicado'): ?>
                                            <span class="badge badge-warning">Duplicado</span>
                                        <?php elseif (($item['status_operacional'] ?? '') === 'Fora do Horário'): ?>
                                            <span class="badge badge-danger">Fora do horário</span>
                                        <?php elseif (($item['status_operacional'] ?? '') === 'Múltiplo Acesso'): ?>
                                            <span class="badge badge-soft">Múltiplo acesso</span>
                                        <?php else: ?>
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

    <div class="modal fade" id="hostessTutorialModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-mortarboard me-1"></i>Tutorial rápido da Hostess</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="small text-muted mb-2">Passo <span id="tutorialStepNum">1</span> de 4</div>
                    <div class="card p-3 bg-light border-0">
                        <h6 class="fw-bold mb-1" id="tutorialStepTitle">Inicie o turno corretamente</h6>
                        <p class="mb-0 text-muted" id="tutorialStepText">Selecione restaurante, operação e porta (quando aplicável). Confira o resumo antes de confirmar.</p>
                    </div>
                    <div class="row g-2 mt-2">
                        <div class="col-12 col-md-6">
                            <div class="alert alert-info mb-0 small">
                                <strong>Dica:</strong> em caso de dúvida, registre "998 (Não informado)" ou "999 (Day use)".
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="alert alert-warning mb-0 small">
                                <strong>Atenção:</strong> no jantar do Corais, reservas temáticas podem bloquear o lançamento.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" id="tutorialSkip">Pular por agora</button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary" id="tutorialPrev" disabled>Voltar</button>
                        <button type="button" class="btn btn-primary" id="tutorialNext">Próximo</button>
                    </div>
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
    const quickUhButtons = Array.from(document.querySelectorAll('.js-quick-uh'));
    const quickHint = document.getElementById('uhQuickHint');

    function syncQuickUhState() {
        if (!uhInput) return;
        const current = (uhInput.value || '').trim();
        quickUhButtons.forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.uh === current);
        });
        if (!quickHint) return;
        if (current === '998') {
            quickHint.textContent = 'UH técnica selecionada: Não informado (998).';
        } else if (current === '999') {
            quickHint.textContent = 'UH técnica selecionada: Day use (999).';
        } else {
            quickHint.textContent = 'Toque para preencher a UH automaticamente.';
        }
    }

    quickUhButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!uhInput) return;
            uhInput.value = btn.dataset.uh || '';
            uhInput.dispatchEvent(new Event('input', { bubbles: true }));
            syncQuickUhState();
            if (paxInput) {
                paxInput.focus();
                if (typeof paxInput.select === 'function') paxInput.select();
            } else {
                uhInput.blur();
            }
        });
    });

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
    if (uhInput) {
        uhInput.addEventListener('input', syncQuickUhState);
    }
    syncQuickUhState();

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
        if (uhInput) uhInput.value = '';
        if (paxInput) paxInput.value = 1;
        syncQuickUhState();
        // Em tablet/mobile, evita reabrir o teclado automaticamente.
        if (document.activeElement && typeof document.activeElement.blur === 'function') {
            document.activeElement.blur();
        }
        if (uhInput && typeof uhInput.blur === 'function') {
            uhInput.blur();
        }
        dirty = false;
    <?php endif; ?>

    <?php if ($restOp): ?>
    (function() {
        const endTime = '<?= h($restOp['hora_fim']) ?>';
        const toleranceMin = <?= (int)($restOp['tolerancia_min'] ?? 0) ?>;
        const countdownEl = document.getElementById('shiftCountdown');
        const [h, m, s] = endTime.split(':').map(Number);
        let endModalShown = false;
        const calcEnd = () => {
            const now = new Date();
            const end = new Date();
            end.setHours(h, m, s || 0, 0);
            return { now, end };
        };

        const updateCountdown = () => {
            const { now, end } = calcEnd();
            const diffMs = end.getTime() - now.getTime();
            const diffMin = Math.ceil(diffMs / 60000);
            const tolEnd = new Date(end.getTime() + (toleranceMin * 60000));
            if (countdownEl) {
                if (diffMin > 10) {
                    countdownEl.className = 'alert alert-info';
                    countdownEl.textContent = `Tempo restante do turno: ${diffMin} min`;
                } else if (diffMin > 0) {
                    countdownEl.className = 'alert alert-warning';
                    countdownEl.textContent = `Tempo restante do turno: ${diffMin} min`;
                } else if (now <= tolEnd) {
                    countdownEl.className = 'alert alert-warning';
                    const extraMin = Math.max(0, Math.ceil((tolEnd.getTime() - now.getTime()) / 60000));
                    countdownEl.textContent = `Turno fora do horário, aguardando tempo de tolerância (${extraMin} min).`;
                } else {
                    countdownEl.className = 'alert alert-danger';
                    countdownEl.textContent = 'Turno fora do horário limite. Encerrar imediatamente.';
                }
            }
            if (now > tolEnd && !endModalShown) {
                const modalEl = document.getElementById('endShiftModal');
                if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                    const modal = new window.bootstrap.Modal(modalEl);
                    modal.show();
                    endModalShown = true;
                }
            }
        };
        updateCountdown();
        setInterval(updateCountdown, 30000);
    })();
    <?php endif; ?>

    (function () {
        const modalEl = document.getElementById('hostessTutorialModal');
        if (!modalEl) return;
        let modal = null;
        function getModal() {
            if (!modal && window.bootstrap && window.bootstrap.Modal) {
                modal = new window.bootstrap.Modal(modalEl);
            }
            return modal;
        }
        const openBtns = [
            document.getElementById('openHostessTutorial'),].filter(Boolean);
        const stepNum = document.getElementById('tutorialStepNum');
        const stepTitle = document.getElementById('tutorialStepTitle');
        const stepText = document.getElementById('tutorialStepText');
        const btnPrev = document.getElementById('tutorialPrev');
        const btnNext = document.getElementById('tutorialNext');
        const btnSkip = document.getElementById('tutorialSkip');
        const csrf = '<?= h(csrf_token()) ?>';
        const autoOpen = <?= $showHostessTutorial ? 'true' : 'false' ?>;

        const steps = [
            {
                title: 'Inicie o turno corretamente',
                text: 'Selecione restaurante, operação e porta (quando aplicável). Confira o resumo antes de confirmar.'
            },
            {
                title: 'Registre UH e PAX com agilidade',
                text: 'Use os botões de ajuste de PAX e valide o número da UH. O sistema sinaliza duplicidade e fora de horário.'
            },
            {
                title: 'Tratamento de exceções',
                text: 'Para ausência de UH, use 998 (Não informado). Para day use, use 999. No Corais jantar, respeite o alerta de reserva temática.'
            },
            {
                title: 'Fechamento seguro do turno',
                text: 'Encerrar turno grava o fechamento operacional. Se houver erro de PAX recente, use a correção rápida da janela de 2 minutos.'
            }
        ];
        let idx = 0;

        function renderStep() {
            if (!stepNum || !stepTitle || !stepText || !btnPrev || !btnNext) return;
            stepNum.textContent = String(idx + 1);
            stepTitle.textContent = steps[idx].title;
            stepText.textContent = steps[idx].text;
            btnPrev.disabled = idx === 0;
            btnNext.textContent = idx === (steps.length - 1) ? 'Concluir tutorial' : 'Próximo';
        }

        function post(url) {
            const body = new URLSearchParams();
            body.set('csrf_token', csrf);
            return fetch(url, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                body: body.toString()
            }).catch(() => null);
        }

        openBtns.forEach((btn) => btn.addEventListener('click', () => {
            idx = 0;
            renderStep();
            const m = getModal();
            if (!m) return;
            m.show();
            post('/?r=onboarding/hostessSeen');
        }));

        btnPrev?.addEventListener('click', () => {
            idx = Math.max(0, idx - 1);
            renderStep();
        });
        btnNext?.addEventListener('click', async () => {
            if (idx < steps.length - 1) {
                idx++;
                renderStep();
                return;
            }
            await post('/?r=onboarding/hostessComplete');
            getModal()?.hide();
        });
        btnSkip?.addEventListener('click', async () => {
            await post('/?r=onboarding/hostessSeen');
            getModal()?.hide();
        });

        if (autoOpen) {
            setTimeout(() => {
                idx = 0;
                renderStep();
                const m = getModal();
                if (!m) return;
                m.show();
                post('/?r=onboarding/hostessSeen');
            }, 650);
        }
    })();

    // evita duplo envio
    document.querySelectorAll('form').forEach((f) => {
        f.addEventListener('submit', () => {
            const btn = f.querySelector('button[type="submit"], button:not([type])');
            if (btn) {
                btn.setAttribute('disabled', 'disabled');
                setTimeout(() => btn.removeAttribute('disabled'), 5000);
            }
        });
    });
    </script>
<?php endif; ?>





