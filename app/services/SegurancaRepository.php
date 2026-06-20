<?php
declare(strict_types=1);

final class SegurancaRepository
{
    /**
     * Valida credenciais sem expor detalhes sobre qual parte da autenticação falhou.
     *
     * @return array<string, mixed>
     */
    public function autenticarCredenciais(string $email, string $senha): array
    {
        return (new UserModel())->authenticateByEmailAndPassword($email, $senha);
    }

    /**
     * Registra evento de segurança preservando a operação mesmo quando a auditoria estiver indisponível.
     */
    public function registrarLogSegurancaSeguro(string $tipoEventoSeguranca, ?int $usuarioId = null, array $contexto = []): void
    {
        try {
            (new AuditoriaRepository())->registrarEvento(
                $tipoEventoSeguranca,
                $usuarioId,
                [],
                $contexto,
                'seguranca',
                null
            );
        } catch (Throwable $e) {
            error_log('[security-log] ' . $tipoEventoSeguranca . ' ' . json_encode(Model::sanitizeAuditPayload($contexto), JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * Lê o controle de tentativas para aplicar bloqueio progressivo contra força bruta.
     *
     * @return array{count: int, first: int, last: int, blocked_until: int}
     */
    public function lerControleTentativas(?string $email): array
    {
        $file = $this->caminhoArquivoTentativas($email);
        if (!is_file($file)) {
            return ['count' => 0, 'first' => 0, 'last' => 0, 'blocked_until' => 0];
        }

        $raw = @file_get_contents($file);
        $data = json_decode((string)$raw, true);
        if (!is_array($data)) {
            return ['count' => 0, 'first' => 0, 'last' => 0, 'blocked_until' => 0];
        }

        return [
            'count' => (int)($data['count'] ?? 0),
            'first' => (int)($data['first'] ?? 0),
            'last' => (int)($data['last'] ?? 0),
            'blocked_until' => (int)($data['blocked_until'] ?? 0),
        ];
    }

    /**
     * Atualiza a janela de tentativas de login de forma atômica e restrita ao usuário/IP.
     *
     * @param array{count: int, first: int, last: int, blocked_until: int} $entry
     */
    public function salvarControleTentativas(?string $email, array $entry): void
    {
        $file = $this->caminhoArquivoTentativas($email);
        if (is_link($file)) {
            return;
        }
        $tmpFile = $file . '.tmp';
        $encoded = json_encode($entry, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return;
        }
        @file_put_contents($tmpFile, $encoded, LOCK_EX);
        @chmod($tmpFile, 0600);
        @rename($tmpFile, $file);
    }

    /**
     * Remove o controle de tentativas após autenticação válida ou expiração da janela.
     */
    public function limparControleTentativas(?string $email): void
    {
        $file = $this->caminhoArquivoTentativas($email);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    private function caminhoArquivoTentativas(?string $email): string
    {
        $base = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . AppConstants::AUTH_THROTTLE_DIR;
        if (!is_dir($base)) {
            @mkdir($base, 0700, true);
        } else {
            @chmod($base, 0700);
        }
        return $base . DIRECTORY_SEPARATOR . $this->chaveControleTentativas($email) . '.json';
    }

    private function chaveControleTentativas(?string $email): string
    {
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $emailKey = mb_strtolower(trim((string)$email), 'UTF-8');
        return hash('sha256', $ip . '|' . $emailKey);
    }
}
