-- FBControl 3.6 - Saneamento da base oficial de UHs para validacao operacional.
-- Mantem historico referenciado desativando UHs avulsas e remove apenas UHs sem uso.
-- Excecoes tecnicas mantidas: 998 (day use/nao informado) e 999 (nao informado).

START TRANSACTION;

CREATE TEMPORARY TABLE tmp_unidades_ranges (
  inicio INT NOT NULL,
  fim INT NOT NULL
);

INSERT INTO tmp_unidades_ranges (inicio, fim) VALUES
  (101, 151),
  (200, 248),
  (300, 319),
  (400, 419),
  (500, 519),
  (600, 619),
  (700, 719),
  (800, 819),
  (900, 919),
  (1000, 1019),
  (1101, 1111),
  (2100, 2109),
  (2200, 2209),
  (2300, 2309),
  (3100, 3109),
  (3200, 3209),
  (3300, 3309),
  (4000, 4021),
  (4100, 4122),
  (4200, 4222),
  (4300, 4322);

CREATE TEMPORARY TABLE tmp_seq (n INT NOT NULL PRIMARY KEY);

INSERT INTO tmp_seq (n) VALUES
  (0),(1),(2),(3),(4),(5),(6),(7),(8),(9),
  (10),(11),(12),(13),(14),(15),(16),(17),(18),(19),
  (20),(21),(22),(23),(24),(25),(26),(27),(28),(29),
  (30),(31),(32),(33),(34),(35),(36),(37),(38),(39),
  (40),(41),(42),(43),(44),(45),(46),(47),(48),(49),
  (50),(51),(52),(53),(54),(55),(56),(57),(58),(59),
  (60),(61),(62),(63),(64),(65),(66),(67),(68),(69),
  (70),(71),(72),(73),(74),(75),(76),(77),(78),(79),
  (80),(81),(82),(83),(84),(85),(86),(87),(88),(89),
  (90),(91),(92),(93),(94),(95),(96),(97),(98),(99);

CREATE TEMPORARY TABLE tmp_unidades_oficiais (
  numero VARCHAR(20) NOT NULL PRIMARY KEY
);

INSERT IGNORE INTO tmp_unidades_oficiais (numero)
SELECT CAST(r.inicio + s.n AS CHAR)
FROM tmp_unidades_ranges r
JOIN tmp_seq s ON s.n <= (r.fim - r.inicio);

INSERT IGNORE INTO tmp_unidades_oficiais (numero) VALUES ('998'), ('999');

INSERT INTO unidades_habitacionais (numero, ativo, criado_em)
SELECT numero, 1, NOW()
FROM tmp_unidades_oficiais
ON DUPLICATE KEY UPDATE ativo = 1;

UPDATE unidades_habitacionais uh
LEFT JOIN tmp_unidades_oficiais ok ON ok.numero = uh.numero
SET uh.ativo = 0
WHERE ok.numero IS NULL
  AND (
    EXISTS (SELECT 1 FROM acessos a WHERE a.uh_id = uh.id)
    OR EXISTS (SELECT 1 FROM acessos_especiais ae WHERE ae.uh_id = uh.id)
    OR EXISTS (SELECT 1 FROM reservas_tematicas rt WHERE rt.uh_id = uh.id)
  );

DELETE uh
FROM unidades_habitacionais uh
LEFT JOIN tmp_unidades_oficiais ok ON ok.numero = uh.numero
WHERE ok.numero IS NULL
  AND NOT EXISTS (SELECT 1 FROM acessos a WHERE a.uh_id = uh.id)
  AND NOT EXISTS (SELECT 1 FROM acessos_especiais ae WHERE ae.uh_id = uh.id)
  AND NOT EXISTS (SELECT 1 FROM reservas_tematicas rt WHERE rt.uh_id = uh.id);

DROP TEMPORARY TABLE IF EXISTS tmp_unidades_oficiais;
DROP TEMPORARY TABLE IF EXISTS tmp_seq;
DROP TEMPORARY TABLE IF EXISTS tmp_unidades_ranges;

COMMIT;
