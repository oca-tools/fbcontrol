<aside class="sidebar">
    <div class="sidebar-top">
    <div class="mb-4 brand-block">
        <?php if (!empty($logoPath)): ?>
            <img src="<?= h($logoPath) ?>?v=20260705g" data-logo-light="<?= h($logoPath) ?>?v=20260705g" data-logo-dark="/assets/logo-fbcontrol-dark.svg?v=20260705g" alt="Logo do FBControl" class="brand-logo js-theme-logo">
        <?php endif; ?>
        <div class="brand-badge mx-auto">
            <i class="bi bi-stars"></i>
            Versão <?= h($appVersion) ?>
        </div>
    </div>
    <div class="sidebar-user-card">
        <div class="d-flex align-items-center gap-2">
            <?php $safeProfilePhoto = safe_public_upload_url((string)($user['foto_path'] ?? ''), 'profiles'); ?>
            <?php if ($safeProfilePhoto !== ''): ?>
                <img src="<?= h($safeProfilePhoto) ?>" alt="Foto do usuário" class="sidebar-user-avatar">
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
        <div class="sidebar-section-label">Operação</div>
        <?php if (in_array($user['perfil'], ['admin', 'hostess', 'supervisor', 'gerente'], true)): ?>
            <a <?= $navAttrs('access/index') ?> href="/?r=access/index"><i class="bi bi-clipboard-check"></i> Registro</a>
        <?php endif; ?>
        <?php if ($user['perfil'] === 'hostess'): ?>
            <a <?= $navAttrs('hostess/turnos') ?> href="/?r=hostess/turnos"><i class="bi bi-calendar-week"></i> Meus turnos</a>
        <?php endif; ?>
        <?php if (in_array($user['perfil'], ['admin', 'supervisor', 'hostess', 'gerente'], true)): ?>
            <a <?= $navAttrs('vouchers/index') ?> href="/?r=vouchers/index"><i class="bi bi-ticket-perforated"></i> Vouchers</a>
        <?php endif; ?>
        <?php if ($canTematicas): ?>
            <div class="sidebar-section-label">Temáticos</div>
            <?php if (in_array($user['perfil'], ['admin', 'supervisor', 'gerente'], true) || $canTematicasReserva): ?>
                <a <?= $navAttrs('reservasTematicas/reservas') ?> href="/?r=reservasTematicas/reservas"><i class="bi bi-calendar-heart"></i> Reservas Temáticas</a>
            <?php endif; ?>
            <a <?= $navAttrs('reservasTematicas/operacao') ?> href="/?r=reservasTematicas/operacao"><i class="bi bi-clipboard-data"></i> Operação Temática</a>
            <?php if (in_array($user['perfil'], ['admin', 'supervisor', 'gerente'], true)): ?>
                <a <?= $navAttrs('reservasTematicas/admin') ?> href="/?r=reservasTematicas/admin"><i class="bi bi-sliders"></i> Config. Temáticas</a>
            <?php endif; ?>
        <?php endif; ?>
        <?php if (in_array($user['perfil'], ['admin', 'supervisor', 'gerente'], true)): ?>
            <div class="sidebar-section-label">Gestão e BI</div>
            <a <?= $navAttrs(['operacao/index', 'control/index']) ?> href="/?r=operacao/index"><i class="bi bi-speedometer2"></i> Operação</a>
            <a <?= $navAttrs(['analise/index', 'dashboard/index', 'dashboard/restaurant']) ?> href="/?r=analise/index"><i class="bi bi-bar-chart"></i> Análise</a>
<?php /* KPIs Estratégicos virou a aba KPIs do hub Análise. */ ?>
            <a <?= $navAttrs('relatorios/index') ?> href="/?r=relatorios/index"><i class="bi bi-file-earmark-text"></i> Relatórios</a>
            <?php if (in_array($user['perfil'], ['admin', 'gerente'], true)): ?>
                <a <?= $navAttrs('auditoria/index') ?> href="/?r=auditoria/index"><i class="bi bi-shield-check"></i> Auditoria</a>
            <?php endif; ?>
            <?php if (in_array($user['perfil'], ['admin'], true)): ?>
                <a <?= $navAttrs('lgpd/index') ?> href="/?r=lgpd/index"><i class="bi bi-shield-lock"></i> LGPD</a>
            <?php endif; ?>
<?php /* Relatórios Temáticos virou a aba Temáticos do hub Análise. */ ?>
<?php /* Dashboard do Restaurante saiu do menu: agora é drill-down dos hubs Operação e Análise. */ ?>
            <?php if (in_array($user['perfil'], ['admin', 'gerente'], true)): ?>
                <div class="sidebar-section-label">Administração</div>
            <?php endif; ?>
            <?php if (in_array($user['perfil'], ['admin'], true)): ?>
                <a <?= $navAttrs('restaurantes/index') ?> href="/?r=restaurantes/index"><i class="bi bi-building"></i> Restaurantes</a>
                <a <?= $navAttrs('portas/index') ?> href="/?r=portas/index"><i class="bi bi-door-open"></i> Portas</a>
                <a <?= $navAttrs('operacoes/index') ?> href="/?r=operacoes/index"><i class="bi bi-collection"></i> Operações</a>
                <a <?= $navAttrs('horarios/index') ?> href="/?r=horarios/index"><i class="bi bi-clock"></i> Horários</a>
            <?php endif; ?>
            <?php if (in_array($user['perfil'], ['admin', 'gerente'], true)): ?>
                <a <?= $navAttrs('usuarios/index') ?> href="/?r=usuarios/index"><i class="bi bi-people"></i> Usuários</a>
            <?php endif; ?>
<?php /* E-mail Diário saiu do menu: agora é a seção "Envios automáticos" do hub Relatórios. */ ?>
        <?php endif; ?>
        </nav>
    </div>
</aside>
