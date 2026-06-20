<?php
declare(strict_types=1);

interface RegistrarOnboardingEquipeServiceInterface
{
    /**
     * Registra a evolucao do colaborador nos protocolos de onboarding da equipe.
     */
    public function executar(RegistrarOnboardingEquipeCommand $command): ServiceResult;
}
