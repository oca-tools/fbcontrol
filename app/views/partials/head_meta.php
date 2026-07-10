<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($appName) ?></title>
<script>
(function () {
    var allowedThemes = ['light', 'dark', 'sand', 'ocean'];
    try {
        var savedTheme = localStorage.getItem('oca_theme');
        var theme = allowedThemes.indexOf(savedTheme) >= 0 ? savedTheme : 'light';
        document.documentElement.setAttribute('data-theme', theme);
    } catch (e) {
        document.documentElement.setAttribute('data-theme', 'light');
    }
})();
</script>
<?php if (!empty($faviconPath)): ?>
    <link rel="icon" type="image/svg+xml" href="<?= h($faviconPath) ?>?v=20260705g">
    <link rel="shortcut icon" href="<?= h($faviconPath) ?>?v=20260705g">
<?php elseif (!empty($logoPath)): ?>
    <link rel="icon" type="image/png" href="<?= h($logoPath) ?>?v=20260705g">
<?php endif; ?>
<link rel="manifest" href="/manifest.webmanifest?v=20260705g">
<link rel="apple-touch-icon" href="/assets/apple-touch-icon.png?v=20260705g">
<meta name="theme-color" content="#E67E3C">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="FBControl">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script src="/assets/js/fb-filters.js?v=20260705g" defer></script>
