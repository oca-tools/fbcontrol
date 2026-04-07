-- v2.3 - Garante coluna titular_nome para reservas temáticas

SET @db_name := DATABASE();

SELECT COUNT(*) INTO @has_titular_nome_col
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db_name
  AND TABLE_NAME = 'reservas_tematicas'
  AND COLUMN_NAME = 'titular_nome';

SET @sql := IF(
  @has_titular_nome_col = 0,
  'ALTER TABLE reservas_tematicas ADD COLUMN titular_nome VARCHAR(160) NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @has_grupos_table
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = @db_name
  AND TABLE_NAME = 'reservas_tematicas_grupos';

SELECT COUNT(*) INTO @has_grupo_id_col
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db_name
  AND TABLE_NAME = 'reservas_tematicas'
  AND COLUMN_NAME = 'grupo_id';

SELECT COUNT(*) INTO @has_responsavel_nome_col
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db_name
  AND TABLE_NAME = 'reservas_tematicas_grupos'
  AND COLUMN_NAME = 'responsavel_nome';

SET @sql := IF(
  @has_grupos_table > 0 AND @has_grupo_id_col > 0 AND @has_responsavel_nome_col > 0,
  'UPDATE reservas_tematicas rsv
     LEFT JOIN reservas_tematicas_grupos grp ON grp.id = rsv.grupo_id
     SET rsv.titular_nome = COALESCE(NULLIF(TRIM(rsv.titular_nome), ''''), NULLIF(TRIM(grp.responsavel_nome), ''''))
   WHERE rsv.titular_nome IS NULL OR TRIM(rsv.titular_nome) = ''''',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SHOW COLUMNS FROM reservas_tematicas LIKE 'titular_nome';
