<?php
declare(strict_types=1);

// Marca automaticamente como "Nao compareceu" as reservas tematicas
// cujo tempo de tolerancia configurado expirou.

require __DIR__ . '/../bootstrap_cli.php';

$db = Database::getInstance();
$systemUserId = 0;
$stmtUser = $db->query("
    SELECT id
    FROM usuarios
    WHERE ativo = 1
      AND perfil IN ('admin', 'supervisor')
    ORDER BY FIELD(perfil, 'admin', 'supervisor'), id
    LIMIT 1
");
$rowUser = $stmtUser->fetch();
if ($rowUser) {
    $systemUserId = (int)$rowUser['id'];
}

if ($systemUserId <= 0) {
    echo '[' . date('Y-m-d H:i:s') . "] nenhum usuario admin/supervisor ativo para auditoria.\n";
    exit(1);
}

$reservaModel = new ReservaTematicaModel();
$logModel = new ReservaTematicaLogModel();
$agora = date('Y-m-d H:i:s');
$candidatas = $reservaModel->findAutoNoShowCandidates($agora, null, null);

$count = 0;
foreach ($candidatas as $cand) {
    $id = (int)($cand['id'] ?? 0);
    if ($id <= 0) {
        continue;
    }
    $before = $reservaModel->find($id);
    if (!$before) {
        continue;
    }
    $statusAtual = normalize_mojibake((string)($before['status'] ?? ''));
    if ($statusAtual !== 'Reservada') {
        continue;
    }

    $obsAtual = trim((string)($before['observacao_operacao'] ?? ''));
    $obsAuto = 'No-show automático por expiração da tolerância da reserva.';
    if ($obsAtual !== '') {
        $obsAuto .= ' ' . $obsAtual;
    }

    $reservaModel->updateOperacao($id, 'Nao compareceu', $obsAuto, $systemUserId, 0);
    $after = $reservaModel->find($id) ?? [];
    $logModel->log($id, 'auto_no_show', $systemUserId, $before, $after, 'Aplicado automaticamente via cron.');
    $count++;
}

echo '[' . date('Y-m-d H:i:s') . '] reservas marcadas como no-show automatico: ' . $count . "\n";
