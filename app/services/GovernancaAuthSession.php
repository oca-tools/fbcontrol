<?php
declare(strict_types=1);

final class GovernancaAuthSession
{
    /**
     * Confirma se há sessão autenticada para evitar reentrada no login.
     */
    public function isAuthenticated(): bool
    {
        return Auth::check();
    }

    /**
     * Inicia a sessão operacional de um usuário validado pelo serviço de autenticação.
     */
    public function login(array $usuario): void
    {
        Auth::login($usuario);
    }

    /**
     * Retorna o usuário autenticado para registrar trilhas de auditoria e LGPD.
     *
     * @return array<string, mixed>|null
     */
    public function user(): ?array
    {
        return Auth::user();
    }

    /**
     * Encerra a sessão para cumprir rastreabilidade de acesso e troca de operador.
     */
    public function logout(): void
    {
        Auth::logout();
    }

    /**
     * Bloqueia telas de governança para perfis fora da cadeia autorizada.
     *
     * @param array<int, string> $roles
     */
    public function requireRole(array $roles): void
    {
        Auth::requireRole($roles);
    }
}
