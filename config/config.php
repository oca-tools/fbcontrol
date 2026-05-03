<?php

if (!function_exists('cfg_env')) {
    function cfg_env(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            return $default;
        }
        return $value;
    }
}

$config = [
    'app' => [
        'name' => cfg_env('APP_NAME', 'FBControl'),
        'version' => cfg_env('APP_VERSION', '2.0'),
        'logo_path' => cfg_env('APP_LOGO_PATH', '/assets/logo-fbcontrol.svg'),
        'favicon_path' => cfg_env('APP_FAVICON_PATH', '/assets/favicon-fb-white.svg'),
        'base_url' => cfg_env('APP_BASE_URL', '/'),
        'timezone' => cfg_env('APP_TIMEZONE', 'America/Sao_Paulo'),
        'session_timeout_min' => (int)cfg_env('APP_SESSION_TIMEOUT_MIN', '30'),
    ],
    'db' => [
        'host' => cfg_env('DB_HOST', '127.0.0.1'),
        'name' => cfg_env('DB_NAME', 'controle_ab'),
        'user' => cfg_env('DB_USER', 'root'),
        'pass' => cfg_env('DB_PASS', ''),
        'charset' => cfg_env('DB_CHARSET', 'utf8mb4'),
    ],
];

$localConfigPath = __DIR__ . '/config.local.php';
if (file_exists($localConfigPath)) {
    $local = require $localConfigPath;
    if (is_array($local)) {
        $config = array_replace_recursive($config, $local);
    }
}

return $config;
