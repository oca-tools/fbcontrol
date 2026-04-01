<?php
class OperationModel extends Model
{
    public function all(): array
    {
        $rows = $this->db->query("SELECT * FROM operacoes ORDER BY nome, id")->fetchAll();
        return $this->uniqueByNormalizedName($rows);
    }

    public function allBuffet(): array
    {
        $rows = $this->db->query("
            SELECT *
            FROM operacoes
            WHERE nome NOT IN ('Tematico','Temático','Privileged','VIP Premium','Vip Premium')
            ORDER BY nome
        ")->fetchAll();
        return $this->uniqueByNormalizedName($rows);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM operacoes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch();
        return $item ?: null;
    }

    public function create(array $data, int $userId): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO operacoes (nome, ativo, criado_em)
            VALUES (:nome, :ativo, NOW())
        ");
        $stmt->execute([
            ':nome' => $data['nome'],
            ':ativo' => $data['ativo'] ?? 1,
        ]);
        $id = (int)$this->db->lastInsertId();
        $this->audit('create', $userId, [], array_merge($data, ['id' => $id]), 'operacoes', $id);
        return $id;
    }

    public function update(int $id, array $data, int $userId): void
    {
        $before = $this->find($id) ?? [];
        $stmt = $this->db->prepare("
            UPDATE operacoes
            SET nome = :nome,
                ativo = :ativo
            WHERE id = :id
        ");
        $stmt->execute([
            ':nome' => $data['nome'],
            ':ativo' => $data['ativo'] ?? 1,
            ':id' => $id,
        ]);
        $after = $this->find($id) ?? [];
        $this->audit('update', $userId, $before, $after, 'operacoes', $id);
    }

    private function uniqueByNormalizedName(array $rows): array
    {
        $map = [];
        foreach ($rows as $row) {
            $name = trim((string)($row['nome'] ?? ''));
            if ($name === '') {
                continue;
            }
            $normalized = mb_strtolower(normalize_mojibake($name), 'UTF-8');
            $normalizedAscii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
            $key = preg_replace('/[^a-z0-9]/', '', (string)$normalizedAscii);
            if ($key === '') {
                $key = $normalized;
            }
            if (!isset($map[$key])) {
                $row['nome'] = normalize_mojibake($name);
                $map[$key] = $row;
            }
        }
        return array_values($map);
    }
}
