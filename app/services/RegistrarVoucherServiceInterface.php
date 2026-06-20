<?php
declare(strict_types=1);

interface RegistrarVoucherServiceInterface
{
    /**
     * Registra voucher com comprovante para conferencia financeira e operacional de A&B.
     */
    public function executar(RegistrarVoucherCommand $command): ServiceResult;
}
