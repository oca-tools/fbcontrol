<?php
declare(strict_types=1);

class AuditoriaController extends Controller
{
    private GovernancaAuthSession $authSession;

    public function __construct()
    {
        $this->authSession = new GovernancaAuthSession();
    }

    /**
     * Exibe trilhas de auditoria para supervisão de alterações, acessos e encerramentos de turno.
     */
    public function index(): void
    {
        $this->requireAuth();
        $this->authSession->requireRole([GovernancaConstants::ROLE_ADMIN, GovernancaConstants::ROLE_GERENTE]);

        $this->view('auditoria/index', (new RegistrarEventoAuditoriaService())->montarPainel($_GET));
    }
}
