<aside class="sidebar">
    <div class="sidebar-top">
    <div class="mb-4 brand-block">
        <?php if (!empty($logoPath)): ?>
            <img src="<?= h($logoPath) ?>" data-logo-light="<?= h($logoPath) ?>" data-logo-dark="/assets/logo-fbcontrol-dark.svg" alt="Logo do FBControl" class="brand-logo js-theme-logo">
        <?php endif; ?>
        <div class="brand-badge mx-auto">
            <i class="bi bi-stars"></i>
            Versão <?= h($appVersion) ?>
        </div>
    </div>
    <div class="sidebar-user-card">
        <div class="d-flex align-items-center gap-2">
            <?php if (!empty($user['foto_path'])): ?>
                <img src="<?= h($user['foto_path']) ?>" alt="Foto do usuário" class="sidebar-user-avatar">
            <?php else: ?>
                <div class="sidebar-user-fallback">
                    <i class="bi bi-person"></i>
                </div>
            <?php endif; ?>
            <div>
                <div class="fw-semibold"><?= h($user['nome']) ?></div>
                <div class="sidebar-user-role"><?= h($perfilLabel) ?></div>
            </div>
        </div>
        <?php if ($perfilAtual === 'hostess'): ?>
            <div class="sidebar-user-extra">
                <span><i class="bi bi-award me-1"></i>Nível <?= h((string)$level) ?></span>
                <span><i class="bi bi-flag me-1"></i><?= (int)$completedTurns ?> turnos concluídos</span>
            </div>
        <?php endif; ?>
    </div>
    </div>
    <div class="sidebar-menu">
        <nav class="nav flex-column">
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
                <hr class="text-white-50">
                <a class="nav-link" href="/?r=restaurantes/index"><i class="bi bi-building"></i> Restaurantes</a>
                <a class="nav-link" href="/?r=portas/index"><i class="bi bi-door-open"></i> Portas</a>
                <a class="nav-link" href="/?r=operacoes/index"><i class="bi bi-collection"></i> Operações</a>
                <a class="nav-link" href="/?r=horarios/index"><i class="bi bi-clock"></i> Horários</a>
                <a class="nav-link" href="/?r=usuarios/index"><i class="bi bi-people"></i> Usuários</a>
                <a class="nav-link" href="/?r=emailRelatorios/index"><i class="bi bi-envelope-paper"></i> E-mail Diário</a>
            <?php endif; ?>
        <?php endif; ?>
        </nav>
    </div>
</aside>
