-- MIGRATION: registros de colaboradores e vouchers
CREATE TABLE IF NOT EXISTS colaborador_refeicoes (
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

CREATE TABLE IF NOT EXISTS vouchers (
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

ALTER TABLE vouchers ADD COLUMN IF NOT EXISTS voucher_anexo_path VARCHAR(255) NULL;