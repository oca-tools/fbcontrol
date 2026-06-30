<?php
declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);
require $root . '/app/bootstrap_cli.php';

$ranges = [
    [101, 151], [200, 248], [400, 419], [500, 519], [600, 619],
    [700, 719], [800, 819], [900, 919], [1000, 1019], [1101, 1111],
    [2100, 2109], [2200, 2209], [2300, 2309], [3100, 3109], [3200, 3209],
    [3300, 3309], [4000, 4021], [4100, 4122], [4200, 4222], [4300, 4322],
];
$invalid = [2, 100, 152, 187, 199, 249, 300, 342, 399, 420, 423, 499, 520,
    620, 720, 727, 820, 920, 997, 1020, 1100, 1112, 2110, 2113, 2199,
    2210, 2310, 2510, 3099, 3110, 3115, 3199, 3210, 3310, 3314, 3502,
    3999, 4022, 4099, 4123, 4124, 4199, 4223, 4299, 4323];

$model = new UnitModel();
$validCount = 0;
foreach ($ranges as [$start, $end]) {
    for ($numero = $start; $numero <= $end; $numero++) {
        if (!$model->isValidNumero((string)$numero)) {
            fwrite(STDERR, '[FAIL] UH oficial rejeitada: ' . $numero . PHP_EOL);
            exit(1);
        }
        $validCount++;
    }
}
foreach ([998, 999] as $technical) {
    if (!$model->isValidNumero((string)$technical)) {
        fwrite(STDERR, '[FAIL] UH tecnica rejeitada: ' . $technical . PHP_EOL);
        exit(1);
    }
    $validCount++;
}
foreach ($invalid as $numero) {
    if ($model->isValidNumero((string)$numero)) {
        fwrite(STDERR, '[FAIL] UH fora da lista aceita: ' . $numero . PHP_EOL);
        exit(1);
    }
}

$paxLimits = ['101' => 4, '248' => 4, '400' => 5, '1111' => 5, '2100' => 6, '3200' => 6, '4322' => 6];
foreach ($paxLimits as $numero => $expectedLimit) {
    if ($model->maxPaxForNumero((string)$numero) !== $expectedLimit) {
        fwrite(STDERR, '[FAIL] Limite de PAX incorreto para UH ' . $numero . PHP_EOL);
        exit(1);
    }
}
if ($model->maxPaxForNumero('998') !== null || $model->maxPaxForNumero('999') !== null) {
    fwrite(STDERR, '[FAIL] UHs tecnicas devem permanecer sem limite rigido de PAX.' . PHP_EOL);
    exit(1);
}

$db = Database::getInstance();
$db->beginTransaction();
try {
    $repository = new UnitRepository();
    $repositoryCount = 0;
    foreach ($ranges as [$start, $end]) {
        for ($numero = $start; $numero <= $end; $numero++) {
            $uh = $repository->buscarUhPorNumero((string)$numero);
            if (!$uh || (string)($uh['numero'] ?? '') !== (string)$numero || (int)($uh['ativo'] ?? 0) !== 1) {
                throw new RuntimeException('UH oficial nao foi localizada/criada no fluxo real: ' . $numero);
            }
            $repositoryCount++;
        }
    }
    foreach ([998, 999] as $technical) {
        $uh = $repository->buscarUhPorNumero((string)$technical);
        if (!$uh || (string)($uh['numero'] ?? '') !== (string)$technical || (int)($uh['ativo'] ?? 0) !== 1) {
            throw new RuntimeException('UH tecnica nao foi localizada/criada no fluxo real: ' . $technical);
        }
        $repositoryCount++;
    }
    foreach ($invalid as $numero) {
        if ($repository->buscarUhPorNumero((string)$numero) !== null) {
            throw new RuntimeException('UH fora da lista foi aceita pelo fluxo real: ' . $numero);
        }
    }
    if ($repositoryCount !== $validCount) {
        throw new RuntimeException('Quantidade divergente no teste do repositorio: ' . $repositoryCount . '/' . $validCount);
    }
    $db->rollBack();
} catch (Throwable $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, '[FAIL] ' . $error->getMessage() . PHP_EOL);
    exit(1);
}

echo '[OK] ' . $validCount . ' UHs oficiais/tecnicas passaram pela regra e pelo repositorio real; lacunas rejeitadas.' . PHP_EOL;
