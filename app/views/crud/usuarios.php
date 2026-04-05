<?php
$flash = $this->data['flash'] ?? null;
$restaurantes = $this->data['restaurantes'] ?? [];
$assignmentOptions = $this->data['assignment_options'] ?? [];
$assignedRestaurants = $this->data['assigned_restaurants'] ?? [];
$assignedOperations = $this->data['assigned_operations'] ?? [];
?>
<div class="card card-soft p-4 mb-4">
    <div class="section-title">
        <div class="icon"><i class="bi bi-people"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Cadastros</div>
            <h3 class="fw-bold mb-0">Usuários</h3>
        </div>
    </div>
</div>
<div class="row g-4">
    <div class="col-12 col-lg-4">
        <div class="card p-4">
            <div class="text-uppercase text-muted small">Cadastro</div>
            <h4 class="fw-bold">Novo Usuário</h4>
            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
            <?php endif; ?>
            <form method="post" action="/?r=usuarios/create">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <div class="mb-3">
                    <label class="form-label">Nome</label>
                    <input type="text" name="nome" class="form-control input-xl" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">E-mail</label>
                    <input type="email" name="email" class="form-control input-xl" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Senha</label>
                    <input type="password" name="senha" class="form-control input-xl" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Perfil</label>
                    <select name="perfil" class="form-select input-xl">
                        <option value="hostess">Hostess</option>
                        <option value="gerente">Gerente</option>
                        <option value="supervisor">Supervisor</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Restaurantes e Operações (para hostess)</label>
                    <div class="tag-grid">
                        <?php foreach ($assignmentOptions as $opt): ?>
                            <?php $key = preg_replace('/[^a-zA-Z0-9_]/', '_', (string)$opt['key']); ?>
                            <div class="tag-choice">
                                <input type="checkbox" id="novo_assign_<?= h($key) ?>" name="assignments[]" value="<?= h((string)$opt['key']) ?>">
                                <label for="novo_assign_<?= h($key) ?>"><?= h((string)$opt['label']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button class="btn btn-success btn-xl w-100">Cadastrar</button>
            </form>
        </div>
    </div>
    <div class="col-12 col-lg-8">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0">Usuários</h4>
                <span class="text-muted small">Perfis e permissões</span>
            </div>
            <div class="row g-3">
                <?php foreach ($this->data['items'] as $item): ?>
                    <?php
                        $uid = (int)$item['id'];
                        $isRemovedUser =
                            (strpos((string)($item['email'] ?? ''), '@anon.local') !== false) ||
                            (stripos((string)($item['nome'] ?? ''), 'removido') !== false);
                    ?>
                    <div class="col-12">
                        <form method="post" action="/?r=usuarios/edit" class="card p-3">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= $uid ?>">
                            <?php if ($isRemovedUser): ?>
                                <div class="d-flex justify-content-end mb-2">
                                    <span class="badge badge-danger">Usuário removido</span>
                                </div>
                            <?php endif; ?>
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small text-muted">Nome</label>
                                    <input type="text" name="nome" class="form-control" value="<?= h($item['nome']) ?>">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small text-muted">E-mail</label>
                                    <input type="email" name="email" class="form-control" value="<?= h($item['email']) ?>">
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label small text-muted">Perfil</label>
                                    <select name="perfil" class="form-select">
                                        <option value="hostess" <?= $item['perfil'] === 'hostess' ? 'selected' : '' ?>>Hostess</option>
                                        <option value="gerente" <?= $item['perfil'] === 'gerente' ? 'selected' : '' ?>>Gerente</option>
                                        <option value="supervisor" <?= $item['perfil'] === 'supervisor' ? 'selected' : '' ?>>Supervisor</option>
                                        <option value="admin" <?= $item['perfil'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label small text-muted">Status</label>
                                    <select name="ativo" class="form-select">
                                        <option value="1" <?= $item['ativo'] ? 'selected' : '' ?>>Ativo</option>
                                        <option value="0" <?= !$item['ativo'] ? 'selected' : '' ?>>Inativo</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small text-muted">Nova senha</label>
                                    <input type="password" name="senha" class="form-control" placeholder="Digite uma nova senha">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label small text-muted">Restaurantes e operações</label>
                                    <div class="tag-grid">
                                        <?php foreach ($assignmentOptions as $opt): ?>
                                            <?php
                                                $rid = (int)($opt['restaurante_id'] ?? 0);
                                                $oid = isset($opt['operacao_id']) && $opt['operacao_id'] !== null ? (int)$opt['operacao_id'] : null;
                                                $restSelected = in_array($rid, $assignedRestaurants[$uid] ?? [], true);
                                                $opsSelected = $assignedOperations[$uid][$rid] ?? [];
                                                $isSelected = $oid === null
                                                    ? $restSelected
                                                    : (in_array($oid, $opsSelected, true) || ($restSelected && empty($opsSelected)));
                                                $key = preg_replace('/[^a-zA-Z0-9_]/', '_', (string)$opt['key']);
                                            ?>
                                            <div class="tag-choice">
                                                <input type="checkbox"
                                                       id="edit_<?= $uid ?>_assign_<?= h($key) ?>"
                                                       name="assignments[]"
                                                       value="<?= h((string)$opt['key']) ?>"
                                                       <?= $isSelected ? 'checked' : '' ?>>
                                                <label for="edit_<?= $uid ?>_assign_<?= h($key) ?>"><?= h((string)$opt['label']) ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="col-12 col-md-2 d-grid gap-2">
                                    <button class="btn btn-outline-primary w-100">Salvar</button>
                                    <button class="btn btn-outline-danger w-100" type="submit" formaction="/?r=usuarios/delete" data-confirm="Excluir usuário? Os dados pessoais serão anonimizados e o histórico será mantido." data-confirm-title="Excluir usuário" data-confirm-type="danger">Excluir</button>
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