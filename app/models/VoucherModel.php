<?php
class VoucherModel extends Model
{
    public function create(array $data, int $userId): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO vouchers
            (turno_id, restaurante_id, operacao_id, nome_hospede, data_estadia, numero_reserva, servico_upselling, assinatura, data_venda, voucher_anexo_path, criado_em, usuario_id)
            VALUES (:turno_id, :restaurante_id, :operacao_id, :nome_hospede, :data_estadia, :numero_reserva, :servico_upselling, :assinatura, :data_venda, :voucher_anexo_path, NOW(), :usuario_id)
        ");
        $stmt->execute([
            ':turno_id' => $data['turno_id'] ?? null,
            ':restaurante_id' => $data['restaurante_id'],
            ':operacao_id' => $data['operacao_id'],
            ':nome_hospede' => $data['nome_hospede'],
            ':data_estadia' => $data['data_estadia'],
            ':numero_reserva' => $data['numero_reserva'],
            ':servico_upselling' => $data['servico_upselling'],
            ':assinatura' => $data['assinatura'],
            ':data_venda' => $data['data_venda'],
            ':voucher_anexo_path' => $data['voucher_anexo_path'] ?? null,
            ':usuario_id' => $userId,
        ]);
        $id = (int)$this->db->lastInsertId();
        $this->audit('create', $userId, [], array_merge($data, ['id' => $id]), 'vouchers', $id);
        return $id;
    }

    private function buildWhere(array $filters, array &$params): string
    {
        $where = "WHERE 1=1";
        $params = [];
        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $where .= " AND v.criado_em >= :data_inicio_start AND v.criado_em < DATE_ADD(:data_fim_end, INTERVAL 1 DAY)";
            $params[':data_inicio_start'] = $filters['data_inicio'];
            $params[':data_fim_end'] = $filters['data_fim'];
        } elseif (!empty($filters['data'])) {
            $where .= " AND v.criado_em >= :data_start AND v.criado_em < DATE_ADD(:data_end, INTERVAL 1 DAY)";
            $params[':data_start'] = $filters['data'];
            $params[':data_end'] = $filters['data'];
        }
        if (!empty($filters['restaurante_id'])) {
            $where .= " AND v.restaurante_id = :restaurante_id";
            $params[':restaurante_id'] = $filters['restaurante_id'];
        }
        if (!empty($filters['operacao_id'])) {
            $where .= " AND v.operacao_id = :operacao_id";
            $params[':operacao_id'] = $filters['operacao_id'];
        }

        $where .= " AND r.nome <> 'Privileged' AND o.nome <> 'Privileged'";
        return $where;
    }

    public function listByFilters(array $filters, ?int $limit = null, int $offset = 0): array
    {
        $params = [];
        $where = $this->buildWhere($filters, $params);
        $paginationSql = $limit !== null ? " LIMIT :limit OFFSET :offset" : "";

        $stmt = $this->db->prepare("
            SELECT v.*, r.nome AS restaurante, o.nome AS operacao, u.nome AS usuario
            FROM vouchers v
            JOIN restaurantes r ON r.id = v.restaurante_id
            JOIN operacoes o ON o.id = v.operacao_id
            JOIN usuarios u ON u.id = v.usuario_id
            $where
            ORDER BY v.criado_em ASC
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
            FROM vouchers v
            JOIN restaurantes r ON r.id = v.restaurante_id
            JOIN operacoes o ON o.id = v.operacao_id
            $where
        ");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }
}
