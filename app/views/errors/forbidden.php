<div class="card card-soft p-4 p-md-5 text-center">
    <div class="mb-3">
        <span class="badge badge-danger"><i class="bi bi-shield-lock me-1"></i> 403</span>
    </div>
    <h1 class="h4 mb-2">OOps, acesso não autorizado.</h1>
    <p class="text-muted mb-4"><?= h($message ?? 'Você não possui permissão para acessar esta área.') ?></p>
    <div class="d-flex gap-2 justify-content-center flex-wrap">
        <a href="/?r=home" class="btn btn-primary"><i class="bi bi-house me-1"></i>Ir para início</a>
        <a href="/?r=auth/login" class="btn btn-outline-primary"><i class="bi bi-box-arrow-in-right me-1"></i>Entrar novamente</a>
    </div>
</div>
