<?php
$flash = $this->data['flash'] ?? null;
$restaurantes = $this->data['restaurantes'] ?? [];
$restOps = $this->data['restOps'] ?? [];
$doorsByRestaurant = $this->data['doorsByRestaurant'] ?? [];
$needConfirm = $this->data['need_confirm'] ?? false;
$preselect = $this->data['preselect'] ?? [];
?>
<div class="saas-page shift-start-page">
    <section class="saas-hero-card">
        <div class="saas-headline d-flex flex-wrap gap-3 align-items-start justify-content-between">
            <div>
                <div class="saas-label">Turno operacional</div>
                <h3 class="saas-title mb-1">Iniciar turno</h3>
                <p class="saas-subtitle mb-0">Selecione restaurante, operação e porta para começar o registro.</p>
            </div>
            <span class="badge badge-soft"><i class="bi bi-check2-circle"></i> Checklist rápido</span>
        </div>
    </section>

    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
        <section class="saas-table-card">
            <div class="shift-start-head">
                <div>
                    <h5 class="fw-bold mb-1">Dados do turno</h5>
                    <div class="text-muted small">Confirme as informações antes de iniciar.</div>
                </div>
                <i class="bi bi-door-open shift-start-icon"></i>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
            <?php endif; ?>

            <?php if ($needConfirm): ?>
                    <div class="alert alert-warning">
                        O turno está sendo iniciado fora do horário. Confirme para continuar.
                    </div>
            <?php endif; ?>

            <form method="post" action="/?r=turnos/start">
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
                        <div class="shift-start-checklist">
                            <i class="bi bi-info-circle"></i>
                            <span>Confira restaurante, operação e porta. Depois de iniciado, o turno fica vinculado ao seu usuário.</span>
                        </div>
                    </div>

                    <div class="col-12">
                        <button class="btn btn-success btn-xl w-100">Iniciar turno</button>
                    </div>
                </div>
            </form>
        </section>
        </div>
    </div>
</div>

<style>
    .shift-start-page {
        max-width: 1180px;
        margin: 0 auto;
        min-width: 0;
        overflow-x: hidden;
    }
    .shift-start-page .row {
        margin-left: 0;
        margin-right: 0;
    }
    .shift-start-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.1rem;
    }
    .shift-start-icon {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: color-mix(in srgb, var(--ab-accent) 12%, transparent);
        color: color-mix(in srgb, var(--ab-accent) 72%, var(--ab-ink) 28%);
        font-size: 1.25rem;
        flex: 0 0 auto;
    }
    .shift-start-page .form-label {
        font-weight: 650;
    }
    .shift-start-checklist {
        display: flex;
        align-items: flex-start;
        gap: 0.65rem;
        border: 1px solid color-mix(in srgb, var(--ab-border) 78%, transparent);
        border-radius: 14px;
        padding: 0.85rem 0.95rem;
        color: var(--ab-muted);
        background: color-mix(in srgb, var(--ab-soft) 72%, transparent);
        font-size: 0.92rem;
    }
    .shift-start-checklist i {
        color: color-mix(in srgb, var(--ab-accent) 78%, var(--ab-ink) 22%);
        margin-top: 0.1rem;
    }
    @media (max-width: 576px) {
        .shift-start-page .saas-hero-card,
        .shift-start-page .saas-table-card {
            padding: 1rem;
            border-radius: 16px;
        }
        .shift-start-page .saas-headline .badge {
            width: 100%;
            justify-content: center;
        }
        .shift-start-head {
            align-items: flex-start;
        }
        .shift-start-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
        }
    }
</style>

<script>
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

    // Evita manter seleção de outro restaurante (operação/porta incompatíveis).
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
}

restauranteSelect.addEventListener('change', updateFilters);
updateFilters();

const startForm = document.querySelector('form[action="/?r=turnos/start"]');
if (startForm) {
    startForm.addEventListener('submit', async (e) => {
        if (startForm.dataset.confirmPending === '1') {
            e.preventDefault();
            return;
        }
        const confirmStart = document.getElementById('confirm_start');
        if (confirmStart && confirmStart.value === '1') {
            return;
        }
        e.preventDefault();
        startForm.dataset.confirmPending = '1';
        const userName = '<?= h(Auth::user()['nome'] ?? '') ?>';
        const restLabel = restauranteSelect?.selectedOptions?.[0]?.text || 'N/D';
        const opLabel = operacaoSelect?.selectedOptions?.[0]?.text || 'N/D';
        const doorLabel = (portaWrapper?.style?.display !== 'none' ? (portaSelect?.selectedOptions?.[0]?.text || 'N/D') : 'N/A');
        const ok = await window.ocafConfirm({
            title: 'Confirmar início do turno',
            message:
                '<strong>Usuário:</strong> ' + userName + '<br>' +
                '<strong>Restaurante:</strong> ' + restLabel + '<br>' +
                '<strong>Operação:</strong> ' + opLabel + '<br>' +
                '<strong>Porta:</strong> ' + doorLabel,
            confirmText: 'Sim, iniciar turno',
            cancelText: 'Não'
        });
        startForm.dataset.confirmPending = '0';
        if (!ok) {
            return;
        }
        if (confirmStart) confirmStart.value = '1';
        startForm.submit();
    });
}

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
