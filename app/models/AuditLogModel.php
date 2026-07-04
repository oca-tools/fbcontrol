<?php
class AuditLogModel extends Model
{
    public function generalLogs(array $filters, int $limit = 200, int $offset = 0): array
    {
        return $this->generalLogsPage($filters, $limit, $offset)['rows'];
    }

    public function generalLogsPage(array $filters, int $limit = 200, int $offset = 0): array
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
            SELECT SQL_CALC_FOUND_ROWS a.*, u.nome AS usuario
            FROM auditoria a
            LEFT JOIN usuarios u ON u.id = a.usuario_id
            $where
            ORDER BY a.criado_em DESC, a.id DESC
            LIMIT " . max(1, min(500, $limit)) . " OFFSET " . max(0, $offset) . "
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $total = (int)$this->db->query("SELECT FOUND_ROWS()")->fetchColumn();
        return ['rows' => $rows, 'total' => $total];
    }

    public function thematicLogs(array $filters, int $limit = 200, int $offset = 0): array
    {
        return $this->thematicLogsPage($filters, $limit, $offset)['rows'];
    }

    public function thematicLogsPage(array $filters, int $limit = 200, int $offset = 0): array
    {
        $where = "WHERE 1=1";
        $params = [];
        // A auditoria temática acompanha o dia operacional da reserva. A criação
        // pode ocorrer dias antes e não deve desaparecer ao filtrar a data reservada.
        if (empty($filters['uh_numero'])) {
            $this->applyCreatedAtFilter($where, $params, 'rsv.data_reserva', $filters, 'thematic_reserva_data');
        }
        if (!empty($filters['usuario_id'])) {
            $where .= " AND (rsv.usuario_id = :reserva_usuario_id OR l.usuario_id = :acao_usuario_id)";
            $params[':reserva_usuario_id'] = (int)$filters['usuario_id'];
            $params[':acao_usuario_id'] = (int)$filters['usuario_id'];
        }
        if (!empty($filters['uh_numero'])) {
            $where .= " AND (uh.numero = :uh_numero_atual OR uh_antes.numero = :uh_numero_antes OR uh_depois.numero = :uh_numero_depois)";
            $params[':uh_numero_atual'] = (string)$filters['uh_numero'];
            $params[':uh_numero_antes'] = (string)$filters['uh_numero'];
            $params[':uh_numero_depois'] = (string)$filters['uh_numero'];
        }

        $stmt = $this->db->prepare("
            SELECT SQL_CALC_FOUND_ROWS l.*, u.nome AS usuario, criador.nome AS reserva_criador,
                   COALESCE(r_depois.nome, r_antes.nome, r.nome, 'Registro indisponivel') AS restaurante,
                   COALESCE(t_depois.hora, t_antes.hora, t.hora) AS turno_hora,
                   COALESCE(
                       JSON_UNQUOTE(JSON_EXTRACT(l.dados_depois, '$.data_reserva')),
                       JSON_UNQUOTE(JSON_EXTRACT(l.dados_antes, '$.data_reserva')),
                       rsv.data_reserva
                   ) AS data_reserva,
                   COALESCE(uh_depois.numero, uh_antes.numero, uh.numero, 'Nao informado') AS uh_numero
            FROM reservas_tematicas_logs l
            LEFT JOIN reservas_tematicas rsv ON rsv.id = l.reserva_id
            LEFT JOIN restaurantes r ON r.id = rsv.restaurante_id
            LEFT JOIN reservas_tematicas_turnos t ON t.id = rsv.turno_id
            LEFT JOIN unidades_habitacionais uh ON uh.id = rsv.uh_id
            LEFT JOIN unidades_habitacionais uh_antes
                   ON uh_antes.id = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(l.dados_antes, '$.uh_id')), '') AS UNSIGNED)
            LEFT JOIN unidades_habitacionais uh_depois
                   ON uh_depois.id = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(l.dados_depois, '$.uh_id')), '') AS UNSIGNED)
            LEFT JOIN restaurantes r_antes
                   ON r_antes.id = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(l.dados_antes, '$.restaurante_id')), '') AS UNSIGNED)
            LEFT JOIN restaurantes r_depois
                   ON r_depois.id = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(l.dados_depois, '$.restaurante_id')), '') AS UNSIGNED)
            LEFT JOIN reservas_tematicas_turnos t_antes
                   ON t_antes.id = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(l.dados_antes, '$.turno_id')), '') AS UNSIGNED)
            LEFT JOIN reservas_tematicas_turnos t_depois
                   ON t_depois.id = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(l.dados_depois, '$.turno_id')), '') AS UNSIGNED)
            LEFT JOIN usuarios u ON u.id = l.usuario_id
            LEFT JOIN usuarios criador ON criador.id = rsv.usuario_id
            $where
            ORDER BY l.criado_em DESC, l.id DESC
            LIMIT " . max(1, min(500, $limit)) . " OFFSET " . max(0, $offset) . "
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $total = (int)$this->db->query("SELECT FOUND_ROWS()")->fetchColumn();
        return ['rows' => $rows, 'total' => $total];
    }

    public function shiftLogs(array $filters, int $limit = 200, int $offset = 0): array
    {
        return $this->shiftLogsPage($filters, $limit, $offset)['rows'];
    }

    public function shiftLogsPage(array $filters, int $limit = 200, int $offset = 0): array
    {
        $where = "WHERE 1=1";
        $params = [];
        $this->applyShiftDateFilters($where, $params, $filters);
        if (!empty($filters['usuario_id'])) {
            $where .= " AND t.usuario_id = :usuario_id";
            $params[':usuario_id'] = (int)$filters['usuario_id'];
        }

        $stmt = $this->db->prepare("
            SELECT SQL_CALC_FOUND_ROWS t.*, u.nome AS usuario, r.nome AS restaurante, o.nome AS operacao,
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
            LIMIT " . max(1, min(500, $limit)) . " OFFSET " . max(0, $offset) . "
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $total = (int)$this->db->query("SELECT FOUND_ROWS()")->fetchColumn();
        return ['rows' => $rows, 'total' => $total];
    }

    public function users(): array
    {
        return $this->db->query("SELECT id, nome, perfil FROM usuarios ORDER BY nome")->fetchAll();
    }

    private function applyDateFilters(string &$where, array &$params, string $field, array $filters): void
    {
        $this->applyCreatedAtFilter($where, $params, $field, $filters, 'audit_data');
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
