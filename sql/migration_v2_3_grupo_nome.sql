-- v2.3 - Grupos nomeados para reservas temáticas

SET @db_name := DATABASE();

SELECT COUNT(*) INTO @has_grupo_nome_col
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db_name
  AND TABLE_NAME = 'reservas_tematicas'
  AND COLUMN_NAME = 'grupo_nome';

SET @sql := IF(
  @has_grupo_nome_col = 0,
  'ALTER TABLE reservas_tematicas ADD COLUMN grupo_nome VARCHAR(120) NULL AFTER titular_nome',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @has_grupo_nome_idx
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = @db_name
  AND TABLE_NAME = 'reservas_tematicas'
  AND INDEX_NAME = 'idx_reservas_tematicas_grupo_nome';

SET @sql := IF(
  @has_grupo_nome_idx = 0,
  'CREATE INDEX idx_reservas_tematicas_grupo_nome ON reservas_tematicas (grupo_nome)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @has_grupos_table
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = @db_name
  AND TABLE_NAME = 'reservas_tematicas_grupos';

SET @sql := IF(
  @has_grupos_table > 0,
  'UPDATE reservas_tematicas rsv
     LEFT JOIN reservas_tematicas_grupos grp ON grp.id = rsv.grupo_id
     SET rsv.grupo_nome = COALESCE(NULLIF(TRIM(rsv.grupo_nome), ''''), NULLIF(TRIM(grp.responsavel_nome), ''''))
   WHERE (rsv.grupo_nome IS NULL OR TRIM(rsv.grupo_nome) = '''')
     AND rsv.grupo_id IS NOT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
