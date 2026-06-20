<?php
declare(strict_types=1);

final class RegistrarOnboardingEquipeCommand
{
    public string $acao;
    public int $usuarioId;

    /**
     * Transporta o evento de protocolo visualizado ou concluido pelo colaborador.
     */
    public function __construct(array $dados)
    {
        $this->acao = (string)($dados['acao'] ?? '');
        $this->usuarioId = (int)($dados['usuario_id'] ?? 0);
    }
}
