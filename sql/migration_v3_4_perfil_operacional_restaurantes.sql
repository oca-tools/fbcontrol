-- Migration v3.4: perfil operacional de restaurantes e operações.
-- Substitui as regras codificadas por nome (Corais, La Brasa, Giardino, IX'u)
-- por flags configuráveis no cadastro.
--
-- Estratégia de rollout seguro: as colunas nascem NULL ("não configurado").
-- Enquanto o valor for NULL, a aplicação (PerfilRestauranteService) mantém o
-- comportamento histórico por nome. O seed abaixo espelha essas regras.

ALTER TABLE `restaurantes`
  ADD COLUMN `modo_tematico` ENUM('nao','sempre','por_operacao') NULL DEFAULT NULL AFTER `tipo`,
  ADD COLUMN `permite_filtro_operacao` TINYINT(1) NULL DEFAULT NULL AFTER `modo_tematico`,
  ADD COLUMN `filtro_operacao_grupo` ENUM('todas','buffet','almoco_tematico') NULL DEFAULT NULL AFTER `permite_filtro_operacao`;

ALTER TABLE `operacoes`
  ADD COLUMN `tematica` TINYINT(1) NULL DEFAULT NULL AFTER `ativo`;

-- Seed: espelha as regras históricas por nome. Revisar os resultados após aplicar.
UPDATE `restaurantes`
   SET `permite_filtro_operacao` = 1,
       `filtro_operacao_grupo` = 'buffet',
       `modo_tematico` = 'nao'
 WHERE `nome` LIKE '%Corais%';

UPDATE `restaurantes`
   SET `permite_filtro_operacao` = 1,
       `filtro_operacao_grupo` = 'almoco_tematico',
       `modo_tematico` = 'por_operacao'
 WHERE `nome` LIKE '%La Brasa%';

UPDATE `restaurantes`
   SET `modo_tematico` = 'sempre'
 WHERE `nome` LIKE '%Giardino%'
    OR `nome` LIKE '%IX%';

-- Demais restaurantes: valores padrão explícitos.
UPDATE `restaurantes`
   SET `modo_tematico` = COALESCE(`modo_tematico`, 'nao'),
       `permite_filtro_operacao` = COALESCE(`permite_filtro_operacao`, 0),
       `filtro_operacao_grupo` = COALESCE(`filtro_operacao_grupo`, 'todas');

-- Operações temáticas (collation utf8mb4_unicode_ci ignora acento/caixa).
UPDATE `operacoes`
   SET `tematica` = (CASE WHEN `nome` LIKE '%tematic%' THEN 1 ELSE 0 END);
