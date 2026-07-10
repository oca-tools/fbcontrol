<?php
declare(strict_types=1);

/**
 * Regenera public/assets/css/legacy.css a partir de app/views/partials/style_global.php
 * (que agrega os hotfixes legados). Rode após qualquer alteração nesses partials e
 * atualize o parâmetro ?v= em app/views/partials/head_inline_styles.php.
 *
 * Uso: php tools/build_legacy_css.php
 */

chdir(__DIR__ . '/..');

ob_start();
require 'app/views/partials/style_global.php';
$css = ob_get_clean();

$cabecalho = "/* CSS legado do FBControl, gerado de app/views/partials/style_global.php (faxina, etapa 9).\n"
    . " * Para alterar: edite o partial e regenere com tools/build_legacy_css.php.\n"
    . " * Este arquivo congela o estilo das telas ainda nao migradas; morre junto com elas. */\n";

file_put_contents('public/assets/css/legacy.css', $cabecalho . $css);
echo 'legacy.css regenerado: ' . strlen($cabecalho . $css) . " bytes\n";
