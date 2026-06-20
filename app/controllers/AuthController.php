<?php
declare(strict_types=1);

class AuthController extends Controller
{
    private GovernancaAuthSession $authSession;

    public function __construct()
    {
        $this->authSession = new GovernancaAuthSession();
    }

    /**
     * Valida credenciais e inicia a sessão rastreável do operador para fins de auditoria.
     */
    public function login(): void
    {
        if ($this->authSession->isAuthenticated() && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(GovernancaConstants::ROUTE_HOME);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $resultado = (new AutenticarUsuarioService())->autenticar(
                (string)($_POST['email'] ?? ''),
                (string)($_POST['senha'] ?? '')
            );

            if ($resultado->isSuccess()) {
                $this->redirect(GovernancaConstants::ROUTE_HOME);
            }

            set_flash(GovernancaConstants::FLASH_DANGER, $resultado->message());
            $this->redirect(GovernancaConstants::ROUTE_LOGIN);
        }

        $this->view('auth/login', [
            'flash' => get_flash(),
        ]);
    }

    /**
     * Encerra a sessão e registra a saída para preservar a trilha de responsabilidade do operador.
     */
    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(GovernancaConstants::ROUTE_HOME);
        }

        (new AutenticarUsuarioService())->registrarLogout($this->authSession->user());
        $this->redirect(GovernancaConstants::ROUTE_LOGIN);
    }
}
