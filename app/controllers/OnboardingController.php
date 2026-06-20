<?php
declare(strict_types=1);

class OnboardingController extends Controller
{
    /**
     * Registra que o colaborador visualizou o protocolo inicial de atendimento da equipe.
     */
    public function hostessSeen(): void
    {
        $this->requireAuth();
        Auth::requireRole([
            ProtocolosEquipeConstants::PROFILE_HOSTESS,
            ProtocolosEquipeConstants::PROFILE_ADMIN,
            ProtocolosEquipeConstants::PROFILE_SUPERVISOR,
        ]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['ok' => false, 'message' => ProtocolosEquipeConstants::MESSAGE_METHOD_INVALID], ProtocolosEquipeConstants::HTTP_METHOD_NOT_ALLOWED);
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            json_response(['ok' => false, 'message' => ProtocolosEquipeConstants::MESSAGE_TOKEN_INVALID], ProtocolosEquipeConstants::HTTP_BAD_REQUEST);
        }

        $user = Auth::user();
        $resultado = (new RegistrarOnboardingEquipeService())->executar(new RegistrarOnboardingEquipeCommand([
            'acao' => ProtocolosEquipeConstants::ACTION_ONBOARDING_SEEN,
            'usuario_id' => (int)$user['id'],
        ]));
        json_response(['ok' => $resultado->isSuccess(), 'message' => $resultado->message()]);
    }

    /**
     * Registra a conclusao do onboarding do colaborador para liberar o fluxo operacional habitual.
     */
    public function hostessComplete(): void
    {
        $this->requireAuth();
        Auth::requireRole([
            ProtocolosEquipeConstants::PROFILE_HOSTESS,
            ProtocolosEquipeConstants::PROFILE_ADMIN,
            ProtocolosEquipeConstants::PROFILE_SUPERVISOR,
        ]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['ok' => false, 'message' => ProtocolosEquipeConstants::MESSAGE_METHOD_INVALID], ProtocolosEquipeConstants::HTTP_METHOD_NOT_ALLOWED);
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            json_response(['ok' => false, 'message' => ProtocolosEquipeConstants::MESSAGE_TOKEN_INVALID], ProtocolosEquipeConstants::HTTP_BAD_REQUEST);
        }

        $user = Auth::user();
        $resultado = (new RegistrarOnboardingEquipeService())->executar(new RegistrarOnboardingEquipeCommand([
            'acao' => ProtocolosEquipeConstants::ACTION_ONBOARDING_COMPLETE,
            'usuario_id' => (int)$user['id'],
        ]));
        json_response(['ok' => $resultado->isSuccess(), 'message' => $resultado->message()]);
    }
}
