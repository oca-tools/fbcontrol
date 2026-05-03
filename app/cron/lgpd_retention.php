<?php
// Executa limpeza de retencao LGPD conforme politicas ativas.

declare(strict_types=1);

require __DIR__ . '/../bootstrap_cli.php';

$model = new LgpdModel();
$result = $model->runRetentionJob(null);

echo '[' . date('Y-m-d H:i:s') . '] LGPD retention: '
    . 'processed=' . (int)($result['processed'] ?? 0)
    . ', affected=' . (int)($result['affected'] ?? 0);

if (!empty($result['errors'])) {
    echo ', errors=' . implode(' | ', (array)$result['errors']);
}
echo PHP_EOL;
