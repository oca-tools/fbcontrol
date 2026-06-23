ALTER TABLE reservas_tematicas_chd
    ADD COLUMN IF NOT EXISTS idade_label VARCHAR(8) NULL AFTER idade;

UPDATE reservas_tematicas_chd
SET idade_label = CASE WHEN idade > 0 THEN CONCAT(idade, 'y') ELSE NULL END
WHERE idade_label IS NULL OR TRIM(idade_label) = '';
