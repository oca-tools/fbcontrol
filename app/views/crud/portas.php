<?php $flash = $this->data['flash'] ?? null; $restaurantes = $this->data['restaurantes'] ?? []; ?>
<div class="card card-soft p-4 mb-4">
    <div class="section-title">
        <div class="icon"><i class="bi bi-door-open"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Cadastros</div>
            <h3 class="fw-bold mb-0">Portas</h3>
        </div>
    </div>
</div>
<div class="row g-4">
    <div class="col-12 col-lg-4">
        <div class="card p-4">
            <div class="text-uppercase text-muted small">Cadastro</div>
            <h4 class="fw-bold">Nova Porta</h4>
            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
            <?php endif; ?>
            <form method="post" action="/?r=portas/create">
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
                    <label class="form-label">Nome</label>
                    <input type="text" name="nome" class="form-control input-xl" required>
                </div>
                <button class="btn btn-success btn-xl w-100">Cadastrar</button>
            </form>
        </div>
    </div>
    <div class="col-12 col-lg-8">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0">Portas</h4>
                <span class="text-muted small">Ligadas ao restaurante</span>
            </div>
            <div class="row g-3">
                <?php foreach ($this->data['items'] as $item): ?>
                    <div class="col-12">
                        <form method="post" action="/?r=portas/edit" class="card p-3">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-5">
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
                                    <label class="form-label small text-muted">Nome da porta</label>
                                    <input type="text" name="nome" class="form-control" value="<?= h($item['nome']) ?>">
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



