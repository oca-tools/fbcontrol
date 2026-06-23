<?php
declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

require $root . '/app/bootstrap_cli.php';

$db = Database::getInstance();
$checks = [];

$record = static function (string $name, bool $ok, string $detail = '') use (&$checks): void {
    $checks[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail];
};

$invokePrivate = static function (object $object, string $method, array $args = []) {
    $reflection = new ReflectionMethod($object, $method);
    $reflection->setAccessible(true);
    return $reflection->invokeArgs($object, $args);
};

$temporaryTables = [
    'acessos',
    'unidades_habitacionais',
    'reservas_tematicas',
    'turnos',
    'restaurante_operacoes',
    'reservas_tematicas_logs',
    'turnos_especiais',
    'restaurante_especiais',
    'acessos_especiais',
];

try {
    $db->exec("
        CREATE TEMPORARY TABLE unidades_habitacionais (
            id INT PRIMARY KEY,
            numero VARCHAR(20) NOT NULL
        )
    ");
    $db->exec("
        CREATE TEMPORARY TABLE acessos (
            id INT PRIMARY KEY,
            turno_id INT NULL,
            uh_id INT NOT NULL,
            pax INT NOT NULL,
            restaurante_id INT NOT NULL,
            operacao_id INT NOT NULL,
            alerta_duplicidade TINYINT NOT NULL DEFAULT 0,
            fora_do_horario TINYINT NOT NULL DEFAULT 0,
            criado_em DATETIME NOT NULL,
            usuario_id INT NOT NULL
        )
    ");
    $db->exec("
        INSERT INTO unidades_habitacionais (id, numero)
        VALUES (1, '401'), (2, '998'), (3, '999')
    ");

    $accessModel = new AccessModel();
    $checkDuplicidade = static function (
        int $minutesAgo,
        int $storedPax,
        int $requestedPax
    ) use ($db, $accessModel, $invokePrivate): bool {
        $db->exec('TRUNCATE TABLE acessos');
        $stmt = $db->prepare("
            INSERT INTO acessos
                (id, turno_id, uh_id, pax, restaurante_id, operacao_id, criado_em, usuario_id)
            VALUES
                (1, 10, 1, :pax, 20, 30, DATE_SUB(NOW(), INTERVAL :minutes MINUTE), 40)
        ");
        $stmt->bindValue(':pax', $storedPax, PDO::PARAM_INT);
        $stmt->bindValue(':minutes', $minutesAgo, PDO::PARAM_INT);
        $stmt->execute();
        return (bool)$invokePrivate($accessModel, 'checkDuplicidade', [1, 20, 30, $requestedPax]);
    };

    $record(
        'duplicate_exact_inside_10_minutes',
        $checkDuplicidade(9, 4, 4),
        'mesma UH, restaurante, operacao e PAX'
    );
    $record(
        'duplicate_exact_after_10_minutes',
        !$checkDuplicidade(11, 4, 4),
        'fora da janela nao deve ser duplicidade imediata'
    );
    $record(
        'duplicate_requires_same_pax',
        !$checkDuplicidade(5, 4, 5),
        'PAX diferente segue a regra de multiplo acesso'
    );

    $db->exec('TRUNCATE TABLE acessos');
    $db->exec("
        INSERT INTO acessos
            (id, turno_id, uh_id, pax, restaurante_id, operacao_id, criado_em, usuario_id)
        VALUES
            (1, 10, 1, 2, 20, 30, '2026-06-10 12:00:00', 40),
            (2, 10, 1, 3, 20, 30, '2026-06-10 12:15:00', 40),
            (3, 10, 1, 4, 20, 30, '2026-06-10 12:16:00', 40),
            (4, 10, 2, 5, 20, 30, '2026-06-10 13:00:00', 40),
            (5, 10, 2, 6, 20, 30, '2026-06-10 13:30:00', 40)
    ");
    $multipleSql = (string)$invokePrivate($accessModel, 'multipleAccessExistsSql', ['a']);
    $multipleRows = $db->query("
        SELECT a.id, CASE WHEN {$multipleSql} THEN 1 ELSE 0 END AS multiplo
        FROM acessos a
        ORDER BY a.id
    ")->fetchAll(PDO::FETCH_KEY_PAIR);

    $record(
        'multiple_access_only_after_15_minutes',
        (int)($multipleRows[1] ?? -1) === 0
            && (int)($multipleRows[2] ?? -1) === 0
            && (int)($multipleRows[3] ?? -1) === 1,
        'somente o acesso posterior a 15 minutos e marcado'
    );
    $record(
        'multiple_access_technical_uh_excluded',
        (int)($multipleRows[4] ?? -1) === 0 && (int)($multipleRows[5] ?? -1) === 0,
        'UH 998/999 nao gera multiplo acesso'
    );

    $record(
        'immediate_duplicate_technical_uh_excluded',
        !$accessModel->hasImmediateExactDuplicate('998', 20, 30, 5, 10, 40),
        'UH tecnica nao exige confirmacao de duplicidade'
    );

    $db->exec("
        CREATE TEMPORARY TABLE reservas_tematicas (
            id INT PRIMARY KEY,
            uh_id INT NOT NULL,
            restaurante_id INT NOT NULL,
            data_reserva DATE NOT NULL,
            turno_id INT NOT NULL,
            pax INT NOT NULL,
            pax_real INT NULL,
            status VARCHAR(30) NOT NULL,
            usuario_id INT NOT NULL
        )
    ");
    $db->exec("
        INSERT INTO reservas_tematicas
            (id, uh_id, restaurante_id, data_reserva, turno_id, pax, pax_real, status, usuario_id)
        VALUES
            (1, 1, 20, '2026-06-10', 50, 4, NULL, 'Reservada', 70),
            (2, 2, 20, '2026-06-10', 50, 6, 5, 'Finalizada', 71),
            (3, 3, 20, '2026-06-10', 50, 8, NULL, 'Cancelada', 72)
    ");

    $reservationModel = new ReservaTematicaModel();
    $record(
        'capacity_uses_real_pax_and_ignores_cancelled',
        $reservationModel->sumPax(20, '2026-06-10', 50) === 9,
        '4 reservados + 5 reais; cancelada fora do total'
    );
    $record(
        'reservation_duplicate_ignores_cancelled',
        $reservationModel->hasDuplicateUh(1, '2026-06-10', 50, 20)
            && !$reservationModel->hasDuplicateUh(3, '2026-06-10', 50, 20),
        'somente reserva ativa bloqueia a mesma UH/turno'
    );

    $record(
        'hostess_edits_own_reservation',
        ReservaTematicaPolicy::canEdit(['usuario_id' => 70], ['id' => 70, 'perfil' => 'hostess']),
        'autoria individual preservada'
    );
    $record(
        'hostess_cannot_edit_other_reservation',
        !ReservaTematicaPolicy::canEdit(['usuario_id' => 70], ['id' => 71, 'perfil' => 'hostess']),
        'hostess nao altera reserva de outra pessoa'
    );
    $record(
        'management_can_edit_any_reservation',
        ReservaTematicaPolicy::canEdit(['usuario_id' => 70], ['id' => 99, 'perfil' => 'supervisor'])
            && ReservaTematicaPolicy::canEdit(['usuario_id' => 70], ['id' => 99, 'perfil' => 'gerente'])
            && ReservaTematicaPolicy::canEdit(['usuario_id' => 70], ['id' => 99, 'perfil' => 'admin']),
        'hierarquia administrativa mantida'
    );
    $record(
        'chd_age_parser_accepts_operational_format',
        ReservaTematicaPolicy::parseChdAges('1y2y4y') === [1, 2, 4],
        'formato documentado preservado'
    );
    $record(
        'chd_age_parser_accepts_months',
        ReservaTematicaPolicy::parseChdAgeEntries('3m9m6m') === [
            ['idade' => 0, 'label' => '3m', 'unit' => 'm'],
            ['idade' => 0, 'label' => '9m', 'unit' => 'm'],
            ['idade' => 0, 'label' => '6m', 'unit' => 'm'],
        ],
        'meses preservados para impressao operacional'
    );
    $invalidAgeRejected = false;
    try {
        ReservaTematicaPolicy::parseChdAges('18y');
    } catch (RuntimeException $e) {
        $invalidAgeRejected = true;
    }
    $record('chd_age_parser_rejects_invalid_age', $invalidAgeRejected, 'limite de 0 a 17 anos');
    $record(
        'tematic_restaurant_classification',
        TematicAccessService::isTematicRestaurant('Restaurante Giardino')
            && TematicAccessService::isTematicRestaurant("Restaurante IX'U")
            && TematicAccessService::isTematicRestaurant('Restaurante La Brasa')
            && !TematicAccessService::isTematicRestaurant('Restaurante Corais'),
        'classificacao unica dos restaurantes'
    );
    $record(
        'la_brasa_requires_tematic_operation',
        TematicAccessService::isTematicShift([
            'restaurante' => 'Restaurante La Brasa',
            'operacao' => 'Temático',
        ]) && !TematicAccessService::isTematicShift([
            'restaurante' => 'Restaurante La Brasa',
            'operacao' => 'Almoço',
        ]),
        'almoco La Brasa continua fora do fluxo tematico'
    );

    $db->exec("
        CREATE TEMPORARY TABLE turnos (
            id INT PRIMARY KEY,
            usuario_id INT NOT NULL,
            restaurante_id INT NOT NULL,
            operacao_id INT NOT NULL,
            inicio_em DATETIME NOT NULL,
            fim_em DATETIME NULL,
            modo_demo TINYINT NOT NULL DEFAULT 0
        )
    ");
    $db->exec("
        CREATE TEMPORARY TABLE restaurante_operacoes (
            restaurante_id INT NOT NULL,
            operacao_id INT NOT NULL,
            hora_inicio TIME NOT NULL,
            hora_fim TIME NOT NULL,
            tolerancia_min INT NOT NULL DEFAULT 0,
            ativo TINYINT NOT NULL DEFAULT 1
        )
    ");
    $db->exec("
        CREATE TEMPORARY TABLE reservas_tematicas_logs (
            usuario_id INT NOT NULL,
            acao VARCHAR(30) NOT NULL,
            criado_em DATETIME NOT NULL
        )
    ");
    $db->exec("
        INSERT INTO restaurante_operacoes
            (restaurante_id, operacao_id, hora_inicio, hora_fim, tolerancia_min, ativo)
        VALUES (20, 30, '00:00:00', '23:59:59', 0, 1)
    ");
    $db->exec("
        INSERT INTO turnos
            (id, usuario_id, restaurante_id, operacao_id, inicio_em, fim_em, modo_demo)
        VALUES
            (1, 40, 20, 30, DATE_SUB(NOW(), INTERVAL 31 MINUTE), NULL, 0),
            (2, 41, 20, 30, DATE_SUB(NOW(), INTERVAL 90 MINUTE), NULL, 1)
    ");
    $expiredRegular = (new ShiftModel())->findExpiredActive(10);
    $expiredRegularIds = array_map(static fn(array $row): int => (int)$row['id'], $expiredRegular);
    $record(
        'regular_shift_idle_closes_after_30_minutes',
        in_array(1, $expiredRegularIds, true),
        'turno real sem lancamento expira'
    );
    $record(
        'regular_demo_shift_never_auto_closes',
        !in_array(2, $expiredRegularIds, true),
        'modo demo ignora encerramento automatico'
    );

    $db->exec("
        CREATE TEMPORARY TABLE turnos_especiais (
            id INT PRIMARY KEY,
            usuario_id INT NOT NULL,
            restaurante_id INT NOT NULL,
            tipo VARCHAR(30) NOT NULL,
            inicio_em DATETIME NOT NULL,
            fim_em DATETIME NULL,
            modo_demo TINYINT NOT NULL DEFAULT 0
        )
    ");
    $db->exec("
        CREATE TEMPORARY TABLE restaurante_especiais (
            restaurante_id INT NOT NULL,
            tipo VARCHAR(30) NOT NULL,
            hora_inicio TIME NOT NULL,
            hora_fim TIME NOT NULL,
            tolerancia_min INT NOT NULL DEFAULT 0,
            ativo TINYINT NOT NULL DEFAULT 1
        )
    ");
    $db->exec("
        CREATE TEMPORARY TABLE acessos_especiais (
            id INT PRIMARY KEY,
            turno_especial_id INT NOT NULL
        )
    ");
    $db->exec("
        INSERT INTO restaurante_especiais
            (restaurante_id, tipo, hora_inicio, hora_fim, tolerancia_min, ativo)
        VALUES (20, 'Tematico', '00:00:00', '23:59:59', 0, 1)
    ");
    $db->exec("
        INSERT INTO turnos_especiais
            (id, usuario_id, restaurante_id, tipo, inicio_em, fim_em, modo_demo)
        VALUES
            (1, 40, 20, 'Tematico', DATE_SUB(NOW(), INTERVAL 31 MINUTE), NULL, 0),
            (2, 41, 20, 'Tematico', DATE_SUB(NOW(), INTERVAL 90 MINUTE), NULL, 1)
    ");
    $expiredSpecial = (new SpecialShiftModel())->findExpiredActive(10);
    $expiredSpecialIds = array_map(static fn(array $row): int => (int)$row['id'], $expiredSpecial);
    $record(
        'special_shift_idle_closes_after_30_minutes',
        in_array(1, $expiredSpecialIds, true),
        'turno tematico real sem lancamento expira'
    );
    $record(
        'special_demo_shift_never_auto_closes',
        !in_array(2, $expiredSpecialIds, true),
        'modo demo tambem protege o tematico'
    );
    $record(
        'auto_close_service_skips_demo_without_queries',
        (new ShiftAutoCloseService())->closeForUser(40, true) === 0,
        'atalho central impede qualquer encerramento em demonstracao'
    );
} catch (Throwable $e) {
    $record('fatal', false, $e->getMessage());
} finally {
    foreach ($temporaryTables as $table) {
        try {
            $db->exec("DROP TEMPORARY TABLE IF EXISTS {$table}");
        } catch (Throwable $ignored) {
        }
    }
}

$failed = array_values(array_filter($checks, static fn(array $check): bool => !$check['ok']));
foreach ($checks as $check) {
    echo ($check['ok'] ? '[OK] ' : '[FAIL] ') . $check['name'];
    if ($check['detail'] !== '') {
        echo ' - ' . $check['detail'];
    }
    echo PHP_EOL;
}

echo PHP_EOL . 'Resultado: ' . (count($failed) === 0 ? 'OK' : 'FALHOU') . PHP_EOL;
exit(count($failed) === 0 ? 0 : 1);
