<?php
declare(strict_types=1);

interface EstruturaRestauranteRepositoryInterface
{
    /**
     * Lista restaurantes configurados para as rotinas de A&B.
     */
    public function listarRestaurantes(): array;

    /**
     * Persiste um novo restaurante operacional.
     */
    public function criarRestaurante(array $dadosRestaurante, int $usuarioId): int;

    /**
     * Atualiza regras e status de um restaurante operacional.
     */
    public function atualizarRestaurante(int $restauranteId, array $dadosRestaurante, int $usuarioId): void;

    /**
     * Lista as operacoes disponiveis para vinculo aos restaurantes.
     */
    public function listarOperacoes(): array;

    /**
     * Persiste uma nova operacao de A&B.
     */
    public function criarOperacao(array $dadosOperacao, int $usuarioId): int;

    /**
     * Atualiza status e nome de uma operacao de A&B.
     */
    public function atualizarOperacao(int $operacaoId, array $dadosOperacao, int $usuarioId): void;

    /**
     * Lista portas de acesso configuradas por restaurante.
     */
    public function listarPontosDeAcesso(): array;

    /**
     * Persiste uma porta usada no registro de entrada de hospedes.
     */
    public function criarPontoDeAcesso(array $dadosPontoDeAcesso, int $usuarioId): int;

    /**
     * Verifica se o restaurante ja possui porta equivalente cadastrada.
     */
    public function pontoDeAcessoDuplicado(int $restauranteId, string $nomePontoDeAcesso, int $ignorarPontoDeAcessoId = 0): bool;

    /**
     * Atualiza uma porta de acesso e seu vinculo operacional.
     */
    public function atualizarPontoDeAcesso(int $pontoDeAcessoId, array $dadosPontoDeAcesso, int $usuarioId): void;

    /**
     * Lista a grade de horarios por restaurante e operacao.
     */
    public function listarHorariosDaOperacao(): array;

    /**
     * Persiste o horario em que uma operacao estara ativa para atendimento.
     */
    public function criarHorarioDaOperacao(array $dadosHorario, int $usuarioId): int;

    /**
     * Atualiza horario, tolerancia e status da operacao de restaurante.
     */
    public function atualizarHorarioDaOperacao(int $horarioId, array $dadosHorario, int $usuarioId): void;
}
