<?php
class RestaurantModel extends Model
{
    public function all(): array
    {
        return $this->db->query("SELECT * FROM restaurantes ORDER BY nome")->fetchAll();
    }

    public function buffetOnly(): array
    {
        return $this->db->query("SELECT * FROM restaurantes WHERE tipo = 'buffet' ORDER BY nome")->fetchAll();
    }

    public function especiaisOnly(): array
    {
        return $this->db->query("SELECT * FROM restaurantes WHERE tipo IN ('tematico','area') ORDER BY nome")->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM restaurantes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch();
        return $item ?: null;
    }

    public function create(array $data, int $userId): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO restaurantes (nome, tipo, seleciona_porta_no_turno, exige_pax, ativo, criado_em)
            VALUES (:nome, :tipo, :seleciona_porta_no_turno, :exige_pax, :ativo, NOW())
        ");
        $stmt->execute([
            ':nome' => $data['nome'],
            ':tipo' => $data['tipo'] ?? 'buffet',
            ':seleciona_porta_no_turno' => $data['seleciona_porta_no_turno'] ?? 0,
            ':exige_pax' => $data['exige_pax'] ?? 1,
            ':ativo' => $data['ativo'] ?? 1,
        ]);
        $id = (int)$this->db->lastInsertId();
        $this->audit('create', $userId, [], array_merge($data, ['id' => $id]), 'restaurantes', $id);
        return $id;
    }

    public function update(int $id, array $data, int $userId): void
    {
        $before = $this->find($id) ?? [];
        $stmt = $this->db->prepare("
            UPDATE restaurantes
            SET nome = :nome,
                tipo = :tipo,
                seleciona_porta_no_turno = :seleciona_porta_no_turno,
                exige_pax = :exige_pax,
                ativo = :ativo
            WHERE id = :id
        ");
        $stmt->execute([
            ':nome' => $data['nome'],
            ':tipo' => $data['tipo'] ?? 'buffet',
            ':seleciona_porta_no_turno' => $data['seleciona_porta_no_turno'] ?? 0,
            ':exige_pax' => $data['exige_pax'] ?? 1,
            ':ativo' => $data['ativo'] ?? 1,
            ':id' => $id,
        ]);
        $after = $this->find($id) ?? [];
        $this->audit('update', $userId, $before, $after, 'restaurantes', $id);
    }
}
