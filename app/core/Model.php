<?php
class Model
{
    protected PDO $db;
    private const AUDIT_REDACTED_VALUE = '[REDACTED]';
    private const AUDIT_SENSITIVE_SEGMENTS = [
        'senha',
        'password',
        'passwd',
        'token',
        'authorization',
        'secret',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    protected function dayRange(string $date): array
    {
        $start = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', trim($date) . ' 00:00:00');
        if (!$start) {
            $start = new DateTimeImmutable(date('Y-m-d 00:00:00'));
        }

        return [
            $start->format('Y-m-d H:i:s'),
            $start->modify('+1 day')->format('Y-m-d H:i:s'),
        ];
    }

    protected function dateRange(string $dateInicio, string $dateFim): array
    {
        [$start] = $this->dayRange($dateInicio);
        [, $end] = $this->dayRange($dateFim);
        return [$start, $end];
    }

    protected function applyCreatedAtFilter(string &$where, array &$params, string $field, array $filters, string $prefix = 'data'): void
    {
        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            [$start, $end] = $this->dateRange((string)$filters['data_inicio'], (string)$filters['data_fim']);
            $where .= " AND {$field} >= :{$prefix}_inicio_at AND {$field} < :{$prefix}_fim_at";
            $params[":{$prefix}_inicio_at"] = $start;
            $params[":{$prefix}_fim_at"] = $end;
            return;
        }

        if (!empty($filters['data'])) {
            [$start, $end] = $this->dayRange((string)$filters['data']);
            $where .= " AND {$field} >= :{$prefix}_start_at AND {$field} < :{$prefix}_end_at";
            $params[":{$prefix}_start_at"] = $start;
            $params[":{$prefix}_end_at"] = $end;
        }
    }

    public static function sanitizeAuditPayload(array $payload): array
    {
        $sanitized = [];
        foreach ($payload as $key => $value) {
            $rawKey = trim((string)$key);
            $snakeKey = preg_replace('/(?<!^)[A-Z]/', '_$0', $rawKey);
            $normalizedKey = strtolower(str_replace('-', '_', (string)$snakeKey));
            $segments = array_filter(explode('_', $normalizedKey), static fn(string $part): bool => $part !== '');
            $isSensitive = $normalizedKey === 'apikey'
                || $normalizedKey === 'api_key'
                || count(array_intersect($segments, self::AUDIT_SENSITIVE_SEGMENTS)) > 0;

            if ($isSensitive) {
                $sanitized[$key] = self::AUDIT_REDACTED_VALUE;
                continue;
            }

            $sanitized[$key] = is_array($value)
                ? self::sanitizeAuditPayload($value)
                : $value;
        }

        return $sanitized;
    }

    protected function audit(string $action, int $userId, array $before, array $after, string $table, ?int $recordId = null): void
    {
        $before = self::sanitizeAuditPayload($before);
        $after = self::sanitizeAuditPayload($after);
        $stmt = $this->db->prepare("
            INSERT INTO auditoria (tabela, registro_id, acao, usuario_id, dados_antes, dados_depois, criado_em)
            VALUES (:tabela, :registro_id, :acao, :usuario_id, :dados_antes, :dados_depois, NOW())
        ");
        $stmt->execute([
            ':tabela' => $table,
            ':registro_id' => $recordId,
            ':acao' => $action,
            ':usuario_id' => $userId,
            ':dados_antes' => json_encode($before, JSON_UNESCAPED_UNICODE),
            ':dados_depois' => json_encode($after, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
