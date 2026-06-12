<?php
declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

require $root . '/app/bootstrap_cli.php';

$db = Database::getInstance();
$check = $db->prepare("
    SELECT IS_NULLABLE
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'auditoria'
      AND COLUMN_NAME = 'usuario_id'
");
$check->execute();
$nullable = $check->fetchColumn();

if ($nullable === false) {
    fwrite(STDERR, "[FAIL] Coluna auditoria.usuario_id nao encontrada." . PHP_EOL);
    exit(1);
}

if ($nullable !== 'YES') {
    $db->exec('ALTER TABLE auditoria MODIFY usuario_id INT NULL');
}

$check->execute();
if ($check->fetchColumn() !== 'YES') {
    fwrite(STDERR, "[FAIL] auditoria.usuario_id continua obrigatoria." . PHP_EOL);
    exit(1);
}

echo "[OK] auditoria.usuario_id aceita eventos pre-login sem usuario identificado." . PHP_EOL;
