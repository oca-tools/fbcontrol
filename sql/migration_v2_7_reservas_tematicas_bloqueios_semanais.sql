CREATE TABLE IF NOT EXISTS reservas_tematicas_bloqueios_semanais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT NOT NULL,
    dia_semana TINYINT NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    motivo VARCHAR(255) NULL,
    usuario_id INT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_restaurante_dia (restaurante_id, dia_semana),
    KEY idx_dia_ativo (dia_semana, ativo),
    CONSTRAINT fk_bloqueio_semanal_restaurante FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id),
    CONSTRAINT fk_bloqueio_semanal_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO reservas_tematicas_bloqueios_semanais (restaurante_id, dia_semana, ativo, motivo, usuario_id, criado_em)
SELECT r.id, 2, 1, 'Fechamento semanal padrão: terça-feira', NULL, NOW()
FROM restaurantes r
WHERE LOWER(r.nome) LIKE '%giardino%'
  AND NOT EXISTS (
      SELECT 1 FROM reservas_tematicas_bloqueios_semanais b
      WHERE b.restaurante_id = r.id AND b.dia_semana = 2
  )
ORDER BY r.nome
LIMIT 1;

INSERT INTO reservas_tematicas_bloqueios_semanais (restaurante_id, dia_semana, ativo, motivo, usuario_id, criado_em)
SELECT r.id, 6, 1, 'Fechamento semanal padrão: sábado', NULL, NOW()
FROM restaurantes r
WHERE LOWER(r.nome) LIKE '%la brasa%'
  AND NOT EXISTS (
      SELECT 1 FROM reservas_tematicas_bloqueios_semanais b
      WHERE b.restaurante_id = r.id AND b.dia_semana = 6
  )
ORDER BY r.nome
LIMIT 1;

INSERT INTO reservas_tematicas_bloqueios_semanais (restaurante_id, dia_semana, ativo, motivo, usuario_id, criado_em)
SELECT r.id, 0, 1, 'Fechamento semanal padrão: domingo', NULL, NOW()
FROM restaurantes r
WHERE LOWER(r.nome) LIKE '%ix%'
  AND NOT EXISTS (
      SELECT 1 FROM reservas_tematicas_bloqueios_semanais b
      WHERE b.restaurante_id = r.id AND b.dia_semana = 0
  )
ORDER BY r.nome
LIMIT 1;
