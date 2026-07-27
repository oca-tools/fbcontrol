<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este utilitario deve ser executado no terminal.\n");
    exit(1);
}

$destination = trim((string)($argv[1] ?? ''));
if ($destination === '') {
    fwrite(STDERR, "Uso: php tools/backup_database.php /caminho/backup.sql\n");
    exit(1);
}

$directory = dirname($destination);
if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
    fwrite(STDERR, "Nao foi possivel criar o diretorio de backup.\n");
    exit(1);
}

$config = require dirname(__DIR__) . '/config/config.php';
$db = (array)($config['db'] ?? []);
foreach (['host', 'name', 'user'] as $key) {
    if (trim((string)($db[$key] ?? '')) === '') {
        fwrite(STDERR, "Configuracao de banco incompleta: {$key}.\n");
        exit(1);
    }
}

$binary = trim((string)(getenv('MYSQLDUMP_BIN') ?: 'mysqldump'));
$command = implode(' ', [
    escapeshellcmd($binary),
    '--single-transaction',
    '--routines',
    '--events',
    '--skip-lock-tables',
    '--default-character-set=' . escapeshellarg((string)($db['charset'] ?? 'utf8mb4')),
    '--host=' . escapeshellarg((string)$db['host']),
    '--user=' . escapeshellarg((string)$db['user']),
    escapeshellarg((string)$db['name']),
]);

$environment = getenv();
if (!is_array($environment)) {
    $environment = $_ENV;
}
$environment['MYSQL_PWD'] = (string)($db['pass'] ?? '');
$pipes = [];
$process = proc_open($command, [
    0 => ['pipe', 'r'],
    1 => ['file', $destination, 'w'],
    2 => ['pipe', 'w'],
], $pipes, null, $environment);

if (!is_resource($process)) {
    fwrite(STDERR, "Nao foi possivel iniciar o mysqldump.\n");
    exit(1);
}

fclose($pipes[0]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[2]);
$exitCode = proc_close($process);

if ($exitCode !== 0 || !is_file($destination) || filesize($destination) === 0) {
    @unlink($destination);
    fwrite(STDERR, "Falha no backup do banco. " . trim((string)$stderr) . "\n");
    exit(1);
}

chmod($destination, 0600);
echo "Backup do banco criado: {$destination}\n";
