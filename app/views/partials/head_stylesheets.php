<?php
$cssRoot = dirname(__DIR__, 3) . '/public/assets/css';
$cssVersion = static function (string $file) use ($cssRoot): string {
    $path = $cssRoot . '/' . $file;
    return is_file($path) ? (string)filemtime($path) : '1';
};
?>
<link href="/assets/css/design-system.css?v=<?= h($cssVersion('design-system.css')) ?>" rel="stylesheet">
<link href="/assets/css/layout.css?v=<?= h($cssVersion('layout.css')) ?>" rel="stylesheet">
<link href="/assets/css/app-modern.css?v=<?= h($cssVersion('app-modern.css')) ?>" rel="stylesheet">
<link href="/assets/css/components.css?v=<?= h($cssVersion('components.css')) ?>" rel="stylesheet">
<?php /* tokens.css por último: identidade nova + ponte sobre o CSS legado (--ab-*). */ ?>
<link href="/assets/css/tokens.css?v=<?= h($cssVersion('tokens.css')) ?>" rel="stylesheet">
