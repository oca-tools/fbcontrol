<?php
class SpecialAccessModel extends Model
{
    public function register(array $data, int $userId): array
    {
        $unitModel = new UnitModel();
        $specialModel = new RestaurantSpecialModel();

        $uh = $unitModel->findByNumero($data['uh_numero']);
        if (!$uh) {
            return [
                'id' => 0,
                'error' => 'uh_invalida',
            ];
        }

        $special = $specialModel->findByRestaurantType((int)$data['restaurante_id'], $data['tipo']);
        $alertaDuplicidade = $this->checkDuplicidade((int)$uh['id'], $data['tipo']);
        $foraDoHorario = $this->checkForaHorario($special);

        $stmt = $this->db->prepare("
            INSERT INTO acessos_especiais
            (turno_especial_id, uh_id, pax, restaurante_id, porta_id, tipo, alerta_duplicidade, fora_do_horario, criado_em, usuario_id)
            VALUES
            (:turno_id, :uh_id, :pax, :restaurante_id, :porta_id, :tipo, :alerta_duplicidade, :fora_do_horario, NOW(), :usuario_id)
        ");
        $stmt->execute([
            ':turno_id' => $data['turno_especial_id'] ?? null,
            ':uh_id' => $uh['id'],
            ':pax' => $data['pax'],
            ':restaurante_id' => $data['restaurante_id'],
            ':porta_id' => $data['porta_id'] ?? null,
            ':tipo' => $data['tipo'],
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

    private function checkDuplicidade(int $uhId, string $tipo): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM acessos_especiais
            WHERE uh_id = :uh_id
              AND tipo = :tipo
              AND criado_em >= (NOW() - INTERVAL 10 MINUTE)
        ");
        $stmt->execute([
            ':uh_id' => $uhId,
            ':tipo' => $tipo,
        ]);
        $row = $stmt->fetch();
        return ($row['total'] ?? 0) > 0;
    }

    private function checkForaHorario(?array $special): bool
    {
        if (!$special) {
            return true;
        }

        $tz = new DateTimeZone(date_default_timezone_get());
        $now = new DateTime('now', $tz);

        $start = DateTime::createFromFormat('H:i:s', $special['hora_inicio'], $tz);
        $end = DateTime::createFromFormat('H:i:s', $special['hora_fim'], $tz);
        $tolerance = (int)$special['tolerancia_min'];

        if (!$start || !$end) {
            return false;
        }

        $start->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));
        $end->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));

        $start->modify("-{$tolerance} minutes");
        $end->modify("+{$tolerance} minutes");

        return $now < $start || $now > $end;
    }

    public function listRecent(int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT a.*, uh.numero AS uh_numero, r.nome AS restaurante, p.nome AS porta,
                   CASE WHEN a.tipo = 'privileged' THEN 'Privileged' ELSE 'Temático' END AS operacao,
                   u.nome AS usuario
            FROM acessos_especiais a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN restaurantes r ON r.id = a.restaurante_id
            LEFT JOIN portas p ON p.id = a.porta_id
            JOIN usuarios u ON u.id = a.usuario_id
            ORDER BY a.criado_em DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function recentAll(int $limit = 20, string $data = ''): array
    {
        $where = "";
        $params = [];
        if ($data !== '') {
            $where = "WHERE DATE(a.criado_em) = :data";
            $params[':data'] = $data;
        }

        $stmt = $this->db->prepare("
            SELECT a.*, uh.numero AS uh_numero, r.nome AS restaurante, p.nome AS porta,
                   CASE WHEN a.tipo = 'privileged' THEN 'Privileged' ELSE 'Temático' END AS operacao,
                   u.nome AS usuario
            FROM acessos_especiais a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN restaurantes r ON r.id = a.restaurante_id
            LEFT JOIN portas p ON p.id = a.porta_id
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

    public function sumPaxByUhTipoDate(string $uhNumero, string $tipo, string $date): int
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(a.pax), 0) AS total_pax
            FROM acessos_especiais a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            WHERE uh.numero = :uh
              AND a.tipo = :tipo
              AND DATE(a.criado_em) = :data
        ");
        $stmt->execute([
            ':uh' => $uhNumero,
            ':tipo' => $tipo,
            ':data' => $date,
        ]);
        $row = $stmt->fetch();
        return (int)($row['total_pax'] ?? 0);
    }

    public function statsForDate(string $date): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total_acessos,
                SUM(pax) AS total_pax,
                SUM(CASE WHEN alerta_duplicidade = 1 THEN 1 ELSE 0 END) AS duplicados,
                SUM(CASE WHEN fora_do_horario = 1 THEN 1 ELSE 0 END) AS fora_horario
            FROM acessos_especiais
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

    public function reportList(array $filters): array
    {
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($filters['data'])) {
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

        $stmt = $this->db->prepare("
            SELECT a.*, uh.numero AS uh_numero, r.nome AS restaurante, p.nome AS porta,
                   CASE WHEN a.tipo = 'privileged' THEN 'Privileged' ELSE 'Temático' END AS operacao,
                   u.nome AS usuario
            FROM acessos_especiais a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN restaurantes r ON r.id = a.restaurante_id
            LEFT JOIN portas p ON p.id = a.porta_id
            JOIN usuarios u ON u.id = a.usuario_id
            $where
            ORDER BY a.criado_em ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function uhJourney(string $uhNumero, string $data): array
    {
        $stmt = $this->db->prepare("
            SELECT a.criado_em, r.nome AS restaurante,
                   CASE WHEN a.tipo = 'privileged' THEN 'Privileged' ELSE 'Temático' END AS operacao,
                   p.nome AS porta, a.pax, a.alerta_duplicidade, a.fora_do_horario, u.nome AS usuario
            FROM acessos_especiais a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN restaurantes r ON r.id = a.restaurante_id
            LEFT JOIN portas p ON p.id = a.porta_id
            JOIN usuarios u ON u.id = a.usuario_id
            WHERE uh.numero = :uh AND DATE(a.criado_em) = :data
            ORDER BY a.criado_em ASC
        ");
        $stmt->execute([
            ':uh' => $uhNumero,
            ':data' => $data,
        ]);
        return $stmt->fetchAll();
    }

    public function uhSummary(string $uhNumero, string $data): array
    {
        $stmt = $this->db->prepare("
            SELECT r.nome AS restaurante,
                   CASE WHEN a.tipo = 'privileged' THEN 'Privileged' ELSE 'Temático' END AS operacao,
                   MIN(a.criado_em) AS primeira_passagem,
                   MAX(a.criado_em) AS ultima_passagem,
                   COUNT(*) AS acessos,
                   SUM(a.pax) AS pax_total
            FROM acessos_especiais a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            JOIN restaurantes r ON r.id = a.restaurante_id
            WHERE uh.numero = :uh AND DATE(a.criado_em) = :data
            GROUP BY r.nome, operacao
            ORDER BY primeira_passagem ASC
        ");
        $stmt->execute([
            ':uh' => $uhNumero,
            ':data' => $data,
        ]);
        return $stmt->fetchAll();
    }

    public function dailyMap(string $data): array
    {
        $stmt = $this->db->prepare("
            SELECT
                uh.numero AS uh_numero,
                MAX(CASE WHEN a.tipo = 'tematico' THEN 1 ELSE 0 END) AS tematico,
                MAX(CASE WHEN a.tipo = 'privileged' THEN 1 ELSE 0 END) AS privileged
            FROM acessos_especiais a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            WHERE DATE(a.criado_em) = :data
            GROUP BY uh.numero
            ORDER BY CAST(uh.numero AS UNSIGNED)
        ");
        $stmt->execute([':data' => $data]);
        return $stmt->fetchAll();
    }
}

