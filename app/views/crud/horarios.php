<?php
$flash = $this->data['flash'] ?? null;
$restaurantes = $this->data['restaurantes'] ?? [];
$operacoes = $this->data['operacoes'] ?? [];
?>
<div class="card card-soft p-4 mb-4">
    <div class="section-title">
        <div class="icon"><i class="bi bi-clock"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Cadastros</div>
            <h3 class="fw-bold mb-0">Horários</h3>
        </div>
    </div>
</div>
<div class="row g-4">
    <div class="col-12 col-lg-4">
        <div class="card p-4">
            <div class="text-uppercase text-muted small">Cadastro</div>
            <h4 class="fw-bold">Novo Horário</h4>
            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
            <?php endif; ?>
            <form method="post" action="/?r=horarios/create">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <div class="mb-3">
                    <label class="form-label">Restaurante</label>
                    <select name="restaurante_id" class="form-select input-xl" required>
                        <option value="">Selecione</option>
                        <?php foreach ($restaurantes as $rest): ?>
                            <option value="<?= (int)$rest['id'] ?>"><?= h($rest['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Operação</label>
                    <select name="operacao_id" class="form-select input-xl" required>
                        <option value="">Selecione</option>
                        <?php foreach ($operacoes as $op): ?>
                            <option value="<?= (int)$op['id'] ?>"><?= h($op['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Hora início</label>
                    <input type="time" name="hora_inicio" class="form-control input-xl" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Hora fim</label>
                    <input type="time" name="hora_fim" class="form-control input-xl" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tolerância (min)</label>
                    <input type="number" name="tolerancia_min" class="form-control input-xl" value="0" min="0">
                </div>
                <button class="btn btn-success btn-xl w-100">Cadastrar</button>
            </form>
        </div>
    </div>
    <div class="col-12 col-lg-8">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0">Horários</h4>
                <span class="text-muted small">Operação por restaurante</span>
            </div>
            <div class="row g-3">
                <?php foreach ($this->data['items'] as $item): ?>
                    <div class="col-12">
                        <form method="post" action="/?r=horarios/edit" class="card p-3">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small text-muted">Restaurante</label>
                                    <select name="restaurante_id" class="form-select">
                                        <?php foreach ($restaurantes as $rest): ?>
                                            <option value="<?= (int)$rest['id'] ?>" <?= $item['restaurante_id'] == $rest['id'] ? 'selected' : '' ?>>
                                                <?= h($rest['nome']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small text-muted">Operação</label>
                                    <select name="operacao_id" class="form-select">
                                        <?php foreach ($operacoes as $op): ?>
                                            <option value="<?= (int)$op['id'] ?>" <?= $item['operacao_id'] == $op['id'] ? 'selected' : '' ?>>
                                                <?= h($op['nome']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label small text-muted">Início</label>
                                    <input type="time" name="hora_inicio" class="form-control" value="<?= h($item['hora_inicio']) ?>">
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label small text-muted">Fim</label>
                                    <input type="time" name="hora_fim" class="form-control" value="<?= h($item['hora_fim']) ?>">
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label small text-muted">Tolerância</label>
                                    <input type="number" name="tolerancia_min" class="form-control" value="<?= h($item['tolerancia_min']) ?>">
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label small text-muted">Status</label>
                                    <select name="ativo" class="form-select">
                                        <option value="1" <?= $item['ativo'] ? 'selected' : '' ?>>Ativo</option>
                                        <option value="0" <?= !$item['ativo'] ? 'selected' : '' ?>>Inativo</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-2">
                                    <button class="btn btn-outline-primary w-100">Salvar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($this->data['items'])): ?>
                    <div class="col-12 text-muted">Sem registros.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>



