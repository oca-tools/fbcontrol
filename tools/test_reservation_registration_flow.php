<?php
declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);
require $root . '/app/bootstrap_cli.php';

$db = Database::getInstance();
$repository = new ReservaTematicaRepository();
$service = new CriarReservaService($repository, new UnitRepository());

$config = $db->query("
    SELECT ct.restaurante_id, ct.turno_id, ct.capacidade, r.nome
    FROM reservas_tematicas_config_turnos ct
    JOIN reservas_tematicas_config cfg ON cfg.restaurante_id = ct.restaurante_id AND cfg.ativo = 1
    JOIN restaurantes r ON r.id = ct.restaurante_id AND r.ativo = 1
    JOIN reservas_tematicas_turnos t ON t.id = ct.turno_id AND t.ativo = 1
    WHERE ct.capacidade >= 3
    ORDER BY ct.capacidade DESC, ct.restaurante_id, ct.turno_id
    LIMIT 1
")->fetch();
$usuario = $db->query("
    SELECT id, nome, email, perfil, ativo
    FROM usuarios
    WHERE ativo = 1
    ORDER BY (perfil = 'admin') DESC, id
    LIMIT 1
")->fetch();

if (!$config || !$usuario) {
    fwrite(STDERR, '[FAIL] Ambiente sem restaurante/turno configurado ou usuário ativo para testar reservas.' . PHP_EOL);
    exit(1);
}

$data = new DateTimeImmutable('2098-01-01');
for ($tentativa = 0; $tentativa < 60; $tentativa++) {
    $dataReserva = $data->modify('+' . $tentativa . ' days')->format('Y-m-d');
    if (!$repository->restauranteFechadoNaData((int)$config['restaurante_id'], $dataReserva)) {
        break;
    }
}
if (!isset($dataReserva) || $repository->restauranteFechadoNaData((int)$config['restaurante_id'], $dataReserva)) {
    fwrite(STDERR, '[FAIL] Não foi encontrada uma data aberta para o teste de reservas.' . PHP_EOL);
    exit(1);
}

$base = [
    'usuario_id' => (int)$usuario['id'],
    'usuario' => $usuario,
    'hostess_fora_da_janela' => false,
    'restaurantes_permitidos' => [[
        'id' => (int)$config['restaurante_id'],
        'nome' => (string)$config['nome'],
    ]],
    'restaurante_id' => (int)$config['restaurante_id'],
    'data_reserva' => $dataReserva,
    'turno_id' => (int)$config['turno_id'],
    'observacao_reserva' => '',
    'observacao_tags' => [],
];

$db->beginTransaction();
try {
    $invalida = $service->executar(new CriarReservaCommand($base + [
        'action' => ReservasTematicasConstants::ACTION_CREATE,
        'acao' => ReservasTematicasConstants::ACTION_CREATE,
        'uh_numero' => '342',
        'titular_nome' => 'Teste UH inválida',
        'pax' => 1,
    ]));
    if ($invalida->isSuccess() || $invalida->code() !== ReservasTematicasConstants::CODE_UH_INVALIDA || strpos($invalida->message(), '342') === false) {
        throw new RuntimeException('Erro de UH inválida não informou a UH 342.');
    }

    $individual = $service->executar(new CriarReservaCommand($base + [
        'acao' => ReservasTematicasConstants::ACTION_CREATE,
        'uh_numero' => '3200',
        'titular_nome' => 'Teste reserva individual',
        'pax' => 1,
    ]));
    if (!$individual->isSuccess() || (int)($individual->payload()['reserva_id'] ?? 0) <= 0) {
        throw new RuntimeException('Reserva válida da UH 3200 falhou: ' . $individual->message());
    }

    $grupoInvalido = $service->executar(new CriarReservaCommand($base + [
        'acao' => ReservasTematicasConstants::ACTION_CREATE_BATCH,
        'grupo_responsavel' => 'Teste grupo inválido',
        'batch_uh_numero' => ['3201', '3502'],
        'batch_pax' => [1, 1],
        'batch_chd_idades' => ['', ''],
    ]));
    if ($grupoInvalido->isSuccess() || $grupoInvalido->code() !== ReservasTematicasConstants::CODE_UH_GRUPO_INVALIDA || strpos($grupoInvalido->message(), '3502') === false) {
        throw new RuntimeException('Erro de grupo não informou a UH inválida 3502.');
    }

    $grupo = $service->executar(new CriarReservaCommand($base + [
        'acao' => ReservasTematicasConstants::ACTION_CREATE_BATCH,
        'grupo_responsavel' => 'Teste reserva em grupo',
        'batch_uh_numero' => ['3201', '3202'],
        'batch_pax' => [1, 1],
        'batch_chd_idades' => ['', ''],
    ]));
    if (!$grupo->isSuccess() || count($grupo->payload()['reservas_ids'] ?? []) !== 2) {
        throw new RuntimeException('Reserva válida em grupo falhou: ' . $grupo->message());
    }

    $db->rollBack();
} catch (Throwable $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, '[FAIL] ' . $error->getMessage() . PHP_EOL);
    exit(1);
}

echo '[OK] Fluxo completo: UH inválida contextualizada; reservas individual e em grupo gravadas atomicamente e revertidas.' . PHP_EOL;
