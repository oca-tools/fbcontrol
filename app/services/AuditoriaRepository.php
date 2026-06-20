<?php
declare(strict_types=1);

final class AuditoriaRepository extends RepositoryBase
{
    /**
     * Persiste um evento de auditoria com payload sanitizado para rastreabilidade sem excesso de dados sensíveis.
     */
    public function registrarEvento(
        string $tipoAuditoria,
        ?int $usuarioId,
        array $dadosAntes,
        array $dadosDepois,
        string $tabela,
        ?int $registroId = null
    ): void {
        $stmt = $this->db->prepare("
            INSERT INTO auditoria (tabela, registro_id, acao, usuario_id, dados_antes, dados_depois, criado_em)
            VALUES (:tabela, :registro_id, :acao, :usuario_id, :dados_antes, :dados_depois, NOW())
        ");
        $stmt->execute([
            ':tabela' => $tabela,
            ':registro_id' => $registroId,
            ':acao' => substr($tipoAuditoria, 0, GovernancaConstants::MAX_AUDIT_ACTION_LENGTH),
            ':usuario_id' => $usuarioId,
            ':dados_antes' => json_encode(Model::sanitizeAuditPayload($dadosAntes), JSON_UNESCAPED_UNICODE),
            ':dados_depois' => json_encode(Model::sanitizeAuditPayload($dadosDepois), JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * Registra auditoria sem interromper a operação quando a trilha não puder ser gravada.
     */
    public function registrarEventoSeguro(
        string $tipoAuditoria,
        ?int $usuarioId,
        array $dadosAntes,
        array $dadosDepois,
        string $tabela,
        ?int $registroId = null
    ): void {
        try {
            $this->registrarEvento($tipoAuditoria, $usuarioId, $dadosAntes, $dadosDepois, $tabela, $registroId);
        } catch (Throwable $e) {
            error_log('[audit-log] ' . json_encode([
                'tipo_auditoria' => $tipoAuditoria,
                'usuario_id' => $usuarioId,
                'tabela' => $tabela,
                'registro_id' => $registroId,
                'erro' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * Lista eventos gerais para revisão de alterações administrativas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarEventosGerais(array $filters, int $limit = GovernancaConstants::AUDITORIA_QUERY_LIMIT): array
    {
        return (new AuditLogModel())->generalLogs($filters, $limit);
    }

    /**
     * Lista trilhas de reservas temáticas para auditoria de operação e exceções.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarEventosTematicos(array $filters, int $limit = GovernancaConstants::AUDITORIA_QUERY_LIMIT): array
    {
        return (new AuditLogModel())->thematicLogs($filters, $limit);
    }

    /**
     * Lista alterações de turno para acompanhar abertura, encerramento e fechamento automático.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarEventosTurnos(array $filters, int $limit = GovernancaConstants::AUDITORIA_QUERY_LIMIT): array
    {
        return (new AuditLogModel())->shiftLogs($filters, $limit);
    }

    /**
     * Lista usuários disponíveis como filtro de responsabilidade nas trilhas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarUsuariosParaFiltro(): array
    {
        return (new AuditLogModel())->users();
    }
}
