<?php
declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

require $root . '/app/bootstrap_cli.php';

$db = Database::getInstance();
$checks = [];
$warns = [];

$record = static function (string $name, bool $ok, string $detail = '') use (&$checks): void {
    $checks[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail];
};
$warn = static function (string $message) use (&$warns): void {
    $warns[] = $message;
};

$requiredIndexes = [
    'acessos' => [
        'idx_acessos_rest_oper_data' => ['restaurante_id', 'operacao_id', 'criado_em'],
        'idx_acessos_duplicate_scan' => ['uh_id', 'operacao_id', 'criado_em', 'pax'],
    ],
    'reservas_tematicas' => [
        'idx_res_tem_data_id' => ['data_reserva', 'id'],
        'idx_res_tem_rest_data_id' => ['restaurante_id', 'data_reserva', 'id'],
        'idx_res_tem_duplicate_lookup' => ['uh_id', 'data_reserva', 'turno_id', 'restaurante_id', 'status'],
    ],
];

try {
    $stmt = $db->query("
        SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME, SEQ_IN_INDEX
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
        ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX
    ");
    $actual = [];
    foreach ($stmt->fetchAll() as $row) {
        $actual[(string)$row['TABLE_NAME']][(string)$row['INDEX_NAME']][] = (string)$row['COLUMN_NAME'];
    }

    foreach ($requiredIndexes as $table => $indexes) {
        foreach ($indexes as $name => $columns) {
            $found = $actual[$table][$name] ?? [];
            $record(
                'index_' . $name,
                $found === $columns,
                $table . '(' . implode(',', $found ?: ['ausente']) . ')'
            );
        }
    }

    $plans = [
        'access_range' => "
            EXPLAIN SELECT a.id
            FROM acessos a
            WHERE a.criado_em >= '2000-01-01 00:00:00'
              AND a.criado_em < '2100-01-01 00:00:00'
            ORDER BY a.criado_em, a.id
            LIMIT 1000
        ",
        'voucher_range' => "
            EXPLAIN SELECT v.id
            FROM vouchers v
            WHERE v.criado_em >= '2000-01-01 00:00:00'
              AND v.criado_em < '2100-01-01 00:00:00'
            ORDER BY v.criado_em, v.id
            LIMIT 1000
        ",
        'thematic_range' => "
            EXPLAIN SELECT rsv.id
            FROM reservas_tematicas rsv
            WHERE rsv.data_reserva BETWEEN '2000-01-01' AND '2100-01-01'
            ORDER BY rsv.data_reserva, rsv.id
            LIMIT 1000
        ",
    ];

    foreach ($plans as $name => $sql) {
        $rows = $db->query($sql)->fetchAll();
        $plan = $rows[0] ?? [];
        $key = (string)($plan['key'] ?? '');
        $type = (string)($plan['type'] ?? '');
        $estimatedRows = (int)($plan['rows'] ?? 0);
        $record('explain_' . $name, !empty($rows), "type={$type} key=" . ($key !== '' ? $key : '-') . " rows={$estimatedRows}");
        if ($type === 'ALL' && $estimatedRows > 1000) {
            $warn("{$name} ainda estima varredura completa de {$estimatedRows} linhas.");
        }
    }

    $exportChecks = [
        'access_export_cursor' => [new AccessModel(), 'reportListCount', 'exportReportRows'],
        'voucher_export_cursor' => [new VoucherModel(), 'countByFilters', 'exportByFilters'],
        'collaborator_export_cursor' => [new CollaboratorMealModel(), 'countByFilters', 'exportByFilters'],
        'thematic_export_cursor' => [new ReservaTematicaModel(), 'countByFilters', 'exportByFilters'],
    ];

    foreach ($exportChecks as $name => [$model, $countMethod, $exportMethod]) {
        $expected = (int)$model->{$countMethod}([]);
        $seen = 0;
        $processed = (int)$model->{$exportMethod}([], static function () use (&$seen): void {
            $seen++;
        }, 1000);
        $record($name, $expected === $seen && $seen === $processed, "esperado={$expected} processado={$processed}");
    }
} catch (Throwable $e) {
    $record('fatal', false, $e->getMessage());
}

foreach ($checks as $check) {
    echo ($check['ok'] ? '[OK] ' : '[FAIL] ') . $check['name'];
    if ($check['detail'] !== '') {
        echo ' - ' . $check['detail'];
    }
    echo PHP_EOL;
}
foreach ($warns as $message) {
    echo '[WARN] ' . $message . PHP_EOL;
}

$failed = array_values(array_filter($checks, static fn (array $check): bool => !$check['ok']));
echo PHP_EOL . 'Resultado: ' . (count($failed) === 0 ? 'OK' : 'FALHOU') . PHP_EOL;
exit(count($failed) === 0 ? 0 : 1);
