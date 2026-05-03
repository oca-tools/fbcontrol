<?php
declare(strict_types=1);

// Bootstrap compartilhado da aplicacao web. Sessao, headers e roteamento ficam no public/index.php.
require_once __DIR__ . '/helpers/php7_compat.php';

$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['app']['timezone']);

require_once __DIR__ . '/helpers/functions.php';
ob_start('normalize_output_mojibake');

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Model.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/core/Auth.php';

spl_autoload_register(static function ($class): void {
    $paths = [
        __DIR__ . '/controllers/' . $class . '.php',
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

