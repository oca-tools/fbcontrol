<?php
$flash = $this->data['flash'] ?? null;
$config = $this->data['config'] ?? [];
$recipients = $this->data['recipients'] ?? [];
$logs = $this->data['logs'] ?? [];
?>
<div class="saas-page email-reports-page">
    <section class="saas-hero-card">
        <div class="saas-headline d-flex flex-wrap gap-3 align-items-start justify-content-between">
            <div>
                <div class="saas-label">Automação</div>
                <h3 class="saas-title mb-1">E-mail Diário</h3>
                <p class="saas-subtitle mb-0">Envio automático do resumo operacional para a liderança.</p>
            </div>
            <span class="badge badge-soft"><i class="bi bi-clock-history"></i> Rotina diária</span>
        </div>
    </section>


    <div class="row g-4">
        <div class="col-12 col-lg-6">
        <section class="saas-table-card h-100">
            <h5 class="fw-bold mb-3">Configuração de envio</h5>
            <form method="post" action="/?r=emailRelatorios/saveConfig" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo" <?= (int)($config['ativo'] ?? 0) === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="ativo">Ativar envio automático diário</label>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Hora do envio</label>
                    <input type="time" class="form-control input-xl" name="hora_envio" value="<?= h(substr((string)($config['hora_envio'] ?? '23:00:00'), 0, 5)) ?>">
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label">Assunto (use <code>{data}</code>)</label>
                    <input type="text" class="form-control input-xl" name="assunto" value="<?= h($config['assunto'] ?? 'Resumo diário A&B - {data}') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Remetente (nome)</label>
                    <input type="text" class="form-control input-xl" name="remetente_nome" value="<?= h($config['remetente_nome'] ?? 'FBControl') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Remetente (e-mail)</label>
                    <input type="email" class="form-control input-xl" name="remetente_email" value="<?= h($config['remetente_email'] ?? '') ?>" placeholder="no-reply@dominio.com">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary btn-xl">Salvar configuração</button>
                </div>
            </form>
        </section>
        </div>
        <div class="col-12 col-lg-6">
        <section class="saas-table-card h-100">
            <h5 class="fw-bold mb-3">Destinatários</h5>
            <form method="post" action="/?r=emailRelatorios/addRecipient" class="row g-2 mb-3">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <div class="col-12 col-md-8">
                    <input type="email" class="form-control input-xl" name="email" placeholder="email@dominio.com" required>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="receber_anexo_vouchers" value="1" id="receber_anexo_vouchers_novo">
                        <label class="form-check-label" for="receber_anexo_vouchers_novo">
                            Enviar anexos dos vouchers para este destinatário
                        </label>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <button class="btn btn-outline-primary btn-xl w-100">Adicionar</button>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-sm align-middle email-responsive-table">
                    <thead><tr><th>E-mail</th><th style="width:220px;">Anexo vouchers</th><th style="width:120px;">Ação</th></tr></thead>
                    <tbody>
                    <?php foreach ($recipients as $r): ?>
                        <tr>
                            <td data-label="E-mail"><?= h($r['email']) ?></td>
                            <td data-label="Anexo vouchers">
                                <form method="post" action="/?r=emailRelatorios/updateRecipientAttachment" class="d-flex align-items-center gap-2">
                                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="receber_anexo_vouchers" value="<?= (int)($r['receber_anexo_vouchers'] ?? 0) === 1 ? '0' : '1' ?>">
                                    <span class="badge <?= (int)($r['receber_anexo_vouchers'] ?? 0) === 1 ? 'badge-success' : 'badge-soft' ?>">
                                        <?= (int)($r['receber_anexo_vouchers'] ?? 0) === 1 ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                    <button class="btn btn-sm btn-outline-primary" type="submit">
                                        <?= (int)($r['receber_anexo_vouchers'] ?? 0) === 1 ? 'Desativar' : 'Ativar' ?>
                                    </button>
                                </form>
                            </td>
                            <td data-label="Ação">
                                <form method="post" action="/?r=emailRelatorios/removeRecipient" data-confirm="Remover destinatário?" data-confirm-title="Remover destinatário" data-confirm-type="danger">
                                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger">Remover</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recipients)): ?>
                        <tr><td colspan="3" class="text-muted">Nenhum destinatário.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-5">
        <section class="saas-table-card h-100">
            <h5 class="fw-bold mb-3">Envio manual</h5>
            <form method="post" action="/?r=emailRelatorios/sendNow" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <div class="col-12 col-md-7">
                    <label class="form-label">Data de referência</label>
                    <input type="date" class="form-control input-xl" name="data_referencia" value="<?= h(date('Y-m-d')) ?>">
                </div>
                <div class="col-12 col-md-5 d-flex align-items-end">
                    <button class="btn btn-success btn-xl w-100">Enviar agora</button>
                </div>
            </form>
            <div class="text-muted small mt-3">
                Indicadores incluídos: Corais, La Brasa (almoço), Privileged, VIP Premium, Day use, Não informado por operação, reservas temáticas (Giardino, IXU e La Brasa temático), PAX real e no-show.
            </div>
        </section>
        </div>
        <div class="col-12 col-lg-7">
        <section class="saas-table-card h-100">
            <h5 class="fw-bold mb-3">Histórico de envios</h5>
            <div class="table-responsive">
                <table class="table table-sm align-middle email-responsive-table">
                    <thead><tr><th>Data</th><th>Status</th><th>Destinatários</th><th>Enviado em</th><th>Erro</th></tr></thead>
                    <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td data-label="Data"><?= h($log['data_referencia']) ?></td>
                            <td data-label="Status">
                                <?php if (($log['status'] ?? '') === 'success'): ?>
                                    <span class="badge badge-success">Sucesso</span>
                                <?php elseif (($log['status'] ?? '') === 'partial'): ?>
                                    <span class="badge badge-warning">Parcial</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Erro</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Destinatários"><?= (int)($log['total_destinatarios'] ?? 0) ?></td>
                            <td data-label="Enviado em"><?= h($log['enviado_em'] ?? '') ?></td>
                            <td data-label="Erro" class="small text-muted"><?= h($log['erro'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="5" class="text-muted">Sem histórico.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        </div>
    </div>
</div>

<style>
    .email-reports-page {
        min-width: 0;
        max-width: 100%;
        overflow-x: hidden;
    }
    .email-reports-page > * {
        min-width: 0;
        max-width: 100%;
    }
    .email-reports-page .row {
        margin-left: 0;
        margin-right: 0;
        --bs-gutter-x: 1rem;
    }
    .email-reports-page .row + .row {
        margin-top: 0;
    }
    .email-reports-page .row > [class*="col-"] {
        min-width: 0;
        max-width: 100%;
        padding-left: calc(var(--bs-gutter-x) * 0.5);
        padding-right: calc(var(--bs-gutter-x) * 0.5);
    }
    .email-reports-page .saas-table-card {
        min-width: 0;
    }
    .email-reports-page .form-control,
    .email-reports-page .form-select {
        min-width: 0;
        max-width: 100%;
    }
    .email-reports-page .email-responsive-table td[data-label] {
        vertical-align: middle;
    }
    .email-reports-page .d-flex.gap-2 {
        flex-wrap: wrap;
    }
    @media (max-width: 768px) {
        .email-reports-page .saas-headline .badge {
            width: 100%;
            justify-content: center;
        }
        .email-reports-page .saas-table-card {
            padding: 1rem;
            border-radius: 16px;
        }
        .email-reports-page .table-responsive {
            overflow-x: visible;
        }
        .email-reports-page .email-responsive-table {
            border-collapse: separate;
            border-spacing: 0 0.75rem;
        }
        .email-reports-page .email-responsive-table thead {
            display: none;
        }
        .email-reports-page .email-responsive-table,
        .email-reports-page .email-responsive-table tbody,
        .email-reports-page .email-responsive-table tr,
        .email-reports-page .email-responsive-table td {
            display: block;
            width: 100%;
        }
        .email-reports-page .email-responsive-table tr {
            border: 1px solid rgba(148, 163, 184, 0.24);
            border-radius: 14px;
            padding: 0.4rem 0.75rem;
            background: var(--surface, #fff);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }
        .email-reports-page .email-responsive-table td {
            border: 0;
            padding: 0.55rem 0;
        }
        .email-reports-page .email-responsive-table td[data-label] {
            display: grid;
            grid-template-columns: minmax(104px, 38%) minmax(0, 1fr);
            gap: 0.75rem;
            align-items: center;
        }
        .email-reports-page .email-responsive-table td[data-label]::before {
            content: attr(data-label);
            color: var(--text-muted, #64748b);
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .email-reports-page .email-responsive-table td[colspan] {
            text-align: center;
            padding: 1rem 0.5rem;
        }
        .email-reports-page .email-responsive-table form.d-flex {
            align-items: stretch !important;
        }
        .email-reports-page .email-responsive-table form.d-flex .btn {
            width: 100%;
        }
        .email-reports-page .btn-xl {
            width: 100%;
            justify-content: center;
        }
    }
</style>
