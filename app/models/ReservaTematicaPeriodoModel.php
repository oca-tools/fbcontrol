<?php
class ReservaTematicaPeriodoModel extends Model
{
    public function all(): array
    {
        return $this->db->query("
            SELECT * FROM reservas_tematicas_periodos
            ORDER BY ordem, hora_inicio
        ")->fetchAll();
    }

    public function allActive(): array
    {
        return $this->db->query("
            SELECT * FROM reservas_tematicas_periodos
            WHERE ativo = 1
            ORDER BY ordem, hora_inicio
        ")->fetchAll();
    }

    public function updateBatch(array $items): void
    {
        foreach ($items as $id => $data) {
            $stmt = $this->db->prepare("
                UPDATE reservas_tematicas_periodos
                SET hora_inicio = :hora_inicio,
                    hora_fim = :hora_fim,
                    ativo = :ativo,
                    ordem = :ordem
                WHERE id = :id
            ");
            $stmt->execute([
                ':hora_inicio' => $data['hora_inicio'] ?? '08:30:00',
                ':hora_fim' => $data['hora_fim'] ?? '12:00:00',
                ':ativo' => (int)($data['ativo'] ?? 0),
                ':ordem' => (int)($data['ordem'] ?? 0),
                ':id' => (int)$id,
            ]);
        }
    }
}


