<?php
class AccessModel extends Model
{
    public function register(array $data, int $userId): array
    {
        $unitModel = new UnitModel();
        $restOpModel = new RestaurantOperationModel();

        $uh = $unitModel->findByNumero($data['uh_numero']);
        if (!$uh) {
            return [
                'id' => 0,
                'error' => 'uh_invalida',
            ];
        }

        $restOp = $restOpModel->findByRestaurantOperation((int)$data['restaurante_id'], (int)$data['operacao_id']);
        $isTechnicalUh = in_array((string)($uh['numero'] ?? ''), ['998', '999'], true);
        $alertaDuplicidade = $isTechnicalUh
            ? false
            : $this->checkDuplicidade(
                (int)$uh['id'],
                (int)$data['restaurante_id'],
                (int)$data['operacao_id'],
                (int)$data['pax']
            );
        $foraDoHorario = $this->checkForaHorario($restOp);

        $stmt = $this->db->prepare("
            INSERT INTO acessos
            (turno_id, uh_id, pax, restaurante_id, porta_id, operacao_id, alerta_duplicidade, fora_do_horario, criado_em, usuario_id)
            VALUES
            (:turno_id, :uh_id, :pax, :restaurante_id, :porta_id, :operacao_id, :alerta_duplicidade, :fora_do_horario, NOW(), :usuario_id)
        ");
        $stmt->execute([
            ':turno_id' => $data['turno_id'] ?? null,
            ':uh_id' => $uh['id'],
            ':pax' => $data['pax'],
            ':restaurante_id' => $data['restaurante_id'],
            ':porta_id' => $data['porta_id'] ?? null,
            ':operacao_id' => $data['operacao_id'],
            ':alerta_duplicidade' => $alertaDuplicidade ? 1 : 0,
            ':fora_do_horario' => $foraDoHorario ? 1 : 0,
            ':usuario_id' => $userId,
        ]);

        return [
            'id' => (int)$this->db->lastInsertId(),
            'alerta_duplicidade' => $alertaDuplicidade,
            'fora_do_horario' => $foraDoHorario,
        ];
    }

    public function sumPaxByUhOperacaoDate(string $uhNumero, int $operacaoId, string $date): int
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(a.pax), 0) AS total_pax
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            WHERE uh.numero = :uh
              AND a.operacao_id = :operacao_id
              AND DATE(a.criado_em) = :data
        ");
        $stmt->execute([
            ':uh' => $uhNumero,
            ':operacao_id' => $operacaoId,
            ':data' => $date,
        ]);
        $row = $stmt->fetch();
        return (int)($row['total_pax'] ?? 0);
    }

    private function checkDuplicidade(int $uhId, int $restauranteId, int $operacaoId, int $pax): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM acessos
            WHERE uh_id = :uh_id
              AND restaurante_id = :restaurante_id
              AND operacao_id = :operacao_id
              AND pax = :pax
              AND criado_em >= (NOW() - INTERVAL 10 MINUTE)
        ");
        $stmt->execute([
            ':uh_id' => $uhId,
            ':restaurante_id' => $restauranteId,
            ':operacao_id' => $operacaoId,
            ':pax' => $pax,
        ]);
        $row = $stmt->fetch();
        return ($row['total'] ?? 0) > 0;
    }

    private function checkForaHorario(?array $restauranteOperacao): bool
    {
        if (!$restauranteOperacao) {
            return false;
        }

        $tz = new DateTimeZone(date_default_timezone_get());
        $now = new DateTime('now', $tz);

        $start = DateTime::createFromFormat('H:i:s', $restauranteOperacao['hora_inicio'], $tz);
        $end = DateTime::createFromFormat('H:i:s', $restauranteOperacao['hora_fim'], $tz);

        if (!$start || !$end) {
            return false;
        }

        $start->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));
        $end->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));


        // ObservAção: Operações que atravessam meia-noite podem precisar de ajuste futuro.
        if ($end < $start) {
            $end->modify('+1 day');
            if ($now < $start) {
                $now->modify('+1 day');
            }
        }

        $tolerance = (int)($restauranteOperacao['tolerancia_min'] ?? 0);
        if ($tolerance > 0) {
            $start->modify("-{$tolerance} minutes");
            $end->modify("+{$tolerance} minutes");
        }

        return $now < $start || $now > $end;
    }

    private function multipleAccessExistsSql(string $alias = 'a'): string
    {
        $technicalUhSql = "(SELECT id FROM unidades_habitacionais WHERE numero IN ('998', '999'))";
        return "({$alias}.uh_id NOT IN {$technicalUhSql}) AND EXISTS (
            SELECT 1
            FROM acessos ax
            WHERE ax.uh_id = {$alias}.uh_id
              AND ax.uh_id NOT IN {$technicalUhSql}
              AND ax.operacao_id = {$alias}.operacao_id
              AND DATE(ax.criado_em) = DATE({$alias}.criado_em)
              AND ax.pax <> {$alias}.pax
              AND (ax.criado_em < {$alias}.criado_em OR (ax.criado_em = {$alias}.criado_em AND ax.id < {$alias}.id))
              AND TIMESTAMPDIFF(MINUTE, ax.criado_em, {$alias}.criado_em) > 15
        )";
    }

    public function hasImmediateExactDuplicate(
        string $uhNumero,
        int $restauranteId,
        int $operacaoId,
        int $pax,
        int $turnoId,
        int $userId
    ): bool {
        $uhNumero = trim((string)$uhNumero);
        if ($uhNumero === '' || $pax <= 0) {
            return false;
        }
        if (in_array($uhNumero, ['998', '999'], true)) {
            return false;
        }

        $stmtUh = $this->db->prepare("SELECT id FROM unidades_habitacionais WHERE numero = :numero LIMIT 1");
        $stmtUh->execute([':numero' => $uhNumero]);
        $uh = $stmtUh->fetch();
        if (!$uh) {
            return false;
        }
        $uhId = (int)($uh['id'] ?? 0);
        if ($uhId <= 0) {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT a.id
            FROM acessos a
            WHERE a.uh_id = :uh_id
              AND a.restaurante_id = :restaurante_id
              AND a.operacao_id = :operacao_id
              AND a.turno_id = :turno_id
              AND a.usuario_id = :usuario_id
              AND a.pax = :pax
              AND a.criado_em >= (NOW() - INTERVAL 2 MINUTE)
            ORDER BY a.id DESC
            LIMIT 1
        ");
        $stmt->execute([
            ':uh_id' => $uhId,
            ':restaurante_id' => $restauranteId,
            ':operacao_id' => $operacaoId,
            ':turno_id' => $turnoId,
            ':usuario_id' => $userId,
            ':pax' => $pax,
        ]);

        return (bool)$stmt->fetch();
    }

    private function statusCaseSql(string $alias = 'a'): string
    {
        $multiple = $this->multipleAccessExistsSql($alias);
        return "
            CASE
                WHEN {$alias}.alerta_duplicidade = 1 THEN 'Duplicado'
                WHEN {$alias}.fora_do_horario = 1 THEN 'Fora do Horário'
                WHEN {$multiple} THEN 'Múltiplo Acesso'
                ELSE 'OK'
            END
        ";
    }

    private function appendStatusFilter(string &$where, string $status = '', string $alias = 'a'): void
    {
        $status = trim(mb_strtolower($status, 'UTF-8'));
        if ($status === '') {
            return;
        }

        $multiple = $this->multipleAccessExistsSql($alias);

        if ($status === 'duplicado') {
            $where .= " AND {$alias}.alerta_duplicidade = 1";
            return;
        }
        if ($status === 'fora_horario') {
            $where .= " AND {$alias}.alerta_duplicidade = 0 AND {$alias}.fora_do_horario = 1";
            return;
        }
        if ($status === 'multiplo') {
            $where .= " AND {$alias}.alerta_duplicidade = 0 AND {$alias}.fora_do_horario = 0 AND {$multiple}";
            return;
        }
        if ($status === 'ok') {
            $where .= " AND {$alias}.alerta_duplicidade = 0 AND {$alias}.fora_do_horario = 0 AND NOT {$multiple}";
            return;
        }
        if ($status === 'nao_informado') {
            $where .= " AND uh.numero = '998'";
            return;
        }
        if ($status === 'day_use') {
            $where .= " AND uh.numero = '999'";
        }
    }

    public function listRecent(int $limit = 20): array
    {
        $statusCase = $this->statusCaseSql('a');
        $multiple = $this->multipleAccessExistsSql('a');
        $stmt = $this->db->prepare("
            SELECT a.*, uh.numero AS uh_numero, r.nome AS restaurante, p.nome AS porta, o.nome AS operacao, u.nome AS usuario,
                   ({$multiple}) AS multiplo_acesso,
                   ({$statusCase}) AS status_operacional
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN restaurantes r ON r.id = a.restaurante_id
            LEFT JOIN portas p ON p.id = a.porta_id
            JOIN operacoes o ON o.id = a.operacao_id
            JOIN usuarios u ON u.id = a.usuario_id
            ORDER BY a.criado_em DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countByTurno(int $turnoId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM acessos
            WHERE turno_id = :turno_id
        ");
        $stmt->execute([':turno_id' => $turnoId]);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function findLastEditableByTurnoUser(int $turnoId, int $userId, int $windowMinutes = 2): ?array
    {
        $windowMinutes = max(1, (int)$windowMinutes);
        $stmt = $this->db->prepare("
            SELECT a.*, uh.numero AS uh_numero
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            WHERE a.turno_id = :turno_id
              AND a.usuario_id = :usuario_id
              AND a.criado_em >= (NOW() - INTERVAL {$windowMinutes} MINUTE)
            ORDER BY a.id DESC
            LIMIT 1
        ");
        $stmt->bindValue(':turno_id', $turnoId, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updatePax(int $accessId, int $newPax, int $userId): void
    {
        $before = $this->findById($accessId) ?? [];
        if (!$before) {
            return;
        }

        $stmt = $this->db->prepare("UPDATE acessos SET pax = :pax WHERE id = :id");
        $stmt->execute([
            ':pax' => $newPax,
            ':id' => $accessId,
        ]);

        $after = $this->findById($accessId) ?? [];
        $this->audit('update_pax_2min', $userId, $before, $after, 'acessos', $accessId);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM acessos WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function dashboard(array $filters): array
    {
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $where .= " AND DATE(a.criado_em) BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $filters['data_inicio'];
            $params[':data_fim'] = $filters['data_fim'];
        } elseif (!empty($filters['data'])) {
            $where .= " AND DATE(a.criado_em) = :data";
            $params[':data'] = $filters['data'];
        }
        if (!empty($filters['restaurante_id'])) {
            $where .= " AND a.restaurante_id = :restaurante_id";
            $params[':restaurante_id'] = $filters['restaurante_id'];
        }
        if (!empty($filters['operacao_id'])) {
            $where .= " AND a.operacao_id = :operacao_id";
            $params[':operacao_id'] = $filters['operacao_id'];
        }
        if (!empty($filters['uh_numero'])) {
            $where .= " AND uh.numero = :uh";
            $params[':uh'] = $filters['uh_numero'];
        }
        $this->appendStatusFilter($where, (string)($filters['status'] ?? ''), 'a');

        $totaisOperacao = $this->aggregate("
            SELECT o.nome, SUM(a.pax) AS total_pax
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN operacoes o ON o.id = a.operacao_id
            $where
            GROUP BY o.nome
            ORDER BY total_pax DESC
        ", $params);

        $totaisRestaurante = $this->aggregate("
            SELECT r.nome, SUM(a.pax) AS total_pax
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN restaurantes r ON r.id = a.restaurante_id
            $where
            GROUP BY r.nome
            ORDER BY total_pax DESC
        ", $params);

        $fluxoHorario = $this->aggregate("
            SELECT DATE_FORMAT(a.criado_em, '%H:00') AS hora, SUM(a.pax) AS total_pax
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            $where
            GROUP BY DATE_FORMAT(a.criado_em, '%H:00')
            ORDER BY hora
        ", $params);

        $foraHorario = $this->aggregate("
            SELECT COUNT(*) AS total
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            $where AND a.fora_do_horario = 1
        ", $params);

        $duplicados = $this->aggregate("
            SELECT COUNT(*) AS total
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            $where AND a.alerta_duplicidade = 1
        ", $params);

        $multiple = $this->multipleAccessExistsSql('a');
        $multiplos = $this->aggregate("
            SELECT COUNT(*) AS total
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            $where
              AND a.alerta_duplicidade = 0
              AND a.fora_do_horario = 0
              AND {$multiple}
        ", $params);

        $totais = $this->aggregate("
            SELECT COUNT(*) AS total_acessos, SUM(a.pax) AS total_pax
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            $where
        ", $params);

        $uhTecnicas = $this->aggregate("
            SELECT
                SUM(CASE WHEN uh.numero = '998' THEN 1 ELSE 0 END) AS nao_informado_acessos,
                SUM(CASE WHEN uh.numero = '998' THEN a.pax ELSE 0 END) AS nao_informado_pax,
                SUM(CASE WHEN uh.numero = '999' THEN 1 ELSE 0 END) AS day_use_acessos,
                SUM(CASE WHEN uh.numero = '999' THEN a.pax ELSE 0 END) AS day_use_pax
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            $where
        ", $params);
        $uhTecnicasRow = $uhTecnicas[0] ?? [];

        $vipPremium = $this->aggregate("
            SELECT
                COUNT(*) AS vip_premium_acessos,
                COALESCE(SUM(a.pax), 0) AS vip_premium_pax
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN restaurantes r ON r.id = a.restaurante_id
            JOIN operacoes o ON o.id = a.operacao_id
            $where
              AND (LOWER(r.nome) LIKE '%vip%premium%' OR LOWER(o.nome) LIKE '%vip%premium%')
        ", $params);
        $vipPremiumRow = $vipPremium[0] ?? [];

        $privileged = $this->aggregate("
            SELECT
                COUNT(*) AS privileged_acessos,
                COALESCE(SUM(a.pax), 0) AS privileged_pax
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN restaurantes r ON r.id = a.restaurante_id
            JOIN operacoes o ON o.id = a.operacao_id
            $where
              AND (
                    LOWER(r.nome) LIKE '%privileged%'
                 OR LOWER(o.nome) LIKE '%privileged%'
              )
        ", $params);
        $privilegedRow = $privileged[0] ?? [];

        return [
            'totais_operacao' => $totaisOperacao,
            'totais_restaurante' => $totaisRestaurante,
            'fluxo_horario' => $fluxoHorario,
            'fora_horario' => $foraHorario[0]['total'] ?? 0,
            'duplicados' => $duplicados[0]['total'] ?? 0,
            'multiplos' => $multiplos[0]['total'] ?? 0,
            'total_acessos' => $totais[0]['total_acessos'] ?? 0,
            'total_pax' => $totais[0]['total_pax'] ?? 0,
            'nao_informado_acessos' => (int)($uhTecnicasRow['nao_informado_acessos'] ?? 0),
            'nao_informado_pax' => (int)($uhTecnicasRow['nao_informado_pax'] ?? 0),
            'day_use_acessos' => (int)($uhTecnicasRow['day_use_acessos'] ?? 0),
            'day_use_pax' => (int)($uhTecnicasRow['day_use_pax'] ?? 0),
            'privileged_acessos' => (int)($privilegedRow['privileged_acessos'] ?? 0),
            'privileged_pax' => (int)($privilegedRow['privileged_pax'] ?? 0),
            'vip_premium_acessos' => (int)($vipPremiumRow['vip_premium_acessos'] ?? 0),
            'vip_premium_pax' => (int)($vipPremiumRow['vip_premium_pax'] ?? 0),
        ];
    }

    public function dashboardFlow(array $filters): array
    {
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $where .= " AND DATE(a.criado_em) BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $filters['data_inicio'];
            $params[':data_fim'] = $filters['data_fim'];
        } elseif (!empty($filters['data'])) {
            $where .= " AND DATE(a.criado_em) = :data";
            $params[':data'] = $filters['data'];
        }
        if (!empty($filters['restaurante_id'])) {
            $where .= " AND a.restaurante_id = :restaurante_id";
            $params[':restaurante_id'] = $filters['restaurante_id'];
        }
        if (!empty($filters['operacao_id'])) {
            $where .= " AND a.operacao_id = :operacao_id";
            $params[':operacao_id'] = $filters['operacao_id'];
        }
        if (!empty($filters['uh_numero'])) {
            $where .= " AND uh.numero = :uh";
            $params[':uh'] = $filters['uh_numero'];
        }
        $this->appendStatusFilter($where, (string)($filters['status'] ?? ''), 'a');

        return $this->aggregate("
            SELECT DATE_FORMAT(a.criado_em, '%H:00') AS hora, SUM(a.pax) AS total_pax
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            $where
            GROUP BY DATE_FORMAT(a.criado_em, '%H:00')
            ORDER BY hora
        ", $params);
    }

    public function recentByRestaurant(
        int $restauranteId,
        int $limit = 20,
        string $data = '',
        string $dataInicio = '',
        string $dataFim = '',
        string $status = '',
        string $uhNumero = ''
    ): array
    {
        $where = "WHERE a.restaurante_id = :restaurante_id";
        $params = [':restaurante_id' => $restauranteId];

        if ($dataInicio !== '' && $dataFim !== '') {
            $where .= " AND DATE(a.criado_em) BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $dataInicio;
            $params[':data_fim'] = $dataFim;
        } elseif ($data !== '') {
            $where .= " AND DATE(a.criado_em) = :data";
            $params[':data'] = $data;
        }
        if ($uhNumero !== '') {
            $where .= " AND uh.numero = :uh";
            $params[':uh'] = $uhNumero;
        }
        $this->appendStatusFilter($where, $status, 'a');

        $statusCase = $this->statusCaseSql('a');
        $multiple = $this->multipleAccessExistsSql('a');

        $stmt = $this->db->prepare("
            SELECT a.*, uh.numero AS uh_numero, r.nome AS restaurante, p.nome AS porta,
                   o.nome AS operacao, u.nome AS usuario,
                   ({$multiple}) AS multiplo_acesso,
                   ({$statusCase}) AS status_operacional
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN restaurantes r ON r.id = a.restaurante_id
            LEFT JOIN portas p ON p.id = a.porta_id
            JOIN operacoes o ON o.id = a.operacao_id
            JOIN usuarios u ON u.id = a.usuario_id
            $where
            ORDER BY a.criado_em DESC
            LIMIT :limit
        ");
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function recentAll(
        int $limit = 20,
        string $data = '',
        string $dataInicio = '',
        string $dataFim = '',
        string $status = '',
        string $uhNumero = ''
    ): array
    {
        $where = "";
        $params = [];
        if ($dataInicio !== '' && $dataFim !== '') {
            $where = "WHERE DATE(a.criado_em) BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $dataInicio;
            $params[':data_fim'] = $dataFim;
        } elseif ($data !== '') {
            $where = "WHERE DATE(a.criado_em) = :data";
            $params[':data'] = $data;
        }
        if ($where === '') {
            $where = "WHERE 1=1";
        }
        if ($uhNumero !== '') {
            $where .= " AND uh.numero = :uh";
            $params[':uh'] = $uhNumero;
        }
        $this->appendStatusFilter($where, $status, 'a');

        $statusCase = $this->statusCaseSql('a');
        $multiple = $this->multipleAccessExistsSql('a');

        $stmt = $this->db->prepare("
            SELECT a.*, uh.numero AS uh_numero, r.nome AS restaurante, p.nome AS porta,
                   o.nome AS operacao, u.nome AS usuario,
                   ({$multiple}) AS multiplo_acesso,
                   ({$statusCase}) AS status_operacional
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN restaurantes r ON r.id = a.restaurante_id
            LEFT JOIN portas p ON p.id = a.porta_id
            JOIN operacoes o ON o.id = a.operacao_id
            JOIN usuarios u ON u.id = a.usuario_id
            $where
            ORDER BY a.criado_em DESC
            LIMIT :limit
        ");
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function reportList(array $filters, ?int $limit = null, int $offset = 0): array
    {
        $params = [];
        $where = $this->buildKpiWhere($filters, $params, 'a');
        $statusCase = $this->statusCaseSql('a');
        $multiple = $this->multipleAccessExistsSql('a');
        $paginationSql = $limit !== null ? " LIMIT :limit OFFSET :offset" : "";

        $stmt = $this->db->prepare("
            SELECT a.*, uh.numero AS uh_numero, r.nome AS restaurante, p.nome AS porta,
                   o.nome AS operacao, u.nome AS usuario,
                   ({$multiple}) AS multiplo_acesso,
                   ({$statusCase}) AS status_operacional
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN restaurantes r ON r.id = a.restaurante_id
            LEFT JOIN portas p ON p.id = a.porta_id
            JOIN operacoes o ON o.id = a.operacao_id
            JOIN usuarios u ON u.id = a.usuario_id
            $where
            ORDER BY a.criado_em ASC
            $paginationSql
        ");
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        if ($limit !== null) {
            $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
            $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function reportListCount(array $filters): int
    {
        $params = [];
        $where = $this->buildKpiWhere($filters, $params, 'a');
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            $where
        ");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function reportMultipleAccessGroups(array $filters, ?int $limit = null, int $offset = 0): array
    {
        $params = [];
        $filters = array_merge($filters, ['status' => '']);
        $where = $this->buildKpiWhere($filters, $params, 'a');
        $multiple = $this->multipleAccessExistsSql('a');
        $where .= " AND a.alerta_duplicidade = 0 AND a.fora_do_horario = 0 AND {$multiple}";
        $paginationSql = $limit !== null ? " LIMIT :limit OFFSET :offset" : "";

        $stmt = $this->db->prepare("
            SELECT
                uh.numero AS uh_numero,
                MIN(a.criado_em) AS primeira_passagem,
                MAX(a.criado_em) AS ultima_passagem,
                COUNT(*) AS total_acessos,
                COALESCE(SUM(a.pax), 0) AS total_pax,
                MIN(a.pax) AS menor_pax,
                MAX(a.pax) AS maior_pax,
                COUNT(DISTINCT DATE(a.criado_em)) AS dias,
                GROUP_CONCAT(DISTINCT r.nome ORDER BY r.nome SEPARATOR ', ') AS restaurantes,
                GROUP_CONCAT(DISTINCT o.nome ORDER BY o.nome SEPARATOR ', ') AS operacoes,
                GROUP_CONCAT(DISTINCT COALESCE(p.nome, '-') ORDER BY COALESCE(p.nome, '-') SEPARATOR ', ') AS portas,
                GROUP_CONCAT(DISTINCT u.nome ORDER BY u.nome SEPARATOR ', ') AS usuarios,
                'Múltiplo Acesso' AS status_operacional
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN restaurantes r ON r.id = a.restaurante_id
            LEFT JOIN portas p ON p.id = a.porta_id
            JOIN operacoes o ON o.id = a.operacao_id
            JOIN usuarios u ON u.id = a.usuario_id
            $where
            GROUP BY uh.id, uh.numero
            ORDER BY ultima_passagem DESC, uh.numero ASC
            $paginationSql
        ");
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        if ($limit !== null) {
            $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
            $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['total_acessos'] = (int)($row['total_acessos'] ?? 0);
            $row['total_pax'] = (int)($row['total_pax'] ?? 0);
            $row['menor_pax'] = (int)($row['menor_pax'] ?? 0);
            $row['maior_pax'] = (int)($row['maior_pax'] ?? 0);
            $row['dias'] = (int)($row['dias'] ?? 0);
        }
        unset($row);
        return $rows;
    }

    public function reportMultipleAccessGroupsCount(array $filters): int
    {
        $params = [];
        $filters = array_merge($filters, ['status' => '']);
        $where = $this->buildKpiWhere($filters, $params, 'a');
        $multiple = $this->multipleAccessExistsSql('a');
        $where .= " AND a.alerta_duplicidade = 0 AND a.fora_do_horario = 0 AND {$multiple}";

        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM (
                SELECT a.uh_id
                FROM acessos a
                JOIN unidades_habitacionais uh ON uh.id = a.uh_id
                $where
                GROUP BY a.uh_id
            ) grouped
        ");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function uhJourney(string $uhNumero, string $data, string $dataInicio = '', string $dataFim = ''): array
    {
        $where = "WHERE uh.numero = :uh";
        $params = [':uh' => $uhNumero];

        if ($dataInicio !== '' && $dataFim !== '') {
            $where .= " AND DATE(a.criado_em) BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $dataInicio;
            $params[':data_fim'] = $dataFim;
        } else {
            $where .= " AND DATE(a.criado_em) = :data";
            $params[':data'] = $data;
        }
        $stmt = $this->db->prepare("
            SELECT a.criado_em, r.nome AS restaurante, o.nome AS operacao, p.nome AS porta,
                   a.pax, a.alerta_duplicidade, a.fora_do_horario, u.nome AS usuario
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN restaurantes r ON r.id = a.restaurante_id
            LEFT JOIN portas p ON p.id = a.porta_id
            JOIN operacoes o ON o.id = a.operacao_id
            JOIN usuarios u ON u.id = a.usuario_id
            $where
            ORDER BY a.criado_em ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function uhSummary(string $uhNumero, string $data, string $dataInicio = '', string $dataFim = ''): array
    {
        $where = "WHERE uh.numero = :uh";
        $params = [':uh' => $uhNumero];

        if ($dataInicio !== '' && $dataFim !== '') {
            $where .= " AND DATE(a.criado_em) BETWEEN :data_inicio AND :data_fim";
            $params[':data_inicio'] = $dataInicio;
            $params[':data_fim'] = $dataFim;
        } else {
            $where .= " AND DATE(a.criado_em) = :data";
            $params[':data'] = $data;
        }
        $stmt = $this->db->prepare("
            SELECT r.nome AS restaurante, o.nome AS operacao,
                   MIN(a.criado_em) AS primeira_passagem,
                   MAX(a.criado_em) AS ultima_passagem,
                   COUNT(*) AS acessos,
                   SUM(a.pax) AS pax_total
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN restaurantes r ON r.id = a.restaurante_id
            JOIN operacoes o ON o.id = a.operacao_id
            $where
            GROUP BY r.nome, o.nome
            ORDER BY primeira_passagem ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function dailyMap(string $data): array
    {
        $operationLabelSql = "
            REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                LOWER(COALESCE(o.nome, '')),
                'á', 'a'), 'à', 'a'), 'â', 'a'), 'ã', 'a'), 'é', 'e'), 'ê', 'e'),
                'í', 'i'), 'ó', 'o'), 'ô', 'o'), 'ú', 'u'), 'ç', 'c')
        ";
        $fullLabelSql = "
            REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                LOWER(CONCAT_WS(' ', o.nome, r.nome)),
                'á', 'a'), 'à', 'a'), 'â', 'a'), 'ã', 'a'), 'é', 'e'), 'ê', 'e'),
                'í', 'i'), 'ó', 'o'), 'ô', 'o'), 'ú', 'u'), 'ç', 'c')
        ";
        $stmt = $this->db->prepare("
            SELECT
                uh.numero AS uh_numero,
                COALESCE(MAX(CASE WHEN {$operationLabelSql} LIKE '%cafe%' THEN 1 ELSE 0 END), 0) AS cafe,
                COALESCE(MAX(CASE WHEN {$operationLabelSql} LIKE '%almoco%' THEN 1 ELSE 0 END), 0) AS almoco,
                COALESCE(MAX(CASE WHEN {$operationLabelSql} LIKE '%jantar%' THEN 1 ELSE 0 END), 0) AS jantar,
                COALESCE(MAX(CASE WHEN {$operationLabelSql} LIKE '%tematico%' THEN 1 ELSE 0 END), 0) AS tematico,
                COALESCE(MAX(CASE WHEN {$operationLabelSql} LIKE '%privileged%' THEN 1 ELSE 0 END), 0) AS privileged,
                MAX(
                    CASE
                        WHEN {$fullLabelSql} LIKE '%vip%premium%' THEN 1
                        ELSE 0
                    END
                ) AS vip_premium
            FROM unidades_habitacionais uh
            LEFT JOIN acessos a ON a.uh_id = uh.id AND DATE(a.criado_em) = :data
            LEFT JOIN operacoes o ON o.id = a.operacao_id
            LEFT JOIN restaurantes r ON r.id = a.restaurante_id
            WHERE uh.ativo = 1
              AND uh.numero NOT IN ('998', '999')
              AND (
                  CAST(uh.numero AS UNSIGNED) BETWEEN 101 AND 1019
                  OR CAST(uh.numero AS UNSIGNED) BETWEEN 1101 AND 1112
                  OR CAST(uh.numero AS UNSIGNED) BETWEEN 2100 AND 4322
              )
            GROUP BY uh.numero
            ORDER BY CAST(uh.numero AS UNSIGNED)
        ");
        $stmt->execute([':data' => $data]);
        return $stmt->fetchAll();
    }

    public function statsForDate(string $date): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total_acessos,
                SUM(pax) AS total_pax,
                SUM(CASE WHEN alerta_duplicidade = 1 THEN 1 ELSE 0 END) AS duplicados,
                SUM(CASE WHEN fora_do_horario = 1 THEN 1 ELSE 0 END) AS fora_horario
            FROM acessos
            WHERE DATE(criado_em) = :data
        ");
        $stmt->execute([':data' => $date]);
        $row = $stmt->fetch();
        return [
            'total_acessos' => (int)($row['total_acessos'] ?? 0),
            'total_pax' => (int)($row['total_pax'] ?? 0),
            'duplicados' => (int)($row['duplicados'] ?? 0),
            'fora_horario' => (int)($row['fora_horario'] ?? 0),
        ];
    }

    private function buildKpiWhere(array $filters, array &$params, string $alias = 'a'): string
    {
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $where .= " AND {$alias}.criado_em >= :data_inicio_start AND {$alias}.criado_em < DATE_ADD(:data_fim_end, INTERVAL 1 DAY)";
            $params[':data_inicio_start'] = $filters['data_inicio'];
            $params[':data_fim_end'] = $filters['data_fim'];
        } elseif (!empty($filters['data'])) {
            $where .= " AND {$alias}.criado_em >= :data_start AND {$alias}.criado_em < DATE_ADD(:data_end, INTERVAL 1 DAY)";
            $params[':data_start'] = $filters['data'];
            $params[':data_end'] = $filters['data'];
        }
        if (!empty($filters['restaurante_id'])) {
            $where .= " AND {$alias}.restaurante_id = :restaurante_id";
            $params[':restaurante_id'] = (int)$filters['restaurante_id'];
        }
        if (!empty($filters['operacao_id'])) {
            $where .= " AND {$alias}.operacao_id = :operacao_id";
            $params[':operacao_id'] = (int)$filters['operacao_id'];
        }
        if (!empty($filters['turno_id'])) {
            $where .= " AND {$alias}.turno_id = :turno_id";
            $params[':turno_id'] = (int)$filters['turno_id'];
        }
        if (!empty($filters['uh_numero'])) {
            $where .= " AND uh.numero = :uh";
            $params[':uh'] = $filters['uh_numero'];
        }

        $this->appendStatusFilter($where, (string)($filters['status'] ?? ''), $alias);
        return $where;
    }

    public function kpiSummary(array $filters): array
    {
        $params = [];
        $where = $this->buildKpiWhere($filters, $params, 'a');
        $multiple = $this->multipleAccessExistsSql('a');

        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total_registros,
                COALESCE(SUM(a.pax), 0) AS total_pax,
                COUNT(DISTINCT a.uh_id) AS uhs_unicas,
                COUNT(DISTINCT CASE WHEN uh.numero NOT IN ('998','999') THEN a.uh_id END) AS uhs_hospedes,
                COUNT(DISTINCT a.usuario_id) AS operadores_ativos,
                SUM(CASE WHEN a.alerta_duplicidade = 1 THEN 1 ELSE 0 END) AS duplicados,
                SUM(CASE WHEN a.fora_do_horario = 1 THEN 1 ELSE 0 END) AS fora_horario,
                SUM(CASE WHEN a.alerta_duplicidade = 0 AND a.fora_do_horario = 0 AND {$multiple} THEN 1 ELSE 0 END) AS multiplos,
                SUM(CASE WHEN uh.numero = '998' THEN 1 ELSE 0 END) AS nao_informado_registros,
                SUM(CASE WHEN uh.numero = '998' THEN a.pax ELSE 0 END) AS nao_informado_pax,
                SUM(CASE WHEN uh.numero = '999' THEN 1 ELSE 0 END) AS day_use_registros,
                SUM(CASE WHEN uh.numero = '999' THEN a.pax ELSE 0 END) AS day_use_pax,
                SUM(CASE WHEN LOWER(CONCAT(r.nome, ' ', o.nome)) LIKE '%privileged%' THEN 1 ELSE 0 END) AS privileged_registros,
                SUM(CASE WHEN LOWER(CONCAT(r.nome, ' ', o.nome)) LIKE '%privileged%' THEN a.pax ELSE 0 END) AS privileged_pax,
                SUM(CASE WHEN LOWER(CONCAT(r.nome, ' ', o.nome)) LIKE '%vip premium%' THEN 1 ELSE 0 END) AS vip_premium_registros,
                SUM(CASE WHEN LOWER(CONCAT(r.nome, ' ', o.nome)) LIKE '%vip premium%' THEN a.pax ELSE 0 END) AS vip_premium_pax,
                AVG(a.pax) AS media_pax_registro
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN restaurantes r ON r.id = a.restaurante_id
            JOIN operacoes o ON o.id = a.operacao_id
            $where
        ");
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        $totalRegistros = (int)($row['total_registros'] ?? 0);
        $totalPax = (int)($row['total_pax'] ?? 0);
        $duplicados = (int)($row['duplicados'] ?? 0);
        $foraHorario = (int)($row['fora_horario'] ?? 0);
        $multiplos = (int)($row['multiplos'] ?? 0);
        $alertas = $duplicados + $foraHorario + $multiplos;

        $topRest = $this->aggregate("
            SELECT r.nome, COALESCE(SUM(a.pax), 0) AS total_pax, COUNT(*) AS registros
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN restaurantes r ON r.id = a.restaurante_id
            $where
            GROUP BY r.id, r.nome
            ORDER BY total_pax DESC, registros DESC
            LIMIT 1
        ", $params);

        $topOp = $this->aggregate("
            SELECT o.nome, COALESCE(SUM(a.pax), 0) AS total_pax, COUNT(*) AS registros
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN operacoes o ON o.id = a.operacao_id
            $where
            GROUP BY o.id, o.nome
            ORDER BY total_pax DESC, registros DESC
            LIMIT 1
        ", $params);

        $percent = static function (int $part, int $total): float {
            if ($total <= 0) {
                return 0.0;
            }
            return round(($part / $total) * 100, 2);
        };

        $taxaAlertas = $percent($alertas, $totalRegistros);
        $indiceQualidade = max(0.0, round(100 - $taxaAlertas, 2));

        return [
            'total_registros' => $totalRegistros,
            'total_pax' => $totalPax,
            'uhs_unicas' => (int)($row['uhs_unicas'] ?? 0),
            'uhs_hospedes' => (int)($row['uhs_hospedes'] ?? 0),
            'operadores_ativos' => (int)($row['operadores_ativos'] ?? 0),
            'duplicados' => $duplicados,
            'fora_horario' => $foraHorario,
            'multiplos' => $multiplos,
            'alertas_total' => $alertas,
            'nao_informado_registros' => (int)($row['nao_informado_registros'] ?? 0),
            'nao_informado_pax' => (int)($row['nao_informado_pax'] ?? 0),
            'day_use_registros' => (int)($row['day_use_registros'] ?? 0),
            'day_use_pax' => (int)($row['day_use_pax'] ?? 0),
            'privileged_registros' => (int)($row['privileged_registros'] ?? 0),
            'privileged_pax' => (int)($row['privileged_pax'] ?? 0),
            'vip_premium_registros' => (int)($row['vip_premium_registros'] ?? 0),
            'vip_premium_pax' => (int)($row['vip_premium_pax'] ?? 0),
            'media_pax_registro' => round((float)($row['media_pax_registro'] ?? 0), 2),
            'pax_por_uh' => (int)($row['uhs_unicas'] ?? 0) > 0 ? round($totalPax / (int)$row['uhs_unicas'], 2) : 0.0,
            'taxa_alertas' => $taxaAlertas,
            'indice_qualidade' => $indiceQualidade,
            'taxa_day_use' => $percent((int)($row['day_use_registros'] ?? 0), $totalRegistros),
            'taxa_nao_informado' => $percent((int)($row['nao_informado_registros'] ?? 0), $totalRegistros),
            'top_restaurante' => $topRest[0] ?? null,
            'top_operacao' => $topOp[0] ?? null,
        ];
    }

    public function kpiDailyTrend(array $filters): array
    {
        $params = [];
        $where = $this->buildKpiWhere($filters, $params, 'a');
        $stmt = $this->db->prepare("
            SELECT
                DATE(a.criado_em) AS data_ref,
                COUNT(*) AS registros,
                COALESCE(SUM(a.pax), 0) AS pax_total,
                COUNT(DISTINCT a.uh_id) AS uhs_unicas,
                SUM(CASE WHEN a.alerta_duplicidade = 1 THEN 1 ELSE 0 END) AS duplicados,
                SUM(CASE WHEN a.fora_do_horario = 1 THEN 1 ELSE 0 END) AS fora_horario
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            $where
            GROUP BY DATE(a.criado_em)
            ORDER BY data_ref ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function kpiOperatorRanking(array $filters, int $limit = 8): array
    {
        $params = [];
        $where = $this->buildKpiWhere($filters, $params, 'a');
        $multiple = $this->multipleAccessExistsSql('a');

        $stmt = $this->db->prepare("
            SELECT
                u.id,
                u.nome,
                COUNT(*) AS registros,
                COALESCE(SUM(a.pax), 0) AS pax_total,
                SUM(CASE WHEN a.alerta_duplicidade = 1 THEN 1 ELSE 0 END) AS duplicados,
                SUM(CASE WHEN a.fora_do_horario = 1 THEN 1 ELSE 0 END) AS fora_horario,
                SUM(CASE WHEN a.alerta_duplicidade = 0 AND a.fora_do_horario = 0 AND {$multiple} THEN 1 ELSE 0 END) AS multiplos
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN usuarios u ON u.id = a.usuario_id
            $where
            GROUP BY u.id, u.nome
            ORDER BY registros DESC, pax_total DESC
            LIMIT :lim
        ");
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':lim', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $reg = (int)($row['registros'] ?? 0);
            $alertas = (int)($row['duplicados'] ?? 0) + (int)($row['fora_horario'] ?? 0) + (int)($row['multiplos'] ?? 0);
            $row['alertas_total'] = $alertas;
            $row['indice_qualidade'] = $reg > 0 ? round(max(0, 100 - (($alertas / $reg) * 100)), 2) : 100.0;
        }
        unset($row);
        return $rows;
    }

    private function normalizeOperationLabel(string $name): string
    {
        $normalized = normalize_mojibake(trim($name));
        $token = mb_strtolower($normalized, 'UTF-8');
        $tokenAscii = strtr($token, ['á' => 'a', 'ã' => 'a', 'â' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c']);

        if (strpos($tokenAscii, 'temat') !== false) {
            return 'Temático';
        }
        if (strpos($tokenAscii, 'cafe') !== false) {
            return 'Café';
        }
        if (strpos($tokenAscii, 'almoc') !== false) {
            return 'Almoço';
        }
        if (strpos($tokenAscii, 'jantar') !== false) {
            return 'Jantar';
        }
        if (strpos($tokenAscii, 'privileged') !== false) {
            return 'Privileged';
        }
        if (strpos($tokenAscii, 'vip') !== false && strpos($tokenAscii, 'premium') !== false) {
            return 'VIP Premium';
        }
        return $normalized !== '' ? $normalized : 'Sem nome';
    }

    public function kpiOperationMix(array $filters): array
    {
        $params = [];
        $where = $this->buildKpiWhere($filters, $params, 'a');
        $rows = $this->aggregate("
            SELECT o.nome, COALESCE(SUM(a.pax), 0) AS total_pax
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN operacoes o ON o.id = a.operacao_id
            $where
            GROUP BY o.nome
        ", $params);

        $merged = [];
        foreach ($rows as $row) {
            $label = $this->normalizeOperationLabel((string)($row['nome'] ?? ''));
            if (!isset($merged[$label])) {
                $merged[$label] = 0;
            }
            $merged[$label] += (int)($row['total_pax'] ?? 0);
        }
        arsort($merged);

        $result = [];
        foreach ($merged as $label => $total) {
            $result[] = ['nome' => $label, 'total_pax' => (int)$total];
        }
        return $result;
    }

    public function kpiRestaurantMix(array $filters): array
    {
        $params = [];
        $where = $this->buildKpiWhere($filters, $params, 'a');
        $rows = $this->aggregate("
            SELECT r.nome, COALESCE(SUM(a.pax), 0) AS total_pax
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN restaurantes r ON r.id = a.restaurante_id
            $where
            GROUP BY r.id, r.nome
            ORDER BY total_pax DESC
        ", $params);

        foreach ($rows as &$row) {
            $row['nome'] = normalize_mojibake((string)($row['nome'] ?? ''));
            $row['total_pax'] = (int)($row['total_pax'] ?? 0);
        }
        unset($row);
        return $rows;
    }

    public function kpiHourlyOperationFlow(array $filters): array
    {
        $params = [];
        $where = $this->buildKpiWhere($filters, $params, 'a');
        $rows = $this->aggregate("
            SELECT
                DATE_FORMAT(a.criado_em, '%H:00') AS hora,
                o.nome AS operacao,
                COALESCE(SUM(a.pax), 0) AS total_pax,
                COUNT(*) AS registros
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN operacoes o ON o.id = a.operacao_id
            $where
            GROUP BY DATE_FORMAT(a.criado_em, '%H:00'), o.nome
            ORDER BY hora ASC, total_pax DESC
        ", $params);

        $merged = [];
        foreach ($rows as $row) {
            $hour = (string)($row['hora'] ?? '');
            $operation = $this->normalizeOperationLabel((string)($row['operacao'] ?? ''));
            if ($hour === '') {
                continue;
            }
            $key = $hour . '|' . $operation;
            if (!isset($merged[$key])) {
                $merged[$key] = [
                    'hora' => $hour,
                    'operacao' => $operation,
                    'total_pax' => 0,
                    'registros' => 0,
                ];
            }
            $merged[$key]['total_pax'] += (int)($row['total_pax'] ?? 0);
            $merged[$key]['registros'] += (int)($row['registros'] ?? 0);
        }

        return array_values($merged);
    }

    public function kpiCandleSeries(array $filters): array
    {
        $params = [];
        $where = $this->buildKpiWhere($filters, $params, 'a');
        $stmt = $this->db->prepare("
            SELECT
                t.data_ref,
                CAST(SUBSTRING_INDEX(GROUP_CONCAT(t.pax_hora ORDER BY t.hora_ref ASC SEPARATOR ','), ',', 1) AS UNSIGNED) AS open_pax,
                MAX(t.pax_hora) AS high_pax,
                MIN(t.pax_hora) AS low_pax,
                CAST(SUBSTRING_INDEX(GROUP_CONCAT(t.pax_hora ORDER BY t.hora_ref DESC SEPARATOR ','), ',', 1) AS UNSIGNED) AS close_pax
            FROM (
                SELECT
                    DATE(a.criado_em) AS data_ref,
                    DATE_FORMAT(a.criado_em, '%H:00') AS hora_ref,
                    SUM(a.pax) AS pax_hora
                FROM acessos a
                JOIN unidades_habitacionais uh ON uh.id = a.uh_id
                $where
                GROUP BY DATE(a.criado_em), DATE_FORMAT(a.criado_em, '%H:00')
            ) t
            GROUP BY t.data_ref
            ORDER BY t.data_ref ASC
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['open_pax'] = (int)($row['open_pax'] ?? 0);
            $row['high_pax'] = (int)($row['high_pax'] ?? 0);
            $row['low_pax'] = (int)($row['low_pax'] ?? 0);
            $row['close_pax'] = (int)($row['close_pax'] ?? 0);
        }
        unset($row);
        return $rows;
    }

    public function kpiShiftOptions(array $filters): array
    {
        $params = [];
        $where = $this->buildKpiWhere($filters, $params, 'a');
        unset($params[':turno_id']);
        $where = preg_replace('/\s+AND\s+a\.turno_id\s*=\s*:turno_id/', '', $where) ?? $where;

        $stmt = $this->db->prepare("
            SELECT
                t.id,
                t.inicio_em,
                t.fim_em,
                r.nome AS restaurante,
                o.nome AS operacao,
                u.nome AS usuario,
                COUNT(a.id) AS registros,
                COALESCE(SUM(a.pax), 0) AS pax_total
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN turnos t ON t.id = a.turno_id
            JOIN restaurantes r ON r.id = t.restaurante_id
            JOIN operacoes o ON o.id = t.operacao_id
            JOIN usuarios u ON u.id = t.usuario_id
            $where
            GROUP BY t.id, t.inicio_em, t.fim_em, r.nome, o.nome, u.nome
            ORDER BY t.inicio_em DESC
            LIMIT 120
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['id'] = (int)($row['id'] ?? 0);
            $row['registros'] = (int)($row['registros'] ?? 0);
            $row['pax_total'] = (int)($row['pax_total'] ?? 0);
        }
        unset($row);
        return $rows;
    }

    public function kpiBuffetPax(array $filters): int
    {
        $params = [];
        $where = $this->buildKpiWhere($filters, $params, 'a');
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(a.pax), 0) AS total_pax
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN restaurantes r ON r.id = a.restaurante_id
            $where
              AND r.tipo = 'buffet'
        ");
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int)($row['total_pax'] ?? 0);
    }

    public function kpiBuffetDailyRange(string $dataInicio, string $dataFim): array
    {
        $stmt = $this->db->prepare("
            SELECT DATE(a.criado_em) AS data_ref, COALESCE(SUM(a.pax), 0) AS total_pax
            FROM acessos a
            JOIN restaurantes r ON r.id = a.restaurante_id
            WHERE DATE(a.criado_em) BETWEEN :data_inicio AND :data_fim
              AND r.tipo = 'buffet'
            GROUP BY DATE(a.criado_em)
            ORDER BY DATE(a.criado_em) ASC
        ");
        $stmt->execute([
            ':data_inicio' => $dataInicio,
            ':data_fim' => $dataFim,
        ]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['total_pax'] = (int)($row['total_pax'] ?? 0);
        }
        unset($row);
        return $rows;
    }

    public function kpiBuffetDailyOperationRange(string $dataInicio, string $dataFim): array
    {
        $stmt = $this->db->prepare("
            SELECT
                DATE(a.criado_em) AS data_ref,
                o.nome AS operacao,
                COALESCE(SUM(a.pax), 0) AS total_pax
            FROM acessos a
            JOIN restaurantes r ON r.id = a.restaurante_id
            JOIN operacoes o ON o.id = a.operacao_id
            WHERE DATE(a.criado_em) BETWEEN :data_inicio AND :data_fim
              AND r.tipo = 'buffet'
            GROUP BY DATE(a.criado_em), o.nome
            ORDER BY DATE(a.criado_em) ASC, o.nome ASC
        ");
        $stmt->execute([
            ':data_inicio' => $dataInicio,
            ':data_fim' => $dataFim,
        ]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['operacao'] = $this->normalizeOperationLabel((string)($row['operacao'] ?? ''));
            $row['total_pax'] = (int)($row['total_pax'] ?? 0);
        }
        unset($row);
        return $rows;
    }

    private function aggregate(string $sql, array $params): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

