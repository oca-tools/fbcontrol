<?php
/**
 * Base comum para controllers web.
 */
class Controller
{
    protected array $data = [];

    /**
     * Renderiza uma view dentro do layout padrao.
     *
     * @param string $view Caminho da view relativo a app/views.
     * @param array $data Dados expostos ao template.
     * @return void
     */
    protected function view(string $view, array $data = []): void
    {
        if (!array_key_exists('flash', $data)) {
            $data['flash'] = get_flash();
        }
        $this->data = $data;
        $config = require __DIR__ . '/../../config/config.php';
        $appName = $config['app']['name'];

        $viewPath = __DIR__ . '/../views/' . $view . '.php';
        if (!file_exists($viewPath)) {
            http_response_code(404);
            $notFoundPath = __DIR__ . '/../views/errors/not_found.php';
            $message = AppConstants::MESSAGE_NOT_FOUND;
            $flash = get_flash();
            if (file_exists($notFoundPath)) {
                require __DIR__ . '/../views/partials/header.php';
                require $notFoundPath;
                require __DIR__ . '/../views/partials/footer.php';
                return;
            }
            echo AppConstants::MESSAGE_VIEW_NOT_FOUND;
            return;
        }

        require __DIR__ . '/../views/partials/header.php';
        require $viewPath;
        require __DIR__ . '/../views/partials/footer.php';
    }

    /**
     * Redireciona para uma rota local sanitizada.
     *
     * @param string $route Rota local desejada.
     * @return void
     */
    protected function redirect(string $route): void
    {
        $safeRoute = sanitize_local_redirect_path($route, AppConstants::ROUTE_HOME);
        header('Location: ' . $safeRoute);
        exit;
    }

    /**
     * Envia o usuario autenticado para a pagina inicial do perfil.
     *
     * @return void
     */
    protected function redirectHome(): void
    {
        $user = Auth::user();
        $perfil = $user['perfil'] ?? '';

        if (in_array($perfil, AppConstants::ACCESS_HOME_ROLES, true)) {
            $this->redirect(AppConstants::ROUTE_ACCESS_INDEX);
        }
        if ($perfil === AppConstants::ROLE_MANAGER) {
            $this->redirect(AppConstants::ROUTE_DASHBOARD_INDEX);
        }
        $this->redirect(AppConstants::ROUTE_LOGIN);
    }

    /**
     * Exige sessao autenticada para continuar.
     *
     * @return void
     */
    protected function requireAuth(): void
    {
        if (!Auth::check()) {
            $this->redirect(AppConstants::ROUTE_LOGIN);
        }
    }

    /**
     * Renderiza a resposta padrao de acesso negado.
     *
     * @param string $message Mensagem exibida ao usuario.
     * @return void
     */
    protected function forbidden(string $message = AppConstants::MESSAGE_FORBIDDEN): void
    {
        http_response_code(403);
        $this->view('errors/forbidden', [
            'message' => $message,
            'flash' => get_flash(),
        ]);
        exit;
    }

    /**
     * Renderiza a resposta padrao de pagina nao encontrada.
     *
     * @param string $message Mensagem exibida ao usuario.
     * @return void
     */
    protected function notFound(string $message = AppConstants::MESSAGE_NOT_FOUND): void
    {
        http_response_code(404);
        $this->view('errors/not_found', [
            'message' => $message,
            'flash' => get_flash(),
        ]);
        exit;
    }
}
