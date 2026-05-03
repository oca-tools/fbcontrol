<?php
class AuditLogModel extends Model
{
    public function generalLogs(array $filters, int $limit = 200): array
    {
        $where = "WHERE 1=1";
        $params = [];
        $this->applyDateFilters($where, $params, 'a.criado_em', $filters);
        if (!empty($filters['usuario_id'])) {
            $where .= " AND a.usuario_id = :usuario_id";
            $params[':usuario_id'] = (int)$filters['usuario_id'];
        }
        if (!empty($filters['tabela'])) {
            $where .= " AND a.tabela = :tabela";
            $params[':tabela'] = (string)$filters['tabela'];
        }

        $stmt = $this->db->prepare("
            SELECT a.*, u.nome AS usuario
            FROM auditoria a
            LEFT JOIN usuarios u ON u.id = a.usuario_id
            $where
            ORDER BY a.criado_em DESC, a.id DESC
            LIMIT " . max(1, min(500, $limit)) . "
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function thematicLogs(array $filters, int $limit = 200): array
    {
        $where = "WHERE 1=1";
        $params = [];
        $this->applyDateFilters($where, $params, 'l.criado_em', $filters);
        if (!empty($filters['usuario_id'])) {
            $where .= " AND l.usuario_id = :usuario_id";
            $params[':usuario_id'] = (int)$filters['usuario_id'];
        }

        $stmt = $this->db->prepare("
            SELECT l.*, u.nome AS usuario, r.nome AS restaurante, t.hora AS turno_hora,
                   rsv.data_reserva, uh.numero AS uh_numero
            FROM reservas_tematicas_logs l
            JOIN reservas_tematicas rsv ON rsv.id = l.reserva_id
            JOIN restaurantes r ON r.id = rsv.restaurante_id
            JOIN reservas_tematicas_turnos t ON t.id = rsv.turno_id
            JOIN unidades_habitacionais uh ON uh.id = rsv.uh_id
            LEFT JOIN usuarios u ON u.id = l.usuario_id
            $where
            ORDER BY l.criado_em DESC, l.id DESC
            LIMIT " . max(1, min(500, $limit)) . "
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function shiftLogs(array $filters, int $limit = 200): array
    {
        $where = "WHERE 1=1";
        $params = [];
        $this->applyShiftDateFilters($where, $params, $filters);
        if (!empty($filters['usuario_id'])) {
            $where .= " AND t.usuario_id = :usuario_id";
            $params[':usuario_id'] = (int)$filters['usuario_id'];
        }

        $stmt = $this->db->prepare("
            SELECT t.*, u.nome AS usuario, r.nome AS restaurante, o.nome AS operacao,
                   COUNT(a.id) AS total_registros,
                   COALESCE(SUM(a.pax), 0) AS total_pax
            FROM turnos t
            JOIN usuarios u ON u.id = t.usuario_id
            JOIN restaurantes r ON r.id = t.restaurante_id
            JOIN operacoes o ON o.id = t.operacao_id
            LEFT JOIN acessos a ON a.turno_id = t.id
            $where
            GROUP BY t.id
            ORDER BY COALESCE(t.fim_em, t.inicio_em) DESC, t.id DESC
            LIMIT " . max(1, min(500, $limit)) . "
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function users(): array
    {
        return $this->db->query("SELECT id, nome, perfil FROM usuarios ORDER BY nome")->fetchAll();
    }

    private function applyDateFilters(string &$where, array &$params, string $field, array $filters): void
    {
        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $where .= " AND DATE($field) BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $filters['data_inicio'];
            $params[':data_fim'] = $filters['data_fim'];
            return;
        }
        if (!empty($filters['data'])) {
            $where .= " AND DATE($field) = :data";
            $params[':data'] = $filters['data'];
        }
    }

    private function applyShiftDateFilters(string &$where, array &$params, array $filters): void
    {
        $start = '';
        $end = '';

        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $start = (string)$filters['data_inicio'];
            $endDate = DateTimeImmutable::createFromFormat('Y-m-d', (string)$filters['data_fim']);
            if ($endDate instanceof DateTimeImmutable) {
                $end = $endDate->modify('+1 day')->format('Y-m-d');
            }
        } elseif (!empty($filters['data'])) {
            $start = (string)$filters['data'];
            $endDate = DateTimeImmutable::createFromFormat('Y-m-d', (string)$filters['data']);
            if ($endDate instanceof DateTimeImmutable) {
                $end = $endDate->modify('+1 day')->format('Y-m-d');
            }
        }

        if ($start !== '' && $end !== '') {
            $where .= " AND t.inicio_em >= :shift_inicio AND t.inicio_em < :shift_fim";
            $params[':shift_inicio'] = $start . ' 00:00:00';
            $params[':shift_fim'] = $end . ' 00:00:00';
        }
    }
}
