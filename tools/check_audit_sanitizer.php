<?php
declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

require_once $root . '/app/core/Model.php';

$payload = [
    'email' => 'operacao@example.com',
    'senha' => 'nao-pode-vazar',
    'nova_senha' => 'tambem-nao',
    'resetToken' => 'token-camel-case',
    'nested' => [
        'token' => 'segredo',
        'safe' => 'mantido',
    ],
];

$sanitized = Model::sanitizeAuditPayload($payload);
$ok = ($sanitized['email'] ?? null) === 'operacao@example.com'
    && ($sanitized['senha'] ?? null) === '[REDACTED]'
    && ($sanitized['nova_senha'] ?? null) === '[REDACTED]'
    && ($sanitized['resetToken'] ?? null) === '[REDACTED]'
    && ($sanitized['nested']['token'] ?? null) === '[REDACTED]'
    && ($sanitized['nested']['safe'] ?? null) === 'mantido';

echo ($ok ? '[OK] ' : '[FAIL] ') . 'audit_sensitive_redaction' . PHP_EOL;
exit($ok ? 0 : 1);
