<?php
declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);
require $root . '/app/bootstrap_cli.php';

$db = Database::getInstance();
$restaurantId = (int)$db->query("SELECT id FROM restaurantes WHERE ativo = 1 ORDER BY id LIMIT 1")->fetchColumn();
$userId = (int)$db->query("SELECT id FROM usuarios WHERE ativo = 1 ORDER BY (perfil = 'admin') DESC, id LIMIT 1")->fetchColumn();
if ($restaurantId <= 0 || $userId <= 0) {
    fwrite(STDERR, '[FAIL] Restaurante ou usuario de teste indisponivel.' . PHP_EOL);
    exit(1);
}

$date = (new DateTimeImmutable('next sunday'))->modify('+365 days')->format('Y-m-d');
$weekday = (int)(new DateTimeImmutable($date))->format('w');
$weekly = new ReservaTematicaBloqueioSemanalModel();
$daily = new ReservaTematicaBloqueioDataModel();
$repository = new ReservaTematicaRepository();

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$db->beginTransaction();
try {
    $weekly->setClosed($restaurantId, $weekday, true, 'Teste automatizado', $userId);
    $daily->removeOverride($restaurantId, $date, $userId);
    $assert($daily->isClosed($restaurantId, $date), 'Fechamento semanal nao foi aplicado.');
    $assert($repository->restauranteFechadoNaData($restaurantId, $date), 'Servico de reserva ignorou fechamento semanal.');

    $daily->setOverride($restaurantId, $date, 'aberto', 'Abertura de teste', $userId);
    $assert(!$daily->isClosed($restaurantId, $date), 'Abertura pontual nao prevaleceu.');
    $assert(!$repository->restauranteFechadoNaData($restaurantId, $date), 'Servico de reserva ignorou abertura pontual.');

    $daily->setOverride($restaurantId, $date, 'fechado', 'Fechamento de teste', $userId);
    $assert($daily->isClosed($restaurantId, $date), 'Fechamento pontual nao prevaleceu.');
    $assert($repository->restauranteFechadoNaData($restaurantId, $date), 'Servico de reserva ignorou fechamento pontual.');

    $daily->removeOverride($restaurantId, $date, $userId);
    $assert($daily->isClosed($restaurantId, $date), 'Remocao da excecao nao restaurou a regra semanal.');

    $db->rollBack();
    echo '[OK] Fechamento semanal, abertura pontual e retorno ao cronograma validados.' . PHP_EOL;
} catch (Throwable $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, '[FAIL] ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
