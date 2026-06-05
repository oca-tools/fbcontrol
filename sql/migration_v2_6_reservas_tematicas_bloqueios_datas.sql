CREATE TABLE IF NOT EXISTS `reservas_tematicas_bloqueios_datas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `restaurante_id` int NOT NULL,
  `data_reserva` date NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `motivo` varchar(255) DEFAULT NULL,
  `usuario_id` int NOT NULL,
  `atualizado_em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_res_tem_bloq_data` (`restaurante_id`,`data_reserva`),
  KEY `idx_res_tem_bloq_data_ativo` (`data_reserva`,`ativo`),
  KEY `fk_res_tem_bloq_user` (`usuario_id`),
  CONSTRAINT `fk_res_tem_bloq_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`),
  CONSTRAINT `fk_res_tem_bloq_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
