<?php
declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);
require $root . '/app/bootstrap_cli.php';

$db = Database::getInstance();

try {
    $db->exec("\n        ALTER TABLE reservas_tematicas\n            ADD COLUMN IF NOT EXISTS correlation_id VARCHAR(80) NULL AFTER grupo_nome,\n            ADD COLUMN IF NOT EXISTS correlation_item SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER correlation_id\n    ");

    $check = $db->prepare("\n        SELECT COUNT(*)\n        FROM information_schema.statistics\n        WHERE table_schema = DATABASE()\n          AND table_name = 'reservas_tematicas'\n          AND index_name = 'uq_res_tem_correlation_item'\n    ");
    $check->execute();
    if ((int)$check->fetchColumn() === 0) {
        $db->exec("\n            ALTER TABLE reservas_tematicas\n            ADD UNIQUE KEY uq_res_tem_correlation_item (usuario_id, correlation_id, correlation_item)\n        ");
    }

    echo '[OK] Idempotência de reservas temáticas aplicada.' . PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, '[FAIL] ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
