-- v2.2 - Reservas tematicas: lote/grupo, CHD e indices operacionais

CREATE TABLE IF NOT EXISTS reservas_tematicas_grupos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT NOT NULL,
    data_reserva DATE NOT NULL,
    turno_id INT NOT NULL,
    responsavel_nome VARCHAR(160) NULL,
    observacao_grupo TEXT NULL,
    usuario_id INT NOT NULL,
    criado_em DATETIME NOT NULL,
    KEY idx_res_tem_grupo_data_rest_turno (data_reserva, restaurante_id, turno_id),
    CONSTRAINT fk_res_tem_grupo_rest FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id),
    CONSTRAINT fk_res_tem_grupo_turno FOREIGN KEY (turno_id) REFERENCES reservas_tematicas_turnos(id),
    CONSTRAINT fk_res_tem_grupo_user FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @has_col_grupo_id := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'reservas_tematicas'
      AND column_name = 'grupo_id'
);
SET @sql_col_grupo_id := IF(@has_col_grupo_id = 0,
    'ALTER TABLE reservas_tematicas ADD COLUMN grupo_id INT NULL AFTER id',
    'SELECT 1'
);
PREPARE st_col_grupo_id FROM @sql_col_grupo_id;
EXECUTE st_col_grupo_id;
DEALLOCATE PREPARE st_col_grupo_id;

SET @has_col_pax_adulto := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'reservas_tematicas'
      AND column_name = 'pax_adulto'
);
SET @sql_col_pax_adulto := IF(@has_col_pax_adulto = 0,
    'ALTER TABLE reservas_tematicas ADD COLUMN pax_adulto INT NOT NULL DEFAULT 0 AFTER pax',
    'SELECT 1'
);
PREPARE st_col_pax_adulto FROM @sql_col_pax_adulto;
EXECUTE st_col_pax_adulto;
DEALLOCATE PREPARE st_col_pax_adulto;

SET @has_col_pax_chd := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'reservas_tematicas'
      AND column_name = 'pax_chd'
);
SET @sql_col_pax_chd := IF(@has_col_pax_chd = 0,
    'ALTER TABLE reservas_tematicas ADD COLUMN pax_chd INT NOT NULL DEFAULT 0 AFTER pax_adulto',
    'SELECT 1'
);
PREPARE st_col_pax_chd FROM @sql_col_pax_chd;
EXECUTE st_col_pax_chd;
DEALLOCATE PREPARE st_col_pax_chd;

SET @has_col_qtd_chd := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'reservas_tematicas'
      AND column_name = 'qtd_chd'
);
SET @sql_col_qtd_chd := IF(@has_col_qtd_chd = 0,
    'ALTER TABLE reservas_tematicas ADD COLUMN qtd_chd INT NOT NULL DEFAULT 0 AFTER pax_chd',
    'SELECT 1'
);
PREPARE st_col_qtd_chd FROM @sql_col_qtd_chd;
EXECUTE st_col_qtd_chd;
DEALLOCATE PREPARE st_col_qtd_chd;

UPDATE reservas_tematicas
SET pax_adulto = CASE WHEN pax > 0 THEN pax ELSE 0 END
WHERE pax_adulto = 0 AND pax_chd = 0 AND qtd_chd = 0;

SET @has_fk_grupo := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE()
      AND table_name = 'reservas_tematicas'
      AND constraint_name = 'fk_res_tem_grupo'
);
SET @sql_fk_grupo := IF(@has_fk_grupo = 0,
    'ALTER TABLE reservas_tematicas ADD CONSTRAINT fk_res_tem_grupo FOREIGN KEY (grupo_id) REFERENCES reservas_tematicas_grupos(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE st_fk_grupo FROM @sql_fk_grupo;
EXECUTE st_fk_grupo;
DEALLOCATE PREPARE st_fk_grupo;

CREATE TABLE IF NOT EXISTS reservas_tematicas_chd (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reserva_id INT NOT NULL,
    idade TINYINT UNSIGNED NOT NULL,
    criado_em DATETIME NOT NULL,
    KEY idx_res_tem_chd_reserva (reserva_id),
    CONSTRAINT fk_res_tem_chd_reserva FOREIGN KEY (reserva_id) REFERENCES reservas_tematicas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @has_idx_status := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'reservas_tematicas'
      AND index_name = 'idx_res_tem_status'
);
SET @sql_idx_status := IF(@has_idx_status = 0,
    'ALTER TABLE reservas_tematicas ADD INDEX idx_res_tem_status (status)',
    'SELECT 1'
);
PREPARE st_idx_status FROM @sql_idx_status;
EXECUTE st_idx_status;
DEALLOCATE PREPARE st_idx_status;

SET @has_idx_grupo := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'reservas_tematicas'
      AND index_name = 'idx_res_tem_grupo'
);
SET @sql_idx_grupo := IF(@has_idx_grupo = 0,
    'ALTER TABLE reservas_tematicas ADD INDEX idx_res_tem_grupo (grupo_id)',
    'SELECT 1'
);
PREPARE st_idx_grupo FROM @sql_idx_grupo;
EXECUTE st_idx_grupo;
DEALLOCATE PREPARE st_idx_grupo;
