<?php
declare(strict_types=1);

final class AutenticarUsuarioService
{
    private SegurancaRepository $segurancaRepository;
    private GovernancaAuthSession $authSession;

    public function __construct(?SegurancaRepository $segurancaRepository = null, ?GovernancaAuthSession $authSession = null)
    {
        $this->segurancaRepository = $segurancaRepository ?? new SegurancaRepository();
        $this->authSession = $authSession ?? new GovernancaAuthSession();
    }

    /**
     * Autentica o operador e registra sucesso, falha ou bloqueio para rastreabilidade de acesso.
     */
    public function autenticar(string $email, string $senha): ServiceResult
    {
        $emailOperador = trim($email);
        if ($emailOperador === '' || $senha === '') {
            return ServiceResult::failure('credenciais_incompletas', GovernancaConstants::MESSAGE_CREDENCIAIS_INCOMPLETAS);
        }

        $segundosBloqueio = $this->segundosBloqueioLogin($emailOperador);
        $limiteDeTentativasDeAcessoExcedido = $segundosBloqueio > 0;
        if ($limiteDeTentativasDeAcessoExcedido) {
            $this->segurancaRepository->registrarLogSegurancaSeguro(GovernancaConstants::AUDIT_LOGIN_BLOCKED, null, $this->contextoLogin($emailOperador, [
                'blocked_seconds' => $segundosBloqueio,
            ]));
            $minutos = max(1, (int)ceil($segundosBloqueio / 60));
            return ServiceResult::failure(
                'login_bloqueado',
                GovernancaConstants::MESSAGE_LOGIN_BLOQUEADO_PREFIX
                . $minutos
                . GovernancaConstants::MESSAGE_LOGIN_BLOQUEADO_SUFFIX
            );
        }

        $resultadoAutenticacao = $this->segurancaRepository->autenticarCredenciais($emailOperador, $senha);
        $statusCredencial = (string)($resultadoAutenticacao['status'] ?? 'invalid');
        $usuarioOperacao = $resultadoAutenticacao['user'] ?? null;

        if ($statusCredencial === 'ok' && is_array($usuarioOperacao)) {
            $this->segurancaRepository->limparControleTentativas($emailOperador);
            $this->authSession->login($usuarioOperacao);
            $this->segurancaRepository->registrarLogSegurancaSeguro(
                GovernancaConstants::AUDIT_LOGIN_SUCCESS,
                (int)$usuarioOperacao['id'],
                $this->contextoLogin($emailOperador)
            );
            return ServiceResult::success(GovernancaConstants::MESSAGE_LOGIN_SUCESSO, ['usuario_id' => (int)$usuarioOperacao['id']]);
        }

        if ($statusCredencial === 'ambiguous') {
            $this->segurancaRepository->registrarLogSegurancaSeguro(GovernancaConstants::AUDIT_LOGIN_AMBIGUOUS, null, $this->contextoLogin($emailOperador));
            return ServiceResult::failure('credencial_ambigua', GovernancaConstants::MESSAGE_CREDENCIAL_AMBIGUA);
        }

        $this->registrarFalhaLogin($emailOperador);
        $this->segurancaRepository->registrarLogSegurancaSeguro(GovernancaConstants::AUDIT_LOGIN_FAILED, null, $this->contextoLogin($emailOperador));
        return ServiceResult::failure('credenciais_invalidas', GovernancaConstants::MESSAGE_CREDENCIAIS_INVALIDAS);
    }

    /**
     * Registra o encerramento da sessão para comprovar a troca de responsabilidade operacional.
     *
     * @param array<string, mixed>|null $usuario
     */
    public function registrarLogout(?array $usuario): void
    {
        $this->segurancaRepository->registrarLogSegurancaSeguro(GovernancaConstants::AUDIT_LOGOUT, (int)($usuario['id'] ?? 0), [
            'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
        ]);
        $this->authSession->logout();
    }

    private function segundosBloqueioLogin(string $email): int
    {
        return max($this->segundosBloqueio($email), $this->segundosBloqueio(null));
    }

    private function segundosBloqueio(?string $email): int
    {
        $entry = $this->segurancaRepository->lerControleTentativas($email);
        $agora = time();
        if (($agora - (int)$entry['first']) > AppConstants::AUTH_THROTTLE_WINDOW_SECONDS) {
            $this->segurancaRepository->limparControleTentativas($email);
            return 0;
        }
        return max(0, (int)$entry['blocked_until'] - $agora);
    }

    private function registrarFalhaLogin(string $email): void
    {
        $this->registrarFalhaNaChave($email);
        $this->registrarFalhaNaChave(null);
    }

    private function registrarFalhaNaChave(?string $email): void
    {
        $entry = $this->segurancaRepository->lerControleTentativas($email);
        $agora = time();
        if (($agora - (int)$entry['first']) > AppConstants::AUTH_THROTTLE_WINDOW_SECONDS) {
            $entry = ['count' => 0, 'first' => $agora, 'last' => 0, 'blocked_until' => 0];
        }
        if ((int)$entry['first'] <= 0) {
            $entry['first'] = $agora;
        }

        $entry['count'] = (int)$entry['count'] + 1;
        $entry['last'] = $agora;
        if ((int)$entry['count'] >= AppConstants::AUTH_THROTTLE_LIMIT) {
            $tentativasAcimaDoLimite = (int)$entry['count'] - AppConstants::AUTH_THROTTLE_LIMIT;
            $tempoDeEspera = min(
                AppConstants::AUTH_THROTTLE_MAX_BACKOFF_SECONDS,
                (int)(AppConstants::AUTH_THROTTLE_MIN_BACKOFF_SECONDS * (2 ** $tentativasAcimaDoLimite))
            );
            $entry['blocked_until'] = max((int)$entry['blocked_until'], $agora + $tempoDeEspera);
        }

        $this->segurancaRepository->salvarControleTentativas($email, $entry);
    }

    private function contextoLogin(string $email, array $extra = []): array
    {
        return array_merge([
            'email' => mb_strtolower($email, 'UTF-8'),
            'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
        ], $extra);
    }
}
