<?php
class ReservaTematicaLogModel extends Model
{
    /**
     * Retorna a linha do tempo completa de uma reserva, identificando o autor de cada evento.
     */
    public function historyByReservation(int $reservaId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                l.id,
                l.reserva_id,
                l.acao,
                l.usuario_id,
                l.dados_antes,
                l.dados_depois,
                l.justificativa,
                l.criado_em,
                COALESCE(NULLIF(TRIM(u.nome), ''), CONCAT('Usuário #', l.usuario_id)) AS usuario_nome
            FROM reservas_tematicas_logs l
            LEFT JOIN usuarios u ON u.id = l.usuario_id
            WHERE l.reserva_id = :reserva_id
            ORDER BY l.criado_em ASC, l.id ASC
        ");
        $stmt->execute([':reserva_id' => $reservaId]);
        return $stmt->fetchAll();
    }

    public function countManualByUserSince(int $userId, string $since): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM reservas_tematicas_logs
            WHERE usuario_id = :usuario_id
              AND acao = 'status'
              AND criado_em >= :since
        ");
        $stmt->execute([
            ':usuario_id' => $userId,
            ':since' => $since,
        ]);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function log(int $reservaId, string $acao, int $userId, array $antes = [], array $depois = [], ?string $justificativa = null): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO reservas_tematicas_logs
            (reserva_id, acao, usuario_id, dados_antes, dados_depois, justificativa, criado_em)
            VALUES (:reserva_id, :acao, :usuario_id, :antes, :depois, :justificativa, NOW())
        ");
        $stmt->execute([
            ':reserva_id' => $reservaId,
            ':acao' => $acao,
            ':usuario_id' => $userId,
            ':antes' => !empty($antes) ? json_encode($antes, JSON_UNESCAPED_UNICODE) : null,
            ':depois' => !empty($depois) ? json_encode($depois, JSON_UNESCAPED_UNICODE) : null,
            ':justificativa' => $justificativa,
        ]);
    }
}

