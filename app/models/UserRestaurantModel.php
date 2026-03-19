<?php
class UserRestaurantModel extends Model
{
    public function byUser(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT r.*
            FROM usuarios_restaurantes ur
            JOIN restaurantes r ON r.id = ur.restaurante_id
            WHERE ur.usuario_id = :user_id AND ur.ativo = 1 AND r.ativo = 1
            ORDER BY r.nome
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function mapByUsers(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $this->db->prepare("
            SELECT usuario_id, restaurante_id
            FROM usuarios_restaurantes
            WHERE ativo = 1 AND usuario_id IN ($placeholders)
        ");
        $stmt->execute($userIds);
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[$row['usuario_id']][] = (int)$row['restaurante_id'];
        }
        return $map;
    }

    public function assign(int $userId, int $restauranteId, int $adminId): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO usuarios_restaurantes (usuario_id, restaurante_id, ativo, criado_em)
            VALUES (:usuario_id, :restaurante_id, 1, NOW())
        ");
        $stmt->execute([
            ':usuario_id' => $userId,
            ':restaurante_id' => $restauranteId,
        ]);
        $this->audit('create', $adminId, [], ['usuario_id' => $userId, 'restaurante_id' => $restauranteId], 'usuarios_restaurantes', null);
    }

    public function clearByUser(int $userId, int $adminId): void
    {
        $before = ['usuario_id' => $userId];
        $stmt = $this->db->prepare("UPDATE usuarios_restaurantes SET ativo = 0 WHERE usuario_id = :usuario_id");
        $stmt->execute([':usuario_id' => $userId]);
        $this->audit('update', $adminId, $before, ['usuario_id' => $userId, 'action' => 'clear'], 'usuarios_restaurantes', null);
    }
}
