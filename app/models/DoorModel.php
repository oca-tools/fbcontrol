<?php
class DoorModel extends Model
{
    public function all(): array
    {
        return $this->db->query("
            SELECT p.*, r.nome AS restaurante
            FROM portas p
            JOIN restaurantes r ON r.id = p.restaurante_id
            ORDER BY r.nome, p.nome
        ")->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM portas WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch();
        return $item ?: null;
    }

    public function create(array $data, int $userId): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO portas (restaurante_id, nome, ativo, criado_em)
            VALUES (:restaurante_id, :nome, :ativo, NOW())
        ");
        $stmt->execute([
            ':restaurante_id' => $data['restaurante_id'],
            ':nome' => $data['nome'],
            ':ativo' => $data['ativo'] ?? 1,
        ]);
        $id = (int)$this->db->lastInsertId();
        $this->audit('create', $userId, [], array_merge($data, ['id' => $id]), 'portas', $id);
        return $id;
    }

    public function update(int $id, array $data, int $userId): void
    {
        $before = $this->find($id) ?? [];
        $stmt = $this->db->prepare("
            UPDATE portas SET restaurante_id = :restaurante_id, nome = :nome, ativo = :ativo WHERE id = :id
        ");
        $stmt->execute([
            ':restaurante_id' => $data['restaurante_id'],
            ':nome' => $data['nome'],
            ':ativo' => $data['ativo'] ?? 1,
            ':id' => $id,
        ]);
        $after = $this->find($id) ?? [];
        $this->audit('update', $userId, $before, $after, 'portas', $id);
    }

    public function byRestaurant(int $restauranteId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM portas WHERE restaurante_id = :id AND ativo = 1 ORDER BY nome");
        $stmt->execute([':id' => $restauranteId]);
        return $stmt->fetchAll();
    }
}
