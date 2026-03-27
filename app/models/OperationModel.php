<?php
class OperationModel extends Model
{
    public function all(): array
    {
        return $this->db->query("SELECT * FROM operacoes ORDER BY nome")->fetchAll();
    }

    public function allBuffet(): array
    {
        return $this->db->query("
            SELECT *
            FROM operacoes
            WHERE nome NOT IN ('Tematico','Temático','Privileged','VIP Premium','Vip Premium')
            ORDER BY nome
        ")->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM operacoes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch();
        return $item ?: null;
    }

    public function create(array $data, int $userId): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO operacoes (nome, ativo, criado_em)
            VALUES (:nome, :ativo, NOW())
        ");
        $stmt->execute([
            ':nome' => $data['nome'],
            ':ativo' => $data['ativo'] ?? 1,
        ]);
        $id = (int)$this->db->lastInsertId();
        $this->audit('create', $userId, [], array_merge($data, ['id' => $id]), 'operacoes', $id);
        return $id;
    }

    public function update(int $id, array $data, int $userId): void
    {
        $before = $this->find($id) ?? [];
        $stmt = $this->db->prepare("
            UPDATE operacoes
            SET nome = :nome,
                ativo = :ativo
            WHERE id = :id
        ");
        $stmt->execute([
            ':nome' => $data['nome'],
            ':ativo' => $data['ativo'] ?? 1,
            ':id' => $id,
        ]);
        $after = $this->find($id) ?? [];
        $this->audit('update', $userId, $before, $after, 'operacoes', $id);
    }
}
