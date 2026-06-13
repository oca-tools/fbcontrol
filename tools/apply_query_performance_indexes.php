<?php
declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

require $root . '/app/bootstrap_cli.php';

$db = Database::getInstance();
$indexes = [
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

$obsoleteIndexes = [
    'acessos' => ['idx_acessos_data_id', 'idx_acessos_rest_oper_data_id'],
    'vouchers' => ['idx_vouchers_data_id', 'idx_vouchers_rest_oper_data_id'],
    'colaborador_refeicoes' => ['idx_colab_data_id', 'idx_colab_rest_oper_data_id'],
];

$existing = [];
$stmt = $db->query("
    SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME, SEQ_IN_INDEX
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX
");
foreach ($stmt->fetchAll() as $row) {
    $existing[(string)$row['TABLE_NAME']][(string)$row['INDEX_NAME']][] = (string)$row['COLUMN_NAME'];
}

foreach ($obsoleteIndexes as $table => $names) {
    foreach ($names as $name) {
        if (empty($existing[$table][$name])) {
            continue;
        }
        $db->exec(sprintf(
            'ALTER TABLE `%s` DROP INDEX `%s`',
            str_replace('`', '``', $table),
            str_replace('`', '``', $name)
        ));
        unset($existing[$table][$name]);
        echo "[DROP] {$table}.{$name}" . PHP_EOL;
    }
}

foreach ($indexes as $table => $tableIndexes) {
    foreach ($tableIndexes as $name => $columns) {
        $currentColumns = $existing[$table][$name] ?? [];
        if ($currentColumns === $columns) {
            echo "[SKIP] {$table}.{$name}" . PHP_EOL;
            continue;
        }
        if ($currentColumns) {
            $db->exec(sprintf(
                'ALTER TABLE `%s` DROP INDEX `%s`',
                str_replace('`', '``', $table),
                str_replace('`', '``', $name)
            ));
        }

        $quotedColumns = array_map(
            static fn (string $column): string => '`' . str_replace('`', '``', $column) . '`',
            $columns
        );
        $sql = sprintf(
            'ALTER TABLE `%s` ADD INDEX `%s` (%s)',
            str_replace('`', '``', $table),
            str_replace('`', '``', $name),
            implode(', ', $quotedColumns)
        );
        $db->exec($sql);
        echo "[OK] {$table}.{$name}" . PHP_EOL;
    }
}

echo PHP_EOL . 'Indices de desempenho aplicados.' . PHP_EOL;
