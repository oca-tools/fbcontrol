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
    <script>
    (function () {
        try {
            var savedTheme = localStorage.getItem('oca_theme');
            var theme = savedTheme === 'dark' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', theme);
        } catch (e) {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    })();
    </script>
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
            --ab-page-bg: #f8fafc;
            --ab-grad-1: rgba(249,115,22,0.18);
            --ab-grad-2: rgba(251,146,60,0.16);
            --ab-sidebar-bg: #ffffff;
            --ab-hover-bg: rgba(249, 115, 22, 0.12);
            --ab-panel-bg: #ffffff;
            --ab-soft-bg: #f8fafc;
            --ab-input-bg: #ffffff;
            --ab-input-text: #0f172a;
        }
        html[data-theme='dark'] {
            --ab-ink: #e2e8f0;
            --ab-muted: #94a3b8;
            --ab-surface: #0b1220;
            --ab-card: #111827;
            --ab-border: #334155;
            --ab-page-bg: #0b1220;
            --ab-grad-1: rgba(249,115,22,0.1);
            --ab-grad-2: rgba(251,146,60,0.08);
            --ab-sidebar-bg: #0f172a;
            --ab-hover-bg: rgba(249, 115, 22, 0.18);
            --ab-panel-bg: #111827;
            --ab-soft-bg: #0f172a;
            --ab-input-bg: #0b1220;
            --ab-input-text: #e2e8f0;
        }
        body {
            font-family: "Manrope", sans-serif;
            background:
                radial-gradient(1200px 600px at 10% -10%, var(--ab-grad-1), transparent 60%),
                radial-gradient(900px 500px at 90% -20%, var(--ab-grad-2), transparent 60%),
                var(--ab-page-bg);
            color: var(--ab-ink);
            overflow-x: hidden;
        }
        a {
            color: #c2410c;
        }
        html[data-theme='dark'] a {
            color: #fb923c;
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
            color: var(--ab-ink);
        }
        .card-soft {
            background: linear-gradient(180deg, rgba(255,255,255,0.98) 0%, rgba(248,250,252,0.92) 100%);
        }
        html[data-theme='dark'] .card-soft {
            background: linear-gradient(180deg, rgba(17,24,39,0.98) 0%, rgba(15,23,42,0.92) 100%);
        }
        .status-success { color: #22c55e; font-weight: 700; }
        .status-warning { color: #f59e0b; font-weight: 700; }
        .status-danger { color: #ef4444; font-weight: 700; }
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
        .badge-success { background: linear-gradient(135deg, #16a34a, #22c55e) !important; color: #fff !important; box-shadow: 0 6px 14px rgba(34,197,94,0.32); }
        .badge-warning { background: linear-gradient(135deg, #f59e0b, #fbbf24) !important; color: #1f2937 !important; box-shadow: 0 6px 14px rgba(245,158,11,0.32); }
        .badge-danger { background: linear-gradient(135deg, #dc2626, #ef4444) !important; color: #fff !important; box-shadow: 0 6px 14px rgba(239,68,68,0.3); }
        .badge-soft {
            background: #fff;
            color: #9a3412;
            border: 1.5px solid #fdba74;
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
        .tag-rest-corais { background: linear-gradient(135deg, #0891b2, #06b6d4); color: #fff; border-color: transparent; box-shadow: 0 6px 14px rgba(6,182,212,0.28); }
        .tag-rest-giardino { background: linear-gradient(135deg, #16a34a, #22c55e); color: #fff; border-color: transparent; box-shadow: 0 6px 14px rgba(34,197,94,0.3); }
        .tag-rest-brasa { background: linear-gradient(135deg, #ea580c, #f97316); color: #fff; border-color: transparent; box-shadow: 0 6px 14px rgba(249,115,22,0.3); }
        .tag-rest-ixu { background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; border-color: transparent; box-shadow: 0 6px 14px rgba(99,102,241,0.3); }
        .tag-rest-privileged { background: linear-gradient(135deg, #0e7490, #06b6d4); color: #fff; border-color: transparent; box-shadow: 0 6px 14px rgba(14,116,144,0.28); }
        .tag-rest-default { background: linear-gradient(135deg, #64748b, #94a3b8); color: #fff; border-color: transparent; }
        .tag-op-cafe { background: linear-gradient(135deg, #f59e0b, #fbbf24); color: #111827; border-color: transparent; }
        .tag-op-almoco { background: linear-gradient(135deg, #16a34a, #22c55e); color: #fff; border-color: transparent; }
        .tag-op-jantar { background: linear-gradient(135deg, #dc2626, #ef4444); color: #fff; border-color: transparent; }
        .tag-op-tematico { background: linear-gradient(135deg, #4338ca, #6366f1); color: #fff; border-color: transparent; }
        .tag-op-privileged { background: linear-gradient(135deg, #0369a1, #0ea5e9); color: #fff; border-color: transparent; }
        .tag-op-default { background: linear-gradient(135deg, #64748b, #94a3b8); color: #fff; border-color: transparent; }
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
        .uh-bungalow { background: linear-gradient(135deg, #ea580c, #f97316); color: #fff; border-color: transparent; }
        .uh-standard { background: linear-gradient(135deg, #16a34a, #22c55e); color: #fff; border-color: transparent; }
        .uh-family { background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; border-color: transparent; }
        .uh-nova { background: linear-gradient(135deg, #0284c7, #06b6d4); color: #fff; border-color: transparent; }
        .uh-nao-informado { background: linear-gradient(135deg, #ca8a04, #facc15); color: #111827; border-color: transparent; }
        .uh-day-use { background: linear-gradient(135deg, #db2777, #ec4899); color: #fff; border-color: transparent; }
        .uh-default { background: linear-gradient(135deg, #64748b, #94a3b8); color: #fff; border-color: transparent; }
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
            border: 1.5px solid #fdba74;
            background: #fff;
            font-size: 0.85rem;
            font-weight: 600;
            color: #9a3412;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .tag-choice input:checked + label {
            background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);
            border-color: #f97316;
            color: #fff;
            box-shadow: 0 8px 18px rgba(249, 115, 22, 0.28);
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
            background: var(--ab-sidebar-bg);
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
            background: var(--ab-hover-bg);
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
            background: var(--ab-panel-bg);
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
            background: var(--ab-panel-bg);
            border: 1px solid var(--ab-border);
            border-radius: 999px;
            padding: 8px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .turno-pill {
            background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);
            border: none;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.85rem;
            color: #fff;
            box-shadow: 0 8px 20px rgba(249,115,22,0.28);
        }
        .stat-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 10px;
            background: var(--ab-soft-bg);
            border: 1px solid var(--ab-border);
            font-size: 0.85rem;
        }
        .theme-toggle {
            border: 1px solid var(--ab-border);
            background: var(--ab-panel-bg);
            color: var(--ab-ink);
        }
        .theme-toggle:hover {
            border-color: #f97316;
            color: #9a3412;
        }
        .form-control,
        .form-select {
            background-color: var(--ab-input-bg);
            color: var(--ab-input-text);
            border-color: var(--ab-border);
        }
        .form-control:focus,
        .form-select:focus {
            background-color: var(--ab-input-bg);
            color: var(--ab-input-text);
        }
        .offcanvas {
            background: var(--ab-panel-bg);
            color: var(--ab-ink);
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
        .table {
            color: var(--ab-ink);
        }
        .table > :not(caption) > * > * {
            background-color: transparent;
            color: inherit;
            border-color: var(--ab-border);
        }
        .text-muted,
        .text-secondary {
            color: var(--ab-muted) !important;
        }
        .alert {
            border-color: var(--ab-border);
        }
        html[data-theme='dark'] .alert-light,
        html[data-theme='dark'] .bg-light {
            background-color: #1f2937 !important;
            color: var(--ab-ink) !important;
            border-color: var(--ab-border) !important;
        }
        .list-group-item {
            background: var(--ab-card);
            color: var(--ab-ink);
            border-color: var(--ab-border);
        }
        .modal-content {
            background: var(--ab-card);
            color: var(--ab-ink);
            border-color: var(--ab-border);
        }
        .dropdown-menu {
            background: var(--ab-card);
            color: var(--ab-ink);
            border-color: var(--ab-border);
        }
        .dropdown-item {
            color: var(--ab-ink);
        }
        .dropdown-item:hover {
            background: var(--ab-soft-bg);
        }
        .form-control::placeholder,
        textarea::placeholder {
            color: var(--ab-muted);
            opacity: 1;
        }
        .btn-outline-dark {
            border-color: var(--ab-border);
            color: var(--ab-ink);
        }
        .btn-outline-dark:hover {
            background: var(--ab-soft-bg);
            border-color: #f97316;
            color: #9a3412;
        }
        .btn {
            border-radius: 12px;
            font-weight: 600;
            letter-spacing: 0.1px;
            transition: all 0.18s ease;
        }
        .btn i {
            color: inherit !important;
        }
        .btn-sm {
            border-radius: 10px;
        }
        .btn-lg {
            border-radius: 14px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);
            border: none;
            box-shadow: 0 10px 22px rgba(249, 115, 22, 0.28);
            color: #fff !important;
        }
        .btn-primary:hover,
        .btn-primary:focus {
            transform: translateY(-1px);
            box-shadow: 0 14px 26px rgba(249, 115, 22, 0.35);
            background: linear-gradient(135deg, #ea580c 0%, #f97316 100%);
            color: #fff !important;
        }
        .btn-primary:visited,
        .btn-primary:active {
            color: #fff !important;
        }
        .btn-outline-primary {
            border-width: 1.5px;
            border-color: #fb923c;
            color: #9a3412;
            background: #fff;
        }
        .btn-outline-primary:hover,
        .btn-outline-primary:focus {
            background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);
            border-color: #f97316;
            color: #fff !important;
        }
        html[data-theme='dark'] .btn-outline-primary {
            background: #fff;
            color: #9a3412 !important;
        }
        .btn-secondary,
        .btn-outline-secondary {
            border-radius: 12px;
        }
        .btn-success {
            background: linear-gradient(135deg, #16a34a, #22c55e);
            border: none;
            color: #fff !important;
            box-shadow: 0 10px 22px rgba(34, 197, 94, 0.25);
        }
        .btn-success:hover,
        .btn-success:focus {
            background: linear-gradient(135deg, #15803d, #16a34a);
            color: #fff !important;
        }
        .btn-danger {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            border: none;
            color: #fff !important;
            box-shadow: 0 10px 22px rgba(239, 68, 68, 0.24);
        }
        .btn-danger:hover,
        .btn-danger:focus {
            background: linear-gradient(135deg, #b91c1c, #dc2626);
            color: #fff !important;
        }
        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            border: none;
            color: #fff !important;
            box-shadow: 0 10px 22px rgba(245, 158, 11, 0.24);
        }
        .btn-warning:hover,
        .btn-warning:focus {
            background: linear-gradient(135deg, #d97706, #f59e0b);
            color: #fff !important;
        }
        .btn-info {
            background: linear-gradient(135deg, #0284c7, #0ea5e9);
            border: none;
            color: #fff !important;
            box-shadow: 0 10px 22px rgba(14, 165, 233, 0.23);
        }
        .btn-info:hover,
        .btn-info:focus {
            background: linear-gradient(135deg, #0369a1, #0284c7);
            color: #fff !important;
        }
        .btn-outline-secondary {
            color: var(--ab-ink);
            border-color: var(--ab-border);
            background: transparent;
        }
        .btn-outline-secondary:hover,
        .btn-outline-secondary:focus {
            background: var(--ab-soft-bg);
            color: var(--ab-ink);
            border-color: #94a3b8;
        }
        .card {
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
        }
        .card-header {
            background: linear-gradient(180deg, rgba(249,115,22,0.08), transparent);
            border-bottom: 1px solid var(--ab-border);
            font-weight: 700;
            color: var(--ab-ink);
        }
        html[data-theme='dark'] .card-header {
            background: linear-gradient(180deg, rgba(249,115,22,0.13), rgba(15,23,42,0.2));
        }
        .form-label {
            font-weight: 700;
            font-size: 0.88rem;
            margin-bottom: 0.35rem;
            color: var(--ab-ink);
        }
        .form-text {
            color: var(--ab-muted);
        }
        .form-control,
        .form-select {
            border-radius: 12px;
            min-height: 44px;
            box-shadow: none;
        }
        .form-control:focus,
        .form-select:focus {
            border-color: #fb923c;
            box-shadow: 0 0 0 0.2rem rgba(249, 115, 22, 0.18);
        }
        .input-group-text {
            background: var(--ab-soft-bg);
            color: var(--ab-muted);
            border-color: var(--ab-border);
            border-radius: 12px;
        }
        .table-responsive {
            border: 1px solid var(--ab-border);
            border-radius: 14px;
            background: var(--ab-card);
        }
        .table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: rgba(249,115,22,0.08);
        }
        html[data-theme='dark'] .table thead th {
            background: rgba(249,115,22,0.14);
            color: #fdba74;
        }
        .table-striped > tbody > tr:nth-of-type(odd) > * {
            background: rgba(15,23,42,0.02);
        }
        html[data-theme='dark'] .table-striped > tbody > tr:nth-of-type(odd) > * {
            background: rgba(148,163,184,0.06);
        }
        .badge,
        .tag,
        .uh-badge {
            font-weight: 700;
            letter-spacing: 0.15px;
        }
        .alert {
            border-radius: 12px;
        }
        .nav-tabs {
            border-bottom-color: var(--ab-border);
        }
        .nav-tabs .nav-link {
            border-radius: 10px 10px 0 0;
            color: var(--ab-muted);
            border: 1px solid transparent;
        }
        .nav-tabs .nav-link.active {
            color: #9a3412;
            border-color: var(--ab-border) var(--ab-border) transparent;
            background: var(--ab-card);
            font-weight: 700;
        }
        .pagination .page-link {
            border-color: var(--ab-border);
            color: var(--ab-ink);
            background: var(--ab-card);
        }
        .pagination .page-link:hover {
            background: var(--ab-soft-bg);
        }
        .pagination .active > .page-link {
            background: #f97316;
            border-color: #f97316;
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
        }
    </style>
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
                        <a class="nav-link" href="/?r=emailRelatorios/index"><i class="bi bi-envelope-paper"></i> E-mail Diário</a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>
        </aside>
    <?php endif; ?>
    <div class="app-main">
        <?php if ($user): ?>
            <div class="mobile-nav">
                <div class="brand"><?= h($appName) ?></div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm theme-toggle js-theme-toggle" type="button" title="Alternar tema">
                        <i class="bi bi-moon-stars-fill"></i>
                    </button>
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
                    <button class="btn btn-sm theme-toggle js-theme-toggle" type="button" title="Alternar tema">
                        <i class="bi bi-moon-stars-fill me-1"></i>
                        Tema
                    </button>
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

