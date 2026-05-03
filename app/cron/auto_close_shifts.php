<?php
// Fecha automaticamente turnos ativos que excederam hora fim + tolerancia + 10 min.

declare(strict_types=1);

require __DIR__ . '/../bootstrap_cli.php';

$graceMinutes = 10;
$closedRegular = (new ShiftModel())->autoCloseExpired($graceMinutes, null);
$closedSpecial = (new SpecialShiftModel())->autoCloseExpired($graceMinutes, null);
$total = $closedRegular + $closedSpecial;

echo sprintf(
    "[%s] auto-close shifts: regular=%d special=%d total=%d\n",
    date('Y-m-d H:i:s'),
    $closedRegular,
    $closedSpecial,
    $total
);
