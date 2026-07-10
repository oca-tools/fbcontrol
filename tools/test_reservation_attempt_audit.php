<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap_cli.php';

$db = Database::getInstance();
$assertions = [];
$failed = false;

$assert = static function (bool $condition, string $label) use (&$assertions, &$failed): void {
    $assertions[] = ($condition ? '[OK] ' : '[FAIL] ') . $label;
    $failed = $failed || !$condition;
};

$user = $db->query("SELECT id, nome, perfil FROM usuarios WHERE ativo = 1 ORDER BY (perfil = 'admin') DESC, id LIMIT 1")->fetch();
$restaurant = $db->query("SELECT id, nome FROM restaurantes WHERE ativo = 1 ORDER BY id LIMIT 1")->fetch();
$shift = $db->query("SELECT id, hora FROM reservas_tematicas_turnos ORDER BY id LIMIT 1")->fetch();

if (!$user || !$restaurant || !$shift) {
    fwrite(STDERR, "[FAIL] Dados mínimos indisponíveis para testar auditoria de tentativas.\n");
    exit(1);
}

$correlationId = 'test-attempt-' . bin2hex(random_bytes(6));
$command = new CriarReservaCommand([
    'acao' => ReservasTematicasConstants::ACTION_CREATE,
    'usuario_id' => (int)$user['id'],
    'usuario' => $user,
    'restaurantes_permitidos' => [$restaurant],
    'restaurante_id' => (int)$restaurant['id'],
    'data_reserva' => date('Y-m-d'),
    'turno_id' => (int)$shift['id'],
    'uh_numero' => '3200',
    'titular_nome' => 'Teste de rastreabilidade',
    'pax' => 4,
    'correlation_id' => $correlationId,
]);

$db->beginTransaction();
try {
    $service = new RegistrarTentativaReservaTematicaService();
    $service->registrarInicio($command);
    $service->registrarRecusa(
        $command,
        ServiceResult::failure('teste_recusa', 'Recusa controlada para validação.', [
            'pax_disponivel' => 2,
            'pax_tentativa' => 4,
            'pax_reservado' => 14,
            'capacidade' => 16,
        ]),
        'Recusa controlada para validação.'
    );

    $stmt = $db->prepare("
        SELECT acao, usuario_id, dados_depois
          FROM auditoria
         WHERE tabela = :tabela
           AND dados_depois LIKE :correlation
         ORDER BY id
    ");
    $stmt->execute([
        ':tabela' => ReservasTematicasConstants::AUDIT_TABLE_ATTEMPTS,
        ':correlation' => '%"' . $correlationId . '"%',
    ]);
    $rows = $stmt->fetchAll();

    $assert(count($rows) === 2, 'tentativa recebida e recusada foram persistidas');
    $assert(($rows[0]['acao'] ?? '') === ReservasTematicasConstants::AUDIT_ACTION_ATTEMPT_STARTED, 'evento inicial identificado');
    $assert(($rows[1]['acao'] ?? '') === ReservasTematicasConstants::AUDIT_ACTION_ATTEMPT_REJECTED, 'evento de recusa identificado');

    $payload = json_decode((string)($rows[1]['dados_depois'] ?? ''), true);
    $assert(is_array($payload), 'payload de auditoria é JSON válido');
    $assert(($payload['correlation_id'] ?? '') === $correlationId, 'referência correlaciona toda a tentativa');
    $assert(($payload['uhs'][0] ?? '') === '3200', 'UH tentada preservada');
    $assert((int)($payload['pax_total_tentado'] ?? 0) === 4, 'PAX tentado preservado');
    $assert(($payload['motivo'] ?? '') === 'Recusa controlada para validação.', 'motivo da recusa preservado');
    $assert((int)($payload['pax_disponivel'] ?? -1) === 2, 'capacidade disponível preservada');

    $panel = (new AuditLogModel())->reservationAttemptLogsPage([
        'data' => date('Y-m-d'),
        'data_inicio' => '',
        'data_fim' => '',
        'usuario_id' => (int)$user['id'],
        'tabela' => '',
        'uh_numero' => '3200',
    ], 50, 0);
    $panelRows = array_values(array_filter($panel['rows'] ?? [], static function (array $row) use ($correlationId): bool {
        return strpos((string)($row['dados_depois'] ?? ''), $correlationId) !== false;
    }));
    $assert(count($panelRows) === 1, 'painel consolida a tentativa em uma única linha');
    $assert(($panelRows[0]['acao'] ?? '') === ReservasTematicasConstants::AUDIT_ACTION_ATTEMPT_REJECTED, 'painel exibe o desfecho final da tentativa');
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
}

echo implode(PHP_EOL, $assertions) . PHP_EOL;
echo PHP_EOL . 'Resultado: ' . ($failed ? 'FALHOU' : 'OK') . PHP_EOL;
exit($failed ? 1 : 0);
