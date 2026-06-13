<?php
class CollaboratorMealModel extends Model
{
    public function create(array $data, int $userId): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO colaborador_refeicoes
            (turno_id, restaurante_id, operacao_id, nome_colaborador, quantidade, criado_em, usuario_id)
            VALUES (:turno_id, :restaurante_id, :operacao_id, :nome_colaborador, :quantidade, NOW(), :usuario_id)
        ");
        $stmt->execute([
            ':turno_id' => $data['turno_id'] ?? null,
            ':restaurante_id' => $data['restaurante_id'],
            ':operacao_id' => $data['operacao_id'],
            ':nome_colaborador' => $data['nome_colaborador'],
            ':quantidade' => $data['quantidade'],
            ':usuario_id' => $userId,
        ]);
        $id = (int)$this->db->lastInsertId();
        $this->audit('create', $userId, [], array_merge($data, ['id' => $id]), 'colaborador_refeicoes', $id);
        return $id;
    }

    private function buildWhere(array $filters, array &$params): string
    {
        $where = "WHERE 1=1";
        $params = [];
        $this->applyCreatedAtFilter($where, $params, 'c.criado_em', $filters);
        if (!empty($filters['restaurante_id'])) {
            $where .= " AND c.restaurante_id = :restaurante_id";
            $params[':restaurante_id'] = $filters['restaurante_id'];
        }
        if (!empty($filters['operacao_id'])) {
            $where .= " AND c.operacao_id = :operacao_id";
            $params[':operacao_id'] = $filters['operacao_id'];
        }
        return $where;
    }

    public function listByFilters(
        array $filters,
        ?int $limit = null,
        int $offset = 0,
        ?string $afterCreatedAt = null,
        int $afterId = 0
    ): array
    {
        $params = [];
        $where = $this->buildWhere($filters, $params);
        if ($afterCreatedAt !== null) {
            $where .= " AND (c.criado_em > :cursor_created_after OR (c.criado_em = :cursor_created_equal AND c.id > :cursor_id))";
            $params[':cursor_created_after'] = $afterCreatedAt;
            $params[':cursor_created_equal'] = $afterCreatedAt;
            $params[':cursor_id'] = $afterId;
        }
        $paginationSql = $limit !== null
            ? ($afterCreatedAt !== null ? " LIMIT :limit" : " LIMIT :limit OFFSET :offset")
            : "";

        $stmt = $this->db->prepare("
            SELECT c.*, r.nome AS restaurante, o.nome AS operacao, u.nome AS usuario
            FROM colaborador_refeicoes c
            JOIN restaurantes r ON r.id = c.restaurante_id
            JOIN operacoes o ON o.id = c.operacao_id
            JOIN usuarios u ON u.id = c.usuario_id
            $where
            ORDER BY c.criado_em ASC, c.id ASC
            $paginationSql
        ");
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        if ($limit !== null) {
            $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
            if ($afterCreatedAt === null) {
                $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
            }
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countByFilters(array $filters): int
    {
        $params = [];
        $where = $this->buildWhere($filters, $params);
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM colaborador_refeicoes c
            $where
        ");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function exportByFilters(array $filters, callable $callback, int $batchSize = 1000): int
    {
        $targetTotal = $this->countByFilters($filters);
        $batchSize = max(100, min(5000, $batchSize));
        $processed = 0;
        $afterCreatedAt = null;
        $afterId = 0;

        while ($processed < $targetTotal) {
            $rows = $this->listByFilters($filters, $batchSize, 0, $afterCreatedAt, $afterId);
            if (!$rows) {
                break;
            }

            foreach ($rows as $row) {
                if ($processed >= $targetTotal) {
                    break 2;
                }
                $callback($row);
                $processed++;
                $afterCreatedAt = (string)$row['criado_em'];
                $afterId = (int)$row['id'];
            }
        }

        return $processed;
    }
}
