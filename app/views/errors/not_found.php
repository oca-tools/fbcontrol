<div class="fb-system-state fb-system-state--warning">
    <div class="fb-system-state__code">404</div>
    <div class="fb-system-state__icon"><i class="bi bi-compass"></i></div>
    <span class="fb-eyebrow">Destino não localizado</span>
    <h1>Esta página não está disponível.</h1>
    <p><?= h($message ?? 'O endereço pode ter mudado ou não fazer mais parte do fluxo atual.') ?></p>
    <div class="fb-system-state__actions">
        <a href="/?r=auth/login" class="btn btn-primary"><i class="bi bi-box-arrow-in-right"></i> Ir para o acesso</a>
        <a href="/?r=auth/login" class="btn btn-outline-primary"><i class="bi bi-box-arrow-in-right"></i> Tela de login</a>
    </div>
</div>
