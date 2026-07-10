<?php
$config = $this->data['config'] ?? [];
$recipients = $this->data['recipients'] ?? [];
$logs = $this->data['logs'] ?? [];
$ativo = (int)($config['ativo'] ?? 0) === 1;
$enviosComSucesso = count(array_filter($logs, static fn(array $log): bool => ($log['status'] ?? '') === 'success'));
?>

<div class="fb-email-page">
    <section class="fb-page-head">
        <div class="fb-page-head__meta">
            <div>
                <p class="fb-card__eyebrow">Automação gerencial</p>
                <h1 class="fb-page-head__title">E-mail diário</h1>
                <p class="fb-page-head__subtitle">Configure o resumo operacional enviado à liderança e acompanhe cada execução da rotina.</p>
            </div>
            <div class="fb-page-head__actions">
                <span class="fb-badge <?= $ativo ? 'fb-badge--ok' : 'fb-badge--nao-informado' ?>"><?= $ativo ? 'Rotina ativa' : 'Rotina inativa' ?></span>
            </div>
        </div>
        <div class="fb-summary-bar">
            <div class="fb-summary-chip">
                <p class="fb-summary-chip__label">Horário</p>
                <p class="fb-summary-chip__value"><?= h(substr((string)($config['hora_envio'] ?? '23:00:00'), 0, 5)) ?></p>
                <p class="fb-summary-chip__hint">execução diária</p>
            </div>
            <div class="fb-summary-chip">
                <p class="fb-summary-chip__label">Destinatários</p>
                <p class="fb-summary-chip__value"><?= count($recipients) ?></p>
                <p class="fb-summary-chip__hint">endereços cadastrados</p>
            </div>
            <div class="fb-summary-chip">
                <p class="fb-summary-chip__label">Sucessos recentes</p>
                <p class="fb-summary-chip__value"><?= $enviosComSucesso ?></p>
                <p class="fb-summary-chip__hint">no histórico carregado</p>
            </div>
        </div>
    </section>

    <div class="fb-email-grid">
        <section class="fb-card fb-card--flat fb-email-config">
            <div class="fb-card__head">
                <div>
                    <p class="fb-card__eyebrow">Configuração</p>
                    <h2 class="fb-card__title">Rotina automática</h2>
                    <p class="fb-card__subtitle">Defina horário, assunto e identidade do remetente.</p>
                </div>
                <span class="fb-admin-icon"><i class="bi bi-envelope-check"></i></span>
            </div>

            <form method="post" action="/?r=emailRelatorios/saveConfig" class="fb-email-config__form">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <label class="fb-admin-check">
                    <input type="checkbox" name="ativo" value="1" <?= $ativo ? 'checked' : '' ?>>
                    <span><strong>Ativar envio automático</strong><small>Executa a rotina diariamente no horário configurado.</small></span>
                </label>
                <label class="fb-field">
                    <span class="fb-label">Hora do envio</span>
                    <input type="time" class="fb-input" name="hora_envio" value="<?= h(substr((string)($config['hora_envio'] ?? '23:00:00'), 0, 5)) ?>">
                </label>
                <label class="fb-field fb-email-span-2">
                    <span class="fb-label">Assunto <small>Use {data} para incluir a referência</small></span>
                    <input type="text" class="fb-input" name="assunto" value="<?= h((string)($config['assunto'] ?? 'Resumo diário A&B - {data}')) ?>">
                </label>
                <label class="fb-field">
                    <span class="fb-label">Nome do remetente</span>
                    <input type="text" class="fb-input" name="remetente_nome" value="<?= h((string)($config['remetente_nome'] ?? 'FBControl')) ?>">
                </label>
                <label class="fb-field">
                    <span class="fb-label">E-mail do remetente</span>
                    <input type="email" class="fb-input" name="remetente_email" value="<?= h((string)($config['remetente_email'] ?? '')) ?>" placeholder="no-reply@dominio.com">
                </label>
                <div class="fb-email-span-2">
                    <button class="fb-btn fb-btn--primary" type="submit"><i class="bi bi-check2"></i> Salvar configuração</button>
                </div>
            </form>
        </section>

        <section class="fb-card fb-card--flat fb-email-recipients">
            <div class="fb-card__head">
                <div>
                    <p class="fb-card__eyebrow">Distribuição</p>
                    <h2 class="fb-card__title">Destinatários</h2>
                    <p class="fb-card__subtitle">Controle quem recebe o resumo e os anexos de vouchers.</p>
                </div>
                <span class="fb-badge"><?= count($recipients) ?> cadastrados</span>
            </div>

            <form method="post" action="/?r=emailRelatorios/addRecipient" class="fb-email-recipient-add">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <label class="fb-field">
                    <span class="fb-label">Novo e-mail</span>
                    <input type="email" class="fb-input" name="email" placeholder="email@dominio.com" required>
                </label>
                <label class="fb-admin-check">
                    <input type="checkbox" name="receber_anexo_vouchers" value="1">
                    <span><strong>Receber vouchers</strong><small>Inclui anexos de vouchers no envio.</small></span>
                </label>
                <button class="fb-btn fb-btn--primary" type="submit"><i class="bi bi-plus-lg"></i> Adicionar</button>
            </form>

            <div class="fb-email-recipient-list">
                <?php foreach ($recipients as $recipient): ?>
                    <?php $recebeAnexo = (int)($recipient['receber_anexo_vouchers'] ?? 0) === 1; ?>
                    <article class="fb-email-recipient">
                        <div class="fb-email-recipient__identity">
                            <span class="fb-admin-record__avatar"><i class="bi bi-envelope"></i></span>
                            <div>
                                <strong><?= h((string)$recipient['email']) ?></strong>
                                <span><?= $recebeAnexo ? 'Recebe anexos de vouchers' : 'Somente resumo operacional' ?></span>
                            </div>
                        </div>
                        <div class="fb-email-recipient__actions">
                            <form method="post" action="/?r=emailRelatorios/updateRecipientAttachment">
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$recipient['id'] ?>">
                                <input type="hidden" name="receber_anexo_vouchers" value="<?= $recebeAnexo ? '0' : '1' ?>">
                                <button class="fb-btn fb-btn--ghost" type="submit"><i class="bi <?= $recebeAnexo ? 'bi-paperclip' : 'bi-paperclip' ?>"></i> <?= $recebeAnexo ? 'Desativar anexos' : 'Ativar anexos' ?></button>
                            </form>
                            <form method="post" action="/?r=emailRelatorios/removeRecipient" data-confirm="Remover destinatário?" data-confirm-title="Remover destinatário" data-confirm-type="danger">
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$recipient['id'] ?>">
                                <button class="fb-btn fb-btn--danger" type="submit" title="Remover destinatário"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if ($recipients === []): ?>
                    <div class="fb-admin-empty"><i class="bi bi-envelope"></i><strong>Nenhum destinatário</strong><span>Adicione o primeiro endereço para ativar a distribuição.</span></div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="fb-email-lower-grid">
        <section class="fb-card fb-card--flat fb-email-manual">
            <div class="fb-card__head">
                <div>
                    <p class="fb-card__eyebrow">Execução sob demanda</p>
                    <h2 class="fb-card__title">Envio manual</h2>
                    <p class="fb-card__subtitle">Reenvie o resumo de uma data específica quando necessário.</p>
                </div>
            </div>
            <form method="post" action="/?r=emailRelatorios/sendNow" class="fb-email-manual__form">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <label class="fb-field">
                    <span class="fb-label">Data de referência</span>
                    <input type="date" class="fb-input" name="data_referencia" value="<?= h(date('Y-m-d')) ?>">
                </label>
                <button class="fb-btn fb-btn--primary" type="submit"><i class="bi bi-send"></i> Enviar agora</button>
            </form>
            <p class="fb-email-note">O resumo inclui operação buffet, acessos especiais, vouchers e reservas temáticas do período.</p>
        </section>

        <section class="fb-card fb-card--flat fb-email-history">
            <div class="fb-card__head">
                <div>
                    <p class="fb-card__eyebrow">Rastreabilidade</p>
                    <h2 class="fb-card__title">Histórico de envios</h2>
                    <p class="fb-card__subtitle">Resultado das últimas execuções registradas.</p>
                </div>
                <span class="fb-badge"><?= count($logs) ?> eventos</span>
            </div>
            <div class="fb-email-history__list">
                <?php foreach ($logs as $log): ?>
                    <?php
                    $status = (string)($log['status'] ?? 'error');
                    $statusLabel = $status === 'success' ? 'Sucesso' : ($status === 'partial' ? 'Parcial' : 'Erro');
                    $statusClass = $status === 'success' ? 'fb-badge--ok' : ($status === 'partial' ? 'fb-badge--warn' : 'fb-badge--danger');
                    ?>
                    <article class="fb-email-history__item">
                        <div>
                            <strong><?= h(format_date_br((string)($log['data_referencia'] ?? ''))) ?></strong>
                            <span><?= (int)($log['total_destinatarios'] ?? 0) ?> destinatário(s) · <?= h((string)($log['enviado_em'] ?? 'Horário não informado')) ?></span>
                            <?php if (!empty($log['erro'])): ?><small><?= h((string)$log['erro']) ?></small><?php endif; ?>
                        </div>
                        <span class="fb-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                    </article>
                <?php endforeach; ?>
                <?php if ($logs === []): ?>
                    <div class="fb-admin-empty"><i class="bi bi-clock-history"></i><strong>Sem histórico de envios</strong><span>As próximas execuções aparecerão nesta área.</span></div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
