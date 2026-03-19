<?php
class RestaurantSpecialModel extends Model
{
    public function findByRestaurantType(int $restauranteId, string $tipo): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM restaurante_especiais
            WHERE restaurante_id = :restaurante_id AND tipo = :tipo AND ativo = 1
            LIMIT 1
        ");
        $stmt->execute([
            ':restaurante_id' => $restauranteId,
            ':tipo' => $tipo,
        ]);
        $item = $stmt->fetch();
        return $item ?: null;
    }

    public function byRestaurant(int $restauranteId): array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM restaurante_especiais
            WHERE restaurante_id = :id AND ativo = 1
            ORDER BY tipo
        ");
        $stmt->execute([':id' => $restauranteId]);
        return $stmt->fetchAll();
    }
}
