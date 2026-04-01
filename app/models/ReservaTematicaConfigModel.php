<?php
class ReservaTematicaConfigModel extends Model
{
    private ?bool $hasAutoCancelNoShowMinColumnCache = null;

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

    public function updateConfig(int $restauranteId, int $capacidadeTotal, array $turnos, int $userId, int $autoCancelNoShowMin = 0): void
    {
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

        foreach ($turnos as $turnoId => $capacidade) {
            $stmt = $this->db->prepare("
                INSERT INTO reservas_tematicas_config_turnos (restaurante_id, turno_id, capacidade)
                VALUES (:restaurante_id, :turno_id, :capacidade)
                ON DUPLICATE KEY UPDATE capacidade = :capacidade_upd
            ");
            $stmt->execute([
                ':restaurante_id' => $restauranteId,
                ':turno_id' => (int)$turnoId,
                ':capacidade' => (int)$capacidade,
                ':capacidade_upd' => (int)$capacidade,
            ]);
        }
    }
}


