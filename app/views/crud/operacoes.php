<?php $flash = $this->data['flash'] ?? null; ?>
<style>
    .config-crud-page,
    .config-crud-page .row,
    .config-crud-page [class*="col-"] { min-width: 0; }
    .config-crud-page .card { overflow: hidden; }
    .config-edit-details > summary { display: flex; }
    .config-edit-summary {
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        cursor: pointer;
        list-style: none;
        padding: .15rem 0 .85rem;
        font-weight: 800;
    }
    .config-edit-summary::-webkit-details-marker { display: none; }
    .config-edit-summary .bi-chevron-down {
        color: var(--ab-primary);
        transition: transform .18s ease;
    }
    .config-edit-details[open] .config-edit-summary .bi-chevron-down { transform: rotate(180deg); }
    @media (max-width: 991.98px) {
        .config-crud-page .card.p-4,
        .config-crud-page .card-soft.p-4 {
            padding: 1rem !important;
            border-radius: 18px;
        }
        .config-edit-details > summary { display: flex; }
        .config-edit-details:not([open]) .config-edit-body { display: none !important; }
    }
    @media (max-width: 575.98px) {
        .config-crud-page .section-title .icon {
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
        }
        .config-crud-page .section-title h3,
        .config-crud-page h4 { font-size: 1.25rem; }
    }
</style>
<div class="config-crud-page">
<div class="card card-soft p-4 mb-4">
    <div class="section-title">
        <div class="icon"><i class="bi bi-collection"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Cadastros</div>
            <h3 class="fw-bold mb-0">Operações</h3>
        </div>
    </div>
</div>
<div class="row g-4">
    <div class="col-12 col-lg-4">
        <div class="card p-4">
            <div class="text-uppercase text-muted small">Cadastro</div>
            <h4 class="fw-bold">Nova Operação</h4>
            <form method="post" action="/?r=operacoes/create">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <div class="mb-3">
                    <label class="form-label">Nome</label>
                    <input type="text" name="nome" class="form-control input-xl" required>
                </div>
                <button class="btn btn-success btn-xl w-100">Cadastrar</button>
            </form>
        </div>
    </div>
    <div class="col-12 col-lg-8">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0">Operações</h4>
                <span class="text-muted small">Gestão de serviços</span>
            </div>
            <div class="row g-3">
                <?php foreach ($this->data['items'] as $item): ?>
                    <div class="col-12">
                        <form method="post" action="/?r=operacoes/edit" class="card p-3">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                            <details class="config-edit-details" open data-config-mobile-collapsed>
                                <summary class="config-edit-summary">
                                    <span>
                                        <?= h($item['nome']) ?>
                                        <span class="badge <?= $item['ativo'] ? 'badge-success' : 'badge-soft' ?> ms-1"><?= $item['ativo'] ? 'Ativo' : 'Inativo' ?></span>
                                    </span>
                                    <i class="bi bi-chevron-down"></i>
                                </summary>
                            <div class="row g-3 align-items-end config-edit-body">
                                <div class="col-12 col-md-6">
                                    <label class="form-label small text-muted">Nome</label>
                                    <input type="text" name="nome" class="form-control" value="<?= h($item['nome']) ?>">
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label small text-muted">Status</label>
                                    <select name="ativo" class="form-select">
                                        <option value="1" <?= $item['ativo'] ? 'selected' : '' ?>>Ativo</option>
                                        <option value="0" <?= !$item['ativo'] ? 'selected' : '' ?>>Inativo</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-3">
                                    <button class="btn btn-outline-primary w-100">Salvar</button>
                                </div>
                            </div>
                            </details>
                        </form>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($this->data['items'])): ?>
                    <div class="col-12 text-muted">Sem registros.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>
<script>
(() => {
    const isMobile = window.matchMedia('(max-width: 991.98px)').matches;
    document.querySelectorAll('[data-config-mobile-collapsed]').forEach((panel) => {
        if (isMobile) panel.removeAttribute('open');
        else panel.setAttribute('open', 'open');
    });
})();
</script>
