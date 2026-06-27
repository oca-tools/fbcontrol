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
            SELECT modo
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
        $override = $stmt->fetch();
        if ($override) {
            return (string)($override['modo'] ?? 'fechado') !== 'aberto';
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
        if ($closed) {
            $this->setOverride($restauranteId, $dataReserva, 'fechado', $motivo, $userId);
            return;
        }

        $this->removeOverride($restauranteId, $dataReserva, $userId);
    }

    public function setOverride(int $restauranteId, string $dataReserva, string $modo, string $motivo, int $userId): void
    {
        if (!in_array($modo, ['fechado', 'aberto'], true)) {
            throw new InvalidArgumentException('Modo de disponibilidade inválido.');
        }

        $before = $this->find($restauranteId, $dataReserva) ?? [];
        $stmt = $this->db->prepare("
            INSERT INTO reservas_tematicas_bloqueios_datas
                (restaurante_id, data_reserva, ativo, modo, motivo, usuario_id, atualizado_em)
            VALUES
                (:restaurante_id, :data_reserva, 1, :modo, :motivo, :usuario_id, NOW())
            ON DUPLICATE KEY UPDATE
                ativo = 1,
                modo = VALUES(modo),
                motivo = VALUES(motivo),
                usuario_id = VALUES(usuario_id),
                atualizado_em = NOW()
        ");
        $stmt->execute([
            ':restaurante_id' => $restauranteId,
            ':data_reserva' => $dataReserva,
            ':modo' => $modo,
            ':motivo' => $motivo !== '' ? $motivo : null,
            ':usuario_id' => $userId,
        ]);
        $after = $this->find($restauranteId, $dataReserva) ?? [];
        $this->audit(
            $modo === 'aberto' ? 'open_date_exception' : 'close_date',
            $userId,
            $before,
            $after,
            'reservas_tematicas_bloqueios_datas',
            isset($after['id']) ? (int)$after['id'] : null
        );
    }

    public function removeOverride(int $restauranteId, string $dataReserva, int $userId): void
    {
        $before = $this->find($restauranteId, $dataReserva) ?? [];
        if ($before === []) {
            return;
        }

        $stmt = $this->db->prepare("
            UPDATE reservas_tematicas_bloqueios_datas
            SET ativo = 0, usuario_id = :usuario_id, atualizado_em = NOW()
            WHERE restaurante_id = :restaurante_id
              AND data_reserva = :data_reserva
        ");
        $stmt->execute([
            ':usuario_id' => $userId,
            ':restaurante_id' => $restauranteId,
            ':data_reserva' => $dataReserva,
        ]);

        $after = $this->find($restauranteId, $dataReserva) ?? [];
        $this->audit(
            'remove_date_override',
            $userId,
            $before,
            $after,
            'reservas_tematicas_bloqueios_datas',
            isset($after['id']) ? (int)$after['id'] : null
        );
    }
}
