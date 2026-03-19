<?php
$flash = $this->data['flash'] ?? null;
$restaurantes = $this->data['restaurantes'] ?? [];
$turnos = $this->data['turnos'] ?? [];
$periodos = $this->data['periodos'] ?? [];
$reservas = $this->data['reservas'] ?? [];
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
    'Observação geral',
];
$statusMap = [
    'Reservada' => 'badge-warning',
    'Conferida' => 'badge-success',
    'Em atendimento' => 'badge-warning',
    'Finalizada' => 'badge-success',
    'Não compareceu' => 'badge-danger',
    'Cancelada' => 'badge-danger',
    'Divergência' => 'badge-danger',
    'Excedente' => 'badge-warning',
];
$selectedTags = [];
if ($editItem && !empty($editItem['observacao_tags'])) {
    $selectedTags = array_map('trim', explode(',', $editItem['observacao_tags']));
}
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
                <span class="badge badge-success">Ativo</span>
            </div>

            <form method="post" action="/?r=reservasTematicas/reservas">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="<?= $editItem ? 'update' : 'create' ?>">
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
                    <label class="form-label">Quantidade de PAX</label>
                    <input type="number" class="form-control input-xl text-center" min="1" name="pax" value="<?= h($editItem['pax'] ?? 1) ?>" required>
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
                </div>
                <span class="badge badge-soft">Atualizado</span>
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
                                    <td>
                                        <span class="badge <?= $status ?>"><?= (int)$info['restante'] ?></span>
                                        <div class="text-muted small"><?= (int)$info['reservado'] ?>/<?= (int)$info['capacidade'] ?></div>
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

<div class="card p-4 mt-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-list-check"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Reservas do dia</div>
            <h5 class="fw-bold mb-0">Listagem e filtros</h5>
        </div>
    </div>

    <form class="row g-3 align-items-end" method="get" action="/">
        <input type="hidden" name="r" value="reservasTematicas/reservas">
        <div class="col-12 col-md-3">
            <label class="form-label">Data</label>
            <input type="date" class="form-control input-xl" name="data" value="<?= h($filters['data'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Restaurante</label>
            <select class="form-select input-xl" name="restaurante_id">
                <option value="">Todos</option>
                <?php foreach ($restaurantes as $rest): ?>
                    <option value="<?= (int)$rest['id'] ?>" <?= ($filters['restaurante_id'] ?? '') == $rest['id'] ? 'selected' : '' ?>>
                        <?= h($rest['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
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
        <div class="col-12 d-flex flex-wrap gap-2">
            <button class="btn btn-primary btn-xl">Aplicar filtros</button>
            <a class="btn btn-outline-primary btn-xl" href="/?r=reservasTematicas/reservas">Remover filtro</a>
        </div>
    </form>

    <div class="table-responsive mt-4">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Data</th>
                    <th>Turno</th>
                    <th>UH</th>
                    <th>PAX</th>
                    <th>Restaurante</th>
                    <th>Observações</th>
                    <th>Excedente</th>
                    <th>Usuário</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservas as $row): ?>
                    <?php $statusClass = $statusMap[$row['status']] ?? 'badge-soft'; ?>
                    <tr>
                        <td>
                            <span class="badge <?= h($statusClass) ?>"><?= h($row['status']) ?></span>
                            <?php if (!empty($row['observacao_tags'])): ?>
                                <div class="text-muted small"><?= h($row['observacao_tags']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= h($row['data_reserva']) ?></td>
                        <td><span class="tag badge-soft"><?= h($row['turno_hora']) ?></span></td>
                        <td><span class="uh-badge <?= uh_badge_class($row['uh_numero']) ?>"><?= h($row['uh_numero']) ?></span></td>
                        <td><?= h($row['pax']) ?></td>
                        <td><span class="tag <?= restaurant_badge_class($row['restaurante']) ?>"><?= h($row['restaurante']) ?></span></td>
                        <td><?= h($row['observacao_reserva'] ?? '-') ?></td>
                        <td>
                            <?php if (!empty($row['excedente'])): ?>
                                <span class="badge badge-warning">Excedente</span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?= h($row['usuario']) ?></td>
                        <td>
                            <?php if ($isHostess && !$canReserve): ?>
                                <button class="btn btn-outline-primary btn-sm" disabled>Editar</button>
                            <?php else: ?>
                                <a class="btn btn-outline-primary btn-sm" href="/?r=reservasTematicas/reservas&edit=<?= (int)$row['id'] ?>">Editar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($reservas)): ?>
                    <tr><td colspan="10" class="text-muted">Nenhuma reserva encontrada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

