<?php
declare(strict_types=1);

class EmailRelatoriosController extends Controller
{
    /**
     * Exibe a governança do envio diário de indicadores de A&B para a liderança.
     */
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);

        $model = new DailyReportEmailModel();
        $this->view('crud/email_relatorios', [
            'config' => $model->getConfig(),
            'recipients' => $model->listRecipients(),
            'logs' => $model->listLogs(InteligenciaOperacionalConstants::DEFAULT_EMAIL_LOG_LIMIT),
            'flash' => get_flash(),
        ]);
    }

    /**
     * Atualiza horário, assunto e remetente do resumo diário enviado à gestão.
     */
    public function saveConfig(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(InteligenciaOperacionalConstants::ROUTE_EMAIL_RELATORIOS_INDEX);
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash(InteligenciaOperacionalConstants::FLASH_DANGER, InteligenciaOperacionalConstants::MESSAGE_TOKEN_INVALIDO);
            $this->redirect(InteligenciaOperacionalConstants::ROUTE_EMAIL_RELATORIOS_INDEX);
        }

        $ativo = (int)($_POST['ativo'] ?? 0) === 1 ? 1 : 0;
        $horaEnvio = trim((string)($_POST['hora_envio'] ?? InteligenciaOperacionalConstants::DEFAULT_EMAIL_SEND_TIME_SHORT));
        $assunto = trim((string)($_POST['assunto'] ?? InteligenciaOperacionalConstants::DEFAULT_DAILY_REPORT_SUBJECT));
        $remetenteNome = trim((string)($_POST['remetente_nome'] ?? InteligenciaOperacionalConstants::DEFAULT_EMAIL_SENDER_NAME));
        $remetenteEmail = trim((string)($_POST['remetente_email'] ?? ''));
        if (!preg_match('/^\d{2}:\d{2}$/', $horaEnvio) && !preg_match('/^\d{2}:\d{2}:\d{2}$/', $horaEnvio)) {
            set_flash(InteligenciaOperacionalConstants::FLASH_DANGER, InteligenciaOperacionalConstants::MESSAGE_HORA_INVALIDA);
            $this->redirect(InteligenciaOperacionalConstants::ROUTE_EMAIL_RELATORIOS_INDEX);
        }
        if (preg_match('/^\d{2}:\d{2}$/', $horaEnvio)) {
            $horaEnvio .= ':00';
        }
        if ($remetenteEmail !== '' && !filter_var($remetenteEmail, FILTER_VALIDATE_EMAIL)) {
            set_flash(InteligenciaOperacionalConstants::FLASH_DANGER, InteligenciaOperacionalConstants::MESSAGE_REMETENTE_INVALIDO);
            $this->redirect(InteligenciaOperacionalConstants::ROUTE_EMAIL_RELATORIOS_INDEX);
        }

        $model = new DailyReportEmailModel();
        $model->saveConfig([
            'ativo' => $ativo,
            'hora_envio' => $horaEnvio,
            'assunto' => $assunto !== '' ? $assunto : InteligenciaOperacionalConstants::DEFAULT_DAILY_REPORT_SUBJECT,
            'remetente_nome' => $remetenteNome !== '' ? $remetenteNome : InteligenciaOperacionalConstants::DEFAULT_EMAIL_SENDER_NAME,
            'remetente_email' => $remetenteEmail,
        ], (int)Auth::user()['id']);

        set_flash(InteligenciaOperacionalConstants::FLASH_SUCCESS, InteligenciaOperacionalConstants::MESSAGE_CONFIG_SALVA);
        $this->redirect(InteligenciaOperacionalConstants::ROUTE_EMAIL_RELATORIOS_INDEX);
    }

    /**
     * Inclui um destinatário da liderança na rotina de resumo diário de A&B.
     */
    public function addRecipient(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(InteligenciaOperacionalConstants::ROUTE_EMAIL_RELATORIOS_INDEX);
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash(InteligenciaOperacionalConstants::FLASH_DANGER, InteligenciaOperacionalConstants::MESSAGE_TOKEN_INVALIDO);
            $this->redirect(InteligenciaOperacionalConstants::ROUTE_EMAIL_RELATORIOS_INDEX);
        }

        $email = trim((string)($_POST['email'] ?? ''));
        $receberAnexoVouchers = (int)($_POST['receber_anexo_vouchers'] ?? 0) === 1;
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash(InteligenciaOperacionalConstants::FLASH_DANGER, InteligenciaOperacionalConstants::MESSAGE_EMAIL_INVALIDO);
            $this->redirect(InteligenciaOperacionalConstants::ROUTE_EMAIL_RELATORIOS_INDEX);
        }

        $model = new DailyReportEmailModel();
        $model->addRecipient($email, (int)Auth::user()['id'], $receberAnexoVouchers);
        set_flash(InteligenciaOperacionalConstants::FLASH_SUCCESS, InteligenciaOperacionalConstants::MESSAGE_DESTINATARIO_ADICIONADO);
        $this->redirect(InteligenciaOperacionalConstants::ROUTE_EMAIL_RELATORIOS_INDEX);
    }

    /**
     * Ajusta se um destinatário recebe anexos de vouchers no resumo diário.
     */
    public function updateRecipientAttachment(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(InteligenciaOperacionalConstants::ROUTE_EMAIL_RELATORIOS_INDEX);
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash(InteligenciaOperacionalConstants::FLASH_DANGER, InteligenciaOperacionalConstants::MESSAGE_TOKEN_INVALIDO);
            $this->redirect(InteligenciaOperacionalConstants::ROUTE_EMAIL_RELATORIOS_INDEX);
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            set_flash(InteligenciaOperacionalConstants::FLASH_DANGER, InteligenciaOperacionalConstants::MESSAGE_DESTINATARIO_INVALIDO);
            $this->redirect(InteligenciaOperacionalConstants::ROUTE_EMAIL_RELATORIOS_INDEX);
        }
        $enabled = (int)($_POST['receber_anexo_vouchers'] ?? 0) === 1;

        $model = new DailyReportEmailModel();
        $model->updateRecipientAttachmentFlag($id, $enabled, (int)Auth::user()['id']);
        set_flash(InteligenciaOperacionalConstants::FLASH_SUCCESS, InteligenciaOperacionalConstants::MESSAGE_PREFERENCIA_ANEXO_ATUALIZADA);
        $this->redirect(InteligenciaOperacionalConstants::ROUTE_EMAIL_RELATORIOS_INDEX);
    }

    /**
     * Remove um destinatário do circuito de comunicação gerencial diária.
     */
    public function removeRecipient(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(InteligenciaOperacionalConstants::ROUTE_EMAIL_RELATORIOS_INDEX);
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash(InteligenciaOperacionalConstants::FLASH_DANGER, InteligenciaOperacionalConstants::MESSAGE_TOKEN_INVALIDO);
            $this->redirect(InteligenciaOperacionalConstants::ROUTE_EMAIL_RELATORIOS_INDEX);
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            set_flash(InteligenciaOperacionalConstants::FLASH_DANGER, InteligenciaOperacionalConstants::MESSAGE_DESTINATARIO_INVALIDO);
            $this->redirect(InteligenciaOperacionalConstants::ROUTE_EMAIL_RELATORIOS_INDEX);
        }

        $model = new DailyReportEmailModel();
        $model->removeRecipient($id, (int)Auth::user()['id']);
        set_flash(InteligenciaOperacionalConstants::FLASH_SUCCESS, InteligenciaOperacionalConstants::MESSAGE_DESTINATARIO_REMOVIDO);
        $this->redirect(InteligenciaOperacionalConstants::ROUTE_EMAIL_RELATORIOS_INDEX);
    }

    /**
     * Dispara manualmente o resumo diário para validar ou antecipar a comunicação de indicadores.
     */
    public function sendNow(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(InteligenciaOperacionalConstants::ROUTE_EMAIL_RELATORIOS_INDEX);
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash(InteligenciaOperacionalConstants::FLASH_DANGER, InteligenciaOperacionalConstants::MESSAGE_TOKEN_INVALIDO);
            $this->redirect(InteligenciaOperacionalConstants::ROUTE_EMAIL_RELATORIOS_INDEX);
        }

        $dateRef = trim((string)($_POST['data_referencia'] ?? date('Y-m-d')));
        $model = new DailyReportEmailModel();
        $result = $model->sendDailyReport(true, $dateRef);
        set_flash(
            $result['ok'] ? InteligenciaOperacionalConstants::FLASH_SUCCESS : InteligenciaOperacionalConstants::FLASH_WARNING,
            $result['message'] ?? InteligenciaOperacionalConstants::MESSAGE_FALHA_ENVIO
        );
        $this->redirect(InteligenciaOperacionalConstants::ROUTE_EMAIL_RELATORIOS_INDEX);
    }
}
