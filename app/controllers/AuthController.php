<?php
class AuthController extends Controller
{
    private const THROTTLE_WINDOW_SECONDS = 900; // 15 min
    private const THROTTLE_LIMIT = 5;
    private const THROTTLE_MAX_BACKOFF_SECONDS = 1800; // 30 min

    private function throttleKey(?string $email = null): string
    {
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $emailKey = mb_strtolower(trim((string)$email), 'UTF-8');
        return hash('sha256', $ip . '|' . $emailKey);
    }

    private function throttleFilePath(?string $email = null): string
    {
        $base = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'ocafbcontrol_auth_throttle';
        if (!is_dir($base)) {
            @mkdir($base, 0700, true);
        } else {
            @chmod($base, 0700);
        }
        return $base . DIRECTORY_SEPARATOR . $this->throttleKey($email) . '.json';
    }

    private function readThrottle(?string $email = null): array
    {
        $file = $this->throttleFilePath($email);
        if (!is_file($file)) {
            return ['count' => 0, 'first' => 0, 'last' => 0, 'blocked_until' => 0];
        }

        $raw = @file_get_contents($file);
        $data = json_decode((string)$raw, true);
        if (!is_array($data)) {
            return ['count' => 0, 'first' => 0, 'last' => 0, 'blocked_until' => 0];
        }

        return [
            'count' => (int)($data['count'] ?? 0),
            'first' => (int)($data['first'] ?? 0),
            'last' => (int)($data['last'] ?? 0),
            'blocked_until' => (int)($data['blocked_until'] ?? 0),
        ];
    }

    private function writeThrottle(?string $email, array $entry): void
    {
        $file = $this->throttleFilePath($email);
        if (is_link($file)) {
            return;
        }
        $tmpFile = $file . '.tmp';
        $encoded = json_encode($entry, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return;
        }
        @file_put_contents($tmpFile, $encoded, LOCK_EX);
        @chmod($tmpFile, 0600);
        @rename($tmpFile, $file);
    }

    private function getBlockedSeconds(?string $email = null): int
    {
        $entry = $this->readThrottle($email);
        $now = time();

        if (($now - (int)$entry['first']) > self::THROTTLE_WINDOW_SECONDS) {
            $this->clearThrottle($email);
            return 0;
        }

        $remaining = (int)$entry['blocked_until'] - $now;
        return max(0, $remaining);
    }

    private function isBlocked(?string $email = null): bool
    {
        return $this->getBlockedSeconds($email) > 0;
    }

    private function blockedSecondsForLogin(?string $email = null): int
    {
        // Bloqueio combinado por e-mail+IP e também por IP isolado.
        return max($this->getBlockedSeconds($email), $this->getBlockedSeconds(null));
    }

    private function registerFail(?string $email = null): void
    {
        $entry = $this->readThrottle($email);
        $now = time();

        if (($now - (int)$entry['first']) > self::THROTTLE_WINDOW_SECONDS) {
            $entry = ['count' => 0, 'first' => $now, 'last' => 0, 'blocked_until' => 0];
        }
        if ((int)$entry['first'] <= 0) {
            $entry['first'] = $now;
        }

        $entry['count'] = (int)$entry['count'] + 1;
        $entry['last'] = $now;

        if ((int)$entry['count'] >= self::THROTTLE_LIMIT) {
            $overflow = (int)$entry['count'] - self::THROTTLE_LIMIT;
            $backoff = min(self::THROTTLE_MAX_BACKOFF_SECONDS, (int)(60 * (2 ** $overflow)));
            $entry['blocked_until'] = max((int)$entry['blocked_until'], $now + $backoff);
        }

        $this->writeThrottle($email, $entry);
    }

    private function clearThrottle(?string $email = null): void
    {
        $file = $this->throttleFilePath($email);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    public function login(): void
    {
        if (Auth::check() && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=home');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim((string)($_POST['email'] ?? ''));
            $senha = (string)($_POST['senha'] ?? '');

            if ($email === '' || $senha === '') {
                set_flash('danger', 'Informe e-mail e senha.');
                $this->redirect('/?r=auth/login');
            }

            $remaining = $this->blockedSecondsForLogin($email);
            if ($remaining > 0) {
                $mins = max(1, (int)ceil($remaining / 60));
                set_flash('danger', 'Muitas tentativas. Aguarde ' . $mins . ' minuto(s) e tente novamente.');
                (new SecurityLogModel())->log('auth_blocked', null, [
                    'email' => mb_strtolower($email, 'UTF-8'),
                    'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
                    'blocked_seconds' => $remaining,
                ]);
                $this->redirect('/?r=auth/login');
            }

            $userModel = new UserModel();
            $user = $userModel->findByEmail($email);

            if ($user && password_verify($senha, (string)$user['senha'])) {
                $this->clearThrottle($email);
                Auth::login($user);
                (new SecurityLogModel())->log('auth_login_success', (int)$user['id'], [
                    'email' => mb_strtolower($email, 'UTF-8'),
                    'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
                ]);
                $this->redirect('/?r=home');
            }

            $this->registerFail($email);
            $this->registerFail(null);
            (new SecurityLogModel())->log('auth_login_failed', null, [
                'email' => mb_strtolower($email, 'UTF-8'),
                'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            ]);
            set_flash('danger', 'Credenciais invÃ¡lidas.');
            $this->redirect('/?r=auth/login');
        }

        $this->view('auth/login', [
            'flash' => get_flash(),
        ]);
    }

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=home');
        }

        $user = Auth::user();
        (new SecurityLogModel())->log('auth_logout', (int)($user['id'] ?? 0), [
            'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
        ]);
        Auth::logout();
        $this->redirect('/?r=auth/login');
    }
}
