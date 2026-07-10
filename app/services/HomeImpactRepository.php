<?php
declare(strict_types=1);

final class HomeImpactRepository extends RepositoryBase
{
    public function reservasTematicas(): array
    {
        return $this->row("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN r.status <> 'Cancelada' THEN 1 ELSE 0 END) AS ativas,
                COALESCE(SUM(CASE WHEN r.status <> 'Cancelada' THEN r.pax ELSE 0 END), 0) AS pax_planejado,
                SUM(CASE WHEN r.status = 'Finalizada' THEN 1 ELSE 0 END) AS finalizadas,
                SUM(CASE WHEN r.status = 'Cancelada' THEN 1 ELSE 0 END) AS canceladas,
                COUNT(DISTINCT CASE WHEN r.grupo_id IS NOT NULL THEN r.grupo_id END) AS grupos,
                COUNT(DISTINCT CASE
                    WHEN r.status <> 'Cancelada'
                    THEN CONCAT(r.data_reserva, ':', r.restaurante_id, ':', r.turno_id)
                END) AS servicos_planejados,
                SUM(CASE WHEN r.usuario_id IS NOT NULL THEN 1 ELSE 0 END) AS com_autoria,
                SUM(CASE WHEN COALESCE(logs.tem_criacao, 0) = 1 THEN 1 ELSE 0 END) AS com_log_criacao,
                COALESCE(SUM(logs.total), 0) AS eventos_historico
            FROM reservas_tematicas r
            LEFT JOIN (
                SELECT reserva_id,
                       MAX(CASE WHEN acao = 'create' THEN 1 ELSE 0 END) AS tem_criacao,
                       COUNT(*) AS total
                FROM reservas_tematicas_logs
                GROUP BY reserva_id
            ) logs ON logs.reserva_id = r.id
        ");
    }

    public function alcance(): array
    {
        return $this->row("
            SELECT
                (SELECT COUNT(DISTINCT restaurante_id)
                   FROM reservas_tematicas) AS restaurantes,
                (SELECT COUNT(DISTINCT usuario_id)
                   FROM reservas_tematicas
                  WHERE usuario_id IS NOT NULL) AS operadores
        ");
    }

    public function periodo(): array
    {
        return $this->row("
            SELECT MIN(criado_em) AS inicio,
                   MAX(COALESCE(atualizado_em, criado_em)) AS fim
              FROM reservas_tematicas
        ");
    }

    private function row(string $sql): array
    {
        try {
            $row = $this->db->query($sql)->fetch();
            return is_array($row) ? $row : [];
        } catch (Throwable $error) {
            error_log('[home-impact] ' . $error->getMessage());
            return [];
        }
    }
}
