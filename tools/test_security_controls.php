<?php
declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

require $root . '/app/bootstrap_cli.php';
require_once $root . '/app/core/Auth.php';

$checks = [];
$record = static function (string $name, bool $ok, string $detail = '') use (&$checks): void {
    $checks[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail];
};

$payload = '</script><script>alert("xss")</script>';
$encoded = json_for_html(['value' => $payload]);
$decoded = json_decode($encoded, true);
$record(
    'json_script_context_escapes_tags',
    strpos($encoded, '</script>') === false
        && strpos($encoded, '<script>') === false
        && ($decoded['value'] ?? null) === $payload,
    $encoded
);

$record(
    'upload_url_accepts_profile',
    safe_public_upload_url('/uploads/profiles/avatar_10.webp', 'profiles') === '/uploads/profiles/avatar_10.webp'
);
$record(
    'upload_url_accepts_voucher',
    safe_public_upload_url('/uploads/vouchers/voucher-10.pdf', 'vouchers') === '/uploads/vouchers/voucher-10.pdf'
);
foreach ([
    'javascript:alert(1)',
    '//example.com/file.pdf',
    '/uploads/vouchers/../config.php',
    '/uploads/vouchers/file.pdf?download=1',
    '/uploads/profiles/file.php',
    "/uploads/vouchers/file.pdf\nX-Test: 1",
] as $unsafeUrl) {
    $record(
        'upload_url_rejects_' . substr(hash('sha256', $unsafeUrl), 0, 8),
        safe_public_upload_url($unsafeUrl) === '',
        $unsafeUrl
    );
}

$record(
    'local_redirect_rejects_external',
    sanitize_local_redirect_path('https://example.com', '/?r=home') === '/?r=home'
);
$record(
    'local_redirect_accepts_route',
    sanitize_local_redirect_path('/?r=dashboard/index', '/?r=home') === '/?r=dashboard/index'
);
$record(
    'download_filename_strips_headers',
    safe_download_filename("voucher.pdf\r\nX-Test: injected", 'voucher') === 'voucher.pdfX-Test_ injected'
);

$activeUser = Auth::sessionUserFromRecord([
    'id' => '25',
    'nome' => 'Operadora',
    'email' => 'operadora@example.com',
    'perfil' => ' HOSTESS ',
    'ativo' => '1',
    'foto_path' => '/uploads/profiles/avatar_25.webp',
]);
$record(
    'active_session_user_is_normalized',
    is_array($activeUser)
        && ($activeUser['id'] ?? null) === 25
        && ($activeUser['perfil'] ?? null) === 'hostess'
);
$record(
    'inactive_session_user_is_rejected',
    Auth::sessionUserFromRecord(['id' => 25, 'ativo' => 0]) === null
);

$voucherHtaccess = is_file('public/uploads/.htaccess')
    ? (string)file_get_contents('public/uploads/.htaccess')
    : '';
$record(
    'voucher_directory_denies_direct_access',
    strpos($voucherHtaccess, 'RewriteRule ^vouchers') !== false
        && strpos($voucherHtaccess, '[F,L]') !== false,
    'public/uploads/.htaccess'
);

$kpiView = is_file('app/views/kpis/index.php') ? (string)file_get_contents('app/views/kpis/index.php') : '';
$record(
    'kpi_view_uses_safe_json',
    strpos($kpiView, 'json_for_html(') !== false
        && preg_match('/<\?=\s*json_encode\s*\(/i', $kpiView) !== 1
);

$voucherViews = [
    'app/views/vouchers/index.php',
    'app/views/reports/index.php',
];
foreach ($voucherViews as $view) {
    $contents = is_file($view) ? (string)file_get_contents($view) : '';
    $record(
        'voucher_view_uses_authorized_route_' . str_replace(['/', '.'], '_', $view),
        strpos($contents, 'vouchers/attachment') !== false
            && preg_match('/href\s*=\s*["\'][^"\']*voucher_anexo_path/i', $contents) !== 1,
        $view
    );
}

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
