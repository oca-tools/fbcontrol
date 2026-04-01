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
            --ab-primary: #f97316;
            --ab-accent: #f97316;
            --ab-accent-2: #fb923c;
            --ab-surface: #f8fafc;
            --ab-card: #ffffff;
            --ab-border: #e2e8f0;
            --ab-glow: rgba(249, 115, 22, 0.2);
            --ab-page-bg: #f8fafc;
            --ab-bg: #f8fafc;
            --ab-grad-1: rgba(249,115,22,0.18);
            --ab-grad-2: rgba(251,146,60,0.16);
            --ab-sidebar-bg: #ffffff;
            --ab-hover-bg: rgba(249, 115, 22, 0.12);
            --ab-panel-bg: #ffffff;
            --ab-soft-bg: #f8fafc;
            --ab-input-bg: #ffffff;
            --ab-input-text: #0f172a;
            --ab-shadow-card: 0 18px 44px rgba(15, 23, 42, 0.08);
            --ab-shadow-soft: 0 8px 24px rgba(15, 23, 42, 0.06);
            --ab-ring: 0 0 0 0.22rem rgba(249, 115, 22, 0.18);
        }
        html[data-theme='dark'] {
            --ab-ink: #edf4ff;
            --ab-muted: #a8b5c8;
            --ab-surface: #121b2e;
            --ab-card: #172338;
            --ab-border: #324561;
            --ab-page-bg: #0f1a2c;
            --ab-bg: #0f1a2c;
            --ab-grad-1: rgba(249,115,22,0.14);
            --ab-grad-2: rgba(251,146,60,0.1);
            --ab-sidebar-bg: #131f34;
            --ab-hover-bg: rgba(249, 115, 22, 0.18);
            --ab-panel-bg: #18263b;
            --ab-soft-bg: #1c2d46;
            --ab-input-bg: #122038;
            --ab-input-text: #edf4ff;
            --ab-shadow-card: 0 22px 48px rgba(2, 6, 23, 0.5);
            --ab-shadow-soft: 0 10px 28px rgba(2, 6, 23, 0.34);
            --ab-ring: 0 0 0 0.22rem rgba(251, 146, 60, 0.24);
        }
        body {
            font-family: "Manrope", sans-serif;
            background:
                radial-gradient(1200px 600px at 10% -10%, var(--ab-grad-1), transparent 60%),
                radial-gradient(900px 500px at 90% -20%, var(--ab-grad-2), transparent 60%),
                var(--ab-page-bg);
            color: var(--ab-ink);
            overflow-x: hidden;
            line-height: 1.42;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
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
            box-shadow: var(--ab-shadow-card);
            border-radius: 24px;
            background: var(--ab-card);
            color: var(--ab-ink);
            backdrop-filter: saturate(120%) blur(6px);
            transition: transform .18s ease, box-shadow .22s ease, border-color .2s ease;
        }
        .card:hover {
            transform: translateY(-1px);
            box-shadow: 0 22px 52px rgba(15, 23, 42, 0.11);
        }
        .card-soft {
            background: linear-gradient(
                180deg,
                color-mix(in srgb, var(--ab-card) 96%, #ffffff 4%) 0%,
                color-mix(in srgb, var(--ab-soft-bg) 92%, #ffffff 8%) 100%
            );
        }
        html[data-theme='dark'] .card-soft {
            background: linear-gradient(
                180deg,
                color-mix(in srgb, var(--ab-card) 95%, #1f2a3d 5%) 0%,
                color-mix(in srgb, var(--ab-soft-bg) 93%, #152138 7%) 100%
            );
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
        html[data-theme='dark'] .badge-soft {
            background: color-mix(in srgb, var(--ab-soft-bg) 86%, #152138 14%);
            color: #fdba74;
            border-color: rgba(251, 146, 60, 0.5);
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
        html[data-theme='dark'] .tag-choice label {
            background: color-mix(in srgb, var(--ab-soft-bg) 88%, #16253d 12%);
            color: #fdba74;
            border-color: rgba(251, 146, 60, 0.45);
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
            height: 100dvh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 12px;
            box-shadow: var(--ab-shadow-soft);
        }
        .sidebar-top {
            flex: 0 0 auto;
            padding-bottom: 8px;
            border-bottom: 1px solid color-mix(in srgb, var(--ab-border) 82%, transparent);
        }
        .sidebar-user-card {
            margin-top: 6px;
            padding: 12px;
            border-radius: 14px;
            background: color-mix(in srgb, var(--ab-soft-bg) 86%, white 14%);
            border: 1px solid color-mix(in srgb, var(--ab-border) 78%, white 22%);
        }
        html[data-theme='dark'] .sidebar-user-card {
            background: color-mix(in srgb, var(--ab-soft-bg) 90%, #0f172a 10%);
            border-color: color-mix(in srgb, var(--ab-border) 92%, #0f172a 8%);
        }
        .sidebar-user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            object-fit: cover;
        }
        .sidebar-user-fallback {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(249,115,22,0.12);
            color: #c2410c;
        }
        .sidebar-user-role {
            color: var(--ab-muted);
            font-size: 0.78rem;
        }
        .sidebar-user-extra {
            display: grid;
            gap: 4px;
            margin-top: 8px;
            color: var(--ab-muted);
            font-size: 0.78rem;
        }
        .sidebar-menu {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding-top: 14px;
            padding-right: 4px;
            margin-right: -4px;
            scrollbar-width: thin;
            scrollbar-color: rgba(249,115,22,.45) transparent;
        }
        .sidebar-menu::-webkit-scrollbar {
            width: 8px;
        }
        .sidebar-menu::-webkit-scrollbar-track {
            background: transparent;
            border-radius: 999px;
        }
        .sidebar-menu::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, rgba(251,146,60,.7), rgba(249,115,22,.45));
            border-radius: 999px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }
        .sidebar-menu::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, rgba(251,146,60,.92), rgba(249,115,22,.72));
            background-clip: padding-box;
        }
        .sidebar-menu .nav {
            padding-bottom: 8px;
        }
        .sidebar-menu::before,
        .sidebar-menu::after {
            display: none;
        }
        .sidebar-menu .nav-link:first-child {
            margin-top: 2px;
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
            border-radius: 14px;
            padding: 11px 14px;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid transparent;
            transition: all .16s ease;
        }
        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background: var(--ab-hover-bg);
            color: #9a3412 !important;
            border-color: rgba(249, 115, 22, 0.24);
            transform: translateX(2px);
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
            box-shadow: var(--ab-shadow-soft);
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
            box-shadow: var(--ab-shadow-soft);
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
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.25);
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
            border-radius: 14px;
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
            background: var(--ab-card);
        }
        .btn-outline-primary:hover,
        .btn-outline-primary:focus {
            background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);
            border-color: #f97316;
            color: #fff !important;
        }
        html[data-theme='dark'] .btn-outline-primary {
            background: rgba(251, 146, 60, 0.08);
            color: #fdba74 !important;
            border-color: #fb923c;
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
            border-radius: 22px;
            overflow: hidden;
            box-shadow: var(--ab-shadow-card);
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
            box-shadow: var(--ab-ring);
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
            border-radius: 12px;
            min-width: 40px;
            text-align: center;
            margin: 0 2px;
        }
        .pagination .page-link:hover {
            background: var(--ab-soft-bg);
            transform: translateY(-1px);
        }
        .pagination .active > .page-link {
            background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);
            border-color: #f97316;
            color: #fff;
            box-shadow: 0 10px 20px rgba(249, 115, 22, 0.28);
        }
        .app-content {
            max-width: 1320px;
            margin: 0 auto;
        }
        /* iOS-like visual refresh (strong overrides) */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            letter-spacing: 0.01em;
        }
        .card,
        .card-soft,
        .table-responsive,
        .topbar .user-pill,
        .mobile-nav {
            border-radius: 20px !important;
        }
        .card,
        .table-responsive,
        .topbar .user-pill,
        .mobile-nav {
            background: color-mix(in srgb, var(--ab-card) 92%, white 8%) !important;
            border: 1px solid color-mix(in srgb, var(--ab-border) 85%, white 15%) !important;
            box-shadow: 0 18px 44px rgba(15, 23, 42, 0.10) !important;
        }
        .topbar {
            position: sticky;
            top: 0;
            z-index: 30;
            padding: 10px 0;
            backdrop-filter: blur(8px);
        }
        html[data-theme='dark'] .card,
        html[data-theme='dark'] .table-responsive,
        html[data-theme='dark'] .topbar .user-pill,
        html[data-theme='dark'] .mobile-nav {
            background: color-mix(in srgb, #111827 92%, #1f2937 8%) !important;
            border-color: #334155 !important;
            box-shadow: 0 18px 44px rgba(2, 6, 23, 0.5) !important;
        }
        .btn {
            border-radius: 16px !important;
            padding-top: 0.58rem;
            padding-bottom: 0.58rem;
        }
        .btn-primary,
        .btn-success,
        .btn-danger,
        .btn-warning,
        .btn-info {
            color: #fff !important;
            text-shadow: 0 1px 0 rgba(0, 0, 0, 0.18);
        }
        .btn-outline-primary {
            backdrop-filter: blur(8px);
            border-width: 1.5px !important;
        }
        .form-control,
        .form-select {
            border-radius: 14px !important;
            min-height: 46px;
            padding-top: 0.55rem;
            padding-bottom: 0.55rem;
        }
        .form-control:focus,
        .form-select:focus {
            box-shadow: 0 0 0 0.24rem rgba(249, 115, 22, 0.16), 0 8px 20px rgba(249, 115, 22, 0.12) !important;
        }
        .metric-card::before {
            height: 5px !important;
            background: linear-gradient(90deg, #f97316, #fb923c, #fdba74) !important;
        }
        .metric-icon {
            border-radius: 16px !important;
            background: linear-gradient(145deg, rgba(249, 115, 22, 0.24), rgba(251, 146, 60, 0.14)) !important;
        }
        .section-title .icon {
            border-radius: 14px !important;
            background: linear-gradient(145deg, rgba(249,115,22,0.22), rgba(251,146,60,0.12)) !important;
            border: 1px solid rgba(249, 115, 22, 0.2);
            box-shadow: 0 8px 16px rgba(249, 115, 22, 0.16);
        }
        .sidebar .nav-link {
            border-radius: 14px !important;
            margin-bottom: 8px;
        }
        .sidebar .nav-link i {
            width: 26px;
            height: 26px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(249, 115, 22, 0.14);
            color: #c2410c !important;
            font-size: 0.9rem;
        }
        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.16);
        }
        .table thead th {
            font-size: 0.78rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .table thead th i {
            font-size: .8rem;
            color: #f97316;
            opacity: .92;
        }
        html[data-theme='dark'] .table thead th i {
            color: #fdba74;
        }
        .table tbody td {
            vertical-align: middle;
        }
        .table tbody tr {
            transition: background .14s ease, transform .12s ease;
        }
        .table tbody tr:hover {
            transform: translateY(-1px);
        }
        .split-pane-layout {
            display: block;
        }
        @media (min-width: 992px) {
            .app-content .row.g-4 {
                --bs-gutter-x: 1rem;
                --bs-gutter-y: 1rem;
            }
            .app-content .row.g-3 {
                --bs-gutter-x: 0.9rem;
                --bs-gutter-y: 0.9rem;
            }
            .app-content .mb-4 {
                margin-bottom: 1rem !important;
            }
            .app-content .mb-3 {
                margin-bottom: 0.75rem !important;
            }
            .app-content .card.p-4 {
                padding: 1.15rem !important;
            }
            .app-content .card.p-3 {
                padding: 0.95rem !important;
            }
        }
        @media (min-width: 1280px) {
            .split-pane-layout {
                display: grid;
                grid-template-columns: minmax(0, 1.42fr) minmax(300px, 0.95fr);
                gap: 12px;
                align-items: start;
            }
            .split-pane-layout > .mb-4 {
                margin-bottom: 0 !important;
            }
            .split-pane-layout > .split-full {
                grid-column: 1 / -1;
            }
            .split-pane-layout > .split-side {
                grid-column: 2;
            }
            .split-pane-layout > .split-side.side-span-2 {
                grid-row: 1 / span 2;
            }
            .split-pane-layout > :not(.split-side):not(.split-full) {
                grid-column: 1;
            }
            .split-pane-layout > .split-side.sticky-side {
                position: sticky;
                top: 74px;
            }
            .split-pane-layout .split-side .col-lg-4 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
        .pagination {
            gap: 4px;
        }
        .pagination .page-link {
            border-radius: 14px !important;
            min-width: 42px !important;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .export-toast-wrap {
            position: fixed;
            inset: 0;
            z-index: 1200;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }
        .export-toast {
            min-width: 320px;
            max-width: min(92vw, 560px);
            background: color-mix(in srgb, var(--ab-card) 92%, white 8%);
            color: var(--ab-ink);
            border: 1px solid color-mix(in srgb, var(--ab-border) 82%, white 18%);
            border-radius: 18px;
            box-shadow: 0 30px 80px rgba(15, 23, 42, 0.34);
            padding: 18px 22px;
            display: flex;
            align-items: center;
            gap: 14px;
            animation: toast-in .26s ease-out;
            pointer-events: auto;
            backdrop-filter: blur(8px);
        }
        .export-toast .ok-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #16a34a, #22c55e);
            color: #fff;
            font-size: 1.2rem;
            animation: pop-check .45s ease-out;
            flex: 0 0 44px;
            box-shadow: 0 10px 24px rgba(34, 197, 94, 0.35);
        }
        .export-toast .txt {
            font-size: 1.02rem;
            font-weight: 600;
            line-height: 1.4rem;
        }
        .app-confirm-wrap {
            position: fixed;
            inset: 0;
            z-index: 1300;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background: rgba(2, 6, 23, 0.46);
            backdrop-filter: blur(4px);
        }
        .app-confirm-wrap.is-open {
            display: flex;
            animation: confirm-fade-in .16s ease-out;
        }
        .app-confirm-card {
            width: min(96vw, 560px);
            background: color-mix(in srgb, var(--ab-card) 94%, white 6%);
            border: 1px solid color-mix(in srgb, var(--ab-border) 84%, white 16%);
            border-radius: 20px;
            box-shadow: 0 32px 88px rgba(2, 6, 23, 0.38);
            padding: 20px 20px 16px;
            transform: translateY(8px) scale(.98);
            animation: confirm-pop-in .2s ease-out forwards;
        }
        .app-confirm-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }
        .app-confirm-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f97316, #fb923c);
            color: #fff;
            box-shadow: 0 12px 26px rgba(249, 115, 22, 0.3);
            flex: 0 0 42px;
        }
        .app-confirm-wrap.danger .app-confirm-icon {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            box-shadow: 0 12px 26px rgba(239, 68, 68, 0.3);
        }
        .app-confirm-title {
            font-weight: 800;
            font-size: 1.02rem;
            color: var(--ab-ink);
        }
        .app-confirm-message {
            color: var(--ab-muted);
            font-size: 0.94rem;
            line-height: 1.42rem;
            margin-bottom: 14px;
        }
        .app-confirm-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        @keyframes confirm-fade-in {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes confirm-pop-in {
            from { opacity: 0; transform: translateY(8px) scale(.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes toast-in {
            from { opacity: 0; transform: translateY(8px) scale(.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes pop-check {
            0% { transform: scale(.4); opacity: 0; }
            60% { transform: scale(1.12); opacity: 1; }
            100% { transform: scale(1); }
        }
        @media (max-width: 992px) {
            .sidebar {
                display: none;
            }
            .app-main {
                padding: 14px;
            }
            .mobile-nav {
                display: flex;
            }
            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
                position: static;
                padding: 0;
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
            .card {
                border-radius: 18px !important;
            }
            .table-responsive {
                border-radius: 12px !important;
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
                padding: 10px;
            }
            body.compact-mobile .btn {
                min-height: 40px;
                font-size: .88rem;
                padding: .42rem .62rem;
            }
            body.compact-mobile .btn-xl {
                font-size: .96rem;
                padding: .7rem .95rem;
            }
            body.compact-mobile .form-control,
            body.compact-mobile .form-select {
                min-height: 40px;
                font-size: .92rem;
                padding: .42rem .62rem;
            }
            body.compact-mobile .card {
                border-radius: 14px !important;
            }
            body.compact-mobile .metric-card {
                padding: .75rem !important;
            }
            body.compact-mobile .metric-icon {
                width: 34px;
                height: 34px;
                border-radius: 10px !important;
                font-size: .92rem;
            }
            body.compact-mobile .section-title .icon {
                width: 30px;
                height: 30px;
                border-radius: 10px !important;
            }
            .card {
                border-radius: 16px;
            }
            .section-title h3,
            .section-title h4,
            .section-title h5 {
                font-size: 1.02rem;
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
                border-radius: 14px;
                padding: 8px 10px;
                width: 100%;
            }
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                font-size: 0.86rem;
            }
            .table thead th,
            .table tbody td {
                white-space: nowrap;
            }
            .export-toast-wrap {
                padding: 14px;
            }
            .export-toast {
                max-width: 100%;
                width: 100%;
                min-width: auto;
            }
        }
        @media (min-width: 993px) {
            .topbar .user-pill.desktop-only {
                display: none !important;
            }
        }

        /* ===== v2.0 - Refino visual iOS-like ===== */
        :root {
            --ab-radius-xl: 22px;
            --ab-radius-lg: 16px;
            --ab-radius-md: 12px;
            --ab-card-border: color-mix(in srgb, #dbe4f0 84%, #ffffff 16%);
            --ab-card-shadow: 0 20px 48px rgba(15, 23, 42, 0.1);
            --ab-card-shadow-soft: 0 12px 30px rgba(15, 23, 42, 0.07);
        }
        [data-theme="dark"] {
            --ab-card-border: color-mix(in srgb, #3f587a 70%, #0f1a2c 30%);
            --ab-card-shadow: 0 24px 52px rgba(2, 6, 23, 0.42);
            --ab-card-shadow-soft: 0 12px 30px rgba(2, 6, 23, 0.32);
        }
        body {
            background:
                radial-gradient(1100px 520px at 8% -8%, color-mix(in srgb, var(--ab-primary) 12%, transparent), transparent 72%),
                radial-gradient(1100px 520px at 100% -10%, color-mix(in srgb, var(--ab-accent-2) 10%, transparent), transparent 74%),
                var(--ab-bg);
            background-attachment: fixed;
        }
        .card,
        .card-soft,
        .metric-card,
        .table-responsive,
        .topbar .user-pill,
        .mobile-nav {
            border-radius: var(--ab-radius-xl) !important;
            border: 1px solid var(--ab-card-border) !important;
            box-shadow: var(--ab-card-shadow-soft) !important;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .card,
        .card-soft,
        .metric-card,
        .table-responsive {
            background: color-mix(in srgb, var(--ab-card) 94%, #ffffff 6%) !important;
        }
        [data-theme="dark"] .card,
        [data-theme="dark"] .card-soft,
        [data-theme="dark"] .metric-card,
        [data-theme="dark"] .table-responsive {
            background: color-mix(in srgb, var(--ab-card) 96%, #22344d 4%) !important;
        }
        .card:hover,
        .metric-card:hover {
            box-shadow: var(--ab-card-shadow) !important;
            transform: translateY(-1px);
        }
        .sidebar {
            border-right: 1px solid var(--ab-card-border);
            box-shadow: 10px 0 34px rgba(15, 23, 42, 0.07);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }
        [data-theme="dark"] .sidebar {
            box-shadow: 8px 0 32px rgba(2, 6, 23, 0.28);
        }
        .sidebar-menu .nav-link {
            border-radius: var(--ab-radius-lg) !important;
            border: 1px solid transparent;
            background: color-mix(in srgb, var(--ab-panel-bg) 88%, transparent);
        }
        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            border-color: color-mix(in srgb, var(--ab-primary) 28%, transparent);
            box-shadow: 0 10px 24px rgba(249, 115, 22, 0.2);
        }
        .topbar {
            border-radius: var(--ab-radius-xl);
            border: 1px solid var(--ab-card-border);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: var(--ab-card-shadow-soft);
            background: color-mix(in srgb, var(--ab-panel-bg) 92%, transparent);
            padding-inline: 14px;
        }
        [data-theme="dark"] .topbar {
            background: color-mix(in srgb, var(--ab-panel-bg) 86%, #0f1a2c 14%);
        }
        .btn {
            border-radius: var(--ab-radius-md);
            font-weight: 700;
            letter-spacing: 0.01em;
        }
        .btn-primary,
        .btn-success,
        .btn-danger,
        .btn-warning,
        .btn-info {
            color: #fff !important;
            text-shadow: 0 1px 0 rgba(0,0,0,0.18);
        }
        .btn-outline-primary {
            border-color: rgba(249, 115, 22, 0.45);
            color: #c2410c !important;
            background: color-mix(in srgb, var(--ab-card) 90%, transparent);
        }
        [data-theme="dark"] .btn-outline-primary {
            color: #fdba74 !important;
            border-color: rgba(251, 146, 60, 0.54);
            background: rgba(251,146,60,0.08);
        }
        .form-control,
        .form-select,
        .input-group-text {
            border-radius: 14px !important;
        }
        .table {
            border-collapse: separate;
            border-spacing: 0 8px;
        }
        .table thead th {
            border-bottom: none !important;
        }
        .table tbody tr {
            background: color-mix(in srgb, var(--ab-panel-bg) 90%, transparent);
            box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--ab-border) 82%, transparent);
        }
        [data-theme="dark"] .table tbody tr {
            background: color-mix(in srgb, var(--ab-soft-bg) 78%, #18263b 22%);
        }
        .table tbody td:first-child {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }
        .table tbody td:last-child {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }
        .section-title .icon {
            box-shadow: inset 0 0 0 1px rgba(249, 115, 22, 0.25), 0 8px 16px rgba(249, 115, 22, 0.16);
        }
        .split-pane-layout {
            row-gap: 14px !important;
        }
        html[data-theme='dark'] .bg-white,
        html[data-theme='dark'] .bg-light,
        html[data-theme='dark'] .bg-body,
        html[data-theme='dark'] .bg-body-secondary,
        html[data-theme='dark'] .bg-body-tertiary,
        html[data-theme='dark'] .bg-light-subtle {
            background-color: var(--ab-panel-bg) !important;
            color: var(--ab-ink) !important;
        }
        html[data-theme='dark'] .table-light > :not(caption) > * > * {
            background-color: color-mix(in srgb, var(--ab-soft-bg) 84%, #122038 16%) !important;
            color: var(--ab-ink) !important;
            border-color: var(--ab-border) !important;
        }
        html[data-theme='dark'] .text-dark,
        html[data-theme='dark'] .text-body,
        html[data-theme='dark'] .text-black {
            color: var(--ab-ink) !important;
        }
        html[data-theme='dark'] .border,
        html[data-theme='dark'] .border-top,
        html[data-theme='dark'] .border-bottom,
        html[data-theme='dark'] .border-start,
        html[data-theme='dark'] .border-end {
            border-color: var(--ab-border) !important;
        }
        html[data-theme='dark'] .offcanvas .nav-link {
            color: var(--ab-ink) !important;
        }

        /* Tabelas: acabamento visual refinado (desktop + mobile) */
        .table-responsive {
            position: relative;
            border-radius: 18px !important;
            border: 1px solid color-mix(in srgb, var(--ab-border) 86%, transparent) !important;
            background: color-mix(in srgb, var(--ab-card) 96%, #ffffff 4%) !important;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06) !important;
            overflow-x: auto;
            overflow-y: visible;
            padding: 2px;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-x: contain;
            scrollbar-gutter: stable both-edges;
        }
        .table-responsive::before,
        .table-responsive::after {
            content: "";
            position: sticky;
            top: 0;
            bottom: 0;
            width: 18px;
            pointer-events: none;
            opacity: 0;
            transition: opacity .2s ease;
            z-index: 5;
            display: block;
        }
        .table-responsive::before {
            left: 0;
            float: left;
            margin-right: -18px;
            background: linear-gradient(90deg, color-mix(in srgb, var(--ab-card) 96%, #ffffff 4%) 0%, rgba(255,255,255,0) 100%);
        }
        .table-responsive::after {
            right: 0;
            float: right;
            margin-left: -18px;
            background: linear-gradient(270deg, color-mix(in srgb, var(--ab-card) 96%, #ffffff 4%) 0%, rgba(255,255,255,0) 100%);
        }
        .table-responsive.is-scrollable:not(.at-start)::before {
            opacity: 1;
        }
        .table-responsive.is-scrollable:not(.at-end)::after {
            opacity: 1;
        }
        .table-responsive::-webkit-scrollbar {
            height: 10px;
        }
        .table-responsive::-webkit-scrollbar-track {
            background: color-mix(in srgb, var(--ab-soft-bg) 68%, transparent);
            border-radius: 999px;
        }
        .table-responsive::-webkit-scrollbar-thumb {
            background: linear-gradient(90deg, rgba(251,146,60,.82), rgba(249,115,22,.72));
            border-radius: 999px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }
        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(90deg, rgba(251,146,60,.96), rgba(249,115,22,.86));
            background-clip: padding-box;
        }
        [data-theme='dark'] .table-responsive {
            background: color-mix(in srgb, var(--ab-card) 95%, #152138 5%) !important;
            box-shadow: 0 12px 28px rgba(2, 6, 23, 0.3) !important;
        }
        [data-theme='dark'] .table-responsive::before {
            background: linear-gradient(90deg, color-mix(in srgb, var(--ab-card) 96%, #152138 4%) 0%, rgba(21,33,56,0) 100%);
        }
        [data-theme='dark'] .table-responsive::after {
            background: linear-gradient(270deg, color-mix(in srgb, var(--ab-card) 96%, #152138 4%) 0%, rgba(21,33,56,0) 100%);
        }
        [data-theme='dark'] .table-responsive::-webkit-scrollbar-track {
            background: color-mix(in srgb, var(--ab-soft-bg) 74%, #0f1a2c 26%);
        }
        .table {
            margin-bottom: 0 !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
            background: transparent;
        }
        .table thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            background: linear-gradient(180deg, rgba(249,115,22,0.12) 0%, rgba(249,115,22,0.07) 100%) !important;
            color: #9a3412 !important;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            border-bottom: 1px solid color-mix(in srgb, var(--ab-border) 80%, transparent) !important;
            padding: 0.74rem 0.78rem !important;
            white-space: nowrap;
        }
        [data-theme='dark'] .table thead th {
            background: linear-gradient(180deg, rgba(251,146,60,0.2) 0%, rgba(251,146,60,0.12) 100%) !important;
            color: #ffd7b0 !important;
            border-bottom-color: color-mix(in srgb, var(--ab-border) 92%, transparent) !important;
        }
        .table tbody td {
            padding: 0.76rem 0.78rem !important;
            border-top: 0 !important;
            border-bottom: 1px solid color-mix(in srgb, var(--ab-border) 74%, transparent) !important;
            vertical-align: middle;
            background: transparent;
        }
        [data-theme='dark'] .table tbody td {
            border-bottom-color: color-mix(in srgb, var(--ab-border) 82%, transparent) !important;
        }
        .table tbody tr:last-child td {
            border-bottom-color: transparent !important;
        }
        .table tbody tr {
            transition: transform .14s ease, box-shadow .14s ease;
        }
        .table tbody tr:hover td {
            background: color-mix(in srgb, rgba(249,115,22,0.1) 65%, transparent) !important;
        }
        [data-theme='dark'] .table tbody tr:hover td {
            background: color-mix(in srgb, rgba(251,146,60,0.17) 52%, rgba(21,33,56,0.74) 48%) !important;
        }
        .table-striped > tbody > tr:nth-of-type(odd) > * {
            background: color-mix(in srgb, var(--ab-soft-bg) 66%, transparent) !important;
        }
        [data-theme='dark'] .table-striped > tbody > tr:nth-of-type(odd) > * {
            background: color-mix(in srgb, var(--ab-soft-bg) 78%, #14233a 22%) !important;
        }
        .table td .btn,
        .table td .form-control,
        .table td .form-select {
            min-height: 36px;
            border-radius: 10px !important;
        }
        .table td .btn-sm {
            padding: 0.32rem 0.58rem;
            font-size: 0.8rem;
        }
        .table .text-muted {
            font-size: 0.85rem;
        }
        .table td:first-child,
        .table th:first-child {
            padding-left: 0.92rem !important;
        }
        .table td:last-child,
        .table th:last-child {
            padding-right: 0.92rem !important;
        }
        @media (max-width: 768px) {
            .table-responsive {
                border-radius: 14px !important;
                padding: 1px;
            }
            .table thead th {
                font-size: 0.68rem;
                padding: 0.6rem 0.62rem !important;
            }
            .table tbody td {
                font-size: 0.84rem;
                padding: 0.62rem !important;
                white-space: nowrap;
            }
        }
        @media (max-width: 992px) {
            .topbar {
                border: 0;
                box-shadow: none;
                background: transparent;
                padding-inline: 0;
            }
            .card,
            .table-responsive,
            .mobile-nav {
                border-radius: 18px !important;
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
        $isHostessTematicoOnly = false;
        if (!$canTematicas && $user['perfil'] === 'hostess') {
            $userRestaurantModel = new UserRestaurantModel();
            $assignedRests = $userRestaurantModel->byUser($user['id']);
            $hasTematico = false;
            $hasRegistroClassico = false;
            foreach ($assignedRests as $rest) {
                $restName = (string)($rest['nome'] ?? '');
                if (stripos($restName, 'Corais') !== false) {
                    $canTematicas = true;
                    $canTematicasReserva = true;
                    $hasRegistroClassico = true;
                }
                $name = mb_strtolower(normalize_mojibake($restName), 'UTF-8');
                $isTematicoRest = (strpos($name, 'giardino') !== false || strpos($name, 'la brasa') !== false || strpos($name, "ix'u") !== false || strpos($name, 'ixu') !== false || strpos($name, 'ix') !== false);
                if ($isTematicoRest) {
                    $canTematicas = true;
                    $hasTematico = true;
                } else {
                    $hasRegistroClassico = true;
                }
            }
            $isHostessTematicoOnly = $hasTematico && !$hasRegistroClassico;
        }
        ?>
        <aside class="sidebar">
            <div class="sidebar-top">
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
            <?php
            $perfilLabelMap = [
                'admin' => 'Administrador',
                'supervisor' => 'Supervisor',
                'gerente' => 'Gerente',
                'hostess' => 'Hostess',
            ];
            $perfilAtual = strtolower((string)($user['perfil'] ?? ''));
            $perfilLabel = $perfilLabelMap[$perfilAtual] ?? ucfirst($perfilAtual);
            $completedTurns = 0;
            $level = null;
            if ($perfilAtual === 'hostess') {
                $completedTurns = $shiftModel->countCompletedByUser($user['id']);
                $level = 'Bronze';
                if ($completedTurns >= 60) {
                    $level = 'Platina';
                } elseif ($completedTurns >= 30) {
                    $level = 'Ouro';
                } elseif ($completedTurns >= 10) {
                    $level = 'Prata';
                }
            }
            ?>
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
                <?php if (in_array($user['perfil'], ['hostess'], true)): ?>
                    <?php if ($isHostessTematicoOnly): ?>
                        <a class="nav-link" href="/?r=reservasTematicas/operacao"><i class="bi bi-clipboard-data"></i> Registro Temático</a>
                    <?php else: ?>
                        <a class="nav-link" href="/?r=access/index"><i class="bi bi-clipboard-check"></i> Registro</a>
                    <?php endif; ?>
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
                    <?php if (!($user['perfil'] === 'hostess' && $isHostessTematicoOnly)): ?>
                        <a class="nav-link" href="/?r=reservasTematicas/operacao"><i class="bi bi-clipboard-data"></i> Operação Temática</a>
                    <?php endif; ?>
                    <?php if (in_array($user['perfil'], ['admin'], true)): ?>
                        <a class="nav-link" href="/?r=reservasTematicas/admin"><i class="bi bi-sliders"></i> Config. Temáticas</a>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (in_array($user['perfil'], ['admin', 'supervisor', 'gerente'], true)): ?>
                    <a class="nav-link" href="/?r=control/index"><i class="bi bi-speedometer2"></i> Centro de Controle</a>
                    <a class="nav-link" href="/?r=dashboard/index"><i class="bi bi-bar-chart"></i> Dashboard Geral</a>
                    <a class="nav-link" href="/?r=kpis/index"><i class="bi bi-graph-up-arrow"></i> KPIs Estratégicos</a>
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
            </div>
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
                            <?php if ($isHostessTematicoOnly): ?>
                                <a class="nav-link" href="/?r=reservasTematicas/operacao"><i class="bi bi-clipboard-data"></i> Registro Temático</a>
                            <?php else: ?>
                                <a class="nav-link" href="/?r=access/index"><i class="bi bi-clipboard-check"></i> Registro</a>
                            <?php endif; ?>
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
                            <?php if (!($user['perfil'] === 'hostess' && $isHostessTematicoOnly)): ?>
                                <a class="nav-link" href="/?r=reservasTematicas/operacao"><i class="bi bi-clipboard-data"></i> Operação Temática</a>
                            <?php endif; ?>
                            <?php if (in_array($user['perfil'], ['admin'], true)): ?>
                                <a class="nav-link" href="/?r=reservasTematicas/admin"><i class="bi bi-sliders"></i> Config. Temáticas</a>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if (in_array($user['perfil'], ['admin', 'supervisor', 'gerente'], true)): ?>
                            <a class="nav-link" href="/?r=control/index"><i class="bi bi-speedometer2"></i> Centro de Controle</a>
                            <a class="nav-link" href="/?r=dashboard/index"><i class="bi bi-bar-chart"></i> Dashboard Geral</a>
                            <a class="nav-link" href="/?r=kpis/index"><i class="bi bi-graph-up-arrow"></i> KPIs Estratégicos</a>
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
                    <a class="btn btn-outline-dark btn-sm" href="/?r=auth/logout">
                        <i class="bi bi-box-arrow-right me-1"></i>
                        Sair
                    </a>
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
                    <div class="user-pill desktop-only">
                        <i class="bi bi-person-circle"></i>
                        <div>
                            <div class="fw-semibold"><?= h($user['nome']) ?></div>
                            <div class="text-muted small"><?= h($user['perfil']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <main class="app-content pb-4">


