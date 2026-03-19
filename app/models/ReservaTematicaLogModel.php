<?php
class ReservaTematicaLogModel extends Model
{
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


