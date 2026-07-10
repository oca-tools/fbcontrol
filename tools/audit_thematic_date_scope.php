<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap_cli.php';

$db = Database::getInstance();
$limit = max(1, min(200, (int)($argv[1] ?? 30)));
$stmt = $db->query("
    SELECT l.id AS log_id, l.criado_em AS evento_em, r.data_reserva,
           l.acao, u.nome AS usuario, uh.numero AS uh,
           rt.nome AS restaurante, t.hora AS turno
      FROM reservas_tematicas_logs l
      JOIN reservas_tematicas r ON r.id = l.reserva_id
      LEFT JOIN usuarios u ON u.id = l.usuario_id
      LEFT JOIN unidades_habitacionais uh ON uh.id = r.uh_id
      LEFT JOIN restaurantes rt ON rt.id = r.restaurante_id
      LEFT JOIN reservas_tematicas_turnos t ON t.id = r.turno_id
     WHERE DATE(l.criado_em) <> r.data_reserva
     ORDER BY l.criado_em DESC, l.id DESC
     LIMIT {$limit}
");

echo "Eventos cuja data do log difere da data operacional da reserva:" . PHP_EOL;
$rows = $stmt->fetchAll();
foreach ($rows as $row) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
if ($rows === []) {
    echo "Nenhum registro." . PHP_EOL;
}

