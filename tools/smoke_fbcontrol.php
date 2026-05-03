<?php
declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

session_save_path(sys_get_temp_dir());
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$checks = [];

$record = static function (string $name, bool $ok, string $detail = '') use (&$checks): void {
    $checks[] = [
        'name' => $name,
        'ok' => $ok,
        'detail' => $detail,
    ];
};

try {
    $_GET['r'] = 'access/index';
    $config = require $root . '/app/bootstrap_web.php';
    $record('bootstrap_web', is_array($config), 'app/bootstrap_web.php carregado');

    $db = Database::getInstance();
    $record('database_connection', true, (string)($config['db']['name'] ?? ''));

    $requiredTables = ['usuarios', 'turnos', 'acessos', 'reservas_tematicas'];
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

    $stmt = $db->query("
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'reservas_tematicas_config'
          AND COLUMN_NAME = 'auto_cancel_no_show_min'
    ");
    $record('column_auto_cancel_no_show_min', ((int)$stmt->fetchColumn()) === 1, 'reservas_tematicas_config.auto_cancel_no_show_min');

    $_SESSION['user'] = [
        'id' => 1,
        'nome' => 'Smoke Check',
        'email' => 'smoke@example.local',
        'perfil' => 'admin',
        'foto_path' => null,
    ];
    $_SESSION['csrf_token'] = str_repeat('a', 64);

    ob_start();
    require $root . '/app/views/partials/header.php';
    $html = ob_get_clean();

    $record('render_header_main', strpos($html, '<main class=') !== false, 'main app-content');
    $record('render_sidebar', strpos($html, 'sidebar-menu') !== false, 'sidebar desktop');
    $record('render_mobile_nav', strpos($html, 'mobileMenu') !== false, 'offcanvas mobile');
    $record('render_topbar', strpos($html, 'topbar-theme') !== false, 'topbar desktop');
    $record('render_theme_buttons', substr_count($html, 'js-theme-option') === 8, '4 botoes mobile + 4 desktop');
} catch (Throwable $e) {
    $record('fatal', false, $e->getMessage());
}

$failed = array_values(array_filter($checks, static fn (array $check): bool => !$check['ok']));

foreach ($checks as $check) {
    echo ($check['ok'] ? '[OK] ' : '[FAIL] ') . $check['name'];
    if ($check['detail'] !== '') {
        echo ' - ' . $check['detail'];
    }
    echo PHP_EOL;
}

echo PHP_EOL . 'Resultado: ' . (count($failed) === 0 ? 'OK' : 'FALHOU') . PHP_EOL;
exit(count($failed) === 0 ? 0 : 1);
