<?php
declare(strict_types=1);

final class ReservaTematicaPolicy
{
    /**
     * Converte a entrada textual de idades CHD em inteiros.
     *
     * @param string $raw Texto como "1y2y4y", "3m9m", "3y/3m" ou lista separada.
     * @return array Lista de idades validas.
     */
    public static function parseChdAges(string $raw): array
    {
        return array_map(
            static fn(array $entry): int => (int)$entry['idade'],
            self::parseChdAgeEntries($raw)
        );
    }

    /**
     * Normaliza idades de CHD preservando meses para impressão operacional.
     *
     * @return array<int, array{idade:int,label:string,unit:string}>
     */
    public static function parseChdAgeEntries(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $entries = [];
        if (preg_match_all('/(\d+)\s*([ym]?)/i', $raw, $matches, PREG_SET_ORDER) !== false && !empty($matches)) {
            $consumed = '';
            foreach ($matches as $match) {
                $consumed .= $match[0];
                $unit = strtolower((string)($match[2] ?? ''));
                $unit = $unit === 'm' ? 'm' : 'y';
                $value = (int)$match[1];

                if ($unit === 'm') {
                    if ($value < 0 || $value > 23) {
                        throw new RuntimeException('Meses de CHD devem estar entre 0 e 23. Use exemplos como 3m, 9m ou 1y.');
                    }
                    $entries[] = ['idade' => 0, 'label' => $value . 'm', 'unit' => 'm'];
                    continue;
                }

                if ($value < 0 || $value > 17) {
                    throw new RuntimeException('As idades de CHD devem estar entre 0 e 17 anos. Use exemplos como 3m, 9m ou 4y.');
                }
                $entries[] = ['idade' => $value, 'label' => $value . 'y', 'unit' => 'y'];
            }

            $remainder = preg_replace('/[\s,;\/]+/', '', $raw);
            $consumed = preg_replace('/[\s,;\/]+/', '', $consumed);
            if ($remainder !== $consumed) {
                throw new RuntimeException('Idade de CHD inválida. Use o formato 3y/3m, 3m9m, 1y2y4y ou separe por vírgula.');
            }

            return $entries;
        }

        throw new RuntimeException('Idade de CHD inválida. Use o formato 3y/3m, 3m9m, 1y2y4y ou separe por vírgula.');
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
