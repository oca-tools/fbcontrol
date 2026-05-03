<?php
// Envia o e-mail diário de resumo operacional quando estiver no horário configurado.

declare(strict_types=1);

require __DIR__ . '/../bootstrap_cli.php';

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
