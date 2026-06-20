<?php
declare(strict_types=1);

class LgpdController extends Controller
{
    private GovernancaAuthSession $authSession;

    public function __construct()
    {
        $this->authSession = new GovernancaAuthSession();
    }

    /**
     * Exibe o painel LGPD para controlar solicitações de titulares, incidentes e políticas de retenção.
     */
    public function index(): void
    {
        $this->requireAuth();
        $this->authSession->requireRole([GovernancaConstants::ROLE_ADMIN]);

        $this->view('crud/lgpd', (new GerenciarLgpdService())->montarPainel($_GET, $this->authSession->user() ?? []));
    }

    /**
     * Atualiza controlador, encarregado e prazos de atendimento definidos para conformidade LGPD.
     */
    public function saveConfig(): void
    {
        $this->requirePostComCsrf();
        $resultado = (new GerenciarLgpdService())->salvarConfiguracao($_POST, $this->usuarioIdAtual());
        $this->aplicarResultadoLgpd($resultado);
    }

    /**
     * Registra uma solicitação de titular para garantir rastreio do pedido e prazo de resposta.
     */
    public function addRequest(): void
    {
        $this->requirePostComCsrf();
        $resultado = (new GerenciarLgpdService())->criarSolicitacao($_POST, $this->usuarioIdAtual());
        $this->aplicarResultadoLgpd($resultado);
    }

    /**
     * Atualiza o tratamento de uma solicitação LGPD e preserva trilha de decisão administrativa.
     */
    public function updateRequest(): void
    {
        $this->requirePostComCsrf();
        $resultado = (new GerenciarLgpdService())->atualizarSolicitacao($_POST, $this->usuarioIdAtual());
        $this->aplicarResultadoLgpd($resultado);
    }

    /**
     * Registra um incidente de privacidade para controle de risco e comunicação legal.
     */
    public function addIncident(): void
    {
        $this->requirePostComCsrf();
        $resultado = (new GerenciarLgpdService())->criarIncidente($_POST, $this->usuarioIdAtual());
        $this->aplicarResultadoLgpd($resultado);
    }

    /**
     * Atualiza evidências e encerramento do incidente para comprovar resposta à ocorrência.
     */
    public function updateIncident(): void
    {
        $this->requirePostComCsrf();
        $resultado = (new GerenciarLgpdService())->atualizarIncidente($_POST, $this->usuarioIdAtual());
        $this->aplicarResultadoLgpd($resultado);
    }

    /**
     * Define por quanto tempo cada trilha regulatória permanece disponível antes da limpeza.
     */
    public function saveRetention(): void
    {
        $this->requirePostComCsrf();
        $resultado = (new GerenciarLgpdService())->salvarPoliticaRetencao($_POST, $this->usuarioIdAtual());
        $this->aplicarResultadoLgpd($resultado);
    }

    /**
     * Executa a retenção imediatamente para remover dados além do prazo legal configurado.
     */
    public function runRetentionNow(): void
    {
        $this->requirePostComCsrf();
        $resultado = (new GerenciarLgpdService())->executarRetencaoAgora($this->usuarioIdAtual());
        $this->aplicarResultadoLgpd($resultado, [GovernancaConstants::STATUS_RETENCAO_COM_ALERTAS]);
    }

    private function requirePostComCsrf(): void
    {
        $this->requireAuth();
        $this->authSession->requireRole([GovernancaConstants::ROLE_ADMIN]);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(GovernancaConstants::ROUTE_LGPD_INDEX);
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash(GovernancaConstants::FLASH_DANGER, GovernancaConstants::MESSAGE_TOKEN_INVALIDO);
            $this->redirect(GovernancaConstants::ROUTE_LGPD_INDEX);
        }
    }

    private function aplicarResultadoLgpd(ServiceResult $resultado, array $codigosWarning = []): void
    {
        if ($resultado->isSuccess()) {
            set_flash(GovernancaConstants::FLASH_SUCCESS, $resultado->message());
        } else {
            $tipoFlash = in_array(
                $resultado->code(),
                array_merge([GovernancaConstants::STATUS_REGISTRO_NAO_ENCONTRADO], $codigosWarning),
                true
            )
                ? GovernancaConstants::FLASH_WARNING
                : GovernancaConstants::FLASH_DANGER;
            set_flash($tipoFlash, $resultado->message());
        }
        $this->redirect(GovernancaConstants::ROUTE_LGPD_INDEX);
    }

    private function usuarioIdAtual(): int
    {
        $usuario = $this->authSession->user();
        return (int)($usuario['id'] ?? 0);
    }
}
