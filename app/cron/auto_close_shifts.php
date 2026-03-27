<?php
// Fecha automaticamente turnos ativos que excederam hora fim + tolerancia + 10 min.

declare(strict_types=1);

$config = require __DIR__ . '/../../config/config.php';
date_default_timezone_set($config['app']['timezone'] ?? 'America/Sao_Paulo');

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

$graceMinutes = 10;
$closedRegular = (new ShiftModel())->autoCloseExpired($graceMinutes, null);
$closedSpecial = (new SpecialShiftModel())->autoCloseExpired($graceMinutes, null);
$total = $closedRegular + $closedSpecial;

echo sprintf(
    "[%s] auto-close shifts: regular=%d special=%d total=%d\n",
    date('Y-m-d H:i:s'),
    $closedRegular,
    $closedSpecial,
    $total
);
