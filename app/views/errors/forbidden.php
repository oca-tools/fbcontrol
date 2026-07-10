<div class="fb-system-state fb-system-state--danger">
    <div class="fb-system-state__code">403</div>
    <div class="fb-system-state__icon"><i class="bi bi-shield-lock"></i></div>
    <span class="fb-eyebrow">Acesso restrito</span>
    <h1>Você não tem acesso a esta área.</h1>
    <p><?= h($message ?? 'Seu perfil não possui a permissão necessária. Volte ao início ou entre com outra conta autorizada.') ?></p>
    <div class="fb-system-state__actions">
        <a href="/?r=home" class="btn btn-primary"><i class="bi bi-house"></i> Ir para o início</a>
        <a href="/?r=auth/login" class="btn btn-outline-primary"><i class="bi bi-box-arrow-in-right"></i> Trocar acesso</a>
    </div>
</div>
