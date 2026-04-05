<?php
class Controller
{
    protected array $data = [];

    protected function view(string $view, array $data = []): void
    {
        $this->data = $data;
        $config = require __DIR__ . '/../../config/config.php';
        $appName = $config['app']['name'];

        $viewPath = __DIR__ . '/../views/' . $view . '.php';
        if (!file_exists($viewPath)) {
            http_response_code(404);
            $notFoundPath = __DIR__ . '/../views/errors/not_found.php';
            $message = 'OOps, página não encontrada.';
            $flash = get_flash();
            if (file_exists($notFoundPath)) {
                require __DIR__ . '/../views/partials/header.php';
                require $notFoundPath;
                require __DIR__ . '/../views/partials/footer.php';
                return;
            }
            echo 'View não encontrada.';
            return;
        }

        require __DIR__ . '/../views/partials/header.php';
        require $viewPath;
        require __DIR__ . '/../views/partials/footer.php';
    }

    protected function redirect(string $route): void
    {
        $safeRoute = str_replace(["\r", "\n"], '', $route);
        $hasExternalScheme = (bool)preg_match('/^[a-z][a-z0-9+\-.]*:/i', $safeRoute);
        $isProtocolRelative = str_starts_with($safeRoute, '//');
        $isAbsolutePath = str_starts_with($safeRoute, '/');

        if (
            $safeRoute === ''
            || $hasExternalScheme
            || $isProtocolRelative
            || !$isAbsolutePath
            || strpos($safeRoute, '\\') !== false
        ) {
            $safeRoute = '/?r=home';
        }
        header('Location: ' . $safeRoute);
        exit;
    }

    protected function redirectHome(): void
    {
        $user = Auth::user();
        $perfil = $user['perfil'] ?? '';

        if (in_array($perfil, ['hostess', 'admin', 'supervisor'], true)) {
            $this->redirect('/?r=access/index');
        }
        if ($perfil === 'gerente') {
            $this->redirect('/?r=dashboard/index');
        }
        $this->redirect('/?r=auth/login');
    }

    protected function requireAuth(): void
    {
        if (!Auth::check()) {
            $this->redirect('/?r=auth/login');
        }
    }

    protected function forbidden(string $message = 'OOps, acesso não autorizado.'): void
    {
        http_response_code(403);
        $this->view('errors/forbidden', [
            'message' => $message,
            'flash' => get_flash(),
        ]);
        exit;
    }

    protected function notFound(string $message = 'OOps, página não encontrada.'): void
    {
        http_response_code(404);
        $this->view('errors/not_found', [
            'message' => $message,
            'flash' => get_flash(),
        ]);
        exit;
    }
}
