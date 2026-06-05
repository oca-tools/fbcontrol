<?php
$turno = $this->data['turno'];
$summary = $this->data['summary'];
$isTematica = !empty($this->data['is_tematica']);
$tematicaSummary = $this->data['tematica_summary'] ?? [];
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
            <div class="text-uppercase text-muted small">Turno encerrado</div>
            <h3 class="fw-bold mb-2">Resumo do turno</h3>
            <div class="turno-summary-tags mb-3">
                <span class="tag <?= restaurant_badge_class($turno['restaurante']) ?>"><?= h($turno['restaurante']) ?></span>
                <span class="tag <?= operation_badge_class($turno['operacao']) ?>"><?= h($turno['operacao']) ?></span>
            </div>
            <?php if ($isTematica && !empty($tematicaSummary)): ?>
                <div class="turno-summary-metric">
                    <div class="display-6 fw-bold mb-1"><?= (int)($tematicaSummary['pax_comparecidas'] ?? 0) ?></div>
                    <div class="text-muted">PAX comparecidas (temático)</div>
                </div>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <span class="badge badge-success">Finalizadas: <?= (int)($tematicaSummary['finalizadas'] ?? 0) ?></span>
                    <span class="badge badge-warning">No-show: <?= (int)($tematicaSummary['no_shows'] ?? 0) ?></span>
                    <span class="badge badge-soft">Reservas: <?= (int)($tematicaSummary['total_reservas'] ?? 0) ?></span>
                </div>
            <?php else: ?>
                <div class="turno-summary-metric">
                    <div class="display-6 fw-bold mb-1"><?= (int)$summary['total_pax'] ?></div>
                    <div class="text-muted">Total de PAX</div>
                </div>
                <div class="h5 mt-3">Acessos: <?= (int)$summary['total_acessos'] ?></div>
            <?php endif; ?>
            <a href="/?r=turnos/start" class="btn btn-primary btn-xl mt-4">Novo turno</a>
        </div>
    </div>
</div>
