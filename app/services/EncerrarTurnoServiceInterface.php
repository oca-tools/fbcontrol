<?php
declare(strict_types=1);

interface EncerrarTurnoServiceInterface
{
    public function executar(EncerrarTurnoCommand $command): ServiceResult;
}
