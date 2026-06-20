<?php
declare(strict_types=1);

interface CriarReservaServiceInterface
{
    /**
     * Processa criação, edição ou lote de reservas temáticas.
     */
    public function executar(CriarReservaCommand $command): ServiceResult;
}
