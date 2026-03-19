<?php $flash = $this->data['flash'] ?? null; ?>
<div class="card card-soft p-4 mb-4">
    <div class="section-title">
        <div class="icon"><i class="bi bi-building"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Cadastros</div>
            <h3 class="fw-bold mb-0">Restaurantes</h3>
        </div>
    </div>
</div>
<div class="row g-4">
    <div class="col-12 col-lg-4">
        <div class="card p-4">
            <div class="text-uppercase text-muted small">Cadastro</div>
            <h4 class="fw-bold">Novo Restaurante</h4>
            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
            <?php endif; ?>
            <form method="post" action="/?r=restaurantes/create">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <div class="mb-3">
                    <label class="form-label">Nome</label>
                    <input type="text" name="nome" class="form-control input-xl" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipo</label>
                    <select name="tipo" class="form-select input-xl">
                        <option value="buffet">Buffet</option>
                        <option value="tematico">Temático</option>
                        <option value="area">Área</option>
                    </select>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="seleciona_porta_no_turno" value="1" id="sel_porta">
                    <label class="form-check-label" for="sel_porta">Seleciona porta no turno</label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="exige_pax" value="1" id="exige_pax" checked>
                    <label class="form-check-label" for="exige_pax">Exige PAX</label>
                </div>
                <button class="btn btn-success btn-xl w-100">Cadastrar</button>
            </form>
        </div>
    </div>
    <div class="col-12 col-lg-8">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0">Restaurantes</h4>
                <span class="text-muted small">Edite e organize</span>
            </div>
            <div class="row g-3">
                <?php foreach ($this->data['items'] as $item): ?>
                    <div class="col-12">
                        <form method="post" action="/?r=restaurantes/edit" class="card p-3">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small text-muted">Nome</label>
                                    <input type="text" name="nome" class="form-control" value="<?= h($item['nome']) ?>">
                                    <?php if (stripos($item['nome'], 'La Brasa') !== false): ?>
                                        <div class="mt-2">
                                            <span class="badge badge-warning">Híbrido (Buffet + Temático)</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label small text-muted">Tipo</label>
                                    <select name="tipo" class="form-select">
                                        <option value="buffet" <?= $item['tipo'] === 'buffet' ? 'selected' : '' ?>>Buffet</option>
                                        <option value="tematico" <?= $item['tipo'] === 'tematico' ? 'selected' : '' ?>>Temático</option>
                                        <option value="area" <?= $item['tipo'] === 'area' ? 'selected' : '' ?>>Área</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label small text-muted">Porta no turno</label>
                                    <select name="seleciona_porta_no_turno" class="form-select">
                                        <option value="1" <?= $item['seleciona_porta_no_turno'] ? 'selected' : '' ?>>Sim</option>
                                        <option value="0" <?= !$item['seleciona_porta_no_turno'] ? 'selected' : '' ?>>Não</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label small text-muted">Exige PAX</label>
                                    <select name="exige_pax" class="form-select">
                                        <option value="1" <?= $item['exige_pax'] ? 'selected' : '' ?>>Sim</option>
                                        <option value="0" <?= !$item['exige_pax'] ? 'selected' : '' ?>>Não</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-1">
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



