<?php
declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

require $root . '/app/bootstrap_cli.php';

$apply = in_array('--apply', $argv ?? [], true);
$db = Database::getInstance();

$tableExists = $db->query("SHOW TABLES LIKE 'lgpd_eventos'")->fetch();
if (!$tableExists) {
    echo 'Tabela lgpd_eventos nao encontrada. Nada a sanear.' . PHP_EOL;
    exit(0);
}

$select = $db->query('SELECT id, detalhes_json FROM lgpd_eventos ORDER BY id');
$update = $db->prepare('UPDATE lgpd_eventos SET detalhes_json = :detalhes_json WHERE id = :id');

$rowsScanned = 0;
$rowsChanged = 0;
$invalidPayloads = 0;

if ($apply) {
    $db->beginTransaction();
}

try {
    while ($row = $select->fetch(PDO::FETCH_ASSOC)) {
        $rowsScanned++;
        $original = (string)($row['detalhes_json'] ?? '{}');
        $decoded = json_decode($original, true);
        if (!is_array($decoded)) {
            $invalidPayloads++;
            continue;
        }

        $sanitized = LgpdModel::sanitizePrivacyEventDetails($decoded);
        if ($sanitized === $decoded) {
            continue;
        }

        $encoded = json_encode($sanitized, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if (!is_string($encoded)) {
            throw new RuntimeException('Falha ao serializar evento LGPD ID ' . (int)$row['id']);
        }

        $rowsChanged++;
        if ($apply) {
            $update->execute([
                ':id' => (int)$row['id'],
                ':detalhes_json' => $encoded,
            ]);
        }
    }

    if ($apply) {
        $db->commit();
    }
} catch (Throwable $e) {
    if ($apply && $db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, '[FAIL] ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo 'Modo: ' . ($apply ? 'APPLY' : 'DRY-RUN') . PHP_EOL;
echo 'Eventos analisados: ' . $rowsScanned . PHP_EOL;
echo 'Eventos com alteracao: ' . $rowsChanged . PHP_EOL;
echo 'Payloads JSON invalidos: ' . $invalidPayloads . PHP_EOL;

if (!$apply && $rowsChanged > 0) {
    echo 'Execute novamente com --apply para persistir a limpeza.' . PHP_EOL;
}
