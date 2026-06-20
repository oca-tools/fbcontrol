<?php
declare(strict_types=1);

final class ConfigurarPontoAcessoCommand
{
    public string $acao;
    public int $registroId;
    public int $usuarioId;
    public int $restauranteId;
    public string $nomePontoDeAcesso;
    public bool $pontoDeAcessoAtivo;

    /**
     * Normaliza os dados de porta/ponto de acesso vinculado a um restaurante.
     */
    public function __construct(array $dados)
    {
        $this->acao = (string)($dados['acao'] ?? GestaoRestaurantesConstants::ACTION_CREATE);
        $this->registroId = (int)($dados['id'] ?? 0);
        $this->usuarioId = (int)($dados['usuario_id'] ?? 0);
        $this->restauranteId = (int)($dados['restaurante_id'] ?? 0);
        $this->nomePontoDeAcesso = trim((string)($dados['nome'] ?? ''));
        $this->pontoDeAcessoAtivo = (int)($dados['ativo'] ?? GestaoRestaurantesConstants::STATUS_ACTIVE) === GestaoRestaurantesConstants::STATUS_ACTIVE;
    }
}
