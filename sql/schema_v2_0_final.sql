-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: controle_ab
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `acessos`
--

DROP TABLE IF EXISTS `acessos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acessos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `turno_id` int(11) DEFAULT NULL,
  `uh_id` int(11) NOT NULL,
  `pax` int(11) NOT NULL DEFAULT 1,
  `restaurante_id` int(11) NOT NULL,
  `porta_id` int(11) DEFAULT NULL,
  `operacao_id` int(11) NOT NULL,
  `alerta_duplicidade` tinyint(1) NOT NULL DEFAULT 0,
  `fora_do_horario` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL,
  `usuario_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_acessos_turno` (`turno_id`),
  KEY `fk_acessos_porta` (`porta_id`),
  KEY `fk_acessos_operacao` (`operacao_id`),
  KEY `idx_acessos_uh_operacao_time` (`uh_id`,`operacao_id`,`criado_em`),
  KEY `idx_acessos_data` (`criado_em`),
  KEY `idx_acessos_rest_oper` (`restaurante_id`,`operacao_id`),
  KEY `idx_acessos_user_data` (`usuario_id`,`criado_em`),
  KEY `idx_acessos_status_data` (`alerta_duplicidade`,`fora_do_horario`,`criado_em`),
  KEY `idx_acessos_uh_data` (`uh_id`,`criado_em`),
  CONSTRAINT `fk_acessos_operacao` FOREIGN KEY (`operacao_id`) REFERENCES `operacoes` (`id`),
  CONSTRAINT `fk_acessos_porta` FOREIGN KEY (`porta_id`) REFERENCES `portas` (`id`),
  CONSTRAINT `fk_acessos_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`),
  CONSTRAINT `fk_acessos_turno` FOREIGN KEY (`turno_id`) REFERENCES `turnos` (`id`),
  CONSTRAINT `fk_acessos_uh` FOREIGN KEY (`uh_id`) REFERENCES `unidades_habitacionais` (`id`),
  CONSTRAINT `fk_acessos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=214 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acessos_especiais`
--

DROP TABLE IF EXISTS `acessos_especiais`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acessos_especiais` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `turno_especial_id` int(11) DEFAULT NULL,
  `uh_id` int(11) NOT NULL,
  `pax` int(11) NOT NULL DEFAULT 1,
  `restaurante_id` int(11) NOT NULL,
  `porta_id` int(11) DEFAULT NULL,
  `tipo` enum('tematico','privileged') NOT NULL,
  `alerta_duplicidade` tinyint(1) NOT NULL DEFAULT 0,
  `fora_do_horario` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL,
  `usuario_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_acessos_esp_turno` (`turno_especial_id`),
  KEY `fk_acessos_esp_porta` (`porta_id`),
  KEY `fk_acessos_esp_usuario` (`usuario_id`),
  KEY `idx_acessos_esp_uh_tipo_time` (`uh_id`,`tipo`,`criado_em`),
  KEY `idx_acessos_esp_data` (`criado_em`),
  KEY `idx_acessos_esp_rest_tipo` (`restaurante_id`,`tipo`),
  CONSTRAINT `fk_acessos_esp_porta` FOREIGN KEY (`porta_id`) REFERENCES `portas` (`id`),
  CONSTRAINT `fk_acessos_esp_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`),
  CONSTRAINT `fk_acessos_esp_turno` FOREIGN KEY (`turno_especial_id`) REFERENCES `turnos_especiais` (`id`),
  CONSTRAINT `fk_acessos_esp_uh` FOREIGN KEY (`uh_id`) REFERENCES `unidades_habitacionais` (`id`),
  CONSTRAINT `fk_acessos_esp_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `auditoria`
--

DROP TABLE IF EXISTS `auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auditoria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tabela` varchar(80) NOT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `acao` varchar(40) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `dados_antes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`dados_antes`)),
  `dados_depois` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`dados_depois`)),
  `criado_em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_auditoria_usuario` (`usuario_id`),
  CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=125 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `colaborador_refeicoes`
--

DROP TABLE IF EXISTS `colaborador_refeicoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `colaborador_refeicoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `turno_id` int(11) DEFAULT NULL,
  `restaurante_id` int(11) NOT NULL,
  `operacao_id` int(11) NOT NULL,
  `nome_colaborador` varchar(160) NOT NULL,
  `quantidade` int(11) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL,
  `usuario_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_colab_turno` (`turno_id`),
  KEY `fk_colab_rest` (`restaurante_id`),
  KEY `fk_colab_oper` (`operacao_id`),
  KEY `fk_colab_usuario` (`usuario_id`),
  CONSTRAINT `fk_colab_oper` FOREIGN KEY (`operacao_id`) REFERENCES `operacoes` (`id`),
  CONSTRAINT `fk_colab_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`),
  CONSTRAINT `fk_colab_turno` FOREIGN KEY (`turno_id`) REFERENCES `turnos` (`id`),
  CONSTRAINT `fk_colab_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kpi_ocupacao_diaria`
--

DROP TABLE IF EXISTS `kpi_ocupacao_diaria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kpi_ocupacao_diaria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data_ref` date NOT NULL,
  `ocupacao_uh` int(11) DEFAULT NULL,
  `ocupacao_pax` int(11) DEFAULT NULL,
  `observacao` varchar(255) DEFAULT NULL,
  `atualizado_por` int(11) NOT NULL,
  `atualizado_em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kpi_ocupacao_data` (`data_ref`),
  KEY `idx_kpi_ocupacao_data` (`data_ref`),
  KEY `fk_kpi_ocupacao_usuario` (`atualizado_por`),
  CONSTRAINT `fk_kpi_ocupacao_usuario` FOREIGN KEY (`atualizado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `operacoes`
--

DROP TABLE IF EXISTS `operacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `operacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(120) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `portas`
--

DROP TABLE IF EXISTS `portas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `portas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `restaurante_id` int(11) NOT NULL,
  `nome` varchar(120) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_portas_rest` (`restaurante_id`),
  CONSTRAINT `fk_portas_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `relatorio_email_config`
--

DROP TABLE IF EXISTS `relatorio_email_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `relatorio_email_config` (
  `id` int(11) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 0,
  `hora_envio` time NOT NULL DEFAULT '23:00:00',
  `assunto` varchar(255) NOT NULL DEFAULT 'Resumo diário A&B - {data}',
  `remetente_nome` varchar(120) NOT NULL DEFAULT 'OCA FBControl',
  `remetente_email` varchar(190) DEFAULT NULL,
  `atualizado_em` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `relatorio_email_destinatarios`
--

DROP TABLE IF EXISTS `relatorio_email_destinatarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `relatorio_email_destinatarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(190) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `receber_anexo_vouchers` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_relatorio_email_dest` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `relatorio_email_envios`
--

DROP TABLE IF EXISTS `relatorio_email_envios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `relatorio_email_envios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data_referencia` date NOT NULL,
  `enviado_em` datetime NOT NULL,
  `status` varchar(20) NOT NULL,
  `assunto` varchar(255) NOT NULL,
  `total_destinatarios` int(11) NOT NULL DEFAULT 0,
  `destinatarios` text DEFAULT NULL,
  `resumo_json` longtext DEFAULT NULL,
  `erro` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_relatorio_email_data` (`data_referencia`),
  KEY `idx_relatorio_email_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reservas_tematicas`
--

DROP TABLE IF EXISTS `reservas_tematicas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reservas_tematicas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `restaurante_id` int(11) NOT NULL,
  `data_reserva` date NOT NULL,
  `turno_id` int(11) NOT NULL,
  `uh_id` int(11) NOT NULL,
  `pax` int(11) NOT NULL,
  `pax_real` int(11) DEFAULT NULL,
  `observacao_reserva` text DEFAULT NULL,
  `observacao_tags` text DEFAULT NULL,
  `observacao_operacao` text DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'Reservada',
  `excedente` tinyint(1) NOT NULL DEFAULT 0,
  `excedente_motivo` varchar(255) DEFAULT NULL,
  `excedente_autor_id` int(11) DEFAULT NULL,
  `excedente_em` datetime DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `atualizado_por` int(11) DEFAULT NULL,
  `criado_em` datetime NOT NULL,
  `atualizado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_res_tem_data_rest_turno` (`data_reserva`,`restaurante_id`,`turno_id`),
  KEY `idx_res_tem_uh` (`uh_id`),
  KEY `fk_res_tem_rest` (`restaurante_id`),
  KEY `fk_res_tem_turno` (`turno_id`),
  KEY `fk_res_tem_user` (`usuario_id`),
  KEY `fk_res_tem_user_upd` (`atualizado_por`),
  KEY `fk_res_tem_exc_user` (`excedente_autor_id`),
  CONSTRAINT `fk_res_tem_exc_user` FOREIGN KEY (`excedente_autor_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_res_tem_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`),
  CONSTRAINT `fk_res_tem_turno` FOREIGN KEY (`turno_id`) REFERENCES `reservas_tematicas_turnos` (`id`),
  CONSTRAINT `fk_res_tem_uh` FOREIGN KEY (`uh_id`) REFERENCES `unidades_habitacionais` (`id`),
  CONSTRAINT `fk_res_tem_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_res_tem_user_upd` FOREIGN KEY (`atualizado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reservas_tematicas_config`
--

DROP TABLE IF EXISTS `reservas_tematicas_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reservas_tematicas_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `restaurante_id` int(11) NOT NULL,
  `capacidade_total` int(11) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_res_tem_config_rest` (`restaurante_id`),
  CONSTRAINT `fk_res_tem_config_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reservas_tematicas_config_turnos`
--

DROP TABLE IF EXISTS `reservas_tematicas_config_turnos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reservas_tematicas_config_turnos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `restaurante_id` int(11) NOT NULL,
  `turno_id` int(11) NOT NULL,
  `capacidade` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_res_tem_cfg_turno` (`restaurante_id`,`turno_id`),
  KEY `fk_res_tem_cfg_turno_turno` (`turno_id`),
  CONSTRAINT `fk_res_tem_cfg_turno_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`),
  CONSTRAINT `fk_res_tem_cfg_turno_turno` FOREIGN KEY (`turno_id`) REFERENCES `reservas_tematicas_turnos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reservas_tematicas_fechamentos`
--

DROP TABLE IF EXISTS `reservas_tematicas_fechamentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reservas_tematicas_fechamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `restaurante_id` int(11) NOT NULL,
  `data_reserva` date NOT NULL,
  `turno_id` int(11) NOT NULL,
  `fechado_em` datetime NOT NULL,
  `usuario_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_res_tem_fech` (`restaurante_id`,`data_reserva`,`turno_id`),
  KEY `fk_res_tem_fech_turno` (`turno_id`),
  KEY `fk_res_tem_fech_user` (`usuario_id`),
  CONSTRAINT `fk_res_tem_fech_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`),
  CONSTRAINT `fk_res_tem_fech_turno` FOREIGN KEY (`turno_id`) REFERENCES `reservas_tematicas_turnos` (`id`),
  CONSTRAINT `fk_res_tem_fech_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reservas_tematicas_logs`
--

DROP TABLE IF EXISTS `reservas_tematicas_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reservas_tematicas_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reserva_id` int(11) NOT NULL,
  `acao` varchar(60) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `dados_antes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dados_antes`)),
  `dados_depois` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dados_depois`)),
  `justificativa` varchar(255) DEFAULT NULL,
  `criado_em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_res_tem_log_reserva` (`reserva_id`),
  KEY `fk_res_tem_log_user` (`usuario_id`),
  CONSTRAINT `fk_res_tem_log_reserva` FOREIGN KEY (`reserva_id`) REFERENCES `reservas_tematicas` (`id`),
  CONSTRAINT `fk_res_tem_log_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reservas_tematicas_periodos`
--

DROP TABLE IF EXISTS `reservas_tematicas_periodos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reservas_tematicas_periodos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hora_inicio` time NOT NULL,
  `hora_fim` time NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `ordem` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reservas_tematicas_turnos`
--

DROP TABLE IF EXISTS `reservas_tematicas_turnos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reservas_tematicas_turnos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hora` time NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `ordem` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `restaurante_especiais`
--

DROP TABLE IF EXISTS `restaurante_especiais`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `restaurante_especiais` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `restaurante_id` int(11) NOT NULL,
  `tipo` enum('tematico','privileged') NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fim` time NOT NULL,
  `tolerancia_min` int(11) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_rest_esp_rest` (`restaurante_id`),
  CONSTRAINT `fk_rest_esp_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `restaurante_operacoes`
--

DROP TABLE IF EXISTS `restaurante_operacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `restaurante_operacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `restaurante_id` int(11) NOT NULL,
  `operacao_id` int(11) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fim` time NOT NULL,
  `tolerancia_min` int(11) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_rest_oper_rest` (`restaurante_id`),
  KEY `fk_rest_oper_oper` (`operacao_id`),
  CONSTRAINT `fk_rest_oper_oper` FOREIGN KEY (`operacao_id`) REFERENCES `operacoes` (`id`),
  CONSTRAINT `fk_rest_oper_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `restaurantes`
--

DROP TABLE IF EXISTS `restaurantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `restaurantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(120) NOT NULL,
  `tipo` enum('buffet','tematico','area') NOT NULL DEFAULT 'buffet',
  `seleciona_porta_no_turno` tinyint(1) NOT NULL DEFAULT 0,
  `exige_pax` tinyint(1) NOT NULL DEFAULT 1,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `turnos`
--

DROP TABLE IF EXISTS `turnos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `turnos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `restaurante_id` int(11) NOT NULL,
  `operacao_id` int(11) NOT NULL,
  `porta_id` int(11) DEFAULT NULL,
  `inicio_em` datetime NOT NULL,
  `fim_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_turnos_user` (`usuario_id`),
  KEY `fk_turnos_rest` (`restaurante_id`),
  KEY `fk_turnos_oper` (`operacao_id`),
  KEY `fk_turnos_porta` (`porta_id`),
  CONSTRAINT `fk_turnos_oper` FOREIGN KEY (`operacao_id`) REFERENCES `operacoes` (`id`),
  CONSTRAINT `fk_turnos_porta` FOREIGN KEY (`porta_id`) REFERENCES `portas` (`id`),
  CONSTRAINT `fk_turnos_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`),
  CONSTRAINT `fk_turnos_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `turnos_especiais`
--

DROP TABLE IF EXISTS `turnos_especiais`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `turnos_especiais` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `restaurante_id` int(11) NOT NULL,
  `tipo` enum('tematico','privileged') NOT NULL,
  `porta_id` int(11) DEFAULT NULL,
  `inicio_em` datetime NOT NULL,
  `fim_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_turnos_esp_user` (`usuario_id`),
  KEY `fk_turnos_esp_rest` (`restaurante_id`),
  KEY `fk_turnos_esp_porta` (`porta_id`),
  CONSTRAINT `fk_turnos_esp_porta` FOREIGN KEY (`porta_id`) REFERENCES `portas` (`id`),
  CONSTRAINT `fk_turnos_esp_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`),
  CONSTRAINT `fk_turnos_esp_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `unidades_habitacionais`
--

DROP TABLE IF EXISTS `unidades_habitacionais`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unidades_habitacionais` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` varchar(20) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero` (`numero`)
) ENGINE=InnoDB AUTO_INCREMENT=426 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(120) NOT NULL,
  `email` varchar(160) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `perfil` enum('hostess','gerente','supervisor','admin') NOT NULL DEFAULT 'hostess',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_usuarios_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usuarios_onboarding`
--

DROP TABLE IF EXISTS `usuarios_onboarding`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios_onboarding` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `hostess_tutorial_seen` tinyint(1) NOT NULL DEFAULT 0,
  `hostess_tutorial_completed` tinyint(1) NOT NULL DEFAULT 0,
  `hostess_tutorial_completed_em` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL,
  `atualizado_em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_onboarding_usuario` (`usuario_id`),
  CONSTRAINT `fk_onboarding_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usuarios_restaurantes`
--

DROP TABLE IF EXISTS `usuarios_restaurantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios_restaurantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `restaurante_id` int(11) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_usr_rest_user` (`usuario_id`),
  KEY `fk_usr_rest_rest` (`restaurante_id`),
  CONSTRAINT `fk_usr_rest_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`),
  CONSTRAINT `fk_usr_rest_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usuarios_restaurantes_operacoes`
--

DROP TABLE IF EXISTS `usuarios_restaurantes_operacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios_restaurantes_operacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `restaurante_id` int(11) NOT NULL,
  `operacao_id` int(11) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_usr_rest_op_oper` (`operacao_id`),
  KEY `idx_usr_rest_op_user` (`usuario_id`,`ativo`),
  KEY `idx_usr_rest_op_rest` (`restaurante_id`,`operacao_id`,`ativo`),
  CONSTRAINT `fk_usr_rest_op_oper` FOREIGN KEY (`operacao_id`) REFERENCES `operacoes` (`id`),
  CONSTRAINT `fk_usr_rest_op_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`),
  CONSTRAINT `fk_usr_rest_op_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vouchers`
--

DROP TABLE IF EXISTS `vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `turno_id` int(11) DEFAULT NULL,
  `restaurante_id` int(11) NOT NULL,
  `operacao_id` int(11) NOT NULL,
  `nome_hospede` varchar(200) NOT NULL,
  `data_estadia` varchar(50) NOT NULL,
  `numero_reserva` varchar(80) NOT NULL,
  `servico_upselling` varchar(200) NOT NULL,
  `assinatura` varchar(160) NOT NULL,
  `data_venda` date NOT NULL,
  `voucher_anexo_path` varchar(255) DEFAULT NULL,
  `criado_em` datetime NOT NULL,
  `usuario_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_voucher_turno` (`turno_id`),
  KEY `fk_voucher_rest` (`restaurante_id`),
  KEY `fk_voucher_oper` (`operacao_id`),
  KEY `fk_voucher_usuario` (`usuario_id`),
  CONSTRAINT `fk_voucher_oper` FOREIGN KEY (`operacao_id`) REFERENCES `operacoes` (`id`),
  CONSTRAINT `fk_voucher_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`),
  CONSTRAINT `fk_voucher_turno` FOREIGN KEY (`turno_id`) REFERENCES `turnos` (`id`),
  CONSTRAINT `fk_voucher_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping routines for database 'controle_ab'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-02  9:40:11
