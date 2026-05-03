CREATE TABLE IF NOT EXISTS `reservas_tematicas_capacidades_datas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `restaurante_id` int(11) NOT NULL,
  `data_reserva` date NOT NULL,
  `turno_id` int(11) NOT NULL,
  `capacidade` int(11) NOT NULL DEFAULT 0,
  `usuario_id` int(11) DEFAULT NULL,
  `atualizado_em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_res_tem_cap_data` (`restaurante_id`,`data_reserva`,`turno_id`),
  KEY `idx_res_tem_cap_data` (`data_reserva`),
  KEY `fk_res_tem_cap_turno` (`turno_id`),
  KEY `fk_res_tem_cap_user` (`usuario_id`),
  CONSTRAINT `fk_res_tem_cap_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`),
  CONSTRAINT `fk_res_tem_cap_turno` FOREIGN KEY (`turno_id`) REFERENCES `reservas_tematicas_turnos` (`id`),
  CONSTRAINT `fk_res_tem_cap_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
