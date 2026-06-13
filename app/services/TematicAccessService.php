<?php
declare(strict_types=1);

final class TematicAccessService
{
    public static function isTematicRestaurant(string $name): bool
    {
        $name = self::normalize($name);
        return strpos($name, 'giardino') !== false
            || strpos($name, 'la brasa') !== false
            || strpos($name, "ix'u") !== false
            || strpos($name, 'ixu') !== false
            || strpos($name, 'ix') !== false;
    }

    public static function isCorais(string $name): bool
    {
        return strpos(self::normalize($name), 'corais') !== false;
    }

    public static function isLaBrasa(string $name): bool
    {
        return strpos(self::normalize($name), 'la brasa') !== false;
    }

    public static function isTematicOperation(string $name): bool
    {
        return strpos(self::normalize($name), 'tematico') !== false;
    }

    public static function isTematicShift(array $shift): bool
    {
        $restaurant = (string)($shift['restaurante'] ?? '');
        if (!self::isTematicRestaurant($restaurant)) {
            return false;
        }

        if (self::isLaBrasa($restaurant)) {
            return self::isTematicOperation((string)($shift['operacao'] ?? ''));
        }

        return true;
    }

    public static function filterTematicRestaurants(array $restaurants): array
    {
        return array_values(array_filter(
            $restaurants,
            static fn(array $restaurant): bool => self::isTematicRestaurant(
                (string)($restaurant['nome'] ?? '')
            )
        ));
    }

    public function allTematicRestaurants(): array
    {
        return self::filterTematicRestaurants((new RestaurantModel())->all());
    }

    public function assignedRestaurants(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }
        return (new UserRestaurantModel())->byUser($userId);
    }

    public function tematicRestaurantsForUser(int $userId): array
    {
        return self::filterTematicRestaurants($this->assignedRestaurants($userId));
    }

    public function hostessHasCorais(int $userId): bool
    {
        foreach ($this->assignedRestaurants($userId) as $restaurant) {
            if (self::isCorais((string)($restaurant['nome'] ?? ''))) {
                return true;
            }
        }
        return false;
    }

    public function hostessHasTematicRestaurant(int $userId): bool
    {
        return $this->tematicRestaurantsForUser($userId) !== [];
    }

    public function canAccessModule(array $user): bool
    {
        $role = (string)($user['perfil'] ?? '');
        if (in_array($role, ['admin', 'supervisor', 'gerente'], true)) {
            return true;
        }
        if ($role !== 'hostess') {
            return false;
        }

        $userId = (int)($user['id'] ?? 0);
        return $this->hostessHasCorais($userId) || $this->hostessHasTematicRestaurant($userId);
    }

    private static function normalize(string $value): string
    {
        $value = mb_strtolower(normalize_mojibake($value), 'UTF-8');
        return strtr($value, [
            'á' => 'a',
            'à' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'é' => 'e',
            'ê' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ú' => 'u',
            'ç' => 'c',
        ]);
    }
}
