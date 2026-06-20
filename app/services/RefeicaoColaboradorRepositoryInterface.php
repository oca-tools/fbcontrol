<?php
declare(strict_types=1);

interface RefeicaoColaboradorRepositoryInterface
{
    /**
     * Persiste o consumo de refeicao de colaborador para conferencia do turno.
     */
    public function registrarRefeicao(array $dadosRefeicao, int $usuarioId): int;
}
