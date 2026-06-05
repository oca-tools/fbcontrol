<?php
declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

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

try {
    $config = require $root . '/app/bootstrap_cli.php';
    $db = Database::getInstance();
    $dbName = (string)($config['db']['name'] ?? '');

    $record('database_runtime', $dbName !== '', $dbName);

    $requiredTables = [
        'usuarios',
        'restaurantes',
        'operacoes',
        'turnos',
        'acessos',
        'reservas_tematicas',
        'reservas_tematicas_bloqueios_datas',
        'reservas_tematicas_bloqueios_semanais',
        'kpi_ocupacao_diaria',
        'auditoria',
        'reservas_tematicas_capacidades_datas',
    ];

    foreach ($requiredTables as $table) {
        $stmt = $db->prepare("
            SELECT COUNT(*)
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table_name
        ");
        $stmt->execute([':table_name' => $table]);
        $record('table_' . $table, ((int)$stmt->fetchColumn()) === 1, $table);
    }

    $importantColumns = [
        ['turnos', 'modo_demo'],
        ['reservas_tematicas_config', 'auto_cancel_no_show_min'],
        ['reservas_tematicas_capacidades_datas', 'data_reserva'],
    ];

    foreach ($importantColumns as [$table, $column]) {
        $stmt = $db->prepare("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table_name
              AND COLUMN_NAME = :column_name
        ");
        $stmt->execute([
            ':table_name' => $table,
            ':column_name' => $column,
        ]);
        $record('column_' . $table . '_' . $column, ((int)$stmt->fetchColumn()) === 1, $table . '.' . $column);
    }

    $stmt = $db->query("
        SELECT COUNT(*)
        FROM usuarios
        WHERE perfil = 'admin'
          AND ativo = 1
    ");
    $record('active_admin_user', ((int)$stmt->fetchColumn()) > 0, 'usuarios.perfil=admin ativo=1');

    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM usuarios
        WHERE email = :email
          AND perfil = 'admin'
          AND ativo = 1
    ");
    $stmt->execute([':email' => 'admin@oca-tools.com.br']);
    $record('canonical_admin_email', ((int)$stmt->fetchColumn()) === 1, 'admin@oca-tools.com.br');

    $volumeTables = ['usuarios', 'acessos', 'reservas_tematicas', 'turnos'];
    foreach ($volumeTables as $table) {
        try {
            $count = (int)$db->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
            echo '[INFO] rows_' . $table . ' - ' . $count . PHP_EOL;
        } catch (Throwable $e) {
            $warn('Nao foi possivel contar ' . $table . ': ' . $e->getMessage());
        }
    }

    $duplicateStmt = $db->query("
        SELECT email, COUNT(*) AS total
        FROM usuarios
        WHERE ativo = 1
          AND email IS NOT NULL
          AND email <> ''
        GROUP BY email
        HAVING COUNT(*) > 1
        ORDER BY total DESC, email ASC
    ");
    foreach ($duplicateStmt->fetchAll() as $row) {
        $warn('E-mail ativo repetido: ' . $row['email'] . ' (' . (int)$row['total'] . ' usuarios). O login so fica seguro se as senhas forem distintas.');
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

$failed = array_values(array_filter($checks, static fn (array $check): bool => !$check['ok']));
echo PHP_EOL . 'Resultado: ' . (count($failed) === 0 ? 'OK' : 'FALHOU') . PHP_EOL;
exit(count($failed) === 0 ? 0 : 1);
