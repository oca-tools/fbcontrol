<?php
/**
 * Barra de navegação superior (shell novo, revisão de marca Oca — desktop ≥993px).
 * Substitui a sidebar: marca + abas primárias por perfil + cluster do usuário.
 * Os destinos secundários/administrativos continuam no offcanvas #mobileMenu (mobile_nav.php),
 * aberto aqui pelo botão "Menu" — evita reconstruir a árvore de navegação inteira.
 */
$perfilTopnav = (string)($user['perfil'] ?? '');
$abasPrimarias = [];
if (in_array($perfilTopnav, ['admin', 'supervisor', 'gerente'], true)) {
    $abasPrimarias = [
        ['rota' => 'operacao/index', 'match' => ['operacao/index', 'control/index'], 'icone' => 'bi-speedometer2', 'label' => 'Operação'],
        ['rota' => 'analise/index', 'match' => ['analise/index', 'dashboard/index', 'dashboard/restaurant'], 'icone' => 'bi-bar-chart', 'label' => 'Análise'],
        ['rota' => 'relatorios/index', 'match' => ['relatorios/index', 'relatorios/consulta'], 'icone' => 'bi-file-earmark-text', 'label' => 'Relatórios'],
    ];
} else {
    $abasPrimarias = [
        ['rota' => 'access/index', 'match' => ['access/index'], 'icone' => 'bi-clipboard-check', 'label' => 'Registro'],
        ['rota' => 'vouchers/index', 'match' => ['vouchers/index'], 'icone' => 'bi-ticket-perforated', 'label' => 'Vouchers'],
    ];
    if (!empty($canTematicas)) {
        $abasPrimarias[] = ['rota' => 'reservasTematicas/operacao', 'match' => ['reservasTematicas/operacao', 'reservasTematicas/reservas', 'reservasTematicas/conferencia'], 'icone' => 'bi-calendar-heart', 'label' => 'Temáticos'];
    } else {
        $abasPrimarias[] = ['rota' => 'hostess/turnos', 'match' => ['hostess/turnos'], 'icone' => 'bi-calendar-week', 'label' => 'Meus turnos'];
    }
}
$fotoTopnav = safe_public_upload_url((string)($user['foto_path'] ?? ''), 'profiles');
$iniciaisTopnav = mb_strtoupper(mb_substr(trim((string)($user['nome'] ?? '?')), 0, 1, 'UTF-8'), 'UTF-8');
?>
<nav class="fb-topnav" aria-label="Navegação principal">
    <div class="fb-topnav__inner">
        <div class="fb-topnav__left">
            <a class="fb-topnav__brand" href="/?r=home" aria-label="FBControl — início">
                <?php if (!empty($logoPath)): ?>
                    <img src="<?= h($logoPath) ?>?v=20260705g" data-logo-light="<?= h($logoPath) ?>?v=20260705g" data-logo-dark="/assets/logo-fbcontrol-dark.svg?v=20260705g" alt="FBControl" class="fb-topnav__logo js-theme-logo">
                <?php else: ?>
                    <span class="fb-topnav__wordmark">FB<span>Control</span></span>
                <?php endif; ?>
            </a>
            <div class="fb-topnav__tabs">
                <?php foreach ($abasPrimarias as $aba): ?>
                    <?php $ativa = $navIsActive($aba['match']); ?>
                    <a class="fb-topnav__tab<?= $ativa ? ' is-active' : '' ?>" href="/?r=<?= h($aba['rota']) ?>"<?= $ativa ? ' aria-current="page"' : '' ?>>
                        <i class="bi <?= h($aba['icone']) ?>"></i><span><?= h($aba['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="fb-topnav__right">
            <?php if (!empty($showGuidedTutorial)): ?>
                <button class="fb-topnav__icon js-open-tour" type="button" title="Guia" aria-label="Abrir guia"><i class="bi bi-question-circle"></i></button>
            <?php endif; ?>
            <div class="fb-topnav__user">
                <?php if ($fotoTopnav !== ''): ?>
                    <img src="<?= h($fotoTopnav) ?>" alt="" class="fb-topnav__avatar">
                <?php else: ?>
                    <span class="fb-topnav__avatar fb-topnav__avatar--fallback"><?= h($iniciaisTopnav) ?></span>
                <?php endif; ?>
                <span class="fb-topnav__username"><?= h((string)($user['nome'] ?? '')) ?></span>
            </div>
            <button class="fb-topnav__menu" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu">
                <i class="bi bi-grid-3x3-gap"></i> Tudo
            </button>
            <form method="post" action="/?r=auth/logout" class="fb-topnav__logout">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <button class="fb-topnav__icon" type="submit" title="Sair" aria-label="Sair"><i class="bi bi-box-arrow-right"></i></button>
            </form>
        </div>
    </div>
</nav>
