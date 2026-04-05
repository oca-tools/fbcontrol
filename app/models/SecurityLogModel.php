<?php
class SecurityLogModel extends Model
{
    public function log(string $action, ?int $userId = null, array $context = []): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO auditoria (tabela, registro_id, acao, usuario_id, dados_antes, dados_depois, criado_em)
                VALUES (:tabela, :registro_id, :acao, :usuario_id, :dados_antes, :dados_depois, NOW())
            ");
            $stmt->execute([
                ':tabela' => 'seguranca',
                ':registro_id' => null,
                ':acao' => substr($action, 0, 120),
                ':usuario_id' => $userId,
                ':dados_antes' => json_encode([], JSON_UNESCAPED_UNICODE),
                ':dados_depois' => json_encode($context, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (Throwable $e) {
            error_log('[security-log] ' . $action . ' ' . json_encode($context, JSON_UNESCAPED_UNICODE));
        }
    }
}

