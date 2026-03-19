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
        $tolerance = (int)$restauranteOperacao['tolerancia_min'];

        if (!$start || !$end) {
            return false;
        }

        $start->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));
        $end->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));

        $start->modify("-{$tolerance} minutes");
        $end->modify("+{$tolerance} minutes");

        // ObservAção: Operações que atravessam meia-noite podem precisar de ajuste futuro.
        return $now < $start || $now > $end;
    }

    public function listRecent(int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT a.*, uh.numero AS uh_numero, r.nome AS restaurante, p.nome AS porta, o.nome AS operacao, u.nome AS usuario
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

        $totaisOperacao = $this->aggregate("
            SELECT o.nome, SUM(a.pax) AS total_pax
            FROM acessos a
            JOIN operacoes o ON o.id = a.operacao_id
            $where
            GROUP BY o.nome
            ORDER BY total_pax DESC
        ", $params);

        $totaisRestaurante = $this->aggregate("
            SELECT r.nome, SUM(a.pax) AS total_pax
            FROM acessos a
            JOIN restaurantes r ON r.id = a.restaurante_id
            $where
            GROUP BY r.nome
            ORDER BY total_pax DESC
        ", $params);

        $fluxoHorario = $this->aggregate("
            SELECT DATE_FORMAT(a.criado_em, '%H:00') AS hora, SUM(a.pax) AS total_pax
            FROM acessos a
            $where
            GROUP BY DATE_FORMAT(a.criado_em, '%H:00')
            ORDER BY hora
        ", $params);

        $foraHorario = $this->aggregate("
            SELECT COUNT(*) AS total
            FROM acessos a
            $where AND a.fora_do_horario = 1
        ", $params);

        $duplicados = $this->aggregate("
            SELECT COUNT(*) AS total
            FROM acessos a
            $where AND a.alerta_duplicidade = 1
        ", $params);

        $totais = $this->aggregate("
            SELECT COUNT(*) AS total_acessos, SUM(a.pax) AS total_pax
            FROM acessos a
            $where
        ", $params);

        return [
            'totais_operacao' => $totaisOperacao,
            'totais_restaurante' => $totaisRestaurante,
            'fluxo_horario' => $fluxoHorario,
            'fora_horario' => $foraHorario[0]['total'] ?? 0,
            'duplicados' => $duplicados[0]['total'] ?? 0,
            'total_acessos' => $totais[0]['total_acessos'] ?? 0,
            'total_pax' => $totais[0]['total_pax'] ?? 0,
        ];
    }

    public function recentByRestaurant(int $restauranteId, int $limit = 20, string $data = '', string $dataInicio = '', string $dataFim = ''): array
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

        $stmt = $this->db->prepare("
            SELECT a.*, uh.numero AS uh_numero, r.nome AS restaurante, p.nome AS porta,
                   o.nome AS operacao, u.nome AS usuario
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

    public function recentAll(int $limit = 20, string $data = '', string $dataInicio = '', string $dataFim = ''): array
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

        $stmt = $this->db->prepare("
            SELECT a.*, uh.numero AS uh_numero, r.nome AS restaurante, p.nome AS porta,
                   o.nome AS operacao, u.nome AS usuario
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

        $stmt = $this->db->prepare("
            SELECT a.*, uh.numero AS uh_numero, r.nome AS restaurante, p.nome AS porta,
                   o.nome AS operacao, u.nome AS usuario
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
                MAX(CASE WHEN o.nome = 'Privileged' THEN 1 ELSE 0 END) AS privileged
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN operacoes o ON o.id = a.operacao_id
            WHERE DATE(a.criado_em) = :data
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

    private function aggregate(string $sql, array $params): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
