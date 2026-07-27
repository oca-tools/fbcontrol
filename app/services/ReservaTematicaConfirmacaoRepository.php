<?php
declare(strict_types=1);

final class ReservaTematicaConfirmacaoRepository extends RepositoryBase
{
    public function buscarReservasPorIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("
            SELECT rsv.id, rsv.grupo_id, rsv.data_reserva, rsv.pax, rsv.pax_adulto,
                   rsv.pax_chd, rsv.status, rsv.titular_nome, rsv.criado_em,
                   uh.numero AS uh_numero, r.nome AS restaurante,
                   TIME_FORMAT(t.hora, '%H:%i') AS turno,
                   g.responsavel_nome AS grupo_responsavel,
                   g.data_reserva AS grupo_data_reserva
            FROM reservas_tematicas rsv
            LEFT JOIN unidades_habitacionais uh ON uh.id = rsv.uh_id
            LEFT JOIN restaurantes r ON r.id = rsv.restaurante_id
            LEFT JOIN reservas_tematicas_turnos t ON t.id = rsv.turno_id
            LEFT JOIN reservas_tematicas_grupos g ON g.id = rsv.grupo_id
            WHERE rsv.id IN ({$placeholders})
            ORDER BY rsv.id
        ");
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }

    public function buscarReservasPorCorrelacao(string $correlationId, int $usuarioId): array
    {
        if ($correlationId === '' || $usuarioId <= 0) {
            return [];
        }

        try {
            $stmt = $this->db->prepare("\n                SELECT rsv.id\n                FROM reservas_tematicas rsv\n                WHERE rsv.correlation_id = :correlation_id\n                  AND rsv.usuario_id = :usuario_id\n                ORDER BY rsv.id\n            ");
            $stmt->execute([
                ':correlation_id' => $correlationId,
                ':usuario_id' => $usuarioId,
            ]);
            return $this->buscarReservasPorIds(array_column($stmt->fetchAll(), 'id'));
        } catch (Throwable $ignored) {
            // A migration pode ainda não ter sido aplicada em uma instalação antiga.
            return [];
        }
    }

    public function listarRecentesDoUsuario(int $usuarioId, int $limit = 40): array
    {
        $limit = max(10, min(100, $limit));
        $stmt = $this->db->prepare("
            SELECT rsv.id, rsv.grupo_id, rsv.data_reserva, rsv.pax, rsv.pax_adulto,
                   rsv.pax_chd, rsv.status, rsv.titular_nome, rsv.criado_em,
                   uh.numero AS uh_numero, r.nome AS restaurante,
                   TIME_FORMAT(t.hora, '%H:%i') AS turno,
                   g.responsavel_nome AS grupo_responsavel,
                   g.data_reserva AS grupo_data_reserva
            FROM reservas_tematicas rsv
            LEFT JOIN unidades_habitacionais uh ON uh.id = rsv.uh_id
            LEFT JOIN restaurantes r ON r.id = rsv.restaurante_id
            LEFT JOIN reservas_tematicas_turnos t ON t.id = rsv.turno_id
            LEFT JOIN reservas_tematicas_grupos g ON g.id = rsv.grupo_id
            WHERE rsv.usuario_id = :usuario_id
            ORDER BY rsv.criado_em DESC, rsv.id DESC
            LIMIT {$limit}
        ");
        $stmt->execute([':usuario_id' => $usuarioId]);
        return $stmt->fetchAll();
    }

    public function resumoDaData(string $data, array $restauranteIds): array
    {
        $restauranteIds = array_values(array_unique(array_filter(array_map('intval', $restauranteIds), static fn(int $id): bool => $id > 0)));
        if ($restauranteIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($restauranteIds), '?'));
        $stmt = $this->db->prepare("
            SELECT rsv.restaurante_id, r.nome AS restaurante, rsv.turno_id,
                   TIME_FORMAT(t.hora, '%H:%i') AS turno,
                   COUNT(*) AS reservas,
                   COUNT(DISTINCT rsv.grupo_id) AS grupos,
                   COALESCE(SUM(rsv.pax), 0) AS pax,
                   SUM(CASE WHEN rsv.status = 'Reservada' THEN 1 ELSE 0 END) AS pendentes,
                   MAX(rsv.criado_em) AS ultimo_cadastro
            FROM reservas_tematicas rsv
            JOIN restaurantes r ON r.id = rsv.restaurante_id
            JOIN reservas_tematicas_turnos t ON t.id = rsv.turno_id
            WHERE rsv.data_reserva = ?
              AND rsv.restaurante_id IN ({$placeholders})
              AND rsv.status <> 'Cancelada'
            GROUP BY rsv.restaurante_id, r.nome, rsv.turno_id, t.hora
            ORDER BY r.nome, t.hora
        ");
        $stmt->execute(array_merge([$data], $restauranteIds));
        return $stmt->fetchAll();
    }
}
