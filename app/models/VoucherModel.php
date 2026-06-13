<?php
class VoucherModel extends Model
{
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM vouchers WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

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
        $this->applyCreatedAtFilter($where, $params, 'v.criado_em', $filters);
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
            $where .= " AND (v.criado_em > :cursor_created_after OR (v.criado_em = :cursor_created_equal AND v.id > :cursor_id))";
            $params[':cursor_created_after'] = $afterCreatedAt;
            $params[':cursor_created_equal'] = $afterCreatedAt;
            $params[':cursor_id'] = $afterId;
        }
        $paginationSql = $limit !== null
            ? ($afterCreatedAt !== null ? " LIMIT :limit" : " LIMIT :limit OFFSET :offset")
            : "";

        $stmt = $this->db->prepare("
            SELECT v.*, r.nome AS restaurante, o.nome AS operacao, u.nome AS usuario
            FROM vouchers v
            JOIN restaurantes r ON r.id = v.restaurante_id
            JOIN operacoes o ON o.id = v.operacao_id
            JOIN usuarios u ON u.id = v.usuario_id
            $where
            ORDER BY v.criado_em ASC, v.id ASC
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
            FROM vouchers v
            JOIN restaurantes r ON r.id = v.restaurante_id
            JOIN operacoes o ON o.id = v.operacao_id
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
