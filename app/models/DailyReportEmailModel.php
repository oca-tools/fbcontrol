<?php
class DailyReportEmailModel extends Model
{
    private static ?bool $recipientAttachmentReady = null;
    private static ?bool $recipientAttachmentExists = null;

    private function ensureRecipientAttachmentColumn(): void
    {
        if (self::$recipientAttachmentReady === true && self::$recipientAttachmentExists !== null) {
            return;
        }
        self::$recipientAttachmentExists = false;
        try {
            $check = $this->db->query("SHOW COLUMNS FROM relatorio_email_destinatarios LIKE 'receber_anexo_vouchers'");
            if ($check && $check->fetch()) {
                self::$recipientAttachmentExists = true;
                self::$recipientAttachmentReady = true;
                return;
            }
            $this->db->exec("
                ALTER TABLE relatorio_email_destinatarios
                ADD COLUMN IF NOT EXISTS receber_anexo_vouchers TINYINT(1) NOT NULL DEFAULT 0
            ");
            $check = $this->db->query("SHOW COLUMNS FROM relatorio_email_destinatarios LIKE 'receber_anexo_vouchers'");
            self::$recipientAttachmentExists = (bool)($check && $check->fetch());
        } catch (Throwable $e) {
            // Se não conseguir alterar automaticamente, segue fluxo padrão.
        }
        self::$recipientAttachmentReady = true;
    }

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
        $this->ensureRecipientAttachmentColumn();
        $stmt = $this->db->query("
            SELECT *
            FROM relatorio_email_destinatarios
            ORDER BY email ASC
        ");
        return $stmt->fetchAll();
    }

    public function addRecipient(string $email, int $userId, bool $receberAnexoVouchers = false): void
    {
        $this->ensureRecipientAttachmentColumn();
        if (self::$recipientAttachmentExists) {
            $stmt = $this->db->prepare("
                INSERT INTO relatorio_email_destinatarios (email, ativo, receber_anexo_vouchers, criado_em)
                VALUES (:email, 1, :receber_anexo_vouchers, NOW())
                ON DUPLICATE KEY UPDATE
                    ativo = 1,
                    receber_anexo_vouchers = VALUES(receber_anexo_vouchers)
            ");
            $stmt->execute([
                ':email' => $email,
                ':receber_anexo_vouchers' => $receberAnexoVouchers ? 1 : 0,
            ]);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO relatorio_email_destinatarios (email, ativo, criado_em)
                VALUES (:email, 1, NOW())
                ON DUPLICATE KEY UPDATE ativo = 1
            ");
            $stmt->execute([':email' => $email]);
        }
        $id = (int)$this->db->lastInsertId();
        $this->audit('create', $userId, [], [
            'email' => $email,
            'id' => $id,
            'receber_anexo_vouchers' => $receberAnexoVouchers ? 1 : 0,
        ], 'relatorio_email_destinatarios', $id > 0 ? $id : null);
    }

    public function updateRecipientAttachmentFlag(int $id, bool $enabled, int $userId): void
    {
        $this->ensureRecipientAttachmentColumn();
        if (!self::$recipientAttachmentExists) {
            return;
        }
        $before = $this->findRecipient($id);
        if (!$before) {
            return;
        }
        $stmt = $this->db->prepare("
            UPDATE relatorio_email_destinatarios
               SET receber_anexo_vouchers = :enabled
             WHERE id = :id
        ");
        $stmt->execute([
            ':enabled' => $enabled ? 1 : 0,
            ':id' => $id,
        ]);
        $after = $this->findRecipient($id) ?? [];
        $this->audit('update', $userId, $before, $after, 'relatorio_email_destinatarios', $id);
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
        $recipientMap = [];
        foreach ($recipientsRows as $row) {
            $email = $this->sanitizeEmail((string)($row['email'] ?? ''));
            if ($email === '') {
                continue;
            }
            $recipientMap[$email] = $row;
        }

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
        $subject = $this->sanitizeHeaderValue($subject, 'Resumo diário A&B');
        $html = $this->buildHtml($dateRef, $metrics);
        $text = $this->buildText($dateRef, $metrics);

        $fromName = $this->sanitizeHeaderValue((string)($config['remetente_nome'] ?? 'OCA FBControl'), 'OCA FBControl');
        $fromEmailRaw = trim((string)($config['remetente_email'] ?? ''));
        if ($fromEmailRaw === '') {
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $fromEmailRaw = 'no-reply@' . preg_replace('/^www\./', '', $host);
        }
        $fromEmail = $this->sanitizeEmail($fromEmailRaw);
        if ($fromEmail === '') {
            $fromEmail = 'no-reply@localhost';
        }

        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: quoted-printable';
        $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
        $headers[] = 'Reply-To: ' . $fromEmail;
        $headers[] = 'X-Mailer: PHP/' . phpversion();

        $okCount = 0;
        $lastError = '';
        $envelopeFrom = '-f' . $fromEmail;
        $voucherAttachments = $this->getVoucherAttachments($dateRef);
        foreach ($recipients as $toRaw) {
            $to = $this->sanitizeEmail($toRaw);
            if ($to === '') {
                continue;
            }
            $recipient = $recipientMap[$to] ?? [];
            $wantsVoucherAttachment = (int)($recipient['receber_anexo_vouchers'] ?? 0) === 1;
            if ($wantsVoucherAttachment && !empty($voucherAttachments)) {
                $message = $this->buildMultipartMessage($html, $voucherAttachments);
                $mailHeaders = [
                    'MIME-Version: 1.0',
                    'Content-Type: multipart/mixed; boundary="' . $message['boundary'] . '"',
                    'From: ' . $fromName . ' <' . $fromEmail . '>',
                    'Reply-To: ' . $fromEmail,
                    'X-Mailer: PHP/' . phpversion(),
                ];
                $ok = @mail(
                    $to,
                    '=?UTF-8?B?' . base64_encode($subject) . '?=',
                    $message['body'],
                    implode("\r\n", $mailHeaders),
                    $envelopeFrom
                );
            } else {
                $htmlBody = quoted_printable_encode($html);
                $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $htmlBody, implode("\r\n", $headers), $envelopeFrom);
            }
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
            'PAX CAFÉ DA MANHÃ CORAIS' => $this->sumAcessos($dateRef, 'corais', ['cafe da manha', 'café da manhã', 'cafe', 'café']),
            'PAX ALMOÇO CORAIS' => $this->sumAcessos($dateRef, 'corais', ['almoco', 'almoço']),
            'PAX JANTAR CORAIS' => $this->sumAcessos($dateRef, 'corais', ['jantar']),
            'PAX ALMOÇO LA BRASA' => $this->sumAcessos($dateRef, 'la brasa', ['almoco', 'almoço']),
            'PAX PRIVILEGED' => $this->sumPrivileged($dateRef),
            'PAX VIP PREMIUM' => $this->sumVipPremium($dateRef),
            'PAX DAY USE' => $this->sumByUhTecnica($dateRef, '999'),
            'PAX NÃO INFORMADO' => $this->sumByUhTecnica($dateRef, '998'),
            'VOUCHERS REGISTRADOS' => $this->sumVouchers($dateRef),
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

    private function sumVouchers(string $dateRef): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM vouchers v
            WHERE v.data_venda = :d
        ");
        $stmt->execute([':d' => $dateRef]);
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
                    WHEN rsv.status IN ('Nao compareceu', 'Não compareceu', 'Não compareceu', 'Não compareceu') THEN rsv.pax
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
        $rowIndex = 0;

        foreach ($metrics as $label => $value) {
            $cleanLabel = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string)$label);
            $bg = ($rowIndex % 2 === 0) ? '#ffffff' : '#fff7ed';
            $rows .= '<tr style="background:' . $bg . ';">';
            $rows .= '<td style="padding:10px 12px;border-bottom:1px solid #fde2c7;color:#111827;font-size:14px;word-break:break-word;">' . htmlspecialchars($cleanLabel, ENT_QUOTES, 'UTF-8') . '</td>';
            $rows .= '<td style="padding:10px 12px;border-bottom:1px solid #fde2c7;text-align:right;font-weight:700;color:#9a3412;font-size:14px;white-space:nowrap;width:96px;">' . (int)$value . '</td>';
            $rows .= '</tr>';
            $rowIndex++;
        }

        return '<html><body style="margin:0;padding:18px;background:#f8fafc;font-family:Segoe UI,Arial,sans-serif;">'
            . '<table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;max-width:760px;margin:0 auto;background:#ffffff;border:1px solid #fed7aa;border-radius:14px;overflow:hidden;">'
            . '<tr><td style="padding:18px 20px;background:linear-gradient(90deg,#f97316,#fb923c);color:#ffffff;">'
            . '<div style="font-size:12px;opacity:.95;letter-spacing:.3px;text-transform:uppercase;">Relatório diário</div>'
            . '<div style="font-size:22px;font-weight:700;line-height:1.3;">OCA FBControl</div>'
            . '<div style="font-size:13px;opacity:.95;">Data de referência: ' . date('d/m/Y', strtotime($dateRef)) . '</div>'
            . '</td></tr>'
            . '<tr><td style="padding:14px 20px 8px 20px;color:#475569;font-size:13px;">Indicadores operacionais de A&amp;B para acompanhamento gerencial.</td></tr>'
            . '<tr><td style="padding:0 20px 14px 20px;">'
            . '<table cellspacing="0" cellpadding="0" style="width:100%;table-layout:fixed;border-collapse:separate;border-spacing:0;border:1px solid #fde2c7;border-radius:10px;overflow:hidden;">'
            . '<thead><tr style="background:#fff1e6;">'
            . '<th style="padding:10px 12px;text-align:left;font-size:12px;color:#9a3412;border-bottom:1px solid #fde2c7;">INDICADOR</th>'
            . '<th style="padding:10px 12px;text-align:right;font-size:12px;color:#9a3412;border-bottom:1px solid #fde2c7;width:96px;">VALOR</th>'
            . '</tr></thead>'
            . '<tbody>' . $rows . '</tbody>'
            . '</table>'
            . '</td></tr>'
            . '<tr><td style="padding:0 20px 18px 20px;font-size:12px;color:#94a3b8;">Gerado automaticamente pelo OCA FBControl.</td></tr>'
            . '</table>'
            . '</body></html>';
    }

    private function buildText(string $dateRef, array $metrics): string
    {
        $lines = ["Resumo diário A&B - " . date('d/m/Y', strtotime($dateRef)), ''];
        foreach ($metrics as $label => $value) {
            $cleanLabel = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string)$label);
            $lines[] = $cleanLabel . ': ' . (int)$value;
        }
        return implode("\n", $lines);
    }

    private function getVoucherAttachments(string $dateRef): array
    {
        $stmt = $this->db->prepare("
            SELECT voucher_anexo_path
            FROM vouchers
            WHERE data_venda = :d
              AND voucher_anexo_path IS NOT NULL
              AND voucher_anexo_path <> ''
            ORDER BY id ASC
        ");
        $stmt->execute([':d' => $dateRef]);
        $rows = $stmt->fetchAll();
        $files = [];
        $uploadRoot = realpath(dirname(__DIR__, 2) . '/public/uploads/vouchers');
        if ($uploadRoot === false) {
            return [];
        }
        foreach ($rows as $row) {
            $publicPath = (string)($row['voucher_anexo_path'] ?? '');
            if ($publicPath === '') {
                continue;
            }
            $normalized = str_replace('\\', '/', $publicPath);
            $normalized = preg_replace('#^https?://[^/]+#i', '', $normalized);
            if (strpos($normalized, '/public/') === 0) {
                $fullPath = dirname(__DIR__, 2) . $normalized;
            } else {
                $fullPath = dirname(__DIR__, 2) . '/public/' . ltrim($normalized, '/');
            }
            $real = realpath($fullPath);
            if ($real === false || strpos($real, $uploadRoot) !== 0) {
                continue;
            }
            if (!is_file($real) || !is_readable($real)) {
                continue;
            }
            $ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf'], true)) {
                continue;
            }
            $files[] = [
                'path' => $real,
                'name' => basename($real),
            ];
            if (count($files) >= 20) {
                break; // evita e-mails gigantes
            }
        }
        return $files;
    }

    private function buildMultipartMessage(string $html, array $attachments): array
    {
        $boundary = 'oca_fbcontrol_' . bin2hex(random_bytes(8));
        $eol = "\r\n";
        $body = '--' . $boundary . $eol;
        $body .= 'Content-Type: text/html; charset=UTF-8' . $eol;
        $body .= 'Content-Transfer-Encoding: quoted-printable' . $eol . $eol;
        $body .= quoted_printable_encode($html) . $eol;

        foreach ($attachments as $file) {
            $mime = mime_content_type($file['path']) ?: 'application/octet-stream';
            $raw = @file_get_contents($file['path']);
            if ($raw === false) {
                continue;
            }
            $content = chunk_split(base64_encode($raw));
            $body .= '--' . $boundary . $eol;
            $body .= 'Content-Type: ' . $mime . '; name="' . $file['name'] . '"' . $eol;
            $body .= 'Content-Disposition: attachment; filename="' . $file['name'] . '"' . $eol;
            $body .= 'Content-Transfer-Encoding: base64' . $eol . $eol;
            $body .= $content . $eol;
        }

        $body .= '--' . $boundary . '--' . $eol;
        return [
            'boundary' => $boundary,
            'body' => $body,
        ];
    }

    private function sanitizeHeaderValue(string $value, string $fallback = ''): string
    {
        $clean = trim(str_replace(["\r", "\n"], '', $value));
        return $clean !== '' ? $clean : $fallback;
    }

    private function sanitizeEmail(string $email): string
    {
        $clean = trim(str_replace(["\r", "\n"], '', $email));
        return filter_var($clean, FILTER_VALIDATE_EMAIL) ? $clean : '';
    }
}


