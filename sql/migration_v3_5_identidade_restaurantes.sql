-- Migration v3.5: identidade visual por restaurante (revisão de marca Oca, pilar 2).
-- Cada restaurante ganha uma cor e um ícone (Bootstrap Icons) para selos/badges
-- consistentes na operação. Colunas nascem NULL: enquanto vazias, a aplicação
-- (PerfilRestauranteService::identidade) deriva a identidade pelo nome — mesma
-- estratégia de rollout seguro da v3.4.

ALTER TABLE `restaurantes`
  ADD COLUMN `cor_hex` VARCHAR(7) NULL DEFAULT NULL AFTER `filtro_operacao_grupo`,
  ADD COLUMN `icone` VARCHAR(40) NULL DEFAULT NULL AFTER `cor_hex`;

-- Seed espelhando as identidades aprovadas (categóricas, distintas da marca Oca):
--   Corais  = azul recife (buffet livre)      · La Brasa = brasa (churrasco)
--   Giardino = verde (italiano à la carte)     · IX'u    = roxo (mediterrâneo à la carte)
UPDATE `restaurantes` SET `cor_hex` = '#2E7C9E', `icone` = 'bi-water'   WHERE `nome` LIKE '%Corais%';
UPDATE `restaurantes` SET `cor_hex` = '#C0433B', `icone` = 'bi-fire'    WHERE `nome` LIKE '%La Brasa%';
UPDATE `restaurantes` SET `cor_hex` = '#4E8B3B', `icone` = 'bi-flower1' WHERE `nome` LIKE '%Giardino%';
UPDATE `restaurantes` SET `cor_hex` = '#6C5CB0', `icone` = 'bi-star'    WHERE `nome` LIKE '%IX%';

-- Áreas especiais (Privileged / VIP Premium) recebem um tom neutro-âmbar de destaque.
UPDATE `restaurantes` SET `cor_hex` = '#B07D2A', `icone` = 'bi-gem'
 WHERE `tipo` = 'area' AND `cor_hex` IS NULL;
