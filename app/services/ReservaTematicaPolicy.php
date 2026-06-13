<?php
declare(strict_types=1);

final class ReservaTematicaPolicy
{
    public static function parseChdAges(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $ages = [];
        foreach ((preg_split('/(?:y+|[,\s;]+)/i', $raw) ?: []) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            if (!ctype_digit($part)) {
                throw new RuntimeException('Idade de CHD inválida. Use o formato 1y2y4y.');
            }

            $age = (int)$part;
            if ($age < 0 || $age > 17) {
                throw new RuntimeException('As idades de CHD devem estar entre 0 e 17.');
            }
            $ages[] = $age;
        }

        return $ages;
    }

    public static function isDuplicateAllowedUh(?string $uhNumero): bool
    {
        return in_array(trim((string)$uhNumero), ['998', '999'], true);
    }

    public static function canEdit(array $reserva, array $user): bool
    {
        $perfil = (string)($user['perfil'] ?? '');
        if (in_array($perfil, ['admin', 'gerente', 'supervisor'], true)) {
            return true;
        }

        return (int)($reserva['usuario_id'] ?? 0) === (int)($user['id'] ?? 0);
    }
}
