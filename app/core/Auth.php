<?php
class Auth
{
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
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
        }

        session_destroy();
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
