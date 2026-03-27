<?php
class AuthController extends Controller
{
    private function throttleKey(): string
    {
        return (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    private function isBlocked(): bool
    {
        $key = $this->throttleKey();
        $attempt = $_SESSION['auth_throttle'][$key] ?? null;
        if (!$attempt) {
            return false;
        }
        $window = 10 * 60;
        if ((time() - (int)$attempt['first']) > $window) {
            unset($_SESSION['auth_throttle'][$key]);
            return false;
        }
        return (int)$attempt['count'] >= 5;
    }

    private function registerFail(): void
    {
        $key = $this->throttleKey();
        $now = time();
        $window = 10 * 60;
        $entry = $_SESSION['auth_throttle'][$key] ?? ['count' => 0, 'first' => $now];
        if (($now - (int)$entry['first']) > $window) {
            $entry = ['count' => 0, 'first' => $now];
        }
        $entry['count'] = (int)$entry['count'] + 1;
        $_SESSION['auth_throttle'][$key] = $entry;
    }

    private function clearThrottle(): void
    {
        $key = $this->throttleKey();
        unset($_SESSION['auth_throttle'][$key]);
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_validate($_POST['csrf_token'] ?? '')) {
                set_flash('danger', 'Token inválido.');
                $this->redirect('/?r=auth/login');
            }

            if ($this->isBlocked()) {
                set_flash('danger', 'Muitas tentativas. Aguarde 10 minutos e tente novamente.');
                $this->redirect('/?r=auth/login');
            }

            $email = trim($_POST['email'] ?? '');
            $senha = $_POST['senha'] ?? '';

            $userModel = new UserModel();
            $user = $userModel->findByEmail($email);

            if ($user && password_verify($senha, $user['senha'])) {
                $this->clearThrottle();
                Auth::login($user);
                $this->redirect('/?r=home');
            }

            $this->registerFail();
            set_flash('danger', 'Credenciais inválidas.');
            $this->redirect('/?r=auth/login');
        }

        $this->view('auth/login', [
            'flash' => get_flash(),
        ]);
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/?r=auth/login');
    }
}
