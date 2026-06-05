<?php
declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

$checks = [];
$warns = [];
$versionExpected = $argv[1] ?? '3.0';
$php = PHP_BINARY ?: 'php';

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

$run = static function (array $command, ?array &$output = null): int {
    $parts = array_map('escapeshellarg', $command);
    exec(implode(' ', $parts), $output, $code);
    return (int)$code;
};

$startsWith = static function (string $haystack, string $needle): bool {
    return $needle === '' || strpos($haystack, $needle) === 0;
};

try {
    $config = require $root . '/config/config.php';
    $version = (string)($config['app']['version'] ?? '');
    $record('app_version', $version === $versionExpected, $version);

    $requiredFiles = [
        'README.md',
        'CHANGELOG.md',
        'docs/RELEASE_3_0.md',
        'docs/INSTALACAO_VPS.md',
        'docs/LGPD_OPERACAO.md',
        'sql/schema_current.sql',
        'sql/migration_v2_9_performance_indexes.sql',
        'tools/run_checks.php',
        'tools/healthcheck_fbcontrol.php',
        'tools/check_db_context.php',
        'tools/check_release_hygiene.php',
        'tools/build_release.php',
    ];
    foreach ($requiredFiles as $file) {
        $record('file_' . str_replace(['/', '.', '-'], '_', $file), is_file($file), $file);
    }

    $schema = is_file('sql/schema_current.sql') ? (string)file_get_contents('sql/schema_current.sql') : '';
    foreach ([
        'reservas_tematicas_bloqueios_datas',
        'reservas_tematicas_bloqueios_semanais',
        'modo_demo',
        'idx_vouchers_data',
        'idx_turnos_inicio',
    ] as $needle) {
        $record('schema_contains_' . $needle, strpos($schema, $needle) !== false, $needle);
    }

    $gitProbe = [];
    $gitProbeCode = $run(['git', 'rev-parse', '--is-inside-work-tree'], $gitProbe);
    $hasGit = $gitProbeCode === 0 && trim((string)($gitProbe[0] ?? '')) === 'true';
    if ($hasGit) {
        $dryRun = [];
        $dryCode = $run([$php, 'tools/build_release.php', $versionExpected, 'ignored.tar.gz', '--dry-run'], $dryRun);
        $dryText = implode("\n", $dryRun);
        $record('build_release_dry_run', $dryCode === 0, 'codigo=' . $dryCode);
        $record('build_release_keeps_uploads_htaccess', strpos($dryText, 'public/uploads/.htaccess') !== false, 'public/uploads/.htaccess');
        $record('build_release_excludes_local_config', strpos($dryText, 'config/config.local.php') === false, 'config local fora do pacote');
        $record('build_release_excludes_runtime_uploads', strpos($dryText, 'public/uploads/profiles/') === false && strpos($dryText, 'public/uploads/vouchers/') === false, 'uploads reais fora do pacote');
    } else {
        $warn('Git indisponivel nesta pasta; dry-run do builder foi pulado. Isto e esperado em releases extraidas de pacote.');
        $record('build_release_dry_run', true, 'pulado fora do repositorio Git');
    }

    $hygiene = [];
    $hygieneCode = $run([$php, 'tools/check_release_hygiene.php'], $hygiene);
    $record('release_hygiene', $hygieneCode === 0, 'codigo=' . $hygieneCode);
    foreach ($hygiene as $line) {
        if ($startsWith($line, '[WARN]')) {
            $warn(substr($line, 7));
        }
    }

    $readme = is_file('README.md') ? (string)file_get_contents('README.md') : '';
    $record('readme_release_commands', strpos($readme, 'php tools/run_checks.php') !== false && strpos($readme, 'php tools/build_release.php 3.0') !== false, 'comandos de release documentados');
} catch (Throwable $e) {
    $record('fatal', false, $e->getMessage());
}

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
