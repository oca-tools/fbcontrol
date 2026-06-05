<?php
class ReservaTematicaBloqueioDataModel extends Model
{
    public function find(int $restauranteId, string $dataReserva): ?array
    {
        $stmt = $this->db->prepare("
            SELECT b.*, u.nome AS usuario_nome
            FROM reservas_tematicas_bloqueios_datas b
            LEFT JOIN usuarios u ON u.id = b.usuario_id
            WHERE b.restaurante_id = :restaurante_id
              AND b.data_reserva = :data_reserva
            LIMIT 1
        ");
        $stmt->execute([
            ':restaurante_id' => $restauranteId,
            ':data_reserva' => $dataReserva,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function isClosed(int $restauranteId, string $dataReserva): bool
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM reservas_tematicas_bloqueios_datas
            WHERE restaurante_id = :restaurante_id
              AND data_reserva = :data_reserva
              AND ativo = 1
            LIMIT 1
        ");
        $stmt->execute([
            ':restaurante_id' => $restauranteId,
            ':data_reserva' => $dataReserva,
        ]);
        if ((bool)$stmt->fetch()) {
            return true;
        }

        return (new ReservaTematicaBloqueioSemanalModel())->isClosed($restauranteId, $dataReserva);
    }

    public function activeByDate(string $dataReserva): array
    {
        $stmt = $this->db->prepare("
            SELECT b.*, r.nome AS restaurante, u.nome AS usuario_nome
            FROM reservas_tematicas_bloqueios_datas b
            JOIN restaurantes r ON r.id = b.restaurante_id
            LEFT JOIN usuarios u ON u.id = b.usuario_id
            WHERE b.data_reserva = :data_reserva
              AND b.ativo = 1
            ORDER BY r.nome
        ");
        $stmt->execute([':data_reserva' => $dataReserva]);
        return $stmt->fetchAll();
    }

    public function setClosed(int $restauranteId, string $dataReserva, bool $closed, string $motivo, int $userId): void
    {
        $before = $this->find($restauranteId, $dataReserva) ?? [];
        $stmt = $this->db->prepare("
            INSERT INTO reservas_tematicas_bloqueios_datas
                (restaurante_id, data_reserva, ativo, motivo, usuario_id, atualizado_em)
            VALUES
                (:restaurante_id, :data_reserva, :ativo, :motivo, :usuario_id, NOW())
            ON DUPLICATE KEY UPDATE
                ativo = VALUES(ativo),
                motivo = VALUES(motivo),
                usuario_id = VALUES(usuario_id),
                atualizado_em = NOW()
        ");
        $stmt->execute([
            ':restaurante_id' => $restauranteId,
            ':data_reserva' => $dataReserva,
            ':ativo' => $closed ? 1 : 0,
            ':motivo' => $motivo !== '' ? $motivo : null,
            ':usuario_id' => $userId,
        ]);
        $after = $this->find($restauranteId, $dataReserva) ?? [];
        $this->audit(
            $closed ? 'close_date' : 'reopen_date',
            $userId,
            $before,
            $after,
            'reservas_tematicas_bloqueios_datas',
            isset($after['id']) ? (int)$after['id'] : null
        );
    }
}
