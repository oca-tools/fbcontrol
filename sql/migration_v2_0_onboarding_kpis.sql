-- OCA FBControl v2.0 - onboarding + performance de KPIs

CREATE TABLE IF NOT EXISTS usuarios_onboarding (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    hostess_tutorial_seen TINYINT(1) NOT NULL DEFAULT 0,
    hostess_tutorial_completed TINYINT(1) NOT NULL DEFAULT 0,
    hostess_tutorial_completed_em DATETIME NULL,
    criado_em DATETIME NOT NULL,
    atualizado_em DATETIME NOT NULL,
    UNIQUE KEY uq_onboarding_usuario (usuario_id),
    CONSTRAINT fk_onboarding_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Índices para consultas de KPI/monitoramento
SET @idx_user_data := (
    SELECT COUNT(1)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'acessos'
      AND index_name = 'idx_acessos_user_data'
);
SET @sql_user_data := IF(@idx_user_data = 0,
    'CREATE INDEX idx_acessos_user_data ON acessos (usuario_id, criado_em)',
    'SELECT 1'
);
PREPARE stmt_user_data FROM @sql_user_data;
EXECUTE stmt_user_data;
DEALLOCATE PREPARE stmt_user_data;

SET @idx_status_data := (
    SELECT COUNT(1)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'acessos'
      AND index_name = 'idx_acessos_status_data'
);
SET @sql_status_data := IF(@idx_status_data = 0,
    'CREATE INDEX idx_acessos_status_data ON acessos (alerta_duplicidade, fora_do_horario, criado_em)',
    'SELECT 1'
);
PREPARE stmt_status_data FROM @sql_status_data;
EXECUTE stmt_status_data;
DEALLOCATE PREPARE stmt_status_data;

SET @idx_uh_data := (
    SELECT COUNT(1)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'acessos'
      AND index_name = 'idx_acessos_uh_data'
);
SET @sql_uh_data := IF(@idx_uh_data = 0,
    'CREATE INDEX idx_acessos_uh_data ON acessos (uh_id, criado_em)',
    'SELECT 1'
);
PREPARE stmt_uh_data FROM @sql_uh_data;
EXECUTE stmt_uh_data;
DEALLOCATE PREPARE stmt_uh_data;