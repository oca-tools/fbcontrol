-- LGPD module (governanca, solicitacoes de titulares, incidentes e retencao)
-- Safe migration for existing bases

CREATE TABLE IF NOT EXISTS lgpd_config (
    id INT NOT NULL PRIMARY KEY,
    controlador_nome VARCHAR(160) NOT NULL,
    controlador_email VARCHAR(190) NULL,
    encarregado_nome VARCHAR(160) NULL,
    encarregado_email VARCHAR(190) NULL,
    encarregado_telefone VARCHAR(40) NULL,
    canal_titular_url VARCHAR(255) NULL,
    canal_titular_email VARCHAR(190) NULL,
    politica_privacidade_url VARCHAR(255) NULL,
    prazo_titular_dias INT NOT NULL DEFAULT 15,
    prazo_incidente_dias_uteis INT NOT NULL DEFAULT 3,
    atualizado_por INT NULL,
    atualizado_em DATETIME NOT NULL,
    CONSTRAINT fk_lgpd_config_user FOREIGN KEY (atualizado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO lgpd_config
    (id, controlador_nome, controlador_email, encarregado_nome, encarregado_email, encarregado_telefone, canal_titular_url, canal_titular_email, politica_privacidade_url, prazo_titular_dias, prazo_incidente_dias_uteis, atualizado_por, atualizado_em)
SELECT
    1, 'Grand Oca Maragogi Resort', '', '', '', '', '/?r=privacidade/index', '', '/?r=privacidade/index', 15, 3, NULL, NOW()
WHERE NOT EXISTS (SELECT 1 FROM lgpd_config WHERE id = 1);

CREATE TABLE IF NOT EXISTS lgpd_solicitacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    protocolo VARCHAR(40) NOT NULL,
    tipo ENUM('acesso','correcao','anonimizacao','eliminacao','portabilidade','oposicao','revogacao','informacao') NOT NULL DEFAULT 'acesso',
    titular_nome VARCHAR(160) NOT NULL,
    titular_documento VARCHAR(40) NULL,
    titular_email VARCHAR(190) NULL,
    detalhes TEXT NULL,
    status ENUM('aberta','em_tratamento','concluida','indeferida') NOT NULL DEFAULT 'aberta',
    recebido_em DATETIME NOT NULL,
    prazo_resposta_em DATETIME NULL,
    concluido_em DATETIME NULL,
    resposta_resumo TEXT NULL,
    criado_por INT NULL,
    atualizado_por INT NULL,
    atualizado_em DATETIME NOT NULL,
    UNIQUE KEY uq_lgpd_solicitacoes_protocolo (protocolo),
    KEY idx_lgpd_solicitacoes_status_prazo (status, prazo_resposta_em),
    KEY idx_lgpd_solicitacoes_recebido (recebido_em),
    CONSTRAINT fk_lgpd_sol_criado_por FOREIGN KEY (criado_por) REFERENCES usuarios(id),
    CONSTRAINT fk_lgpd_sol_atualizado_por FOREIGN KEY (atualizado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lgpd_incidentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(40) NOT NULL,
    titulo VARCHAR(190) NOT NULL,
    categoria VARCHAR(80) NULL,
    status ENUM('aberto','investigacao','comunicado','encerrado') NOT NULL DEFAULT 'aberto',
    risco_nivel ENUM('baixo','medio','alto') NOT NULL DEFAULT 'medio',
    data_incidente DATETIME NULL,
    detectado_em DATETIME NOT NULL,
    titulares_afetados INT NOT NULL DEFAULT 0,
    dados_afetados TEXT NULL,
    medidas_adotadas TEXT NULL,
    comunicado_anpd TINYINT(1) NOT NULL DEFAULT 0,
    comunicado_titulares TINYINT(1) NOT NULL DEFAULT 0,
    comunicado_em DATETIME NULL,
    encerrado_em DATETIME NULL,
    criado_por INT NULL,
    atualizado_por INT NULL,
    atualizado_em DATETIME NOT NULL,
    UNIQUE KEY uq_lgpd_incidentes_codigo (codigo),
    KEY idx_lgpd_incidentes_status_risco (status, risco_nivel),
    KEY idx_lgpd_incidentes_detectado (detectado_em),
    CONSTRAINT fk_lgpd_inc_criado_por FOREIGN KEY (criado_por) REFERENCES usuarios(id),
    CONSTRAINT fk_lgpd_inc_atualizado_por FOREIGN KEY (atualizado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lgpd_retencao_politicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tabela_nome VARCHAR(100) NOT NULL,
    descricao VARCHAR(190) NULL,
    retencao_dias INT NOT NULL DEFAULT 180,
    modo ENUM('eliminar','anonimizar') NOT NULL DEFAULT 'eliminar',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    atualizado_por INT NULL,
    atualizado_em DATETIME NOT NULL,
    UNIQUE KEY uq_lgpd_retencao_tabela (tabela_nome),
    KEY idx_lgpd_retencao_ativo (ativo),
    CONSTRAINT fk_lgpd_retencao_user FOREIGN KEY (atualizado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lgpd_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(30) NOT NULL,
    referencia VARCHAR(120) NOT NULL,
    acao VARCHAR(40) NOT NULL,
    detalhes_json LONGTEXT NULL,
    usuario_id INT NULL,
    criado_em DATETIME NOT NULL,
    KEY idx_lgpd_eventos_tipo_data (tipo, criado_em),
    KEY idx_lgpd_eventos_ref (referencia),
    CONSTRAINT fk_lgpd_eventos_user FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO lgpd_retencao_politicas (tabela_nome, descricao, retencao_dias, modo, ativo, atualizado_por, atualizado_em)
SELECT 'auditoria', 'Log de auditoria do sistema', 365, 'eliminar', 1, NULL, NOW()
WHERE NOT EXISTS (SELECT 1 FROM lgpd_retencao_politicas WHERE tabela_nome = 'auditoria');

INSERT INTO lgpd_retencao_politicas (tabela_nome, descricao, retencao_dias, modo, ativo, atualizado_por, atualizado_em)
SELECT 'relatorio_email_envios', 'Historico de envios de e-mail diario', 180, 'eliminar', 1, NULL, NOW()
WHERE NOT EXISTS (SELECT 1 FROM lgpd_retencao_politicas WHERE tabela_nome = 'relatorio_email_envios');

INSERT INTO lgpd_retencao_politicas (tabela_nome, descricao, retencao_dias, modo, ativo, atualizado_por, atualizado_em)
SELECT 'lgpd_eventos', 'Eventos internos do modulo LGPD', 365, 'eliminar', 1, NULL, NOW()
WHERE NOT EXISTS (SELECT 1 FROM lgpd_retencao_politicas WHERE tabela_nome = 'lgpd_eventos');
