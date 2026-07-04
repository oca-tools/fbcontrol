<?php
declare(strict_types=1);

$workingDirectory = getcwd();
$root = is_string($workingDirectory) && is_file($workingDirectory . '/app/bootstrap_cli.php')
    ? $workingDirectory
    : dirname(__DIR__);
chdir($root);

$config = require $root . '/app/bootstrap_cli.php';
$db = Database::getInstance();
$options = getopt('', ['uh:', 'inicio::', 'fim::']);
$uhInput = trim((string)($options['uh'] ?? ''));
$inicio = trim((string)($options['inicio'] ?? ''));
$fim = trim((string)($options['fim'] ?? ''));

if ($uhInput === '') {
    fwrite(STDERR, "Uso: php tools/investigate_thematic_reservations.php --uh=2208,4002 [--inicio=2026-07-01 --fim=2026-07-04]\n");
    exit(2);
}

$uhs = array_values(array_unique(array_filter(array_map(
    static function (string $value): string {
        return preg_replace('/\D+/', '', trim($value)) ?? '';
    },
    explode(',', $uhInput)
))));
if ($uhs === []) {
    fwrite(STDERR, "Nenhuma UH valida foi informada.\n");
    exit(2);
}
if (($inicio === '') !== ($fim === '')) {
    fwrite(STDERR, "Informe --inicio e --fim juntos, ou omita ambos para pesquisar todo o historico.\n");
    exit(2);
}

$placeholders = [];
$params = [];
foreach ($uhs as $index => $uh) {
    $key = ':uh_' . $index;
    $placeholders[] = $key;
    $params[$key] = $uh;
}
$uhSql = implode(', ', $placeholders);
$dateSql = '';
if ($inicio !== '' && $fim !== '') {
    $dateSql = ' AND rsv.data_reserva BETWEEN :inicio AND :fim';
    $params[':inicio'] = $inicio;
    $params[':fim'] = $fim;
}

$printSection = static function (string $title): void {
    echo "\n== {$title} ==\n";
};
$printRows = static function (array $rows): void {
    if ($rows === []) {
        echo "Nenhum registro.\n";
        return;
    }
    foreach ($rows as $row) {
        echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
};

echo 'Banco: ' . (string)($config['db']['name'] ?? '') . PHP_EOL;
echo 'Servidor: ' . (string)($config['db']['host'] ?? '') . PHP_EOL;
echo 'UHs: ' . implode(', ', $uhs) . PHP_EOL;
echo 'Periodo da reserva: ' . ($inicio !== '' ? $inicio . ' a ' . $fim : 'todo o historico') . PHP_EOL;

$reservationSql = "
    SELECT
        rsv.id,
        uh.numero AS uh,
        rsv.data_reserva,
        r.nome AS restaurante,
        TIME_FORMAT(t.hora, '%H:%i') AS turno,
        rsv.titular_nome,
        rsv.pax,
        rsv.pax_adulto,
        rsv.pax_chd,
        rsv.status,
        criador.nome AS criado_por,
        rsv.criado_em,
        atualizador.nome AS atualizado_por,
        rsv.atualizado_em,
        (SELECT COUNT(*) FROM reservas_tematicas_logs l WHERE l.reserva_id = rsv.id) AS total_logs,
        (SELECT MIN(l.criado_em) FROM reservas_tematicas_logs l WHERE l.reserva_id = rsv.id) AS primeiro_log_em,
        (SELECT GROUP_CONCAT(DISTINCT l.acao ORDER BY l.acao SEPARATOR ',') FROM reservas_tematicas_logs l WHERE l.reserva_id = rsv.id) AS acoes
    FROM reservas_tematicas rsv
    JOIN unidades_habitacionais uh ON uh.id = rsv.uh_id
    JOIN restaurantes r ON r.id = rsv.restaurante_id
    JOIN reservas_tematicas_turnos t ON t.id = rsv.turno_id
    JOIN usuarios criador ON criador.id = rsv.usuario_id
    LEFT JOIN usuarios atualizador ON atualizador.id = rsv.atualizado_por
    WHERE uh.numero IN ({$uhSql})
    {$dateSql}
    ORDER BY rsv.data_reserva, t.hora, rsv.id
";
$stmt = $db->prepare($reservationSql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

$printSection('RESERVAS ATUAIS');
$printRows($reservations);

$reservationIds = array_map(static function (array $row): int {
    return (int)$row['id'];
}, $reservations);

// Inclui reservas cuja UH foi alterada e hoje ja nao corresponde ao numero pesquisado.
$historicalParams = [];
$historyClauses = [];
foreach ($uhs as $index => $uh) {
    $beforeKey = ':hist_before_' . $index;
    $afterKey = ':hist_after_' . $index;
    $historyClauses[] = "uh_antes.numero = {$beforeKey}";
    $historyClauses[] = "uh_depois.numero = {$afterKey}";
    $historicalParams[$beforeKey] = $uh;
    $historicalParams[$afterKey] = $uh;
}
$historyDateSql = '';
if ($inicio !== '' && $fim !== '') {
    $historyDateSql = ' AND rsv.data_reserva BETWEEN :hist_inicio AND :hist_fim';
    $historicalParams[':hist_inicio'] = $inicio;
    $historicalParams[':hist_fim'] = $fim;
}
$stmt = $db->prepare("
    SELECT DISTINCT l.reserva_id
    FROM reservas_tematicas_logs l
    JOIN reservas_tematicas rsv ON rsv.id = l.reserva_id
    LEFT JOIN unidades_habitacionais uh_antes
           ON uh_antes.id = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(l.dados_antes, '$.uh_id')), '') AS UNSIGNED)
    LEFT JOIN unidades_habitacionais uh_depois
           ON uh_depois.id = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(l.dados_depois, '$.uh_id')), '') AS UNSIGNED)
    WHERE (" . implode(' OR ', $historyClauses) . ")
    {$historyDateSql}
");
$stmt->execute($historicalParams);
foreach ($stmt->fetchAll() as $row) {
    $reservationIds[] = (int)$row['reserva_id'];
}
$reservationIds = array_values(array_unique(array_filter($reservationIds)));

$printSection('LINHA DO TEMPO DOS LOGS');
if ($reservationIds === []) {
    echo "Nenhuma reserva atual ou historica localizada.\n";
} else {
    $idParams = [];
    $idPlaceholders = [];
    foreach ($reservationIds as $index => $id) {
        $key = ':id_' . $index;
        $idPlaceholders[] = $key;
        $idParams[$key] = $id;
    }
    $stmt = $db->prepare("
        SELECT
            l.id AS log_id,
            l.reserva_id,
            l.criado_em AS evento_em,
            l.acao,
            u.nome AS usuario,
            l.justificativa,
            l.dados_antes,
            l.dados_depois
        FROM reservas_tematicas_logs l
        LEFT JOIN usuarios u ON u.id = l.usuario_id
        WHERE l.reserva_id IN (" . implode(', ', $idPlaceholders) . ")
        ORDER BY l.reserva_id, l.criado_em, l.id
    ");
    $stmt->execute($idParams);
    $printRows($stmt->fetchAll());

    $printSection('AUDITORIA GERAL RELACIONADA');
    $auditParams = [];
    $auditPlaceholders = [];
    foreach ($reservationIds as $index => $id) {
        $key = ':audit_id_' . $index;
        $auditPlaceholders[] = $key;
        $auditParams[$key] = $id;
    }
    $stmt = $db->prepare("
        SELECT a.id, a.criado_em, a.tabela, a.registro_id, a.acao, u.nome AS usuario, a.dados_antes, a.dados_depois
        FROM auditoria a
        LEFT JOIN usuarios u ON u.id = a.usuario_id
        WHERE a.registro_id IN (" . implode(', ', $auditPlaceholders) . ")
          AND a.tabela LIKE '%reserva%'
        ORDER BY a.criado_em, a.id
    ");
    $stmt->execute($auditParams);
    $printRows($stmt->fetchAll());
}

$printSection('INCONSISTENCIAS NO PERIODO');
$anomalyParams = [];
$anomalyDateSql = '';
if ($inicio !== '' && $fim !== '') {
    $anomalyDateSql = 'WHERE rsv.data_reserva BETWEEN :anomaly_inicio AND :anomaly_fim';
    $anomalyParams[':anomaly_inicio'] = $inicio;
    $anomalyParams[':anomaly_fim'] = $fim;
}
$stmt = $db->prepare("
    SELECT
        rsv.id,
        uh.numero AS uh,
        rsv.data_reserva,
        rsv.status,
        u.nome AS criado_por,
        rsv.criado_em,
        COUNT(l.id) AS total_logs,
        SUM(CASE WHEN l.acao IN ('create', 'create_pre_reservation') THEN 1 ELSE 0 END) AS logs_criacao
    FROM reservas_tematicas rsv
    JOIN unidades_habitacionais uh ON uh.id = rsv.uh_id
    JOIN usuarios u ON u.id = rsv.usuario_id
    LEFT JOIN reservas_tematicas_logs l ON l.reserva_id = rsv.id
    {$anomalyDateSql}
    GROUP BY rsv.id, uh.numero, rsv.data_reserva, rsv.status, u.nome, rsv.criado_em
    HAVING total_logs = 0 OR logs_criacao = 0
    ORDER BY rsv.data_reserva, rsv.id
");
$stmt->execute($anomalyParams);
$printRows($stmt->fetchAll());

echo "\nInvestigacao concluida sem alterar dados.\n";
