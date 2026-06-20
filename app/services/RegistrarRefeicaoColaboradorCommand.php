<?php
declare(strict_types=1);

final class RegistrarRefeicaoColaboradorCommand
{
    public array $usuario;
    public array $turno;
    public string $nomeColaborador;
    public int $quantidadeRefeicoes;

    /**
     * Transporta os dados do lancamento de refeicao de colaborador dentro do turno ativo.
     */
    public function __construct(array $dados)
    {
        $this->usuario = $dados['usuario'] ?? [];
        $this->turno = $dados['turno'] ?? [];
        $this->nomeColaborador = trim((string)($dados['nome_colaborador'] ?? ''));
        $this->quantidadeRefeicoes = (int)($dados['quantidade'] ?? 0);
    }
}
