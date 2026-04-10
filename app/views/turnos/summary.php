<?php
$turno = $this->data['turno'];
$summary = $this->data['summary'];
$isTematica = !empty($this->data['is_tematica']);
$tematicaSummary = $this->data['tematica_summary'] ?? [];
?>
<div class="row justify-content-center">
    <div class="col-12 col-lg-6">
        <div class="card p-4 text-center">
            <div class="text-uppercase text-muted small">Turno encerrado</div>
            <h3 class="fw-bold mb-2">Resumo do turno</h3>
            <p class="text-muted">Restaurante: <span class="tag <?= restaurant_badge_class($turno['restaurante']) ?>"><?= h($turno['restaurante']) ?></span> | Operacao: <span class="tag <?= operation_badge_class($turno['operacao']) ?>"><?= h($turno['operacao']) ?></span></p>
            <?php if ($isTematica && !empty($tematicaSummary)): ?>
                <div class="display-6 fw-bold mb-1"><?= (int)($tematicaSummary['pax_comparecidas'] ?? 0) ?></div>
                <div class="text-muted mb-3">PAX comparecidas (tematico)</div>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <span class="badge badge-success">Finalizadas: <?= (int)($tematicaSummary['finalizadas'] ?? 0) ?></span>
                    <span class="badge badge-warning">No-show: <?= (int)($tematicaSummary['no_shows'] ?? 0) ?></span>
                    <span class="badge badge-soft">Reservas: <?= (int)($tematicaSummary['total_reservas'] ?? 0) ?></span>
                </div>
            <?php else: ?>
                <div class="display-6 fw-bold mb-1"><?= (int)$summary['total_pax'] ?></div>
                <div class="text-muted mb-4">Total de PAX</div>
                <div class="h5">Acessos: <?= (int)$summary['total_acessos'] ?></div>
            <?php endif; ?>
            <a href="/?r=turnos/start" class="btn btn-primary btn-xl mt-4">Novo turno</a>
        </div>
    </div>
</div>
