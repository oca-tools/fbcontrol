<?php
declare(strict_types=1);

final class ShiftAutoCloseService
{
    /**
     * Fecha turnos expirados do usuario logado.
     *
     * @return int Quantidade de turnos fechados.
     */
    public function closeForCurrentUser(): int
    {
        $user = Auth::user();
        if (!$user) {
            return 0;
        }

        return $this->closeForUser(
            (int)($user['id'] ?? 0),
            app_demo_mode_enabled(),
            AppConstants::DEFAULT_SHIFT_GRACE_MINUTES
        );
    }

    /**
     * Fecha turnos comuns e especiais expirados para um usuario.
     *
     * @param int $userId Identificador do usuario responsavel.
     * @param bool $demoMode Indica se a aplicacao esta em modo demonstracao.
     * @param int $graceMinutes Minutos de tolerancia antes do fechamento.
     * @return int Quantidade total de turnos fechados.
     */
    public function closeForUser(int $userId, bool $demoMode, int $graceMinutes = AppConstants::DEFAULT_SHIFT_GRACE_MINUTES): int
    {
        if ($userId <= 0 || $demoMode) {
            return 0;
        }

        $graceMinutes = max(0, $graceMinutes);
        return (new ShiftModel())->autoCloseExpired($graceMinutes, $userId)
            + (new SpecialShiftModel())->autoCloseExpired($graceMinutes, $userId);
    }
}
