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
    WHERE ct.capacidade >= 8
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
    $hostess = array_merge($usuario, ['perfil' => AppConstants::ROLE_HOSTESS]);
    $outraHostess = array_merge($hostess, ['id' => (int)$usuario['id'] + 999]);
    $supervisao = array_merge($usuario, ['perfil' => AppConstants::ROLE_SUPERVISOR]);
    $gerencia = array_merge($usuario, ['perfil' => AppConstants::ROLE_MANAGER]);

    $preReservaHostess = $service->executar(new CriarReservaCommand(array_merge($base, [
        'acao' => ReservasTematicasConstants::ACTION_CREATE_PRE_RESERVATION,
        'usuario' => $hostess,
        'uh_numero' => '',
        'titular_nome' => 'Teste pré-reserva sem permissão',
        'pax' => 1,
    ])));
    if ($preReservaHostess->isSuccess() || $preReservaHostess->code() !== ReservasTematicasConstants::CODE_PRE_RESERVA_NAO_AUTORIZADA) {
        throw new RuntimeException('Hostess conseguiu criar pré-reserva indevidamente.');
    }

    $preReserva = $service->executar(new CriarReservaCommand(array_merge($base, [
        'acao' => ReservasTematicasConstants::ACTION_CREATE_PRE_RESERVATION,
        'usuario' => $supervisao,
        'uh_numero' => '',
        'titular_nome' => 'Teste pré-reserva supervisionada',
        'pax' => 1,
    ])));
    $preReservaId = (int)($preReserva->payload()['reserva_id'] ?? 0);
    $preReservaRow = $repository->buscarReserva($preReservaId);
    $uhTecnica = $preReservaRow ? (new UnitRepository())->buscarUhPorId((int)$preReservaRow['uh_id']) : null;
    if (!$preReserva->isSuccess() || !$preReservaRow || (string)$preReservaRow['status'] !== ReservasTematicasConstants::STATUS_PRE_RESERVA || (string)($uhTecnica['numero'] ?? '') !== '998') {
        throw new RuntimeException('Pré-reserva supervisionada não foi registrada sem UH operacional.');
    }

    $operacaoService = new OperarReservaService($repository, new UnitRepository());
    $conclusaoPreReserva = $operacaoService->executar(new OperarReservaCommand([
        'acao' => ReservasTematicasConstants::ACTION_UPDATE_DETAIL,
        'usuario_id' => (int)$usuario['id'],
        'usuario' => $gerencia,
        'restaurantes_permitidos' => $base['restaurantes_permitidos'],
        'turnos_permitidos' => [['id' => (int)$config['turno_id']]],
        'reserva_id' => $preReservaId,
        'restaurante_id' => (int)$config['restaurante_id'],
        'turno_id' => (int)$config['turno_id'],
        'data_reserva' => $dataReserva,
        'uh_numero' => '309',
        'status' => ReservasTematicasConstants::STATUS_PRE_RESERVA,
        'pax_real' => '',
    ]));
    $preReservaConcluida = $repository->buscarReserva($preReservaId);
    $uhConcluida = $preReservaConcluida ? (new UnitRepository())->buscarUhPorId((int)$preReservaConcluida['uh_id']) : null;
    if (!$conclusaoPreReserva->isSuccess() || (string)($uhConcluida['numero'] ?? '') !== '309' || (string)($preReservaConcluida['status'] ?? '') !== ReservasTematicasConstants::STATUS_RESERVADA) {
        throw new RuntimeException('Gerência não conseguiu concluir a pré-reserva informando a UH 309.');
    }
    $logConclusao = $db->prepare("SELECT COUNT(*) FROM reservas_tematicas_logs WHERE reserva_id = :id AND acao = :acao");
    $logConclusao->execute([':id' => $preReservaId, ':acao' => ReservasTematicasConstants::ACTION_UPDATE_DETAIL]);
    if ((int)$logConclusao->fetchColumn() < 1) {
        throw new RuntimeException('Conclusão da pré-reserva não gerou auditoria.');
    }

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
    $individualId = (int)$individual->payload()['reserva_id'];
    $edicaoGerencial = $service->executar(new CriarReservaCommand(array_merge($base, [
        'acao' => ReservasTematicasConstants::ACTION_UPDATE,
        'usuario' => $gerencia,
        'reserva_id' => $individualId,
        'uh_numero' => '311',
        'titular_nome' => 'Teste edição gerencial',
        'pax' => 1,
    ])));
    $individualEditada = $repository->buscarReserva($individualId);
    $uhEditada = $individualEditada ? (new UnitRepository())->buscarUhPorId((int)$individualEditada['uh_id']) : null;
    if (!$edicaoGerencial->isSuccess() || (string)($uhEditada['numero'] ?? '') !== '311') {
        throw new RuntimeException('Gerência não conseguiu alterar a UH pelo módulo de reservas.');
    }
    $edicaoHostess = $operacaoService->executar(new OperarReservaCommand([
        'acao' => ReservasTematicasConstants::ACTION_UPDATE_DETAIL,
        'usuario_id' => (int)$usuario['id'],
        'usuario' => $hostess,
        'restaurantes_permitidos' => $base['restaurantes_permitidos'],
        'turnos_permitidos' => [['id' => (int)$config['turno_id']]],
        'reserva_id' => $individualId,
        'restaurante_id' => (int)$config['restaurante_id'],
        'turno_id' => (int)$config['turno_id'],
        'data_reserva' => $dataReserva,
        'uh_numero' => '310',
        'status' => ReservasTematicasConstants::STATUS_RESERVADA,
        'pax_real' => '',
    ]));
    $individualAposHostess = $repository->buscarReserva($individualId);
    $uhAposHostess = $individualAposHostess ? (new UnitRepository())->buscarUhPorId((int)$individualAposHostess['uh_id']) : null;
    if (!$edicaoHostess->isSuccess() || (string)($uhAposHostess['numero'] ?? '') !== '310') {
        throw new RuntimeException('Hostess autora não conseguiu alterar a UH da própria reserva.');
    }
    $logHostess = $db->prepare("SELECT COUNT(*) FROM reservas_tematicas_logs WHERE reserva_id = :id AND acao = :acao AND usuario_id = :usuario_id");
    $logHostess->execute([':id' => $individualId, ':acao' => ReservasTematicasConstants::ACTION_UPDATE_DETAIL, ':usuario_id' => (int)$usuario['id']]);
    if ((int)$logHostess->fetchColumn() < 1) {
        throw new RuntimeException('Alteração de UH pela hostess autora não gerou auditoria.');
    }

    $edicaoOutraHostess = $operacaoService->executar(new OperarReservaCommand([
        'acao' => ReservasTematicasConstants::ACTION_UPDATE_DETAIL,
        'usuario_id' => (int)$outraHostess['id'],
        'usuario' => $outraHostess,
        'restaurantes_permitidos' => $base['restaurantes_permitidos'],
        'turnos_permitidos' => [['id' => (int)$config['turno_id']]],
        'reserva_id' => $individualId,
        'restaurante_id' => (int)$config['restaurante_id'],
        'turno_id' => (int)$config['turno_id'],
        'data_reserva' => $dataReserva,
        'uh_numero' => '312',
        'status' => ReservasTematicasConstants::STATUS_RESERVADA,
        'pax_real' => '',
    ]));
    if ($edicaoOutraHostess->isSuccess() || $edicaoOutraHostess->code() !== ReservasTematicasConstants::CODE_UH_EDICAO_NAO_AUTORIZADA) {
        throw new RuntimeException('Hostess alterou UH de uma reserva criada por outra usuária.');
    }

    foreach (['306', '308'] as $uhHistorica) {
        $reservaHistorica = $service->executar(new CriarReservaCommand($base + [
            'acao' => ReservasTematicasConstants::ACTION_CREATE,
            'uh_numero' => $uhHistorica,
            'titular_nome' => 'Teste faixa histórica 300',
            'pax' => 1,
        ]));
        if (!$reservaHistorica->isSuccess() || (int)($reservaHistorica->payload()['reserva_id'] ?? 0) <= 0) {
            throw new RuntimeException('Reserva válida da UH ' . $uhHistorica . ' falhou: ' . $reservaHistorica->message());
        }
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

echo '[OK] Fluxo completo: pré-reserva supervisionada; hostess autora altera UH com auditoria; outra hostess é bloqueada.' . PHP_EOL;
