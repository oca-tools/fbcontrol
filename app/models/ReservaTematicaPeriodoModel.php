<?php
class ReservaTematicaPeriodoModel extends Model
{
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM reservas_tematicas_periodos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

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

    public function updateBatch(array $items, int $userId): void
    {
        foreach ($items as $id => $data) {
            $before = $this->find((int)$id) ?? [];
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
            $after = $this->find((int)$id) ?? [];
            if ($before !== $after) {
                $this->audit(
                    'update_periodo_tematico',
                    $userId,
                    $before,
                    $after,
                    'reservas_tematicas_periodos',
                    (int)$id
                );
            }
        }
    }

    public function create(string $horaInicio, string $horaFim, int $ativo = 1, int $ordem = 0, int $userId = 0): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO reservas_tematicas_periodos (hora_inicio, hora_fim, ativo, ordem)
            VALUES (:hora_inicio, :hora_fim, :ativo, :ordem)
        ");
        $stmt->execute([
            ':hora_inicio' => $horaInicio,
            ':hora_fim' => $horaFim,
            ':ativo' => $ativo ? 1 : 0,
            ':ordem' => $ordem,
        ]);

        $id = (int)$this->db->lastInsertId();
        if ($userId > 0) {
            $after = $this->find($id) ?? [];
            $this->audit('create_periodo_tematico', $userId, [], $after, 'reservas_tematicas_periodos', $id);
        }

        return $id;
    }
}
