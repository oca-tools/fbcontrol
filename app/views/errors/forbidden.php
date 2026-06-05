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
        background: color-mix(in srgb, var(--ab-danger) 12%, transparent);
        color: var(--ab-danger);
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
        <i class="bi bi-shield-lock"></i>
    </div>
    <div class="mb-3">
        <span class="badge badge-danger">403</span>
    </div>
    <h1 class="h4 mb-2">Acesso não autorizado</h1>
    <p class="text-muted mb-4"><?= h($message ?? 'Seu perfil não possui permissão para acessar esta área.') ?></p>
    <div class="d-flex gap-2 justify-content-center flex-wrap">
        <a href="/?r=home" class="btn btn-primary"><i class="bi bi-house me-1"></i>Ir para início</a>
        <a href="/?r=auth/login" class="btn btn-outline-primary"><i class="bi bi-box-arrow-in-right me-1"></i>Entrar novamente</a>
    </div>
</div>
