-- Módulo Reservas Temáticas (sem alterar base principal)

CREATE TABLE IF NOT EXISTS reservas_tematicas_turnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hora TIME NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    ordem INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS reservas_tematicas_periodos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    ordem INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS reservas_tematicas_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT NOT NULL,
    capacidade_total INT NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_res_tem_config_rest (restaurante_id),
    CONSTRAINT fk_res_tem_config_rest FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id)
);

CREATE TABLE IF NOT EXISTS reservas_tematicas_config_turnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT NOT NULL,
    turno_id INT NOT NULL,
    capacidade INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_res_tem_cfg_turno (restaurante_id, turno_id),
    CONSTRAINT fk_res_tem_cfg_turno_rest FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id),
    CONSTRAINT fk_res_tem_cfg_turno_turno FOREIGN KEY (turno_id) REFERENCES reservas_tematicas_turnos(id)
);

CREATE TABLE IF NOT EXISTS reservas_tematicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT NOT NULL,
    data_reserva DATE NOT NULL,
    turno_id INT NOT NULL,
    uh_id INT NOT NULL,
    pax INT NOT NULL,
    pax_real INT NULL,
    observacao_reserva TEXT NULL,
    observacao_tags TEXT NULL,
    observacao_operacao TEXT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'Reservada',
    excedente TINYINT(1) NOT NULL DEFAULT 0,
    excedente_motivo VARCHAR(255) NULL,
    excedente_autor_id INT NULL,
    excedente_em DATETIME NULL,
    usuario_id INT NOT NULL,
    atualizado_por INT NULL,
    criado_em DATETIME NOT NULL,
    atualizado_em DATETIME NULL,
    INDEX idx_res_tem_data_rest_turno (data_reserva, restaurante_id, turno_id),
    INDEX idx_res_tem_uh (uh_id),
    CONSTRAINT fk_res_tem_rest FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id),
    CONSTRAINT fk_res_tem_turno FOREIGN KEY (turno_id) REFERENCES reservas_tematicas_turnos(id),
    CONSTRAINT fk_res_tem_uh FOREIGN KEY (uh_id) REFERENCES unidades_habitacionais(id),
    CONSTRAINT fk_res_tem_user FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    CONSTRAINT fk_res_tem_user_upd FOREIGN KEY (atualizado_por) REFERENCES usuarios(id),
    CONSTRAINT fk_res_tem_exc_user FOREIGN KEY (excedente_autor_id) REFERENCES usuarios(id)
);

CREATE TABLE IF NOT EXISTS reservas_tematicas_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reserva_id INT NOT NULL,
    acao VARCHAR(60) NOT NULL,
    usuario_id INT NOT NULL,
    dados_antes JSON NULL,
    dados_depois JSON NULL,
    justificativa VARCHAR(255) NULL,
    criado_em DATETIME NOT NULL,
    CONSTRAINT fk_res_tem_log_reserva FOREIGN KEY (reserva_id) REFERENCES reservas_tematicas(id),
    CONSTRAINT fk_res_tem_log_user FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE IF NOT EXISTS reservas_tematicas_fechamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT NOT NULL,
    data_reserva DATE NOT NULL,
    turno_id INT NOT NULL,
    fechado_em DATETIME NOT NULL,
    usuario_id INT NOT NULL,
    UNIQUE KEY uq_res_tem_fech (restaurante_id, data_reserva, turno_id),
    CONSTRAINT fk_res_tem_fech_rest FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id),
    CONSTRAINT fk_res_tem_fech_turno FOREIGN KEY (turno_id) REFERENCES reservas_tematicas_turnos(id),
    CONSTRAINT fk_res_tem_fech_user FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Seed de turnos (19:00 a 21:00)
INSERT INTO reservas_tematicas_turnos (hora, ativo, ordem) VALUES
('19:00:00', 1, 1),
('19:30:00', 1, 2),
('20:00:00', 1, 3),
('20:30:00', 1, 4),
('21:00:00', 1, 5);

-- Seed de períodos de reserva (08:30-12:00, 13:00-16:30)
INSERT INTO reservas_tematicas_periodos (hora_inicio, hora_fim, ativo, ordem) VALUES
('08:30:00', '12:00:00', 1, 1),
('13:00:00', '16:30:00', 1, 2);

-- Configuração inicial de capacidade total por restaurante (ajustável)
INSERT INTO reservas_tematicas_config (restaurante_id, capacidade_total, ativo) VALUES
((SELECT id FROM restaurantes WHERE nome = 'Restaurante Giardino'), 130, 1),
((SELECT id FROM restaurantes WHERE nome = 'Restaurante La Brasa'), 130, 1),
((SELECT id FROM restaurantes WHERE nome = 'Restaurante IX''u'), 80, 1);

-- Distribuição inicial por turno (ajustável)
INSERT INTO reservas_tematicas_config_turnos (restaurante_id, turno_id, capacidade) VALUES
((SELECT id FROM restaurantes WHERE nome = 'Restaurante Giardino'), 1, 26),
((SELECT id FROM restaurantes WHERE nome = 'Restaurante Giardino'), 2, 26),
((SELECT id FROM restaurantes WHERE nome = 'Restaurante Giardino'), 3, 26),
((SELECT id FROM restaurantes WHERE nome = 'Restaurante Giardino'), 4, 26),
((SELECT id FROM restaurantes WHERE nome = 'Restaurante Giardino'), 5, 26),

((SELECT id FROM restaurantes WHERE nome = 'Restaurante La Brasa'), 1, 26),
((SELECT id FROM restaurantes WHERE nome = 'Restaurante La Brasa'), 2, 26),
((SELECT id FROM restaurantes WHERE nome = 'Restaurante La Brasa'), 3, 26),
((SELECT id FROM restaurantes WHERE nome = 'Restaurante La Brasa'), 4, 26),
((SELECT id FROM restaurantes WHERE nome = 'Restaurante La Brasa'), 5, 26),

((SELECT id FROM restaurantes WHERE nome = 'Restaurante IX''u'), 1, 16),
((SELECT id FROM restaurantes WHERE nome = 'Restaurante IX''u'), 2, 16),
((SELECT id FROM restaurantes WHERE nome = 'Restaurante IX''u'), 3, 16),
((SELECT id FROM restaurantes WHERE nome = 'Restaurante IX''u'), 4, 16),
((SELECT id FROM restaurantes WHERE nome = 'Restaurante IX''u'), 5, 16);
