<?php
declare(strict_types=1);

/**
 * FBCONTROL - Detector de bindings fantasmas entre backend e views.
 *
 * Uso:
 *   php tools/check_ghost_bindings.php
 *
 * Objetivo:
 *   Encontrar chaves usadas pelas views que nao aparecem no vocabulario PHP do
 *   backend. O sinal mais confiavel vem de $this->data['chave'].
 */

$root = realpath(__DIR__ . '/..');
if ($root === false) {
    fwrite(STDERR, "Erro: nao foi possivel resolver a raiz do projeto.\n");
    exit(2);
}

$viewsDir = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'views';
$backendDirs = [
    $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'controllers',
    $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'services',
    $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'models',
    $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'core',
    $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'support',
];

if (!is_dir($viewsDir)) {
    fwrite(STDERR, "Erro: pasta de views nao encontrada em {$viewsDir}\n");
    exit(2);
}

$backendVocabulary = '';
foreach ($backendDirs as $dir) {
    if (!is_dir($dir)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
            $content = file_get_contents($file->getPathname());
            if ($content !== false) {
                $backendVocabulary .= $content . "\n";
            }
        }
    }
}

$ignoreList = array_fill_keys(array_map('strtolower', [
    '_get', '_post', '_session', '_cookie', '_server', '_request', '_files',
    '_env', 'globals', 'this', 'key', 'val', 'value', 'item', 'items', 'i',
    'j', 'k', 'v', 'row', 'rows', 'index', 'data', 'dados', 'info', 'id',
    'ids', 'class', 'style', 'type', 'name', 'checked', 'selected', 'url',
    'href', 'src', 'alt', 'title', 'option', 'options', 'r', 'e', 'ok',
    'error', 'message', 'flash', 'csrf_token',
]), true);

echo "Inspecionando bindings entre backend e views...\n\n";

$viewIterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($viewsDir, FilesystemIterator::SKIP_DOTS)
);

$viewsWithContractWarnings = 0;
$viewsWithBroadWarnings = 0;
$totalContractOrphans = 0;
$totalBroadOrphans = 0;
$name = '[A-Za-z_][A-Za-z0-9_]*';

foreach ($viewIterator as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }

    $content = file_get_contents($file->getPathname());
    if ($content === false) {
        continue;
    }

    $relPath = str_replace($root . DIRECTORY_SEPARATOR, '', $file->getPathname());
    $relPath = str_replace(DIRECTORY_SEPARATOR, '/', $relPath);

    preg_match_all('/\$this->data\s*\[\s*[\'"](' . $name . ')[\'"]\s*\]/', $content, $dataMatches);
    $dataKeys = array_values(array_unique($dataMatches[1] ?? []));

    preg_match_all('/\$([A-Za-z_][A-Za-z0-9_]*)/', $content, $variableMatches);
    preg_match_all('/\[\s*[\'"](' . $name . ')[\'"]\s*\]/', $content, $arrayKeyMatches);
    preg_match_all('/->(' . $name . ')/', $content, $propertyMatches);

    preg_match_all('/\$([A-Za-z_][A-Za-z0-9_]*)\s*=/', $content, $assignedMatches);
    preg_match_all('/\bas\s+\$([A-Za-z_][A-Za-z0-9_]*)/', $content, $foreachValueMatches);
    preg_match_all('/=>\s*\$([A-Za-z_][A-Za-z0-9_]*)/', $content, $foreachKeyMatches);

    $localSymbols = array_fill_keys(array_map('strtolower', array_merge(
        $assignedMatches[1] ?? [],
        $foreachValueMatches[1] ?? [],
        $foreachKeyMatches[1] ?? []
    )), true);

    $contractOrphans = [];
    foreach ($dataKeys as $symbol) {
        if (isset($ignoreList[strtolower($symbol)])) {
            continue;
        }
        if (!str_contains($backendVocabulary, $symbol)) {
            $contractOrphans[] = $symbol;
        }
    }

    $broadSymbols = array_values(array_unique(array_merge(
        $variableMatches[1] ?? [],
        $arrayKeyMatches[1] ?? [],
        $propertyMatches[1] ?? []
    )));

    $broadOrphans = [];
    foreach ($broadSymbols as $symbol) {
        $lower = strtolower($symbol);
        if (isset($ignoreList[$lower]) || isset($localSymbols[$lower])) {
            continue;
        }
        if (!str_contains($backendVocabulary, $symbol)) {
            $broadOrphans[] = $symbol;
        }
    }

    if ($contractOrphans !== []) {
        $viewsWithContractWarnings++;
        $totalContractOrphans += count($contractOrphans);
        echo "[ALTO] Possivel quebra de contrato em {$relPath}\n";
        echo "       Chaves em \$this->data nao encontradas no backend:\n";
        foreach ($contractOrphans as $symbol) {
            echo "       - {$symbol}\n";
        }
        echo "\n";
    }

    if ($broadOrphans !== []) {
        $viewsWithBroadWarnings++;
        $totalBroadOrphans += count($broadOrphans);
        echo "[INFO] Simbolos amplos sem correspondencia em {$relPath}\n";
        echo "       Revise apenas se a tela estiver em refatoracao:\n";
        foreach (array_slice($broadOrphans, 0, 30) as $symbol) {
            echo "       - {$symbol}\n";
        }
        if (count($broadOrphans) > 30) {
            echo "       ... +" . (count($broadOrphans) - 30) . " simbolos\n";
        }
        echo "\n";
    }
}

echo "Resumo:\n";
echo "- Views com risco alto: {$viewsWithContractWarnings}\n";
echo "- Chaves de contrato suspeitas: {$totalContractOrphans}\n";
echo "- Views com achados informativos: {$viewsWithBroadWarnings}\n";
echo "- Simbolos informativos suspeitos: {$totalBroadOrphans}\n";

exit($viewsWithContractWarnings > 0 ? 1 : 0);
