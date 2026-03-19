<?php
class ReservaTematicaTurnoModel extends Model
{
    public function all(): array
    {
        return $this->db->query("
            SELECT * FROM reservas_tematicas_turnos
            ORDER BY ordem, hora
        ")->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM reservas_tematicas_turnos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function allActive(): array
    {
        return $this->db->query("
            SELECT * FROM reservas_tematicas_turnos
            WHERE ativo = 1
            ORDER BY ordem, hora
        ")->fetchAll();
    }

    public function updateBatch(array $items): void
    {
        foreach ($items as $id => $data) {
            $stmt = $this->db->prepare("
                UPDATE reservas_tematicas_turnos
                SET hora = :hora,
                    ativo = :ativo,
                    ordem = :ordem
                WHERE id = :id
            ");
            $stmt->execute([
                ':hora' => $data['hora'] ?? '19:00:00',
                ':ativo' => (int)($data['ativo'] ?? 0),
                ':ordem' => (int)($data['ordem'] ?? 0),
                ':id' => (int)$id,
            ]);
        }
    }
}


