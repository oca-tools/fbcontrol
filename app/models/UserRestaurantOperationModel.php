<?php
class UserRestaurantOperationModel extends Model
{
    private function tableExists(): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }
        try {
            $stmt = $this->db->query("SHOW TABLES LIKE 'usuarios_restaurantes_operacoes'");
            $exists = (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            $exists = false;
        }
        return $exists;
    }

    public function operationsByUser(int $userId): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        try {
            $stmt = $this->db->prepare("
                SELECT restaurante_id, operacao_id
                FROM usuarios_restaurantes_operacoes
                WHERE usuario_id = :usuario_id AND ativo = 1
            ");
            $stmt->execute([':usuario_id' => $userId]);
        } catch (Throwable $e) {
            return [];
        }

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $rid = (int)$row['restaurante_id'];
            $oid = (int)$row['operacao_id'];
            $map[$rid][] = $oid;
        }

        foreach ($map as $rid => $ops) {
            $map[$rid] = array_values(array_unique(array_map('intval', $ops)));
        }
        return $map;
    }

    public function mapByUsers(array $userIds): array
    {
        if (empty($userIds) || !$this->tableExists()) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        try {
            $stmt = $this->db->prepare("
                SELECT usuario_id, restaurante_id, operacao_id
                FROM usuarios_restaurantes_operacoes
                WHERE ativo = 1 AND usuario_id IN ($placeholders)
            ");
            $stmt->execute($userIds);
        } catch (Throwable $e) {
            return [];
        }

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $uid = (int)$row['usuario_id'];
            $rid = (int)$row['restaurante_id'];
            $oid = (int)$row['operacao_id'];
            if (!isset($map[$uid][$rid])) {
                $map[$uid][$rid] = [];
            }
            $map[$uid][$rid][] = $oid;
        }

        foreach ($map as $uid => $restMap) {
            foreach ($restMap as $rid => $ops) {
                $map[$uid][$rid] = array_values(array_unique(array_map('intval', $ops)));
            }
        }
        return $map;
    }

    public function assign(int $userId, int $restauranteId, int $operacaoId, int $adminId): void
    {
        if (!$this->tableExists()) {
            return;
        }
        $stmt = $this->db->prepare("
            INSERT INTO usuarios_restaurantes_operacoes (usuario_id, restaurante_id, operacao_id, ativo, criado_em)
            VALUES (:usuario_id, :restaurante_id, :operacao_id, 1, NOW())
        ");
        $stmt->execute([
            ':usuario_id' => $userId,
            ':restaurante_id' => $restauranteId,
            ':operacao_id' => $operacaoId,
        ]);

        $this->audit(
            'create',
            $adminId,
            [],
            ['usuario_id' => $userId, 'restaurante_id' => $restauranteId, 'operacao_id' => $operacaoId],
            'usuarios_restaurantes_operacoes',
            null
        );
    }

    public function clearByUser(int $userId, int $adminId): void
    {
        if (!$this->tableExists()) {
            return;
        }
        $stmt = $this->db->prepare("
            UPDATE usuarios_restaurantes_operacoes
            SET ativo = 0
            WHERE usuario_id = :usuario_id
        ");
        $stmt->execute([':usuario_id' => $userId]);

        $this->audit(
            'update',
            $adminId,
            ['usuario_id' => $userId],
            ['usuario_id' => $userId, 'action' => 'clear'],
            'usuarios_restaurantes_operacoes',
            null
        );
    }
}
