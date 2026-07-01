<?php
class UnitModel extends Model
{
    private const TECHNICAL_UNITS = [998, 999];

    private const OPERATIONAL_RANGES = [
        [101, 151],
        [200, 248],
        [300, 319],
        [400, 419],
        [500, 519],
        [600, 619],
        [700, 719],
        [800, 819],
        [900, 919],
        [1000, 1019],
        [1101, 1111],
        [2100, 2109],
        [2200, 2209],
        [2300, 2309],
        [3100, 3109],
        [3200, 3209],
        [3300, 3309],
        [4000, 4021],
        [4100, 4122],
        [4200, 4222],
        [4300, 4322],
    ];

    private function normalizeNumeroInput(string $numero): string
    {
        $numero = preg_replace('/\s+/u', '', $numero) ?? $numero;
        $numero = trim($numero);
        if ($numero === '') {
            return '';
        }

        // Alguns teclados/mobile podem enviar "700,0" ou "700.0".
        if (preg_match('/^\d+[.,]0+$/', $numero)) {
            $numero = preg_replace('/[.,]0+$/', '', $numero) ?? $numero;
        }

        return trim($numero);
    }

    private function findByNumeroFlexible(string $numero, bool $onlyActive = true): ?array
    {
        $sql = "
            SELECT *
            FROM unidades_habitacionais
            WHERE " . ($onlyActive ? "ativo = 1 AND " : "") . " (
                numero = :numero_a
                OR TRIM(numero) = :numero_b
                OR (
                    :numero_int_a IS NOT NULL
                    AND CAST(REPLACE(TRIM(numero), ',', '.') AS UNSIGNED) = :numero_int_b
                )
            )
            ORDER BY
                (numero = :numero_exact_a) DESC,
                (TRIM(numero) = :numero_exact_b) DESC,
                id ASC
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $numeroInt = ctype_digit($numero) ? (int)$numero : null;
        $stmt->execute([
            ':numero_a' => $numero,
            ':numero_b' => $numero,
            ':numero_int_a' => $numeroInt,
            ':numero_int_b' => $numeroInt,
            ':numero_exact_a' => $numero,
            ':numero_exact_b' => $numero,
        ]);
        $item = $stmt->fetch();
        return $item ?: null;
    }

    private function isCoreOperationalUnit(int $num): bool
    {
        if (in_array($num, self::TECHNICAL_UNITS, true)) {
            return true;
        }

        foreach (self::OPERATIONAL_RANGES as [$start, $end]) {
            if ($num >= $start && $num <= $end) {
                return true;
            }
        }

        return false;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM unidades_habitacionais WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch();
        return $item ?: null;
    }

    public function isValidNumero(string $numero): bool
    {
        $numero = $this->normalizeNumeroInput($numero);
        return $numero !== ''
            && ctype_digit($numero)
            && $this->isCoreOperationalUnit((int)$numero);
    }

    public function findByNumero(string $numero): ?array
    {
        $numero = $this->normalizeNumeroInput($numero);
        if (!$this->isValidNumero($numero)) {
            return null;
        }
        $numeroInt = (int)$numero;
        $numero = (string)$numeroInt;

        $item = $this->findByNumeroFlexible($numero, true);
        if ($item) {
            return $item;
        }

        // Auto-reparo: unidade existe mas está inativa por erro operacional.
        $anyStatus = $this->findByNumeroFlexible($numero, false);
        if ($anyStatus && (int)($anyStatus['ativo'] ?? 0) !== 1 && ctype_digit($numero)) {
            $num = (int)$numero;
            if ($this->isCoreOperationalUnit($num)) {
                $stmtReactivate = $this->db->prepare("UPDATE unidades_habitacionais SET ativo = 1 WHERE id = :id");
                $stmtReactivate->execute([':id' => (int)$anyStatus['id']]);
                $anyStatus['ativo'] = 1;
                return $anyStatus;
            }
        }

        // Auto-reparo: uma UH oficial ausente e criada no primeiro uso.
        if (!$anyStatus) {
            $this->ensureOperationalUnit($numero);
            return $this->findByNumeroFlexible($numero, false);
        }
        return null;
    }

    public function maxPaxForNumero(string $numero): ?int
    {
        $numero = $this->normalizeNumeroInput($numero);
        $num = (int)$numero;
        if (in_array($num, self::TECHNICAL_UNITS, true)) {
            return null; // UHs técnicas (não informado/day use): sem limite rígido por tipologia
        }
        if (!$this->isCoreOperationalUnit($num)) {
            return null;
        }
        if (($num >= 101 && $num <= 151) || ($num >= 200 && $num <= 248)) {
            return 4; // bangalos (series 100 e 200)
        }
        if ($num <= 1111) {
            return 5; // blocos standards e suites family
        }
        if ($num >= 2100) {
            return 6; // area nova
        }
        return null;
    }

    public function create(array $data, int $userId): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO unidades_habitacionais (numero, ativo, criado_em)
            VALUES (:numero, :ativo, NOW())
        ");
        $stmt->execute([
            ':numero' => $data['numero'],
            ':ativo' => $data['ativo'] ?? 1,
        ]);
        $id = (int)$this->db->lastInsertId();
        $this->audit('create', $userId, [], array_merge($data, ['id' => $id]), 'unidades_habitacionais', $id);
        return $id;
    }

    private function ensureOperationalUnit(string $numero): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO unidades_habitacionais (numero, ativo, criado_em)
            VALUES (:numero, 1, NOW())
            ON DUPLICATE KEY UPDATE ativo = 1
        ");
        $stmt->execute([':numero' => $numero]);
    }
}
