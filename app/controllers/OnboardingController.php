<?php
class OnboardingController extends Controller
{
    public function hostessSeen(): void
    {
        $this->requireAuth();
        Auth::requireRole(['hostess', 'admin', 'supervisor']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['ok' => false, 'message' => 'Método inválido.'], 405);
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            json_response(['ok' => false, 'message' => 'Token inválido.'], 400);
        }

        $user = Auth::user();
        (new UserOnboardingModel())->markHostessSeen((int)$user['id']);
        json_response(['ok' => true]);
    }

    public function hostessComplete(): void
    {
        $this->requireAuth();
        Auth::requireRole(['hostess', 'admin', 'supervisor']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['ok' => false, 'message' => 'Método inválido.'], 405);
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            json_response(['ok' => false, 'message' => 'Token inválido.'], 400);
        }

        $user = Auth::user();
        (new UserOnboardingModel())->completeHostessTutorial((int)$user['id']);
        json_response(['ok' => true]);
    }
}

