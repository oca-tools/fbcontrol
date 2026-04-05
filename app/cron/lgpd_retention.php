<?php
// Executa limpeza de retencao LGPD conforme politicas ativas.

declare(strict_types=1);

$config = require __DIR__ . '/../../config/config.php';
date_default_timezone_set($config['app']['timezone'] ?? 'America/Sao_Paulo');

require __DIR__ . '/../helpers/functions.php';
require __DIR__ . '/../core/Database.php';
require __DIR__ . '/../core/Model.php';

spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../models/' . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require $path;
            return;
        }
    }
});

$model = new LgpdModel();
$result = $model->runRetentionJob(null);

echo '[' . date('Y-m-d H:i:s') . '] LGPD retention: '
    . 'processed=' . (int)($result['processed'] ?? 0)
    . ', affected=' . (int)($result['affected'] ?? 0);

if (!empty($result['errors'])) {
    echo ', errors=' . implode(' | ', (array)$result['errors']);
}
echo PHP_EOL;
