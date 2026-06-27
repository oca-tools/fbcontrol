ALTER TABLE reservas_tematicas_bloqueios_datas
    ADD COLUMN IF NOT EXISTS modo ENUM('fechado', 'aberto') NOT NULL DEFAULT 'fechado' AFTER ativo;

UPDATE reservas_tematicas_bloqueios_datas
SET modo = 'fechado'
WHERE modo IS NULL OR modo = '';
