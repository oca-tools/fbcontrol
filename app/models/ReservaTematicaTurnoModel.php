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

        // Reservas/grupos são histórico operacional real. Fechamentos acidentais não devem impedir exclusão.
        if ($refs['reservas'] > 0 || $refs['grupos'] > 0) {
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

            if ($this->tableExists('reservas_tematicas_capacidades_datas')) {
                $stmtDateCfg = $this->db->prepare("DELETE FROM reservas_tematicas_capacidades_datas WHERE turno_id = :id");
                $stmtDateCfg->execute([':id' => $id]);
            }

            $stmtFechamentos = $this->db->prepare("DELETE FROM reservas_tematicas_fechamentos WHERE turno_id = :id");
            $stmtFechamentos->execute([':id' => $id]);

            $stmtTurno = $this->db->prepare("DELETE FROM reservas_tematicas_turnos WHERE id = :id");
            $stmtTurno->execute([':id' => $id]);

            $this->db->commit();
            return ['result' => 'deleted', 'refs' => $refs];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if ($e instanceof PDOException && (string)$e->getCode() === '23000') {
                $stmt = $this->db->prepare("
                    UPDATE reservas_tematicas_turnos
                    SET ativo = 0
                    WHERE id = :id
                ");
                $stmt->execute([':id' => $id]);
                return ['result' => 'inactivated', 'refs' => $refs];
            }
            throw $e;
        }
    }

    private function referenceCounters(int $id): array
    {
        $counters = [
            'reservas' => 0,
            'fechamentos' => 0,
            'grupos' => 0,
            'configs' => 0,
        ];

        $stmtReservas = $this->db->prepare("SELECT COUNT(*) FROM reservas_tematicas WHERE turno_id = :id");
        $stmtReservas->execute([':id' => $id]);
        $counters['reservas'] = (int)$stmtReservas->fetchColumn();

        $stmtFechamentos = $this->db->prepare("SELECT COUNT(*) FROM reservas_tematicas_fechamentos WHERE turno_id = :id");
        $stmtFechamentos->execute([':id' => $id]);
        $counters['fechamentos'] = (int)$stmtFechamentos->fetchColumn();

        if ($this->tableExists('reservas_tematicas_grupos')) {
            $stmtGrupos = $this->db->prepare("SELECT COUNT(*) FROM reservas_tematicas_grupos WHERE turno_id = :id");
            $stmtGrupos->execute([':id' => $id]);
            $counters['grupos'] = (int)$stmtGrupos->fetchColumn();
        }

        $stmtConfigs = $this->db->prepare("SELECT COUNT(*) FROM reservas_tematicas_config_turnos WHERE turno_id = :id");
        $stmtConfigs->execute([':id' => $id]);
        $counters['configs'] = (int)$stmtConfigs->fetchColumn();

        return $counters;
    }

    private function tableExists(string $tableName): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table_name
            LIMIT 1
        ");
        $stmt->execute([':table_name' => $tableName]);
        return (bool)$stmt->fetchColumn();
    }
}


