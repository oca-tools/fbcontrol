<?php
class UnitModel extends Model
{
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
                    AND TRIM(numero) REGEXP '^[0-9]+([\\.,]0+)?$'
                    AND CAST(TRIM(numero) AS UNSIGNED) = :numero_int_b
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
        if ($num === 2 || $num === 998 || $num === 999) {
            return true;
        }
        if ($num >= 100 && $num <= 299) {
            return true;
        }
        if ($num >= 300 && $num <= 1019) {
            return true;
        }
        if ($num >= 1101 && $num <= 1112) {
            return true;
        }
        if ($num >= 2100 && $num <= 4322) {
            return true;
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

    public function findByNumero(string $numero): ?array
    {
        $numero = $this->normalizeNumeroInput($numero);
        if ($numero === '') {
            return null;
        }

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

        // Garante UHs técnicas para operação (Não Informado / Day Use).
        if (in_array($numero, ['998', '999'], true)) {
            $this->ensureTechnicalUnit($numero);
            return $this->findByNumeroFlexible($numero, false);
        }

        return null;
    }

    public function maxPaxForNumero(string $numero): ?int
    {
        $numero = $this->normalizeNumeroInput($numero);
        $num = (int)$numero;
        if ($num === 998 || $num === 999) {
            return null; // UHs técnicas (não informado/day use): sem limite rígido por tipologia
        }
        if ($num >= 100 && $num <= 299) {
            return 4; // bangalos (series 100 e 200)
        }
        if ($num >= 300 && $num <= 1019) {
            return 5; // standards
        }
        if ($num >= 1101 && $num <= 1112) {
            return 5; // suites family
        }
        if ($num >= 2100 && $num <= 4322) {
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

    private function ensureTechnicalUnit(string $numero): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO unidades_habitacionais (numero, ativo, criado_em)
            SELECT :numero_insert, 1, NOW()
            WHERE NOT EXISTS (
                SELECT 1 FROM unidades_habitacionais WHERE numero = :numero_exists
            )
        ");
        $stmt->execute([
            ':numero_insert' => $numero,
            ':numero_exists' => $numero,
        ]);

        $stmtUpdate = $this->db->prepare("UPDATE unidades_habitacionais SET ativo = 1 WHERE numero = :numero");
        $stmtUpdate->execute([':numero' => $numero]);
    }
}
