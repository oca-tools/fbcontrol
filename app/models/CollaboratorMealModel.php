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

    public function listByFilters(array $filters): array
    {
        $where = "WHERE 1=1";
        $params = [];
        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $where .= " AND DATE(c.criado_em) BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $filters['data_inicio'];
            $params[':data_fim'] = $filters['data_fim'];
        } elseif (!empty($filters['data'])) {
            $where .= " AND DATE(c.criado_em) = :data";
            $params[':data'] = $filters['data'];
        }
        if (!empty($filters['restaurante_id'])) {
            $where .= " AND c.restaurante_id = :restaurante_id";
            $params[':restaurante_id'] = $filters['restaurante_id'];
        }
        if (!empty($filters['operacao_id'])) {
            $where .= " AND c.operacao_id = :operacao_id";
            $params[':operacao_id'] = $filters['operacao_id'];
        }

        $stmt = $this->db->prepare("
            SELECT c.*, r.nome AS restaurante, o.nome AS operacao, u.nome AS usuario
            FROM colaborador_refeicoes c
            JOIN restaurantes r ON r.id = c.restaurante_id
            JOIN operacoes o ON o.id = c.operacao_id
            JOIN usuarios u ON u.id = c.usuario_id
            $where
            ORDER BY c.criado_em ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
