<?php
$items = $this->data['items'] ?? [];
$restaurantes = $this->data['restaurantes'] ?? [];
$operacoes = $this->data['operacoes'] ?? [];
$ativos = count(array_filter($items, static fn(array $item): bool => (int)($item['ativo'] ?? 0) === 1));
?>

<div class="fb-admin-page">
    <section class="fb-page-head">
        <div class="fb-page-head__meta">
            <div>
                <p class="fb-card__eyebrow">Janelas operacionais</p>
                <h1 class="fb-page-head__title">Horários</h1>
                <p class="fb-page-head__subtitle">Defina início, encerramento e tolerância de cada operação por restaurante.</p>
            </div>
        </div>
        <div class="fb-summary-bar">
            <div class="fb-summary-chip">
                <p class="fb-summary-chip__label">Configurados</p>
                <p class="fb-summary-chip__value"><?= count($items) ?></p>
                <p class="fb-summary-chip__hint">horários cadastrados</p>
            </div>
            <div class="fb-summary-chip">
                <p class="fb-summary-chip__label">Ativos</p>
                <p class="fb-summary-chip__value"><?= $ativos ?></p>
                <p class="fb-summary-chip__hint">janelas disponíveis</p>
            </div>
            <div class="fb-summary-chip">
                <p class="fb-summary-chip__label">Combinações</p>
                <p class="fb-summary-chip__value"><?= count($restaurantes) ?> × <?= count($operacoes) ?></p>
                <p class="fb-summary-chip__hint">restaurantes e operações</p>
            </div>
        </div>
    </section>

    <div class="fb-admin-layout">
        <section class="fb-card fb-card--flat fb-admin-create">
            <div class="fb-card__head">
                <div>
                    <p class="fb-card__eyebrow">Nova janela</p>
                    <h2 class="fb-card__title">Adicionar horário</h2>
                    <p class="fb-card__subtitle">Relacione restaurante, operação e período de funcionamento.</p>
                </div>
                <span class="fb-admin-icon"><i class="bi bi-clock"></i></span>
            </div>
            <form method="post" action="/?r=horarios/create" class="fb-admin-form">
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
                    <span class="fb-label">Operação</span>
                    <select name="operacao_id" class="fb-select" required>
                        <option value="">Selecione</option>
                        <?php foreach ($operacoes as $op): ?>
                            <option value="<?= (int)$op['id'] ?>"><?= h((string)$op['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="fb-admin-time-grid">
                    <label class="fb-field"><span class="fb-label">Início</span><input type="time" name="hora_inicio" class="fb-input" required></label>
                    <label class="fb-field"><span class="fb-label">Fim</span><input type="time" name="hora_fim" class="fb-input" required></label>
                </div>
                <label class="fb-field">
                    <span class="fb-label">Tolerância em minutos</span>
                    <input type="number" name="tolerancia_min" class="fb-input" value="0" min="0">
                </label>
                <button class="fb-btn fb-btn--primary fb-btn--lg" type="submit"><i class="bi bi-plus-lg"></i> Cadastrar horário</button>
            </form>
        </section>

        <section class="fb-card fb-card--flat fb-admin-list">
            <div class="fb-card__head">
                <div>
                    <p class="fb-card__eyebrow">Janelas cadastradas</p>
                    <h2 class="fb-card__title">Operação por restaurante</h2>
                    <p class="fb-card__subtitle">Abra um horário para revisar os vínculos e limites.</p>
                </div>
                <span class="fb-badge"><?= count($items) ?> registros</span>
            </div>
            <div class="fb-admin-records">
                <?php foreach ($items as $item): ?>
                    <?php $ativo = (int)($item['ativo'] ?? 0); ?>
                    <form method="post" action="/?r=horarios/edit" class="fb-admin-record">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                        <details>
                            <summary>
                                <span class="fb-admin-record__identity">
                                    <span class="fb-admin-record__avatar"><i class="bi bi-clock"></i></span>
                                    <span>
                                        <strong><?= h((string)($item['restaurante'] ?? 'Restaurante')) ?></strong>
                                        <small><?= h((string)($item['operacao'] ?? 'Operação')) ?> · <?= h(substr((string)$item['hora_inicio'], 0, 5)) ?>–<?= h(substr((string)$item['hora_fim'], 0, 5)) ?></small>
                                    </span>
                                </span>
                                <span class="fb-admin-record__status">
                                    <span class="fb-badge <?= $ativo ? 'fb-badge--ok' : 'fb-badge--nao-informado' ?>"><?= $ativo ? 'Ativo' : 'Inativo' ?></span>
                                    <i class="bi bi-chevron-down"></i>
                                </span>
                            </summary>
                            <div class="fb-admin-record__body fb-admin-record__body--schedule">
                                <label class="fb-field">
                                    <span class="fb-label">Restaurante</span>
                                    <select name="restaurante_id" class="fb-select">
                                        <?php foreach ($restaurantes as $rest): ?>
                                            <option value="<?= (int)$rest['id'] ?>" <?= (int)$item['restaurante_id'] === (int)$rest['id'] ? 'selected' : '' ?>><?= h((string)$rest['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="fb-field">
                                    <span class="fb-label">Operação</span>
                                    <select name="operacao_id" class="fb-select">
                                        <?php foreach ($operacoes as $op): ?>
                                            <option value="<?= (int)$op['id'] ?>" <?= (int)$item['operacao_id'] === (int)$op['id'] ? 'selected' : '' ?>><?= h((string)$op['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="fb-field"><span class="fb-label">Início</span><input type="time" name="hora_inicio" class="fb-input" value="<?= h((string)$item['hora_inicio']) ?>"></label>
                                <label class="fb-field"><span class="fb-label">Fim</span><input type="time" name="hora_fim" class="fb-input" value="<?= h((string)$item['hora_fim']) ?>"></label>
                                <label class="fb-field"><span class="fb-label">Tolerância</span><input type="number" name="tolerancia_min" class="fb-input" min="0" value="<?= h((string)$item['tolerancia_min']) ?>"></label>
                                <label class="fb-field">
                                    <span class="fb-label">Status</span>
                                    <select name="ativo" class="fb-select">
                                        <option value="1" <?= $ativo ? 'selected' : '' ?>>Ativo</option>
                                        <option value="0" <?= !$ativo ? 'selected' : '' ?>>Inativo</option>
                                    </select>
                                </label>
                                <button class="fb-btn fb-btn--primary" type="submit"><i class="bi bi-check2"></i> Salvar alterações</button>
                            </div>
                        </details>
                    </form>
                <?php endforeach; ?>
                <?php if ($items === []): ?>
                    <div class="fb-admin-empty"><i class="bi bi-clock"></i><strong>Nenhum horário configurado</strong><span>Crie a primeira janela operacional pelo formulário.</span></div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
