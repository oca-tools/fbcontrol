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

<div class="saas-page reservas-tematicas-page">
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

<style>
.availability-vertical-grid {
    display: grid;
    gap: 1rem;
}

.availability-restaurant-card {
    border: 1px solid var(--border-soft, rgba(15, 23, 42, 0.08));
    border-radius: 1rem;
    padding: 0.95rem;
    background: var(--card-surface, rgba(255, 255, 255, 0.75));
}

.availability-turnos-grid {
    display: grid;
    gap: 0.65rem;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    margin-top: 0.7rem;
}

.availability-turno-tile {
    border: 1px solid var(--border-soft, rgba(15, 23, 42, 0.08));
    border-radius: 0.85rem;
    padding: 0.6rem;
    background: var(--surface-muted, rgba(248, 250, 252, 0.85));
    text-align: center;
}

.availability-turno-time {
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    color: var(--text-muted, #6b7280);
}

.availability-turno-values {
    margin-top: 0.45rem;
}

.availability-turno-values .badge {
    font-size: 0.95rem;
    min-width: 42px;
}

.availability-turno-ratio {
    margin-top: 0.35rem;
    font-size: 0.8rem;
    color: var(--text-muted, #6b7280);
}

.batch-rows {
    display: grid;
    gap: 0.55rem;
}

.batch-row-wrap {
    border: 1px solid var(--ab-border);
    border-radius: 0.8rem;
    padding: 0.55rem;
    overflow-x: auto;
}

.batch-row-grid {
    display: grid;
    grid-template-columns: 92px 110px minmax(140px, 1fr) minmax(160px, 1.2fr) 44px;
    gap: 0.5rem;
    align-items: end;
    min-width: 560px;
}

.batch-row-grid .form-label {
    font-size: 0.76rem;
    margin-bottom: 0.2rem;
}

.batch-hint {
    font-size: 0.82rem;
    color: var(--text-muted, #6b7280);
}

@media (max-width: 991px) {
    .batch-row-grid {
        min-width: 100%;
        grid-template-columns: 1fr 1fr;
    }

    .batch-row-grid > div:last-child {
        grid-column: span 2;
    }
}
</style>

<div class="row g-4">
    <div class="col-12">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="text-uppercase text-muted small"><?= $editItem ? 'Editar reserva' : 'Nova reserva' ?></div>
                    <h5 class="fw-bold mb-0">Cadastro rápido</h5>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#availabilityModal">
                        <i class="bi bi-grid-3x3-gap me-1"></i>Ver capacidade
                    </button>
                    <?php if ($isHostess && !$canReserve): ?>
                        <span class="badge badge-danger">Inativo</span>
                    <?php else: ?>
                        <span class="badge badge-success">Ativo</span>
                    <?php endif; ?>
                </div>
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

                <div class="row g-2 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">1) Restaurante</label>
                        <select class="form-select input-xl" name="restaurante_id" required>
                            <option value="">Selecione</option>
                            <?php foreach ($restaurantes as $rest): ?>
                                <option value="<?= (int)$rest['id'] ?>" <?= ($editItem['restaurante_id'] ?? '') == $rest['id'] ? 'selected' : '' ?>>
                                    <?= h($rest['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">2) Turno</label>
                        <select class="form-select input-xl" name="turno_id" required>
                            <option value="">Selecione</option>
                            <?php foreach ($turnos as $turno): ?>
                                <option value="<?= (int)$turno['id'] ?>" <?= ($editItem['turno_id'] ?? '') == $turno['id'] ? 'selected' : '' ?>>
                                    <?= h($turno['hora']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <?php if (!$editItem): ?>
                    <div class="mb-3">
                        <label class="form-label">Tipo de registro</label>
                        <div class="btn-group w-100" role="group" aria-label="Tipo de reserva">
                            <button type="button" class="btn btn-primary" id="btnModeSingle">Individual</button>
                            <button type="button" class="btn btn-outline-primary" id="btnModeBatch">Lote</button>
                        </div>
                        <div class="batch-hint mt-1">No modo lote, a tela troca para múltiplas UHs e reduz a poluição visual.</div>
                    </div>
                <?php endif; ?>

                <div id="singleReservationPanel">
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
                        <div class="col-12 col-md-5">
                            <label class="form-label">Qtd PAX</label>
                            <input type="number" class="form-control input-xl text-center" min="1" name="pax" value="<?= h($editItem['pax'] ?? 1) ?>" required>
                        </div>
                        <div class="col-12 col-md-7">
                            <label class="form-label">Idades CHD (opcional)</label>
                            <input type="text" class="form-control input-xl" name="chd_idades" value="<?= h($editItem['chd_idades'] ?? '') ?>" placeholder="Ex: 3, 7">
                        </div>
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
                </div>

                <?php if (!$editItem): ?>
                    <div id="batchReservationPanel" class="card border-0 bg-light-subtle p-3 mb-3 d-none">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                            <div>
                                <div class="text-uppercase text-muted small">Reserva em lote</div>
                                <div class="fw-semibold">Múltiplas UHs no mesmo atendimento</div>
                            </div>
                            <span class="badge badge-soft">Modo lote</span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Grupo (opcional)</label>
                            <input type="text" class="form-control" name="grupo_nome" maxlength="120" placeholder="Ex: Famtour ABAV, Família Silva, Evento XYZ">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Marcadores rápidos</label>
                            <div class="tag-grid">
                                <?php foreach ($tagsPadrao as $tag): ?>
                                    <?php $tagId = 'batch_tag_' . md5($tag); ?>
                                    <div class="tag-choice">
                                        <input type="checkbox" id="<?= h($tagId) ?>" name="observacao_tags[]" value="<?= h($tag) ?>">
                                        <label for="<?= h($tagId) ?>"><i class="bi bi-bookmark-star"></i><?= h($tag) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Observações</label>
                            <textarea class="form-control" name="observacao_reserva" rows="3" placeholder="Observações gerais para o lote..."></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Titular padrão do lote</label>
                            <input type="text" class="form-control" name="grupo_responsavel" id="batchDefaultTitular" placeholder="Nome que pode ser repetido em todas as UHs">
                            <div class="batch-hint mt-1">Preencha uma vez e ajuste por UH apenas quando for diferente.</div>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" value="1" id="batchApplyDefault" checked>
                            <label class="form-check-label" for="batchApplyDefault">Aplicar titular padrão nas UHs sem nome informado</label>
                        </div>
                        <div id="batchContainer">
                            <div class="batch-hint mb-2">Fluxo rápido: UH + PAX + CHD por UH. Titular por UH é opcional.</div>
                            <div id="batchRows" class="batch-rows"></div>
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

</div>

<div class="modal fade" id="availabilityModal" tabindex="-1" aria-labelledby="availabilityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <div class="text-uppercase text-muted small">Disponibilidade</div>
                    <h5 class="fw-bold mb-0" id="availabilityModalLabel">Capacidade por restaurante e turno</h5>
                    <div class="text-muted small mt-1" id="availabilityDateLabel">Data: <?= h(date('d/m/Y', strtotime($availabilityDate))) ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
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

                <div class="availability-vertical-grid">
                    <?php foreach ($restaurantes as $rest): ?>
                        <div class="availability-restaurant-card">
                            <span class="tag <?= restaurant_badge_class($rest['nome']) ?>"><?= h($rest['nome']) ?></span>
                            <div class="availability-turnos-grid">
                                <?php foreach ($turnos as $turno): ?>
                                    <?php
                                        $info = $availability[$rest['id']][$turno['id']] ?? ['capacidade' => 0, 'reservado' => 0, 'restante' => 0];
                                        $status = $info['restante'] > 0 ? 'badge-success' : 'badge-danger';
                                    ?>
                                    <div
                                        class="availability-turno-tile js-availability-cell"
                                        data-rest-id="<?= (int)$rest['id'] ?>"
                                        data-turno-id="<?= (int)$turno['id'] ?>"
                                        data-rest-nome="<?= h($rest['nome']) ?>"
                                        data-turno-hora="<?= h($turno['hora']) ?>"
                                    >
                                        <div class="availability-turno-time"><?= h($turno['hora']) ?></div>
                                        <div class="availability-turno-values">
                                            <span class="badge <?= $status ?> js-availability-restante" role="button" title="Clique para ver os detalhes do turno"><?= (int)$info['restante'] ?></span>
                                        </div>
                                        <div class="availability-turno-ratio js-availability-rc"><span class="js-availability-reservado" role="button" title="Clique para ver as reservas preenchidas do turno"><?= (int)$info['reservado'] ?></span>/<?= (int)$info['capacidade'] ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Fechar</button>
            </div>
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
    const setTurnoSequentialState = () => {
        if (!restauranteSelect || !turnoSelect) return;
        const hasRestaurant = restauranteSelect.value !== '';
        turnoSelect.disabled = !hasRestaurant;
        if (!hasRestaurant) {
            turnoSelect.value = '';
        }
    };

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
    const paintAvailability = (payload) => {
        const data = payload?.availability || {};
        availabilityCache[payload?.date || ''] = data;
        document.querySelectorAll('.js-availability-cell[data-rest-id][data-turno-id]').forEach((cell) => {
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
                rc.innerHTML = `<span class="js-availability-reservado" role="button" title="Clique para ver as reservas preenchidas do turno">${parseInt(info.reservado || 0, 10)}</span>/${parseInt(info.capacidade || 0, 10)}`;
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
    restauranteSelect?.addEventListener('change', () => {
        setTurnoSequentialState();
        applyTurnoAvailability();
    });
    excedenteCheckbox?.addEventListener('change', applyTurnoAvailability);

    const showTurnoPopup = (html) => {
        if (window.Swal) {
            window.Swal.fire({
                title: 'Detalhes do turno',
                html,
                width: 920,
                confirmButtonText: 'Fechar'
            });
            return;
        }

        if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            let modalEl = document.getElementById('availabilityDetailModal');
            if (!modalEl) {
                modalEl = document.createElement('div');
                modalEl.id = 'availabilityDetailModal';
                modalEl.className = 'modal fade';
                modalEl.tabIndex = -1;
                modalEl.innerHTML = `
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Detalhes do turno</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                            </div>
                            <div class="modal-body" id="availabilityDetailModalBody"></div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Fechar</button>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modalEl);
            }
            const bodyEl = modalEl.querySelector('#availabilityDetailModalBody');
            if (bodyEl) bodyEl.innerHTML = html;
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
            return;
        }

        const textFallback = document.createElement('div');
        textFallback.innerHTML = html;
        window.alert(textFallback.textContent || 'Detalhes do turno');
    };

    const openAvailabilityDetail = async (triggerEl) => {
        const cell = triggerEl?.closest('.js-availability-cell[data-rest-id][data-turno-id]');
        if (!cell) return;
        const restId = cell.getAttribute('data-rest-id');
        const turnoId = cell.getAttribute('data-turno-id');
        const restNome = cell.getAttribute('data-rest-nome') || 'Restaurante';
        const turnoHora = cell.getAttribute('data-turno-hora') || '--:--';
        const date = dateInput?.value || reservaDateInput?.value || '';
        if (!date) return;

        try {
            const url = `/?r=reservasTematicas/reservas&ajax=availability_detail&data=${encodeURIComponent(date)}&restaurante_id=${encodeURIComponent(restId)}&turno_id=${encodeURIComponent(turnoId)}`;
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error('Falha ao buscar detalhes do turno.');
            const payload = await res.json();
            if (!payload?.ok) throw new Error(payload?.message || 'Não foi possível carregar os detalhes.');

            const rows = (payload.items || []).map((item) => `
                <tr>
                    <td>${escapeHtml(item.titular_nome || '-')}</td>
                    <td>${escapeHtml(item.uh_numero || '-')}</td>
                    <td>${escapeHtml(String(item.pax ?? 0))}</td>
                    <td>${escapeHtml(String(item.qtd_chd ?? 0))}</td>
                    <td class="text-end">
                        <a class="btn btn-outline-primary btn-sm" href="${escapeHtml(item.edit_url || '#')}">Editar</a>
                    </td>
                </tr>
            `).join('');
        const restante = parseInt(String(payload.restante ?? cell.dataset.restante ?? '0'), 10) || 0;
        const reservado = parseInt(String(payload.reservado ?? cell.dataset.reservado ?? '0'), 10) || 0;
        const capacidade = parseInt(String(payload.capacidade ?? cell.dataset.capacidade ?? '0'), 10) || 0;
            const html = `
                <div class="text-start">
                    <div class="small text-muted mb-2">Data ${escapeHtml(fmtBr(payload.date || date))} · ${escapeHtml(restNome)} · ${escapeHtml(turnoHora)}</div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-2">
                            <thead><tr><th>Nome</th><th>UH</th><th>PAX</th><th>CHD</th><th></th></tr></thead>
                            <tbody>${rows || '<tr><td colspan="5" class="text-muted">Sem reservas neste turno.</td></tr>'}</tbody>
                        </table>
                    </div>
                    <div class="fw-semibold">Disponíveis: ${escapeHtml(String(restante))} · Preenchidas: ${escapeHtml(String(reservado))}/${escapeHtml(String(capacidade))}</div>
                    <div class="small text-muted">Total de reservas: ${escapeHtml(String(payload.count || 0))} · Total de PAX: ${escapeHtml(String(payload.total_pax || 0))} · Total CHD: ${escapeHtml(String(payload.total_chd || 0))}</div>
                </div>
            `;
            showTurnoPopup(html);
        } catch (err) {
            const msg = err?.message || 'Erro ao carregar detalhes.';
            if (window.Swal) {
                window.Swal.fire({ icon: 'error', title: 'Não foi possível abrir', text: msg });
            } else {
                window.alert(msg);
            }
        }
    };

    document.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target : null;
        if (!target) return;
        const trigger = target.closest('.js-availability-restante, .js-availability-reservado');
        if (!trigger) return;
        openAvailabilityDetail(trigger);
    });
    const btnModeSingle = document.getElementById('btnModeSingle');
    const btnModeBatch = document.getElementById('btnModeBatch');
    const singleReservationPanel = document.getElementById('singleReservationPanel');
    const batchReservationPanel = document.getElementById('batchReservationPanel');
    const addBatchRowBtn = document.getElementById('btnAddBatchRow');
    const batchRows = document.getElementById('batchRows');
    const batchDefaultTitular = document.getElementById('batchDefaultTitular');
    const batchApplyDefault = document.getElementById('batchApplyDefault');
    const actionInput = document.getElementById('reservaActionInput');
    const reservaForm = document.querySelector('form[action="/?r=reservasTematicas/reservas"]');
    const singleFields = [
        document.querySelector('input[name=\"uh_numero\"]'),
        document.querySelector('input[name=\"titular_nome\"]'),
        document.querySelector('input[name=\"pax\"]')
    ];

    const batchTemplate = () => {
        const wrap = document.createElement('div');
        wrap.className = 'batch-row-wrap';
        wrap.innerHTML = `
            <div class="batch-row-grid">
                <div><label class="form-label">UH</label><input class="form-control" name="batch_uh_numero[]" inputmode="numeric" required></div>
                <div><label class="form-label">Qtd PAX</label><input type="number" class="form-control" min="1" name="batch_pax[]" value="1" required></div>
                <div><label class="form-label">Idades CHD</label><input class="form-control" name="batch_chd_idades[]" placeholder="Ex: 3,7"></div>
                <div><label class="form-label">Titular desta UH (opcional)</label><input class="form-control" name="batch_titular_nome[]" placeholder="Em branco = usar titular padrão"></div>
                <div class="d-grid"><button type="button" class="btn btn-outline-danger btn-sm js-remove-batch-row">X</button></div>
            </div>
        `;
        wrap.querySelector('.js-remove-batch-row')?.addEventListener('click', () => wrap.remove());
        return wrap;
    };

    const applyDefaultTitularToEmptyRows = () => {
        if (!batchRows || !batchApplyDefault || !batchApplyDefault.checked) return;
        const defaultName = (batchDefaultTitular?.value || '').trim();
        if (!defaultName) return;
        batchRows.querySelectorAll('input[name="batch_titular_nome[]"]').forEach((input) => {
            if (input.disabled) return;
            if (input.value.trim() === '') {
                input.value = defaultName;
            }
        });
    };

    const setBatchEnabled = (enabled) => {
        if (!batchRows) return;
        batchRows.querySelectorAll('input').forEach((input) => {
            input.disabled = !enabled;
        });
    };

    const setPanelEnabled = (panel, enabled) => {
        if (!panel) return;
        panel.querySelectorAll('input, select, textarea, button').forEach((el) => {
            el.disabled = !enabled;
        });
    };

    const setReservaMode = (mode) => {
        if (!actionInput || !singleReservationPanel || !batchReservationPanel) return;
        const isBatch = mode === 'batch';
        actionInput.value = isBatch ? 'create_batch' : 'create';

        singleReservationPanel.classList.toggle('d-none', isBatch);
        batchReservationPanel.classList.toggle('d-none', !isBatch);
        setPanelEnabled(singleReservationPanel, !isBatch);
        setPanelEnabled(batchReservationPanel, isBatch);

        btnModeSingle?.classList.toggle('btn-primary', !isBatch);
        btnModeSingle?.classList.toggle('btn-outline-primary', isBatch);
        btnModeBatch?.classList.toggle('btn-primary', isBatch);
        btnModeBatch?.classList.toggle('btn-outline-primary', !isBatch);

        singleFields.forEach((el) => {
            if (!el) return;
            el.required = !isBatch;
        });

        if (isBatch) {
            if (batchRows && batchRows.children.length === 0) {
                batchRows.appendChild(batchTemplate());
            }
            setBatchEnabled(true);
            applyDefaultTitularToEmptyRows();
        } else {
            setBatchEnabled(false);
        }
    };

    addBatchRowBtn?.addEventListener('click', () => {
        if (!batchRows) return;
        const node = batchTemplate();
        if (batchReservationPanel && !batchReservationPanel.classList.contains('d-none')) {
            node.querySelectorAll('input').forEach((input) => (input.disabled = false));
        } else {
            node.querySelectorAll('input').forEach((input) => (input.disabled = true));
        }
        batchRows.appendChild(node);
        applyDefaultTitularToEmptyRows();
    });

    btnModeSingle?.addEventListener('click', () => setReservaMode('single'));
    btnModeBatch?.addEventListener('click', () => setReservaMode('batch'));

    batchDefaultTitular?.addEventListener('blur', applyDefaultTitularToEmptyRows);
    batchApplyDefault?.addEventListener('change', applyDefaultTitularToEmptyRows);

    reservaForm?.addEventListener('submit', (event) => {
        if (!actionInput || actionInput.value !== 'create_batch') return;
        applyDefaultTitularToEmptyRows();

        const defaultName = (batchDefaultTitular?.value || '').trim();
        const rowTitulares = Array.from(batchRows?.querySelectorAll('input[name="batch_titular_nome[]"]') || [])
            .filter((input) => !input.disabled);
        const missingTitular = rowTitulares.some((input) => input.value.trim() === '');
        if (!missingTitular || defaultName !== '') {
            return;
        }

        event.preventDefault();
        if (window.Swal) {
            window.Swal.fire({
                icon: 'warning',
                title: 'Titular padrão pendente',
                text: 'Informe o titular padrão do lote ou preencha o titular em cada UH.'
            });
        } else {
            window.alert('Informe o titular padrão do lote ou preencha o titular em cada UH.');
        }
    });

    if (btnModeSingle && btnModeBatch) {
        setReservaMode('single');
    }
    setTurnoSequentialState();
    applyTurnoAvailability();
})();
</script>

