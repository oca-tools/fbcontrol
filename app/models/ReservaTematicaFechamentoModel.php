<?php
class ReservaTematicaFechamentoModel extends Model
{
    public function find(int $restauranteId, string $data, int $turnoId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT f.*, r.nome AS restaurante, t.hora AS turno_hora, u.nome AS usuario_nome
            FROM reservas_tematicas_fechamentos f
            JOIN restaurantes r ON r.id = f.restaurante_id
            JOIN reservas_tematicas_turnos t ON t.id = f.turno_id
            LEFT JOIN usuarios u ON u.id = f.usuario_id
            WHERE f.restaurante_id = :restaurante_id
              AND f.data_reserva = :data
              AND f.turno_id = :turno_id
            LIMIT 1
        ");
        $stmt->execute([
            ':restaurante_id' => $restauranteId,
            ':data' => $data,
            ':turno_id' => $turnoId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

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
        $before = $this->find($restauranteId, $data, $turnoId) ?? [];
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
        $after = $this->find($restauranteId, $data, $turnoId) ?? [];
        if ($before !== $after) {
            $this->audit(
                empty($before) ? 'close_turno_tematico' : 'update_close_turno_tematico',
                $userId,
                $before,
                $after,
                'reservas_tematicas_fechamentos',
                isset($after['id']) ? (int)$after['id'] : null
            );
        }
    }
}
