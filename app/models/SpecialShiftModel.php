<?php
class SpecialShiftModel extends Model
{
    public function getActiveByUser(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT t.*, r.nome AS restaurante, r.tipo AS restaurante_tipo, r.exige_pax, r.seleciona_porta_no_turno, p.nome AS porta
            FROM turnos_especiais t
            JOIN restaurantes r ON r.id = t.restaurante_id
            LEFT JOIN portas p ON p.id = t.porta_id
            WHERE t.usuario_id = :user_id AND t.fim_em IS NULL
            ORDER BY t.inicio_em DESC
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $userId]);
        $item = $stmt->fetch();
        return $item ?: null;
    }

    public function start(array $data, int $userId): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO turnos_especiais (usuario_id, restaurante_id, tipo, porta_id, inicio_em)
            VALUES (:usuario_id, :restaurante_id, :tipo, :porta_id, NOW())
        ");
        $stmt->execute([
            ':usuario_id' => $userId,
            ':restaurante_id' => $data['restaurante_id'],
            ':tipo' => $data['tipo'],
            ':porta_id' => $data['porta_id'] ?? null,
        ]);
        $id = (int)$this->db->lastInsertId();
        $this->audit('create', $userId, [], array_merge($data, ['id' => $id]), 'turnos_especiais', $id);
        return $id;
    }

    public function end(int $turnoId, int $userId): void
    {
        $before = $this->find($turnoId) ?? [];
        $stmt = $this->db->prepare("UPDATE turnos_especiais SET fim_em = NOW() WHERE id = :id");
        $stmt->execute([':id' => $turnoId]);
        $after = $this->find($turnoId) ?? [];
        $this->audit('update', $userId, $before, $after, 'turnos_especiais', $turnoId);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM turnos_especiais WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch();
        return $item ?: null;
    }

    public function summary(int $turnoId): array
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total_acessos, SUM(pax) AS total_pax
            FROM acessos_especiais
            WHERE turno_especial_id = :turno_id
        ");
        $stmt->execute([':turno_id' => $turnoId]);
        $row = $stmt->fetch();
        return [
            'total_acessos' => (int)($row['total_acessos'] ?? 0),
            'total_pax' => (int)($row['total_pax'] ?? 0),
        ];
    }

    public function listActive(): array
    {
        $stmt = $this->db->query("
            SELECT t.*, r.nome AS restaurante, t.tipo, u.nome AS usuario, p.nome AS porta
            FROM turnos_especiais t
            JOIN restaurantes r ON r.id = t.restaurante_id
            JOIN usuarios u ON u.id = t.usuario_id
            LEFT JOIN portas p ON p.id = t.porta_id
            WHERE t.fim_em IS NULL
            ORDER BY t.inicio_em DESC
        ");
        return $stmt->fetchAll();
    }

    public function activeRestaurants(): array
    {
        $stmt = $this->db->query("
            SELECT DISTINCT r.id, r.nome
            FROM turnos_especiais t
            JOIN restaurantes r ON r.id = t.restaurante_id
            WHERE t.fim_em IS NULL
            ORDER BY r.nome
        ");
        return $stmt->fetchAll();
    }

    public function listByUser(int $userId, int $limit = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT
                t.*,
                r.nome AS restaurante,
                CASE WHEN t.tipo = 'privileged' THEN 'Privileged' ELSE 'Temático' END AS operacao,
                COALESCE(a.total_acessos, 0) AS total_acessos,
                COALESCE(a.total_pax, 0) AS total_pax
            FROM turnos_especiais t
            JOIN restaurantes r ON r.id = t.restaurante_id
            LEFT JOIN (
                SELECT turno_especial_id, COUNT(*) AS total_acessos, SUM(pax) AS total_pax
                FROM acessos_especiais
                GROUP BY turno_especial_id
            ) a ON a.turno_especial_id = t.id
            WHERE t.usuario_id = :user_id
            ORDER BY t.inicio_em DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countCompletedByUser(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM turnos_especiais WHERE usuario_id = :user_id AND fim_em IS NOT NULL");
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }
}
