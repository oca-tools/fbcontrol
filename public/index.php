<?php

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
if ($https) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
$csp = [
    "default-src 'self'",
    "img-src 'self' data:",
    "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
    "style-src-elem 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
    "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
    "script-src-elem 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
    "font-src 'self' data: https://cdn.jsdelivr.net https://fonts.gstatic.com",
    "connect-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.gstatic.com",
    "frame-ancestors 'self'",
    "base-uri 'self'",
    "form-action 'self'",
];
header('Content-Security-Policy: ' . implode('; ', $csp));

$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['app']['timezone']);

require __DIR__ . '/../app/helpers/functions.php';
ob_start('normalize_output_mojibake');
require __DIR__ . '/../app/core/Database.php';
require __DIR__ . '/../app/core/Model.php';
require __DIR__ . '/../app/core/Controller.php';
require __DIR__ . '/../app/core/Auth.php';

if (Auth::check()) {
    $timeout = (int)($config['app']['session_timeout_min'] ?? 30);
    Auth::enforceIdleTimeout($timeout);
    Auth::enforceSingleSession();
}

spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../app/controllers/' . $class . '.php',
        __DIR__ . '/../app/models/' . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require $path;
            return;
        }
    }
});

$route = (string)($_GET['r'] ?? '');
if ($route !== '' && !preg_match('/^[a-zA-Z0-9_\/-]+$/', $route)) {
    http_response_code(400);
    $route = 'errors/notFound';
}
if ($route === '' || $route === 'home') {
    if (!Auth::check()) {
        $route = 'auth/login';
    } else {
        $perfil = strtolower((string)(Auth::user()['perfil'] ?? ''));
        $route = ($perfil === 'gerente') ? 'dashboard/index' : 'access/index';
    }
}

[$controllerName, $action] = array_pad(explode('/', $route, 2), 2, 'index');
$controllerClass = ucfirst($controllerName) . 'Controller';

if (!class_exists($controllerClass)) {
    http_response_code(404);
    $controllerClass = 'ErrorsController';
    $action = 'notFound';
}

$controller = new $controllerClass();
if (!preg_match('/^[a-zA-Z0-9_]+$/', $action) || !is_callable([$controller, $action])) {
    http_response_code(404);
    $controller = new ErrorsController();
    $action = 'notFound';
}

$controller->$action();
