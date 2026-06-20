<?php
declare(strict_types=1);

final class ShiftRepository extends RepositoryBase implements ShiftRepositoryInterface
{
    /**
     * Recupera o turno de salão ainda aberto do usuário, incluindo contexto
     * de restaurante, operação e porta para continuidade da operação.
     */
    public function turnoAtivoDoUsuario(int $usuarioId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT t.*, r.nome AS restaurante, o.nome AS operacao, r.seleciona_porta_no_turno, r.exige_pax, p.nome AS porta
            FROM turnos t
            JOIN restaurantes r ON r.id = t.restaurante_id
            JOIN operacoes o ON o.id = t.operacao_id
            LEFT JOIN portas p ON p.id = t.porta_id
            WHERE t.usuario_id = :usuario_id AND t.fim_em IS NULL
            ORDER BY t.inicio_em DESC
            LIMIT 1
        ");
        $stmt->execute([':usuario_id' => $usuarioId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Inicia um turno operacional de salão para a hostess e registra a abertura
     * na auditoria do sistema.
     */
    public function abrirTurno(array $dadosTurno, int $usuarioId): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO turnos (usuario_id, restaurante_id, operacao_id, porta_id, inicio_em, modo_demo)
            VALUES (:usuario_id, :restaurante_id, :operacao_id, :porta_id, NOW(), :modo_demo)
        ");
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':restaurante_id' => (int)$dadosTurno['restaurante_id'],
            ':operacao_id' => (int)$dadosTurno['operacao_id'],
            ':porta_id' => $dadosTurno['porta_id'] ?? null,
            ':modo_demo' => !empty($dadosTurno['modo_demo']) ? ControleSalaoConstants::SQL_TRUE : ControleSalaoConstants::SQL_FALSE,
        ]);

        $turnoId = (int)$this->db->lastInsertId();
        $this->registrarAuditoria(
            ControleSalaoConstants::ACTION_AUDIT_CREATE,
            $usuarioId,
            [],
            array_merge($dadosTurno, ['id' => $turnoId]),
            'turnos',
            $turnoId
        );
        return $turnoId;
    }

    /**
     * Finaliza o turno de salão e mantém trilha de auditoria da mudança de estado.
     */
    public function encerrarTurno(
        int $turnoId,
        int $usuarioId,
        string $acaoAuditoria = ControleSalaoConstants::ACTION_AUDIT_UPDATE
    ): void {
        $antes = $this->buscarTurnoPorId($turnoId) ?? [];
        $stmt = $this->db->prepare("UPDATE turnos SET fim_em = NOW() WHERE id = :id");
        $stmt->execute([':id' => $turnoId]);
        $depois = $this->buscarTurnoPorId($turnoId) ?? [];
        $this->registrarAuditoria($acaoAuditoria, $usuarioId, $antes, $depois, 'turnos', $turnoId);
    }

    /**
     * Resume os totais do turno encerrado para conferência operacional da saída.
     */
    public function resumoDoTurno(int $turnoId): array
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total_acessos, SUM(pax) AS total_pax
            FROM acessos
            WHERE turno_id = :turno_id
        ");
        $stmt->execute([':turno_id' => $turnoId]);
        $row = $stmt->fetch();
        return [
            'total_acessos' => (int)($row['total_acessos'] ?? 0),
            'total_pax' => (int)($row['total_pax'] ?? 0),
        ];
    }

    private function buscarTurnoPorId(int $turnoId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM turnos WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $turnoId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
