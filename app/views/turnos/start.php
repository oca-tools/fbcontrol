<?php
$flash = $this->data['flash'] ?? null;
$restaurantes = $this->data['restaurantes'] ?? [];
$restOps = $this->data['restOps'] ?? [];
$doorsByRestaurant = $this->data['doorsByRestaurant'] ?? [];
$needConfirm = $this->data['need_confirm'] ?? false;
$preselect = $this->data['preselect'] ?? [];
?>
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

            <?php if ($needConfirm): ?>
                    <div class="alert alert-warning">
                        O turno está sendo iniciado fora do horário. Confirme para continuar.
                    </div>
            <?php endif; ?>

            <form method="post" action="/?r=turnos/start">
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
                        <button class="btn btn-success btn-xl w-100">Iniciar turno</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

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

    const hasPorta = Array.from(portaSelect.options).some(opt => opt.dataset.rest === restId);
    portaWrapper.style.display = hasPorta ? 'block' : 'none';
}

restauranteSelect.addEventListener('change', updateFilters);
updateFilters();
</script>
