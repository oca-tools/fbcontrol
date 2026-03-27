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
        $alertaDuplicidade = $this->checkDuplicidade((int)$uh['id'], (int)$data['operacao_id']);
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

    private function checkDuplicidade(int $uhId, int $operacaoId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM acessos
            WHERE uh_id = :uh_id
              AND operacao_id = :operacao_id
              AND criado_em >= (NOW() - INTERVAL 10 MINUTE)
        ");
        $stmt->execute([
            ':uh_id' => $uhId,
            ':operacao_id' => $operacaoId,
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
        return $now < $start || $now > $end;
    }

    private function multipleAccessExistsSql(string $alias = 'a'): string
    {
        return "EXISTS (
            SELECT 1
            FROM acessos ax
            WHERE ax.uh_id = {$alias}.uh_id
              AND ax.operacao_id = {$alias}.operacao_id
              AND DATE(ax.criado_em) = DATE({$alias}.criado_em)
              AND ax.id <> {$alias}.id
        )";
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
            'vip_premium_acessos' => (int)($vipPremiumRow['vip_premium_acessos'] ?? 0),
            'vip_premium_pax' => (int)($vipPremiumRow['vip_premium_pax'] ?? 0),
        ];
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

    public function reportList(array $filters): array
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
        if (!empty($filters['uh_numero'])) {
            $where .= " AND uh.numero = :uh";
            $params[':uh'] = $filters['uh_numero'];
        }
        if (!empty($filters['restaurante_id'])) {
            $where .= " AND a.restaurante_id = :restaurante_id";
            $params[':restaurante_id'] = $filters['restaurante_id'];
        }
        if (!empty($filters['operacao_id'])) {
            $where .= " AND a.operacao_id = :operacao_id";
            $params[':operacao_id'] = $filters['operacao_id'];
        }
        $this->appendStatusFilter($where, (string)($filters['status'] ?? ''), 'a');

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
            ORDER BY a.criado_em ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
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
        $stmt = $this->db->prepare("
            SELECT
                uh.numero AS uh_numero,
                MAX(CASE WHEN o.nome IN ('Cafe','Café') THEN 1 ELSE 0 END) AS cafe,
                MAX(CASE WHEN o.nome IN ('Almoco','Almoço') THEN 1 ELSE 0 END) AS almoco,
                MAX(CASE WHEN o.nome = 'Jantar' THEN 1 ELSE 0 END) AS jantar,
                MAX(CASE WHEN o.nome IN ('Tematico','Temático') THEN 1 ELSE 0 END) AS tematico,
                MAX(CASE WHEN o.nome = 'Privileged' THEN 1 ELSE 0 END) AS privileged,
                MAX(
                    CASE
                        WHEN LOWER(o.nome) LIKE '%vip%premium%' OR LOWER(r.nome) LIKE '%vip%premium%' THEN 1
                        ELSE 0
                    END
                ) AS vip_premium
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN operacoes o ON o.id = a.operacao_id
            JOIN restaurantes r ON r.id = a.restaurante_id
            WHERE DATE(a.criado_em) = :data
            GROUP BY uh.numero
            ORDER BY
                CASE WHEN uh.numero IN ('998', '999') THEN 0 ELSE 1 END,
                CAST(uh.numero AS UNSIGNED)
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

    private function aggregate(string $sql, array $params): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

