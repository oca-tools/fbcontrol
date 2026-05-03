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
    <link rel="icon" type="image/svg+xml" href="<?= h($faviconPath) ?>">
    <link rel="shortcut icon" href="<?= h($faviconPath) ?>">
<?php elseif (!empty($logoPath)): ?>
    <link rel="icon" type="image/png" href="<?= h($logoPath) ?>">
<?php endif; ?>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
