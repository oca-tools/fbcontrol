<?php
$turno = $this->data['turno'];
$summary = $this->data['summary'];
$tipoLabel = $turno['tipo'] === 'privileged' ? 'Privileged' : 'Temático';
?>
<style>
    .turno-summary-page .card {
        border-radius: 24px;
        overflow: hidden;
    }
    .turno-summary-tags {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: .5rem;
    }
    .turno-summary-metric {
        background: var(--ab-soft-bg);
        border: 1px solid var(--ab-border);
        border-radius: 18px;
        padding: 1rem;
        margin-top: 1rem;
    }
    @media (max-width: 575.98px) {
        .turno-summary-page .card {
            padding: 1rem !important;
            border-radius: 18px;
        }
        .turno-summary-page h3 {
            font-size: 1.35rem;
        }
        .turno-summary-page .display-6 {
            font-size: 2rem;
        }
        .turno-summary-page .btn {
            width: 100%;
        }
    }
</style>
<div class="row justify-content-center turno-summary-page">
    <div class="col-12 col-lg-6">
        <div class="card p-4 text-center">
            <div class="text-uppercase text-muted small">Turno especial encerrado</div>
            <h3 class="fw-bold mb-2">Resumo do turno</h3>
            <div class="turno-summary-tags mb-3">
                <span class="tag <?= restaurant_badge_class($turno['restaurante']) ?>"><?= h($turno['restaurante']) ?></span>
                <span class="tag <?= operation_badge_class($tipoLabel) ?>"><?= h($tipoLabel) ?></span>
            </div>
            <div class="turno-summary-metric">
                <div class="display-6 fw-bold mb-1"><?= (int)$summary['total_pax'] ?></div>
                <div class="text-muted">Total de PAX</div>
            </div>
            <div class="h5">Acessos: <?= (int)$summary['total_acessos'] ?></div>
            <a href="/?r=especiais/index" class="btn btn-primary btn-xl mt-4">Novo turno especial</a>
        </div>
    </div>
</div>
