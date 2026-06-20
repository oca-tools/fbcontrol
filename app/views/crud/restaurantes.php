<?php $flash = $this->data['flash'] ?? null; ?>
<style>
    .config-crud-page,
    .config-crud-page .row,
    .config-crud-page [class*="col-"] {
        min-width: 0;
    }
    .config-crud-page .card {
        overflow: hidden;
    }
    .config-edit-details > summary {
        display: flex;
    }
    .config-edit-summary {
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        cursor: pointer;
        list-style: none;
        padding: .15rem 0 .85rem;
        font-weight: 800;
    }
    .config-edit-summary::-webkit-details-marker {
        display: none;
    }
    .config-edit-summary .bi-chevron-down {
        color: var(--ab-primary);
        transition: transform .18s ease;
    }
    .config-edit-details[open] .config-edit-summary .bi-chevron-down {
        transform: rotate(180deg);
    }
    @media (max-width: 991.98px) {
        .config-crud-page .card.p-4,
        .config-crud-page .card-soft.p-4 {
            padding: 1rem !important;
            border-radius: 18px;
        }
        .config-edit-details > summary {
            display: flex;
        }
        .config-edit-details:not([open]) .config-edit-body {
            display: none !important;
        }
    }
    @media (max-width: 575.98px) {
        .config-crud-page .section-title .icon {
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
        }
        .config-crud-page .section-title h3,
        .config-crud-page h4 {
            font-size: 1.25rem;
        }
    }
</style>
<div class="config-crud-page">
<div class="card card-soft p-4 mb-4">
    <div class="section-title">
        <div class="icon"><i class="bi bi-building"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Cadastros</div>
            <h3 class="fw-bold mb-0">Restaurantes</h3>
        </div>
    </div>
</div>
<div class="row g-4">
    <div class="col-12 col-lg-4">
        <div class="card p-4">
            <div class="text-uppercase text-muted small">Cadastro</div>
            <h4 class="fw-bold">Novo Restaurante</h4>
            <form method="post" action="/?r=restaurantes/create">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <div class="mb-3">
                    <label class="form-label">Nome</label>
                    <input type="text" name="nome" class="form-control input-xl" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipo</label>
                    <select name="tipo" class="form-select input-xl">
                        <option value="buffet">Buffet</option>
                        <option value="tematico">Temático</option>
                        <option value="area">Área</option>
                    </select>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="seleciona_porta_no_turno" value="1" id="sel_porta">
                    <label class="form-check-label" for="sel_porta">Seleciona porta no turno</label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="exige_pax" value="1" id="exige_pax" checked>
                    <label class="form-check-label" for="exige_pax">Exige PAX</label>
                </div>
                <button class="btn btn-success btn-xl w-100">Cadastrar</button>
            </form>
        </div>
    </div>
    <div class="col-12 col-lg-8">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0">Restaurantes</h4>
                <span class="text-muted small">Edite e organize</span>
            </div>
            <div class="row g-3">
                <?php foreach ($this->data['items'] as $item): ?>
                    <?php
                    $portaNoTurno = (int)($item['seleciona_porta_no_turno'] ?? 0);
                    $exigePax = (int)($item['exige_pax'] ?? 0);
                    $ativo = (int)($item['ativo'] ?? 0);
                    ?>
                    <div class="col-12">
                        <form method="post" action="/?r=restaurantes/edit" class="card p-3">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                            <details class="config-edit-details" open data-config-mobile-collapsed>
                                <summary class="config-edit-summary">
                                    <span>
                                        <?= h($item['nome']) ?>
                                        <span class="badge <?= $ativo === 1 ? 'badge-success' : 'badge-soft' ?> ms-1"><?= $ativo === 1 ? 'Ativo' : 'Inativo' ?></span>
                                    </span>
                                    <i class="bi bi-chevron-down"></i>
                                </summary>
                            <div class="row g-3 align-items-end config-edit-body">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small text-muted">Nome</label>
                                    <input type="text" name="nome" class="form-control" value="<?= h($item['nome']) ?>">
                                    <?php if (stripos($item['nome'], 'La Brasa') !== false): ?>
                                        <div class="mt-2">
                                        <span class="badge badge-warning">Híbrido (Buffet + Temático)</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label small text-muted">Tipo</label>
                                    <select name="tipo" class="form-select">
                                        <option value="buffet" <?= $item['tipo'] === 'buffet' ? 'selected' : '' ?>>Buffet</option>
                                        <option value="tematico" <?= $item['tipo'] === 'tematico' ? 'selected' : '' ?>>Temático</option>
                                        <option value="area" <?= $item['tipo'] === 'area' ? 'selected' : '' ?>>Área</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label small text-muted">Porta no turno</label>
                                    <select name="seleciona_porta_no_turno" class="form-select">
                                        <option value="1" <?= $portaNoTurno === 1 ? 'selected' : '' ?>>Sim</option>
                                        <option value="0" <?= $portaNoTurno === 0 ? 'selected' : '' ?>>Não</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label small text-muted">Exige PAX</label>
                                    <select name="exige_pax" class="form-select">
                                        <option value="1" <?= $exigePax === 1 ? 'selected' : '' ?>>Sim</option>
                                        <option value="0" <?= $exigePax === 0 ? 'selected' : '' ?>>Não</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label small text-muted">Status</label>
                                    <select name="ativo" class="form-select">
                                        <option value="1" <?= $ativo === 1 ? 'selected' : '' ?>>Ativo</option>
                                        <option value="0" <?= $ativo === 0 ? 'selected' : '' ?>>Inativo</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-2">
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
