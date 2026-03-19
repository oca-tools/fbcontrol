CREATE DATABASE IF NOT EXISTS controle_ab CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE controle_ab;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    foto_path VARCHAR(255) NULL,
    perfil ENUM('hostess', 'supervisor', 'admin') NOT NULL DEFAULT 'hostess',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL
);

CREATE TABLE restaurantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    tipo ENUM('buffet', 'tematico', 'area') NOT NULL DEFAULT 'buffet',
    seleciona_porta_no_turno TINYINT(1) NOT NULL DEFAULT 0,
    exige_pax TINYINT(1) NOT NULL DEFAULT 1,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL
);

CREATE TABLE portas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT NOT NULL,
    nome VARCHAR(120) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL,
    CONSTRAINT fk_portas_rest FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id)
);

CREATE TABLE operacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL
);

CREATE TABLE restaurante_operacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT NOT NULL,
    operacao_id INT NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    tolerancia_min INT NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_rest_oper_rest FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id),
    CONSTRAINT fk_rest_oper_oper FOREIGN KEY (operacao_id) REFERENCES operacoes(id)
);

CREATE TABLE usuarios_restaurantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    restaurante_id INT NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL,
    CONSTRAINT fk_usr_rest_user FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    CONSTRAINT fk_usr_rest_rest FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id)
);

CREATE TABLE unidades_habitacionais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) NOT NULL UNIQUE,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL
);

INSERT IGNORE INTO unidades_habitacionais (numero, ativo, criado_em) VALUES
('101', 1, NOW()),('102', 1, NOW()),('103', 1, NOW()),('104', 1, NOW()),('105', 1, NOW()),('106', 1, NOW()),
('107', 1, NOW()),('108', 1, NOW()),('109', 1, NOW()),('110', 1, NOW()),('111', 1, NOW()),('112', 1, NOW()),
('113', 1, NOW()),('114', 1, NOW()),('115', 1, NOW()),('116', 1, NOW()),('117', 1, NOW()),('118', 1, NOW()),
('119', 1, NOW()),('120', 1, NOW()),('121', 1, NOW()),('122', 1, NOW()),('123', 1, NOW()),('124', 1, NOW()),
('125', 1, NOW()),('126', 1, NOW()),('127', 1, NOW()),('128', 1, NOW()),('129', 1, NOW()),('130', 1, NOW()),
('131', 1, NOW()),('132', 1, NOW()),('133', 1, NOW()),('134', 1, NOW()),('135', 1, NOW()),('136', 1, NOW()),
('137', 1, NOW()),('138', 1, NOW()),('139', 1, NOW()),('140', 1, NOW()),('141', 1, NOW()),('142', 1, NOW()),
('143', 1, NOW()),('144', 1, NOW()),('145', 1, NOW()),('146', 1, NOW()),('147', 1, NOW()),('148', 1, NOW()),
('149', 1, NOW()),('150', 1, NOW()),('151', 1, NOW()),
('201', 1, NOW()),('202', 1, NOW()),('203', 1, NOW()),('204', 1, NOW()),('205', 1, NOW()),('206', 1, NOW()),
('207', 1, NOW()),('208', 1, NOW()),('209', 1, NOW()),('210', 1, NOW()),('211', 1, NOW()),('212', 1, NOW()),
('213', 1, NOW()),('214', 1, NOW()),('215', 1, NOW()),('216', 1, NOW()),('217', 1, NOW()),('218', 1, NOW()),
('219', 1, NOW()),('220', 1, NOW()),('221', 1, NOW()),('222', 1, NOW()),('223', 1, NOW()),('224', 1, NOW()),
('225', 1, NOW()),('226', 1, NOW()),('227', 1, NOW()),('228', 1, NOW()),('229', 1, NOW()),('230', 1, NOW()),
('231', 1, NOW()),('232', 1, NOW()),('233', 1, NOW()),('234', 1, NOW()),('235', 1, NOW()),('236', 1, NOW()),
('237', 1, NOW()),('238', 1, NOW()),('239', 1, NOW()),('240', 1, NOW()),('241', 1, NOW()),('242', 1, NOW()),
('243', 1, NOW()),('244', 1, NOW()),('245', 1, NOW()),('246', 1, NOW()),('247', 1, NOW()),
('300', 1, NOW()),('301', 1, NOW()),('302', 1, NOW()),('303', 1, NOW()),('304', 1, NOW()),('305', 1, NOW()),
('306', 1, NOW()),('307', 1, NOW()),('308', 1, NOW()),('309', 1, NOW()),('310', 1, NOW()),('311', 1, NOW()),
('312', 1, NOW()),('313', 1, NOW()),('314', 1, NOW()),('315', 1, NOW()),('316', 1, NOW()),('317', 1, NOW()),
('318', 1, NOW()),('319', 1, NOW()),
('400', 1, NOW()),('401', 1, NOW()),('402', 1, NOW()),('403', 1, NOW()),('404', 1, NOW()),('405', 1, NOW()),
('406', 1, NOW()),('407', 1, NOW()),('408', 1, NOW()),('409', 1, NOW()),('410', 1, NOW()),('411', 1, NOW()),
('412', 1, NOW()),('413', 1, NOW()),('414', 1, NOW()),('415', 1, NOW()),('416', 1, NOW()),('417', 1, NOW()),
('418', 1, NOW()),('419', 1, NOW()),
('500', 1, NOW()),('501', 1, NOW()),('502', 1, NOW()),('503', 1, NOW()),('504', 1, NOW()),('505', 1, NOW()),
('506', 1, NOW()),('507', 1, NOW()),('508', 1, NOW()),('509', 1, NOW()),('510', 1, NOW()),('511', 1, NOW()),
('512', 1, NOW()),('513', 1, NOW()),('514', 1, NOW()),('515', 1, NOW()),('516', 1, NOW()),('517', 1, NOW()),
('518', 1, NOW()),('519', 1, NOW()),
('600', 1, NOW()),('601', 1, NOW()),('602', 1, NOW()),('603', 1, NOW()),('604', 1, NOW()),('605', 1, NOW()),
('606', 1, NOW()),('607', 1, NOW()),('608', 1, NOW()),('609', 1, NOW()),('610', 1, NOW()),('611', 1, NOW()),
('612', 1, NOW()),('613', 1, NOW()),('614', 1, NOW()),('615', 1, NOW()),('616', 1, NOW()),('617', 1, NOW()),
('618', 1, NOW()),('619', 1, NOW()),
('700', 1, NOW()),('701', 1, NOW()),('702', 1, NOW()),('703', 1, NOW()),('704', 1, NOW()),('705', 1, NOW()),
('706', 1, NOW()),('707', 1, NOW()),('708', 1, NOW()),('709', 1, NOW()),('710', 1, NOW()),('711', 1, NOW()),
('712', 1, NOW()),('713', 1, NOW()),('714', 1, NOW()),('715', 1, NOW()),('716', 1, NOW()),('717', 1, NOW()),
('718', 1, NOW()),('719', 1, NOW()),
('800', 1, NOW()),('801', 1, NOW()),('802', 1, NOW()),('803', 1, NOW()),('804', 1, NOW()),('805', 1, NOW()),
('806', 1, NOW()),('807', 1, NOW()),('808', 1, NOW()),('809', 1, NOW()),('810', 1, NOW()),('811', 1, NOW()),
('812', 1, NOW()),('813', 1, NOW()),('814', 1, NOW()),('815', 1, NOW()),('816', 1, NOW()),('817', 1, NOW()),
('818', 1, NOW()),('819', 1, NOW()),
('900', 1, NOW()),('901', 1, NOW()),('902', 1, NOW()),('903', 1, NOW()),('904', 1, NOW()),('905', 1, NOW()),
('906', 1, NOW()),('907', 1, NOW()),('908', 1, NOW()),('909', 1, NOW()),('910', 1, NOW()),('911', 1, NOW()),
('912', 1, NOW()),('913', 1, NOW()),('914', 1, NOW()),('915', 1, NOW()),('916', 1, NOW()),('917', 1, NOW()),
('918', 1, NOW()),('919', 1, NOW()),
('1000', 1, NOW()),('1001', 1, NOW()),('1002', 1, NOW()),('1003', 1, NOW()),('1004', 1, NOW()),('1005', 1, NOW()),
('1006', 1, NOW()),('1007', 1, NOW()),('1008', 1, NOW()),('1009', 1, NOW()),('1010', 1, NOW()),('1011', 1, NOW()),
('1012', 1, NOW()),('1013', 1, NOW()),('1014', 1, NOW()),('1015', 1, NOW()),('1016', 1, NOW()),('1017', 1, NOW()),
('1018', 1, NOW()),('1019', 1, NOW()),
('1101', 1, NOW()),('1102', 1, NOW()),('1103', 1, NOW()),('1104', 1, NOW()),('1105', 1, NOW()),('1106', 1, NOW()),
('1107', 1, NOW()),('1108', 1, NOW()),('1109', 1, NOW()),('1110', 1, NOW()),('1111', 1, NOW()),('1112', 1, NOW()),
('2100', 1, NOW()),('2101', 1, NOW()),('2102', 1, NOW()),('2103', 1, NOW()),('2104', 1, NOW()),('2105', 1, NOW()),
('2106', 1, NOW()),('2107', 1, NOW()),('2108', 1, NOW()),('2109', 1, NOW()),
('2200', 1, NOW()),('2201', 1, NOW()),('2202', 1, NOW()),('2203', 1, NOW()),('2204', 1, NOW()),('2205', 1, NOW()),
('2206', 1, NOW()),('2207', 1, NOW()),('2208', 1, NOW()),('2209', 1, NOW()),
('2300', 1, NOW()),('2301', 1, NOW()),('2302', 1, NOW()),('2303', 1, NOW()),('2304', 1, NOW()),('2305', 1, NOW()),
('2306', 1, NOW()),('2307', 1, NOW()),('2308', 1, NOW()),('2309', 1, NOW()),
('3100', 1, NOW()),('3101', 1, NOW()),('3102', 1, NOW()),('3103', 1, NOW()),('3104', 1, NOW()),('3105', 1, NOW()),
('3106', 1, NOW()),('3107', 1, NOW()),('3108', 1, NOW()),('3109', 1, NOW()),
('3200', 1, NOW()),('3201', 1, NOW()),('3202', 1, NOW()),('3203', 1, NOW()),('3204', 1, NOW()),('3205', 1, NOW()),
('3206', 1, NOW()),('3207', 1, NOW()),('3208', 1, NOW()),('3209', 1, NOW()),
('3300', 1, NOW()),('3301', 1, NOW()),('3302', 1, NOW()),('3303', 1, NOW()),('3304', 1, NOW()),('3305', 1, NOW()),
('3306', 1, NOW()),('3307', 1, NOW()),('3308', 1, NOW()),('3309', 1, NOW()),
('4000', 1, NOW()),('4001', 1, NOW()),('4002', 1, NOW()),('4003', 1, NOW()),('4004', 1, NOW()),('4005', 1, NOW()),
('4006', 1, NOW()),('4007', 1, NOW()),('4008', 1, NOW()),('4009', 1, NOW()),('4010', 1, NOW()),('4011', 1, NOW()),
('4012', 1, NOW()),('4013', 1, NOW()),('4014', 1, NOW()),('4015', 1, NOW()),('4016', 1, NOW()),('4017', 1, NOW()),
('4018', 1, NOW()),('4019', 1, NOW()),('4020', 1, NOW()),('4021', 1, NOW()),('4022', 1, NOW()),
('4100', 1, NOW()),('4101', 1, NOW()),('4102', 1, NOW()),('4103', 1, NOW()),('4104', 1, NOW()),('4105', 1, NOW()),
('4106', 1, NOW()),('4107', 1, NOW()),('4108', 1, NOW()),('4109', 1, NOW()),('4110', 1, NOW()),('4111', 1, NOW()),
('4112', 1, NOW()),('4113', 1, NOW()),('4114', 1, NOW()),('4115', 1, NOW()),('4116', 1, NOW()),('4117', 1, NOW()),
('4118', 1, NOW()),('4119', 1, NOW()),('4120', 1, NOW()),('4121', 1, NOW()),('4122', 1, NOW()),
('4200', 1, NOW()),('4201', 1, NOW()),('4202', 1, NOW()),('4203', 1, NOW()),('4204', 1, NOW()),('4205', 1, NOW()),
('4206', 1, NOW()),('4207', 1, NOW()),('4208', 1, NOW()),('4209', 1, NOW()),('4210', 1, NOW()),('4211', 1, NOW()),
('4212', 1, NOW()),('4213', 1, NOW()),('4214', 1, NOW()),('4215', 1, NOW()),('4216', 1, NOW()),('4217', 1, NOW()),
('4218', 1, NOW()),('4219', 1, NOW()),('4220', 1, NOW()),('4221', 1, NOW()),('4222', 1, NOW()),
('4300', 1, NOW()),('4301', 1, NOW()),('4302', 1, NOW()),('4303', 1, NOW()),('4304', 1, NOW()),('4305', 1, NOW()),
('4306', 1, NOW()),('4307', 1, NOW()),('4308', 1, NOW()),('4309', 1, NOW()),('4310', 1, NOW()),('4311', 1, NOW()),
('4312', 1, NOW()),('4313', 1, NOW()),('4314', 1, NOW()),('4315', 1, NOW()),('4316', 1, NOW()),('4317', 1, NOW()),
('4318', 1, NOW()),('4319', 1, NOW()),('4320', 1, NOW()),('4321', 1, NOW()),('4322', 1, NOW());


CREATE TABLE restaurante_especiais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT NOT NULL,
    tipo ENUM('tematico','privileged') NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    tolerancia_min INT NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_rest_esp_rest FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id)
);

CREATE TABLE turnos_especiais (
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

CREATE TABLE acessos_especiais (
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
CREATE TABLE turnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    restaurante_id INT NOT NULL,
    operacao_id INT NOT NULL,
    porta_id INT NULL,
    inicio_em DATETIME NOT NULL,
    fim_em DATETIME NULL,
    CONSTRAINT fk_turnos_user FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    CONSTRAINT fk_turnos_rest FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id),
    CONSTRAINT fk_turnos_oper FOREIGN KEY (operacao_id) REFERENCES operacoes(id),
    CONSTRAINT fk_turnos_porta FOREIGN KEY (porta_id) REFERENCES portas(id)
);

CREATE TABLE acessos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turno_id INT NULL,
    uh_id INT NOT NULL,
    pax INT NOT NULL DEFAULT 1,
    restaurante_id INT NOT NULL,
    porta_id INT NULL,
    operacao_id INT NOT NULL,
    alerta_duplicidade TINYINT(1) NOT NULL DEFAULT 0,
    fora_do_horario TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL,
    usuario_id INT NOT NULL,
    CONSTRAINT fk_acessos_turno FOREIGN KEY (turno_id) REFERENCES turnos(id),
    CONSTRAINT fk_acessos_uh FOREIGN KEY (uh_id) REFERENCES unidades_habitacionais(id),
    CONSTRAINT fk_acessos_rest FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id),
    CONSTRAINT fk_acessos_porta FOREIGN KEY (porta_id) REFERENCES portas(id),
    CONSTRAINT fk_acessos_operacao FOREIGN KEY (operacao_id) REFERENCES operacoes(id),
    CONSTRAINT fk_acessos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tabela VARCHAR(80) NOT NULL,
    registro_id INT NULL,
    acao VARCHAR(40) NOT NULL,
    usuario_id INT NOT NULL,
    dados_antes JSON NOT NULL,
    dados_depois JSON NOT NULL,
    criado_em DATETIME NOT NULL,
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE INDEX idx_acessos_uh_operacao_time ON acessos (uh_id, operacao_id, criado_em);
CREATE INDEX idx_acessos_data ON acessos (criado_em);
CREATE INDEX idx_acessos_rest_oper ON acessos (restaurante_id, operacao_id);

INSERT INTO operacoes (nome, ativo, criado_em) VALUES
('Cafe', 1, NOW()),
('Almoco', 1, NOW()),
('Jantar', 1, NOW());

INSERT INTO restaurantes (nome, tipo, seleciona_porta_no_turno, exige_pax, ativo, criado_em) VALUES
('Restaurante IX''u', 'tematico', 0, 1, 1, NOW()),
('Restaurante Giardino', 'tematico', 0, 1, 1, NOW()),
('Restaurante La Brasa', 'buffet', 0, 1, 1, NOW()),
('Restaurante Corais', 'buffet', 1, 1, 1, NOW()),
('Privileged', 'area', 0, 0, 1, NOW());

INSERT INTO portas (restaurante_id, nome, ativo, criado_em) VALUES
((SELECT id FROM restaurantes WHERE nome = 'Restaurante Corais'), 'Entrada Principal', 1, NOW()),
((SELECT id FROM restaurantes WHERE nome = 'Restaurante Corais'), 'Entrada Lateral', 1, NOW());

INSERT INTO restaurante_operacoes (restaurante_id, operacao_id, hora_inicio, hora_fim, tolerancia_min, ativo) VALUES
((SELECT id FROM restaurantes WHERE nome = 'Restaurante La Brasa'), (SELECT id FROM operacoes WHERE nome = 'Almoco'), '12:30:00', '14:30:00', 0, 1),
((SELECT id FROM restaurantes WHERE nome = 'Restaurante Corais'), (SELECT id FROM operacoes WHERE nome = 'Cafe'), '07:00:00', '10:00:00', 30, 1),
((SELECT id FROM restaurantes WHERE nome = 'Restaurante Corais'), (SELECT id FROM operacoes WHERE nome = 'Almoco'), '12:30:00', '15:00:00', 30, 1),
((SELECT id FROM restaurantes WHERE nome = 'Restaurante Corais'), (SELECT id FROM operacoes WHERE nome = 'Jantar'), '19:00:00', '22:00:00', 30, 1);

-- Usuario admin inicial (trocar senha apos instalar)
INSERT INTO usuarios (nome, email, senha, perfil, ativo, criado_em) VALUES
('Admin A&B', 'admin@hotel.local', '$2y$10$noW0IVhd2SqOe9CXCGq7H.DeMA6DKU7iWHaDdfSHs6tD02BzzsDMe', 'admin', 1, NOW());

INSERT INTO restaurante_especiais (restaurante_id, tipo, hora_inicio, hora_fim, tolerancia_min, ativo) VALUES
((SELECT id FROM restaurantes WHERE nome = 'Restaurante IX''u'), 'tematico', '19:00:00', '22:00:00', 0, 1),
((SELECT id FROM restaurantes WHERE nome = 'Restaurante Giardino'), 'tematico', '19:00:00', '22:00:00', 0, 1),
((SELECT id FROM restaurantes WHERE nome = 'Restaurante La Brasa'), 'tematico', '19:00:00', '22:00:00', 0, 1),
((SELECT id FROM restaurantes WHERE nome = 'Privileged'), 'privileged', '00:00:00', '23:59:00', 0, 1);
CREATE TABLE colaborador_refeicoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turno_id INT NULL,
    restaurante_id INT NOT NULL,
    operacao_id INT NOT NULL,
    nome_colaborador VARCHAR(160) NOT NULL,
    quantidade INT NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL,
    usuario_id INT NOT NULL,
    CONSTRAINT fk_colab_turno FOREIGN KEY (turno_id) REFERENCES turnos(id),
    CONSTRAINT fk_colab_rest FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id),
    CONSTRAINT fk_colab_oper FOREIGN KEY (operacao_id) REFERENCES operacoes(id),
    CONSTRAINT fk_colab_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE vouchers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turno_id INT NULL,
    restaurante_id INT NOT NULL,
    operacao_id INT NOT NULL,
    nome_hospede VARCHAR(200) NOT NULL,
    data_estadia VARCHAR(50) NOT NULL,
    numero_reserva VARCHAR(80) NOT NULL,
    servico_upselling VARCHAR(200) NOT NULL,
    assinatura VARCHAR(160) NOT NULL,
    data_venda DATE NOT NULL,
    voucher_anexo_path VARCHAR(255) NULL,
    criado_em DATETIME NOT NULL,
    usuario_id INT NOT NULL,
    CONSTRAINT fk_voucher_turno FOREIGN KEY (turno_id) REFERENCES turnos(id),
    CONSTRAINT fk_voucher_rest FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id),
    CONSTRAINT fk_voucher_oper FOREIGN KEY (operacao_id) REFERENCES operacoes(id),
    CONSTRAINT fk_voucher_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
-- Modulo Reservas Tematicas (versao consolidada 1.0)
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

INSERT IGNORE INTO reservas_tematicas_turnos (id, hora, ativo, ordem) VALUES
(1, '19:00:00', 1, 1),
(2, '19:30:00', 1, 2),
(3, '20:00:00', 1, 3),
(4, '20:30:00', 1, 4),
(5, '21:00:00', 1, 5);

INSERT IGNORE INTO reservas_tematicas_periodos (id, hora_inicio, hora_fim, ativo, ordem) VALUES
(1, '08:30:00', '12:00:00', 1, 1),
(2, '13:00:00', '16:30:00', 1, 2);

INSERT IGNORE INTO reservas_tematicas_config (restaurante_id, capacidade_total, ativo) VALUES
((SELECT id FROM restaurantes WHERE nome = 'Restaurante Giardino'), 130, 1),
((SELECT id FROM restaurantes WHERE nome = 'Restaurante La Brasa'), 130, 1),
((SELECT id FROM restaurantes WHERE nome = 'Restaurante IX''u'), 80, 1);

INSERT IGNORE INTO reservas_tematicas_config_turnos (restaurante_id, turno_id, capacidade) VALUES
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

