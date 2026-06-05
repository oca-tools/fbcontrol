<?php
class DemoController extends Controller
{
    public function toggle(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=home');
        }

        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=home');
        }

        $_SESSION['demo_mode'] = (int)($_POST['demo_mode'] ?? 0) === 1 ? 1 : 0;
        set_flash(
            !empty($_SESSION['demo_mode']) ? 'warning' : 'success',
            !empty($_SESSION['demo_mode'])
                ? 'Modo demonstração ativado. Validações de horário foram ignoradas apenas nesta sessão admin.'
                : 'Modo demonstração desativado.'
        );

        $this->redirect(sanitize_local_redirect_path((string)($_POST['return_to'] ?? '/?r=home')));
    }
}
