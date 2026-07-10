<?php
$items = $this->data['items'] ?? [];
$restaurantes = $this->data['restaurantes'] ?? [];
$ativos = count(array_filter($items, static fn(array $item): bool => (int)($item['ativo'] ?? 0) === 1));
?>

<div class="fb-admin-page">
    <section class="fb-page-head">
        <div class="fb-page-head__meta">
            <div>
                <p class="fb-card__eyebrow">Estrutura de acesso</p>
                <h1 class="fb-page-head__title">Portas</h1>
                <p class="fb-page-head__subtitle">Organize os pontos de entrada utilizados na abertura e no acompanhamento dos turnos.</p>
            </div>
        </div>
        <div class="fb-summary-bar">
            <div class="fb-summary-chip">
                <p class="fb-summary-chip__label">Cadastradas</p>
                <p class="fb-summary-chip__value"><?= count($items) ?></p>
                <p class="fb-summary-chip__hint">portas no sistema</p>
            </div>
            <div class="fb-summary-chip">
                <p class="fb-summary-chip__label">Ativas</p>
                <p class="fb-summary-chip__value"><?= $ativos ?></p>
                <p class="fb-summary-chip__hint">disponíveis para seleção</p>
            </div>
            <div class="fb-summary-chip">
                <p class="fb-summary-chip__label">Restaurantes</p>
                <p class="fb-summary-chip__value"><?= count($restaurantes) ?></p>
                <p class="fb-summary-chip__hint">ambientes vinculáveis</p>
            </div>
        </div>
    </section>

    <div class="fb-admin-layout">
        <section class="fb-card fb-card--flat fb-admin-create">
            <div class="fb-card__head">
                <div>
                    <p class="fb-card__eyebrow">Novo ponto de acesso</p>
                    <h2 class="fb-card__title">Adicionar porta</h2>
                    <p class="fb-card__subtitle">Vincule a porta ao restaurante correto.</p>
                </div>
                <span class="fb-admin-icon"><i class="bi bi-door-open"></i></span>
            </div>
            <form method="post" action="/?r=portas/create" class="fb-admin-form">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <label class="fb-field">
                    <span class="fb-label">Restaurante</span>
                    <select name="restaurante_id" class="fb-select" required>
                        <option value="">Selecione</option>
                        <?php foreach ($restaurantes as $rest): ?>
                            <option value="<?= (int)$rest['id'] ?>"><?= h((string)$rest['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="fb-field">
                    <span class="fb-label">Nome da porta</span>
                    <input type="text" name="nome" class="fb-input" placeholder="Ex.: Entrada principal" required>
                </label>
                <button class="fb-btn fb-btn--primary fb-btn--lg" type="submit"><i class="bi bi-plus-lg"></i> Cadastrar porta</button>
            </form>
        </section>

        <section class="fb-card fb-card--flat fb-admin-list">
            <div class="fb-card__head">
                <div>
                    <p class="fb-card__eyebrow">Pontos cadastrados</p>
                    <h2 class="fb-card__title">Portas por restaurante</h2>
                    <p class="fb-card__subtitle">Abra um registro para alterar vínculo, nome ou status.</p>
                </div>
                <span class="fb-badge"><?= count($items) ?> registros</span>
            </div>
            <div class="fb-admin-records">
                <?php foreach ($items as $item): ?>
                    <?php $ativo = (int)($item['ativo'] ?? 0); ?>
                    <form method="post" action="/?r=portas/edit" class="fb-admin-record">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                        <details>
                            <summary>
                                <span class="fb-admin-record__identity">
                                    <span class="fb-admin-record__avatar"><i class="bi bi-door-open"></i></span>
                                    <span>
                                        <strong><?= h((string)$item['nome']) ?></strong>
                                        <small><?= h((string)($item['restaurante'] ?? 'Restaurante não informado')) ?></small>
                                    </span>
                                </span>
                                <span class="fb-admin-record__status">
                                    <span class="fb-badge <?= $ativo ? 'fb-badge--ok' : 'fb-badge--nao-informado' ?>"><?= $ativo ? 'Ativa' : 'Inativa' ?></span>
                                    <i class="bi bi-chevron-down"></i>
                                </span>
                            </summary>
                            <div class="fb-admin-record__body">
                                <label class="fb-field">
                                    <span class="fb-label">Restaurante</span>
                                    <select name="restaurante_id" class="fb-select">
                                        <?php foreach ($restaurantes as $rest): ?>
                                            <option value="<?= (int)$rest['id'] ?>" <?= (int)$item['restaurante_id'] === (int)$rest['id'] ? 'selected' : '' ?>><?= h((string)$rest['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="fb-field">
                                    <span class="fb-label">Nome da porta</span>
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
                    <div class="fb-admin-empty"><i class="bi bi-door-open"></i><strong>Nenhuma porta cadastrada</strong><span>Adicione o primeiro ponto de entrada pelo formulário.</span></div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
