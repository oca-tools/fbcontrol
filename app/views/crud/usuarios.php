<?php
$flash = $this->data['flash'] ?? null;
$restaurantes = $this->data['restaurantes'] ?? [];
$assignmentOptions = $this->data['assignment_options'] ?? [];
$assignedRestaurants = $this->data['assigned_restaurants'] ?? [];
$assignedOperations = $this->data['assigned_operations'] ?? [];
$itemsAtivos = $this->data['items_ativos'] ?? [];
$itemsDesativados = $this->data['items_desativados'] ?? [];
$canManagePrivilegedProfiles = !empty($this->data['can_manage_privileged_profiles']);
$tabAtual = ($_GET['tab'] ?? 'ativos') === 'desativados' ? 'desativados' : 'ativos';
$userTabs = [
    'ativos' => ['label' => 'Ativos', 'items' => $itemsAtivos],
    'desativados' => ['label' => 'Desativados', 'items' => $itemsDesativados],
];
?>
<style>
    .usuarios-toolbar {
        background: color-mix(in srgb, var(--ab-soft-bg) 78%, var(--ab-card) 22%);
        border: 1px solid var(--ab-border);
        border-radius: 12px;
        padding: 0.75rem;
    }
    .usuarios-form-card,
    .usuarios-list-card {
        border: 1px solid var(--ab-border);
        border-radius: 18px;
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.06);
    }
    .usuario-edit-card {
        border: 1px solid color-mix(in srgb, var(--ab-border) 82%, transparent);
        border-radius: 16px;
        background: color-mix(in srgb, var(--ab-card) 94%, var(--ab-soft-bg) 6%);
    }
    .usuario-card-summary {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        border: 1px solid color-mix(in srgb, var(--ab-border) 78%, transparent);
        border-radius: 14px;
        background: color-mix(in srgb, var(--ab-soft-bg) 70%, var(--ab-card) 30%);
        padding: 0.7rem;
        margin-bottom: 0.85rem;
    }
    .usuario-card-summary strong,
    .usuario-card-summary span {
        display: block;
        min-width: 0;
    }
    .usuario-card-summary strong {
        color: var(--ab-ink);
        line-height: 1.18;
    }
    .usuario-card-summary span {
        color: var(--ab-muted);
        font-size: 0.82rem;
        margin-top: 0.15rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .usuario-card-badges {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 0.35rem;
        flex: 0 0 auto;
    }
    .usuarios-assignment-details {
        border: 1px solid color-mix(in srgb, var(--ab-border) 78%, transparent);
        border-radius: 14px;
        background: color-mix(in srgb, var(--ab-card) 86%, var(--ab-soft-bg) 14%);
        padding: 0.65rem;
    }
    .usuarios-assignment-details summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        cursor: pointer;
        color: var(--ab-ink);
        font-weight: 800;
        list-style: none;
    }
    .usuarios-assignment-details summary::-webkit-details-marker {
        display: none;
    }
    .usuarios-assignment-details summary::after {
        content: '+';
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 999px;
        color: var(--ab-accent);
        background: color-mix(in srgb, var(--ab-accent) 10%, transparent);
        flex: 0 0 auto;
    }
    .usuarios-assignment-details[open] summary::after {
        content: '-';
    }
    .usuarios-assignment-body {
        margin-top: 0.65rem;
    }
    .usuarios-pagination .btn {
        min-width: 38px;
    }
    .usuarios-pagination .btn.active {
        pointer-events: none;
    }
    @media (max-width: 767.98px) {
        .usuarios-page-hero {
            padding: 0.9rem !important;
            margin-bottom: 0.85rem !important;
        }
        .usuarios-page-hero .section-title {
            align-items: flex-start;
            gap: 0.55rem;
        }
        .usuarios-page-hero .section-title .icon {
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
        }
        .usuarios-page-hero h3 {
            font-size: 1.08rem;
            line-height: 1.18;
        }
        .usuarios-form-card,
        .usuarios-list-card {
            padding: 0.9rem !important;
            border-radius: 16px;
        }
        .usuarios-create-column {
            order: 2;
        }
        .usuarios-list-column {
            order: 1;
        }
        .usuarios-toolbar {
            border-radius: 10px;
            padding: 0.65rem;
        }
        .usuarios-toolbar .row,
        .usuario-edit-card .row {
            --bs-gutter-x: 0.65rem;
            --bs-gutter-y: 0.65rem;
        }
        .usuario-edit-card {
            padding: 0.75rem !important;
        }
        .usuario-card-summary {
            align-items: stretch;
            flex-direction: column;
            gap: 0.55rem;
            padding: 0.65rem;
        }
        .usuario-card-summary span {
            white-space: normal;
        }
        .usuario-card-badges {
            justify-content: flex-start;
        }
        .usuario-edit-card .tag-grid {
            grid-template-columns: 1fr;
        }
        .usuario-edit-card .btn {
            min-height: 38px;
        }
        .usuarios-pagination {
            justify-content: center !important;
        }
        .usuarios-pagination .usuarios-page-info {
            width: 100%;
            text-align: center;
        }
    }
</style>
<div class="card card-soft usuarios-page-hero p-4 mb-4">
    <div class="section-title">
        <div class="icon"><i class="bi bi-people"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Cadastros</div>
            <h3 class="fw-bold mb-0">Usuários</h3>
        </div>
    </div>
</div>
<div class="row g-4">
    <div class="col-12 col-lg-4 usuarios-create-column">
        <div class="card usuarios-form-card p-4">
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
                        <?php if (!$canManagePrivilegedProfiles): ?>
                            <option value="supervisor">Supervisor</option>
                        <?php endif; ?>
                        <?php if ($canManagePrivilegedProfiles): ?>
                            <option value="gerente">Gerente</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="admin">Admin</option>
                        <?php endif; ?>
                    </select>
                </div>
                <details class="usuarios-assignment-details mb-3">
                    <summary>Restaurantes e operações</summary>
                    <div class="usuarios-assignment-body">
                        <div class="text-muted small mb-2">Use para limitar o acesso de hostess a restaurantes/operações específicas.</div>
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
                </details>
                <button class="btn btn-success btn-xl w-100">Cadastrar</button>
            </form>
        </div>
    </div>
    <div class="col-12 col-lg-8 usuarios-list-column">
        <div class="card usuarios-list-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0">Usuários</h4>
                <span class="text-muted small">Perfis e permissões</span>
            </div>
            <ul class="nav nav-tabs mb-3" role="tablist">
                <?php foreach ($userTabs as $tabKey => $tab): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $tabAtual === $tabKey ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#usuarios-<?= h($tabKey) ?>" type="button" role="tab">
                            <?= h($tab['label']) ?> <span class="badge badge-soft ms-1"><?= count($tab['items']) ?></span>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="usuarios-toolbar row g-2 align-items-end mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label small text-muted mb-1">Buscar usuário</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="search" class="form-control" id="usuariosSearch" placeholder="Nome, e-mail ou perfil">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1">Ordenar</label>
                    <select class="form-select" id="usuariosSort">
                        <option value="nome">Nome A-Z</option>
                        <option value="perfil">Perfil</option>
                        <option value="email">E-mail</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1">Por página</label>
                    <select class="form-select" id="usuariosPerPage">
                        <option value="6">6</option>
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                    </select>
                </div>
            </div>
            <div class="tab-content">
            <?php foreach ($userTabs as $tabKey => $tab): ?>
                <div class="tab-pane fade <?= $tabAtual === $tabKey ? 'show active' : '' ?>" id="usuarios-<?= h($tabKey) ?>" role="tabpanel">
                <div class="row g-3 js-usuarios-list" data-tab="<?= h($tabKey) ?>">
                <?php foreach ($tab['items'] as $item): ?>
                    <?php
                        $uid = (int)$item['id'];
                        $isRemovedUser =
                            (strpos((string)($item['email'] ?? ''), '@anon.local') !== false) ||
                            (stripos((string)($item['nome'] ?? ''), 'removido') !== false);
                        $nomeBusca = (string)($item['nome'] ?? '');
                        $emailBusca = (string)($item['email'] ?? '');
                        $perfilBusca = (string)($item['perfil'] ?? '');
                    ?>
                    <div class="col-12 js-usuario-card"
                         data-user-name="<?= h($nomeBusca) ?>"
                         data-user-email="<?= h($emailBusca) ?>"
                         data-user-profile="<?= h($perfilBusca) ?>"
                         data-user-text="<?= h($nomeBusca . ' ' . $emailBusca . ' ' . $perfilBusca) ?>">
                        <form method="post" action="/?r=usuarios/edit" class="card usuario-edit-card p-3">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= $uid ?>">
                            <div class="usuario-card-summary">
                                <div>
                                    <strong><?= h($item['nome']) ?></strong>
                                    <span><?= h($item['email']) ?> · <?= h(ucfirst((string)$item['perfil'])) ?></span>
                                </div>
                                <div class="usuario-card-badges">
                                    <span class="badge <?= (int)$item['ativo'] === 1 ? 'badge-success' : 'badge-danger' ?>">
                                        <?= (int)$item['ativo'] === 1 ? 'Ativo' : 'Desativado' ?>
                                    </span>
                                    <?php if ($isRemovedUser): ?>
                                        <span class="badge badge-danger">Registro legado anonimizado</span>
                                    <?php endif; ?>
                                </div>
                            </div>
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
                                        <?php if (!$canManagePrivilegedProfiles): ?>
                                            <option value="supervisor" <?= $item['perfil'] === 'supervisor' ? 'selected' : '' ?>>Supervisor</option>
                                        <?php endif; ?>
                                        <?php if ($canManagePrivilegedProfiles): ?>
                                            <option value="gerente" <?= $item['perfil'] === 'gerente' ? 'selected' : '' ?>>Gerente</option>
                                            <option value="supervisor" <?= $item['perfil'] === 'supervisor' ? 'selected' : '' ?>>Supervisor</option>
                                            <option value="admin" <?= $item['perfil'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label small text-muted">Status</label>
                                    <select name="ativo" class="form-select">
                                        <option value="1" <?= $item['ativo'] ? 'selected' : '' ?>>Ativo</option>
                                        <option value="0" <?= !$item['ativo'] ? 'selected' : '' ?>>Desativado</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small text-muted">Nova senha</label>
                                    <input type="password" name="senha" class="form-control" placeholder="Digite uma nova senha">
                                </div>
                                <div class="col-12 col-md-6">
                                    <details class="usuarios-assignment-details">
                                        <summary>Restaurantes e operações</summary>
                                        <div class="usuarios-assignment-body">
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
                                    </details>
                                </div>
                                <div class="col-12 col-md-2 d-grid gap-2">
                                    <button class="btn btn-outline-primary w-100">Salvar</button>
                                    <button class="btn btn-outline-danger w-100" type="submit" formaction="/?r=usuarios/delete" data-confirm="Desativar usuário? O acesso será bloqueado, mas nome e histórico serão preservados para auditoria." data-confirm-title="Desativar usuário" data-confirm-type="danger">Desativar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($tab['items'])): ?>
                    <div class="col-12 text-muted">Sem registros.</div>
                <?php endif; ?>
                </div>
                <div class="usuarios-pagination js-usuarios-pagination d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3" data-tab="<?= h($tabKey) ?>"></div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('usuariosSearch');
    const sort = document.getElementById('usuariosSort');
    const perPage = document.getElementById('usuariosPerPage');
    const state = {};

    const normalize = function (value) {
        return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    };

    const activeTabKey = function () {
        const activePane = document.querySelector('.tab-pane.active[id^="usuarios-"]');
        return activePane ? activePane.id.replace('usuarios-', '') : 'ativos';
    };

    const render = function (tabKey, page) {
        const list = document.querySelector('.js-usuarios-list[data-tab="' + tabKey + '"]');
        const pager = document.querySelector('.js-usuarios-pagination[data-tab="' + tabKey + '"]');
        if (!list || !pager) {
            return;
        }

        const cards = Array.from(list.querySelectorAll('.js-usuario-card'));
        const term = normalize(search ? search.value : '');
        const sortBy = sort ? sort.value : 'nome';
        const pageSize = Math.max(1, parseInt(perPage ? perPage.value : '10', 10) || 10);

        cards.sort(function (a, b) {
            const key = sortBy === 'email' ? 'userEmail' : (sortBy === 'perfil' ? 'userProfile' : 'userName');
            return normalize(a.dataset[key]).localeCompare(normalize(b.dataset[key]), 'pt-BR');
        }).forEach(function (card) {
            list.appendChild(card);
        });

        const filtered = cards.filter(function (card) {
            return !term || normalize(card.dataset.userText).includes(term);
        });
        const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
        const currentPage = Math.min(Math.max(1, page || state[tabKey] || 1), totalPages);
        state[tabKey] = currentPage;

        cards.forEach(function (card) {
            card.classList.add('d-none');
        });
        filtered.slice((currentPage - 1) * pageSize, currentPage * pageSize).forEach(function (card) {
            card.classList.remove('d-none');
        });

        if (!cards.length) {
            pager.innerHTML = '';
            return;
        }

        const start = filtered.length ? ((currentPage - 1) * pageSize + 1) : 0;
        const end = Math.min(currentPage * pageSize, filtered.length);
        let buttons = '';
        for (let i = 1; i <= totalPages; i++) {
            if (totalPages > 7 && i !== 1 && i !== totalPages && Math.abs(i - currentPage) > 1) {
                if (i === 2 || i === totalPages - 1) {
                    buttons += '<span class="btn btn-sm btn-outline-secondary disabled">...</span>';
                }
                continue;
            }
            buttons += '<button type="button" class="btn btn-sm ' + (i === currentPage ? 'btn-primary active' : 'btn-outline-primary') + '" data-page="' + i + '">' + i + '</button>';
        }

        pager.innerHTML =
            '<span class="usuarios-page-info text-muted small">' + start + '-' + end + ' de ' + filtered.length + ' usuários</span>' +
            '<div class="btn-group flex-wrap" role="group">' + buttons + '</div>';

        pager.querySelectorAll('[data-page]').forEach(function (button) {
            button.addEventListener('click', function () {
                render(tabKey, parseInt(button.dataset.page, 10));
            });
        });
    };

    const renderActive = function () {
        const key = activeTabKey();
        state[key] = 1;
        render(key, 1);
    };

    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (tab) {
        tab.addEventListener('shown.bs.tab', function () {
            render(activeTabKey());
        });
        tab.addEventListener('click', function () {
            window.setTimeout(function () { render(activeTabKey()); }, 80);
        });
    });
    [search, sort, perPage].forEach(function (input) {
        if (input) {
            input.addEventListener('input', renderActive);
            input.addEventListener('change', renderActive);
        }
    });

    Object.keys(state).forEach(function (key) { render(key); });
    document.querySelectorAll('.js-usuarios-list').forEach(function (list) {
        render(list.dataset.tab);
    });
});
</script>
