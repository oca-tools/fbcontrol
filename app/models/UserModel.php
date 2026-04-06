<?php
class UserModel extends Model
{
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE email = :email AND ativo = 1 ORDER BY id DESC LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function authenticateByEmailAndPassword(string $email, string $plainPassword): array
    {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE email = :email AND ativo = 1 ORDER BY id DESC");
        $stmt->execute([':email' => $email]);
        $rows = $stmt->fetchAll();

        $matches = [];
        foreach ($rows as $row) {
            if (password_verify($plainPassword, (string)$row['senha'])) {
                $matches[] = $row;
            }
        }

        if (count($matches) === 1) {
            return ['status' => 'ok', 'user' => $matches[0]];
        }
        if (count($matches) > 1) {
            return ['status' => 'ambiguous', 'user' => null];
        }
        return ['status' => 'invalid', 'user' => null];
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE email = :email AND id <> :id LIMIT 1");
            $stmt->execute([
                ':email' => $email,
                ':id' => $excludeId,
            ]);
        } else {
            $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
        }
        return (bool)$stmt->fetch();
    }

    public function emailPasswordExists(string $email, string $plainPassword, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT id, senha FROM usuarios WHERE email = :email AND id <> :id");
            $stmt->execute([
                ':email' => $email,
                ':id' => $excludeId,
            ]);
        } else {
            $stmt = $this->db->prepare("SELECT id, senha FROM usuarios WHERE email = :email");
            $stmt->execute([':email' => $email]);
        }

        foreach ($stmt->fetchAll() as $row) {
            if (password_verify($plainPassword, (string)($row['senha'] ?? ''))) {
                return true;
            }
        }
        return false;
    }

    public function all(): array
    {
        return $this->db->query("SELECT * FROM usuarios ORDER BY nome")->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function create(array $data, int $userId): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO usuarios (nome, email, senha, perfil, ativo, criado_em)
            VALUES (:nome, :email, :senha, :perfil, :ativo, NOW())
        ");
        $stmt->execute([
            ':nome' => $data['nome'],
            ':email' => $data['email'],
            ':senha' => password_hash($data['senha'], PASSWORD_DEFAULT),
            ':perfil' => $data['perfil'],
            ':ativo' => $data['ativo'] ?? 1,
        ]);

        $id = (int)$this->db->lastInsertId();
        $this->audit('create', $userId, [], array_merge($data, ['id' => $id]), 'usuarios', $id);
        return $id;
    }

    public function update(int $id, array $data, int $userId): void
    {
        $before = $this->find($id) ?? [];
        $fields = [
            'nome' => $data['nome'],
            'email' => $data['email'],
            'perfil' => $data['perfil'],
            'ativo' => $data['ativo'] ?? 1,
        ];

        if (!empty($data['senha'])) {
            $fields['senha'] = password_hash($data['senha'], PASSWORD_DEFAULT);
        }

        $setParts = [];
        $params = [':id' => $id];
        foreach ($fields as $key => $value) {
            $setParts[] = "{$key} = :{$key}";
            $params[":{$key}"] = $value;
        }
        $sql = "UPDATE usuarios SET " . implode(', ', $setParts) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $after = $this->find($id) ?? [];
        $this->audit('update', $userId, $before, $after, 'usuarios', $id);
    }

    public function updatePhoto(int $id, string $path, int $userId): void
    {
        $before = $this->find($id) ?? [];
        $stmt = $this->db->prepare("UPDATE usuarios SET foto_path = :foto WHERE id = :id");
        $stmt->execute([
            ':foto' => $path,
            ':id' => $id,
        ]);
        $after = $this->find($id) ?? [];
        $this->audit('update', $userId, $before, $after, 'usuarios', $id);
    }

    public function deactivate(int $id, int $userId): void
    {
        $before = $this->find($id) ?? [];
        $stmt = $this->db->prepare("UPDATE usuarios SET ativo = 0 WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $after = $this->find($id) ?? [];
        $this->audit('deactivate', $userId, $before, $after, 'usuarios', $id);
    }

    public function anonymizeAndDeactivate(int $id, int $userId): void
    {
        $before = $this->find($id) ?? [];
        if (empty($before)) {
            return;
        }

        $token = date('YmdHis') . '_' . $id;
        $anonEmail = 'removido+' . $token . '@anon.local';
        $anonName = 'Usuário removido #' . $id;
        $randomPass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("
            UPDATE usuarios
            SET nome = :nome,
                email = :email,
                senha = :senha,
                foto_path = NULL,
                ativo = 0
            WHERE id = :id
        ");
        $stmt->execute([
            ':id' => $id,
            ':nome' => $anonName,
            ':email' => $anonEmail,
            ':senha' => $randomPass,
        ]);

        $after = $this->find($id) ?? [];
        $this->audit('anonymize_deactivate', $userId, $before, $after, 'usuarios', $id);
    }
}
