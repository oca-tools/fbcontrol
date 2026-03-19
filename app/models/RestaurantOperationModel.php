<?php
class RestaurantOperationModel extends Model
{
    public function all(): array
    {
        return $this->db->query("
            SELECT ro.*, r.nome AS restaurante, o.nome AS operacao
            FROM restaurante_operacoes ro
            JOIN restaurantes r ON r.id = ro.restaurante_id
            JOIN operacoes o ON o.id = ro.operacao_id
            ORDER BY r.nome, o.nome
        ")->fetchAll();
    }

    public function findByRestaurantOperation(int $restauranteId, int $operacaoId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM restaurante_operacoes
            WHERE restaurante_id = :restaurante_id AND operacao_id = :operacao_id AND ativo = 1
            LIMIT 1
        ");
        $stmt->execute([
            ':restaurante_id' => $restauranteId,
            ':operacao_id' => $operacaoId,
        ]);
        $item = $stmt->fetch();
        return $item ?: null;
    }

    public function byRestaurant(int $restauranteId): array
    {
        $stmt = $this->db->prepare("
            SELECT ro.*, o.nome AS operacao
            FROM restaurante_operacoes ro
            JOIN operacoes o ON o.id = ro.operacao_id
            WHERE ro.restaurante_id = :id AND ro.ativo = 1
            ORDER BY o.nome
        ");
        $stmt->execute([':id' => $restauranteId]);
        return $stmt->fetchAll();
    }

    public function create(array $data, int $userId): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO restaurante_operacoes
            (restaurante_id, operacao_id, hora_inicio, hora_fim, tolerancia_min, ativo)
            VALUES (:restaurante_id, :operacao_id, :hora_inicio, :hora_fim, :tolerancia_min, :ativo)
        ");
        $stmt->execute([
            ':restaurante_id' => $data['restaurante_id'],
            ':operacao_id' => $data['operacao_id'],
            ':hora_inicio' => $data['hora_inicio'],
            ':hora_fim' => $data['hora_fim'],
            ':tolerancia_min' => $data['tolerancia_min'] ?? 0,
            ':ativo' => $data['ativo'] ?? 1,
        ]);

        $id = (int)$this->db->lastInsertId();
        $this->audit('create', $userId, [], array_merge($data, ['id' => $id]), 'restaurante_operacoes', $id);
        return $id;
    }

    public function update(int $id, array $data, int $userId): void
    {
        $before = $this->findById($id) ?? [];
        $stmt = $this->db->prepare("
            UPDATE restaurante_operacoes
            SET restaurante_id = :restaurante_id,
                operacao_id = :operacao_id,
                hora_inicio = :hora_inicio,
                hora_fim = :hora_fim,
                tolerancia_min = :tolerancia_min,
                ativo = :ativo
            WHERE id = :id
        ");
        $stmt->execute([
            ':restaurante_id' => $data['restaurante_id'],
            ':operacao_id' => $data['operacao_id'],
            ':hora_inicio' => $data['hora_inicio'],
            ':hora_fim' => $data['hora_fim'],
            ':tolerancia_min' => $data['tolerancia_min'] ?? 0,
            ':ativo' => $data['ativo'] ?? 1,
            ':id' => $id,
        ]);
        $after = $this->findById($id) ?? [];
        $this->audit('update', $userId, $before, $after, 'restaurante_operacoes', $id);
    }

    private function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM restaurante_operacoes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch();
        return $item ?: null;
    }
}
