<?php
$items = $this->data['items'] ?? [];
$ativos = count(array_filter($items, static fn(array $item): bool => (int)($item['ativo'] ?? 0) === 1));
?>

<div class="fb-admin-page">
    <section class="fb-page-head">
        <div class="fb-page-head__meta">
            <div>
                <p class="fb-card__eyebrow">Catálogo operacional</p>
                <h1 class="fb-page-head__title">Operações</h1>
                <p class="fb-page-head__subtitle">Mantenha os serviços usados nos turnos, relatórios e indicadores do FBControl.</p>
            </div>
        </div>
        <div class="fb-summary-bar">
            <div class="fb-summary-chip">
                <p class="fb-summary-chip__label">Cadastradas</p>
                <p class="fb-summary-chip__value"><?= count($items) ?></p>
                <p class="fb-summary-chip__hint">operações no catálogo</p>
            </div>
            <div class="fb-summary-chip">
                <p class="fb-summary-chip__label">Ativas</p>
                <p class="fb-summary-chip__value"><?= $ativos ?></p>
                <p class="fb-summary-chip__hint">disponíveis para uso</p>
            </div>
            <div class="fb-summary-chip">
                <p class="fb-summary-chip__label">Inativas</p>
                <p class="fb-summary-chip__value"><?= count($items) - $ativos ?></p>
                <p class="fb-summary-chip__hint">mantidas no histórico</p>
            </div>
        </div>
    </section>

    <div class="fb-admin-layout">
        <section class="fb-card fb-card--flat fb-admin-create">
            <div class="fb-card__head">
                <div>
                    <p class="fb-card__eyebrow">Nova classificação</p>
                    <h2 class="fb-card__title">Adicionar operação</h2>
                    <p class="fb-card__subtitle">Use nomes curtos e reconhecidos pela equipe.</p>
                </div>
                <span class="fb-admin-icon"><i class="bi bi-collection"></i></span>
            </div>
            <form method="post" action="/?r=operacoes/create" class="fb-admin-form">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <label class="fb-field">
                    <span class="fb-label">Nome da operação</span>
                    <input type="text" name="nome" class="fb-input" placeholder="Ex.: Café da manhã" required>
                </label>
                <button class="fb-btn fb-btn--primary fb-btn--lg" type="submit"><i class="bi bi-plus-lg"></i> Cadastrar operação</button>
            </form>
        </section>

        <section class="fb-card fb-card--flat fb-admin-list">
            <div class="fb-card__head">
                <div>
                    <p class="fb-card__eyebrow">Serviços cadastrados</p>
                    <h2 class="fb-card__title">Catálogo de operações</h2>
                    <p class="fb-card__subtitle">Abra uma operação para editar o nome ou alterar sua disponibilidade.</p>
                </div>
                <span class="fb-badge"><?= count($items) ?> registros</span>
            </div>
            <div class="fb-admin-records">
                <?php foreach ($items as $item): ?>
                    <?php $ativo = (int)($item['ativo'] ?? 0); ?>
                    <form method="post" action="/?r=operacoes/edit" class="fb-admin-record">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                        <details>
                            <summary>
                                <span class="fb-admin-record__identity">
                                    <span class="fb-admin-record__avatar"><i class="bi bi-collection"></i></span>
                                    <span><strong><?= h((string)$item['nome']) ?></strong><small>Operação #<?= (int)$item['id'] ?></small></span>
                                </span>
                                <span class="fb-admin-record__status">
                                    <span class="fb-badge <?= $ativo ? 'fb-badge--ok' : 'fb-badge--nao-informado' ?>"><?= $ativo ? 'Ativa' : 'Inativa' ?></span>
                                    <i class="bi bi-chevron-down"></i>
                                </span>
                            </summary>
                            <div class="fb-admin-record__body fb-admin-record__body--compact">
                                <label class="fb-field">
                                    <span class="fb-label">Nome</span>
                                    <input type="text" name="nome" class="fb-input" value="<?= h((string)$item['nome']) ?>" required>
                                </label>
                                <label class="fb-field">
                                    <span class="fb-label">Status</span>
                                    <select name="ativo" class="fb-select">
                                        <option value="1" <?= $ativo ? 'selected' : '' ?>>Ativa</option>
                                        <option value="0" <?= !$ativo ? 'selected' : '' ?>>Inativa</option>
                                    </select>
                                </label>
                                <button class="fb-btn fb-btn--primary" type="submit"><i class="bi bi-check2"></i> Salvar alterações</button>
                            </div>
                        </details>
                    </form>
                <?php endforeach; ?>
                <?php if ($items === []): ?>
                    <div class="fb-admin-empty"><i class="bi bi-collection"></i><strong>Nenhuma operação cadastrada</strong><span>Adicione o primeiro serviço pelo formulário.</span></div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
