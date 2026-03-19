<?php
class UnitModel extends Model
{
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM unidades_habitacionais WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch();
        return $item ?: null;
    }

    public function findByNumero(string $numero): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM unidades_habitacionais WHERE numero = :numero AND ativo = 1 LIMIT 1");
        $stmt->execute([':numero' => $numero]);
        $item = $stmt->fetch();
        return $item ?: null;
    }

    public function maxPaxForNumero(string $numero): ?int
    {
        $num = (int)$numero;
        if ($num >= 100 && $num <= 299) {
            return 4; // bangalos (series 100 e 200)
        }
        if ($num >= 300 && $num <= 1019) {
            return 5; // standards
        }
        if ($num >= 1101 && $num <= 1112) {
            return 5; // suites family
        }
        if ($num >= 2100 && $num <= 4322) {
            return 6; // area nova
        }
        return null;
    }

    public function create(array $data, int $userId): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO unidades_habitacionais (numero, ativo, criado_em)
            VALUES (:numero, :ativo, NOW())
        ");
        $stmt->execute([
            ':numero' => $data['numero'],
            ':ativo' => $data['ativo'] ?? 1,
        ]);
        $id = (int)$this->db->lastInsertId();
        $this->audit('create', $userId, [], array_merge($data, ['id' => $id]), 'unidades_habitacionais', $id);
        return $id;
    }
}
