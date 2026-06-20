<?php
$shift = $this->data['shift'] ?? null;
$vouchers = $this->data['vouchers'] ?? [];
$flash = $this->data['flash'] ?? null;
$restaurantes = $this->data['restaurantes'] ?? [];
$operacoes = $this->data['operacoes'] ?? [];
$voucherReceiveLimitBytes = (int)($this->data['voucher_receive_limit_bytes'] ?? upload_limit_bytes(10 * 1024 * 1024));
$voucherReceiveLimitLabel = (string)($this->data['voucher_receive_limit_label'] ?? format_bytes_ptbr($voucherReceiveLimitBytes));
$voucherTargetLimitBytes = (int)($this->data['voucher_target_limit_bytes'] ?? (5 * 1024 * 1024));
$voucherTargetLimitLabel = (string)($this->data['voucher_target_limit_label'] ?? format_bytes_ptbr($voucherTargetLimitBytes));
?>
<style>
    .vouchers-page,
    .vouchers-page .row,
    .vouchers-page [class*="col-"] {
        min-width: 0;
    }
    .vouchers-page .card {
        overflow: hidden;
    }
    .voucher-file-box {
        border: 1px dashed var(--ab-border);
        border-radius: 16px;
        padding: 1rem;
        background: var(--ab-soft-bg);
    }
    .voucher-file-box .form-control {
        background: var(--ab-card);
    }
    .voucher-list-table td,
    .voucher-list-table th {
        vertical-align: middle;
    }
    @media (max-width: 991.98px) {
        .vouchers-page .card.p-4,
        .vouchers-page .card-soft.p-4 {
            padding: 1rem !important;
            border-radius: 18px;
        }
        .vouchers-page .section-title {
            align-items: flex-start;
        }
    }
    @media (max-width: 575.98px) {
        .vouchers-page .section-title .icon {
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
        }
        .vouchers-page .section-title h3 {
            font-size: 1.35rem;
        }
        .vouchers-page .section-title .text-muted:not(.small) {
            display: none;
        }
        .voucher-form-card .badge {
            align-self: flex-start;
        }
        .voucher-file-box {
            padding: .85rem;
        }
        .voucher-list-table,
        .voucher-list-table tbody,
        .voucher-list-table tr,
        .voucher-list-table td {
            display: block;
            width: 100%;
        }
        .voucher-list-table thead {
            display: none;
        }
        .voucher-list-table tr {
            border: 1px solid var(--ab-border);
            border-radius: 16px;
            background: var(--ab-card);
            padding: .85rem;
            margin-bottom: .75rem;
            box-shadow: 0 10px 22px rgba(15, 23, 42, .06);
        }
        .voucher-list-table td {
            border: 0;
            padding: .35rem 0 !important;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            text-align: right;
        }
        .voucher-list-table td::before {
            content: attr(data-label);
            color: var(--ab-muted);
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            text-align: left;
        }
        .voucher-list-table td .btn {
            min-width: 92px;
        }
    }
</style>

<div class="vouchers-page">
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


<div class="row g-4">
    <div class="col-12 col-lg-6">
        <div class="card p-4 voucher-form-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="text-uppercase text-muted small">Cadastro</div>
                    <h5 class="fw-bold mb-0">Novo voucher</h5>
                </div>
                <span class="badge badge-soft">Turno atual</span>
            </div>
            <form method="post" action="/?r=vouchers/index" class="row g-3" enctype="multipart/form-data" data-voucher-form>
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
                <div class="col-12 col-md-6">
                    <label class="form-label">Data da venda</label>
                    <input type="date" name="data_venda" class="form-control input-xl" value="<?= h(date('Y-m-d')) ?>" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Nome do hóspede</label>
                    <input type="text" name="nome_hospede" class="form-control input-xl" placeholder="Nome no voucher" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Localizador</label>
                    <input type="text" name="numero_reserva" class="form-control input-xl" placeholder="Número ou código da reserva" required>
                </div>
                <div class="col-12">
                    <div class="voucher-file-box">
                    <label class="form-label">Anexo do voucher (obrigatório)</label>
                    <input type="hidden" name="MAX_FILE_SIZE" value="<?= (int)$voucherReceiveLimitBytes ?>">
                    <input type="file" name="voucher_anexo" class="form-control" accept="application/pdf,image/png,image/jpeg,image/webp" data-voucher-file data-max-bytes="<?= (int)$voucherReceiveLimitBytes ?>" data-target-bytes="<?= (int)$voucherTargetLimitBytes ?>" required>
                    <div class="form-text">Formatos aceitos: PDF, JPG, PNG ou WEBP. Imagens acima de <?= h($voucherTargetLimitLabel) ?> serão compactadas automaticamente. Limite de envio: <?= h($voucherReceiveLimitLabel) ?>.</div>
                    <div class="form-text text-muted" data-voucher-file-status></div>
                    </div>
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
                <table class="table table-sm align-middle voucher-list-table">
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
                                <td data-label="Hóspede"><?= h($row['nome_hospede']) ?></td>
                                <td data-label="Reserva"><?= h($row['numero_reserva']) ?></td>
                                <td data-label="Data"><?= h($row['data_venda']) ?></td>
                                <td data-label="Anexo">
                                    <?php if (safe_public_upload_url((string)($row['voucher_anexo_path'] ?? ''), 'vouchers') !== ''): ?>
                                        <a class="btn btn-outline-primary btn-sm" href="/?r=vouchers/attachment&id=<?= (int)$row['id'] ?>" target="_blank" rel="noopener noreferrer">Abrir</a>
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
</div>

<script>
(function () {
    var form = document.querySelector('[data-voucher-form]');
    if (!form) return;

    var input = form.querySelector('[data-voucher-file]');
    var status = form.querySelector('[data-voucher-file-status]');
    var submit = form.querySelector('button[type="submit"], button:not([type])');
    var compressing = false;

    function bytesLabel(bytes) {
        if (!bytes || bytes < 0) return '0B';
        if (bytes >= 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(bytes % (1024 * 1024) === 0 ? 0 : 1).replace('.', ',') + 'MB';
        if (bytes >= 1024) return Math.round(bytes / 1024) + 'KB';
        return bytes + 'B';
    }

    function setStatus(message, type) {
        if (!status) return;
        status.textContent = message || '';
        status.classList.remove('text-muted', 'text-danger', 'text-success');
        status.classList.add(type === 'danger' ? 'text-danger' : (type === 'success' ? 'text-success' : 'text-muted'));
    }

    function setBusy(isBusy) {
        compressing = isBusy;
        if (submit) submit.disabled = isBusy;
    }

    function canvasToBlob(canvas, type, quality) {
        return new Promise(function (resolve) {
            canvas.toBlob(resolve, type, quality);
        });
    }

    function imageFromFile(file) {
        return new Promise(function (resolve, reject) {
            var url = URL.createObjectURL(file);
            var image = new Image();
            image.onload = function () {
                URL.revokeObjectURL(url);
                resolve(image);
            };
            image.onerror = function () {
                URL.revokeObjectURL(url);
                reject(new Error('image_load_failed'));
            };
            image.src = url;
        });
    }

    async function compressImage(file, targetBytes) {
        var image = await imageFromFile(file);
        var maxSide = 2200;
        var ratio = Math.min(1, maxSide / Math.max(image.width, image.height));
        var width = Math.max(1, Math.round(image.width * ratio));
        var height = Math.max(1, Math.round(image.height * ratio));
        var canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        var ctx = canvas.getContext('2d');
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, width, height);
        ctx.drawImage(image, 0, 0, width, height);

        var qualities = [0.86, 0.78, 0.70, 0.62];
        var bestBlob = null;
        for (var i = 0; i < qualities.length; i++) {
            var blob = await canvasToBlob(canvas, 'image/jpeg', qualities[i]);
            if (!blob) continue;
            bestBlob = blob;
            if (blob.size <= targetBytes) break;
        }
        if (!bestBlob || bestBlob.size >= file.size) return file;

        var baseName = file.name.replace(/\.[^.]+$/, '');
        return new File([bestBlob], baseName + '.jpg', {
            type: 'image/jpeg',
            lastModified: Date.now()
        });
    }

    function replaceSelectedFile(file) {
        if (typeof DataTransfer === 'undefined') return false;
        var dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        input.files = dataTransfer.files;
        return true;
    }

    input.addEventListener('change', async function () {
        var file = input.files && input.files[0];
        var maxBytes = parseInt(input.getAttribute('data-max-bytes') || '0', 10);
        var targetBytes = parseInt(input.getAttribute('data-target-bytes') || '0', 10);
        if (!file || !maxBytes) {
            setStatus('');
            return;
        }

        if (file.type.indexOf('image/') !== 0) {
            if (targetBytes && file.size > targetBytes) {
                setStatus('Este PDF tem ' + bytesLabel(file.size) + ' e ultrapassa o limite de ' + bytesLabel(targetBytes) + '. Gere um PDF mais leve ou envie uma imagem.', 'danger');
            } else {
                setStatus('Arquivo selecionado: ' + bytesLabel(file.size) + '.', 'muted');
            }
            return;
        }

        if (file.size <= targetBytes) {
            setStatus('Imagem selecionada: ' + bytesLabel(file.size) + '.', 'muted');
            return;
        }
        if (file.size > maxBytes) {
            setStatus('Esta imagem tem ' + bytesLabel(file.size) + ' e ultrapassa o limite de envio de ' + bytesLabel(maxBytes) + '.', 'danger');
            return;
        }

        setBusy(true);
        setStatus('Compactando imagem antes do envio...', 'muted');
        try {
            var compressed = await compressImage(file, targetBytes);
            if (compressed.size < file.size && replaceSelectedFile(compressed)) {
                var type = compressed.size <= targetBytes ? 'success' : 'muted';
                setStatus('Imagem compactada de ' + bytesLabel(file.size) + ' para ' + bytesLabel(compressed.size) + '.', type);
            } else {
                setStatus('Não foi possível compactar no tablet. O servidor tentará compactar ao registrar.', 'muted');
            }
        } catch (e) {
            setStatus('Não foi possível compactar no tablet. O servidor tentará compactar ao registrar.', 'muted');
        } finally {
            setBusy(false);
        }
    });

    form.addEventListener('submit', function (event) {
        var file = input.files && input.files[0];
        var maxBytes = parseInt(input.getAttribute('data-max-bytes') || '0', 10);
        var targetBytes = parseInt(input.getAttribute('data-target-bytes') || '0', 10);
        if (compressing) {
            event.preventDefault();
            setStatus('Aguarde a compactação do anexo terminar.', 'danger');
            return;
        }
        if (file && maxBytes && file.size > maxBytes) {
            event.preventDefault();
            setStatus('O anexo ultrapassa o limite de envio de ' + bytesLabel(maxBytes) + '. Reduza o arquivo antes de enviar.', 'danger');
            return;
        }
        if (file && targetBytes && file.type.indexOf('image/') !== 0 && file.size > targetBytes) {
            event.preventDefault();
            setStatus('PDF acima de ' + bytesLabel(targetBytes) + '. Gere um PDF mais leve ou envie imagem.', 'danger');
        }
    });
})();
</script>
