-- Sessao unica por usuario (hardening de autenticacao)
CREATE TABLE IF NOT EXISTS sessoes_ativas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    session_id VARCHAR(128) NOT NULL,
    token VARCHAR(64) NOT NULL,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    atualizado_em DATETIME NOT NULL,
    UNIQUE KEY uq_sessoes_ativas_usuario (usuario_id),
    KEY idx_sessoes_ativas_atualizado (atualizado_em),
    CONSTRAINT fk_sessoes_ativas_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Limpeza defensiva de sessoes antigas (execucao segura e idempotente)
DELETE FROM sessoes_ativas
WHERE atualizado_em < (NOW() - INTERVAL 2 DAY);
