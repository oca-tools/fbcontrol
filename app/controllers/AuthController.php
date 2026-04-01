<?php
class AuthController extends Controller
{
    private function throttleKey(?string $email = null): string
    {
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $emailKey = mb_strtolower(trim((string)$email), 'UTF-8');
        return hash('sha256', $ip . '|' . $emailKey);
    }

    private function isBlocked(?string $email = null): bool
    {
        $key = $this->throttleKey($email);
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

    private function registerFail(?string $email = null): void
    {
        $key = $this->throttleKey($email);
        $now = time();
        $window = 10 * 60;
        if (!isset($_SESSION['auth_throttle']) || !is_array($_SESSION['auth_throttle'])) {
            $_SESSION['auth_throttle'] = [];
        }
        foreach ($_SESSION['auth_throttle'] as $k => $entry) {
            if (($now - (int)($entry['first'] ?? 0)) > $window) {
                unset($_SESSION['auth_throttle'][$k]);
            }
        }
        $entry = $_SESSION['auth_throttle'][$key] ?? ['count' => 0, 'first' => $now];
        if (($now - (int)$entry['first']) > $window) {
            $entry = ['count' => 0, 'first' => $now];
        }
        $entry['count'] = (int)$entry['count'] + 1;
        $_SESSION['auth_throttle'][$key] = $entry;
    }

    private function clearThrottle(?string $email = null): void
    {
        $key = $this->throttleKey($email);
        unset($_SESSION['auth_throttle'][$key]);
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim((string)($_POST['email'] ?? ''));
            if (!csrf_validate($_POST['csrf_token'] ?? '')) {
                set_flash('danger', 'Token inválido.');
                $this->redirect('/?r=auth/login');
            }

            if ($this->isBlocked($email)) {
                set_flash('danger', 'Muitas tentativas. Aguarde 10 minutos e tente novamente.');
                $this->redirect('/?r=auth/login');
            }

            $senha = $_POST['senha'] ?? '';

            $userModel = new UserModel();
            $user = $userModel->findByEmail($email);

            if ($user && password_verify($senha, $user['senha'])) {
                $this->clearThrottle($email);
                Auth::login($user);
                $this->redirect('/?r=home');
            }

            $this->registerFail($email);
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
