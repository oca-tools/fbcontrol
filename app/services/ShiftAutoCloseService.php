<?php
declare(strict_types=1);

final class ShiftAutoCloseService
{
    private const DEFAULT_GRACE_MINUTES = 10;

    public function closeForCurrentUser(): int
    {
        $user = Auth::user();
        if (!$user) {
            return 0;
        }

        return $this->closeForUser(
            (int)($user['id'] ?? 0),
            app_demo_mode_enabled(),
            self::DEFAULT_GRACE_MINUTES
        );
    }

    public function closeForUser(int $userId, bool $demoMode, int $graceMinutes = self::DEFAULT_GRACE_MINUTES): int
    {
        if ($userId <= 0 || $demoMode) {
            return 0;
        }

        $graceMinutes = max(0, $graceMinutes);
        return (new ShiftModel())->autoCloseExpired($graceMinutes, $userId)
            + (new SpecialShiftModel())->autoCloseExpired($graceMinutes, $userId);
    }
}
