<?php
$config = require __DIR__ . '/../../../config/config.php';
$appName = $config['app']['name'];
$appVersion = $config['app']['version'] ?? '1.0';
$logoPath = $config['app']['logo_path'] ?? '';
$user = Auth::user();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($appName) ?></title>
    <?php if (!empty($logoPath)): ?>
        <link rel="icon" type="image/png" href="<?= h($logoPath) ?>">
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --ab-success: #f97316;
            --ab-warning: #f59e0b;
            --ab-danger: #ef4444;
            --ab-ink: #0f172a;
            --ab-muted: #64748b;
            --ab-accent: #f97316;
            --ab-accent-2: #fb923c;
            --ab-surface: #f8fafc;
            --ab-card: #ffffff;
            --ab-border: #e2e8f0;
            --ab-glow: rgba(249, 115, 22, 0.2);
        }
        body {
            font-family: "Manrope", sans-serif;
            background:
                radial-gradient(1200px 600px at 10% -10%, rgba(249,115,22,0.18), transparent 60%),
                radial-gradient(900px 500px at 90% -20%, rgba(251,146,60,0.16), transparent 60%),
                #f8fafc;
            color: var(--ab-ink);
            overflow-x: hidden;
        }
        *, *::before, *::after {
            box-sizing: border-box;
        }
        img {
            max-width: 100%;
            height: auto;
        }
        .brand-title {
            font-family: "Space Grotesk", sans-serif;
            letter-spacing: 0.2px;
        }
        .brand-block {
            text-align: center;
        }
        .brand-logo {
            width: 120px;
            max-width: 100%;
            height: auto;
            margin: 10px auto 0;
            display: block;
        }
        .btn-xl {
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .input-xl {
            padding: 1rem;
            font-size: 1.2rem;
        }
        .form-control,
        .form-select,
        textarea,
        input,
        select {
            font-size: 16px;
        }
        .card {
            border: 1px solid var(--ab-border);
            box-shadow: 0 14px 40px rgba(15, 23, 42, 0.08);
            border-radius: 22px;
            background: var(--ab-card);
        }
        .card-soft {
            background: linear-gradient(180deg, rgba(255,255,255,0.98) 0%, rgba(248,250,252,0.92) 100%);
        }
        .status-success { color: var(--ab-success); }
        .status-warning { color: var(--ab-warning); }
        .status-danger { color: var(--ab-danger); }
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #fff;
            line-height: 1;
        }
        .badge-success { background: var(--ab-success) !important; color: #fff !important; }
        .badge-warning { background: var(--ab-warning) !important; color: #111827 !important; }
        .badge-danger { background: var(--ab-danger) !important; color: #fff !important; }
        .badge-soft {
            background: #f1f5f9;
            color: #b94700;
            border: 1px solid var(--ab-border);
        }
        .tag {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid transparent;
            line-height: 1;
            white-space: nowrap;
        }
        .tag-rest-corais { background: rgba(14, 116, 144, 0.12); color: #0e7490; border-color: rgba(14, 116, 144, 0.28); }
        .tag-rest-giardino { background: rgba(16, 185, 129, 0.12); color: #0f766e; border-color: rgba(16, 185, 129, 0.28); }
        .tag-rest-brasa { background: rgba(217, 119, 6, 0.12); color: #b45309; border-color: rgba(217, 119, 6, 0.28); }
        .tag-rest-ixu { background: rgba(99, 102, 241, 0.12); color: #4f46e5; border-color: rgba(99, 102, 241, 0.28); }
        .tag-rest-privileged { background: rgba(2, 132, 199, 0.12); color: #0369a1; border-color: rgba(2, 132, 199, 0.28); }
        .tag-rest-default { background: rgba(148, 163, 184, 0.18); color: #475569; border-color: rgba(148, 163, 184, 0.28); }
        .tag-op-cafe { background: rgba(251, 191, 36, 0.18); color: #92400e; border-color: rgba(251, 191, 36, 0.35); }
        .tag-op-almoco { background: rgba(34, 197, 94, 0.16); color: #166534; border-color: rgba(34, 197, 94, 0.32); }
        .tag-op-jantar { background: rgba(239, 68, 68, 0.14); color: #991b1b; border-color: rgba(239, 68, 68, 0.3); }
        .tag-op-tematico { background: rgba(59, 130, 246, 0.16); color: #1d4ed8; border-color: rgba(59, 130, 246, 0.32); }
        .tag-op-privileged { background: rgba(2, 132, 199, 0.14); color: #0c4a6e; border-color: rgba(2, 132, 199, 0.3); }
        .tag-op-default { background: rgba(148, 163, 184, 0.18); color: #475569; border-color: rgba(148, 163, 184, 0.28); }
        .uh-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.5rem;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 700;
            border: 1px solid transparent;
            letter-spacing: 0.2px;
        }
        .uh-bungalow { background: rgba(249, 115, 22, 0.14); color: #9a3412; border-color: rgba(249, 115, 22, 0.3); }
        .uh-standard { background: rgba(34, 197, 94, 0.14); color: #166534; border-color: rgba(34, 197, 94, 0.3); }
        .uh-family { background: rgba(99, 102, 241, 0.14); color: #4338ca; border-color: rgba(99, 102, 241, 0.3); }
        .uh-nova { background: rgba(14, 116, 144, 0.14); color: #0f766e; border-color: rgba(14, 116, 144, 0.3); }
        .uh-default { background: rgba(148, 163, 184, 0.18); color: #475569; border-color: rgba(148, 163, 184, 0.28); }
        .tag-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .tag-choice input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .tag-choice label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            border: 1px solid var(--ab-border);
            background: #fff;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--ab-ink);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .tag-choice input:checked + label {
            background: rgba(249, 115, 22, 0.16);
            border-color: rgba(249, 115, 22, 0.45);
            color: #9a3412;
        }
        .table-editor {
            table-layout: auto;
        }
        .table-editor td,
        .table-editor th {
            vertical-align: middle;
        }
        .table-editor input,
        .table-editor select {
            min-width: 180px;
        }
        .table-editor .col-mini {
            min-width: 120px;
        }
        .app-shell {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 280px;
            background: #ffffff;
            color: var(--ab-ink);
            border-right: 1px solid var(--ab-border);
            padding: 24px 20px;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            scrollbar-width: thin;
        }
        .sidebar .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(249, 115, 22, 0.12);
            font-size: 0.8rem;
            color: #9a3412;
        }
        .sidebar .nav-link {
            color: var(--ab-ink) !important;
            border-radius: 12px;
            padding: 10px 14px;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background: rgba(249, 115, 22, 0.12);
            color: #9a3412 !important;
        }
        .sidebar .nav-link i {
            color: #f97316;
        }
        .app-main {
            flex: 1;
            padding: 28px 28px 40px;
            max-width: 100%;
            overflow-x: hidden;
        }
        .mobile-nav {
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: #fff;
            border: 1px solid var(--ab-border);
            border-radius: 14px;
            padding: 10px 14px;
            margin-bottom: 16px;
        }
        .mobile-nav .brand {
            font-family: "Space Grotesk", sans-serif;
            font-weight: 600;
            color: #9a3412;
        }
        .mobile-nav .menu-btn {
            border: 1px solid rgba(249, 115, 22, 0.35);
            color: #9a3412;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .topbar .user-pill {
            background: #fff;
            border: 1px solid var(--ab-border);
            border-radius: 999px;
            padding: 8px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .turno-pill {
            background: rgba(249, 115, 22, 0.16);
            border: 1px solid rgba(249, 115, 22, 0.35);
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.85rem;
            color: #9a3412;
        }
        .stat-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid var(--ab-border);
            font-size: 0.85rem;
        }
        .metric-card {
            position: relative;
            overflow: hidden;
        }
        .metric-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            height: 4px;
            width: 100%;
            background: linear-gradient(90deg, #f97316, #fb923c, #fbbf24);
        }
        .metric-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: rgba(249, 115, 22, 0.16);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #9a3412;
        }
        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title .icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #9a3412;
        }
        .table thead th {
            color: #9a3412;
            font-weight: 600;
        }
        .table tbody tr:hover {
            background: rgba(15, 23, 42, 0.03);
        }
        .btn-primary {
            background: #f97316;
            border-color: #f97316;
        }
        .btn-outline-primary {
            border-color: #f97316;
            color: #9a3412;
        }
        .btn-outline-primary:hover {
            background: #f97316;
            color: #fff;
        }
        .app-content {
            max-width: 1280px;
            margin: 0 auto;
        }
        @media (max-width: 992px) {
            .sidebar {
                display: none;
            }
            .app-main {
                padding: 16px;
            }
            .mobile-nav {
                display: flex;
            }
            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            .topbar .user-pill {
                width: 100%;
                justify-content: space-between;
                flex-wrap: wrap;
            }
            .turno-pill {
                width: 100%;
                justify-content: center;
            }
            .stat-chip {
                font-size: 0.8rem;
            }
            .btn-xl {
                width: 100%;
            }
            .input-xl {
                font-size: 1.05rem;
                padding: 0.9rem;
            }
            .form-control,
            .form-select,
            textarea,
            input,
            select {
                font-size: 16px;
            }
        }
        @media (max-width: 576px) {
            .app-main {
                padding: 12px;
            }
            .card {
                border-radius: 16px;
            }
            .section-title {
                align-items: flex-start;
            }
            .section-title .icon {
                width: 34px;
                height: 34px;
            }
            .metric-icon {
                width: 38px;
                height: 38px;
            }
            .topbar .user-pill {
                gap: 6px;
            }
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            font-size: 0.9rem;
        }
        }    </style>
</head>
<body>
<div class="app-shell">
    <?php if ($user): ?>
        <?php
        $shiftModel = new ShiftModel();
        $activeShift = $shiftModel->getActiveByUser($user['id']);
        $canTematicas = in_array($user['perfil'], ['admin', 'supervisor'], true);
        $canTematicasReserva = false;
        if (!$canTematicas && $user['perfil'] === 'hostess') {
            $userRestaurantModel = new UserRestaurantModel();
            $assignedRests = $userRestaurantModel->byUser($user['id']);
            foreach ($assignedRests as $rest) {
                if (stripos($rest['nome'], 'Corais') !== false) {
                    $canTematicas = true;
                    $canTematicasReserva = true;
                }
                $name = mb_strtolower($rest['nome'], 'UTF-8');
                if (strpos($name, 'giardino') !== false || strpos($name, 'la brasa') !== false || strpos($name, 'ix') !== false || strpos($name, 'ixu') !== false) {
                    $canTematicas = true;
                }
            }
        }
        ?>
        <aside class="sidebar">
            <div class="mb-4 brand-block">
                <div class="text-uppercase text-muted small">Plataforma</div>
                <div class="brand-title h4 mb-1"><?= h($appName) ?></div>
                <div class="brand-badge mx-auto">
                    <i class="bi bi-stars"></i>
                    Versão <?= h($appVersion) ?>
                </div>
                <?php if (!empty($logoPath)): ?>
                    <img src="<?= h($logoPath) ?>" alt="Logo do Resort" class="brand-logo">
                <?php endif; ?>
            </div>
            <?php if ($user['perfil'] === 'hostess'): ?>
                <?php
                $completedTurns = $shiftModel->countCompletedByUser($user['id']);
                $level = 'Bronze';
                if ($completedTurns >= 60) {
                    $level = 'Platina';
                } elseif ($completedTurns >= 30) {
                    $level = 'Ouro';
                } elseif ($completedTurns >= 10) {
                    $level = 'Prata';
                }
                ?>
                <div class="mb-3 p-3 rounded-3" style="background: rgba(249,115,22,0.08); border: 1px solid rgba(249,115,22,0.2);">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <?php if (!empty($user['foto_path'])): ?>
                            <img src="<?= h($user['foto_path']) ?>" alt="Foto" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center bg-light" style="width:40px;height:40px;border-radius:50%;">
                                <i class="bi bi-person"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <div class="fw-semibold"><?= h($user['nome']) ?></div>
                            <div class="text-muted small">Hostess</div>
                        </div>
                    </div>
                    <div class="d-flex flex-column gap-1 small text-muted">
                        <span><i class="bi bi-award me-1"></i>Nível <?= h($level) ?></span>
                        <span><i class="bi bi-flag me-1"></i><?= (int)$completedTurns ?> turnos concluídos</span>
                    </div>
                </div>
            <?php endif; ?>
            <nav class="nav flex-column">
                <?php if (in_array($user['perfil'], ['hostess'], true)): ?>
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
                    <?php if (in_array($user['perfil'], ['admin'], true)): ?>
                        <a class="nav-link" href="/?r=reservasTematicas/admin"><i class="bi bi-sliders"></i> Config. Temáticas</a>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (in_array($user['perfil'], ['admin', 'supervisor', 'gerente'], true)): ?>
                    <a class="nav-link" href="/?r=control/index"><i class="bi bi-speedometer2"></i> Centro de Controle</a>
                    <a class="nav-link" href="/?r=dashboard/index"><i class="bi bi-bar-chart"></i> Dashboard Geral</a>
                    <a class="nav-link" href="/?r=relatorios/index"><i class="bi bi-file-earmark-text"></i> Relatórios</a>
                    <?php if (in_array($user['perfil'], ['admin', 'supervisor'], true)): ?>
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
                    <?php endif; ?>
                <?php endif; ?>
            </nav>
        </aside>
    <?php endif; ?>
    <div class="app-main">
        <?php if ($user): ?>
            <div class="mobile-nav">
                <div class="brand"><?= h($appName) ?></div>
                <button class="btn btn-sm menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu">
                    <i class="bi bi-list"></i> Menu
                </button>
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
                    <div class="nav flex-column gap-1">
                        <?php if (in_array($user['perfil'], ['hostess'], true)): ?>
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
                            <?php if (in_array($user['perfil'], ['admin'], true)): ?>
                                <a class="nav-link" href="/?r=reservasTematicas/admin"><i class="bi bi-sliders"></i> Config. Temáticas</a>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if (in_array($user['perfil'], ['admin', 'supervisor', 'gerente'], true)): ?>
                            <a class="nav-link" href="/?r=control/index"><i class="bi bi-speedometer2"></i> Centro de Controle</a>
                            <a class="nav-link" href="/?r=dashboard/index"><i class="bi bi-bar-chart"></i> Dashboard Geral</a>
                            <a class="nav-link" href="/?r=relatorios/index"><i class="bi bi-file-earmark-text"></i> Relatórios</a>
                            <?php if (in_array($user['perfil'], ['admin', 'supervisor'], true)): ?>
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
                        <a class="nav-link text-danger" href="/?r=auth/logout"><i class="bi bi-box-arrow-right"></i> Sair</a>
                    </div>
                </div>
            </div>
            <div class="topbar">
                <div>
                    <div class="text-muted small">Grand Oca Maragogi Resort</div>
                    <div class="h5 mb-0"><?= h($appName) ?> <span class="text-muted small">v<?= h($appVersion) ?></span></div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <?php if ($activeShift): ?>
                        <div class="turno-pill">
                            <i class="bi bi-clock-history"></i>
                            Turno: <?= h($activeShift['restaurante']) ?> - <?= h($activeShift['operacao']) ?>
                        </div>
                        <div class="stat-chip"><i class="bi bi-shop-window"></i><?= h($activeShift['restaurante']) ?></div>
                        <div class="stat-chip"><i class="bi bi-collection"></i><?= h($activeShift['operacao']) ?></div>
                        <?php if (!empty($activeShift['porta'])): ?>
                            <div class="stat-chip"><i class="bi bi-door-open"></i><?= h($activeShift['porta']) ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div class="user-pill">
                        <i class="bi bi-person-circle"></i>
                        <div>
                            <div class="fw-semibold"><?= h($user['nome']) ?></div>
                            <div class="text-muted small"><?= h($user['perfil']) ?></div>
                        </div>
                        <a class="btn btn-outline-dark btn-sm" href="/?r=auth/logout">Sair</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <main class="app-content pb-4">

