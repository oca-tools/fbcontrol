<?php
$shift = $this->data['shift'] ?? null;
$vouchers = $this->data['vouchers'] ?? [];
$flash = $this->data['flash'] ?? null;
$restaurantes = $this->data['restaurantes'] ?? [];
$operacoes = $this->data['operacoes'] ?? [];
?>
<div class="card card-soft p-4 mb-4">
    <div class="section-title">
        <div class="icon"><i class="bi bi-ticket-perforated"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Registro</div>
            <h3 class="fw-bold mb-0">Vouchers do Turno</h3>
            <div class="text-muted">Recomendado registrar ao final do turno.</div>
        </div>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-12 col-lg-6">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="text-uppercase text-muted small">Cadastro</div>
                    <h5 class="fw-bold mb-0">Novo voucher</h5>
                </div>
                <span class="badge badge-soft">Turno atual</span>
            </div>
            <form method="post" action="/?r=access/register_voucher" class="row g-3" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <div class="col-12 col-md-6">
                    <label class="form-label">Restaurante</label>
                    <select name="restaurante_id" class="form-select input-xl" required>
                        <option value="">Selecione</option>
                        <?php foreach ($restaurantes as $rest): ?>
                            <?php if ($rest['nome'] === 'Privileged') continue; ?>
                            <option value="<?= (int)$rest['id'] ?>" <?= ($shift && (int)$shift['restaurante_id'] === (int)$rest['id']) ? 'selected' : '' ?>>
                                <?= h($rest['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Operação</label>
                    <select name="operacao_id" class="form-select input-xl" required>
                        <option value="">Selecione</option>
                        <?php foreach ($operacoes as $op): ?>
                            <?php if ($op['nome'] === 'Privileged') continue; ?>
                            <option value="<?= (int)$op['id'] ?>" <?= ($shift && (int)$shift['operacao_id'] === (int)$op['id']) ? 'selected' : '' ?>>
                                <?= h($op['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Nome completo do hóspede</label>
                    <input type="text" name="nome_hospede" class="form-control input-xl" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Data da venda</label>
                    <input type="date" name="data_venda" class="form-control input-xl" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Período da estadia</label>
                    <input type="text" name="data_estadia" class="form-control input-xl" placeholder="Ex: 23/09 a 30/09" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Número da reserva</label>
                    <input type="text" name="numero_reserva" class="form-control input-xl" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Assinatura responsável</label>
                    <input type="text" name="assinatura" class="form-control input-xl" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Serviço upselling contratado</label>
                    <input type="text" name="servico_upselling" class="form-control input-xl" placeholder="Ex: Almoço Restaurante Corais" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Anexo do voucher (PDF ou imagem)</label>
                    <input type="file" name="voucher_anexo" class="form-control" accept="application/pdf,image/png,image/jpeg,image/webp">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary btn-xl w-100">Registrar voucher</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Vouchers do dia</h5>
                <span class="text-muted small">Restaurante Corais</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Hóspede</th>
                            <th>Reserva</th>
                            <th>Data</th>
                            <th>Anexo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vouchers as $row): ?>
                            <tr>
                                <td><?= h($row['nome_hospede']) ?></td>
                                <td><?= h($row['numero_reserva']) ?></td>
                                <td><?= h($row['data_venda']) ?></td>
                                <td>
                                    <?php if (!empty($row['voucher_anexo_path'])): ?>
                                        <a class="btn btn-outline-primary btn-sm" href="<?= h($row['voucher_anexo_path']) ?>" target="_blank">Abrir</a>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($vouchers)): ?>
                            <tr><td colspan="4" class="text-muted">Sem vouchers registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
