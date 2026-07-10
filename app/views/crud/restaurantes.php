<?php
$items = $this->data['items'] ?? [];
$ativos = count(array_filter($items, static fn(array $item): bool => (int)($item['ativo'] ?? 0) === 1));
$tematicos = count(array_filter($items, static fn(array $item): bool => ($item['tipo'] ?? '') === 'tematico'));
?>

<div class="fb-admin-page">
    <section class="fb-page-head">
        <div class="fb-page-head__meta">
            <div>
                <p class="fb-card__eyebrow">Estrutura operacional</p>
                <h1 class="fb-page-head__title">Restaurantes</h1>
                <p class="fb-page-head__subtitle">Cadastre ambientes e defina como cada restaurante participa dos fluxos de acesso e reservas.</p>
            </div>
        </div>
        <div class="fb-summary-bar">
            <div class="fb-summary-chip">
                <p class="fb-summary-chip__label">Cadastrados</p>
                <p class="fb-summary-chip__value"><?= count($items) ?></p>
                <p class="fb-summary-chip__hint">ambientes no sistema</p>
            </div>
            <div class="fb-summary-chip">
                <p class="fb-summary-chip__label">Ativos</p>
                <p class="fb-summary-chip__value"><?= $ativos ?></p>
                <p class="fb-summary-chip__hint">disponíveis na operação</p>
            </div>
            <div class="fb-summary-chip">
                <p class="fb-summary-chip__label">Temáticos</p>
                <p class="fb-summary-chip__value"><?= $tematicos ?></p>
                <p class="fb-summary-chip__hint">com fluxo de reservas</p>
            </div>
        </div>
    </section>

    <div class="fb-admin-layout">
        <section class="fb-card fb-card--flat fb-admin-create">
            <div class="fb-card__head">
                <div>
                    <p class="fb-card__eyebrow">Novo cadastro</p>
                    <h2 class="fb-card__title">Adicionar restaurante</h2>
                    <p class="fb-card__subtitle">Configure o comportamento principal do ambiente.</p>
                </div>
                <span class="fb-admin-icon"><i class="bi bi-shop"></i></span>
            </div>

            <form method="post" action="/?r=restaurantes/create" class="fb-admin-form">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <label class="fb-field">
                    <span class="fb-label">Nome</span>
                    <input type="text" name="nome" class="fb-input" placeholder="Ex.: Restaurante Giardino" required>
                </label>
                <label class="fb-field">
                    <span class="fb-label">Tipo de operação</span>
                    <select name="tipo" class="fb-select">
                        <option value="buffet">Buffet</option>
                        <option value="tematico">Temático</option>
                        <option value="area">Área operacional</option>
                    </select>
                </label>
                <div class="fb-admin-options">
                    <label class="fb-admin-check">
                        <input type="checkbox" name="seleciona_porta_no_turno" value="1">
                        <span><strong>Selecionar porta</strong><small>Solicita a porta ao iniciar o turno.</small></span>
                    </label>
                    <label class="fb-admin-check">
                        <input type="checkbox" name="exige_pax" value="1" checked>
                        <span><strong>Exigir PAX</strong><small>O lançamento exige quantidade de pessoas.</small></span>
                    </label>
                </div>
                <button class="fb-btn fb-btn--primary fb-btn--lg" type="submit"><i class="bi bi-plus-lg"></i> Cadastrar restaurante</button>
            </form>
        </section>

        <section class="fb-card fb-card--flat fb-admin-list">
            <div class="fb-card__head">
                <div>
                    <p class="fb-card__eyebrow">Ambientes cadastrados</p>
                    <h2 class="fb-card__title">Configuração operacional</h2>
                    <p class="fb-card__subtitle">Selecione um restaurante para revisar ou alterar seus dados.</p>
                </div>
                <span class="fb-badge"><?= count($items) ?> registros</span>
            </div>

            <div class="fb-admin-records">
                <?php foreach ($items as $item): ?>
                    <?php
                    $portaNoTurno = (int)($item['seleciona_porta_no_turno'] ?? 0);
                    $exigePax = (int)($item['exige_pax'] ?? 0);
                    $ativo = (int)($item['ativo'] ?? 0);
                    $tipo = (string)($item['tipo'] ?? 'buffet');
                    ?>
                    <form method="post" action="/?r=restaurantes/edit" class="fb-admin-record">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                        <details>
                            <summary>
                                <span class="fb-admin-record__identity">
                                    <span class="fb-admin-record__avatar"><i class="bi bi-shop"></i></span>
                                    <span>
                                        <strong><?= h((string)$item['nome']) ?></strong>
                                        <small><?= h(ucfirst($tipo)) ?> · <?= $exigePax ? 'com PAX' : 'sem PAX' ?></small>
                                    </span>
                                </span>
                                <span class="fb-admin-record__status">
                                    <span class="fb-badge <?= $ativo ? 'fb-badge--ok' : 'fb-badge--nao-informado' ?>"><?= $ativo ? 'Ativo' : 'Inativo' ?></span>
                                    <i class="bi bi-chevron-down"></i>
                                </span>
                            </summary>
                            <div class="fb-admin-record__body fb-admin-record__body--restaurant">
                                <label class="fb-field">
                                    <span class="fb-label">Nome</span>
                                    <input type="text" name="nome" class="fb-input" value="<?= h((string)$item['nome']) ?>" required>
                                </label>
                                <label class="fb-field">
                                    <span class="fb-label">Tipo</span>
                                    <select name="tipo" class="fb-select">
                                        <option value="buffet" <?= $tipo === 'buffet' ? 'selected' : '' ?>>Buffet</option>
                                        <option value="tematico" <?= $tipo === 'tematico' ? 'selected' : '' ?>>Temático</option>
                                        <option value="area" <?= $tipo === 'area' ? 'selected' : '' ?>>Área operacional</option>
                                    </select>
                                </label>
                                <label class="fb-field">
                                    <span class="fb-label">Porta no turno</span>
                                    <select name="seleciona_porta_no_turno" class="fb-select">
                                        <option value="1" <?= $portaNoTurno === 1 ? 'selected' : '' ?>>Sim</option>
                                        <option value="0" <?= $portaNoTurno === 0 ? 'selected' : '' ?>>Não</option>
                                    </select>
                                </label>
                                <label class="fb-field">
                                    <span class="fb-label">Exige PAX</span>
                                    <select name="exige_pax" class="fb-select">
                                        <option value="1" <?= $exigePax === 1 ? 'selected' : '' ?>>Sim</option>
                                        <option value="0" <?= $exigePax === 0 ? 'selected' : '' ?>>Não</option>
                                    </select>
                                </label>
                                <label class="fb-field">
                                    <span class="fb-label">Status</span>
                                    <select name="ativo" class="fb-select">
                                        <option value="1" <?= $ativo === 1 ? 'selected' : '' ?>>Ativo</option>
                                        <option value="0" <?= $ativo === 0 ? 'selected' : '' ?>>Inativo</option>
                                    </select>
                                </label>
                                <button class="fb-btn fb-btn--primary" type="submit"><i class="bi bi-check2"></i> Salvar alterações</button>
                            </div>
                        </details>
                    </form>
                <?php endforeach; ?>

                <?php if ($items === []): ?>
                    <div class="fb-admin-empty">
                        <i class="bi bi-shop"></i>
                        <strong>Nenhum restaurante cadastrado</strong>
                        <span>Use o formulário ao lado para criar o primeiro ambiente.</span>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
