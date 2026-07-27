-- Garante que um reenvio apos queda de conexao devolva a mesma reserva,
-- sem criar uma segunda reserva para a mesma tentativa do operador.
ALTER TABLE reservas_tematicas
    ADD COLUMN IF NOT EXISTS correlation_id VARCHAR(80) NULL AFTER grupo_nome,
    ADD COLUMN IF NOT EXISTS correlation_item SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER correlation_id;

SET @index_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'reservas_tematicas'
      AND index_name = 'uq_res_tem_correlation_item'
);
SET @index_sql := IF(
    @index_exists = 0,
    'ALTER TABLE reservas_tematicas ADD UNIQUE KEY uq_res_tem_correlation_item (usuario_id, correlation_id, correlation_item)',
    'SELECT 1'
);
PREPARE migration_statement FROM @index_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;
