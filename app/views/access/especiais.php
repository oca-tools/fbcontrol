<?php
$flash = $this->data['flash'] ?? null;
$mode = $this->data['mode'] ?? 'register';
$turno = $this->data['turno'] ?? null;
$special = $this->data['special'] ?? null;
$recentes = $this->data['recentes'] ?? [];
$toleranceAlert = $this->data['tolerance_alert'] ?? null;
$restaurantes = $this->data['restaurantes'] ?? [];
$doorsByRestaurant = $this->data['doorsByRestaurant'] ?? [];
$needConfirm = $this->data['need_confirm'] ?? false;
$preselect = $this->data['preselect'] ?? [];
?>
<style>
    .special-access-page,
    .special-access-page .row,
    .special-access-page [class*="col-"] {
        min-width: 0;
    }
    .special-access-page .card {
        overflow: hidden;
    }
    .special-access-actions {
        align-items: flex-start;
        justify-content: flex-end;
    }
    .special-primary-form {
        display: grid;
        gap: 1rem;
    }
    .special-primary-form .mb-3 {
        margin-bottom: 0 !important;
    }
    .special-pax-control {
        display: grid;
        grid-template-columns: 56px minmax(0, 1fr) 56px;
        gap: .65rem;
        align-items: stretch;
    }
    .special-pax-control .btn,
    .special-pax-control .form-control {
        min-height: 54px;
    }
    .special-live-table td,
    .special-live-table th {
        vertical-align: middle;
    }
    @media (max-width: 991.98px) {
        .special-access-page .card.p-4 {
            padding: 1rem !important;
            border-radius: 18px;
        }
        .special-access-actions {
            width: 100%;
            justify-content: stretch;
        }
        .special-access-actions form,
        .special-access-actions .btn {
            width: 100%;
        }
    }
    @media (max-width: 575.98px) {
        .special-access-page h3 {
            font-size: 1.25rem;
            line-height: 1.2;
        }
        .special-pax-control {
            grid-template-columns: 52px minmax(0, 1fr) 52px;
        }
        .special-live-table,
        .special-live-table tbody,
        .special-live-table tr,
        .special-live-table td {
            display: block;
            width: 100%;
        }
        .special-live-table thead {
            display: none;
        }
        .special-live-table tr {
            border: 1px solid var(--ab-border);
            border-radius: 16px;
            background: var(--ab-card);
            padding: .85rem;
            margin-bottom: .75rem;
            box-shadow: 0 10px 22px rgba(15, 23, 42, .06);
        }
        .special-live-table td {
            border: 0;
            padding: .35rem 0 !important;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            white-space: normal;
            text-align: right;
        }
        .special-live-table td::before {
            content: attr(data-label);
            color: var(--ab-muted);
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            text-align: left;
        }
        .special-live-table td .tag,
        .special-live-table td .uh-badge,
        .special-live-table td .badge {
            max-width: 62%;
            white-space: normal;
            text-align: center;
        }
        .special-live-table td[colspan] {
            display: block;
            text-align: left;
        }
        .special-live-table td[colspan]::before {
            content: "";
            display: none;
        }
    }
</style>

<div class="special-access-page">
<?php if ($mode === 'start'): ?>
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card p-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                    <div>
                        <div class="text-uppercase text-muted small">Serviços especiais</div>
                        <h3 class="fw-bold mb-1">Iniciar turno especial</h3>
                        <p class="text-muted mb-0">Selecione o restaurante temático ou a área Privileged.</p>
                    </div>
                    <span class="turno-pill">Checklist rápido</span>
                </div>

                <?php if ($toleranceAlert): ?>
                    <div class="app-inline-note is-warning"><?= h($toleranceAlert) ?></div>
                <?php endif; ?>

                <?php if ($needConfirm): ?>
                    <div class="app-inline-note is-warning">
                        O turno está sendo iniciado antes do horário. Confirme para continuar.
                    </div>
                <?php endif; ?>

                <form method="post" action="/?r=especiais/start">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Restaurante / Área</label>
                            <select class="form-select input-xl" name="restaurante_id" id="restaurante_id" required>
                                <option value="">Selecione</option>
                                <?php foreach ($restaurantes as $rest): ?>
                                    <option value="<?= (int)$rest['id'] ?>" <?= ($preselect['restaurante_id'] ?? '') == $rest['id'] ? 'selected' : '' ?>>
                                        <?= h($rest['nome']) ?> (<?= $rest['tipo'] === 'area' ? 'Privileged' : 'temático' ?>)
                                    </option>
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
                                        Confirmo o início antes do horário.
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
        filterOptions(portaSelect, restId);

        const hasPorta = Array.from(portaSelect.options).some(opt => opt.dataset.rest === restId);
        portaWrapper.style.display = hasPorta ? 'block' : 'none';
    }

    restauranteSelect.addEventListener('change', updateFilters);
    updateFilters();
    </script>
<?php else: ?>
    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <div class="card p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-uppercase text-muted small">Registro especial em tempo real</div>
                        <h3 class="fw-bold mb-1"><i class="bi bi-stars me-1"></i>
                            <span class="tag <?= restaurant_badge_class($turno['restaurante']) ?>"><?= h($turno['restaurante']) ?></span>
                        </h3>
                        <div class="text-muted">Serviço: <?= h($turno['tipo']) === 'privileged' ? 'Privileged' : 'temático' ?></div>
                    </div>
                    <form method="post" action="/?r=turnos/especial_end" class="special-access-actions">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <button class="btn btn-outline-danger"><i class="bi bi-box-arrow-right me-1"></i>Encerrar turno</button>
                    </form>
                </div>


                <form method="post" action="/?r=especiais/register" class="special-primary-form">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <div class="mb-3">
                        <label class="form-label">Número da UH</label>
                        <input type="text" name="uh_numero" class="form-control input-xl" inputmode="numeric" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Quantidade de PAX</label>
                        <div class="special-pax-control">
                            <button class="btn btn-outline-secondary btn-xl" type="button" onclick="adjustPax(-1)">-</button>
                            <input type="number" min="1" name="pax" id="pax" class="form-control input-xl text-center" value="1" <?= ($turno['exige_pax'] ?? 1) == 1 ? 'required' : '' ?>>
                            <button class="btn btn-outline-secondary btn-xl" type="button" onclick="adjustPax(1)">+</button>
                        </div>
                        <?php if (($turno['exige_pax'] ?? 1) == 0): ?>
                            <div class="text-muted small mt-2">PAX é opcional para Privileged, mas recomendamos registrar.</div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-success btn-xl w-100"><i class="bi bi-check2-circle me-1"></i>Registrar</button>
                </form>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0">Últimos acessos</h4>
                    <span class="text-muted small">Ao vivo</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle special-live-table">
                        <thead>
                            <tr>
                                <th>UH</th>
                                <th>PAX</th>
                                <th>Serviço</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentes as $item): ?>
                                <tr>
                                    <td data-label="UH"><span class="uh-badge <?= uh_badge_class($item['uh_numero']) ?>"><?= h($item['uh_numero']) ?></span></td>
                                    <td data-label="PAX"><?= h($item['pax']) ?></td>
                                    <td data-label="Serviço"><span class="tag <?= operation_badge_class($item['operacao']) ?>"><?= h($item['operacao']) ?></span></td>
                                    <td data-label="Status">
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
<?php endif; ?>
</div>

<script>
function adjustPax(delta) {
    const input = document.getElementById('pax');
    if (!input) return;
    const value = parseInt(input.value || '1', 10) + delta;
    input.value = Math.max(1, value);
}
</script>

