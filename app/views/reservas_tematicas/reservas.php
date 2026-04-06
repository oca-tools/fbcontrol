<?php
$flash = $this->data['flash'] ?? null;
$restaurantes = $this->data['restaurantes'] ?? [];
$turnos = $this->data['turnos'] ?? [];
$periodos = $this->data['periodos'] ?? [];
$availability = $this->data['availability'] ?? [];
$filters = $this->data['filters'] ?? [];
$canReserve = $this->data['can_reserve'] ?? false;
$editItem = $this->data['edit_item'] ?? null;
$isHostess = $this->data['is_hostess'] ?? false;
$user = Auth::user();

$tagsPadrao = [
    'Cortesia',
    'Aniversário',
    'Cupcake',
    'Reclamação',
    'Atenção especial',
    'VIP',
    'Restrição alimentar',
];
$selectedTags = [];
if ($editItem && !empty($editItem['observacao_tags'])) {
    $selectedTags = array_map('trim', explode(',', $editItem['observacao_tags']));
}
$availabilityDate = $filters['data'] ?? date('Y-m-d');
$quickDates = [
    ['label' => 'Hoje', 'date' => date('Y-m-d')],
    ['label' => 'Amanhã', 'date' => date('Y-m-d', strtotime('+1 day'))],
];
?>

<div class="card card-soft p-4 mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div class="section-title">
            <div class="icon"><i class="bi bi-calendar-heart"></i></div>
            <div>
                <div class="text-uppercase text-muted small">Reservas Temáticas</div>
                <h3 class="fw-bold mb-1">Ambiente de Reserva</h3>
                <div class="text-muted">Registre e gerencie reservas para Giardino, IX'u e La Brasa.</div>
            </div>
        </div>
        <span class="badge badge-soft">Jornada 1</span>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= h($flash['type']) ?> mt-3"><?= h($flash['message']) ?></div>
    <?php endif; ?>

    <div class="mt-3">
        <div class="text-muted small">Horários permitidos para reservas:</div>
        <div class="d-flex flex-wrap gap-2 mt-2">
            <?php foreach ($periodos as $p): ?>
                <span class="tag badge-soft"><i class="bi bi-clock me-1"></i><?= h($p['hora_inicio']) ?> - <?= h($p['hora_fim']) ?></span>
            <?php endforeach; ?>
        </div>
        <?php if ($isHostess && !$canReserve): ?>
            <div class="alert alert-warning mt-3">Fora do horário permitido para reservas. A criação está bloqueada para hostess.</div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-5">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="text-uppercase text-muted small"><?= $editItem ? 'Editar reserva' : 'Nova reserva' ?></div>
                    <h5 class="fw-bold mb-0">Cadastro rápido</h5>
                </div>
                <?php if ($isHostess && !$canReserve): ?>
                    <span class="badge badge-danger">Inativo</span>
                <?php else: ?>
                    <span class="badge badge-success">Ativo</span>
                <?php endif; ?>
            </div>

            <form method="post" action="/?r=reservasTematicas/reservas">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" id="reservaActionInput" value="<?= $editItem ? 'update' : 'create' ?>">
                <?php if ($editItem): ?>
                    <input type="hidden" name="id" value="<?= (int)$editItem['id'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Data da reserva</label>
                    <input type="date" class="form-control input-xl" name="data_reserva" value="<?= h($editItem['data_reserva'] ?? $filters['data'] ?? date('Y-m-d')) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Restaurante temático</label>
                    <select class="form-select input-xl" name="restaurante_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($restaurantes as $rest): ?>
                            <option value="<?= (int)$rest['id'] ?>" <?= ($editItem['restaurante_id'] ?? '') == $rest['id'] ? 'selected' : '' ?>>
                                <?= h($rest['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">UH</label>
                    <input type="text" class="form-control input-xl" name="uh_numero" inputmode="numeric" value="<?= h($editItem['uh_numero'] ?? '') ?>" placeholder="Ex: 402" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Titular da reserva</label>
                    <input type="text" class="form-control input-xl" name="titular_nome" value="<?= h($editItem['titular_nome'] ?? '') ?>" placeholder="Nome e sobrenome" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Grupo (opcional)</label>
                    <input type="text" class="form-control input-xl" name="grupo_nome" value="<?= h($editItem['grupo_nome'] ?? '') ?>" maxlength="120" placeholder="Ex: Famtour ABAV, Família Silva, Evento XYZ">
                    <div class="text-muted small mt-1">Use para identificar grupos comerciais/famílias, separado do conceito de lote técnico.</div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label">PAX adulto</label>
                        <input type="number" class="form-control input-xl text-center" min="1" name="pax_adulto" value="<?= h($editItem['pax_adulto'] ?? 1) ?>" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Qtd CHD</label>
                        <input type="number" class="form-control input-xl text-center" min="0" name="qtd_chd" value="<?= h($editItem['qtd_chd'] ?? 0) ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Idades CHD</label>
                        <input type="text" class="form-control input-xl" name="chd_idades" value="<?= h($editItem['chd_idades'] ?? '') ?>" placeholder="Ex: 3,7">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Turno de operação</label>
                    <select class="form-select input-xl" name="turno_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($turnos as $turno): ?>
                            <option value="<?= (int)$turno['id'] ?>" <?= ($editItem['turno_id'] ?? '') == $turno['id'] ? 'selected' : '' ?>>
                                <?= h($turno['hora']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Marcadores rápidos</label>
                    <div class="tag-grid">
                        <?php foreach ($tagsPadrao as $tag): ?>
                            <?php $tagId = 'tag_' . md5($tag); ?>
                            <div class="tag-choice">
                                <input type="checkbox" id="<?= h($tagId) ?>" name="observacao_tags[]" value="<?= h($tag) ?>" <?= in_array($tag, $selectedTags, true) ? 'checked' : '' ?>>
                                <label for="<?= h($tagId) ?>"><i class="bi bi-bookmark-star"></i><?= h($tag) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Observações</label>
                    <textarea class="form-control" name="observacao_reserva" rows="3" placeholder="Observações gerais..."><?= h($editItem['observacao_reserva'] ?? '') ?></textarea>
                </div>
                <?php if (!$editItem): ?>
                    <div class="card border-0 bg-light-subtle p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                            <div>
                                <div class="text-uppercase text-muted small">Reserva em lote</div>
                                <div class="fw-semibold">Múltiplas UHs no mesmo atendimento</div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btnToggleBatch">Ativar lote</button>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Responsável do grupo</label>
                            <input type="text" class="form-control" name="grupo_responsavel" placeholder="Nome de quem solicitou o lote">
                        </div>
                        <div id="batchContainer" class="d-none">
                            <div id="batchRows"></div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="btnAddBatchRow">
                                <i class="bi bi-plus-circle me-1"></i>Adicionar UH
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (in_array(($user['perfil'] ?? ''), ['admin', 'supervisor'], true)): ?>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="excedente" name="excedente" <?= !empty($editItem['excedente']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="excedente">Reserva excedente</label>
                        </div>
                        <input type="text" class="form-control mt-2" name="excedente_motivo" placeholder="Motivo do excedente" value="<?= h($editItem['excedente_motivo'] ?? '') ?>">
                    </div>
                <?php endif; ?>

                <button class="btn btn-primary btn-xl w-100" <?= !$canReserve ? 'disabled' : '' ?>>
                    <i class="bi bi-check2-circle me-1"></i><?= $editItem ? 'Salvar alterações' : 'Registrar reserva' ?>
                </button>
                <?php if ($editItem): ?>
                    <a class="btn btn-outline-primary btn-xl w-100 mt-2" href="/?r=reservasTematicas/reservas">Cancelar edição</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="col-12 col-lg-7">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="text-uppercase text-muted small">Disponibilidade</div>
                    <h5 class="fw-bold mb-0">Capacidade por restaurante e turno</h5>
                    <div class="text-muted small mt-1" id="availabilityDateLabel">Data: <?= h(date('d/m/Y', strtotime($availabilityDate))) ?></div>
                </div>
                <span class="badge badge-soft">Atualizado</span>
            </div>

            <div class="d-flex flex-wrap gap-2 mb-3">
                <?php foreach ($quickDates as $quick): ?>
                    <?php $isActive = ($availabilityDate === $quick['date']); ?>
                    <button
                        type="button"
                        class="btn <?= $isActive ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm js-quick-date"
                        data-date="<?= h($quick['date']) ?>"
                    >
                        <?= h($quick['label']) ?>
                    </button>
                <?php endforeach; ?>
                <div class="d-flex align-items-center gap-2">
                    <input type="date" class="form-control form-control-sm" id="availabilityDateInput" value="<?= h($availabilityDate) ?>">
                    <button class="btn btn-outline-primary btn-sm" type="button" id="btnAvailabilityGo">Ir</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Restaurante</th>
                            <?php foreach ($turnos as $turno): ?>
                                <th><?= h($turno['hora']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($restaurantes as $rest): ?>
                            <tr>
                                <td><span class="tag <?= restaurant_badge_class($rest['nome']) ?>"><?= h($rest['nome']) ?></span></td>
                                <?php foreach ($turnos as $turno): ?>
                                    <?php
                                        $info = $availability[$rest['id']][$turno['id']] ?? ['capacidade' => 0, 'reservado' => 0, 'restante' => 0];
                                        $status = $info['restante'] > 0 ? 'badge-success' : 'badge-danger';
                                    ?>
                                    <td
                                        data-rest-id="<?= (int)$rest['id'] ?>"
                                        data-turno-id="<?= (int)$turno['id'] ?>"
                                        data-rest-nome="<?= h($rest['nome']) ?>"
                                        data-turno-hora="<?= h($turno['hora']) ?>"
                                    >
                                        <span class="badge <?= $status ?> js-availability-restante" role="button" title="Clique para ver o resumo do turno"><?= (int)$info['restante'] ?></span>
                                        <div class="text-muted small js-availability-rc"><?= (int)$info['reservado'] ?>/<?= (int)$info['capacidade'] ?></div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const dateLabel = document.getElementById('availabilityDateLabel');
    const dateInput = document.getElementById('availabilityDateInput');
    const goBtn = document.getElementById('btnAvailabilityGo');
    const quickBtns = Array.from(document.querySelectorAll('.js-quick-date'));
    const reservaDateInput = document.querySelector('input[name="data_reserva"]');
    const restauranteSelect = document.querySelector('select[name="restaurante_id"]');
    const turnoSelect = document.querySelector('select[name="turno_id"]');
    const excedenteCheckbox = document.getElementById('excedente');
    const availabilityCache = {};

    const fmtBr = (iso) => {
        if (!iso || !/^\d{4}-\d{2}-\d{2}$/.test(iso)) return '--/--/----';
        const [y,m,d] = iso.split('-');
        return `${d}/${m}/${y}`;
    };
    const escapeHtml = (value) => (value ?? '')
        .toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    const statusLabel = (status) => {
        const map = {
            Reservada: 'Reservada',
            Finalizada: 'Finalizada',
            'Nao compareceu': 'Não compareceu',
            Cancelada: 'Cancelada',
            Divergencia: 'Divergência',
            Excedente: 'Excedente'
        };
        return map[status] || status;
    };

    const paintAvailability = (payload) => {
        const data = payload?.availability || {};
        availabilityCache[payload?.date || ''] = data;
        document.querySelectorAll('td[data-rest-id][data-turno-id]').forEach((cell) => {
            const restId = cell.getAttribute('data-rest-id');
            const turnoId = cell.getAttribute('data-turno-id');
            const info = data?.[restId]?.[turnoId] || { capacidade: 0, reservado: 0, restante: 0 };
            cell.dataset.restante = String(parseInt(info.restante || 0, 10));
            cell.dataset.reservado = String(parseInt(info.reservado || 0, 10));
            cell.dataset.capacidade = String(parseInt(info.capacidade || 0, 10));
            const badge = cell.querySelector('.js-availability-restante');
            const rc = cell.querySelector('.js-availability-rc');
            if (badge) {
                badge.textContent = String(parseInt(info.restante || 0, 10));
                badge.classList.remove('badge-success', 'badge-danger');
                badge.classList.add((parseInt(info.restante || 0, 10) > 0) ? 'badge-success' : 'badge-danger');
            }
            if (rc) {
                rc.textContent = `${parseInt(info.reservado || 0, 10)}/${parseInt(info.capacidade || 0, 10)}`;
            }
        });
    };

    const setQuickActive = (date) => {
        quickBtns.forEach((btn) => {
            const isActive = btn.dataset.date === date;
            btn.classList.toggle('btn-primary', isActive);
            btn.classList.toggle('btn-outline-primary', !isActive);
        });
    };

    const fetchAvailability = async (date) => {
        if (!date) return;
        if (availabilityCache[date]) {
            return { ok: true, date, availability: availabilityCache[date] };
        }
        const url = `/?r=reservasTematicas/reservas&ajax=availability&data=${encodeURIComponent(date)}`;
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (!res.ok) return null;
        const payload = await res.json();
        if (!payload?.ok) return null;
        return payload;
    };

    const applyTurnoAvailability = async () => {
        if (!restauranteSelect || !turnoSelect || !reservaDateInput) return;
        const restId = restauranteSelect.value;
        const date = reservaDateInput.value || dateInput?.value || '';
        if (!restId || !date) {
            Array.from(turnoSelect.options).forEach((opt) => {
                if (!opt.value) return;
                opt.hidden = false;
                opt.disabled = false;
            });
            return;
        }
        const payload = await fetchAvailability(date);
        if (!payload?.ok) return;
        const availability = payload.availability || {};
        const byTurno = availability?.[restId] || {};
        const canUseExcedente = !!(excedenteCheckbox && excedenteCheckbox.checked);
        let selectedBlocked = false;

        Array.from(turnoSelect.options).forEach((opt) => {
            if (!opt.value) return;
            const info = byTurno?.[opt.value] || { capacidade: 0, restante: 0 };
            const capacidade = parseInt(info.capacidade || 0, 10);
            const restante = parseInt(info.restante || 0, 10);
            const lotado = capacidade > 0 && restante <= 0;
            const blocked = lotado && !canUseExcedente;
            opt.hidden = blocked;
            opt.disabled = blocked;
            if (blocked && opt.selected) {
                selectedBlocked = true;
            }
        });

        if (selectedBlocked) {
            turnoSelect.value = '';
            if (window.Swal) {
                window.Swal.fire({
                    icon: 'info',
                    title: 'Turno lotado',
                    text: 'Esse turno está lotado para a data e restaurante selecionados.'
                });
            }
        }
    };

    const loadAvailability = async (date) => {
        if (!date) return;
        const payload = await fetchAvailability(date);
        if (!payload?.ok) return;
        paintAvailability(payload);
        if (dateLabel) dateLabel.textContent = `Data: ${fmtBr(payload.date || date)}`;
        if (dateInput) dateInput.value = payload.date || date;
        setQuickActive(payload.date || date);
        if (reservaDateInput && !reservaDateInput.value) {
            reservaDateInput.value = payload.date || date;
        }
        await applyTurnoAvailability();
    };

    quickBtns.forEach((btn) => btn.addEventListener('click', () => loadAvailability(btn.dataset.date || '')));
    goBtn?.addEventListener('click', () => loadAvailability(dateInput?.value || ''));
    reservaDateInput?.addEventListener('change', applyTurnoAvailability);
    restauranteSelect?.addEventListener('change', applyTurnoAvailability);
    excedenteCheckbox?.addEventListener('change', applyTurnoAvailability);

    document.querySelectorAll('.js-availability-restante').forEach((badge) => {
        badge.addEventListener('click', async (event) => {
            const cell = event.currentTarget.closest('td[data-rest-id][data-turno-id]');
            if (!cell) return;
            const restId = cell.getAttribute('data-rest-id');
            const turnoId = cell.getAttribute('data-turno-id');
            const restNome = cell.getAttribute('data-rest-nome') || 'Restaurante';
            const turnoHora = cell.getAttribute('data-turno-hora') || '--:--';
            const date = dateInput?.value || reservaDateInput?.value || '';
            if (!date) return;

            const url = `/?r=reservasTematicas/reservas&ajax=availability_detail&data=${encodeURIComponent(date)}&restaurante_id=${encodeURIComponent(restId)}&turno_id=${encodeURIComponent(turnoId)}`;
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) return;
            const payload = await res.json();
            if (!payload?.ok) return;

            const rows = (payload.items || []).map((item) => `
                <tr>
                    <td>${escapeHtml(item.uh_numero || '-')}</td>
                    <td>${escapeHtml(item.titular_nome || '-')}</td>
                    <td>${escapeHtml(String(item.pax ?? 0))}</td>
                    <td>${escapeHtml(statusLabel(item.status || ''))}</td>
                </tr>
            `).join('');
            const html = `
                <div class="text-start">
                    <div class="small text-muted mb-2">Data ${escapeHtml(fmtBr(payload.date || date))} · ${escapeHtml(restNome)} · ${escapeHtml(turnoHora)}</div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-2">
                            <thead><tr><th>UH</th><th>Titular</th><th>PAX</th><th>Status</th></tr></thead>
                            <tbody>${rows || '<tr><td colspan="4" class="text-muted">Sem reservas neste turno.</td></tr>'}</tbody>
                        </table>
                    </div>
                    <div class="fw-semibold">Total de reservas: ${escapeHtml(String(payload.count || 0))} · Total de PAX: ${escapeHtml(String(payload.total_pax || 0))}</div>
                </div>
            `;

            if (window.Swal) {
                window.Swal.fire({
                    title: `Resumo do turno`,
                    html,
                    width: 860,
                    confirmButtonText: 'Fechar'
                });
            }
        });
    });

    const toggleBatchBtn = document.getElementById('btnToggleBatch');
    const batchContainer = document.getElementById('batchContainer');
    const addBatchRowBtn = document.getElementById('btnAddBatchRow');
    const batchRows = document.getElementById('batchRows');
    const actionInput = document.getElementById('reservaActionInput');
    const singleFields = [
        document.querySelector('input[name=\"uh_numero\"]'),
        document.querySelector('input[name=\"titular_nome\"]'),
        document.querySelector('input[name=\"pax_adulto\"]')
    ];

    const batchTemplate = () => {
        const wrap = document.createElement('div');
        wrap.className = 'border rounded-3 p-2 mb-2';
        wrap.innerHTML = `
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-2"><label class="form-label">UH</label><input class="form-control" name="batch_uh_numero[]" inputmode="numeric" required></div>
                <div class="col-12 col-md-3"><label class="form-label">Titular</label><input class="form-control" name="batch_titular_nome[]" required></div>
                <div class="col-12 col-md-2"><label class="form-label">Adulto</label><input type="number" class="form-control" min="1" name="batch_pax_adulto[]" value="1" required></div>
                <div class="col-12 col-md-2"><label class="form-label">Qtd CHD</label><input type="number" class="form-control" min="0" name="batch_qtd_chd[]" value="0"></div>
                <div class="col-12 col-md-2"><label class="form-label">Idades CHD</label><input class="form-control" name="batch_chd_idades[]" placeholder="3,7"></div>
                <div class="col-12 col-md-1 d-grid"><button type="button" class="btn btn-outline-danger btn-sm js-remove-batch-row">X</button></div>
            </div>
        `;
        wrap.querySelector('.js-remove-batch-row')?.addEventListener('click', () => wrap.remove());
        return wrap;
    };

    const setBatchEnabled = (enabled) => {
        if (!batchRows) return;
        batchRows.querySelectorAll('input').forEach((input) => {
            input.disabled = !enabled;
        });
    };

    toggleBatchBtn?.addEventListener('click', () => {
        if (!batchContainer || !actionInput) return;
        const active = !batchContainer.classList.contains('d-none');
        if (active) {
            batchContainer.classList.add('d-none');
            actionInput.value = 'create';
            toggleBatchBtn.textContent = 'Ativar lote';
            setBatchEnabled(false);
            singleFields.forEach((el) => {
                if (!el) return;
                el.disabled = false;
                el.required = true;
            });
        } else {
            batchContainer.classList.remove('d-none');
            actionInput.value = 'create_batch';
            toggleBatchBtn.textContent = 'Desativar lote';
            setBatchEnabled(true);
            singleFields.forEach((el) => {
                if (!el) return;
                el.disabled = true;
                el.required = false;
            });
            if (batchRows && batchRows.children.length === 0) {
                batchRows.appendChild(batchTemplate());
            }
        }
    });

    addBatchRowBtn?.addEventListener('click', () => {
        if (!batchRows) return;
        const node = batchTemplate();
        if (batchContainer && !batchContainer.classList.contains('d-none')) {
            node.querySelectorAll('input').forEach((input) => (input.disabled = false));
        } else {
            node.querySelectorAll('input').forEach((input) => (input.disabled = true));
        }
        batchRows.appendChild(node);
    });

    applyTurnoAvailability();
})();
</script>
