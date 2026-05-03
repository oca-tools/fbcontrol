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
        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $where .= " AND c.criado_em >= :data_inicio_start AND c.criado_em < DATE_ADD(:data_fim_end, INTERVAL 1 DAY)";
            $params[':data_inicio_start'] = $filters['data_inicio'];
            $params[':data_fim_end'] = $filters['data_fim'];
        } elseif (!empty($filters['data'])) {
            $where .= " AND c.criado_em >= :data_start AND c.criado_em < DATE_ADD(:data_end, INTERVAL 1 DAY)";
            $params[':data_start'] = $filters['data'];
            $params[':data_end'] = $filters['data'];
        }
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

    public function listByFilters(array $filters, ?int $limit = null, int $offset = 0): array
    {
        $params = [];
        $where = $this->buildWhere($filters, $params);
        $paginationSql = $limit !== null ? " LIMIT :limit OFFSET :offset" : "";

        $stmt = $this->db->prepare("
            SELECT c.*, r.nome AS restaurante, o.nome AS operacao, u.nome AS usuario
            FROM colaborador_refeicoes c
            JOIN restaurantes r ON r.id = c.restaurante_id
            JOIN operacoes o ON o.id = c.operacao_id
            JOIN usuarios u ON u.id = c.usuario_id
            $where
            ORDER BY c.criado_em ASC
            $paginationSql
        ");
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        if ($limit !== null) {
            $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
            $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
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
}
