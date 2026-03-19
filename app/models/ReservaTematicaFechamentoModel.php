<?php
class ReservaTematicaFechamentoModel extends Model
{
    public function isClosed(int $restauranteId, string $data, int $turnoId): bool
    {
        $stmt = $this->db->prepare("
            SELECT id FROM reservas_tematicas_fechamentos
            WHERE restaurante_id = :restaurante_id AND data_reserva = :data AND turno_id = :turno_id
            LIMIT 1
        ");
        $stmt->execute([
            ':restaurante_id' => $restauranteId,
            ':data' => $data,
            ':turno_id' => $turnoId,
        ]);
        return (bool)$stmt->fetch();
    }

    public function close(int $restauranteId, string $data, int $turnoId, int $userId): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO reservas_tematicas_fechamentos
            (restaurante_id, data_reserva, turno_id, fechado_em, usuario_id)
            VALUES (:restaurante_id, :data, :turno_id, NOW(), :usuario_id)
        ");
        $stmt->execute([
            ':restaurante_id' => $restauranteId,
            ':data' => $data,
            ':turno_id' => $turnoId,
            ':usuario_id' => $userId,
        ]);
    }
}


