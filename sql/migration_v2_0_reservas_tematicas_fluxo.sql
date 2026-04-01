-- OCA FBControl v2.0 - Reservas Tematicas (titular + auto no-show + simplificacao de status)
-- Execute no banco correto:
-- USE controle_ab;

-- 1) Coluna do titular na reserva tematica
SET @has_col_titular := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'reservas_tematicas'
      AND COLUMN_NAME = 'titular_nome'
);
SET @sql_titular := IF(
    @has_col_titular = 0,
    'ALTER TABLE reservas_tematicas ADD COLUMN titular_nome VARCHAR(180) NULL AFTER uh_id',
    'SELECT 1'
);
PREPARE stmt_titular FROM @sql_titular;
EXECUTE stmt_titular;
DEALLOCATE PREPARE stmt_titular;

-- 2) Indice para busca por titular
SET @has_idx_titular := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'reservas_tematicas'
      AND INDEX_NAME = 'idx_res_tem_titular'
);
SET @sql_idx_titular := IF(
    @has_idx_titular = 0,
    'ALTER TABLE reservas_tematicas ADD INDEX idx_res_tem_titular (titular_nome)',
    'SELECT 1'
);
PREPARE stmt_idx_titular FROM @sql_idx_titular;
EXECUTE stmt_idx_titular;
DEALLOCATE PREPARE stmt_idx_titular;

-- 3) Coluna de auto no-show por restaurante tematico (minutos apos horario do turno)
SET @has_col_auto_no_show := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'reservas_tematicas_config'
      AND COLUMN_NAME = 'auto_cancel_no_show_min'
);
SET @sql_auto_no_show := IF(
    @has_col_auto_no_show = 0,
    'ALTER TABLE reservas_tematicas_config ADD COLUMN auto_cancel_no_show_min INT NOT NULL DEFAULT 0 AFTER capacidade_total',
    'SELECT 1'
);
PREPARE stmt_auto_no_show FROM @sql_auto_no_show;
EXECUTE stmt_auto_no_show;
DEALLOCATE PREPARE stmt_auto_no_show;

-- 4) Normalizacao de status legado para formato canonico da operacao
UPDATE reservas_tematicas
SET status = 'Nao compareceu'
WHERE status IN ('Não compareceu', 'NÃ£o compareceu', 'NÃƒÂ£o compareceu');

UPDATE reservas_tematicas
SET status = 'Divergencia'
WHERE status IN ('Divergência', 'DivergÃªncia', 'DivergÃƒÂªncia');

UPDATE reservas_tematicas
SET status = 'Reservada'
WHERE status IN ('Conferida', 'Em atendimento');

