<?php
declare(strict_types=1);

final class RefeicaoColaboradorRepository extends RepositoryBase implements RefeicaoColaboradorRepositoryInterface
{
    /**
     * Persiste o consumo de refeicao de colaborador e cria trilha de auditoria do lancamento.
     */
    public function registrarRefeicao(array $dadosRefeicao, int $usuarioId): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO colaborador_refeicoes
            (turno_id, restaurante_id, operacao_id, nome_colaborador, quantidade, criado_em, usuario_id)
            VALUES (:turno_id, :restaurante_id, :operacao_id, :nome_colaborador, :quantidade, NOW(), :usuario_id)
        ");
        $stmt->execute([
            ':turno_id' => $dadosRefeicao['turno_id'] ?? null,
            ':restaurante_id' => (int)$dadosRefeicao['restaurante_id'],
            ':operacao_id' => (int)$dadosRefeicao['operacao_id'],
            ':nome_colaborador' => (string)$dadosRefeicao['nome_colaborador'],
            ':quantidade' => (int)$dadosRefeicao['quantidade'],
            ':usuario_id' => $usuarioId,
        ]);

        $refeicaoId = (int)$this->db->lastInsertId();
        $this->registrarAuditoria(
            ConsumosEVouchersConstants::AUDIT_ACTION_CREATE,
            $usuarioId,
            [],
            array_merge($dadosRefeicao, ['id' => $refeicaoId]),
            ConsumosEVouchersConstants::AUDIT_ENTITY_COLABORADOR_REFEICOES,
            $refeicaoId
        );
        return $refeicaoId;
    }
}
