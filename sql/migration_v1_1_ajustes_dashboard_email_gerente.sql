-- OCA FBControl v1.1 - Ajustes de dashboard, perfil gerente e e-mail com anexo de vouchers

-- 1) Permite perfil gerente
ALTER TABLE usuarios
    MODIFY perfil ENUM('hostess', 'gerente', 'supervisor', 'admin') NOT NULL DEFAULT 'hostess';

-- 2) Normaliza operação temática para evitar duplicidade "Tematico" x "Temático"
UPDATE operacoes
   SET nome = 'Temático'
 WHERE LOWER(nome) IN ('tematico', 'temático');

-- 3) Destinatários do e-mail diário podem receber anexo de vouchers
ALTER TABLE relatorio_email_destinatarios
    ADD COLUMN IF NOT EXISTS receber_anexo_vouchers TINYINT(1) NOT NULL DEFAULT 0 AFTER ativo;

