<?php
declare(strict_types=1);

interface RegistrarRefeicaoColaboradorServiceInterface
{
    /**
     * Registra o consumo de colaborador no turno ativo para controle operacional de A&B.
     */
    public function executar(RegistrarRefeicaoColaboradorCommand $command): ServiceResult;
}
