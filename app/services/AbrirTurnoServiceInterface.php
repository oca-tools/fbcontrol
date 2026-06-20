<?php
declare(strict_types=1);

interface AbrirTurnoServiceInterface
{
    public function executar(AbrirTurnoCommand $command): ServiceResult;
}
