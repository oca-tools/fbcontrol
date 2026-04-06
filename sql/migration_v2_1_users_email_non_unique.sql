-- Permite mais de um usuario com o mesmo e-mail.
-- Regra de negocio passa a validar combinacao e-mail + senha.

SET @schema_name := DATABASE();

SET @has_unique_email := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = @schema_name
      AND table_name = 'usuarios'
      AND index_name = 'email'
      AND non_unique = 0
);
SET @sql_drop_unique := IF(@has_unique_email > 0, 'ALTER TABLE usuarios DROP INDEX `email`', 'SELECT 1');
PREPARE stmt_drop_unique FROM @sql_drop_unique;
EXECUTE stmt_drop_unique;
DEALLOCATE PREPARE stmt_drop_unique;

SET @has_idx_email := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = @schema_name
      AND table_name = 'usuarios'
      AND index_name = 'idx_usuarios_email'
);
SET @sql_add_index := IF(@has_idx_email = 0, 'ALTER TABLE usuarios ADD INDEX `idx_usuarios_email` (`email`)', 'SELECT 1');
PREPARE stmt_add_index FROM @sql_add_index;
EXECUTE stmt_add_index;
DEALLOCATE PREPARE stmt_add_index;
