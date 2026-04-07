<?php
// Envia o e-mail diário de resumo operacional quando estiver no horário configurado.

declare(strict_types=1);

$config = require __DIR__ . '/../../config/config.php';
date_default_timezone_set($config['app']['timezone'] ?? 'America/Sao_Paulo');
ini_set('default_charset', 'UTF-8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

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

$model = new DailyReportEmailModel();
$dateRef = date('Y-m-d');

if (!$model->dueNow()) {
    echo '[' . date('Y-m-d H:i:s') . "] envio não devido neste horário.\n";
    exit(0);
}

if ($model->wasSent($dateRef)) {
    echo '[' . date('Y-m-d H:i:s') . "] relatório já enviado para {$dateRef}.\n";
    exit(0);
}

$result = $model->sendDailyReport(false, $dateRef);
echo '[' . date('Y-m-d H:i:s') . '] ' . ($result['message'] ?? 'sem mensagem') . "\n";
