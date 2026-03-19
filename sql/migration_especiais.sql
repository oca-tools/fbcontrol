-- MIGRATION: separar buffet x especiais (temáticos e privileged)
-- 1) criar novas tabelas
CREATE TABLE IF NOT EXISTS restaurante_especiais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT NOT NULL,
    tipo ENUM('tematico','privileged') NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    tolerancia_min INT NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_rest_esp_rest FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id)
);

CREATE TABLE IF NOT EXISTS turnos_especiais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    restaurante_id INT NOT NULL,
    tipo ENUM('tematico','privileged') NOT NULL,
    porta_id INT NULL,
    inicio_em DATETIME NOT NULL,
    fim_em DATETIME NULL,
    CONSTRAINT fk_turnos_esp_user FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    CONSTRAINT fk_turnos_esp_rest FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id),
    CONSTRAINT fk_turnos_esp_porta FOREIGN KEY (porta_id) REFERENCES portas(id)
);

CREATE TABLE IF NOT EXISTS acessos_especiais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turno_especial_id INT NULL,
    uh_id INT NOT NULL,
    pax INT NOT NULL DEFAULT 1,
    restaurante_id INT NOT NULL,
    porta_id INT NULL,
    tipo ENUM('tematico','privileged') NOT NULL,
    alerta_duplicidade TINYINT(1) NOT NULL DEFAULT 0,
    fora_do_horario TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL,
    usuario_id INT NOT NULL,
    CONSTRAINT fk_acessos_esp_turno FOREIGN KEY (turno_especial_id) REFERENCES turnos_especiais(id),
    CONSTRAINT fk_acessos_esp_uh FOREIGN KEY (uh_id) REFERENCES unidades_habitacionais(id),
    CONSTRAINT fk_acessos_esp_rest FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id),
    CONSTRAINT fk_acessos_esp_porta FOREIGN KEY (porta_id) REFERENCES portas(id),
    CONSTRAINT fk_acessos_esp_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE INDEX idx_acessos_esp_uh_tipo_time ON acessos_especiais (uh_id, tipo, criado_em);
CREATE INDEX idx_acessos_esp_data ON acessos_especiais (criado_em);
CREATE INDEX idx_acessos_esp_rest_tipo ON acessos_especiais (restaurante_id, tipo);

-- 2) migrar dados atuais de temático/privileged para especiais
INSERT INTO acessos_especiais (turno_especial_id, uh_id, pax, restaurante_id, porta_id, tipo, alerta_duplicidade, fora_do_horario, criado_em, usuario_id)
SELECT NULL, a.uh_id, a.pax, a.restaurante_id, a.porta_id,
       CASE WHEN o.nome IN ('Tematico','Temático') THEN 'tematico' ELSE 'privileged' END AS tipo,
       a.alerta_duplicidade, a.fora_do_horario, a.criado_em, a.usuario_id
FROM acessos a
JOIN operacoes o ON o.id = a.operacao_id
WHERE o.nome IN ('Tematico','Temático','Privileged');

-- 3) opcional: limpar acessos antigos temático/privileged (descomentar se desejar)
-- DELETE a FROM acessos a
-- JOIN operacoes o ON o.id = a.operacao_id
-- WHERE o.nome IN ('Tematico','Temático','Privileged');

-- 4) opcional: remover operações especiais do cadastro de operações (descomentar se desejar)
-- DELETE FROM operacoes WHERE nome IN ('Tematico','Temático','Privileged');

-- 5) configurar restaurantes especiais iniciais
INSERT INTO restaurante_especiais (restaurante_id, tipo, hora_inicio, hora_fim, tolerancia_min, ativo) VALUES
((SELECT id FROM restaurantes WHERE nome = 'Restaurante IX''u'), 'tematico', '19:00:00', '22:00:00', 0, 1),
((SELECT id FROM restaurantes WHERE nome = 'Restaurante Giardino'), 'tematico', '19:00:00', '22:00:00', 0, 1),
((SELECT id FROM restaurantes WHERE nome = 'Restaurante La Brasa'), 'tematico', '19:00:00', '22:00:00', 0, 1),
((SELECT id FROM restaurantes WHERE nome = 'Privileged'), 'privileged', '00:00:00', '23:59:00', 0, 1);
