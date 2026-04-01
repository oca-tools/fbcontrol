<?php
class UserOnboardingModel extends Model
{
    private static ?bool $tableReady = null;

    private function ensureTable(): bool
    {
        if (self::$tableReady !== null) {
            return self::$tableReady;
        }

        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS usuarios_onboarding (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    hostess_tutorial_seen TINYINT(1) NOT NULL DEFAULT 0,
                    hostess_tutorial_completed TINYINT(1) NOT NULL DEFAULT 0,
                    hostess_tutorial_completed_em DATETIME NULL,
                    criado_em DATETIME NOT NULL,
                    atualizado_em DATETIME NOT NULL,
                    UNIQUE KEY uq_onboarding_usuario (usuario_id),
                    CONSTRAINT fk_onboarding_usuario
                        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
                        ON DELETE CASCADE
                )
            ");
            self::$tableReady = true;
        } catch (Throwable $e) {
            self::$tableReady = false;
        }

        return self::$tableReady;
    }

    public function getByUser(int $userId): array
    {
        if (!$this->ensureTable()) {
            return [
                'usuario_id' => $userId,
                'hostess_tutorial_seen' => 0,
                'hostess_tutorial_completed' => 0,
                'hostess_tutorial_completed_em' => null,
            ];
        }
        $stmt = $this->db->prepare("SELECT * FROM usuarios_onboarding WHERE usuario_id = :usuario_id LIMIT 1");
        $stmt->execute([':usuario_id' => $userId]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }

        $insert = $this->db->prepare("
            INSERT INTO usuarios_onboarding (usuario_id, criado_em, atualizado_em)
            VALUES (:usuario_id, NOW(), NOW())
        ");
        $insert->execute([':usuario_id' => $userId]);

        $stmt->execute([':usuario_id' => $userId]);
        return $stmt->fetch() ?: [
            'usuario_id' => $userId,
            'hostess_tutorial_seen' => 0,
            'hostess_tutorial_completed' => 0,
            'hostess_tutorial_completed_em' => null,
        ];
    }

    public function markHostessSeen(int $userId): void
    {
        if (!$this->ensureTable()) {
            return;
        }
        $stmt = $this->db->prepare("
            INSERT INTO usuarios_onboarding
                (usuario_id, hostess_tutorial_seen, criado_em, atualizado_em)
            VALUES
                (:usuario_id, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                hostess_tutorial_seen = 1,
                atualizado_em = NOW()
        ");
        $stmt->execute([':usuario_id' => $userId]);
    }

    public function completeHostessTutorial(int $userId): void
    {
        if (!$this->ensureTable()) {
            return;
        }
        $stmt = $this->db->prepare("
            INSERT INTO usuarios_onboarding
                (usuario_id, hostess_tutorial_seen, hostess_tutorial_completed, hostess_tutorial_completed_em, criado_em, atualizado_em)
            VALUES
                (:usuario_id, 1, 1, NOW(), NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                hostess_tutorial_seen = 1,
                hostess_tutorial_completed = 1,
                hostess_tutorial_completed_em = NOW(),
                atualizado_em = NOW()
        ");
        $stmt->execute([':usuario_id' => $userId]);
    }
}
