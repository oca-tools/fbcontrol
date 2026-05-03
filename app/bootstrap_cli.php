<?php
declare(strict_types=1);

// Bootstrap compartilhado para jobs CLI. Mantem crons pequenos e consistentes.
$config = require __DIR__ . '/../config/config.php';

date_default_timezone_set($config['app']['timezone'] ?? 'America/Sao_Paulo');
ini_set('default_charset', 'UTF-8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

require_once __DIR__ . '/helpers/php7_compat.php';
require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Model.php';

spl_autoload_register(static function ($class): void {
    $paths = [
        __DIR__ . '/models/' . $class . '.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

return $config;

