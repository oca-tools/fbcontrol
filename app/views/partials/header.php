<?php
$config = require __DIR__ . '/../../../config/config.php';
$appName = $config['app']['name'];
$appVersion = $config['app']['version'] ?? '1.0';
$logoPath = $config['app']['logo_path'] ?? '';
$faviconPath = $config['app']['favicon_path'] ?? '';
$user = Auth::user();
$currentRouteRaw = (string)($_GET['r'] ?? '');
if ($currentRouteRaw === '') {
    $currentRouteRaw = $user ? 'home' : 'auth/login';
}
$currentRoute = preg_replace('/[^a-zA-Z0-9_\/-]/', '', $currentRouteRaw);
$currentRole = $user['perfil'] ?? 'guest';
$currentUserId = (int)($user['id'] ?? 0);
$showGuidedTutorial = $user && in_array(strtolower((string)($user['perfil'] ?? '')), ['hostess', 'supervisor', 'gerente', 'admin'], true);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <?php require __DIR__ . '/head_meta.php'; ?>
    <?php require __DIR__ . '/head_inline_styles.php'; ?>
    <?php require __DIR__ . '/head_stylesheets.php'; ?>
</head>
<body data-route="<?= h((string)$currentRoute) ?>" data-role="<?= h((string)$currentRole) ?>" data-user-id="<?= (int)$currentUserId ?>">
<div class="app-shell">
    <?php if ($user): ?>
        <?php require __DIR__ . '/layout_context.php'; ?>
        <?php require __DIR__ . '/sidebar.php'; ?>
    <?php endif; ?>
    <div class="app-main">
        <?php if ($user): ?>
            <?php require __DIR__ . '/mobile_nav.php'; ?>
            <?php require __DIR__ . '/topbar.php'; ?>
        <?php endif; ?>
        <main class="app-content pb-4">


