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
    .vouchers-page { --vp-ink:#17243a; --vp-muted:#536982; --vp-line:#cedde8; --vp-cyan:#109bb5; --vp-cyan-soft:#e0f5f8; --vp-orange:#e36f14; --vp-green:#168653; color:var(--vp-ink); max-width:1120px; margin:0 auto; }
    .vouchers-page * { min-width:0; }.voucher-hero { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1rem; padding:.35rem 0; }.voucher-hero__title { display:flex; align-items:center; gap:.85rem; }.voucher-hero__icon { display:grid; place-items:center; width:50px; height:50px; border-radius:15px; color:#fff; background:linear-gradient(145deg,#f88735,#df5d0b); box-shadow:0 10px 20px rgba(216,106,19,.22); font-size:1.35rem; }.voucher-hero__eyebrow { margin:0 0 .15rem; color:var(--vp-orange); font-size:.68rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; }.voucher-hero h1 { margin:0; font-size:1.45rem; font-weight:850; }.voucher-hero p { margin:.2rem 0 0; color:var(--vp-muted); font-size:.8rem; }.voucher-hero__counter { display:flex; align-items:center; gap:.6rem; padding:.7rem .85rem; border:1px solid #c6e9ee; border-radius:14px; background:var(--vp-cyan-soft); }.voucher-hero__counter i { color:var(--vp-cyan); font-size:1.15rem; }.voucher-hero__counter b { display:block; color:#08778f; font-size:1rem; line-height:1; }.voucher-hero__counter span { display:block; margin-top:.16rem; color:#387184; font-size:.67rem; font-weight:800; }
    .voucher-workspace { display:grid; grid-template-columns:minmax(0,1.08fr) minmax(330px,.92fr); gap:1rem; align-items:start; }.voucher-panel { border:1px solid var(--vp-line); border-radius:18px; background:var(--ab-card,#fff); box-shadow:0 12px 30px rgba(26,47,73,.06); overflow:hidden; }.voucher-panel__head { display:flex; justify-content:space-between; align-items:flex-start; gap:.85rem; padding:1.05rem 1.1rem .8rem; border-bottom:1px solid var(--vp-line); }.voucher-panel__head small { display:block; color:var(--vp-orange); font-size:.66rem; font-weight:900; letter-spacing:.07em; text-transform:uppercase; }.voucher-panel__head h2 { margin:.18rem 0 0; font-size:1.03rem; font-weight:850; }.voucher-panel__head p { margin:.22rem 0 0; color:var(--vp-muted); font-size:.75rem; }.voucher-context { display:inline-flex; align-items:center; gap:.35rem; padding:.4rem .55rem; border-radius:999px; color:#08778f; background:var(--vp-cyan-soft); font-size:.68rem; font-weight:850; white-space:nowrap; }.voucher-context.is-off { color:#986119; background:#fff2e2; }
    .voucher-form { padding:1.05rem 1.1rem 1.1rem; }.voucher-form__context { display:grid; grid-template-columns:1fr 1fr; gap:.65rem; margin-bottom:.8rem; }.voucher-form__context .form-select { min-height:44px; }.voucher-form__grid { display:grid; grid-template-columns:1fr 1fr; gap:.7rem; }.voucher-form__field--wide { grid-column:1 / -1; }.voucher-form label { display:block; margin:0 0 .3rem; color:#4d617a; font-size:.68rem; font-weight:900; letter-spacing:.035em; text-transform:uppercase; }.voucher-form .form-control,.voucher-form .form-select { min-height:44px; color:var(--vp-ink); border-color:#d2e0eb; font-weight:650; }.voucher-file-box { margin-top:.1rem; padding:.85rem; border:1px dashed #9dced9; border-radius:14px; background:#f5fbfc; transition:border-color .2s,background .2s; }.voucher-file-box.is-dragover { border-color:var(--vp-cyan); background:var(--vp-cyan-soft); }.voucher-file-box__top { display:flex; align-items:center; justify-content:space-between; gap:.75rem; }.voucher-file-box__top > div { display:flex; gap:.55rem; align-items:center; }.voucher-file-box__icon { display:grid; place-items:center; width:34px; height:34px; border-radius:10px; color:#08778f; background:#dff4f7; }.voucher-file-box__title { font-size:.8rem; font-weight:850; }.voucher-file-box__hint { color:var(--vp-muted); font-size:.68rem; }.voucher-file-box input[type="file"] { margin-top:.7rem; background:#fff; }.voucher-file-box__status { min-height:1.1rem; margin-top:.45rem; font-size:.72rem; font-weight:700; }.voucher-form__submit { min-height:48px; margin-top:1rem; font-weight:850; }.voucher-required { color:#d63d43; font-weight:900; }
    .voucher-queue { padding:.35rem .55rem .55rem; }.voucher-queue__item { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:.75rem; align-items:center; padding:.85rem .6rem; border-radius:13px; }.voucher-queue__item + .voucher-queue__item { border-top:1px solid #e8eef4; }.voucher-queue__item:hover { background:#f7fbfd; }.voucher-queue__guest { font-size:.86rem; font-weight:850; }.voucher-queue__guest span { display:flex; flex-wrap:wrap; gap:.35rem; margin-top:.26rem; color:var(--vp-muted); font-size:.71rem; font-weight:700; }.voucher-queue__code { display:inline-flex; align-items:center; padding:.18rem .38rem; border-radius:6px; color:#406078; background:#eef4f8; font-size:.67rem; font-weight:850; }.voucher-queue__time { color:var(--vp-muted); font-size:.69rem; }.voucher-open { display:grid; place-items:center; width:36px; height:36px; border:1px solid #bfe2e9; border-radius:10px; color:#08778f; background:#fff; text-decoration:none; }.voucher-queue__empty { display:grid; place-items:center; min-height:230px; color:var(--vp-muted); text-align:center; font-size:.82rem; }.voucher-queue__empty i { display:block; margin-bottom:.45rem; color:#9ecbd6; font-size:1.45rem; }
    html[data-theme="dark"] .vouchers-page { --vp-ink:#edf5fb; --vp-muted:#adbdcf; --vp-line:#304458; --vp-cyan-soft:#153b45; }html[data-theme="dark"] .voucher-panel { background:var(--ab-card,#152235); }html[data-theme="dark"] .voucher-file-box { border-color:#3d6f7c; background:#142a36; }html[data-theme="dark"] .voucher-file-box input[type="file"],html[data-theme="dark"] .voucher-form .form-control,html[data-theme="dark"] .voucher-form .form-select,html[data-theme="dark"] .voucher-open { color:#eef6fc; border-color:#3d5268; background:#172638; }html[data-theme="dark"] .voucher-queue__item:hover { background:#192b3c; }
    @media (max-width:991px) { .voucher-workspace { grid-template-columns:1fr; }.voucher-queue__empty { min-height:130px; } }
    @media (max-width:575px) { .voucher-hero { align-items:flex-start; }.voucher-hero__counter { padding:.55rem; }.voucher-hero__counter span { display:none; }.voucher-hero__title { gap:.65rem; }.voucher-hero__icon { width:43px; height:43px; border-radius:13px; font-size:1.15rem; }.voucher-hero h1 { font-size:1.18rem; }.voucher-hero p { display:none; }.voucher-panel__head,.voucher-form { padding-left:.9rem; padding-right:.9rem; }.voucher-form__context,.voucher-form__grid { grid-template-columns:1fr; }.voucher-file-box__top { align-items:flex-start; }.voucher-context { font-size:.61rem; }.voucher-queue { padding:.3rem .42rem .45rem; } }
</style>

<div class="vouchers-page">
    <header class="voucher-hero">
        <div class="voucher-hero__title"><span class="voucher-hero__icon"><i class="bi bi-receipt-cutoff"></i></span><div><p class="voucher-hero__eyebrow">Comprovantes da operação</p><h1>Vouchers</h1><p>Registre o comprovante e acompanhe a fila do dia.</p></div></div>
        <div class="voucher-hero__counter"><i class="bi bi-files"></i><div><b><?= count($vouchers) ?></b><span>registrados hoje</span></div></div>
    </header>

    <div class="voucher-workspace">
        <section class="voucher-panel">
            <header class="voucher-panel__head"><div><small>Registro rápido</small><h2>Novo voucher</h2><p>Preencha apenas o necessário e anexe o comprovante.</p></div><span class="voucher-context<?= $shift ? '' : ' is-off' ?>"><i class="bi bi-<?= $shift ? 'check2-circle' : 'exclamation-circle' ?>"></i><?= $shift ? 'Turno atual' : 'Sem turno aberto' ?></span></header>
            <form method="post" action="/?r=vouchers/index" class="voucher-form" enctype="multipart/form-data" data-voucher-form>
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <div class="voucher-form__context">
                    <div><label>Restaurante <span class="voucher-required">*</span></label>
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
                    <div><label>Operação <span class="voucher-required">*</span></label>
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
                </div>
                <div class="voucher-form__grid">
                    <div><label>Data da venda <span class="voucher-required">*</span></label><input type="date" name="data_venda" class="form-control input-xl" value="<?= h(date('Y-m-d')) ?>" required></div>
                    <div><label>Localizador <span class="voucher-required">*</span></label><input type="text" name="numero_reserva" class="form-control input-xl" placeholder="Código da reserva" required></div>
                    <div class="voucher-form__field--wide"><label>Nome do hóspede <span class="voucher-required">*</span></label><input type="text" name="nome_hospede" class="form-control input-xl" placeholder="Nome conforme o voucher" required></div>
                    <div class="voucher-form__field--wide">
                    <div class="voucher-file-box">
                        <div class="voucher-file-box__top"><div><span class="voucher-file-box__icon"><i class="bi bi-paperclip"></i></span><div><span class="voucher-file-box__title">Anexo do voucher <span class="voucher-required">*</span></span><span class="voucher-file-box__hint">PDF, JPG, PNG ou WEBP</span></div></div></div>
                    <input type="hidden" name="MAX_FILE_SIZE" value="<?= (int)$voucherReceiveLimitBytes ?>">
                    <input type="file" name="voucher_anexo" class="form-control" accept="application/pdf,image/png,image/jpeg,image/webp" data-voucher-file data-max-bytes="<?= (int)$voucherReceiveLimitBytes ?>" data-target-bytes="<?= (int)$voucherTargetLimitBytes ?>" required>
                    <div class="voucher-file-box__hint">Imagens acima de <?= h($voucherTargetLimitLabel) ?> serão compactadas. Limite de envio: <?= h($voucherReceiveLimitLabel) ?>.</div>
                    <div class="voucher-file-box__status text-muted" data-voucher-file-status></div>
                    </div>
                    </div>
                </div>
                <button class="fb-btn fb-btn--primary fb-btn--lg w-100 voucher-form__submit" type="submit"><i class="bi bi-check2-circle me-1"></i>Registrar voucher</button>
            </form>
        </section>
        <section class="voucher-panel">
            <header class="voucher-panel__head"><div><small>Fila do dia</small><h2>Últimos vouchers</h2><p><?= $shift ? h((string)($shift['restaurante'] ?? 'Turno atual')) : 'Registros mais recentes' ?></p></div><span class="voucher-context"><i class="bi bi-clock-history"></i><?= count($vouchers) ?></span></header>
            <div class="voucher-queue">
                <?php foreach ($vouchers as $row): ?>
                    <article class="voucher-queue__item"><div class="voucher-queue__guest"><?= h((string)$row['nome_hospede']) ?><span><span class="voucher-queue__code"><?= h((string)$row['numero_reserva']) ?></span><span><?= h(format_date_br((string)$row['data_venda'])) ?></span><?php if (!empty($row['criado_em'])): ?><span class="voucher-queue__time"><?= h(substr((string)$row['criado_em'], 11, 5)) ?></span><?php endif; ?></span></div><?php if (safe_public_upload_url((string)($row['voucher_anexo_path'] ?? ''), 'vouchers') !== ''): ?><a class="voucher-open" href="/?r=vouchers/attachment&id=<?= (int)$row['id'] ?>" target="_blank" rel="noopener noreferrer" aria-label="Abrir anexo de <?= h((string)$row['nome_hospede']) ?>"><i class="bi bi-box-arrow-up-right"></i></a><?php else: ?><span class="voucher-open" title="Anexo indisponível"><i class="bi bi-paperclip"></i></span><?php endif; ?></article>
                <?php endforeach; ?>
                <?php if (empty($vouchers)): ?><div class="voucher-queue__empty"><div><i class="bi bi-receipt"></i>Nenhum voucher registrado neste contexto.</div></div><?php endif; ?>
            </div>
        </section>
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
