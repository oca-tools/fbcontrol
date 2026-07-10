<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap_cli.php';

$db = Database::getInstance();

echo "== AUDITORIA GERAL SEM USUARIO ==" . PHP_EOL;
$rows = $db->query("
    SELECT tabela, acao, COUNT(*) AS total,
           MIN(criado_em) AS primeiro, MAX(criado_em) AS ultimo
      FROM auditoria
     WHERE usuario_id IS NULL
     GROUP BY tabela, acao
     ORDER BY total DESC, tabela, acao
")->fetchAll();
foreach ($rows as $row) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
if ($rows === []) {
    echo "Nenhum registro." . PHP_EOL;
}

echo PHP_EOL . "== LOGS TEMATICOS SEM USUARIO ==" . PHP_EOL;
$rows = $db->query("
    SELECT acao, COUNT(*) AS total,
           MIN(criado_em) AS primeiro, MAX(criado_em) AS ultimo
      FROM reservas_tematicas_logs
     WHERE usuario_id IS NULL
     GROUP BY acao
     ORDER BY total DESC, acao
")->fetchAll();
foreach ($rows as $row) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
if ($rows === []) {
    echo "Nenhum registro." . PHP_EOL;
}

