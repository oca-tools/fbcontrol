<?php
declare(strict_types=1);

final class ConfigurarRestauranteCommand
{
    public string $tipoCadastro;
    public string $acao;
    public int $registroId;
    public int $usuarioId;
    public string $nome;
    public string $tipoRestaurante;
    public bool $restauranteSelecionaPortaNoTurno;
    public bool $restauranteExigePax;
    public bool $cadastroAtivo;

    /**
     * Normaliza os dados de cadastro de restaurante ou operacao de A&B.
     */
    public function __construct(array $dados)
    {
        $this->tipoCadastro = (string)($dados['tipo_cadastro'] ?? GestaoRestaurantesConstants::REGISTER_TYPE_RESTAURANTE);
        $this->acao = (string)($dados['acao'] ?? GestaoRestaurantesConstants::ACTION_CREATE);
        $this->registroId = (int)($dados['id'] ?? 0);
        $this->usuarioId = (int)($dados['usuario_id'] ?? 0);
        $this->nome = trim((string)($dados['nome'] ?? ''));
        $this->tipoRestaurante = (string)($dados['tipo'] ?? GestaoRestaurantesConstants::RESTAURANT_TYPE_BUFFET);
        $this->restauranteSelecionaPortaNoTurno = (int)($dados['seleciona_porta_no_turno'] ?? GestaoRestaurantesConstants::STATUS_INACTIVE) === GestaoRestaurantesConstants::STATUS_ACTIVE;
        $this->restauranteExigePax = (int)($dados['exige_pax'] ?? GestaoRestaurantesConstants::STATUS_INACTIVE) === GestaoRestaurantesConstants::STATUS_ACTIVE;
        $this->cadastroAtivo = (int)($dados['ativo'] ?? GestaoRestaurantesConstants::STATUS_ACTIVE) === GestaoRestaurantesConstants::STATUS_ACTIVE;
    }
}
