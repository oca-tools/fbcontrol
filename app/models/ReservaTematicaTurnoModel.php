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

    public function create(string $hora, int $ativo = 1, int $ordem = 0): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO reservas_tematicas_turnos (hora, ativo, ordem)
            VALUES (:hora, :ativo, :ordem)
        ");
        $stmt->execute([
            ':hora' => $hora,
            ':ativo' => $ativo ? 1 : 0,
            ':ordem' => $ordem,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function removeOrInactivate(int $id): array
    {
        $refs = $this->referenceCounters($id);

        // Se já existe histórico operacional, mantém histórico e apenas inativa.
        if ($refs['reservas'] > 0 || $refs['fechamentos'] > 0) {
            $stmt = $this->db->prepare("
                UPDATE reservas_tematicas_turnos
                SET ativo = 0
                WHERE id = :id
            ");
            $stmt->execute([':id' => $id]);
            return ['result' => 'inactivated', 'refs' => $refs];
        }

        // Sem histórico, remove do cadastro e limpa vínculos de capacidade.
        $this->db->beginTransaction();
        try {
            $stmtCfg = $this->db->prepare("DELETE FROM reservas_tematicas_config_turnos WHERE turno_id = :id");
            $stmtCfg->execute([':id' => $id]);

            $stmtTurno = $this->db->prepare("DELETE FROM reservas_tematicas_turnos WHERE id = :id");
            $stmtTurno->execute([':id' => $id]);

            $this->db->commit();
            return ['result' => 'deleted', 'refs' => $refs];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function referenceCounters(int $id): array
    {
        $counters = [
            'reservas' => 0,
            'fechamentos' => 0,
            'configs' => 0,
        ];

        $stmtReservas = $this->db->prepare("SELECT COUNT(*) FROM reservas_tematicas WHERE turno_id = :id");
        $stmtReservas->execute([':id' => $id]);
        $counters['reservas'] = (int)$stmtReservas->fetchColumn();

        $stmtFechamentos = $this->db->prepare("SELECT COUNT(*) FROM reservas_tematicas_fechamentos WHERE turno_id = :id");
        $stmtFechamentos->execute([':id' => $id]);
        $counters['fechamentos'] = (int)$stmtFechamentos->fetchColumn();

        $stmtConfigs = $this->db->prepare("SELECT COUNT(*) FROM reservas_tematicas_config_turnos WHERE turno_id = :id");
        $stmtConfigs->execute([':id' => $id]);
        $counters['configs'] = (int)$stmtConfigs->fetchColumn();

        return $counters;
    }
}


