<?php
declare(strict_types=1);

final class VoucherRepository extends RepositoryBase implements VoucherRepositoryInterface
{
    /**
     * Persiste o voucher vendido ou compensado e registra auditoria do comprovante anexado.
     */
    public function registrarVoucher(array $dadosVoucher, int $usuarioId): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO vouchers
            (turno_id, restaurante_id, operacao_id, nome_hospede, data_estadia, numero_reserva, servico_upselling, assinatura, data_venda, voucher_anexo_path, criado_em, usuario_id)
            VALUES (:turno_id, :restaurante_id, :operacao_id, :nome_hospede, :data_estadia, :numero_reserva, :servico_upselling, :assinatura, :data_venda, :voucher_anexo_path, NOW(), :usuario_id)
        ");
        $stmt->execute([
            ':turno_id' => $dadosVoucher['turno_id'] ?? null,
            ':restaurante_id' => (int)$dadosVoucher['restaurante_id'],
            ':operacao_id' => (int)$dadosVoucher['operacao_id'],
            ':nome_hospede' => (string)$dadosVoucher['nome_hospede'],
            ':data_estadia' => (string)$dadosVoucher['data_estadia'],
            ':numero_reserva' => (string)$dadosVoucher['numero_reserva'],
            ':servico_upselling' => (string)$dadosVoucher['servico_upselling'],
            ':assinatura' => (string)$dadosVoucher['assinatura'],
            ':data_venda' => (string)$dadosVoucher['data_venda'],
            ':voucher_anexo_path' => $dadosVoucher['voucher_anexo_path'] ?? null,
            ':usuario_id' => $usuarioId,
        ]);

        $voucherId = (int)$this->db->lastInsertId();
        $this->registrarAuditoria(
            ConsumosEVouchersConstants::AUDIT_ACTION_CREATE,
            $usuarioId,
            [],
            array_merge($dadosVoucher, ['id' => $voucherId]),
            ConsumosEVouchersConstants::AUDIT_ENTITY_VOUCHERS,
            $voucherId
        );
        return $voucherId;
    }

    /**
     * Recupera um voucher para exibicao segura do comprovante anexado.
     */
    public function buscarVoucher(int $voucherId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM vouchers WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $voucherId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Lista vouchers por periodo, restaurante e operacao para conferencia do caixa e auditoria.
     */
    public function listarVouchers(array $filters, ?int $limit = null, int $offset = 0): array
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
            {$where}
            ORDER BY v.criado_em ASC, v.id ASC
            {$paginationSql}
        ");
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        if ($limit !== null) {
            $stmt->bindValue(':limit', max(ConsumosEVouchersConstants::MIN_POSITIVE_INT, $limit), PDO::PARAM_INT);
            $stmt->bindValue(':offset', max(ConsumosEVouchersConstants::DEFAULT_ZERO, $offset), PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function buildWhere(array $filters, array &$params): string
    {
        $where = 'WHERE 1=1';
        $params = [];
        $this->applyCreatedAtFilter($where, $params, 'v.criado_em', $filters);
        if (!empty($filters['restaurante_id'])) {
            $where .= ' AND v.restaurante_id = :restaurante_id';
            $params[':restaurante_id'] = (int)$filters['restaurante_id'];
        }
        if (!empty($filters['operacao_id'])) {
            $where .= ' AND v.operacao_id = :operacao_id';
            $params[':operacao_id'] = (int)$filters['operacao_id'];
        }

        $where .= ' AND r.nome <> :privileged_restaurante AND o.nome <> :privileged_operacao';
        $params[':privileged_restaurante'] = ConsumosEVouchersConstants::PRIVILEGED_NAME;
        $params[':privileged_operacao'] = ConsumosEVouchersConstants::PRIVILEGED_NAME;
        return $where;
    }
}
