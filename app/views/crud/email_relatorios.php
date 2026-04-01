<?php
$flash = $this->data['flash'] ?? null;
$config = $this->data['config'] ?? [];
$recipients = $this->data['recipients'] ?? [];
$logs = $this->data['logs'] ?? [];
?>
<div class="card card-soft p-4 mb-4">
    <div class="section-title">
        <div class="icon"><i class="bi bi-envelope-paper"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Automação</div>
            <h3 class="fw-bold mb-0">E-mail Diário</h3>
            <div class="text-muted">Envio automático do resumo operacional (23:00).</div>
        </div>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-12 col-lg-6">
        <div class="card p-4">
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
                    <input type="text" class="form-control input-xl" name="remetente_nome" value="<?= h($config['remetente_nome'] ?? 'OCA FBControl') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Remetente (e-mail)</label>
                    <input type="email" class="form-control input-xl" name="remetente_email" value="<?= h($config['remetente_email'] ?? '') ?>" placeholder="no-reply@dominio.com">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary btn-xl">Salvar configuração</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card p-4">
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
                <table class="table table-sm align-middle">
                    <thead><tr><th>E-mail</th><th style="width:220px;">Anexo vouchers</th><th style="width:120px;">Ação</th></tr></thead>
                    <tbody>
                    <?php foreach ($recipients as $r): ?>
                        <tr>
                            <td><?= h($r['email']) ?></td>
                            <td>
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
                            <td>
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
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-5">
        <div class="card p-4">
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
                Indicadores incluídos: Corais, La Brasa (almoço), Privileged, VIP Premium, Day use, Não informado, reservas temáticas (Giardino, IXU e La Brasa temático), PAX real e no-show.
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-7">
        <div class="card p-4">
            <h5 class="fw-bold mb-3">Histórico de envios</h5>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead><tr><th>Data</th><th>Status</th><th>Destinatários</th><th>Enviado em</th><th>Erro</th></tr></thead>
                    <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= h($log['data_referencia']) ?></td>
                            <td>
                                <?php if (($log['status'] ?? '') === 'success'): ?>
                                    <span class="badge badge-success">Sucesso</span>
                                <?php elseif (($log['status'] ?? '') === 'partial'): ?>
                                    <span class="badge badge-warning">Parcial</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Erro</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int)($log['total_destinatarios'] ?? 0) ?></td>
                            <td><?= h($log['enviado_em'] ?? '') ?></td>
                            <td class="small text-muted"><?= h($log['erro'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="5" class="text-muted">Sem histórico.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
