<?php
class ReservaTematicaModel extends Model
{
    private ?bool $hasPaxRealColumnCache = null;
    private ?bool $hasTitularNomeColumnCache = null;
    private const STATUS_NO_SHOW_VARIANTS = ['Nao compareceu', 'Não compareceu', 'Não compareceu', 'Não compareceu'];
    private const STATUS_DIVERGENCIA_VARIANTS = ['Divergencia', 'Divergência', 'Divergência', 'Divergência'];

    private function hasPaxRealColumn(): bool
    {
        if ($this->hasPaxRealColumnCache !== null) {
            return $this->hasPaxRealColumnCache;
        }
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM reservas_tematicas LIKE 'pax_real'");
            $this->hasPaxRealColumnCache = (bool)$stmt->fetch();
        } catch (Throwable $e) {
            $this->hasPaxRealColumnCache = false;
        }
        return $this->hasPaxRealColumnCache;
    }

    private function hasTitularNomeColumn(): bool
    {
        if ($this->hasTitularNomeColumnCache !== null) {
            return $this->hasTitularNomeColumnCache;
        }
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM reservas_tematicas LIKE 'titular_nome'");
            $this->hasTitularNomeColumnCache = (bool)$stmt->fetch();
        } catch (Throwable $e) {
            $this->hasTitularNomeColumnCache = false;
        }
        return $this->hasTitularNomeColumnCache;
    }

    private function paxComparecidaExpr(string $alias = 'rsv'): string
    {
        if ($this->hasPaxRealColumn()) {
            return "CASE
                        WHEN {$alias}.status = 'Cancelada' THEN 0
                        WHEN {$alias}.pax_real IS NOT NULL THEN {$alias}.pax_real
                        WHEN {$alias}.status = 'Finalizada' THEN {$alias}.pax
                        ELSE 0
                    END";
        }
        return "CASE
                    WHEN {$alias}.status = 'Cancelada' THEN 0
                    WHEN {$alias}.status = 'Finalizada' THEN {$alias}.pax
                    ELSE 0
                END";
    }

    private function paxRealOrReservedExpr(string $alias = 'rsv'): string
    {
        if ($this->hasPaxRealColumn()) {
            return "COALESCE({$alias}.pax_real, {$alias}.pax)";
        }
        return "{$alias}.pax";
    }

    private function statusInExpr(string $alias, array $variants): string
    {
        $quoted = array_map(static fn($value) => "'" . str_replace("'", "''", $value) . "'", $variants);
        return "{$alias}.status IN (" . implode(', ', $quoted) . ")";
    }

    private function noShowCondition(string $alias = 'rsv'): string
    {
        return $this->statusInExpr($alias, self::STATUS_NO_SHOW_VARIANTS);
    }

    private function divergenciaCondition(string $alias = 'rsv'): string
    {
        return $this->statusInExpr($alias, self::STATUS_DIVERGENCIA_VARIANTS);
    }
    private function appendStatusFilter(string &$where, array &$params, ?string $status, string $alias = 'rsv'): void
    {
        if ($status === null || $status === '') {
            return;
        }

        $statusNorm = normalize_mojibake(trim($status));
        if (in_array($statusNorm, self::STATUS_NO_SHOW_VARIANTS, true)) {
            $where .= " AND {$alias}.status IN (:status_no_show_a, :status_no_show_b, :status_no_show_c, :status_no_show_d)";
            $params[':status_no_show_a'] = 'Nao compareceu';
            $params[':status_no_show_b'] = 'Não compareceu';
            $params[':status_no_show_c'] = 'Não compareceu';
            $params[':status_no_show_d'] = 'Não compareceu';
            return;
        }
        if (in_array($statusNorm, self::STATUS_DIVERGENCIA_VARIANTS, true)) {
            $where .= " AND {$alias}.status IN (:status_div_a, :status_div_b, :status_div_c, :status_div_d)";
            $params[':status_div_a'] = 'Divergencia';
            $params[':status_div_b'] = 'Divergência';
            $params[':status_div_c'] = 'Divergência';
            $params[':status_div_d'] = 'Divergência';
            return;
        }

        $where .= " AND {$alias}.status = :status";
        $params[':status'] = $statusNorm;
    }

    public function create(array $data, int $userId): int
    {
        $columns = [
            'restaurante_id',
            'data_reserva',
            'turno_id',
            'uh_id',
            'pax',
        ];
        $values = [
            ':restaurante_id',
            ':data_reserva',
            ':turno_id',
            ':uh_id',
            ':pax',
        ];
        $params = [
            ':restaurante_id' => $data['restaurante_id'],
            ':data_reserva' => $data['data_reserva'],
            ':turno_id' => $data['turno_id'],
            ':uh_id' => $data['uh_id'],
            ':pax' => $data['pax'],
        ];

        if ($this->hasPaxRealColumn()) {
            $columns[] = 'pax_real';
            $values[] = ':pax_real';
            $params[':pax_real'] = $data['pax_real'] ?? null;
        }
        if ($this->hasTitularNomeColumn()) {
            $columns[] = 'titular_nome';
            $values[] = ':titular_nome';
            $params[':titular_nome'] = $data['titular_nome'] ?? null;
        }

        $columns = array_merge($columns, [
            'observacao_reserva',
            'observacao_tags',
            'status',
            'excedente',
            'excedente_motivo',
            'excedente_autor_id',
            'excedente_em',
            'usuario_id',
            'criado_em',
        ]);
        $values = array_merge($values, [
            ':obs',
            ':tags',
            ':status',
            ':excedente',
            ':excedente_motivo',
            ':excedente_autor_id',
            ':excedente_em',
            ':usuario_id',
            'NOW()',
        ]);
        $params = array_merge($params, [
            ':obs' => $data['observacao_reserva'] ?? null,
            ':tags' => $data['observacao_tags'] ?? null,
            ':status' => $data['status'] ?? 'Reservada',
            ':excedente' => $data['excedente'] ?? 0,
            ':excedente_motivo' => $data['excedente_motivo'] ?? null,
            ':excedente_autor_id' => $data['excedente_autor_id'] ?? null,
            ':excedente_em' => $data['excedente_em'] ?? null,
            ':usuario_id' => $userId,
        ]);

        $stmt = $this->db->prepare("
            INSERT INTO reservas_tematicas
            (" . implode(', ', $columns) . ")
            VALUES (" . implode(', ', $values) . ")
        ");
        $stmt->execute($params);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data, int $userId): void
    {
        $sets = [
            'restaurante_id = :restaurante_id',
            'data_reserva = :data_reserva',
            'turno_id = :turno_id',
            'uh_id = :uh_id',
            'pax = :pax',
        ];
        $params = [
            ':restaurante_id' => $data['restaurante_id'],
            ':data_reserva' => $data['data_reserva'],
            ':turno_id' => $data['turno_id'],
            ':uh_id' => $data['uh_id'],
            ':pax' => $data['pax'],
        ];

        if ($this->hasPaxRealColumn()) {
            $sets[] = 'pax_real = :pax_real';
            $params[':pax_real'] = $data['pax_real'] ?? null;
        }
        if ($this->hasTitularNomeColumn()) {
            $sets[] = 'titular_nome = :titular_nome';
            $params[':titular_nome'] = $data['titular_nome'] ?? null;
        }

        $sets = array_merge($sets, [
            'observacao_reserva = :obs',
            'observacao_tags = :tags',
            'status = :status',
            'excedente = :excedente',
            'excedente_motivo = :excedente_motivo',
            'excedente_autor_id = :excedente_autor_id',
            'excedente_em = :excedente_em',
            'atualizado_por = :atualizado_por',
            'atualizado_em = NOW()',
        ]);
        $params = array_merge($params, [
            ':obs' => $data['observacao_reserva'] ?? null,
            ':tags' => $data['observacao_tags'] ?? null,
            ':status' => $data['status'] ?? 'Reservada',
            ':excedente' => $data['excedente'] ?? 0,
            ':excedente_motivo' => $data['excedente_motivo'] ?? null,
            ':excedente_autor_id' => $data['excedente_autor_id'] ?? null,
            ':excedente_em' => $data['excedente_em'] ?? null,
            ':atualizado_por' => $userId,
            ':id' => $id,
        ]);

        $stmt = $this->db->prepare("
            UPDATE reservas_tematicas
            SET " . implode(",\n                    ", $sets) . "
            WHERE id = :id
        ");
        $stmt->execute($params);
    }

    public function updateOperacao(int $id, string $status, ?string $obsOperacao, int $userId, ?int $paxReal = null): void
    {
        if ($this->hasPaxRealColumn()) {
            $stmt = $this->db->prepare("
                UPDATE reservas_tematicas
                SET status = :status,
                    pax_real = CASE WHEN :pax_real_check IS NULL THEN pax_real ELSE :pax_real_value END,
                    observacao_operacao = :obs_operacao,
                    atualizado_por = :atualizado_por,
                    atualizado_em = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':status' => $status,
                ':pax_real_check' => $paxReal,
                ':pax_real_value' => $paxReal,
                ':obs_operacao' => $obsOperacao,
                ':atualizado_por' => $userId,
                ':id' => $id,
            ]);
        } else {
            $stmt = $this->db->prepare("
                UPDATE reservas_tematicas
                SET status = :status,
                    pax = CASE WHEN :pax_check IS NULL THEN pax ELSE :pax_value END,
                    observacao_operacao = :obs_operacao,
                    atualizado_por = :atualizado_por,
                    atualizado_em = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':status' => $status,
                ':pax_check' => $paxReal,
                ':pax_value' => $paxReal,
                ':obs_operacao' => $obsOperacao,
                ':atualizado_por' => $userId,
                ':id' => $id,
            ]);
        }
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM reservas_tematicas WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function listByFilters(array $filters): array
    {
        $where = "WHERE 1=1";
        $params = [];
        if (!empty($filters['restaurante_ids']) && is_array($filters['restaurante_ids'])) {
            $placeholders = [];
            foreach ($filters['restaurante_ids'] as $idx => $rid) {
                $key = ':rest_id_' . $idx;
                $placeholders[] = $key;
                $params[$key] = (int)$rid;
            }
            $placeholders = implode(',', $placeholders);
            $where .= " AND rsv.restaurante_id IN ($placeholders)";
        }
        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $where .= " AND rsv.data_reserva BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $filters['data_inicio'];
            $params[':data_fim'] = $filters['data_fim'];
        } elseif (!empty($filters['data'])) {
            $where .= " AND rsv.data_reserva = :data";
            $params[':data'] = $filters['data'];
        }
        if (!empty($filters['restaurante_id'])) {
            $where .= " AND rsv.restaurante_id = :restaurante_id";
            $params[':restaurante_id'] = $filters['restaurante_id'];
        }
        if (!empty($filters['turno_id'])) {
            $where .= " AND rsv.turno_id = :turno_id";
            $params[':turno_id'] = $filters['turno_id'];
        }
        if (!empty($filters['uh_numero'])) {
            $where .= " AND uh.numero = :uh";
            $params[':uh'] = $filters['uh_numero'];
        }
        if (!empty($filters['titular']) && $this->hasTitularNomeColumn()) {
            $where .= " AND rsv.titular_nome LIKE :titular";
            $params[':titular'] = '%' . $filters['titular'] . '%';
        }
        $this->appendStatusFilter($where, $params, $filters['status'] ?? null, 'rsv');

        $order = "ORDER BY rsv.data_reserva DESC, t.ordem, t.hora, r.nome";
        if (!empty($filters['order']) && $filters['order'] === 'status') {
            $order = "ORDER BY rsv.status, t.ordem, t.hora, uh.numero";
        }
        $stmt = $this->db->prepare("
            SELECT rsv.*, r.nome AS restaurante, t.hora AS turno_hora, uh.numero AS uh_numero, u.nome AS usuario
            FROM reservas_tematicas rsv
            JOIN restaurantes r ON r.id = rsv.restaurante_id
            JOIN reservas_tematicas_turnos t ON t.id = rsv.turno_id
            JOIN unidades_habitacionais uh ON uh.id = rsv.uh_id
            JOIN usuarios u ON u.id = rsv.usuario_id
            $where
            $order
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function summary(array $filters): array
    {
        $where = "WHERE 1=1";
        $params = [];
        $noShowCondition = $this->noShowCondition('rsv');
        $paxComparecidaExpr = $this->paxComparecidaExpr('rsv');
        if (!empty($filters['restaurante_ids']) && is_array($filters['restaurante_ids'])) {
            $placeholders = [];
            foreach ($filters['restaurante_ids'] as $idx => $rid) {
                $key = ':rest_id_' . $idx;
                $placeholders[] = $key;
                $params[$key] = (int)$rid;
            }
            $placeholders = implode(',', $placeholders);
            $where .= " AND rsv.restaurante_id IN ($placeholders)";
        }
        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $where .= " AND rsv.data_reserva BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $filters['data_inicio'];
            $params[':data_fim'] = $filters['data_fim'];
        } elseif (!empty($filters['data'])) {
            $where .= " AND rsv.data_reserva = :data";
            $params[':data'] = $filters['data'];
        }
        if (!empty($filters['restaurante_id'])) {
            $where .= " AND rsv.restaurante_id = :restaurante_id";
            $params[':restaurante_id'] = $filters['restaurante_id'];
        }
        if (!empty($filters['turno_id'])) {
            $where .= " AND rsv.turno_id = :turno_id";
            $params[':turno_id'] = $filters['turno_id'];
        }
        $this->appendStatusFilter($where, $params, $filters['status'] ?? null, 'rsv');

        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total_reservas,
                SUM(CASE WHEN rsv.status = 'Finalizada' THEN 1 ELSE 0 END) AS finalizadas,
                SUM(CASE WHEN {$noShowCondition} THEN 1 ELSE 0 END) AS no_shows,
                SUM(CASE WHEN rsv.status = 'Cancelada' THEN 1 ELSE 0 END) AS canceladas,
                SUM(CASE WHEN rsv.status <> 'Cancelada' THEN rsv.pax ELSE 0 END) AS pax_reservadas,
                SUM({$paxComparecidaExpr}) AS pax_comparecidas
            FROM reservas_tematicas rsv
            $where
        ");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return [
            'total_reservas' => (int)($row['total_reservas'] ?? 0),
            'finalizadas' => (int)($row['finalizadas'] ?? 0),
            'no_shows' => (int)($row['no_shows'] ?? 0),
            'canceladas' => (int)($row['canceladas'] ?? 0),
            'pax_reservadas' => (int)($row['pax_reservadas'] ?? 0),
            'pax_comparecidas' => (int)($row['pax_comparecidas'] ?? 0),
            'pax_nao_comparecidas' => max(0, (int)($row['pax_reservadas'] ?? 0) - (int)($row['pax_comparecidas'] ?? 0)),
        ];
    }

    public function totalsByRestaurant(array $filters): array
    {
        $where = "WHERE 1=1";
        $params = [];
        $noShowCondition = $this->noShowCondition('rsv');
        $paxComparecidaExpr = $this->paxComparecidaExpr('rsv');
        if (!empty($filters['restaurante_ids']) && is_array($filters['restaurante_ids'])) {
            $placeholders = [];
            foreach ($filters['restaurante_ids'] as $idx => $rid) {
                $key = ':rest_id_' . $idx;
                $placeholders[] = $key;
                $params[$key] = (int)$rid;
            }
            $placeholders = implode(',', $placeholders);
            $where .= " AND rsv.restaurante_id IN ($placeholders)";
        }
        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $where .= " AND rsv.data_reserva BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $filters['data_inicio'];
            $params[':data_fim'] = $filters['data_fim'];
        } elseif (!empty($filters['data'])) {
            $where .= " AND rsv.data_reserva = :data";
            $params[':data'] = $filters['data'];
        }
        if (!empty($filters['turno_id'])) {
            $where .= " AND rsv.turno_id = :turno_id";
            $params[':turno_id'] = $filters['turno_id'];
        }
        $this->appendStatusFilter($where, $params, $filters['status'] ?? null, 'rsv');

        $stmt = $this->db->prepare("
            SELECT r.nome AS restaurante,
                   COUNT(*) AS total,
                   SUM(CASE WHEN rsv.status = 'Finalizada' THEN 1 ELSE 0 END) AS finalizadas,
                   SUM(CASE WHEN {$noShowCondition} THEN 1 ELSE 0 END) AS no_shows,
                   SUM(CASE WHEN rsv.status = 'Cancelada' THEN 1 ELSE 0 END) AS canceladas,
                   SUM(CASE WHEN rsv.status <> 'Cancelada' THEN rsv.pax ELSE 0 END) AS pax_reservadas,
                   SUM({$paxComparecidaExpr}) AS pax_comparecidas
            FROM reservas_tematicas rsv
            JOIN restaurantes r ON r.id = rsv.restaurante_id
            $where
            GROUP BY r.id
            ORDER BY total DESC, r.nome
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function totalsByTurno(array $filters): array
    {
        $where = "WHERE 1=1";
        $params = [];
        $noShowCondition = $this->noShowCondition('rsv');
        $paxComparecidaExpr = $this->paxComparecidaExpr('rsv');
        if (!empty($filters['restaurante_ids']) && is_array($filters['restaurante_ids'])) {
            $placeholders = [];
            foreach ($filters['restaurante_ids'] as $idx => $rid) {
                $key = ':rest_id_' . $idx;
                $placeholders[] = $key;
                $params[$key] = (int)$rid;
            }
            $placeholders = implode(',', $placeholders);
            $where .= " AND rsv.restaurante_id IN ($placeholders)";
        }
        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $where .= " AND rsv.data_reserva BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $filters['data_inicio'];
            $params[':data_fim'] = $filters['data_fim'];
        } elseif (!empty($filters['data'])) {
            $where .= " AND rsv.data_reserva = :data";
            $params[':data'] = $filters['data'];
        }
        if (!empty($filters['restaurante_id'])) {
            $where .= " AND rsv.restaurante_id = :restaurante_id";
            $params[':restaurante_id'] = $filters['restaurante_id'];
        }
        $this->appendStatusFilter($where, $params, $filters['status'] ?? null, 'rsv');

        $stmt = $this->db->prepare("
            SELECT t.hora AS turno,
                   COUNT(*) AS total,
                   SUM(CASE WHEN rsv.status = 'Finalizada' THEN 1 ELSE 0 END) AS finalizadas,
                   SUM(CASE WHEN {$noShowCondition} THEN 1 ELSE 0 END) AS no_shows,
                   SUM(CASE WHEN rsv.status = 'Cancelada' THEN 1 ELSE 0 END) AS canceladas,
                   SUM(CASE WHEN rsv.status <> 'Cancelada' THEN rsv.pax ELSE 0 END) AS pax_reservadas,
                   SUM({$paxComparecidaExpr}) AS pax_comparecidas
            FROM reservas_tematicas rsv
            JOIN reservas_tematicas_turnos t ON t.id = rsv.turno_id
            $where
            GROUP BY t.id
            ORDER BY t.ordem, t.hora
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function totalsByDay(array $filters): array
    {
        $where = "WHERE 1=1";
        $params = [];
        $noShowCondition = $this->noShowCondition('rsv');
        $paxComparecidaExpr = $this->paxComparecidaExpr('rsv');
        if (!empty($filters['restaurante_ids']) && is_array($filters['restaurante_ids'])) {
            $placeholders = [];
            foreach ($filters['restaurante_ids'] as $idx => $rid) {
                $key = ':rest_id_' . $idx;
                $placeholders[] = $key;
                $params[$key] = (int)$rid;
            }
            $placeholders = implode(',', $placeholders);
            $where .= " AND rsv.restaurante_id IN ($placeholders)";
        }
        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $where .= " AND rsv.data_reserva BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $filters['data_inicio'];
            $params[':data_fim'] = $filters['data_fim'];
        } elseif (!empty($filters['data'])) {
            $where .= " AND rsv.data_reserva = :data";
            $params[':data'] = $filters['data'];
        }
        if (!empty($filters['restaurante_id'])) {
            $where .= " AND rsv.restaurante_id = :restaurante_id";
            $params[':restaurante_id'] = $filters['restaurante_id'];
        }
        if (!empty($filters['turno_id'])) {
            $where .= " AND rsv.turno_id = :turno_id";
            $params[':turno_id'] = $filters['turno_id'];
        }
        $this->appendStatusFilter($where, $params, $filters['status'] ?? null, 'rsv');

        $stmt = $this->db->prepare("
            SELECT rsv.data_reserva AS data,
                   COUNT(*) AS total,
                   SUM(CASE WHEN rsv.status = 'Finalizada' THEN 1 ELSE 0 END) AS finalizadas,
                   SUM(CASE WHEN {$noShowCondition} THEN 1 ELSE 0 END) AS no_shows,
                   SUM(CASE WHEN rsv.status = 'Cancelada' THEN 1 ELSE 0 END) AS canceladas,
                   SUM(CASE WHEN rsv.status <> 'Cancelada' THEN rsv.pax ELSE 0 END) AS pax_reservadas,
                   SUM({$paxComparecidaExpr}) AS pax_comparecidas
            FROM reservas_tematicas rsv
            $where
            GROUP BY rsv.data_reserva
            ORDER BY rsv.data_reserva DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function dashboardStats(array $filters): array
    {
        $where = "WHERE 1=1";
        $params = [];
        $noShowCondition = $this->noShowCondition('rsv');
        $paxComparecidaExpr = $this->paxComparecidaExpr('rsv');
        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $where .= " AND rsv.data_reserva BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $filters['data_inicio'];
            $params[':data_fim'] = $filters['data_fim'];
        } elseif (!empty($filters['data'])) {
            $where .= " AND rsv.data_reserva = :data";
            $params[':data'] = $filters['data'];
        }
        if (!empty($filters['restaurante_id'])) {
            $where .= " AND rsv.restaurante_id = :restaurante_id";
            $params[':restaurante_id'] = $filters['restaurante_id'];
        }

        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total_reservas,
                SUM(CASE WHEN rsv.status <> 'Cancelada' THEN rsv.pax ELSE 0 END) AS pax_reservadas,
                SUM({$paxComparecidaExpr}) AS pax_comparecidas,
                SUM(CASE WHEN rsv.status = 'Finalizada' THEN 1 ELSE 0 END) AS finalizadas,
                SUM(CASE WHEN {$noShowCondition} THEN 1 ELSE 0 END) AS no_shows,
                SUM(CASE WHEN rsv.status = 'Cancelada' THEN 1 ELSE 0 END) AS canceladas
            FROM reservas_tematicas rsv
            $where
        ");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return [
            'total_reservas' => (int)($row['total_reservas'] ?? 0),
            'pax_reservadas' => (int)($row['pax_reservadas'] ?? 0),
            'pax_comparecidas' => (int)($row['pax_comparecidas'] ?? 0),
            'pax_nao_comparecidas' => max(0, (int)($row['pax_reservadas'] ?? 0) - (int)($row['pax_comparecidas'] ?? 0)),
            'pax_total' => (int)($row['pax_comparecidas'] ?? 0),
            'finalizadas' => (int)($row['finalizadas'] ?? 0),
            'no_shows' => (int)($row['no_shows'] ?? 0),
            'canceladas' => (int)($row['canceladas'] ?? 0),
        ];
    }

    public function dashboardFinalizadasPax(array $filters): array
    {
        $paxExpr = $this->paxRealOrReservedExpr('rsv');
        $where = "
            WHERE rsv.status = 'Finalizada'
              AND (
                  r.nome LIKE '%Giardino%'
                  OR r.nome LIKE '%La Brasa%'
                  OR r.nome LIKE '%IX%'
                  OR r.nome LIKE '%Ix%'
              )
        ";
        $params = [];

        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $where .= " AND rsv.data_reserva BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $filters['data_inicio'];
            $params[':data_fim'] = $filters['data_fim'];
        } elseif (!empty($filters['data'])) {
            $where .= " AND rsv.data_reserva = :data";
            $params[':data'] = $filters['data'];
        }
        if (!empty($filters['restaurante_id'])) {
            $where .= " AND rsv.restaurante_id = :restaurante_id";
            $params[':restaurante_id'] = (int)$filters['restaurante_id'];
        }

        $stmt = $this->db->prepare("
            SELECT r.nome, SUM({$paxExpr}) AS total_pax
            FROM reservas_tematicas rsv
            JOIN restaurantes r ON r.id = rsv.restaurante_id
            $where
            GROUP BY r.id, r.nome
            ORDER BY total_pax DESC, r.nome
        ");
        $stmt->execute($params);
        $byRestaurant = $stmt->fetchAll();

        $total = 0;
        foreach ($byRestaurant as $row) {
            $total += (int)($row['total_pax'] ?? 0);
        }

        return [
            'total_pax' => $total,
            'by_restaurante' => $byRestaurant,
        ];
    }

    public function dashboardFinalizadasResumo(array $filters): array
    {
        $paxExpr = $this->paxRealOrReservedExpr('rsv');
        $where = "
            WHERE rsv.status = 'Finalizada'
              AND (
                  r.nome LIKE '%Giardino%'
                  OR r.nome LIKE '%La Brasa%'
                  OR r.nome LIKE '%IX%'
                  OR r.nome LIKE '%Ix%'
              )
        ";
        $params = [];

        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $where .= " AND rsv.data_reserva BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $filters['data_inicio'];
            $params[':data_fim'] = $filters['data_fim'];
        } elseif (!empty($filters['data'])) {
            $where .= " AND rsv.data_reserva = :data";
            $params[':data'] = $filters['data'];
        }
        if (!empty($filters['restaurante_id'])) {
            $where .= " AND rsv.restaurante_id = :restaurante_id";
            $params[':restaurante_id'] = (int)$filters['restaurante_id'];
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total_finalizadas, COALESCE(SUM({$paxExpr}), 0) AS total_pax
            FROM reservas_tematicas rsv
            JOIN restaurantes r ON r.id = rsv.restaurante_id
            $where
        ");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return [
            'total_finalizadas' => (int)($row['total_finalizadas'] ?? 0),
            'total_pax' => (int)($row['total_pax'] ?? 0),
        ];
    }

    public function dashboardFinalizadasFluxo(array $filters): array
    {
        $paxExpr = $this->paxRealOrReservedExpr('rsv');
        $where = "
            WHERE rsv.status = 'Finalizada'
              AND (
                  r.nome LIKE '%Giardino%'
                  OR r.nome LIKE '%La Brasa%'
                  OR r.nome LIKE '%IX%'
                  OR r.nome LIKE '%Ix%'
              )
        ";
        $params = [];

        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $where .= " AND rsv.data_reserva BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $filters['data_inicio'];
            $params[':data_fim'] = $filters['data_fim'];
        } elseif (!empty($filters['data'])) {
            $where .= " AND rsv.data_reserva = :data";
            $params[':data'] = $filters['data'];
        }
        if (!empty($filters['restaurante_id'])) {
            $where .= " AND rsv.restaurante_id = :restaurante_id";
            $params[':restaurante_id'] = (int)$filters['restaurante_id'];
        }

        $stmt = $this->db->prepare("
            SELECT DATE_FORMAT(t.hora, '%H:00') AS hora, SUM({$paxExpr}) AS total_pax
            FROM reservas_tematicas rsv
            JOIN reservas_tematicas_turnos t ON t.id = rsv.turno_id
            JOIN restaurantes r ON r.id = rsv.restaurante_id
            $where
            GROUP BY DATE_FORMAT(t.hora, '%H:00')
            ORDER BY hora
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function dashboardFinalizadasRecent(int $limit, array $filters): array
    {
        $paxExpr = $this->paxRealOrReservedExpr('rsv');
        $where = "
            WHERE rsv.status = 'Finalizada'
              AND (
                  r.nome LIKE '%Giardino%'
                  OR r.nome LIKE '%La Brasa%'
                  OR r.nome LIKE '%IX%'
                  OR r.nome LIKE '%Ix%'
              )
        ";
        $params = [];

        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $where .= " AND rsv.data_reserva BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $filters['data_inicio'];
            $params[':data_fim'] = $filters['data_fim'];
        } elseif (!empty($filters['data'])) {
            $where .= " AND rsv.data_reserva = :data";
            $params[':data'] = $filters['data'];
        }
        if (!empty($filters['restaurante_id'])) {
            $where .= " AND rsv.restaurante_id = :restaurante_id";
            $params[':restaurante_id'] = (int)$filters['restaurante_id'];
        }

        $sql = "
            SELECT
                0 AS alerta_duplicidade,
                0 AS fora_do_horario,
                uh.numero AS uh_numero,
                {$paxExpr} AS pax,
                r.nome AS restaurante,
                'Tematico' AS operacao,
                COALESCE(ua.nome, uc.nome) AS usuario,
                CONCAT(rsv.data_reserva, ' ', DATE_FORMAT(t.hora, '%H:%i:%s')) AS criado_em
            FROM reservas_tematicas rsv
            JOIN reservas_tematicas_turnos t ON t.id = rsv.turno_id
            JOIN unidades_habitacionais uh ON uh.id = rsv.uh_id
            JOIN restaurantes r ON r.id = rsv.restaurante_id
            JOIN usuarios uc ON uc.id = rsv.usuario_id
            LEFT JOIN usuarios ua ON ua.id = rsv.atualizado_por
            $where
            ORDER BY rsv.data_reserva DESC, t.hora DESC, rsv.id DESC
            LIMIT :limit
        ";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function recentByRestaurant(int $restauranteId, string $data = '', string $dataInicio = '', string $dataFim = '', int $limit = 15): array
    {
        $where = "WHERE rsv.restaurante_id = :restaurante_id";
        $params = [':restaurante_id' => $restauranteId];

        if ($dataInicio !== '' && $dataFim !== '') {
            $where .= " AND rsv.data_reserva BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $dataInicio;
            $params[':data_fim'] = $dataFim;
        } elseif ($data !== '') {
            $where .= " AND rsv.data_reserva = :data";
            $params[':data'] = $data;
        }

        $stmt = $this->db->prepare("
            SELECT rsv.*, r.nome AS restaurante, t.hora AS turno_hora, uh.numero AS uh_numero, u.nome AS usuario
            FROM reservas_tematicas rsv
            JOIN restaurantes r ON r.id = rsv.restaurante_id
            JOIN reservas_tematicas_turnos t ON t.id = rsv.turno_id
            JOIN unidades_habitacionais uh ON uh.id = rsv.uh_id
            JOIN usuarios u ON u.id = rsv.usuario_id
            $where
            ORDER BY rsv.data_reserva DESC, t.ordem, t.hora, rsv.id DESC
            LIMIT :limit
        ");
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function sumPax(int $restauranteId, string $data, int $turnoId): int
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(pax), 0) AS total_pax
            FROM reservas_tematicas
            WHERE restaurante_id = :restaurante_id
              AND data_reserva = :data
              AND turno_id = :turno_id
              AND status NOT IN ('Cancelada')
        ");
        $stmt->execute([
            ':restaurante_id' => $restauranteId,
            ':data' => $data,
            ':turno_id' => $turnoId,
        ]);
        $row = $stmt->fetch();
        return (int)($row['total_pax'] ?? 0);
    }

    public function hasDuplicateUh(int $uhId, string $data, int $turnoId, int $restauranteId): bool
    {
        $stmt = $this->db->prepare("
            SELECT id FROM reservas_tematicas
            WHERE uh_id = :uh_id AND data_reserva = :data AND turno_id = :turno_id AND restaurante_id = :restaurante_id
              AND status <> 'Cancelada'
            LIMIT 1
        ");
        $stmt->execute([
            ':uh_id' => $uhId,
            ':data' => $data,
            ':turno_id' => $turnoId,
            ':restaurante_id' => $restauranteId,
        ]);
        return (bool)$stmt->fetch();
    }

    public function findDuplicateId(int $uhId, string $data, int $turnoId, int $restauranteId): ?int
    {
        $stmt = $this->db->prepare("
            SELECT id FROM reservas_tematicas
            WHERE uh_id = :uh_id AND data_reserva = :data AND turno_id = :turno_id AND restaurante_id = :restaurante_id
              AND status <> 'Cancelada'
            LIMIT 1
        ");
        $stmt->execute([
            ':uh_id' => $uhId,
            ':data' => $data,
            ':turno_id' => $turnoId,
            ':restaurante_id' => $restauranteId,
        ]);
        $row = $stmt->fetch();
        return $row ? (int)$row['id'] : null;
    }

    public function findAutoNoShowCandidates(string $nowDateTime, ?string $data = null, ?int $restauranteId = null): array
    {
        try {
            $where = "
                WHERE rsv.status = 'Reservada'
                  AND COALESCE(cfg.auto_cancel_no_show_min, 0) > 0
                  AND DATE_ADD(TIMESTAMP(rsv.data_reserva, t.hora), INTERVAL cfg.auto_cancel_no_show_min MINUTE) <= :now_dt
            ";
            $params = [':now_dt' => $nowDateTime];

            if ($data !== null && $data !== '') {
                $where .= " AND rsv.data_reserva = :data_reserva";
                $params[':data_reserva'] = $data;
            }
            if ($restauranteId !== null && $restauranteId > 0) {
                $where .= " AND rsv.restaurante_id = :restaurante_id";
                $params[':restaurante_id'] = $restauranteId;
            }

            $stmt = $this->db->prepare("
                SELECT rsv.id
                FROM reservas_tematicas rsv
                JOIN reservas_tematicas_turnos t ON t.id = rsv.turno_id
                JOIN reservas_tematicas_config cfg ON cfg.restaurante_id = rsv.restaurante_id
                $where
            ");
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function findTematicaByUhDate(string $uhNumero, string $data): ?array
    {
        $stmt = $this->db->prepare("
            SELECT rsv.id, r.nome AS restaurante, t.hora AS turno_hora, rsv.status
            FROM reservas_tematicas rsv
            JOIN unidades_habitacionais uh ON uh.id = rsv.uh_id
            JOIN restaurantes r ON r.id = rsv.restaurante_id
            JOIN reservas_tematicas_turnos t ON t.id = rsv.turno_id
            WHERE uh.numero = :uh
              AND rsv.data_reserva = :data
              AND rsv.status <> 'Cancelada'
              AND (
                  r.nome LIKE '%Giardino%'
                  OR r.nome LIKE '%La Brasa%'
                  OR r.nome LIKE '%IX%'
                  OR r.nome LIKE '%Ix%'
              )
            ORDER BY t.ordem, t.hora
            LIMIT 1
        ");
        $stmt->execute([
            ':uh' => $uhNumero,
            ':data' => $data,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}







