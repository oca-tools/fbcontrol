<?php
declare(strict_types=1);

interface AccessRepositoryInterface
{
    /**
     * Persiste a entrada de hóspedes no salão e devolve alertas operacionais da entrada.
     */
    public function registrarEntradaSalao(array $entradaSalao, int $usuarioId): array;

    /**
     * Calcula o PAX da UH ja consumido na operação do dia.
     */
    public function totalPaxDaUhNaOperacaoDoDia(string $uhNumero, int $operacaoId, string $dataOperacao): int;

    /**
     * Indica se a entrada atual repete o mesmo lançamento no turno da hostess.
     */
    public function existeDuplicidadeImediata(string $uhNumero, int $restauranteId, int $operacaoId, int $pax, int $turnoId, int $usuarioId): bool;

    /**
     * Conta lançamentos reais do turno para apoiar regras de cancelamento.
     */
    public function quantidadeLancamentosDoTurno(int $turnoId): int;

    /**
     * Lista os lançamentos recentes do salão para painel operacional.
     */
    public function ultimosLancamentosDoSalao(int $limit = ControleSalaoConstants::DEFAULT_ACCESS_LIST_LIMIT): array;
}
