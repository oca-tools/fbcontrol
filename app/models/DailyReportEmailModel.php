<?php
class DailyReportEmailModel extends Model
{
    public function getConfig(): array
    {
        $stmt = $this->db->query("SELECT * FROM relatorio_email_config WHERE id = 1 LIMIT 1");
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
        return [
            'id' => 1,
            'ativo' => 0,
            'hora_envio' => '23:00:00',
            'assunto' => 'Resumo diário A&B - {data}',
            'remetente_nome' => 'OCA FBControl',
            'remetente_email' => '',
        ];
    }

    public function saveConfig(array $data, int $userId): void
    {
        $before = $this->getConfig();
        $stmt = $this->db->prepare("
            INSERT INTO relatorio_email_config (id, ativo, hora_envio, assunto, remetente_nome, remetente_email, atualizado_em)
            VALUES (1, :ativo, :hora_envio, :assunto, :remetente_nome, :remetente_email, NOW())
            ON DUPLICATE KEY UPDATE
                ativo = VALUES(ativo),
                hora_envio = VALUES(hora_envio),
                assunto = VALUES(assunto),
                remetente_nome = VALUES(remetente_nome),
                remetente_email = VALUES(remetente_email),
                atualizado_em = NOW()
        ");
        $stmt->execute([
            ':ativo' => (int)$data['ativo'],
            ':hora_envio' => $data['hora_envio'],
            ':assunto' => $data['assunto'],
            ':remetente_nome' => $data['remetente_nome'],
            ':remetente_email' => $data['remetente_email'],
        ]);
        $after = $this->getConfig();
        $this->audit('update', $userId, $before, $after, 'relatorio_email_config', 1);
    }

    public function listRecipients(): array
    {
        $stmt = $this->db->query("
            SELECT *
            FROM relatorio_email_destinatarios
            ORDER BY email ASC
        ");
        return $stmt->fetchAll();
    }

    public function addRecipient(string $email, int $userId): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO relatorio_email_destinatarios (email, ativo, criado_em)
            VALUES (:email, 1, NOW())
            ON DUPLICATE KEY UPDATE ativo = 1
        ");
        $stmt->execute([':email' => $email]);
        $id = (int)$this->db->lastInsertId();
        $this->audit('create', $userId, [], ['email' => $email, 'id' => $id], 'relatorio_email_destinatarios', $id > 0 ? $id : null);
    }

    public function removeRecipient(int $id, int $userId): void
    {
        $before = $this->findRecipient($id);
        if (!$before) {
            return;
        }
        $stmt = $this->db->prepare("DELETE FROM relatorio_email_destinatarios WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $this->audit('delete', $userId, $before, [], 'relatorio_email_destinatarios', $id);
    }

    public function findRecipient(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM relatorio_email_destinatarios WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function listLogs(int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM relatorio_email_envios
            ORDER BY data_referencia DESC, enviado_em DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function sendDailyReport(bool $force = false, ?string $dateRef = null): array
    {
        $dateRef = $dateRef ?: date('Y-m-d');
        $config = $this->getConfig();
        $recipientsRows = $this->listRecipients();
        $recipients = array_values(array_unique(array_filter(array_map(static fn($r) => trim((string)$r['email']), $recipientsRows))));

        if (!$force && (int)($config['ativo'] ?? 0) !== 1) {
            return ['ok' => false, 'message' => 'Envio automático desativado.'];
        }
        if (empty($recipients)) {
            return ['ok' => false, 'message' => 'Nenhum destinatário configurado.'];
        }
        if (!$force && $this->wasSent($dateRef)) {
            return ['ok' => true, 'message' => 'Relatório já enviado para esta data.'];
        }

        $metrics = $this->buildMetrics($dateRef);
        $subjectTpl = trim((string)($config['assunto'] ?? 'Resumo diário A&B - {data}'));
        $subject = str_replace('{data}', date('d/m/Y', strtotime($dateRef)), $subjectTpl);
        $html = $this->buildHtml($dateRef, $metrics);
        $text = $this->buildText($dateRef, $metrics);

        $fromName = trim((string)($config['remetente_nome'] ?? 'OCA FBControl'));
        $fromEmail = trim((string)($config['remetente_email'] ?? ''));
        if ($fromEmail === '') {
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $fromEmail = 'no-reply@' . preg_replace('/^www\./', '', $host);
        }

        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
        $headers[] = 'Reply-To: ' . $fromEmail;
        $headers[] = 'X-Mailer: PHP/' . phpversion();

        $okCount = 0;
        $lastError = '';
        foreach ($recipients as $to) {
            $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html, implode("\r\n", $headers));
            if ($ok) {
                $okCount++;
            } else {
                $lastError = 'Falha ao enviar para ' . $to;
            }
        }

        $status = ($okCount === count($recipients)) ? 'success' : (($okCount > 0) ? 'partial' : 'error');
        $this->logSend($dateRef, $status, $subject, $recipients, $metrics, $lastError);

        return [
            'ok' => $okCount > 0,
            'message' => $okCount . '/' . count($recipients) . ' e-mails enviados.',
            'status' => $status,
            'text_preview' => $text,
        ];
    }

    public function dueNow(): bool
    {
        $config = $this->getConfig();
        if ((int)($config['ativo'] ?? 0) !== 1) {
            return false;
        }
        $horaEnvio = (string)($config['hora_envio'] ?? '23:00:00');
        $now = date('H:i:s');
        return $now >= $horaEnvio;
    }

    public function wasSent(string $dateRef): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM relatorio_email_envios
            WHERE data_referencia = :d
              AND status IN ('success','partial')
        ");
        $stmt->execute([':d' => $dateRef]);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0) > 0;
    }

    private function logSend(string $dateRef, string $status, string $subject, array $destinatarios, array $metrics, string $erro = ''): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO relatorio_email_envios
            (data_referencia, enviado_em, status, assunto, total_destinatarios, destinatarios, resumo_json, erro)
            VALUES (:data_ref, NOW(), :status, :assunto, :total_destinatarios, :destinatarios, :resumo_json, :erro)
        ");
        $stmt->execute([
            ':data_ref' => $dateRef,
            ':status' => $status,
            ':assunto' => $subject,
            ':total_destinatarios' => count($destinatarios),
            ':destinatarios' => implode(';', $destinatarios),
            ':resumo_json' => json_encode($metrics, JSON_UNESCAPED_UNICODE),
            ':erro' => $erro,
        ]);
    }

    private function buildMetrics(string $dateRef): array
    {
        return [
            'PAX CAFÉ DA MANHÃ CORAIS' => $this->sumAcessos($dateRef, 'corais', ['café da manhã', 'cafe da manha', 'café', 'cafe']),
            'PAX ALMOÇO CORAIS' => $this->sumAcessos($dateRef, 'corais', ['almoço', 'almoco']),
            'PAX JANTAR CORAIS' => $this->sumAcessos($dateRef, 'corais', ['jantar']),
            'PAX ALMOÇO LA BRASA' => $this->sumAcessos($dateRef, 'la brasa', ['almoço', 'almoco']),
            'PAX PRIVILEGED' => $this->sumPrivileged($dateRef),
            'PAX VIP PREMIUM' => $this->sumVipPremium($dateRef),
            'PAX DAY USE' => $this->sumByUhTecnica($dateRef, '999'),
            'PAX NÃO INFORMADO' => $this->sumByUhTecnica($dateRef, '998'),
            'PAX RESERVADA GIARDINO' => $this->sumTematicaReservada($dateRef, 'giardino'),
            'PAX REAL GIARDINO' => $this->sumTematicaReal($dateRef, 'giardino'),
            'PAX RESERVADA IXU' => $this->sumTematicaReservada($dateRef, 'ix'),
            'PAX REAL IXU' => $this->sumTematicaReal($dateRef, 'ix'),
            'PAX RESERVADA LA BRASA' => $this->sumTematicaReservada($dateRef, 'la brasa'),
            'PAX REAL LA BRASA' => $this->sumTematicaReal($dateRef, 'la brasa'),
            'NO SHOW GIARDINO' => $this->sumTematicaNoShow($dateRef, 'giardino'),
            'NO SHOW IXU' => $this->sumTematicaNoShow($dateRef, 'ix'),
            'NO SHOW LA BRASA' => $this->sumTematicaNoShow($dateRef, 'la brasa'),
        ];
    }

    private function sumAcessos(string $dateRef, string $restauranteLike, array $operacoesLike): int
    {
        $whereOp = [];
        $params = [
            ':d' => $dateRef,
            ':r' => '%' . mb_strtolower($restauranteLike, 'UTF-8') . '%',
        ];
        foreach ($operacoesLike as $idx => $op) {
            $key = ':op' . $idx;
            $whereOp[] = 'LOWER(o.nome) LIKE ' . $key;
            $params[$key] = '%' . mb_strtolower($op, 'UTF-8') . '%';
        }
        $sql = "
            SELECT COALESCE(SUM(a.pax), 0) AS total
            FROM acessos a
            JOIN restaurantes r ON r.id = a.restaurante_id
            JOIN operacoes o ON o.id = a.operacao_id
            WHERE DATE(a.criado_em) = :d
              AND LOWER(r.nome) LIKE :r
              AND (" . implode(' OR ', $whereOp) . ")
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    private function sumPrivileged(string $dateRef): int
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(a.pax), 0) AS total
            FROM acessos a
            JOIN operacoes o ON o.id = a.operacao_id
            WHERE DATE(a.criado_em) = :d
              AND LOWER(o.nome) LIKE '%privileged%'
        ");
        $stmt->execute([':d' => $dateRef]);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    private function sumVipPremium(string $dateRef): int
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(a.pax), 0) AS total
            FROM acessos a
            JOIN restaurantes r ON r.id = a.restaurante_id
            JOIN operacoes o ON o.id = a.operacao_id
            WHERE DATE(a.criado_em) = :d
              AND (
                    LOWER(r.nome) LIKE '%vip%premium%'
                 OR LOWER(o.nome) LIKE '%vip%premium%'
              )
        ");
        $stmt->execute([':d' => $dateRef]);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    private function sumByUhTecnica(string $dateRef, string $uhNumero): int
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(a.pax), 0) AS total
            FROM acessos a
            JOIN unidades_habitacionais uh ON uh.id = a.uh_id
            WHERE DATE(a.criado_em) = :d
              AND uh.numero = :uh
        ");
        $stmt->execute([
            ':d' => $dateRef,
            ':uh' => $uhNumero,
        ]);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    private function sumTematicaReservada(string $dateRef, string $restauranteLike): int
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(CASE WHEN rsv.status <> 'Cancelada' THEN rsv.pax ELSE 0 END), 0) AS total
            FROM reservas_tematicas rsv
            JOIN restaurantes r ON r.id = rsv.restaurante_id
            WHERE rsv.data_reserva = :d
              AND LOWER(r.nome) LIKE :r
        ");
        $stmt->execute([
            ':d' => $dateRef,
            ':r' => '%' . mb_strtolower($restauranteLike, 'UTF-8') . '%',
        ]);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    private function sumTematicaReal(string $dateRef, string $restauranteLike): int
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(
                CASE
                    WHEN rsv.status = 'Cancelada' THEN 0
                    WHEN rsv.pax_real IS NOT NULL THEN rsv.pax_real
                    WHEN rsv.status = 'Finalizada' THEN rsv.pax
                    ELSE 0
                END
            ), 0) AS total
            FROM reservas_tematicas rsv
            JOIN restaurantes r ON r.id = rsv.restaurante_id
            WHERE rsv.data_reserva = :d
              AND LOWER(r.nome) LIKE :r
        ");
        $stmt->execute([
            ':d' => $dateRef,
            ':r' => '%' . mb_strtolower($restauranteLike, 'UTF-8') . '%',
        ]);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    private function sumTematicaNoShow(string $dateRef, string $restauranteLike): int
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(
                CASE
                    WHEN rsv.status = 'Não compareceu' THEN rsv.pax
                    WHEN rsv.status <> 'Cancelada' AND rsv.pax_real IS NOT NULL AND rsv.pax_real < rsv.pax THEN (rsv.pax - rsv.pax_real)
                    ELSE 0
                END
            ), 0) AS total
            FROM reservas_tematicas rsv
            JOIN restaurantes r ON r.id = rsv.restaurante_id
            WHERE rsv.data_reserva = :d
              AND LOWER(r.nome) LIKE :r
        ");
        $stmt->execute([
            ':d' => $dateRef,
            ':r' => '%' . mb_strtolower($restauranteLike, 'UTF-8') . '%',
        ]);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    private function buildHtml(string $dateRef, array $metrics): string
    {
        $rows = '';
        foreach ($metrics as $label => $value) {
            $rows .= '<tr><td style="padding:8px 10px;border:1px solid #e2e8f0;">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</td>';
            $rows .= '<td style="padding:8px 10px;border:1px solid #e2e8f0;text-align:right;font-weight:700;">' . (int)$value . '</td></tr>';
        }
        return '<html><body style="font-family:Arial,sans-serif;">'
            . '<h2>Resumo diário A&B - ' . date('d/m/Y', strtotime($dateRef)) . '</h2>'
            . '<table style="border-collapse:collapse;width:100%;max-width:760px;">'
            . '<thead><tr><th style="padding:8px 10px;border:1px solid #e2e8f0;text-align:left;">Indicador</th>'
            . '<th style="padding:8px 10px;border:1px solid #e2e8f0;text-align:right;">Valor</th></tr></thead>'
            . '<tbody>' . $rows . '</tbody></table>'
            . '<p style="margin-top:16px;color:#64748b;font-size:12px;">Gerado automaticamente pelo OCA FBControl.</p>'
            . '</body></html>';
    }

    private function buildText(string $dateRef, array $metrics): string
    {
        $lines = ["Resumo diário A&B - " . date('d/m/Y', strtotime($dateRef)), ''];
        foreach ($metrics as $label => $value) {
            $lines[] = $label . ': ' . (int)$value;
        }
        return implode("\n", $lines);
    }
}
