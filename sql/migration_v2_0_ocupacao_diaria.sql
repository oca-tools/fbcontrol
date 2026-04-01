-- OCA FBControl v2.0 - ocupação diária para KPIs

CREATE TABLE IF NOT EXISTS kpi_ocupacao_diaria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_ref DATE NOT NULL,
    ocupacao_uh INT NULL,
    ocupacao_pax INT NULL,
    observacao VARCHAR(255) NULL,
    atualizado_por INT NOT NULL,
    atualizado_em DATETIME NOT NULL,
    UNIQUE KEY uq_kpi_ocupacao_data (data_ref),
    KEY idx_kpi_ocupacao_data (data_ref),
    CONSTRAINT fk_kpi_ocupacao_usuario FOREIGN KEY (atualizado_por) REFERENCES usuarios(id)
);