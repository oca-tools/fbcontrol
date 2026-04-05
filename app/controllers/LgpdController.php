<?php
class LgpdController extends Controller
{
    private function handleFailure(string $publicMessage, Throwable $e, string $event): void
    {
        try {
            (new SecurityLogModel())->log($event, (int)(Auth::user()['id'] ?? 0), [
                'exception' => get_class($e),
                'message' => mb_substr((string)$e->getMessage(), 0, 300, 'UTF-8'),
                'route' => (string)($_GET['r'] ?? ''),
                'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            ]);
        } catch (Throwable $ignored) {
            // Nao interrompe o fluxo de erro amigavel.
        }

        set_flash('danger', $publicMessage);
    }

    private function canEdit(): bool
    {
        $perfil = strtolower((string)(Auth::user()['perfil'] ?? ''));
        return in_array($perfil, ['admin', 'supervisor'], true);
    }

    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $filters = [
            'status' => trim((string)($_GET['status'] ?? '')),
            'risk' => trim((string)($_GET['risk'] ?? '')),
            'date_from' => trim((string)($_GET['date_from'] ?? '')),
            'date_to' => trim((string)($_GET['date_to'] ?? '')),
        ];
        $dbError = '';
        $payload = [
            'can_edit' => $this->canEdit(),
            'summary' => [
                'requests_open' => 0,
                'requests_due_soon' => 0,
                'incidents_open' => 0,
            ],
            'config' => [
                'controlador_nome' => 'Grand Oca Maragogi Resort',
                'canal_titular_url' => '/?r=privacidade/index',
                'prazo_titular_dias' => 15,
                'prazo_incidente_dias_uteis' => 3,
            ],
            'requests' => [],
            'incidents' => [],
            'retention' => [],
            'events' => [],
        ];

        try {
            $model = new LgpdModel();
            $payload['summary'] = $model->summary();
            $payload['config'] = $model->getConfig();
            $payload['requests'] = $model->listRequests($filters);
            $payload['incidents'] = $model->listIncidents($filters);
            $payload['retention'] = $model->listRetentionPolicies();
            $payload['events'] = $model->listEvents(40);
        } catch (Throwable $e) {
            $dbError = 'Estrutura LGPD não encontrada. Execute o SQL: sql/migration_v2_1_lgpd.sql';
        }

        $this->view('crud/lgpd', array_merge($payload, [
            'filters' => $filters,
            'db_error' => $dbError,
            'flash' => get_flash(),
        ]));
    }

    public function saveConfig(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=lgpd/index');
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=lgpd/index');
        }

        $payload = [
            'controlador_nome' => $this->sanitizeText($_POST['controlador_nome'] ?? '', 160),
            'controlador_email' => $this->sanitizeEmail($_POST['controlador_email'] ?? ''),
            'encarregado_nome' => $this->sanitizeText($_POST['encarregado_nome'] ?? '', 160),
            'encarregado_email' => $this->sanitizeEmail($_POST['encarregado_email'] ?? ''),
            'encarregado_telefone' => $this->sanitizeText($_POST['encarregado_telefone'] ?? '', 40),
            'canal_titular_url' => $this->sanitizeText($_POST['canal_titular_url'] ?? '', 255),
            'canal_titular_email' => $this->sanitizeEmail($_POST['canal_titular_email'] ?? ''),
            'politica_privacidade_url' => $this->sanitizeText($_POST['politica_privacidade_url'] ?? '', 255),
            'prazo_titular_dias' => max(1, min(180, (int)($_POST['prazo_titular_dias'] ?? 15))),
            'prazo_incidente_dias_uteis' => max(1, min(30, (int)($_POST['prazo_incidente_dias_uteis'] ?? 3))),
        ];

        if ($payload['controlador_nome'] === '') {
            set_flash('danger', 'Informe o nome do controlador.');
            $this->redirect('/?r=lgpd/index');
        }

        try {
            $model = new LgpdModel();
            $model->saveConfig($payload, (int)Auth::user()['id']);
            set_flash('success', 'Configuração LGPD salva com sucesso.');
        } catch (Throwable $e) {
            $this->handleFailure('Falha ao salvar configuracao LGPD. Verifique os logs.', $e, 'lgpd_config_save_failed');
        }
        $this->redirect('/?r=lgpd/index');
    }

    public function addRequest(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=lgpd/index');
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=lgpd/index');
        }

        $payload = [
            'tipo' => $this->normalizeRequestType($_POST['tipo'] ?? ''),
            'titular_nome' => $this->sanitizeText($_POST['titular_nome'] ?? '', 160),
            'titular_documento' => $this->sanitizeText($_POST['titular_documento'] ?? '', 40),
            'titular_email' => $this->sanitizeEmail($_POST['titular_email'] ?? ''),
            'detalhes' => trim((string)($_POST['detalhes'] ?? '')),
            'recebido_em' => $this->normalizeDateTime($_POST['recebido_em'] ?? ''),
            'prazo_resposta_em' => $this->normalizeDateTime($_POST['prazo_resposta_em'] ?? ''),
        ];

        if ($payload['titular_nome'] === '') {
            set_flash('danger', 'Informe o nome do titular.');
            $this->redirect('/?r=lgpd/index');
        }

        try {
            $model = new LgpdModel();
            $model->createRequest($payload, (int)Auth::user()['id']);
            set_flash('success', 'Solicitação LGPD registrada.');
        } catch (Throwable $e) {
            $this->handleFailure('Falha ao registrar solicitacao LGPD. Verifique os logs.', $e, 'lgpd_request_create_failed');
        }
        $this->redirect('/?r=lgpd/index');
    }

    public function updateRequest(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=lgpd/index');
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=lgpd/index');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            set_flash('danger', 'Solicitação inválida.');
            $this->redirect('/?r=lgpd/index');
        }

        $payload = [
            'tipo' => $this->normalizeRequestType($_POST['tipo'] ?? ''),
            'titular_nome' => $this->sanitizeText($_POST['titular_nome'] ?? '', 160),
            'titular_documento' => $this->sanitizeText($_POST['titular_documento'] ?? '', 40),
            'titular_email' => $this->sanitizeEmail($_POST['titular_email'] ?? ''),
            'detalhes' => trim((string)($_POST['detalhes'] ?? '')),
            'status' => $this->normalizeRequestStatus($_POST['status'] ?? ''),
            'prazo_resposta_em' => $this->normalizeDateTime($_POST['prazo_resposta_em'] ?? ''),
            'concluido_em' => $this->normalizeDateTime($_POST['concluido_em'] ?? ''),
            'resposta_resumo' => trim((string)($_POST['resposta_resumo'] ?? '')),
        ];

        try {
            $model = new LgpdModel();
            $ok = $model->updateRequest($id, $payload, (int)Auth::user()['id']);
            set_flash($ok ? 'success' : 'warning', $ok ? 'Solicitação atualizada.' : 'Solicitação não encontrada.');
        } catch (Throwable $e) {
            $this->handleFailure('Falha ao atualizar solicitacao LGPD. Verifique os logs.', $e, 'lgpd_request_update_failed');
        }
        $this->redirect('/?r=lgpd/index');
    }

    public function addIncident(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=lgpd/index');
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=lgpd/index');
        }

        $payload = [
            'titulo' => $this->sanitizeText($_POST['titulo'] ?? '', 190),
            'categoria' => $this->sanitizeText($_POST['categoria'] ?? '', 80),
            'status' => $this->normalizeIncidentStatus($_POST['status'] ?? ''),
            'risco_nivel' => $this->normalizeIncidentRisk($_POST['risco_nivel'] ?? ''),
            'data_incidente' => $this->normalizeDateTime($_POST['data_incidente'] ?? ''),
            'detectado_em' => $this->normalizeDateTime($_POST['detectado_em'] ?? ''),
            'titulares_afetados' => max(0, (int)($_POST['titulares_afetados'] ?? 0)),
            'dados_afetados' => trim((string)($_POST['dados_afetados'] ?? '')),
            'medidas_adotadas' => trim((string)($_POST['medidas_adotadas'] ?? '')),
            'comunicado_anpd' => (int)($_POST['comunicado_anpd'] ?? 0) === 1 ? 1 : 0,
            'comunicado_titulares' => (int)($_POST['comunicado_titulares'] ?? 0) === 1 ? 1 : 0,
            'comunicado_em' => $this->normalizeDateTime($_POST['comunicado_em'] ?? ''),
            'encerrado_em' => $this->normalizeDateTime($_POST['encerrado_em'] ?? ''),
        ];

        if ($payload['titulo'] === '') {
            set_flash('danger', 'Informe um título para o incidente.');
            $this->redirect('/?r=lgpd/index');
        }

        try {
            $model = new LgpdModel();
            $model->createIncident($payload, (int)Auth::user()['id']);
            set_flash('success', 'Incidente registrado.');
        } catch (Throwable $e) {
            $this->handleFailure('Falha ao registrar incidente LGPD. Verifique os logs.', $e, 'lgpd_incident_create_failed');
        }
        $this->redirect('/?r=lgpd/index');
    }

    public function updateIncident(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=lgpd/index');
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=lgpd/index');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            set_flash('danger', 'Incidente inválido.');
            $this->redirect('/?r=lgpd/index');
        }

        $payload = [
            'titulo' => $this->sanitizeText($_POST['titulo'] ?? '', 190),
            'categoria' => $this->sanitizeText($_POST['categoria'] ?? '', 80),
            'status' => $this->normalizeIncidentStatus($_POST['status'] ?? ''),
            'risco_nivel' => $this->normalizeIncidentRisk($_POST['risco_nivel'] ?? ''),
            'data_incidente' => $this->normalizeDateTime($_POST['data_incidente'] ?? ''),
            'detectado_em' => $this->normalizeDateTime($_POST['detectado_em'] ?? ''),
            'titulares_afetados' => max(0, (int)($_POST['titulares_afetados'] ?? 0)),
            'dados_afetados' => trim((string)($_POST['dados_afetados'] ?? '')),
            'medidas_adotadas' => trim((string)($_POST['medidas_adotadas'] ?? '')),
            'comunicado_anpd' => (int)($_POST['comunicado_anpd'] ?? 0) === 1 ? 1 : 0,
            'comunicado_titulares' => (int)($_POST['comunicado_titulares'] ?? 0) === 1 ? 1 : 0,
            'comunicado_em' => $this->normalizeDateTime($_POST['comunicado_em'] ?? ''),
            'encerrado_em' => $this->normalizeDateTime($_POST['encerrado_em'] ?? ''),
        ];

        try {
            $model = new LgpdModel();
            $ok = $model->updateIncident($id, $payload, (int)Auth::user()['id']);
            set_flash($ok ? 'success' : 'warning', $ok ? 'Incidente atualizado.' : 'Incidente não encontrado.');
        } catch (Throwable $e) {
            $this->handleFailure('Falha ao atualizar incidente LGPD. Verifique os logs.', $e, 'lgpd_incident_update_failed');
        }
        $this->redirect('/?r=lgpd/index');
    }

    public function saveRetention(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=lgpd/index');
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=lgpd/index');
        }

        $payload = [
            'tabela_nome' => $this->sanitizeText($_POST['tabela_nome'] ?? '', 100),
            'descricao' => $this->sanitizeText($_POST['descricao'] ?? '', 190),
            'retencao_dias' => max(1, min(3650, (int)($_POST['retencao_dias'] ?? 180))),
            'modo' => ($_POST['modo'] ?? '') === 'anonimizar' ? 'anonimizar' : 'eliminar',
            'ativo' => (int)($_POST['ativo'] ?? 0) === 1 ? 1 : 0,
        ];

        if ($payload['tabela_nome'] === '') {
            set_flash('danger', 'Informe a tabela da política.');
            $this->redirect('/?r=lgpd/index');
        }

        try {
            $model = new LgpdModel();
            $model->upsertRetentionPolicy($payload, (int)Auth::user()['id']);
            set_flash('success', 'Política de retenção salva.');
        } catch (Throwable $e) {
            $this->handleFailure('Nao foi possivel salvar a politica de retencao. Verifique os logs.', $e, 'lgpd_retention_policy_save_failed');
        }
        $this->redirect('/?r=lgpd/index');
    }

    public function runRetentionNow(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=lgpd/index');
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=lgpd/index');
        }

        try {
            $model = new LgpdModel();
            $result = $model->runRetentionJob((int)Auth::user()['id']);
            $msg = sprintf(
                'Retenção executada: %d políticas processadas, %d registros tratados.',
                (int)$result['processed'],
                (int)$result['affected']
            );
            if (!empty($result['errors'])) {
                (new SecurityLogModel())->log('lgpd_retention_run_errors', (int)(Auth::user()['id'] ?? 0), [
                    'errors_count' => count($result['errors']),
                    'errors' => array_slice($result['errors'], 0, 20),
                ]);
                $msg .= ' Houve falhas em algumas politicas. Consulte os logs.';
                set_flash('warning', $msg);
            } else {
                set_flash('success', $msg);
            }
        } catch (Throwable $e) {
            $this->handleFailure('Falha ao executar retencao LGPD. Verifique os logs.', $e, 'lgpd_retention_run_failed');
        }
        $this->redirect('/?r=lgpd/index');
    }

    private function sanitizeText($value, int $maxLen): string
    {
        $text = trim((string)$value);
        if ($text === '') {
            return '';
        }
        return mb_substr($text, 0, $maxLen, 'UTF-8');
    }

    private function sanitizeEmail($value): string
    {
        $email = trim((string)$value);
        if ($email === '') {
            return '';
        }
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? mb_substr($email, 0, 190, 'UTF-8') : '';
    }

    private function normalizeDateTime($value): string
    {
        $input = trim((string)$value);
        if ($input === '') {
            return '';
        }
        $input = str_replace('T', ' ', $input);
        if (preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}$/', $input)) {
            $input .= ':00';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $input)) {
            return '';
        }
        return $input;
    }

    private function normalizeRequestType($value): string
    {
        $allowed = ['acesso', 'correcao', 'anonimizacao', 'eliminacao', 'portabilidade', 'oposicao', 'revogacao', 'informacao'];
        $type = strtolower(trim((string)$value));
        return in_array($type, $allowed, true) ? $type : 'acesso';
    }

    private function normalizeRequestStatus($value): string
    {
        $allowed = ['aberta', 'em_tratamento', 'concluida', 'indeferida'];
        $status = strtolower(trim((string)$value));
        return in_array($status, $allowed, true) ? $status : 'aberta';
    }

    private function normalizeIncidentStatus($value): string
    {
        $allowed = ['aberto', 'investigacao', 'comunicado', 'encerrado'];
        $status = strtolower(trim((string)$value));
        return in_array($status, $allowed, true) ? $status : 'aberto';
    }

    private function normalizeIncidentRisk($value): string
    {
        $allowed = ['baixo', 'medio', 'alto'];
        $risk = strtolower(trim((string)$value));
        return in_array($risk, $allowed, true) ? $risk : 'medio';
    }
}
