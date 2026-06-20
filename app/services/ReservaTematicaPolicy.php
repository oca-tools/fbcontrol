<?php
declare(strict_types=1);

final class ReservaTematicaPolicy
{
    /**
     * Converte a entrada textual de idades CHD em inteiros.
     *
     * @param string $raw Texto como "1y2y4y" ou lista separada.
     * @return array Lista de idades validas.
     */
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

    /**
     * Indica se a UH pode ter reserva duplicada.
     *
     * @param string|null $uhNumero Numero da UH.
     * @return bool Verdadeiro quando a UH esta liberada para duplicidade.
     */
    public static function isDuplicateAllowedUh(?string $uhNumero): bool
    {
        return in_array(trim((string)$uhNumero), AppConstants::DUPLICATE_ALLOWED_UHS, true);
    }

    /**
     * Verifica se o usuario pode editar uma reserva.
     *
     * @param array $reserva Dados da reserva.
     * @param array $user Dados do usuario autenticado.
     * @return bool Verdadeiro quando a edicao e permitida.
     */
    public static function canEdit(array $reserva, array $user): bool
    {
        $perfil = (string)($user['perfil'] ?? '');
        if (in_array($perfil, AppConstants::MANAGEMENT_ROLES, true)) {
            return true;
        }

        return (int)($reserva['usuario_id'] ?? 0) === (int)($user['id'] ?? 0);
    }
}
