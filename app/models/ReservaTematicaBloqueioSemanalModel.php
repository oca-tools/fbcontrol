<?php

class ReservaTematicaBloqueioSemanalModel extends Model
{
    private bool $ensured = false;

    private function ensureTable(): void
    {
        if ($this->ensured) {
            return;
        }

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS reservas_tematicas_bloqueios_semanais (
                id INT AUTO_INCREMENT PRIMARY KEY,
                restaurante_id INT NOT NULL,
                dia_semana TINYINT NOT NULL,
                ativo TINYINT(1) NOT NULL DEFAULT 1,
                motivo VARCHAR(255) NULL,
                usuario_id INT NULL,
                criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_restaurante_dia (restaurante_id, dia_semana),
                KEY idx_dia_ativo (dia_semana, ativo),
                CONSTRAINT fk_bloqueio_semanal_restaurante FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id),
                CONSTRAINT fk_bloqueio_semanal_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->ensured = true;
    }

    public function seedDefaultsIfEmpty(): void
    {
        $this->ensureTable();

        $count = (int)$this->db->query("SELECT COUNT(*) FROM reservas_tematicas_bloqueios_semanais")->fetchColumn();
        if ($count > 0) {
            return;
        }

        $defaults = [
            ['pattern' => 'giardino', 'dia' => 2, 'motivo' => 'Fechamento semanal padrão: terça-feira'],
            ['pattern' => 'la brasa', 'dia' => 6, 'motivo' => 'Fechamento semanal padrão: sábado'],
            ['pattern' => 'ix', 'dia' => 0, 'motivo' => 'Fechamento semanal padrão: domingo'],
        ];

        $select = $this->db->prepare("SELECT id FROM restaurantes WHERE LOWER(nome) LIKE :pattern ORDER BY nome LIMIT 1");
        $insert = $this->db->prepare("
            INSERT INTO reservas_tematicas_bloqueios_semanais
            (restaurante_id, dia_semana, ativo, motivo, usuario_id, criado_em)
            VALUES (:restaurante_id, :dia_semana, 1, :motivo, NULL, NOW())
            ON DUPLICATE KEY UPDATE ativo = VALUES(ativo), motivo = VALUES(motivo)
        ");

        foreach ($defaults as $default) {
            $select->execute([':pattern' => '%' . $default['pattern'] . '%']);
            $restId = (int)($select->fetchColumn() ?: 0);
            if ($restId <= 0) {
                continue;
            }

            $insert->execute([
                ':restaurante_id' => $restId,
                ':dia_semana' => $default['dia'],
                ':motivo' => $default['motivo'],
            ]);
        }
    }

    public function isClosed(int $restauranteId, string $dataReserva): bool
    {
        $this->ensureTable();

        $timestamp = strtotime($dataReserva);
        if ($restauranteId <= 0 || !$timestamp) {
            return false;
        }

        $diaSemana = (int)date('w', $timestamp);
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM reservas_tematicas_bloqueios_semanais
            WHERE restaurante_id = :restaurante_id
              AND dia_semana = :dia_semana
              AND ativo = 1
        ");
        $stmt->execute([
            ':restaurante_id' => $restauranteId,
            ':dia_semana' => $diaSemana,
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    public function all(): array
    {
        $this->ensureTable();

        $stmt = $this->db->query("
            SELECT b.*, r.nome AS restaurante, u.nome AS usuario
            FROM reservas_tematicas_bloqueios_semanais b
            JOIN restaurantes r ON r.id = b.restaurante_id
            LEFT JOIN usuarios u ON u.id = b.usuario_id
            WHERE b.ativo = 1
            ORDER BY b.dia_semana, r.nome
        ");

        return $stmt->fetchAll();
    }

    public function activeByWeekday(int $diaSemana): array
    {
        $this->ensureTable();

        $stmt = $this->db->prepare("
            SELECT b.*, r.nome AS restaurante
            FROM reservas_tematicas_bloqueios_semanais b
            JOIN restaurantes r ON r.id = b.restaurante_id
            WHERE b.dia_semana = :dia_semana
              AND b.ativo = 1
            ORDER BY r.nome
        ");
        $stmt->execute([':dia_semana' => $diaSemana]);

        return $stmt->fetchAll();
    }

    public function find(int $restauranteId, int $diaSemana): ?array
    {
        $this->ensureTable();

        $stmt = $this->db->prepare("
            SELECT *
            FROM reservas_tematicas_bloqueios_semanais
            WHERE restaurante_id = :restaurante_id
              AND dia_semana = :dia_semana
            LIMIT 1
        ");
        $stmt->execute([
            ':restaurante_id' => $restauranteId,
            ':dia_semana' => $diaSemana,
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function setClosed(int $restauranteId, int $diaSemana, bool $closed, string $motivo, int $userId): void
    {
        $this->ensureTable();

        if ($diaSemana < 0 || $diaSemana > 6) {
            throw new InvalidArgumentException('Dia da semana inválido.');
        }

        $before = $this->find($restauranteId, $diaSemana) ?? [];
        $stmt = $this->db->prepare("
            INSERT INTO reservas_tematicas_bloqueios_semanais
            (restaurante_id, dia_semana, ativo, motivo, usuario_id, criado_em, atualizado_em)
            VALUES (:restaurante_id, :dia_semana, :ativo, :motivo, :usuario_id, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                ativo = VALUES(ativo),
                motivo = VALUES(motivo),
                usuario_id = VALUES(usuario_id),
                atualizado_em = NOW()
        ");
        $stmt->execute([
            ':restaurante_id' => $restauranteId,
            ':dia_semana' => $diaSemana,
            ':ativo' => $closed ? 1 : 0,
            ':motivo' => $motivo !== '' ? $motivo : null,
            ':usuario_id' => $userId,
        ]);

        $after = $this->find($restauranteId, $diaSemana) ?? [];
        $this->audit(
            $closed ? 'close_weekday' : 'reopen_weekday',
            $userId,
            $before,
            $after,
            'reservas_tematicas_bloqueios_semanais',
            isset($after['id']) ? (int)$after['id'] : null
        );
    }
}
