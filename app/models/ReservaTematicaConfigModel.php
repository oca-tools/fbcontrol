<?php
class ReservaTematicaConfigModel extends Model
{
    private ?bool $hasAutoCancelNoShowMinColumnCache = null;
    private ?bool $hasDateCapacityTableCache = null;

    private function findConfigRow(int $restauranteId): ?array
    {
        $autoCancelField = $this->hasAutoCancelNoShowMinColumn()
            ? "c.auto_cancel_no_show_min"
            : "0 AS auto_cancel_no_show_min";
        $stmt = $this->db->prepare("
            SELECT c.*, $autoCancelField, r.nome AS restaurante
            FROM reservas_tematicas_config c
            JOIN restaurantes r ON r.id = c.restaurante_id
            WHERE c.restaurante_id = :restaurante_id
            LIMIT 1
        ");
        $stmt->execute([':restaurante_id' => $restauranteId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function findTurnoConfigRow(int $restauranteId, int $turnoId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT ct.*, r.nome AS restaurante, t.hora AS turno_hora
            FROM reservas_tematicas_config_turnos ct
            JOIN restaurantes r ON r.id = ct.restaurante_id
            JOIN reservas_tematicas_turnos t ON t.id = ct.turno_id
            WHERE ct.restaurante_id = :restaurante_id
              AND ct.turno_id = :turno_id
            LIMIT 1
        ");
        $stmt->execute([
            ':restaurante_id' => $restauranteId,
            ':turno_id' => $turnoId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function findDateConfigRow(int $restauranteId, string $dateRef, int $turnoId): ?array
    {
        if (!$this->hasDateCapacityTable()) {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT cd.*, r.nome AS restaurante, t.hora AS turno_hora
            FROM reservas_tematicas_capacidades_datas cd
            JOIN restaurantes r ON r.id = cd.restaurante_id
            JOIN reservas_tematicas_turnos t ON t.id = cd.turno_id
            WHERE cd.restaurante_id = :restaurante_id
              AND cd.data_reserva = :data_reserva
              AND cd.turno_id = :turno_id
            LIMIT 1
        ");
        $stmt->execute([
            ':restaurante_id' => $restauranteId,
            ':data_reserva' => $dateRef,
            ':turno_id' => $turnoId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function hasAutoCancelNoShowMinColumn(): bool
    {
        if ($this->hasAutoCancelNoShowMinColumnCache !== null) {
            return $this->hasAutoCancelNoShowMinColumnCache;
        }
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM reservas_tematicas_config LIKE 'auto_cancel_no_show_min'");
            $this->hasAutoCancelNoShowMinColumnCache = (bool)$stmt->fetch();
        } catch (Throwable $e) {
            $this->hasAutoCancelNoShowMinColumnCache = false;
        }
        return $this->hasAutoCancelNoShowMinColumnCache;
    }

    public function configs(): array
    {
        $autoCancelField = $this->hasAutoCancelNoShowMinColumn()
            ? "c.auto_cancel_no_show_min"
            : "0 AS auto_cancel_no_show_min";
        return $this->db->query("
            SELECT c.*, $autoCancelField, r.nome AS restaurante
            FROM reservas_tematicas_config c
            JOIN restaurantes r ON r.id = c.restaurante_id
            ORDER BY r.nome
        ")->fetchAll();
    }

    public function turnosConfig(int $restauranteId, bool $onlyActive = true): array
    {
        $where = $onlyActive ? "WHERE t.ativo = 1" : "";
        $stmt = $this->db->prepare("
            SELECT t.id AS turno_id, t.hora, COALESCE(ct.capacidade, 0) AS capacidade
            FROM reservas_tematicas_turnos t
            LEFT JOIN reservas_tematicas_config_turnos ct
                ON ct.turno_id = t.id AND ct.restaurante_id = :restaurante_id
            $where
            ORDER BY t.ordem, t.hora
        ");
        $stmt->execute([':restaurante_id' => $restauranteId]);
        return $stmt->fetchAll();
    }

    public function turnosConfigForDate(int $restauranteId, string $dateRef, bool $onlyActive = true): array
    {
        $dateRef = sanitize_date_param($dateRef, '');
        if ($dateRef === '' || !$this->hasDateCapacityTable()) {
            return $this->turnosConfig($restauranteId, $onlyActive);
        }

        $where = $onlyActive ? "WHERE t.ativo = 1" : "";
        $stmt = $this->db->prepare("
            SELECT t.id AS turno_id,
                   t.hora,
                   COALESCE(cd.capacidade, ct.capacidade, 0) AS capacidade,
                   cd.capacidade AS capacidade_data
            FROM reservas_tematicas_turnos t
            LEFT JOIN reservas_tematicas_config_turnos ct
                ON ct.turno_id = t.id AND ct.restaurante_id = :restaurante_id
            LEFT JOIN reservas_tematicas_capacidades_datas cd
                ON cd.turno_id = t.id
               AND cd.restaurante_id = :restaurante_id_date
               AND cd.data_reserva = :data_reserva
            $where
            ORDER BY t.ordem, t.hora
        ");
        $stmt->execute([
            ':restaurante_id' => $restauranteId,
            ':restaurante_id_date' => $restauranteId,
            ':data_reserva' => $dateRef,
        ]);
        return $stmt->fetchAll();
    }

    public function updateDateConfig(string $dateRef, array $turnosByRestaurant, int $userId): void
    {
        $dateRef = sanitize_date_param($dateRef, '');
        if ($dateRef === '' || !$this->hasDateCapacityTable()) {
            return;
        }

        $stmt = $this->db->prepare("
            INSERT INTO reservas_tematicas_capacidades_datas
            (restaurante_id, data_reserva, turno_id, capacidade, usuario_id, atualizado_em)
            VALUES (:restaurante_id, :data_reserva, :turno_id, :capacidade, :usuario_id, NOW())
            ON DUPLICATE KEY UPDATE
                capacidade = :capacidade_upd,
                usuario_id = :usuario_id_upd,
                atualizado_em = NOW()
        ");

        foreach ($turnosByRestaurant as $restId => $turnos) {
            foreach ((array)$turnos as $turnoId => $capacidade) {
                $restauranteId = (int)$restId;
                $turnoIdInt = (int)$turnoId;
                $before = $this->findDateConfigRow($restauranteId, $dateRef, $turnoIdInt) ?? [];
                $stmt->execute([
                    ':restaurante_id' => $restauranteId,
                    ':data_reserva' => $dateRef,
                    ':turno_id' => $turnoIdInt,
                    ':capacidade' => max(0, (int)$capacidade),
                    ':capacidade_upd' => max(0, (int)$capacidade),
                    ':usuario_id' => $userId,
                    ':usuario_id_upd' => $userId,
                ]);
                $after = $this->findDateConfigRow($restauranteId, $dateRef, $turnoIdInt) ?? [];
                if ($before !== $after) {
                    $this->audit(
                        empty($before) ? 'create_date_capacity' : 'update_date_capacity',
                        $userId,
                        $before,
                        $after,
                        'reservas_tematicas_capacidades_datas',
                        isset($after['id']) ? (int)$after['id'] : null
                    );
                }
            }
        }
    }

    public function updateConfig(int $restauranteId, int $capacidadeTotal, array $turnos, int $userId, int $autoCancelNoShowMin = 0): void
    {
        $beforeConfig = $this->findConfigRow($restauranteId) ?? [];
        if ($this->hasAutoCancelNoShowMinColumn()) {
            $stmt = $this->db->prepare("
                INSERT INTO reservas_tematicas_config (restaurante_id, capacidade_total, auto_cancel_no_show_min, ativo)
                VALUES (:restaurante_id, :capacidade_total, :auto_cancel_no_show_min, 1)
                ON DUPLICATE KEY UPDATE
                    capacidade_total = :capacidade_total_upd,
                    auto_cancel_no_show_min = :auto_cancel_no_show_min_upd
            ");
            $stmt->execute([
                ':restaurante_id' => $restauranteId,
                ':capacidade_total' => $capacidadeTotal,
                ':capacidade_total_upd' => $capacidadeTotal,
                ':auto_cancel_no_show_min' => max(0, $autoCancelNoShowMin),
                ':auto_cancel_no_show_min_upd' => max(0, $autoCancelNoShowMin),
            ]);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO reservas_tematicas_config (restaurante_id, capacidade_total, ativo)
                VALUES (:restaurante_id, :capacidade_total, 1)
                ON DUPLICATE KEY UPDATE capacidade_total = :capacidade_total_upd
            ");
            $stmt->execute([
                ':restaurante_id' => $restauranteId,
                ':capacidade_total' => $capacidadeTotal,
                ':capacidade_total_upd' => $capacidadeTotal,
            ]);
        }
        $afterConfig = $this->findConfigRow($restauranteId) ?? [];
        if ($beforeConfig !== $afterConfig) {
            $this->audit(
                empty($beforeConfig) ? 'create_capacity_config' : 'update_capacity_config',
                $userId,
                $beforeConfig,
                $afterConfig,
                'reservas_tematicas_config',
                isset($afterConfig['id']) ? (int)$afterConfig['id'] : null
            );
        }

        foreach ($turnos as $turnoId => $capacidade) {
            $turnoIdInt = (int)$turnoId;
            $beforeTurno = $this->findTurnoConfigRow($restauranteId, $turnoIdInt) ?? [];
            $stmt = $this->db->prepare("
                INSERT INTO reservas_tematicas_config_turnos (restaurante_id, turno_id, capacidade)
                VALUES (:restaurante_id, :turno_id, :capacidade)
                ON DUPLICATE KEY UPDATE capacidade = :capacidade_upd
            ");
            $stmt->execute([
                ':restaurante_id' => $restauranteId,
                ':turno_id' => $turnoIdInt,
                ':capacidade' => (int)$capacidade,
                ':capacidade_upd' => (int)$capacidade,
            ]);
            $afterTurno = $this->findTurnoConfigRow($restauranteId, $turnoIdInt) ?? [];
            if ($beforeTurno !== $afterTurno) {
                $this->audit(
                    empty($beforeTurno) ? 'create_turn_capacity' : 'update_turn_capacity',
                    $userId,
                    $beforeTurno,
                    $afterTurno,
                    'reservas_tematicas_config_turnos',
                    isset($afterTurno['id']) ? (int)$afterTurno['id'] : null
                );
            }
        }
    }

    private function hasDateCapacityTable(): bool
    {
        if ($this->hasDateCapacityTableCache !== null) {
            return $this->hasDateCapacityTableCache;
        }
        try {
            $stmt = $this->db->query("SHOW TABLES LIKE 'reservas_tematicas_capacidades_datas'");
            $this->hasDateCapacityTableCache = (bool)$stmt->fetch();
        } catch (Throwable $e) {
            $this->hasDateCapacityTableCache = false;
        }
        return $this->hasDateCapacityTableCache;
    }
}
