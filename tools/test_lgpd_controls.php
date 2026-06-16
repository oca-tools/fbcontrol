<?php
declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

require $root . '/app/bootstrap_cli.php';

$checks = [];
$record = static function (string $name, bool $ok, string $detail = '') use (&$checks): void {
    $checks[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail];
};

$payload = [
    'before' => [
        'protocolo' => 'LGPD-20260613-00001',
        'titular_nome' => 'Maria Silva',
        'titular_documento' => '123.456.789-00',
        'titular_email' => 'maria@example.com',
        'detalhes' => 'Texto livre com dados pessoais.',
        'status' => 'aberta',
    ],
    'after' => [
        'status' => 'concluida',
        'resposta_resumo' => 'Resposta operacional completa.',
    ],
    'deleted_rows' => 7,
];

$clean = LgpdModel::sanitizePrivacyEventDetails($payload);
$record(
    'lgpd_event_redacts_identity_fields',
    ($clean['before']['titular_nome'] ?? '') === '[REDACTED]'
        && ($clean['before']['titular_documento'] ?? '') === '[REDACTED]'
        && ($clean['before']['titular_email'] ?? '') === '[REDACTED]'
);
$record(
    'lgpd_event_redacts_free_text',
    ($clean['before']['detalhes'] ?? '') === '[TEXT_REDACTED]'
        && ($clean['after']['resposta_resumo'] ?? '') === '[TEXT_REDACTED]'
);
$record(
    'lgpd_event_keeps_operational_metadata',
    ($clean['before']['protocolo'] ?? '') === 'LGPD-20260613-00001'
        && ($clean['after']['status'] ?? '') === 'concluida'
        && ($clean['deleted_rows'] ?? null) === 7
);

$lgpdView = is_file('app/views/crud/lgpd.php') ? (string)file_get_contents('app/views/crud/lgpd.php') : '';
foreach (['titular_documento', 'titular_email', 'detalhes', 'resposta_resumo', 'dados_afetados', 'medidas_adotadas'] as $field) {
    $record(
        'lgpd_fast_status_form_does_not_embed_' . $field,
        strpos($lgpdView, 'type="hidden" name="' . $field . '"') === false,
        $field
    );
}

$reportsController = is_file('app/controllers/RelatoriosController.php') ? (string)file_get_contents('app/controllers/RelatoriosController.php') : '';
$exportVoucherBlock = '';
if (preg_match('/public function export_vouchers\(\): void(.*?)public function export_voucher_pdfs\(\): void/s', $reportsController, $matches)) {
    $exportVoucherBlock = (string)$matches[1];
}
$record(
    'voucher_export_does_not_expose_storage_path',
    $exportVoucherBlock !== ''
        && strpos($exportVoucherBlock, 'anexo_registrado') !== false
        && strpos($exportVoucherBlock, "safe_public_upload_url((string)(\$r['voucher_anexo_path'] ?? ''), 'vouchers') !== '' ? 'sim' : 'nao'") !== false
        && strpos($exportVoucherBlock, "\$r['voucher_anexo_path']") !== false,
    'export_vouchers'
);

foreach ($checks as $check) {
    echo ($check['ok'] ? '[OK] ' : '[FAIL] ') . $check['name'];
    if ($check['detail'] !== '') {
        echo ' - ' . $check['detail'];
    }
    echo PHP_EOL;
}

$failed = array_values(array_filter($checks, static function (array $check): bool {
    return !$check['ok'];
}));
echo PHP_EOL . 'Resultado: ' . ($failed === [] ? 'OK' : 'FALHOU') . PHP_EOL;
exit($failed === [] ? 0 : 1);
