<?php
declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

require $root . '/app/bootstrap_cli.php';

$db = Database::getInstance();
$db->exec("
    ALTER TABLE reservas_tematicas_bloqueios_datas
    ADD COLUMN IF NOT EXISTS modo ENUM('fechado', 'aberto') NOT NULL DEFAULT 'fechado' AFTER ativo
");
$db->exec("
    UPDATE reservas_tematicas_bloqueios_datas
    SET modo = 'fechado'
    WHERE modo IS NULL OR modo = ''
");

$check = $db->query("SHOW COLUMNS FROM reservas_tematicas_bloqueios_datas LIKE 'modo'")->fetch();
if (!$check) {
    fwrite(STDERR, '[FAIL] Coluna de disponibilidade tematica nao encontrada.' . PHP_EOL);
    exit(1);
}

$databaseName = (string)$db->query('SELECT DATABASE()')->fetchColumn();
echo '[OK] Disponibilidade tematica atualizada em ' . $databaseName . '.' . PHP_EOL;
