<?php
declare(strict_types=1);

final class RegistrarOnboardingEquipeService implements RegistrarOnboardingEquipeServiceInterface
{
    private ProtocolosEquipeRepositoryInterface $repository;

    public function __construct(?ProtocolosEquipeRepositoryInterface $repository = null)
    {
        $this->repository = $repository ?? new ProtocolosEquipeRepository();
    }

    /**
     * Registra a evolucao do colaborador nos protocolos de onboarding da equipe.
     */
    public function executar(RegistrarOnboardingEquipeCommand $command): ServiceResult
    {
        if ($command->usuarioId <= 0) {
            return ServiceResult::failure(
                ProtocolosEquipeConstants::CODE_USER_INVALID,
                ProtocolosEquipeConstants::MESSAGE_USER_INVALID
            );
        }

        if (!in_array($command->acao, [
            ProtocolosEquipeConstants::ACTION_ONBOARDING_SEEN,
            ProtocolosEquipeConstants::ACTION_ONBOARDING_COMPLETE,
        ], true)) {
            return ServiceResult::failure(
                ProtocolosEquipeConstants::CODE_METHOD_INVALID,
                ProtocolosEquipeConstants::MESSAGE_METHOD_INVALID
            );
        }

        $this->repository->registrarOnboarding($command->usuarioId, $command->acao);

        return ServiceResult::success(
            $command->acao === ProtocolosEquipeConstants::ACTION_ONBOARDING_COMPLETE
                ? ProtocolosEquipeConstants::MESSAGE_ONBOARDING_COMPLETE
                : ProtocolosEquipeConstants::MESSAGE_ONBOARDING_SEEN,
            ['acao' => $command->acao]
        );
    }
}
