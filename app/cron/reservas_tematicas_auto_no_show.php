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

$resultado = (new AutoNoShowService())->executar(new AutoNoShowCommand([
    'usuario_id' => $systemUserId,
    'executado_em' => date('Y-m-d H:i:s'),
    'origem' => 'cron',
]));
$count = (int)($resultado->payload()['processadas'] ?? 0);

echo '[' . date('Y-m-d H:i:s') . '] reservas marcadas como no-show automatico: ' . $count . "\n";
