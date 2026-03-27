<?php
class EmailRelatoriosController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);

        $model = new DailyReportEmailModel();
        $this->view('crud/email_relatorios', [
            'config' => $model->getConfig(),
            'recipients' => $model->listRecipients(),
            'logs' => $model->listLogs(30),
            'flash' => get_flash(),
        ]);
    }

    public function saveConfig(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=emailRelatorios/index');
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=emailRelatorios/index');
        }

        $ativo = (int)($_POST['ativo'] ?? 0) === 1 ? 1 : 0;
        $horaEnvio = trim((string)($_POST['hora_envio'] ?? '23:00'));
        $assunto = trim((string)($_POST['assunto'] ?? 'Resumo diário A&B - {data}'));
        $remetenteNome = trim((string)($_POST['remetente_nome'] ?? 'OCA FBControl'));
        $remetenteEmail = trim((string)($_POST['remetente_email'] ?? ''));
        if (!preg_match('/^\d{2}:\d{2}$/', $horaEnvio) && !preg_match('/^\d{2}:\d{2}:\d{2}$/', $horaEnvio)) {
            set_flash('danger', 'Hora inválida. Use HH:MM.');
            $this->redirect('/?r=emailRelatorios/index');
        }
        if (preg_match('/^\d{2}:\d{2}$/', $horaEnvio)) {
            $horaEnvio .= ':00';
        }
        if ($remetenteEmail !== '' && !filter_var($remetenteEmail, FILTER_VALIDATE_EMAIL)) {
            set_flash('danger', 'E-mail do remetente inválido.');
            $this->redirect('/?r=emailRelatorios/index');
        }

        $model = new DailyReportEmailModel();
        $model->saveConfig([
            'ativo' => $ativo,
            'hora_envio' => $horaEnvio,
            'assunto' => $assunto !== '' ? $assunto : 'Resumo diário A&B - {data}',
            'remetente_nome' => $remetenteNome !== '' ? $remetenteNome : 'OCA FBControl',
            'remetente_email' => $remetenteEmail,
        ], (int)Auth::user()['id']);

        set_flash('success', 'Configuração salva.');
        $this->redirect('/?r=emailRelatorios/index');
    }

    public function addRecipient(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=emailRelatorios/index');
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=emailRelatorios/index');
        }

        $email = trim((string)($_POST['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('danger', 'Informe um e-mail válido.');
            $this->redirect('/?r=emailRelatorios/index');
        }

        $model = new DailyReportEmailModel();
        $model->addRecipient($email, (int)Auth::user()['id']);
        set_flash('success', 'Destinatário adicionado.');
        $this->redirect('/?r=emailRelatorios/index');
    }

    public function removeRecipient(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=emailRelatorios/index');
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=emailRelatorios/index');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            set_flash('danger', 'Destinatário inválido.');
            $this->redirect('/?r=emailRelatorios/index');
        }

        $model = new DailyReportEmailModel();
        $model->removeRecipient($id, (int)Auth::user()['id']);
        set_flash('success', 'Destinatário removido.');
        $this->redirect('/?r=emailRelatorios/index');
    }

    public function sendNow(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=emailRelatorios/index');
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=emailRelatorios/index');
        }

        $dateRef = trim((string)($_POST['data_referencia'] ?? date('Y-m-d')));
        $model = new DailyReportEmailModel();
        $result = $model->sendDailyReport(true, $dateRef);
        set_flash($result['ok'] ? 'success' : 'warning', $result['message'] ?? 'Falha no envio.');
        $this->redirect('/?r=emailRelatorios/index');
    }
}

