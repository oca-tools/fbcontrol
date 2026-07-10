<?php /* Faxina (etapa 9): o CSS legado deixou de ser inline (~116KB por requisição) e virou
   arquivo estático cacheável, gerado do style_global.php por tools/build_legacy_css.php.
   A posição antes dos demais <link> preserva a ordem da cascata. */ ?>
<?php
$legacyCssPath = dirname(__DIR__, 3) . '/public/assets/css/legacy.css';
$legacyCssVersion = is_file($legacyCssPath) ? (string)filemtime($legacyCssPath) : '1';
?>
<link href="/assets/css/legacy.css?v=<?= h($legacyCssVersion) ?>" rel="stylesheet">
