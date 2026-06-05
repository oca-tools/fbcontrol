<?php
declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

$strict = in_array('--strict', $argv ?? [], true);
$checks = [];
$warns = [];

$record = static function (string $name, bool $ok, string $detail = '') use (&$checks): void {
    $checks[] = [
        'name' => $name,
        'ok' => $ok,
        'detail' => $detail,
    ];
};

$warn = static function (string $message) use (&$warns): void {
    $warns[] = $message;
};

$read = static function (string $path): string {
    return is_file($path) ? (string)file_get_contents($path) : '';
};

$startsWith = static function (string $haystack, string $needle): bool {
    return $needle === '' || strpos($haystack, $needle) === 0;
};

$stderrNull = PHP_OS_FAMILY === 'Windows' ? '2>NUL' : '2>/dev/null';
$gitFiles = [];
exec('git ls-files ' . $stderrNull, $gitFiles, $gitCode);
if ($gitCode !== 0) {
    $warn('Git indisponivel nesta pasta; checagem de arquivos rastreados foi pulada.');
} else {
    $record('git_ls_files', true, count($gitFiles) . ' arquivos rastreados');
}

$forbiddenTracked = [];
$allowedRuntimeTracked = ['public/uploads/.htaccess'];
foreach ($gitFiles as $file) {
    $path = str_replace('\\', '/', trim($file));
    if ($path === '') {
        continue;
    }
    $isRuntimeUpload = $startsWith($path, 'public/uploads/') && !in_array($path, $allowedRuntimeTracked, true);
    $isLocalConfig = $path === 'config/config.local.php' || preg_match('#^config/.*\.local\.php$#', $path);
    $isBuildArtifact = preg_match('#(\.bak(\..*)?|\.tmp|\.tar|\.tar\.gz|\.zip|\.sql\.gz|\.log)$#i', $path);
    $isRuntimeDir = $startsWith($path, 'logs/');

    if ($isRuntimeUpload || $isLocalConfig || $isBuildArtifact || $isRuntimeDir) {
        $forbiddenTracked[] = $path;
    }
}

if (!empty($forbiddenTracked)) {
    $message = 'Arquivos rastreados que nao devem entrar em release: ' . implode(', ', $forbiddenTracked);
    if ($strict) {
        $record('tracked_runtime_artifacts', false, $message);
    } else {
        $warn($message);
    }
} else {
    $record('tracked_runtime_artifacts', true, 'nenhum artefato runtime rastreado');
}

$gitignore = $read('.gitignore');
$record('gitignore_local_config', strpos($gitignore, 'config/config.local.php') !== false, 'config local ignorado');
$record('gitignore_uploads', strpos($gitignore, 'public/uploads/') !== false, 'uploads ignorados');
$record('gitignore_archives', strpos($gitignore, '*.tar.gz') !== false && strpos($gitignore, '*.zip') !== false, 'artefatos compactados ignorados');

$gitattributes = $read('.gitattributes');
$record('export_ignore_local_config', strpos($gitattributes, 'config/config.local.php export-ignore') !== false, 'config local fora do git archive');
$record('export_ignore_uploads', strpos($gitattributes, 'public/uploads/** export-ignore') !== false, 'uploads fora do git archive');
$record('keep_uploads_htaccess', strpos($gitattributes, 'public/uploads/.htaccess -export-ignore') !== false, '.htaccess de uploads preservado');

$record('uploads_htaccess_exists', is_file('public/uploads/.htaccess'), 'protege uploads no Apache');

foreach ($checks as $check) {
    echo ($check['ok'] ? '[OK] ' : '[FAIL] ') . $check['name'];
    if ($check['detail'] !== '') {
        echo ' - ' . $check['detail'];
    }
    echo PHP_EOL;
}

foreach ($warns as $message) {
    echo '[WARN] ' . $message . PHP_EOL;
}

$failed = array_values(array_filter($checks, static fn (array $check): bool => !$check['ok']));
echo PHP_EOL . 'Resultado: ' . (count($failed) === 0 ? 'OK' : 'FALHOU') . PHP_EOL;
exit(count($failed) === 0 ? 0 : 1);
