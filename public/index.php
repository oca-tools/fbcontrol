<?php

$appEnv = strtolower((string)(getenv('APP_ENV') ?: 'production'));
if ($appEnv === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
$bootTimeoutMin = max(30, (int)(getenv('APP_SESSION_TIMEOUT_MIN') ?: 30));
ini_set('session.gc_maxlifetime', (string)(($bootTimeoutMin * 60) + 300));
$sessionName = getenv('APP_SESSION_NAME') ?: 'OCA_FBCONTROL_SESSID';
session_name($sessionName);
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
header('Cross-Origin-Opener-Policy: same-origin');
header('Cross-Origin-Resource-Policy: same-origin');
header('X-Permitted-Cross-Domain-Policies: none');
header('Origin-Agent-Cluster: ?1');
if ($https) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
$csp = [
    "default-src 'self'",
    "base-uri 'self'",
    "form-action 'self'",
    "frame-ancestors 'self'",
    "frame-src 'none'",
    "object-src 'none'",
    "manifest-src 'self'",
    "media-src 'self'",
    "img-src 'self' data:",
    "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
    "style-src-elem 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
    "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
    "script-src-elem 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
    "font-src 'self' data: https://cdn.jsdelivr.net https://fonts.gstatic.com",
    "connect-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.gstatic.com",
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
    $timeout = max(30, (int)($config['app']['session_timeout_min'] ?? 30));
    Auth::enforceIdleTimeout($timeout);
    Auth::enforceSessionBinding();
    Auth::enforceSingleSession();
    header('Cache-Control: private, no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfExemptRoutes = [];
    $routeLower = strtolower($route);
    if (!in_array($routeLower, $csrfExemptRoutes, true)) {
        $csrfToken = (string)($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        if (!csrf_validate($csrfToken)) {
            try {
                (new SecurityLogModel())->log('csrf_invalid', (int)(Auth::user()['id'] ?? 0), [
                    'route' => $routeLower,
                    'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
                    'method' => (string)($_SERVER['REQUEST_METHOD'] ?? ''),
                ]);
            } catch (Throwable $ignored) {
                // Falha de log nao interrompe retorno seguro.
            }

            http_response_code(419);
            if (!headers_sent()) {
                header('Content-Type: text/html; charset=utf-8');
            }
            set_flash('danger', 'Sessao expirada. Atualize a pagina e tente novamente.');
            echo 'Token invalido ou expirado.';
            exit;
        }
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
$isValidActionName = preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $action) === 1 && !str_starts_with($action, '__');
$isAllowedAction = false;
if ($isValidActionName && is_callable([$controller, $action])) {
    try {
        $method = new ReflectionMethod($controller, $action);
        $isAllowedAction = $method->isPublic() && ($method->getDeclaringClass()->getName() === get_class($controller));
    } catch (Throwable $ignored) {
        $isAllowedAction = false;
    }
}
if (!$isAllowedAction) {
    http_response_code(404);
    $controller = new ErrorsController();
    $action = 'notFound';
}

$controller->$action();
