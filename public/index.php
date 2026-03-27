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

$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['app']['timezone']);

require __DIR__ . '/../app/helpers/functions.php';
ob_start('normalize_output_mojibake');
require __DIR__ . '/../app/core/Database.php';
require __DIR__ . '/../app/core/Model.php';
require __DIR__ . '/../app/core/Controller.php';
require __DIR__ . '/../app/core/Auth.php';

if (Auth::check()) {
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

$route = $_GET['r'] ?? '';
if ($route === '') {
    $route = Auth::check() ? 'access/index' : 'auth/login';
}
if ($route === 'home') {
    $route = Auth::check() ? 'access/index' : 'auth/login';
}

[$controllerName, $action] = array_pad(explode('/', $route, 2), 2, 'index');
$controllerClass = ucfirst($controllerName) . 'Controller';

if (!class_exists($controllerClass)) {
    http_response_code(404);
    $controllerClass = 'ErrorsController';
    $action = 'notFound';
}

$controller = new $controllerClass();
if (!method_exists($controller, $action)) {
    http_response_code(404);
    $controller = new ErrorsController();
    $action = 'notFound';
}

$controller->$action();
