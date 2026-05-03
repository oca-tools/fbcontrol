-- v2.4 - Configuracao de tolerancia para no-show automatico em reservas tematicas
-- Idempotente: pode ser executada mais de uma vez.

SET @has_auto_no_show_col := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'reservas_tematicas_config'
      AND COLUMN_NAME = 'auto_cancel_no_show_min'
);

SET @sql_auto_no_show_col := IF(
    @has_auto_no_show_col = 0,
    'ALTER TABLE reservas_tematicas_config ADD COLUMN auto_cancel_no_show_min INT NOT NULL DEFAULT 0 AFTER ativo',
    'SELECT 1'
);

PREPARE st_auto_no_show_col FROM @sql_auto_no_show_col;
EXECUTE st_auto_no_show_col;
DEALLOCATE PREPARE st_auto_no_show_col;

