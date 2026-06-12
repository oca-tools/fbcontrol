<?php
declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

require $root . '/app/bootstrap_cli.php';

$apply = in_array('--apply', $argv ?? [], true);
$db = Database::getInstance();
$select = $db->query('SELECT id, dados_antes, dados_depois FROM auditoria ORDER BY id');
$update = $db->prepare('
    UPDATE auditoria
    SET dados_antes = :dados_antes,
        dados_depois = :dados_depois
    WHERE id = :id
');

$rowsScanned = 0;
$rowsChanged = 0;
$payloadsChanged = 0;
$invalidPayloads = 0;

if ($apply) {
    $db->beginTransaction();
}

try {
    while ($row = $select->fetch(PDO::FETCH_ASSOC)) {
        $rowsScanned++;
        $changed = false;
        $newValues = [];

        foreach (['dados_antes', 'dados_depois'] as $field) {
            $original = (string)($row[$field] ?? '{}');
            $decoded = json_decode($original, true);
            if (!is_array($decoded)) {
                $invalidPayloads++;
                $newValues[$field] = $original;
                continue;
            }

            $sanitized = Model::sanitizeAuditPayload($decoded);
            if ($sanitized !== $decoded) {
                $payloadsChanged++;
                $changed = true;
                $encoded = json_encode($sanitized, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                if (!is_string($encoded)) {
                    throw new RuntimeException('Falha ao serializar auditoria ID ' . (int)$row['id']);
                }
                $newValues[$field] = $encoded;
            } else {
                $newValues[$field] = $original;
            }
        }

        if (!$changed) {
            continue;
        }

        $rowsChanged++;
        if ($apply) {
            $update->execute([
                ':id' => (int)$row['id'],
                ':dados_antes' => $newValues['dados_antes'],
                ':dados_depois' => $newValues['dados_depois'],
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
echo 'Linhas analisadas: ' . $rowsScanned . PHP_EOL;
echo 'Linhas com alteracao: ' . $rowsChanged . PHP_EOL;
echo 'Payloads redigidos: ' . $payloadsChanged . PHP_EOL;
echo 'Payloads JSON invalidos: ' . $invalidPayloads . PHP_EOL;

if (!$apply && $rowsChanged > 0) {
    echo 'Execute novamente com --apply para persistir a limpeza.' . PHP_EOL;
}
