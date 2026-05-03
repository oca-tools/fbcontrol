<div class="mobile-nav">
    <div class="brand">
        <span class="brand-main"><?= h($appName) ?></span>
        <span class="brand-sub"><?= h($perfilLabel ?? ucfirst((string)($user['perfil'] ?? ''))) ?></span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?php if ($showGuidedTutorial): ?>
            <button class="btn btn-sm btn-outline-primary js-open-tour" type="button" title="Abrir guia">
                <i class="bi bi-question-circle"></i>
            </button>
        <?php endif; ?>
        <form method="post" action="/?r=auth/logout" class="logout-inline-form d-inline-flex">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <button class="btn btn-sm btn-outline-dark" type="submit" aria-label="Sair">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </form>
        <button class="btn btn-sm menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu">
            <i class="bi bi-list"></i> Menu
        </button>
    </div>
</div>
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="mobileMenuLabel"><?= h($appName) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
    </div>
    <div class="offcanvas-body">
        <?php if ($user['perfil'] === 'hostess'): ?>
            <div class="d-flex align-items-center gap-2 mb-3">
                <?php if (!empty($user['foto_path'])): ?>
                    <img src="<?= h($user['foto_path']) ?>" alt="Foto" style="width:44px;height:44px;border-radius:50%;object-fit:cover;">
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center bg-light" style="width:44px;height:44px;border-radius:50%;">
                        <i class="bi bi-person"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <div class="fw-semibold"><?= h($user['nome']) ?></div>
                    <div class="text-muted small">Hostess</div>
                </div>
            </div>
        <?php endif; ?>
        <div class="mb-3 mobile-theme-panel">
            <div class="text-muted small mb-2">Tema visual</div>
            <div class="theme-switch theme-switch-compact" role="group" aria-label="Selecionar tema">
                <?php require __DIR__ . '/theme_switch_buttons.php'; ?>
            </div>
        </div>
        <div class="nav flex-column gap-1">
            <?php if (in_array($user['perfil'], ['admin', 'hostess', 'supervisor'], true)): ?>
                <a class="nav-link" href="/?r=access/index"><i class="bi bi-clipboard-check"></i> Registro</a>
            <?php endif; ?>
            <?php if ($user['perfil'] === 'hostess'): ?>
                <a class="nav-link" href="/?r=hostess/turnos"><i class="bi bi-calendar-week"></i> Meus turnos</a>
            <?php endif; ?>
            <?php if (in_array($user['perfil'], ['admin', 'supervisor', 'hostess'], true)): ?>
                <a class="nav-link" href="/?r=vouchers/index"><i class="bi bi-ticket-perforated"></i> Vouchers</a>
            <?php endif; ?>
            <?php if ($canTematicas): ?>
                <?php if (in_array($user['perfil'], ['admin', 'supervisor'], true) || $canTematicasReserva): ?>
                    <a class="nav-link" href="/?r=reservasTematicas/reservas"><i class="bi bi-calendar-heart"></i> Reservas Temáticas</a>
                <?php endif; ?>
                <a class="nav-link" href="/?r=reservasTematicas/operacao"><i class="bi bi-clipboard-data"></i> Operação Temática</a>
                <?php if (in_array($user['perfil'], ['admin', 'supervisor'], true)): ?>
                    <a class="nav-link" href="/?r=reservasTematicas/admin"><i class="bi bi-sliders"></i> Config. Temáticas</a>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (in_array($user['perfil'], ['admin', 'supervisor', 'gerente'], true)): ?>
                <a class="nav-link" href="/?r=control/index"><i class="bi bi-speedometer2"></i> Centro de Controle</a>
                <a class="nav-link" href="/?r=dashboard/index"><i class="bi bi-bar-chart"></i> Dashboard Geral</a>
                <?php if (in_array($user['perfil'], ['admin', 'gerente'], true)): ?>
                    <a class="nav-link" href="/?r=kpis/index"><i class="bi bi-graph-up-arrow"></i> KPIs Estratégicos</a>
                <?php endif; ?>
                <a class="nav-link" href="/?r=relatorios/index"><i class="bi bi-file-earmark-text"></i> Relatórios</a>
                <?php if (in_array($user['perfil'], ['admin'], true)): ?>
                    <a class="nav-link" href="/?r=auditoria/index"><i class="bi bi-shield-check"></i> Auditoria</a>
                <?php endif; ?>
                <?php if (in_array($user['perfil'], ['admin'], true)): ?>
                    <a class="nav-link" href="/?r=lgpd/index"><i class="bi bi-shield-lock"></i> LGPD</a>
                <?php endif; ?>
                <?php if (in_array($user['perfil'], ['admin', 'supervisor', 'gerente'], true)): ?>
                    <a class="nav-link" href="/?r=relatoriosTematicos/index"><i class="bi bi-clipboard-data"></i> Relatórios Temáticos</a>
                <?php endif; ?>
                <?php if ($activeShift): ?>
                    <a class="nav-link" href="/?r=dashboard/restaurant&id=<?= (int)$activeShift['restaurante_id'] ?>"><i class="bi bi-shop-window"></i> Dashboard do Restaurante</a>
                <?php endif; ?>
                <?php if (in_array($user['perfil'], ['admin'], true)): ?>
                    <hr>
                    <a class="nav-link" href="/?r=restaurantes/index"><i class="bi bi-building"></i> Restaurantes</a>
                    <a class="nav-link" href="/?r=portas/index"><i class="bi bi-door-open"></i> Portas</a>
                    <a class="nav-link" href="/?r=operacoes/index"><i class="bi bi-collection"></i> Operações</a>
                    <a class="nav-link" href="/?r=horarios/index"><i class="bi bi-clock"></i> Horários</a>
                    <a class="nav-link" href="/?r=usuarios/index"><i class="bi bi-people"></i> Usuários</a>
                <?php endif; ?>
            <?php endif; ?>
            <form method="post" action="/?r=auth/logout" class="logout-inline-form">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <button class="nav-link text-danger logout-link-btn" type="submit"><i class="bi bi-box-arrow-right"></i> Sair</button>
            </form>
        </div>
    </div>
</div>
