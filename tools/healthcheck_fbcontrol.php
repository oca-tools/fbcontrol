<?php
declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

$checks = [];
$warns = [];
$infos = [];
$strict = in_array('--strict', $argv ?? [], true)
    || strtolower((string)getenv('HEALTHCHECK_STRICT')) === 'yes'
    || strtolower((string)getenv('APP_ENV')) === 'production';

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

$info = static function (string $message) use (&$infos): void {
    $infos[] = $message;
};

$tableExists = static function (PDO $db, string $table): bool {
    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table_name
    ");
    $stmt->execute([':table_name' => $table]);
    return ((int)$stmt->fetchColumn()) === 1;
};

try {
    $config = require $root . '/app/bootstrap_cli.php';
    $db = Database::getInstance();

    $record('bootstrap_cli', is_array($config), 'app/bootstrap_cli.php carregado');
    $record('database_connection', true, (string)($config['db']['name'] ?? ''));

    $appEnv = (string)getenv('APP_ENV');
    if ($appEnv === '') {
        if ($strict) {
            $record('app_env', false, 'APP_ENV nao definido no CLI');
        } else {
            $info('APP_ENV nao definido no CLI. Em producao, defina APP_ENV=production no Apache/FPM e nos jobs.');
        }
    } else {
        $record('app_env', true, $appEnv);
    }

    $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'session'];
    foreach ($requiredExtensions as $extension) {
        $record('php_extension_' . $extension, extension_loaded($extension), $extension);
    }

    $fileinfoLoaded = extension_loaded('fileinfo');
    if ($strict) {
        $record('php_extension_fileinfo', $fileinfoLoaded, 'fileinfo');
    } elseif (!$fileinfoLoaded) {
        $warn('Extensao PHP fileinfo ausente no CLI. Uploads reais de vouchers/perfil dependem dela no ambiente web.');
    }

    $recommendedExtensions = [
        'mbstring' => 'Melhora tratamento de strings UTF-8.',
        'zip' => 'Necessaria para exportacao ZIP de vouchers.',
        'imagick' => 'Necessaria para converter imagens de vouchers em PDF e compactar anexos com mais qualidade.',
    ];
    foreach ($recommendedExtensions as $extension => $detail) {
        if (!extension_loaded($extension)) {
            $warn('Extensao PHP recomendada ausente: ' . $extension . '. ' . $detail);
        }
    }

    $uploadMax = ini_size_to_bytes((string)ini_get('upload_max_filesize'));
    $postMax = ini_size_to_bytes((string)ini_get('post_max_size'));
    $uploadOk = $uploadMax >= 5 * 1024 * 1024;
    $postOk = $postMax >= $uploadMax;
    if ($strict) {
        $record('php_upload_limit', $uploadOk, 'upload_max_filesize=' . ini_get('upload_max_filesize'));
        $record('php_post_limit', $postOk, 'post_max_size=' . ini_get('post_max_size'));
    } else {
        if (!$uploadOk) {
            $warn('upload_max_filesize no CLI esta abaixo de 5M: ' . ini_get('upload_max_filesize') . '. No VPS, mantenha 10M ou mais para vouchers.');
        }
        if (!$postOk) {
            $warn('post_max_size no CLI esta menor que upload_max_filesize: ' . ini_get('post_max_size') . '.');
        }
    }

    $paths = [
        'public/uploads' => $root . '/public/uploads',
        'public/uploads/vouchers' => $root . '/public/uploads/vouchers',
        'public/uploads/profiles' => $root . '/public/uploads/profiles',
    ];
    foreach ($paths as $label => $path) {
        if (!is_dir($path)) {
            $warn($label . ' ainda nao existe. Deve ser criado com permissao de escrita pelo servidor web antes de uploads reais.');
            continue;
        }
        $record('writable_' . str_replace(['/', '\\'], '_', $label), is_writable($path), $label);
    }

    $requiredTables = [
        'usuarios',
        'restaurantes',
        'operacoes',
        'turnos',
        'turnos_especiais',
        'acessos',
        'acessos_especiais',
        'reservas_tematicas',
        'reservas_tematicas_logs',
        'reservas_tematicas_bloqueios_datas',
        'reservas_tematicas_bloqueios_semanais',
        'lgpd_retencao_politicas',
        'relatorio_email_envios',
    ];
    foreach ($requiredTables as $table) {
        $record('table_' . $table, $tableExists($db, $table), $table);
    }

    $stmt = $db->query("
        SELECT COUNT(*)
        FROM usuarios
        WHERE perfil = 'admin'
          AND ativo = 1
    ");
    $record('active_admin_user', ((int)$stmt->fetchColumn()) > 0, 'usuarios.perfil=admin ativo=1');

    $expiredRegular = (new ShiftModel())->findExpiredActive(10, null);
    $expiredSpecial = (new SpecialShiftModel())->findExpiredActive(10, null);
    if (count($expiredRegular) > 0 || count($expiredSpecial) > 0) {
        $warn('Ha turnos elegiveis para encerramento automatico: regular=' . count($expiredRegular) . ', especiais=' . count($expiredSpecial) . '. Verifique o cron auto_close_shifts.php.');
    }

    if ($tableExists($db, 'reservas_tematicas_config')) {
        $candidates = (new ReservaTematicaModel())->findAutoNoShowCandidates(date('Y-m-d H:i:s'), null, null);
        if (count($candidates) > 0) {
            $warn('Ha reservas tematicas elegiveis para no-show automatico: ' . count($candidates) . '. Verifique o cron reservas_tematicas_auto_no_show.php.');
        }
    }

    if ($tableExists($db, 'relatorio_email_config')) {
        $emailModel = new DailyReportEmailModel();
        $info($emailModel->dueNow() ? 'Relatorio diario esta devido neste horario.' : 'Relatorio diario nao esta devido neste horario.');
    }
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

foreach ($infos as $message) {
    echo '[INFO] ' . $message . PHP_EOL;
}

$failed = array_values(array_filter($checks, static fn (array $check): bool => !$check['ok']));
echo PHP_EOL . 'Resultado: ' . (count($failed) === 0 ? 'OK' : 'FALHOU') . PHP_EOL;
exit(count($failed) === 0 ? 0 : 1);
