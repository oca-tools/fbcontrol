<?php
class ReservaTematicaConfigModel extends Model
{
    public function configs(): array
    {
        return $this->db->query("
            SELECT c.*, r.nome AS restaurante
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

    public function updateConfig(int $restauranteId, int $capacidadeTotal, array $turnos, int $userId): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO reservas_tematicas_config (restaurante_id, capacidade_total, ativo)
            VALUES (:restaurante_id, :capacidade_total, 1)
            ON DUPLICATE KEY UPDATE capacidade_total = :capacidade_total
        ");
        $stmt->execute([
            ':restaurante_id' => $restauranteId,
            ':capacidade_total' => $capacidadeTotal,
        ]);

        foreach ($turnos as $turnoId => $capacidade) {
            $stmt = $this->db->prepare("
                INSERT INTO reservas_tematicas_config_turnos (restaurante_id, turno_id, capacidade)
                VALUES (:restaurante_id, :turno_id, :capacidade)
                ON DUPLICATE KEY UPDATE capacidade = :capacidade
            ");
            $stmt->execute([
                ':restaurante_id' => $restauranteId,
                ':turno_id' => (int)$turnoId,
                ':capacidade' => (int)$capacidade,
            ]);
        }
    }
}


