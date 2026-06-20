<?php
declare(strict_types=1);

interface OperarReservaServiceInterface
{
    /**
     * Processa ações operacionais das reservas durante o atendimento A&B.
     */
    public function executar(OperarReservaCommand $command): ServiceResult;
}
