<style>
    .error-state-card {
        max-width: 680px;
        margin-inline: auto;
        border-radius: 24px;
        overflow: hidden;
    }
    .error-state-icon {
        width: 58px;
        height: 58px;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: color-mix(in srgb, var(--ab-warning) 12%, transparent);
        color: var(--ab-warning);
        font-size: 1.65rem;
    }
    @media (max-width: 575.98px) {
        .error-state-card {
            padding: 1rem !important;
            border-radius: 18px;
        }
        .error-state-card .btn {
            width: 100%;
        }
    }
</style>
<div class="card card-soft p-4 p-md-5 text-center error-state-card">
    <div class="error-state-icon mb-3">
        <i class="bi bi-search"></i>
    </div>
    <div class="mb-3">
        <span class="badge badge-warning">404</span>
    </div>
    <h1 class="h4 mb-2">Página não encontrada</h1>
    <p class="text-muted mb-4"><?= h($message ?? 'A rota solicitada não existe ou foi movida.') ?></p>
    <div class="d-flex gap-2 justify-content-center flex-wrap">
        <a href="/?r=home" class="btn btn-primary"><i class="bi bi-house me-1"></i>Ir para início</a>
        <a href="/?r=auth/login" class="btn btn-outline-primary"><i class="bi bi-box-arrow-in-right me-1"></i>Tela de login</a>
    </div>
</div>
