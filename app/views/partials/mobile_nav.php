<?php /* Navegação única (pilar 1): a barra superior mobile é só contexto de marca.
   As ações (guia, demo, sair, tema) e os destinos secundários vivem na gaveta única
   #mobileMenu, aberta pela barra inferior ("Mais"). Sem ações duplicadas. */ ?>
<div class="mobile-nav">
    <a class="brand" href="/?r=auth/login" aria-label="FBControl — início" style="text-decoration: none;">
        <?php if (!empty($logoPath)): ?>
            <img src="<?= h($logoPath) ?>?v=20260705g" data-logo-light="<?= h($logoPath) ?>?v=20260705g" data-logo-dark="/assets/logo-fbcontrol-dark.svg?v=20260705g" alt="Logo do FBControl" class="mobile-brand-logo js-theme-logo">
        <?php else: ?>
            <span class="brand-main"><?= h($appName) ?></span>
        <?php endif; ?>
        <span class="brand-sub"><?= h($perfilLabel ?? ucfirst((string)($user['perfil'] ?? ''))) ?></span>
    </a>
</div>
<?php
/* Launcher de módulos: grade de azulejos coloridos pela identidade, no lugar da
   lista vertical estilo sidebar. Mesmo componente serve mobile (folha inferior)
   e desktop (popover central) — só a pele muda por breakpoint (CSS .fb-launcher).
   Cor por azulejo = área; texto no tom escuro da própria família (AA). */
$perfilLauncher = (string)($user['perfil'] ?? '');
$ehGestor = in_array($perfilLauncher, ['admin', 'supervisor', 'gerente'], true);
$launcherTile = static function (string $route, $match, string $icon, string $label, string $accent = '') use ($navIsActive): void {
    $ativo = $navIsActive($match ?: $route);
    echo '<a class="fb-tile' . ($ativo ? ' is-active' : '') . '" href="/?r=' . h($route) . '"'
        . ' data-search="' . h(mb_strtolower($label, 'UTF-8')) . '"'
        . ($accent !== '' ? ' style="--fb-tile-accent: ' . h($accent) . ';"' : '')
        . ($ativo ? ' aria-current="page"' : '') . '>'
        . '<i class="bi ' . h($icon) . ' fb-tile__icon" aria-hidden="true"></i>'
        . '<span class="fb-tile__label">' . h($label) . '</span></a>';
};
$launcherAdminTile = static function (string $route, string $icon, string $label) use ($launcherTile): void {
    $launcherTile($route, $route, $icon, $label);
};
?>
<div class="offcanvas offcanvas-bottom fb-launcher" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
    <div class="offcanvas-body">
        <div class="fb-launcher__head">
            <h5 class="fb-launcher__title" id="mobileMenuLabel">FB<span>Control</span> · tudo</h5>
            <button type="button" class="fb-launcher__close" data-bs-dismiss="offcanvas" aria-label="Fechar"><i class="bi bi-x-lg"></i></button>
        </div>

        <div class="fb-launcher__search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="text" data-fb-launcher-search placeholder="Buscar tela…" autocomplete="off" aria-label="Buscar tela">
        </div>

        <div class="fb-launcher__section">
            <p class="fb-launcher__label">Dia a dia</p>
            <div class="fb-launcher__grid">
                <?php if (in_array($perfilLauncher, ['admin', 'hostess', 'supervisor', 'gerente'], true)): ?>
                    <?php $launcherTile('access/index', 'access/index', 'bi-clipboard-check', 'Registro', '#2E7C9E'); ?>
                <?php endif; ?>
                <?php if ($perfilLauncher === 'hostess'): ?>
                    <?php if ($canTematicasReserva): ?><?php $launcherTile('reservasTematicas/reservas', ['reservasTematicas/reservas', 'reservasTematicas/conferencia'], 'bi-calendar-heart', 'Reservas', '#6C5CB0'); ?><?php endif; ?>
                    <?php $launcherTile('hostess/perfil', ['hostess/perfil', 'hostess/turnos'], 'bi-person-circle', 'Meu perfil', '#4E8B3B'); ?>
                <?php endif; ?>
                <?php if (in_array($perfilLauncher, ['admin', 'supervisor', 'hostess', 'gerente'], true)): ?>
                    <?php $launcherTile('vouchers/index', 'vouchers/index', 'bi-ticket-perforated', 'Vouchers', '#B07D2A'); ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($ehGestor): ?>
            <div class="fb-launcher__section">
                <p class="fb-launcher__label">Gestão e BI</p>
                <div class="fb-launcher__grid">
                    <?php $launcherTile('operacao/index', ['operacao/index', 'control/index'], 'bi-speedometer2', 'Operação', '#1F1F1E'); ?>
                    <?php $launcherTile('analise/index', ['analise/index', 'dashboard/index', 'dashboard/restaurant'], 'bi-bar-chart', 'Análise', '#15B1C9'); ?>
                    <?php $launcherTile('relatorios/index', 'relatorios/index', 'bi-file-earmark-text', 'Relatórios', '#E67E3C'); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($canTematicas && $perfilLauncher !== 'hostess'): ?>
            <div class="fb-launcher__section">
                <p class="fb-launcher__label">Temáticos</p>
                <div class="fb-launcher__grid">
                    <?php if ($ehGestor || $canTematicasReserva): ?>
                        <?php $launcherTile('reservasTematicas/reservas', 'reservasTematicas/reservas', 'bi-calendar-heart', 'Reservas', '#6C5CB0'); ?>
                    <?php endif; ?>
                    <?php $launcherTile('reservasTematicas/operacao', 'reservasTematicas/operacao', 'bi-clipboard-data', 'Operação temática', '#6C5CB0'); ?>
                    <?php if ($ehGestor): ?>
                        <?php $launcherTile('reservasTematicas/admin', 'reservasTematicas/admin', 'bi-sliders', 'Config. temáticas', '#6C5CB0'); ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($ehGestor): ?>
            <?php
            $mostrarAdmin = in_array($perfilLauncher, ['admin', 'gerente'], true);
            $ehAdmin = $perfilLauncher === 'admin';
            ?>
            <?php if ($mostrarAdmin): ?>
                <div class="fb-launcher__section">
                    <p class="fb-launcher__label">Administração</p>
                    <div class="fb-launcher__grid fb-launcher__grid--admin">
                        <?php if ($ehAdmin): ?>
                            <?php $launcherAdminTile('restaurantes/index', 'bi-building', 'Restaurantes'); ?>
                            <?php $launcherAdminTile('portas/index', 'bi-door-open', 'Portas'); ?>
                            <?php $launcherAdminTile('operacoes/index', 'bi-collection', 'Operações'); ?>
                            <?php $launcherAdminTile('horarios/index', 'bi-clock', 'Horários'); ?>
                        <?php endif; ?>
                        <?php $launcherAdminTile('usuarios/index', 'bi-people', 'Usuários'); ?>
                        <?php $launcherAdminTile('auditoria/index', 'bi-shield-check', 'Auditoria'); ?>
                        <?php if ($ehAdmin): ?>
                            <?php $launcherAdminTile('lgpd/index', 'bi-shield-lock', 'LGPD'); ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="fb-launcher__utility">
            <?php if ($showGuidedTutorial): ?>
                <button class="fb-btn fb-btn--ghost js-open-tour" type="button" data-bs-dismiss="offcanvas"><i class="bi bi-question-circle"></i> Guia</button>
            <?php endif; ?>
            <?php if ($perfilLauncher === 'admin'): ?>
                <form method="post" action="/?r=demo/toggle" class="logout-inline-form d-inline-flex">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="return_to" value="<?= h(sanitize_local_redirect_path((string)($_SERVER['REQUEST_URI'] ?? '/?r=auth/login'))) ?>">
                    <input type="hidden" name="demo_mode" value="<?= app_demo_mode_enabled() ? '0' : '1' ?>">
                    <button class="fb-btn fb-btn--ghost" type="submit"><i class="bi bi-mortarboard"></i> Modo demo<?= app_demo_mode_enabled() ? ' (ativo)' : '' ?></button>
                </form>
            <?php endif; ?>
            <form method="post" action="/?r=auth/logout" class="logout-inline-form">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <button class="fb-btn fb-btn--ghost" type="submit" style="color: var(--fb-danger);"><i class="bi bi-box-arrow-right"></i> Sair</button>
            </form>
            <div class="theme-switch theme-switch-compact" role="group" aria-label="Selecionar tema">
                <?php require __DIR__ . '/theme_switch_buttons.php'; ?>
            </div>
        </div>
    </div>
</div>
