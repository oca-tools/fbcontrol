<?php
declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

$php = PHP_BINARY ?: 'php';

$run = static function (string $label, array $command) use ($root): int {
    echo PHP_EOL . '== ' . $label . ' ==' . PHP_EOL;
    $parts = array_map('escapeshellarg', $command);
    $cmd = implode(' ', $parts);
    passthru($cmd, $code);
    return (int)$code;
};

$lint = static function () use ($root, $php): int {
    echo PHP_EOL . '== PHP lint ==' . PHP_EOL;
    $bases = ['app', 'public', 'config', 'tools', 'deploy'];
    $count = 0;

    foreach ($bases as $base) {
        $dir = $root . DIRECTORY_SEPARATOR . $base;
        if (!is_dir($dir)) {
            continue;
        }

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($it as $file) {
            if (!$file->isFile() || $file->isLink()) {
                continue;
            }
            $path = $file->getPathname();
            if (!preg_match('/\.php$/i', $path)) {
                continue;
            }
            passthru(escapeshellarg($php) . ' -l ' . escapeshellarg($path), $code);
            if ($code !== 0) {
                return (int)$code;
            }
            $count++;
        }
    }

    echo 'Lint finalizado: ' . $count . ' arquivos.' . PHP_EOL;
    return 0;
};

$failed = 0;
$failed += $lint();
$failed += $run('Audit sanitizer', [$php, 'tools/check_audit_sanitizer.php']);
$failed += $run('Critical business rules', [$php, 'tools/test_critical_rules.php']);
$failed += $run('Official UH validation', [$php, 'tools/test_unit_validation.php']);
$failed += $run('Reservation registration flow', [$php, 'tools/test_reservation_registration_flow.php']);
$failed += $run('Security controls', [$php, 'tools/test_security_controls.php']);
$failed += $run('LGPD controls', [$php, 'tools/test_lgpd_controls.php']);
$failed += $run('Export documents', [$php, 'tools/test_export_documents.php']);
$failed += $run('Smoke check', [$php, 'tools/smoke_fbcontrol.php']);
$failed += $run('DB context', [$php, 'tools/check_db_context.php']);
$failed += $run('Query performance', [$php, 'tools/check_query_performance.php']);
$failed += $run('Release hygiene', [$php, 'tools/check_release_hygiene.php']);
$failed += $run('Ops healthcheck', [$php, 'tools/healthcheck_fbcontrol.php']);
$failed += $run('SAST baseline', [$php, 'deploy/security/sast_scan.php']);
$failed += $run('Release candidate', [$php, 'tools/check_release_candidate.php']);

echo PHP_EOL . 'Resultado geral: ' . ($failed === 0 ? 'OK' : 'FALHOU') . PHP_EOL;
exit($failed === 0 ? 0 : 1);
