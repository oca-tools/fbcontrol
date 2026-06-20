<?php
declare(strict_types=1);

final class GerenciarUsuarioEquipeCommand
{
    public string $acao;
    public array $gestor;
    public int $usuarioId;
    public string $nome;
    public string $email;
    public string $senha;
    public string $perfil;
    public int $ativo;
    public array $assignments;

    /**
     * Transporta os dados de cadastro e manutencao de colaborador da equipe de A&B.
     */
    public function __construct(array $dados)
    {
        $this->acao = (string)($dados['acao'] ?? '');
        $this->gestor = $dados['gestor'] ?? [];
        $this->usuarioId = (int)($dados['usuario_id'] ?? 0);
        $this->nome = trim((string)($dados['nome'] ?? ''));
        $this->email = trim((string)($dados['email'] ?? ''));
        $this->senha = (string)($dados['senha'] ?? '');
        $this->perfil = (string)($dados['perfil'] ?? ProtocolosEquipeConstants::PROFILE_HOSTESS);
        $this->ativo = (int)($dados['ativo'] ?? ProtocolosEquipeConstants::USER_STATUS_ACTIVE);
        $this->assignments = is_array($dados['assignments'] ?? null) ? $dados['assignments'] : [];
    }
}
