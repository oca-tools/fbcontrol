<?php
class KpiOccupancyModel extends Model
{
    private static ?bool $tableReady = null;

    private function ensureTable(): bool
    {
        if (self::$tableReady !== null) {
            return self::$tableReady;
        }

        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS kpi_ocupacao_diaria (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    data_ref DATE NOT NULL,
                    ocupacao_uh INT NULL,
                    ocupacao_pax INT NULL,
                    observacao VARCHAR(255) NULL,
                    atualizado_por INT NOT NULL,
                    atualizado_em DATETIME NOT NULL,
                    UNIQUE KEY uq_kpi_ocupacao_data (data_ref),
                    KEY idx_kpi_ocupacao_data (data_ref),
                    CONSTRAINT fk_kpi_ocupacao_usuario FOREIGN KEY (atualizado_por) REFERENCES usuarios(id)
                )
            ");
            self::$tableReady = true;
        } catch (Throwable $e) {
            self::$tableReady = false;
        }

        return self::$tableReady;
    }

    public function getByDate(string $dataRef): ?array
    {
        if (!$this->ensureTable()) {
            return null;
        }
        $stmt = $this->db->prepare("SELECT * FROM kpi_ocupacao_diaria WHERE data_ref = :data_ref LIMIT 1");
        $stmt->execute([':data_ref' => $dataRef]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $row['ocupacao_uh'] = $row['ocupacao_uh'] !== null ? (int)$row['ocupacao_uh'] : null;
        $row['ocupacao_pax'] = $row['ocupacao_pax'] !== null ? (int)$row['ocupacao_pax'] : null;
        return $row;
    }

    public function upsert(string $dataRef, ?int $ocupacaoUhs, ?int $ocupacaoPax, string $observacao, int $userId): bool
    {
        if (!$this->ensureTable()) {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO kpi_ocupacao_diaria
                (data_ref, ocupacao_uh, ocupacao_pax, observacao, atualizado_por, atualizado_em)
            VALUES
                (:data_ref, :ocupacao_uh, :ocupacao_pax, :observacao, :atualizado_por, NOW())
            ON DUPLICATE KEY UPDATE
                ocupacao_uh = VALUES(ocupacao_uh),
                ocupacao_pax = VALUES(ocupacao_pax),
                observacao = VALUES(observacao),
                atualizado_por = VALUES(atualizado_por),
                atualizado_em = NOW()
        ");

        return $stmt->execute([
            ':data_ref' => $dataRef,
            ':ocupacao_uh' => $ocupacaoUhs,
            ':ocupacao_pax' => $ocupacaoPax,
            ':observacao' => $observacao,
            ':atualizado_por' => $userId,
        ]);
    }

    public function history(string $dataInicio, string $dataFim, int $limit = 62): array
    {
        if (!$this->ensureTable()) {
            return [];
        }

        $limit = max(1, min(366, $limit));
        $stmt = $this->db->prepare("
            SELECT data_ref, ocupacao_uh, ocupacao_pax, observacao, atualizado_em
            FROM kpi_ocupacao_diaria
            WHERE data_ref BETWEEN :data_inicio AND :data_fim
            ORDER BY data_ref ASC
            LIMIT :lim
        ");
        $stmt->bindValue(':data_inicio', $dataInicio);
        $stmt->bindValue(':data_fim', $dataFim);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['ocupacao_uh'] = $row['ocupacao_uh'] !== null ? (int)$row['ocupacao_uh'] : null;
            $row['ocupacao_pax'] = $row['ocupacao_pax'] !== null ? (int)$row['ocupacao_pax'] : null;
        }
        unset($row);

        return $rows;
    }
}