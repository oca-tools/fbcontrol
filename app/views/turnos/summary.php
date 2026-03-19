<?php
$turno = $this->data['turno'];
$summary = $this->data['summary'];
?>
<div class="row justify-content-center">
    <div class="col-12 col-lg-6">
        <div class="card p-4 text-center">
            <div class="text-uppercase text-muted small">Turno encerrado</div>
            <h3 class="fw-bold mb-2">Resumo do turno</h3>
            <p class="text-muted">Restaurante: <span class="tag <?= restaurant_badge_class($turno['restaurante']) ?>"><?= h($turno['restaurante']) ?></span> | Operação: <span class="tag <?= operation_badge_class($turno['operacao']) ?>"><?= h($turno['operacao']) ?></span></p>
            <div class="display-6 fw-bold mb-1"><?= (int)$summary['total_pax'] ?></div>
            <div class="text-muted mb-4">Total de PAX</div>
            <div class="h5">Acessos: <?= (int)$summary['total_acessos'] ?></div>
            <a href="/?r=turnos/start" class="btn btn-primary btn-xl mt-4">Novo turno</a>
        </div>
    </div>
</div>
