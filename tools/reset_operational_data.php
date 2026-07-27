<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este utilitario deve ser executado no terminal.\n");
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap_cli.php';

$options = getopt('', ['admin-email:', 'apply', 'confirm:']);
$adminEmail = strtolower(trim((string)($options['admin-email'] ?? '')));
$apply = array_key_exists('apply', $options);
$confirm = (string)($options['confirm'] ?? '');

if ($adminEmail === '') {
    fwrite(STDERR, "Uso: php tools/reset_operational_data.php --admin-email=admin@exemplo.com [--apply --confirm=RESET_FBCONTROL_V4_4]\n");
    exit(1);
}

$db = Database::getInstance();
$adminQuery = $db->prepare('SELECT id, nome, email FROM usuarios WHERE LOWER(email) = :email AND perfil = :perfil AND ativo = 1 LIMIT 2');
$adminQuery->execute([':email' => $adminEmail, ':perfil' => 'admin']);
$admins = $adminQuery->fetchAll(PDO::FETCH_ASSOC);
if (count($admins) !== 1) {
    fwrite(STDERR, "O reset exige exatamente um administrador ativo para o e-mail informado. Nenhum dado foi alterado.\n");
    exit(1);
}

$admin = $admins[0];
$adminId = (int)$admin['id'];
$tablesToClear = [
    'sessoes_ativas',
    'acessos_especiais',
    'acessos',
    'colaborador_refeicoes',
    'vouchers',
    'turnos_especiais',
    'turnos',
    'kpi_ocupacao_diaria',
    'relatorio_email_envios',
    'reservas_tematicas_chd',
    'reservas_tematicas_logs',
    'reservas_tematicas',
    'reservas_tematicas_grupos',
    'reservas_tematicas_fechamentos',
    'reservas_tematicas_bloqueios_datas',
    'auditoria',
    'lgpd_eventos',
    'lgpd_incidentes',
    'lgpd_solicitacoes',
];

echo "Administrador preservado: {$admin['nome']} <{$admin['email']}> (ID {$adminId})\n";
echo "Dados que serao apagados: " . implode(', ', $tablesToClear) . ".\n";
echo "Estrutura preservada: restaurantes, portas, operacoes, UHs, turnos tematicos, capacidades, regras semanais, configuracoes de e-mail/LGPD e administrador.\n";

if (!$apply) {
    echo "Modo de simulacao. Use --apply --confirm=RESET_FBCONTROL_V4_4 depois de validar o backup.\n";
    exit(0);
}
if ($confirm !== 'RESET_FBCONTROL_V4_4') {
    fwrite(STDERR, "Confirmacao invalida. Nenhum dado foi alterado.\n");
    exit(1);
}

try {
    $db->beginTransaction();

    foreach ($tablesToClear as $table) {
        $db->exec('DELETE FROM `' . $table . '`');
    }

    foreach ([
        ['lgpd_config', 'atualizado_por'],
        ['lgpd_retencao_politicas', 'atualizado_por'],
        ['reservas_tematicas_bloqueios_semanais', 'usuario_id'],
    ] as [$table, $column]) {
        $statement = $db->prepare('UPDATE `' . $table . '` SET `' . $column . '` = NULL WHERE `' . $column . '` IS NOT NULL AND `' . $column . '` <> :admin_id');
        $statement->execute([':admin_id' => $adminId]);
    }

    $db->prepare('DELETE FROM usuarios_restaurantes_operacoes WHERE usuario_id <> :admin_id')->execute([':admin_id' => $adminId]);
    $db->prepare('DELETE FROM usuarios_restaurantes WHERE usuario_id <> :admin_id')->execute([':admin_id' => $adminId]);
    $db->prepare('DELETE FROM usuarios_onboarding WHERE usuario_id <> :admin_id')->execute([':admin_id' => $adminId]);
    $db->prepare('DELETE FROM usuarios WHERE id <> :admin_id')->execute([':admin_id' => $adminId]);

    $db->commit();
    echo "Reset operacional concluido. Apenas o administrador informado foi mantido.\n";
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, "Falha no reset. A transacao foi desfeita: " . $exception->getMessage() . "\n");
    exit(1);
}
