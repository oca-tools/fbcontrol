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

    public function listByFilters(array $filters): array
    {
        $where = "WHERE 1=1";
        $params = [];
        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $where .= " AND DATE(v.criado_em) BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $filters['data_inicio'];
            $params[':data_fim'] = $filters['data_fim'];
        } elseif (!empty($filters['data'])) {
            $where .= " AND DATE(v.criado_em) = :data";
            $params[':data'] = $filters['data'];
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

        $stmt = $this->db->prepare("
            SELECT v.*, r.nome AS restaurante, o.nome AS operacao, u.nome AS usuario
            FROM vouchers v
            JOIN restaurantes r ON r.id = v.restaurante_id
            JOIN operacoes o ON o.id = v.operacao_id
            JOIN usuarios u ON u.id = v.usuario_id
            $where
            ORDER BY v.criado_em ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
