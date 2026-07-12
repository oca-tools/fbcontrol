<?php
/**
 * Navegação inferior fixa (mobile, < 992px). Zona do polegar: 3 destinos por perfil
 * + "Mais", que abre o menu completo (offcanvas #mobileMenu do mobile_nav).
 * Renderiza apenas para usuários autenticados; escondida no desktop via d-lg-none.
 */
if (!empty($user)):
    $perfilBottomNav = (string)($user['perfil'] ?? '');
    if ($perfilBottomNav === 'hostess') {
        $bottomNavItems = [
            ['route' => 'access/index', 'icon' => 'bi-clipboard-check', 'label' => 'Registro'],
        ];
        if (!empty($canTematicas)) {
            $bottomNavItems[] = ['route' => 'vouchers/index', 'icon' => 'bi-ticket-perforated', 'label' => 'Vouchers'];
            $bottomNavItems[] = ['route' => 'reservasTematicas/operacao', 'icon' => 'bi-calendar-heart', 'label' => 'Temáticos'];
        } else {
            $bottomNavItems[] = ['route' => 'hostess/turnos', 'icon' => 'bi-calendar-week', 'label' => 'Meus turnos'];
            $bottomNavItems[] = ['route' => 'vouchers/index', 'icon' => 'bi-ticket-perforated', 'label' => 'Vouchers'];
        }
    } else {
        $bottomNavItems = [
            ['route' => 'operacao/index', 'icon' => 'bi-speedometer2', 'label' => 'Operação'],
            ['route' => 'analise/index', 'icon' => 'bi-bar-chart', 'label' => 'Análise'],
            ['route' => 'relatorios/index', 'icon' => 'bi-file-earmark-text', 'label' => 'Relatórios'],
        ];
    }
?>
<div class="fb-bottomnav-spacer" aria-hidden="true"></div>
<nav class="fb-bottomnav" aria-label="Navegação principal">
    <?php foreach ($bottomNavItems as $bottomNavItem): ?>
        <a class="fb-bottomnav__item<?= ($currentRoute ?? '') === $bottomNavItem['route'] ? ' fb-bottomnav__item--active' : '' ?>" href="/?r=<?= h($bottomNavItem['route']) ?>">
            <i class="bi <?= h($bottomNavItem['icon']) ?>"></i><?= h($bottomNavItem['label']) ?>
        </a>
    <?php endforeach; ?>
    <button class="fb-bottomnav__item" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu">
        <i class="bi bi-grid-3x3-gap"></i>Tudo
    </button>
</nav>
<?php endif; ?>
