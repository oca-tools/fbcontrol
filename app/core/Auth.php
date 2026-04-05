<?php
class Auth
{
    private static ?bool $sessionRegistryAvailable = null;

    private static function sessionFingerprint(): string
    {
        $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');

        $ipPrefix = $ip;
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $ipPrefix = implode('.', array_slice($parts, 0, 3));
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            $ipPrefix = implode(':', array_slice($parts, 0, 4));
        }

        return hash('sha256', $ipPrefix . '|' . $ua);
    }

    private static function sessionToken(): string
    {
        if (empty($_SESSION['session_lock_token'])) {
            $_SESSION['session_lock_token'] = bin2hex(random_bytes(16));
        }
        return (string)$_SESSION['session_lock_token'];
    }

    private static function sessionRegistryAvailable(): bool
    {
        if (self::$sessionRegistryAvailable !== null) {
            return self::$sessionRegistryAvailable;
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->query("SHOW TABLES LIKE 'sessoes_ativas'");
            self::$sessionRegistryAvailable = (bool)$stmt->fetch();
        } catch (Throwable $e) {
            self::$sessionRegistryAvailable = false;
        }
        return self::$sessionRegistryAvailable;
    }

    private static function upsertSessionRegistry(int $userId): void
    {
        if (!self::sessionRegistryAvailable()) {
            return;
        }
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                INSERT INTO sessoes_ativas (usuario_id, session_id, token, ip, user_agent, atualizado_em)
                VALUES (:usuario_id, :session_id, :token, :ip, :user_agent, NOW())
                ON DUPLICATE KEY UPDATE
                    session_id = VALUES(session_id),
                    token = VALUES(token),
                    ip = VALUES(ip),
                    user_agent = VALUES(user_agent),
                    atualizado_em = NOW()
            ");
            $stmt->execute([
                ':usuario_id' => $userId,
                ':session_id' => session_id(),
                ':token' => self::sessionToken(),
                ':ip' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
                ':user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);
        } catch (Throwable $e) {
            // Falha de registro de sessão não deve derrubar o login.
        }
    }

    private static function clearSessionRegistry(): void
    {
        if (!self::sessionRegistryAvailable() || !isset($_SESSION['user']['id'])) {
            return;
        }
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("DELETE FROM sessoes_ativas WHERE usuario_id = :usuario_id AND token = :token");
            $stmt->execute([
                ':usuario_id' => (int)$_SESSION['user']['id'],
                ':token' => (string)($_SESSION['session_lock_token'] ?? ''),
            ]);
        } catch (Throwable $e) {
            // Sem impacto no logout.
        }
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        if (!isset($_SESSION['user'])) {
            return null;
        }
        $perfil = strtolower(trim((string)($_SESSION['user']['perfil'] ?? '')));
        $_SESSION['user']['perfil'] = $perfil;
        return $_SESSION['user'];
    }

    public static function login(array $user): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Mitiga session fixation após autenticação.
        session_regenerate_id(true);

        $perfil = strtolower(trim((string)($user['perfil'] ?? '')));
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'nome' => $user['nome'],
            'email' => $user['email'],
            'perfil' => $perfil,
            'foto_path' => $user['foto_path'] ?? null,
        ];
        $_SESSION['last_activity_at'] = time();
        $_SESSION['logged_in_at'] = time();
        $_SESSION['session_fingerprint'] = self::sessionFingerprint();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        self::upsertSessionRegistry((int)$user['id']);
    }

    public static function logout(): void
    {
        self::clearSessionRegistry();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
        }

        session_destroy();
    }

    public static function enforceSingleSession(): void
    {
        if (!self::check() || !self::sessionRegistryAvailable()) {
            return;
        }
        try {
            $userId = (int)($_SESSION['user']['id'] ?? 0);
            if ($userId <= 0) {
                return;
            }

            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT token FROM sessoes_ativas WHERE usuario_id = :usuario_id LIMIT 1");
            $stmt->execute([':usuario_id' => $userId]);
            $row = $stmt->fetch();
            if (!$row) {
                self::upsertSessionRegistry($userId);
                return;
            }

            $currentToken = (string)($row['token'] ?? '');
            $sessionToken = (string)($_SESSION['session_lock_token'] ?? '');
            if ($sessionToken === '') {
                $_SESSION['session_lock_token'] = $currentToken;
                return;
            }

            if (!hash_equals($currentToken, $sessionToken)) {
                if (function_exists('set_flash')) {
                    set_flash('warning', 'Sua sessão foi encerrada porque uma nova sessão foi iniciada neste usuário.');
                }
                self::logout();
                header('Location: /?r=auth/login');
                exit;
            }

            // heartbeat simples
            $touch = $db->prepare("UPDATE sessoes_ativas SET atualizado_em = NOW(), session_id = :session_id WHERE usuario_id = :usuario_id AND token = :token");
            $touch->execute([
                ':session_id' => session_id(),
                ':usuario_id' => $userId,
                ':token' => $sessionToken,
            ]);
        } catch (Throwable $e) {
            // Falha do controle de sessão não bloqueia operação.
        }
    }

    public static function enforceSessionBinding(): void
    {
        if (!self::check()) {
            return;
        }

        $current = self::sessionFingerprint();
        $stored = (string)($_SESSION['session_fingerprint'] ?? '');

        if ($stored === '') {
            $_SESSION['session_fingerprint'] = $current;
            return;
        }

        if (!hash_equals($stored, $current)) {
            if (function_exists('set_flash')) {
                set_flash('warning', 'SessÃ£o encerrada por mudanÃ§a de contexto do navegador.');
            }
            self::logout();
            header('Location: /?r=auth/login');
            exit;
        }
    }

    public static function enforceIdleTimeout(int $timeoutMinutes = 30): void
    {
        if (!self::check()) {
            return;
        }

        $timeoutSeconds = max(60, $timeoutMinutes * 60);
        $now = time();
        $last = (int)($_SESSION['last_activity_at'] ?? 0);

        if ($last > 0 && ($now - $last) > $timeoutSeconds) {
            if (function_exists('set_flash')) {
                set_flash('warning', 'Sessão encerrada por inatividade (' . (int)$timeoutMinutes . ' minutos).');
            }
            self::logout();
            header('Location: /?r=auth/login');
            exit;
        }

        $_SESSION['last_activity_at'] = $now;
    }

    public static function requireRole(array $roles): void
    {
        $user = self::user();
        $perfil = strtolower(trim((string)($user['perfil'] ?? '')));
        $roles = array_map(static fn($r) => strtolower(trim((string)$r)), $roles);

        if (!$user || !in_array($perfil, $roles, true)) {
            if (function_exists('set_flash')) {
                set_flash('danger', 'OOps, acesso não autorizado.');
            }
            header('Location: /?r=errors/forbidden');
            exit;
        }
    }
}

