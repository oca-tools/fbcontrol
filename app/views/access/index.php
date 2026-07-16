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
$lastEditableAccesses = $this->data['last_editable_accesses'] ?? ($lastEditableAccess ? [$lastEditableAccess] : []);
$tematicaConflict = $this->data['tematica_conflict'] ?? null;
$duplicateConfirm = $this->data['duplicate_confirm'] ?? null;
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
    .access-register-actions {
        align-items: flex-start;
        justify-content: flex-end;
    }
    .access-primary-form {
        display: grid;
        gap: 1rem;
    }
    .access-primary-form .mb-3 {
        margin-bottom: 0 !important;
    }
    .access-pax-control {
        display: grid;
        grid-template-columns: 56px minmax(0, 1fr) 56px;
        gap: .65rem;
        align-items: stretch;
    }
    .access-pax-control .btn,
    .access-pax-control .form-control {
        min-height: 54px;
    }
    @media (max-width: 991.98px) {
        .access-start-grid .saas-hero-card,
        .access-register-grid .saas-hero-card,
        .access-register-grid .section-block {
            border-radius: 18px;
            padding: 1rem !important;
        }
        .access-register-actions {
            width: 100%;
            justify-content: stretch;
        }
        .access-register-actions form,
        .access-register-actions .btn {
            flex: 1 1 auto;
        }
    }
    @media (max-width: 575.98px) {
        .access-page h3 {
            font-size: 1.25rem;
            line-height: 1.2;
        }
        .access-register-actions {
            display: grid !important;
            grid-template-columns: 1fr;
        }
        .access-register-actions form,
        .access-register-actions .btn {
            width: 100%;
        }
        .access-register-grid .quick-uh-wrap {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .access-register-grid .quick-uh-wrap .btn {
            min-height: 44px;
            white-space: normal;
            line-height: 1.15;
        }
        .access-pax-control {
            grid-template-columns: 52px minmax(0, 1fr) 52px;
        }
        .access-register-grid .recent-live-table,
        .access-register-grid .recent-live-table tbody,
        .access-register-grid .recent-live-table tr,
        .access-register-grid .recent-live-table td {
            display: block;
            width: 100%;
        }
        .access-register-grid .recent-live-table thead {
            display: none;
        }
        .access-register-grid .recent-live-table tr {
            border: 1px solid var(--ab-border);
            border-radius: 16px;
            background: var(--ab-card);
            padding: .85rem;
            margin-bottom: .75rem;
            box-shadow: 0 10px 22px rgba(15, 23, 42, .06);
        }
        .access-register-grid .recent-live-table td {
            border: 0;
            padding: .35rem 0 !important;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            white-space: normal;
            text-align: right;
        }
        .access-register-grid .recent-live-table td::before {
            content: attr(data-label);
            color: var(--ab-muted);
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            text-align: left;
        }
        .access-register-grid .recent-live-table td .tag,
        .access-register-grid .recent-live-table td .uh-badge,
        .access-register-grid .recent-live-table td .badge {
            max-width: 62%;
            white-space: normal;
            text-align: center;
        }
    }
</style>

<div class="saas-page access-page">
<?php if ($mode === 'start'): ?>
    <div class="fb-startwiz" id="startWiz">
        <div class="fb-startwiz__bar">
            <button type="button" class="fb-startwiz__back" id="wizBack" hidden><i class="bi bi-arrow-left"></i> Voltar</button>
            <div class="fb-startwiz__dots" id="wizDots" aria-hidden="true"></div>
        </div>

        <?php if ($toleranceAlert): ?>
            <div class="app-inline-note is-warning"><?= h($toleranceAlert) ?></div>
        <?php endif; ?>

        <form method="post" action="/?r=access/start" id="startShiftForm" data-single-rest="<?= count($restaurantes) === 1 ? '1' : '0' ?>" data-need-confirm="<?= $needConfirm ? '1' : '0' ?>">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="confirm_start" id="confirm_start" value="0">
            <input type="hidden" name="restaurante_id" id="sel_restaurante" value="<?= h((string)($preselect['restaurante_id'] ?? '')) ?>">
            <input type="hidden" name="operacao_id" id="sel_operacao" value="<?= h((string)($preselect['operacao_id'] ?? '')) ?>">
            <input type="hidden" name="porta_id" id="sel_porta" value="<?= h((string)($preselect['porta_id'] ?? '')) ?>">

            <section class="fb-startwiz__panel" data-wstep="restaurante">
                <h2 class="fb-startwiz__title">Onde você está?</h2>
                <p class="fb-startwiz__hint">Toque no seu restaurante.</p>
                <div class="fb-startshift__cards fb-startshift__cards--lg">
                    <?php foreach ($restaurantes as $rest): ?>
                        <?php $identStart = restaurante_identidade($rest); ?>
                        <button type="button" class="fb-startpick fb-startpick--lg" data-pick-rest="<?= (int)$rest['id'] ?>" style="--fb-pick-accent: <?= h($identStart['cor']) ?>;">
                            <span class="fb-startpick__icon"><i class="bi <?= h($identStart['icone']) ?>" aria-hidden="true"></i></span>
                            <span class="fb-startpick__name"><?= h(normalize_mojibake((string)$rest['nome'])) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="fb-startwiz__panel" data-wstep="operacao" hidden>
                <h2 class="fb-startwiz__title">Qual serviço?</h2>
                <p class="fb-startwiz__hint" id="wizRestHint"></p>
                <?php
                $agoraStart = date('H:i:s');
                $identStartMap = [];
                foreach ($restaurantes as $restIdent) {
                    $identStartMap[(int)$restIdent['id']] = restaurante_identidade($restIdent);
                }
                ?>
                <?php foreach ($restOps as $restId => $ops): ?>
                    <?php
                    // Disponíveis (agora/futuro) por horário no topo; indisponíveis (encerrado) embaixo.
                    $opsOrdenados = $ops;
                    usort($opsOrdenados, static function ($a, $b) use ($agoraStart) {
                        $fimA = (string)($a['hora_fim'] ?? '');
                        $fimB = (string)($b['hora_fim'] ?? '');
                        $encA = ($fimA !== '' && $agoraStart > $fimA) ? 1 : 0;
                        $encB = ($fimB !== '' && $agoraStart > $fimB) ? 1 : 0;
                        if ($encA !== $encB) {
                            return $encA <=> $encB;
                        }
                        return strcmp((string)($a['hora_inicio'] ?? ''), (string)($b['hora_inicio'] ?? ''));
                    });
                    ?>
                    <div class="fb-startshift__services" data-rest-group="<?= (int)$restId ?>" style="--fb-pick-accent: <?= h($identStartMap[(int)$restId]['cor'] ?? 'var(--fb-ink)') ?>;" hidden>
                        <?php foreach ($opsOrdenados as $op): ?>
                            <?php
                            $inicioOp = (string)($op['hora_inicio'] ?? '');
                            $fimOp = (string)($op['hora_fim'] ?? '');
                            $ehAgora = $inicioOp !== '' && $fimOp !== '' && $agoraStart >= $inicioOp && $agoraStart <= $fimOp;
                            $encerradoOp = $fimOp !== '' && $agoraStart > $fimOp;
                            ?>
                            <button type="button" class="fb-startpick fb-startpick--service<?= $ehAgora ? ' is-now' : ($encerradoOp ? ' is-past' : '') ?>"<?= $encerradoOp ? '' : ' data-pick-op="' . (int)$op['operacao_id'] . '"' ?> data-rest="<?= (int)$restId ?>" data-now="<?= $ehAgora ? '1' : '0' ?>"<?= $encerradoOp ? ' disabled aria-disabled="true"' : '' ?>>
                                <span class="fb-startpick__body">
                                    <span class="fb-startpick__name"><?= h(normalize_mojibake((string)$op['operacao'])) ?></span>
                                    <span class="fb-startpick__window"><?= h(substr($inicioOp, 0, 5)) ?> – <?= h(substr($fimOp, 0, 5)) ?></span>
                                </span>
                                <?php if ($ehAgora): ?>
                                    <span class="fb-startpick__badge fb-startpick__badge--now">agora</span>
                                <?php elseif ($encerradoOp): ?>
                                    <span class="fb-startpick__badge fb-startpick__badge--past">encerrado</span>
                                <?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </section>

            <section class="fb-startwiz__panel" data-wstep="porta" hidden>
                <h2 class="fb-startwiz__title">Qual porta?</h2>
                <p class="fb-startwiz__hint">Selecione a entrada do seu posto.</p>
                <?php foreach ($doorsByRestaurant as $restId => $doors): ?>
                    <?php if (empty($doors)) { continue; } // restaurante sem porta: o passo é pulado ?>
                    <div class="fb-startshift__doors" data-door-group="<?= (int)$restId ?>" style="--fb-pick-accent: <?= h($identStartMap[(int)$restId]['cor'] ?? 'var(--fb-ink)') ?>;" hidden>
                        <?php foreach ($doors as $door): ?>
                            <button type="button" class="fb-chip" data-pick-door="<?= (int)$door['id'] ?>" data-rest="<?= (int)$restId ?>"><?= h(normalize_mojibake((string)$door['nome'])) ?></button>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </section>

            <section class="fb-startwiz__panel" data-wstep="confirmar" hidden>
                <h2 class="fb-startwiz__title">Pronto para iniciar</h2>
                <div class="fb-startwiz__review" id="wizReview"></div>
                <?php if ($needConfirm): ?>
                    <label class="fb-startshift__confirm">
                        <input type="checkbox" value="1" id="confirm_early" name="confirm_early" required>
                        <span>Confirmo o início fora do horário permitido.</span>
                    </label>
                <?php endif; ?>
                <button type="submit" class="fb-btn fb-btn--primary fb-btn--lg" id="startShiftBtn"><i class="bi bi-play-fill"></i> Iniciar turno</button>
            </section>
        </form>
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
                            <div class="app-inline-note is-info mb-0 small">
                                <strong>Dica:</strong> confira o resumo antes de confirmar o início do turno.
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="app-inline-note is-warning mb-0 small">
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
    const startForm = document.getElementById('startShiftForm');
    const wiz = document.getElementById('startWiz');
    const selRest = document.getElementById('sel_restaurante');
    const selOp = document.getElementById('sel_operacao');
    const selPorta = document.getElementById('sel_porta');
    const needConfirm = <?= $needConfirm ? 'true' : 'false' ?>;
    const confirmEarly = document.getElementById('confirm_early');
    const confirmStart = document.getElementById('confirm_start');
    let dirty = false;

    if (startForm && wiz) {
        const singleRest = startForm.dataset.singleRest === '1';
        const panels = {};
        Array.prototype.slice.call(startForm.querySelectorAll('[data-wstep]')).forEach(function (p) { panels[p.dataset.wstep] = p; });
        const stepOp = panels.operacao;
        const stepPorta = panels.porta;
        const restCards = Array.prototype.slice.call(startForm.querySelectorAll('[data-pick-rest]'));
        const opGroups = Array.prototype.slice.call(startForm.querySelectorAll('[data-rest-group]'));
        const doorGroups = Array.prototype.slice.call(startForm.querySelectorAll('[data-door-group]'));
        const backBtn = document.getElementById('wizBack');
        const dotsWrap = document.getElementById('wizDots');
        const restHint = document.getElementById('wizRestHint');
        const reviewEl = document.getElementById('wizReview');
        const labels = { rest: '', op: '', door: '', restIcon: '', restColor: '' };
        let history = [];

        function opGroupFor(id) { return opGroups.find(function (g) { return g.dataset.restGroup === String(id); }) || null; }
        function doorGroupFor(id) { return doorGroups.find(function (g) { return g.dataset.doorGroup === String(id); }) || null; }
        function needsDoor() {
            var g = doorGroupFor(selRest.value);
            return !!(g && g.querySelector('[data-pick-door]'));
        }
        function textOf(el, sel) { var n = el.querySelector(sel); return n ? n.textContent.trim() : el.textContent.trim(); }
        function canStart() { return selRest.value !== '' && selOp.value !== '' && (!needsDoor() || selPorta.value !== ''); }

        function sequence() {
            var seq = [];
            if (!singleRest) seq.push('restaurante');
            seq.push('operacao');
            if (needsDoor()) seq.push('porta');
            seq.push('confirmar');
            return seq;
        }
        function renderDots(current) {
            if (!dotsWrap) return;
            var seq = sequence();
            var idx = seq.indexOf(current);
            var html = '';
            for (var i = 0; i < seq.length; i++) {
                html += '<span class="fb-startwiz__dot' + (i === idx ? ' is-active' : (i < idx ? ' is-done' : '')) + '"></span>';
            }
            dotsWrap.innerHTML = html;
        }
        function showPanel(step) {
            Object.keys(panels).forEach(function (k) { panels[k].hidden = k !== step; });
            if (backBtn) backBtn.hidden = history.length <= 1;
            renderDots(step);
            try { window.scrollTo(0, 0); } catch (e) {}
        }
        function goTo(step) {
            if (history[history.length - 1] !== step) history.push(step);
            showPanel(step);
        }
        function back() {
            if (history.length > 1) { history.pop(); showPanel(history[history.length - 1]); }
        }

        function saveDraft() {
            try {
                localStorage.setItem(draftKey, JSON.stringify({ restaurante_id: selRest.value, operacao_id: selOp.value, porta_id: selPorta.value }));
            } catch (e) {}
        }
        function clearPicked(root, sel) { root.querySelectorAll(sel + '.is-picked').forEach(function (el) { el.classList.remove('is-picked'); }); }

        function updateReview() {
            if (!reviewEl) return;
            var rows = ''
                + '<div class="fb-startwiz__reviewrow"><span class="fb-startwiz__reviewicon" style="--fb-pick-accent:' + (labels.restColor || '#1F1F1E') + '"><i class="bi ' + (labels.restIcon || 'bi-shop') + '"></i></span><div><span class="fb-startwiz__reviewlabel">Restaurante</span><span class="fb-startwiz__reviewval">' + (labels.rest || '—') + '</span></div></div>'
                + '<div class="fb-startwiz__reviewrow"><span class="fb-startwiz__reviewicon"><i class="bi bi-clock-history"></i></span><div><span class="fb-startwiz__reviewlabel">Serviço</span><span class="fb-startwiz__reviewval">' + (labels.op || '—') + '</span></div></div>';
            if (needsDoor()) {
                rows += '<div class="fb-startwiz__reviewrow"><span class="fb-startwiz__reviewicon"><i class="bi bi-door-open"></i></span><div><span class="fb-startwiz__reviewlabel">Porta</span><span class="fb-startwiz__reviewval">' + (labels.door || '—') + '</span></div></div>';
            }
            reviewEl.innerHTML = rows;
        }

        function pickOp(id, card) {
            selOp.value = String(id);
            if (stepOp) clearPicked(stepOp, '[data-pick-op]');
            if (card) card.classList.add('is-picked');
            labels.op = card ? textOf(card, '.fb-startpick__name') : '';
            var win = card ? card.querySelector('.fb-startpick__window') : null;
            if (win && labels.op) { labels.op += ' · ' + win.textContent.trim(); }
            saveDraft();
        }
        function pickDoor(id, chip) {
            selPorta.value = String(id);
            if (stepPorta) clearPicked(stepPorta, '[data-pick-door]');
            if (chip) chip.classList.add('is-picked');
            labels.door = chip ? chip.textContent.trim() : '';
            saveDraft();
        }
        function pickRest(id, card, skipPreselect) {
            selRest.value = String(id);
            restCards.forEach(function (c) { c.classList.toggle('is-picked', c === card); });
            labels.rest = card ? textOf(card, '.fb-startpick__name') : '';
            var iconEl = card ? card.querySelector('.fb-startpick__icon i') : null;
            labels.restIcon = iconEl ? iconEl.className.replace('bi ', '').trim() : '';
            labels.restColor = card ? card.style.getPropertyValue('--fb-pick-accent').trim() : '';
            var og = opGroupFor(id);
            opGroups.forEach(function (g) { g.hidden = g !== og; });
            selOp.value = ''; labels.op = '';
            if (og) clearPicked(og, '[data-pick-op]');
            var dg = doorGroupFor(id);
            doorGroups.forEach(function (g) { g.hidden = g !== dg; });
            selPorta.value = ''; labels.door = '';
            if (dg) clearPicked(dg, '[data-pick-door]');
            if (restHint) restHint.textContent = labels.rest;
            if (!skipPreselect && og) {
                var nowCard = og.querySelector('[data-now="1"]');
                if (nowCard) { pickOp(nowCard.dataset.pickOp, nowCard); }
            }
            saveDraft();
        }

        startForm.addEventListener('click', function (e) {
            var rest = e.target.closest ? e.target.closest('[data-pick-rest]') : null;
            if (rest) { dirty = true; pickRest(rest.dataset.pickRest, rest, false); goTo('operacao'); return; }
            var op = e.target.closest ? e.target.closest('[data-pick-op]') : null;
            if (op) { dirty = true; pickOp(op.dataset.pickOp, op); updateReview(); goTo(needsDoor() ? 'porta' : 'confirmar'); return; }
            var door = e.target.closest ? e.target.closest('[data-pick-door]') : null;
            if (door) { dirty = true; pickDoor(door.dataset.pickDoor, door); updateReview(); goTo('confirmar'); return; }
        });
        if (backBtn) backBtn.addEventListener('click', back);

        // Restaura seleção (preseleção do servidor > rascunho); wizard começa no 1º passo.
        var draft = {};
        try { draft = JSON.parse(localStorage.getItem(draftKey) || '{}'); } catch (e) { draft = {}; }
        var initRest = selRest.value || draft.restaurante_id || (singleRest && restCards.length === 1 ? restCards[0].dataset.pickRest : '');
        if (initRest) {
            var rc = restCards.find(function (c) { return c.dataset.pickRest === String(initRest); }) || null;
            var initOp = selOp.value || draft.operacao_id || '';
            pickRest(initRest, rc, initOp !== '');
            if (initOp !== '') {
                var og2 = opGroupFor(initRest);
                var opCard = og2 ? og2.querySelector('[data-pick-op="' + initOp + '"]') : null;
                if (opCard) { pickOp(initOp, opCard); }
            }
            var initDoor = selPorta.value || draft.porta_id || '';
            if (initDoor !== '') {
                var dg2 = doorGroupFor(initRest);
                var doorChip = dg2 ? dg2.querySelector('[data-pick-door="' + initDoor + '"]') : null;
                if (doorChip) { pickDoor(initDoor, doorChip); }
            }
            updateReview();
        }
        history = [(singleRest && selRest.value !== '') ? 'operacao' : 'restaurante'];
        showPanel(history[0]);

        window.addEventListener('beforeunload', function (e) {
            if (!dirty) return;
            e.preventDefault();
            e.returnValue = '';
        });

        startForm.addEventListener('submit', function (e) {
            if (!canStart()) { e.preventDefault(); goTo('restaurante'); return; }
            if (needConfirm && confirmEarly) { confirmEarly.checked = true; }
            if (confirmStart) { confirmStart.value = '1'; }
            dirty = false;
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
                    <?php
                    $identReg = restaurante_identidade($turno['restaurante']);
                    $nomeReg = (string)preg_replace('/^Restaurante\s+/iu', '', normalize_mojibake((string)$turno['restaurante']));
                    ?>
                    <div class="fb-rest-hero">
                        <span class="fb-rest-hero__icon" style="--rest-cor: <?= h($identReg['cor']) ?>;"><i class="bi <?= h($identReg['icone']) ?>" aria-hidden="true"></i></span>
                        <div>
                            <span class="fb-rest-hero__eyebrow">Você está em</span>
                            <span class="fb-rest-hero__name"><?= h($nomeReg) ?></span>
                            <span class="fb-rest-hero__op"><i class="bi bi-clock-history" aria-hidden="true"></i> <?= h(normalize_mojibake((string)$turno['operacao'])) ?></span>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap access-register-actions">
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

                <?php if ($restOp): ?>
                    <div id="shiftCountdown" class="app-inline-note is-info">
                        Tempo restante do turno: calculando...
                    </div>
                <?php endif; ?>

                <?php if (!empty($tematicaConflict)): ?>
                    <div class="app-inline-note is-warning">
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
                                    Confirmar parcial (no-show)
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if (!empty($duplicateConfirm)): ?>
                    <div class="app-inline-note is-warning">
                        <div class="fw-semibold mb-1">Registro duplicado imediato detectado.</div>
                        <div class="small mb-2">
                            UH <strong><?= h((string)($duplicateConfirm['uh_numero'] ?? '')) ?></strong>
                            | PAX <strong><?= (int)($duplicateConfirm['pax'] ?? 0) ?></strong>
                        </div>
                        <form method="post" action="/?r=access/register" class="d-flex gap-2 align-items-end flex-wrap mb-0">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="uh_numero" value="<?= h((string)($duplicateConfirm['uh_numero'] ?? '')) ?>">
                            <input type="hidden" name="pax" value="<?= (int)($duplicateConfirm['pax'] ?? 1) ?>">
                            <input type="hidden" name="confirm_duplicate" value="1">
                            <input type="hidden" name="tematica_processed" value="<?= (int)($duplicateConfirm['tematica_processed'] ?? 0) ?>">
                            <input type="hidden" name="confirm_no_show" value="<?= (int)($duplicateConfirm['confirm_no_show'] ?? 0) ?>">
                            <button
                                type="submit"
                                class="btn btn-outline-danger btn-sm"
                                data-confirm-title="Confirmar registro duplicado"
                                data-confirm="Esse lançamento é idêntico ao último registro. Deseja salvar mesmo assim?"
                                data-confirm-yes="Sim, registrar"
                                data-confirm-no="Não">
                                Registrar mesmo assim
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <form method="post" action="/?r=access/register" class="access-primary-form">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <div class="mb-3">
                    <label class="form-label">Número da UH</label>
                        <input type="text" name="uh_numero" class="form-control input-xl fb-input--big" inputmode="numeric" required autofocus>
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
                            <div class="access-pax-control">
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

                    <button type="submit" class="fb-btn fb-btn--primary fb-btn--lg w-100"><i class="bi bi-check2-circle me-1"></i>Registrar</button>
                </form>

                <?php if (!empty($lastEditableAccesses)): ?>
                    <hr class="my-4">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="text-uppercase text-muted small">Correção rápida (2 min)</div>
                            <div class="small text-muted">
                                Ajuste UH ou PAX dos dois últimos lançamentos feitos neste turno.
                            </div>
                        </div>
                        <span class="badge badge-warning">Janela curta</span>
                    </div>
                    <div class="d-grid gap-2">
                        <?php foreach ($lastEditableAccesses as $idx => $editable): ?>
                            <form method="post" action="/?r=access/correct_last" class="row g-2 align-items-end quick-correction-row">
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="access_id" value="<?= (int)$editable['id'] ?>">
                                <div class="col-12 small text-muted">
                                    <?= $idx === 0 ? 'Mais recente' : 'Registro anterior' ?>:
                                    UH <?= h(uh_label($editable['uh_numero'])) ?> - PAX <?= (int)$editable['pax'] ?>
                                </div>
                                <div class="col-5">
                                    <label class="form-label mb-1">UH</label>
                                    <input type="text" inputmode="numeric" name="uh_corrigida" class="form-control input-xl" value="<?= h($editable['uh_numero']) ?>" required>
                                </div>
                                <div class="col-4">
                                    <label class="form-label mb-1">PAX</label>
                                    <input type="number" min="1" name="pax_corrigido" class="form-control input-xl" value="<?= (int)$editable['pax'] ?>" required>
                                </div>
                                <div class="col-3">
                                    <button type="submit" class="btn btn-outline-primary btn-xl w-100" data-confirm="Confirmar correção deste lançamento?" data-confirm-title="Corrigir lançamento">
                                        OK
                                    </button>
                                </div>
                            </form>
                        <?php endforeach; ?>
                    </div>
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
                                    <td data-label="UH"><span class="uh-badge <?= uh_badge_class($item['uh_numero']) ?>"><?= h(uh_label($item['uh_numero'])) ?></span></td>
                                    <td data-label="PAX"><?= h($item['pax']) ?></td>
                                    <td data-label="Operação"><span class="tag <?= operation_badge_class($item['operacao']) ?>"><?= h($item['operacao']) ?></span></td>
                                    <td data-label="Status">
                                        <?php if (($item['status_operacional'] ?? '') === 'Duplicado'): ?>
                                            <span class="fb-badge fb-badge--duplicado">Duplicado</span>
                                        <?php elseif (($item['status_operacional'] ?? '') === 'Fora do Horário'): ?>
                                            <span class="fb-badge fb-badge--fora-horario">Fora do horário</span>
                                        <?php elseif (($item['status_operacional'] ?? '') === 'Múltiplo Acesso'): ?>
                                            <span class="fb-badge fb-badge--multiplo">Múltiplo acesso</span>
                                        <?php else: ?>
                                            <span class="fb-badge fb-badge--ok">OK</span>
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
                            <div class="app-inline-note is-info mb-0 small">
                                <strong>Dica:</strong> em caso de dúvida, registre "998 (Não informado)" ou "999 (Day use)".
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="app-inline-note is-warning mb-0 small">
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
                    countdownEl.className = 'app-inline-note is-info';
                    countdownEl.textContent = `Tempo restante do turno: ${diffMin} min`;
                } else if (diffMin > 0) {
                    countdownEl.className = 'app-inline-note is-warning';
                    countdownEl.textContent = `Tempo restante do turno: ${diffMin} min`;
                } else if (now <= tolEnd) {
                    countdownEl.className = 'app-inline-note is-warning';
                    const extraMin = Math.max(0, Math.ceil((tolEnd.getTime() - now.getTime()) / 60000));
                    countdownEl.textContent = `Turno fora do horário, aguardando tempo de tolerância (${extraMin} min).`;
                } else {
                    countdownEl.className = 'app-inline-note is-danger';
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
</div>

