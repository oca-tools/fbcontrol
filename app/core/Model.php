<?php
class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    protected function audit(string $action, int $userId, array $before, array $after, string $table, ?int $recordId = null): void
    {
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
