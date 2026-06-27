<?php

class ReservaTematicaBloqueioSemanalModel extends Model
{
    public function seedDefaultsIfEmpty(): void
    {
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
