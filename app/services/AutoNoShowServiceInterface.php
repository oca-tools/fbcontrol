<?php
declare(strict_types=1);

interface AutoNoShowServiceInterface
{
    /**
     * Aplica no-show automático nas reservas vencidas pela tolerância configurada.
     */
    public function executar(AutoNoShowCommand $command): ServiceResult;
}
