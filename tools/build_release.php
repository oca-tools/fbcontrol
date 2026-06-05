<?php
declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

$dryRun = in_array('--dry-run', $argv ?? [], true);
$version = preg_replace('/[^0-9A-Za-z._-]/', '', $argv[1] ?? '3.0') ?: '3.0';
$stamp = date('Ymd_His');
$output = $argv[2] ?? ($root . DIRECTORY_SEPARATOR . 'fbcontrol_release_' . $version . '_' . $stamp . '.tar.gz');
$stderrNull = PHP_OS_FAMILY === 'Windows' ? '2>NUL' : '2>/dev/null';

$gitCheck = [];
exec('git rev-parse --is-inside-work-tree ' . $stderrNull, $gitCheck, $gitCode);
if ($gitCode !== 0 || trim((string)($gitCheck[0] ?? '')) !== 'true') {
    fwrite(STDERR, "Este builder precisa ser executado dentro do repositório Git.\n");
    exit(1);
}

$tracked = [];
exec('git ls-files ' . $stderrNull, $tracked, $gitFilesCode);
if ($gitFilesCode !== 0) {
    fwrite(STDERR, "Nao foi possivel listar arquivos rastreados pelo Git.\n");
    exit(1);
}

$excludes = [
    '#^config/config\.local\.php$#',
    '#^config/.*\.local\.php$#',
    '#^public/uploads/(?!\.htaccess$)#',
    '#^logs/#',
    '#\.bak(\..*)?$#i',
    '#\.tmp$#i',
    '#\.(tar|tar\.gz|zip|sql\.gz|log)$#i',
];

$files = [];
foreach ($tracked as $file) {
    $path = str_replace('\\', '/', trim($file));
    if ($path === '') {
        continue;
    }
    foreach ($excludes as $pattern) {
        if (preg_match($pattern, $path)) {
            continue 2;
        }
    }
    if (is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path))) {
        $files[] = $path;
    }
}

if (empty($files)) {
    fwrite(STDERR, "Nenhum arquivo selecionado para release.\n");
    exit(1);
}

if ($dryRun) {
    echo 'Dry-run release FBControl ' . $version . PHP_EOL;
    echo 'Arquivos selecionados: ' . count($files) . PHP_EOL;
    echo 'Uploads/config local/backups excluidos por padrao.' . PHP_EOL;
    foreach ($files as $file) {
        echo $file . PHP_EOL;
    }
    exit(0);
}

if (file_exists($output)) {
    unlink($output);
}

$tarPath = preg_replace('/\.gz$/i', '', $output);
if ($tarPath === $output) {
    $tarPath .= '.tar';
    $output = $tarPath . '.gz';
}
if (file_exists($tarPath)) {
    unlink($tarPath);
}

$phar = new PharData($tarPath);
foreach ($files as $path) {
    $phar->addFile($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path), $path);
}
$phar->compress(Phar::GZ);
unset($phar);
if (file_exists($tarPath)) {
    unlink($tarPath);
}

echo 'Release gerada: ' . $output . PHP_EOL;
echo 'Arquivos incluidos: ' . count($files) . PHP_EOL;
echo 'Uploads/config local/backups excluidos por padrao.' . PHP_EOL;
