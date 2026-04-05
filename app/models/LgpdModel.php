<?php
class LgpdModel extends Model
{
    private const REQUEST_FINAL_STATUSES = ['concluida', 'indeferida'];
    private const INCIDENT_FINAL_STATUS = 'encerrado';

    public function getConfig(): array
    {
        $stmt = $this->db->query("SELECT * FROM lgpd_config WHERE id = 1 LIMIT 1");
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }

        return [
            'id' => 1,
            'controlador_nome' => 'Grand Oca Maragogi Resort',
            'controlador_email' => '',
            'encarregado_nome' => '',
            'encarregado_email' => '',
            'encarregado_telefone' => '',
            'canal_titular_url' => '/?r=privacidade/index',
            'canal_titular_email' => '',
            'politica_privacidade_url' => '/?r=privacidade/index',
            'prazo_titular_dias' => 15,
            'prazo_incidente_dias_uteis' => 3,
            'atualizado_por' => null,
            'atualizado_em' => null,
        ];
    }

    public function saveConfig(array $data, int $userId): void
    {
        $before = $this->getConfig();
        $stmt = $this->db->prepare("
            INSERT INTO lgpd_config
            (id, controlador_nome, controlador_email, encarregado_nome, encarregado_email, encarregado_telefone, canal_titular_url, canal_titular_email, politica_privacidade_url, prazo_titular_dias, prazo_incidente_dias_uteis, atualizado_por, atualizado_em)
            VALUES
            (1, :controlador_nome, :controlador_email, :encarregado_nome, :encarregado_email, :encarregado_telefone, :canal_titular_url, :canal_titular_email, :politica_privacidade_url, :prazo_titular_dias, :prazo_incidente_dias_uteis, :atualizado_por, NOW())
            ON DUPLICATE KEY UPDATE
                controlador_nome = VALUES(controlador_nome),
                controlador_email = VALUES(controlador_email),
                encarregado_nome = VALUES(encarregado_nome),
                encarregado_email = VALUES(encarregado_email),
                encarregado_telefone = VALUES(encarregado_telefone),
                canal_titular_url = VALUES(canal_titular_url),
                canal_titular_email = VALUES(canal_titular_email),
                politica_privacidade_url = VALUES(politica_privacidade_url),
                prazo_titular_dias = VALUES(prazo_titular_dias),
                prazo_incidente_dias_uteis = VALUES(prazo_incidente_dias_uteis),
                atualizado_por = VALUES(atualizado_por),
                atualizado_em = NOW()
        ");
        $stmt->execute([
            ':controlador_nome' => $data['controlador_nome'],
            ':controlador_email' => $data['controlador_email'],
            ':encarregado_nome' => $data['encarregado_nome'],
            ':encarregado_email' => $data['encarregado_email'],
            ':encarregado_telefone' => $data['encarregado_telefone'],
            ':canal_titular_url' => $data['canal_titular_url'],
            ':canal_titular_email' => $data['canal_titular_email'],
            ':politica_privacidade_url' => $data['politica_privacidade_url'],
            ':prazo_titular_dias' => $data['prazo_titular_dias'],
            ':prazo_incidente_dias_uteis' => $data['prazo_incidente_dias_uteis'],
            ':atualizado_por' => $userId,
        ]);

        $after = $this->getConfig();
        $this->audit('update', $userId, $before, $after, 'lgpd_config', 1);
        $this->logEvent('config', 'lgpd_config', 'update', $userId, [
            'before' => $before,
            'after' => $after,
        ]);
    }

    public function summary(): array
    {
        $openStatuses = ['aberta', 'em_tratamento'];
        $inholders = implode(',', array_fill(0, count($openStatuses), '?'));

        $stmtOpen = $this->db->prepare("SELECT COUNT(*) AS total FROM lgpd_solicitacoes WHERE status IN ($inholders)");
        $stmtOpen->execute($openStatuses);
        $requestsOpen = (int)($stmtOpen->fetch()['total'] ?? 0);

        $stmtSoon = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM lgpd_solicitacoes
            WHERE status IN ($inholders)
              AND prazo_resposta_em IS NOT NULL
              AND prazo_resposta_em BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 2 DAY)
        ");
        $stmtSoon->execute($openStatuses);
        $requestsDueSoon = (int)($stmtSoon->fetch()['total'] ?? 0);

        $stmtInc = $this->db->prepare("SELECT COUNT(*) AS total FROM lgpd_incidentes WHERE status <> :closed");
        $stmtInc->execute([':closed' => self::INCIDENT_FINAL_STATUS]);
        $incidentsOpen = (int)($stmtInc->fetch()['total'] ?? 0);

        return [
            'requests_open' => $requestsOpen,
            'requests_due_soon' => $requestsDueSoon,
            'incidents_open' => $incidentsOpen,
        ];
    }

    public function listRequests(array $filters = []): array
    {
        $where = [];
        $params = [];

        $status = trim((string)($filters['status'] ?? ''));
        if ($status !== '') {
            $where[] = 's.status = :status';
            $params[':status'] = $status;
        }

        $dateFrom = trim((string)($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $where[] = 'DATE(s.recebido_em) >= :date_from';
            $params[':date_from'] = $dateFrom;
        }

        $dateTo = trim((string)($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $where[] = 'DATE(s.recebido_em) <= :date_to';
            $params[':date_to'] = $dateTo;
        }

        $sql = "
            SELECT s.*, uc.nome AS criado_por_nome, uu.nome AS atualizado_por_nome
            FROM lgpd_solicitacoes s
            LEFT JOIN usuarios uc ON uc.id = s.criado_por
            LEFT JOIN usuarios uu ON uu.id = s.atualizado_por
        ";
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY s.recebido_em DESC, s.id DESC LIMIT 200';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function createRequest(array $data, int $userId): int
    {
        $protocol = $this->generateProtocol('LGPD');
        $receivedAt = $data['recebido_em'] ?: date('Y-m-d H:i:s');

        $prazo = $data['prazo_resposta_em'];
        if ($prazo === '') {
            $config = $this->getConfig();
            $prazoDias = max(1, (int)($config['prazo_titular_dias'] ?? 15));
            $prazo = date('Y-m-d H:i:s', strtotime($receivedAt . ' +' . $prazoDias . ' days'));
        }

        $stmt = $this->db->prepare("
            INSERT INTO lgpd_solicitacoes
            (protocolo, tipo, titular_nome, titular_documento, titular_email, detalhes, status, recebido_em, prazo_resposta_em, concluido_em, resposta_resumo, criado_por, atualizado_por, atualizado_em)
            VALUES
            (:protocolo, :tipo, :titular_nome, :titular_documento, :titular_email, :detalhes, :status, :recebido_em, :prazo_resposta_em, NULL, '', :criado_por, :atualizado_por, NOW())
        ");
        $stmt->execute([
            ':protocolo' => $protocol,
            ':tipo' => $data['tipo'],
            ':titular_nome' => $data['titular_nome'],
            ':titular_documento' => $data['titular_documento'],
            ':titular_email' => $data['titular_email'],
            ':detalhes' => $data['detalhes'],
            ':status' => 'aberta',
            ':recebido_em' => $receivedAt,
            ':prazo_resposta_em' => $prazo,
            ':criado_por' => $userId,
            ':atualizado_por' => $userId,
        ]);

        $id = (int)$this->db->lastInsertId();
        $after = $this->findRequest($id) ?? [];
        $this->audit('create', $userId, [], $after, 'lgpd_solicitacoes', $id);
        $this->logEvent('solicitacao', $protocol, 'create', $userId, $after);
        return $id;
    }

    public function updateRequest(int $id, array $data, int $userId): bool
    {
        $before = $this->findRequest($id);
        if (!$before) {
            return false;
        }

        $status = $data['status'];
        $isFinal = in_array($status, self::REQUEST_FINAL_STATUSES, true);
        $concludedAt = $isFinal ? ($data['concluido_em'] ?: date('Y-m-d H:i:s')) : null;

        $stmt = $this->db->prepare("
            UPDATE lgpd_solicitacoes
            SET
                tipo = :tipo,
                titular_nome = :titular_nome,
                titular_documento = :titular_documento,
                titular_email = :titular_email,
                detalhes = :detalhes,
                status = :status,
                prazo_resposta_em = :prazo_resposta_em,
                concluido_em = :concluido_em,
                resposta_resumo = :resposta_resumo,
                atualizado_por = :atualizado_por,
                atualizado_em = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':tipo' => $data['tipo'],
            ':titular_nome' => $data['titular_nome'],
            ':titular_documento' => $data['titular_documento'],
            ':titular_email' => $data['titular_email'],
            ':detalhes' => $data['detalhes'],
            ':status' => $status,
            ':prazo_resposta_em' => $data['prazo_resposta_em'] ?: null,
            ':concluido_em' => $concludedAt,
            ':resposta_resumo' => $data['resposta_resumo'],
            ':atualizado_por' => $userId,
            ':id' => $id,
        ]);

        $after = $this->findRequest($id) ?? [];
        $this->audit('update', $userId, $before, $after, 'lgpd_solicitacoes', $id);
        $this->logEvent('solicitacao', (string)($after['protocolo'] ?? ('id:' . $id)), 'update', $userId, [
            'before' => $before,
            'after' => $after,
        ]);
        return true;
    }

    public function findRequest(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM lgpd_solicitacoes WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function listIncidents(array $filters = []): array
    {
        $where = [];
        $params = [];

        $status = trim((string)($filters['status'] ?? ''));
        if ($status !== '') {
            $where[] = 'i.status = :status';
            $params[':status'] = $status;
        }

        $risk = trim((string)($filters['risk'] ?? ''));
        if ($risk !== '') {
            $where[] = 'i.risco_nivel = :risk';
            $params[':risk'] = $risk;
        }

        $sql = "
            SELECT i.*, uc.nome AS criado_por_nome, uu.nome AS atualizado_por_nome
            FROM lgpd_incidentes i
            LEFT JOIN usuarios uc ON uc.id = i.criado_por
            LEFT JOIN usuarios uu ON uu.id = i.atualizado_por
        ";
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY i.detectado_em DESC, i.id DESC LIMIT 150';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function createIncident(array $data, int $userId): int
    {
        $code = $this->generateProtocol('INC');
        $detectedAt = $data['detectado_em'] ?: date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("
            INSERT INTO lgpd_incidentes
            (codigo, titulo, categoria, status, risco_nivel, data_incidente, detectado_em, titulares_afetados, dados_afetados, medidas_adotadas, comunicado_anpd, comunicado_titulares, comunicado_em, encerrado_em, criado_por, atualizado_por, atualizado_em)
            VALUES
            (:codigo, :titulo, :categoria, :status, :risco_nivel, :data_incidente, :detectado_em, :titulares_afetados, :dados_afetados, :medidas_adotadas, :comunicado_anpd, :comunicado_titulares, :comunicado_em, :encerrado_em, :criado_por, :atualizado_por, NOW())
        ");
        $stmt->execute([
            ':codigo' => $code,
            ':titulo' => $data['titulo'],
            ':categoria' => $data['categoria'],
            ':status' => $data['status'] ?: 'aberto',
            ':risco_nivel' => $data['risco_nivel'],
            ':data_incidente' => $data['data_incidente'] ?: null,
            ':detectado_em' => $detectedAt,
            ':titulares_afetados' => max(0, (int)$data['titulares_afetados']),
            ':dados_afetados' => $data['dados_afetados'],
            ':medidas_adotadas' => $data['medidas_adotadas'],
            ':comunicado_anpd' => (int)$data['comunicado_anpd'],
            ':comunicado_titulares' => (int)$data['comunicado_titulares'],
            ':comunicado_em' => $data['comunicado_em'] ?: null,
            ':encerrado_em' => $data['status'] === self::INCIDENT_FINAL_STATUS ? ($data['encerrado_em'] ?: date('Y-m-d H:i:s')) : null,
            ':criado_por' => $userId,
            ':atualizado_por' => $userId,
        ]);

        $id = (int)$this->db->lastInsertId();
        $after = $this->findIncident($id) ?? [];
        $this->audit('create', $userId, [], $after, 'lgpd_incidentes', $id);
        $this->logEvent('incidente', $code, 'create', $userId, $after);
        return $id;
    }

    public function updateIncident(int $id, array $data, int $userId): bool
    {
        $before = $this->findIncident($id);
        if (!$before) {
            return false;
        }

        $status = $data['status'];
        $closedAt = ($status === self::INCIDENT_FINAL_STATUS) ? ($data['encerrado_em'] ?: date('Y-m-d H:i:s')) : null;

        $stmt = $this->db->prepare("
            UPDATE lgpd_incidentes
            SET
                titulo = :titulo,
                categoria = :categoria,
                status = :status,
                risco_nivel = :risco_nivel,
                data_incidente = :data_incidente,
                detectado_em = :detectado_em,
                titulares_afetados = :titulares_afetados,
                dados_afetados = :dados_afetados,
                medidas_adotadas = :medidas_adotadas,
                comunicado_anpd = :comunicado_anpd,
                comunicado_titulares = :comunicado_titulares,
                comunicado_em = :comunicado_em,
                encerrado_em = :encerrado_em,
                atualizado_por = :atualizado_por,
                atualizado_em = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':titulo' => $data['titulo'],
            ':categoria' => $data['categoria'],
            ':status' => $status,
            ':risco_nivel' => $data['risco_nivel'],
            ':data_incidente' => $data['data_incidente'] ?: null,
            ':detectado_em' => $data['detectado_em'] ?: date('Y-m-d H:i:s'),
            ':titulares_afetados' => max(0, (int)$data['titulares_afetados']),
            ':dados_afetados' => $data['dados_afetados'],
            ':medidas_adotadas' => $data['medidas_adotadas'],
            ':comunicado_anpd' => (int)$data['comunicado_anpd'],
            ':comunicado_titulares' => (int)$data['comunicado_titulares'],
            ':comunicado_em' => $data['comunicado_em'] ?: null,
            ':encerrado_em' => $closedAt,
            ':atualizado_por' => $userId,
            ':id' => $id,
        ]);

        $after = $this->findIncident($id) ?? [];
        $this->audit('update', $userId, $before, $after, 'lgpd_incidentes', $id);
        $this->logEvent('incidente', (string)($after['codigo'] ?? ('id:' . $id)), 'update', $userId, [
            'before' => $before,
            'after' => $after,
        ]);
        return true;
    }

    public function findIncident(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM lgpd_incidentes WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function listRetentionPolicies(): array
    {
        $stmt = $this->db->query("
            SELECT p.*, u.nome AS atualizado_por_nome
            FROM lgpd_retencao_politicas p
            LEFT JOIN usuarios u ON u.id = p.atualizado_por
            ORDER BY p.tabela_nome ASC
        ");
        return $stmt->fetchAll();
    }

    public function upsertRetentionPolicy(array $data, int $userId): void
    {
        $tableName = strtolower(trim((string)$data['tabela_nome']));
        if ($tableName === '') {
            throw new InvalidArgumentException('Tabela invalida.');
        }

        $stmtBefore = $this->db->prepare("SELECT * FROM lgpd_retencao_politicas WHERE tabela_nome = :table LIMIT 1");
        $stmtBefore->execute([':table' => $tableName]);
        $before = $stmtBefore->fetch() ?: [];

        $stmt = $this->db->prepare("
            INSERT INTO lgpd_retencao_politicas
            (tabela_nome, descricao, retencao_dias, modo, ativo, atualizado_por, atualizado_em)
            VALUES
            (:tabela_nome, :descricao, :retencao_dias, :modo, :ativo, :atualizado_por, NOW())
            ON DUPLICATE KEY UPDATE
                descricao = VALUES(descricao),
                retencao_dias = VALUES(retencao_dias),
                modo = VALUES(modo),
                ativo = VALUES(ativo),
                atualizado_por = VALUES(atualizado_por),
                atualizado_em = NOW()
        ");
        $stmt->execute([
            ':tabela_nome' => $tableName,
            ':descricao' => trim((string)$data['descricao']),
            ':retencao_dias' => max(1, (int)$data['retencao_dias']),
            ':modo' => ($data['modo'] === 'anonimizar') ? 'anonimizar' : 'eliminar',
            ':ativo' => (int)$data['ativo'] === 1 ? 1 : 0,
            ':atualizado_por' => $userId,
        ]);

        $stmtAfter = $this->db->prepare("SELECT * FROM lgpd_retencao_politicas WHERE tabela_nome = :table LIMIT 1");
        $stmtAfter->execute([':table' => $tableName]);
        $after = $stmtAfter->fetch() ?: [];

        $recordId = isset($after['id']) ? (int)$after['id'] : null;
        $this->audit('update', $userId, $before, $after, 'lgpd_retencao_politicas', $recordId);
        $this->logEvent('retencao', $tableName, 'upsert', $userId, [
            'before' => $before,
            'after' => $after,
        ]);
    }

    public function runRetentionJob(?int $userId = null): array
    {
        $policies = $this->listRetentionPolicies();
        $result = [
            'processed' => 0,
            'affected' => 0,
            'errors' => [],
        ];

        $allowed = [
            'relatorio_email_envios' => 'data_referencia',
            'auditoria' => 'criado_em',
            'lgpd_eventos' => 'criado_em',
            'sessoes_ativas' => 'atualizado_em',
        ];

        foreach ($policies as $policy) {
            if ((int)($policy['ativo'] ?? 0) !== 1) {
                continue;
            }

            $table = strtolower(trim((string)($policy['tabela_nome'] ?? '')));
            if ($table === '' || !isset($allowed[$table])) {
                continue;
            }
            if (!$this->tableExists($table)) {
                continue;
            }
            if (($policy['modo'] ?? 'eliminar') !== 'eliminar') {
                continue;
            }

            $column = $allowed[$table];
            $days = max(1, (int)($policy['retencao_dias'] ?? 180));
            $sql = "DELETE FROM `$table` WHERE `$column` < DATE_SUB(NOW(), INTERVAL :days DAY)";

            try {
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':days', $days, PDO::PARAM_INT);
                $stmt->execute();
                $affected = $stmt->rowCount();
                $result['processed']++;
                $result['affected'] += $affected;

                $this->logEvent('retencao', $table, 'cleanup', $userId, [
                    'retencao_dias' => $days,
                    'deleted_rows' => $affected,
                ]);
            } catch (Throwable $e) {
                $result['errors'][] = $table . ': ' . $e->getMessage();
            }
        }

        return $result;
    }

    public function listEvents(int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT e.*, u.nome AS usuario_nome
            FROM lgpd_eventos e
            LEFT JOIN usuarios u ON u.id = e.usuario_id
            ORDER BY e.criado_em DESC, e.id DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', max(1, min(200, $limit)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->db->prepare("SHOW TABLES LIKE :table_name");
        $stmt->execute([':table_name' => $table]);
        return (bool)$stmt->fetch();
    }

    private function generateProtocol(string $prefix): string
    {
        $prefix = strtoupper(preg_replace('/[^A-Z0-9]/', '', $prefix) ?: 'LGPD');
        return $prefix . '-' . date('Ymd') . '-' . str_pad((string)random_int(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    private function logEvent(string $type, string $reference, string $action, ?int $userId, array $details): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO lgpd_eventos (tipo, referencia, acao, detalhes_json, usuario_id, criado_em)
            VALUES (:tipo, :referencia, :acao, :detalhes_json, :usuario_id, NOW())
        ");
        $stmt->execute([
            ':tipo' => substr($type, 0, 30),
            ':referencia' => substr($reference, 0, 120),
            ':acao' => substr($action, 0, 40),
            ':detalhes_json' => json_encode($details, JSON_UNESCAPED_UNICODE),
            ':usuario_id' => $userId ?: null,
        ]);
    }
}
