<div class="card card-soft p-4 p-md-5 text-center">
    <div class="mb-3">
        <span class="badge badge-warning"><i class="bi bi-search me-1"></i> 404</span>
    </div>
    <h1 class="h4 mb-2">OOps, página não encontrada.</h1>
    <p class="text-muted mb-4"><?= h($message ?? 'A rota solicitada não existe ou foi movida.') ?></p>
    <div class="d-flex gap-2 justify-content-center flex-wrap">
        <a href="/?r=home" class="btn btn-primary"><i class="bi bi-house me-1"></i>Ir para início</a>
        <a href="/?r=auth/login" class="btn btn-outline-primary"><i class="bi bi-box-arrow-in-right me-1"></i>Tela de login</a>
    </div>
</div>
