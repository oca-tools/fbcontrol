<?php
class ShiftModel extends Model
{
    public function findExpiredActive(int $graceMinutes = 10, ?int $userId = null): array
    {
        $whereUser = '';
        $params = [
            ':grace_min' => max(0, $graceMinutes),
        ];
        if ($userId !== null) {
            $whereUser = ' AND t.usuario_id = :user_id';
            $params[':user_id'] = $userId;
        }

        $sql = "
            SELECT
                t.id,
                t.usuario_id
            FROM turnos t
            JOIN restaurante_operacoes ro
                ON ro.restaurante_id = t.restaurante_id
               AND ro.operacao_id = t.operacao_id
               AND ro.ativo = 1
            WHERE t.fim_em IS NULL
              {$whereUser}
              AND EXISTS (
                    SELECT 1
                    FROM acessos a
                    WHERE a.turno_id = t.id
              )
              AND NOW() >= DATE_ADD(
                    DATE_ADD(
                        CASE
                            WHEN ro.hora_fim < ro.hora_inicio
                                THEN DATE_ADD(TIMESTAMP(DATE(t.inicio_em), ro.hora_fim), INTERVAL 1 DAY)
                            ELSE TIMESTAMP(DATE(t.inicio_em), ro.hora_fim)
                        END,
                        INTERVAL ro.tolerancia_min MINUTE
                    ),
                    INTERVAL :grace_min MINUTE
              )
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function autoCloseExpired(int $graceMinutes = 10, ?int $userId = null): int
    {
        $expired = $this->findExpiredActive($graceMinutes, $userId);
        if (empty($expired)) {
            return 0;
        }

        $closed = 0;
        foreach ($expired as $row) {
            $this->end((int)$row['id'], (int)$row['usuario_id'], 'auto_close_timeout');
            $closed++;
        }
        return $closed;
    }

    public function getActiveByUser(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT t.*, r.nome AS restaurante, o.nome AS operacao, r.seleciona_porta_no_turno, r.exige_pax, p.nome AS porta
            FROM turnos t
            JOIN restaurantes r ON r.id = t.restaurante_id
            JOIN operacoes o ON o.id = t.operacao_id
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
            INSERT INTO turnos (usuario_id, restaurante_id, operacao_id, porta_id, inicio_em)
            VALUES (:usuario_id, :restaurante_id, :operacao_id, :porta_id, NOW())
        ");
        $stmt->execute([
            ':usuario_id' => $userId,
            ':restaurante_id' => $data['restaurante_id'],
            ':operacao_id' => $data['operacao_id'],
            ':porta_id' => $data['porta_id'] ?? null,
        ]);
        $id = (int)$this->db->lastInsertId();
        $this->audit('create', $userId, [], array_merge($data, ['id' => $id]), 'turnos', $id);
        return $id;
    }

    public function end(int $turnoId, int $userId, string $auditAction = 'update'): void
    {
        $before = $this->find($turnoId) ?? [];
        $stmt = $this->db->prepare("UPDATE turnos SET fim_em = NOW() WHERE id = :id");
        $stmt->execute([':id' => $turnoId]);
        $after = $this->find($turnoId) ?? [];
        $this->audit($auditAction, $userId, $before, $after, 'turnos', $turnoId);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM turnos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch();
        return $item ?: null;
    }

    public function summary(int $turnoId): array
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total_acessos, SUM(pax) AS total_pax
            FROM acessos
            WHERE turno_id = :turno_id
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
            SELECT t.*, r.nome AS restaurante, o.nome AS operacao, u.nome AS usuario, p.nome AS porta
            FROM turnos t
            JOIN restaurantes r ON r.id = t.restaurante_id
            JOIN operacoes o ON o.id = t.operacao_id
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
            FROM turnos t
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
                o.nome AS operacao,
                COALESCE(a.total_acessos, 0) AS total_acessos,
                COALESCE(a.total_pax, 0) AS total_pax
            FROM turnos t
            JOIN restaurantes r ON r.id = t.restaurante_id
            JOIN operacoes o ON o.id = t.operacao_id
            LEFT JOIN (
                SELECT turno_id, COUNT(*) AS total_acessos, SUM(pax) AS total_pax
                FROM acessos
                GROUP BY turno_id
            ) a ON a.turno_id = t.id
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
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM turnos t
            WHERE t.usuario_id = :user_id
              AND t.fim_em IS NOT NULL
              AND EXISTS (
                  SELECT 1
                  FROM acessos a
                  WHERE a.turno_id = t.id
              )
        ");
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }
}
