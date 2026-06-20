<?php
declare(strict_types=1);

interface RegistrarAcessoServiceInterface
{
    public function executar(RegistrarAcessoCommand $command): ServiceResult;
}
