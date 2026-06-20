<?php
declare(strict_types=1);

final class GerenciarLgpdService
{
    private LgpdRepository $lgpdRepository;
    private SegurancaRepository $segurancaRepository;
    private GovernancaAuthSession $authSession;

    public function __construct(
        ?LgpdRepository $lgpdRepository = null,
        ?SegurancaRepository $segurancaRepository = null,
        ?GovernancaAuthSession $authSession = null
    ) {
        $this->lgpdRepository = $lgpdRepository ?? new LgpdRepository();
        $this->segurancaRepository = $segurancaRepository ?? new SegurancaRepository();
        $this->authSession = $authSession ?? new GovernancaAuthSession();
    }

    /**
     * Consolida a visão de governança LGPD para acompanhar prazos, incidentes e retenção legal.
     *
     * @return array<string, mixed>
     */
    public function montarPainel(array $query, array $usuario): array
    {
        $filters = [
            'status' => trim((string)($query['status'] ?? '')),
            'risk' => trim((string)($query['risk'] ?? '')),
            'date_from' => trim((string)($query['date_from'] ?? '')),
            'date_to' => trim((string)($query['date_to'] ?? '')),
        ];
        $payload = $this->payloadPadrao($usuario);
        $dbError = '';

        try {
            $payload['summary'] = $this->lgpdRepository->resumo();
            $payload['config'] = $this->lgpdRepository->configuracao();
            $payload['requests'] = $this->lgpdRepository->listarSolicitacoes($filters);
            $payload['incidents'] = $this->lgpdRepository->listarIncidentes($filters);
            $payload['retention'] = $this->lgpdRepository->listarPoliticasRetencao();
            $payload['retention_options'] = $this->lgpdRepository->opcoesTabelasRetencao();
            $payload['events'] = $this->lgpdRepository->listarEventos(GovernancaConstants::LGPD_EVENT_LIMIT);
        } catch (Throwable $e) {
            $dbError = GovernancaConstants::MESSAGE_LGPD_DB_MISSING;
            $this->registrarFalha(GovernancaConstants::AUDIT_LGPD_PANEL_LOAD_FAILED, $e);
        }

        return array_merge($payload, [
            'filters' => $filters,
            'db_error' => $dbError,
            'flash' => get_flash(),
        ]);
    }

    /**
     * Salva a identificação do controlador e prazos usados para comprovar atendimento ao titular.
     */
    public function salvarConfiguracao(array $post, int $usuarioId): ServiceResult
    {
        $payload = [
            'controlador_nome' => $this->sanitizeText($post['controlador_nome'] ?? '', GovernancaConstants::MAX_CONTROLADOR_NOME_LENGTH),
            'controlador_email' => $this->sanitizeEmail($post['controlador_email'] ?? ''),
            'encarregado_nome' => $this->sanitizeText($post['encarregado_nome'] ?? '', GovernancaConstants::MAX_CONTROLADOR_NOME_LENGTH),
            'encarregado_email' => $this->sanitizeEmail($post['encarregado_email'] ?? ''),
            'encarregado_telefone' => $this->sanitizeText($post['encarregado_telefone'] ?? '', GovernancaConstants::MAX_PHONE_LENGTH),
            'canal_titular_url' => $this->sanitizeText($post['canal_titular_url'] ?? '', GovernancaConstants::MAX_URL_LENGTH),
            'canal_titular_email' => $this->sanitizeEmail($post['canal_titular_email'] ?? ''),
            'politica_privacidade_url' => $this->sanitizeText($post['politica_privacidade_url'] ?? '', GovernancaConstants::MAX_URL_LENGTH),
            'prazo_titular_dias' => max(
                1,
                min(GovernancaConstants::MAX_PRAZO_TITULAR_DIAS, (int)($post['prazo_titular_dias'] ?? GovernancaConstants::PRAZO_TITULAR_DIAS))
            ),
            'prazo_incidente_dias_uteis' => max(
                1,
                min(
                    GovernancaConstants::MAX_PRAZO_INCIDENTE_DIAS_UTEIS,
                    (int)($post['prazo_incidente_dias_uteis'] ?? GovernancaConstants::PRAZO_INCIDENTE_DIAS_UTEIS)
                )
            ),
        ];
        if ($payload['controlador_nome'] === '') {
            return ServiceResult::failure('controlador_obrigatorio', GovernancaConstants::MESSAGE_CONTROLADOR_OBRIGATORIO);
        }
        return $this->executarLgpdSeguro(
            static fn(LgpdRepository $repo) => $repo->salvarConfiguracao($payload, $usuarioId),
            GovernancaConstants::MESSAGE_LGPD_CONFIG_SALVA,
            GovernancaConstants::MESSAGE_LGPD_CONFIG_FALHA,
            GovernancaConstants::AUDIT_LGPD_CONFIG_SAVE_FAILED
        );
    }

    /**
     * Registra pedido de titular e calcula prazo de resposta para cumprir o fluxo LGPD.
     */
    public function criarSolicitacao(array $post, int $usuarioId): ServiceResult
    {
        $payload = [
            'tipo' => $this->normalizeRequestType($post['tipo'] ?? ''),
            'titular_nome' => $this->sanitizeText($post['titular_nome'] ?? '', GovernancaConstants::MAX_CONTROLADOR_NOME_LENGTH),
            'titular_documento' => $this->sanitizeText($post['titular_documento'] ?? '', GovernancaConstants::MAX_DOCUMENTO_LENGTH),
            'titular_email' => $this->sanitizeEmail($post['titular_email'] ?? ''),
            'detalhes' => $this->sanitizeLongText($post['detalhes'] ?? '', GovernancaConstants::MAX_LGPD_LONG_TEXT_LENGTH),
            'recebido_em' => $this->normalizeDateTime($post['recebido_em'] ?? ''),
            'prazo_resposta_em' => $this->normalizeDateTime($post['prazo_resposta_em'] ?? ''),
        ];
        if ($payload['titular_nome'] === '') {
            return ServiceResult::failure('titular_obrigatorio', GovernancaConstants::MESSAGE_TITULAR_OBRIGATORIO);
        }
        return $this->executarLgpdSeguro(
            static fn(LgpdRepository $repo) => $repo->criarSolicitacao($payload, $usuarioId),
            GovernancaConstants::MESSAGE_SOLICITACAO_REGISTRADA,
            GovernancaConstants::MESSAGE_SOLICITACAO_FALHA,
            GovernancaConstants::AUDIT_LGPD_REQUEST_CREATE_FAILED
        );
    }

    /**
     * Atualiza o ciclo de vida da solicitação do titular mantendo evidência da decisão.
     */
    public function atualizarSolicitacao(array $post, int $usuarioId): ServiceResult
    {
        $solicitacaoId = (int)($post['id'] ?? 0);
        if ($solicitacaoId <= 0) {
            return ServiceResult::failure('solicitacao_invalida', GovernancaConstants::MESSAGE_SOLICITACAO_INVALIDA);
        }
        $current = $this->lgpdRepository->buscarSolicitacao($solicitacaoId);
        if (!$current) {
            return ServiceResult::failure('solicitacao_nao_encontrada', GovernancaConstants::MESSAGE_SOLICITACAO_NAO_ENCONTRADA);
        }

        $payload = [
            'tipo' => $this->normalizeRequestType($post['tipo'] ?? ($current['tipo'] ?? '')),
            'titular_nome' => $this->sanitizeText($post['titular_nome'] ?? ($current['titular_nome'] ?? ''), GovernancaConstants::MAX_CONTROLADOR_NOME_LENGTH),
            'titular_documento' => $this->sanitizeText($post['titular_documento'] ?? ($current['titular_documento'] ?? ''), GovernancaConstants::MAX_DOCUMENTO_LENGTH),
            'titular_email' => $this->sanitizeEmail($post['titular_email'] ?? ($current['titular_email'] ?? '')),
            'detalhes' => $this->sanitizeLongText($post['detalhes'] ?? ($current['detalhes'] ?? ''), GovernancaConstants::MAX_LGPD_LONG_TEXT_LENGTH),
            'status' => $this->normalizeRequestStatus($post['status'] ?? ''),
            'prazo_resposta_em' => $this->normalizeDateTime($post['prazo_resposta_em'] ?? ($current['prazo_resposta_em'] ?? '')),
            'concluido_em' => $this->normalizeDateTime($post['concluido_em'] ?? ''),
            'resposta_resumo' => $this->sanitizeLongText($post['resposta_resumo'] ?? ($current['resposta_resumo'] ?? ''), GovernancaConstants::MAX_LGPD_LONG_TEXT_LENGTH),
        ];

        return $this->executarLgpdComResultado(
            static fn(LgpdRepository $repo): bool => $repo->atualizarSolicitacao($solicitacaoId, $payload, $usuarioId),
            GovernancaConstants::MESSAGE_SOLICITACAO_ATUALIZADA,
            GovernancaConstants::MESSAGE_SOLICITACAO_NAO_ENCONTRADA,
            GovernancaConstants::MESSAGE_SOLICITACAO_ATUALIZACAO_FALHA,
            GovernancaConstants::AUDIT_LGPD_REQUEST_UPDATE_FAILED
        );
    }

    /**
     * Registra incidente de privacidade para demonstrar contenção, avaliação de risco e comunicação.
     */
    public function criarIncidente(array $post, int $usuarioId): ServiceResult
    {
        $payload = $this->payloadIncidente($post);
        if ($payload['titulo'] === '') {
            return ServiceResult::failure('titulo_incidente_obrigatorio', GovernancaConstants::MESSAGE_INCIDENTE_TITULO_OBRIGATORIO);
        }
        return $this->executarLgpdSeguro(
            static fn(LgpdRepository $repo) => $repo->criarIncidente($payload, $usuarioId),
            GovernancaConstants::MESSAGE_INCIDENTE_REGISTRADO,
            GovernancaConstants::MESSAGE_INCIDENTE_REGISTRO_FALHA,
            GovernancaConstants::AUDIT_LGPD_INCIDENT_CREATE_FAILED
        );
    }

    /**
     * Atualiza ações de resposta ao incidente para preservar a evidência jurídica da contenção.
     */
    public function atualizarIncidente(array $post, int $usuarioId): ServiceResult
    {
        $incidenteId = (int)($post['id'] ?? 0);
        if ($incidenteId <= 0) {
            return ServiceResult::failure('incidente_invalido', GovernancaConstants::MESSAGE_INCIDENTE_INVALIDO);
        }
        $current = $this->lgpdRepository->buscarIncidente($incidenteId);
        if (!$current) {
            return ServiceResult::failure('incidente_nao_encontrado', GovernancaConstants::MESSAGE_INCIDENTE_NAO_ENCONTRADO);
        }
        $payload = $this->payloadIncidente($post, $current);
        return $this->executarLgpdComResultado(
            static fn(LgpdRepository $repo): bool => $repo->atualizarIncidente($incidenteId, $payload, $usuarioId),
            GovernancaConstants::MESSAGE_INCIDENTE_ATUALIZADO,
            GovernancaConstants::MESSAGE_INCIDENTE_NAO_ENCONTRADO,
            GovernancaConstants::MESSAGE_INCIDENTE_ATUALIZACAO_FALHA,
            GovernancaConstants::AUDIT_LGPD_INCIDENT_UPDATE_FAILED
        );
    }

    /**
     * Define retenção de dados para reduzir exposição após o prazo regulatório ou operacional.
     */
    public function salvarPoliticaRetencao(array $post, int $usuarioId): ServiceResult
    {
        $payload = [
            'tabela_nome' => $this->sanitizeText($post['tabela_nome'] ?? '', GovernancaConstants::MAX_TABELA_RETENCAO_LENGTH),
            'descricao' => $this->sanitizeText($post['descricao'] ?? '', GovernancaConstants::MAX_DESCRICAO_RETENCAO_LENGTH),
            'retencao_dias' => max(1, min(3650, (int)($post['retencao_dias'] ?? GovernancaConstants::ANONIMIZACAO_AUTOMATICA_DIAS))),
            'modo' => 'eliminar',
            'ativo' => (int)($post['ativo'] ?? 0) === 1 ? 1 : 0,
        ];
        if ($payload['tabela_nome'] === '') {
            return ServiceResult::failure('tabela_retencao_obrigatoria', GovernancaConstants::MESSAGE_TABELA_RETENCAO_OBRIGATORIA);
        }
        return $this->executarLgpdSeguro(
            static fn(LgpdRepository $repo) => $repo->salvarPoliticaRetencao($payload, $usuarioId),
            GovernancaConstants::MESSAGE_POLITICA_RETENCAO_SALVA,
            GovernancaConstants::MESSAGE_POLITICA_RETENCAO_FALHA,
            GovernancaConstants::AUDIT_LGPD_RETENTION_POLICY_SAVE_FAILED
        );
    }

    /**
     * Executa a retenção LGPD para eliminar registros expirados e reduzir dados mantidos sem necessidade.
     */
    public function executarRetencaoAgora(int $usuarioId): ServiceResult
    {
        try {
            $resultado = $this->lgpdRepository->executarRetencao($usuarioId);
            $mensagem = sprintf(
                'Retenção executada: %d políticas processadas, %d registros tratados.',
                (int)$resultado['processed'],
                (int)$resultado['affected']
            );
            if (!empty($resultado['errors'])) {
                $this->segurancaRepository->registrarLogSegurancaSeguro(GovernancaConstants::AUDIT_LGPD_RETENTION_RUN_ERRORS, $usuarioId, [
                    'errors_count' => count($resultado['errors']),
                    'errors' => array_slice($resultado['errors'], 0, 20),
                ]);
                return ServiceResult::failure(GovernancaConstants::STATUS_RETENCAO_COM_ALERTAS, $mensagem . GovernancaConstants::MESSAGE_RETENCAO_ALERTAS);
            }
            return ServiceResult::success($mensagem);
        } catch (Throwable $e) {
            $this->registrarFalha(GovernancaConstants::AUDIT_LGPD_RETENTION_RUN_FAILED, $e);
            return ServiceResult::failure('falha_retencao', GovernancaConstants::MESSAGE_RETENCAO_FALHA);
        }
    }

    private function payloadPadrao(array $usuario): array
    {
        return [
            'can_edit' => strtolower((string)($usuario['perfil'] ?? '')) === 'admin',
            'summary' => ['requests_open' => 0, 'requests_due_soon' => 0, 'incidents_open' => 0],
            'config' => [
                'controlador_nome' => GovernancaConstants::DEFAULT_CONTROLADOR_NOME,
                'canal_titular_url' => GovernancaConstants::ROUTE_PRIVACIDADE_INDEX,
                'prazo_titular_dias' => GovernancaConstants::PRAZO_TITULAR_DIAS,
                'prazo_incidente_dias_uteis' => GovernancaConstants::PRAZO_INCIDENTE_DIAS_UTEIS,
            ],
            'requests' => [],
            'incidents' => [],
            'retention' => [],
            'events' => [],
        ];
    }

    private function payloadIncidente(array $post, array $current = []): array
    {
        return [
            'titulo' => $this->sanitizeText($post['titulo'] ?? ($current['titulo'] ?? ''), GovernancaConstants::MAX_DESCRICAO_RETENCAO_LENGTH),
            'categoria' => $this->sanitizeText(
                $post['categoria'] ?? ($current['categoria'] ?? ''),
                GovernancaConstants::MAX_INCIDENT_CATEGORY_LENGTH
            ),
            'status' => $this->normalizeIncidentStatus($post['status'] ?? ''),
            'risco_nivel' => $this->normalizeIncidentRisk($post['risco_nivel'] ?? ($current['risco_nivel'] ?? '')),
            'data_incidente' => $this->normalizeDateTime($post['data_incidente'] ?? ($current['data_incidente'] ?? '')),
            'detectado_em' => $this->normalizeDateTime($post['detectado_em'] ?? ($current['detectado_em'] ?? '')),
            'titulares_afetados' => max(0, (int)($post['titulares_afetados'] ?? ($current['titulares_afetados'] ?? 0))),
            'dados_afetados' => $this->sanitizeLongText($post['dados_afetados'] ?? ($current['dados_afetados'] ?? ''), GovernancaConstants::MAX_LGPD_LONG_TEXT_LENGTH),
            'medidas_adotadas' => $this->sanitizeLongText($post['medidas_adotadas'] ?? ($current['medidas_adotadas'] ?? ''), GovernancaConstants::MAX_LGPD_LONG_TEXT_LENGTH),
            'comunicado_anpd' => (int)($post['comunicado_anpd'] ?? ($current['comunicado_anpd'] ?? 0)) === 1 ? 1 : 0,
            'comunicado_titulares' => (int)($post['comunicado_titulares'] ?? ($current['comunicado_titulares'] ?? 0)) === 1 ? 1 : 0,
            'comunicado_em' => $this->normalizeDateTime($post['comunicado_em'] ?? ($current['comunicado_em'] ?? '')),
            'encerrado_em' => $this->normalizeDateTime($post['encerrado_em'] ?? ''),
        ];
    }

    private function executarLgpdSeguro(callable $operacao, string $mensagemSucesso, string $mensagemFalha, string $eventoFalha): ServiceResult
    {
        try {
            $operacao($this->lgpdRepository);
            return ServiceResult::success($mensagemSucesso);
        } catch (Throwable $e) {
            $this->registrarFalha($eventoFalha, $e);
            return ServiceResult::failure($eventoFalha, $mensagemFalha);
        }
    }

    private function executarLgpdComResultado(callable $operacao, string $mensagemSucesso, string $mensagemNaoEncontrado, string $mensagemFalha, string $eventoFalha): ServiceResult
    {
        try {
            $ok = $operacao($this->lgpdRepository);
            return $ok ? ServiceResult::success($mensagemSucesso) : ServiceResult::failure('registro_nao_encontrado', $mensagemNaoEncontrado);
        } catch (Throwable $e) {
            $this->registrarFalha($eventoFalha, $e);
            return ServiceResult::failure($eventoFalha, $mensagemFalha);
        }
    }

    private function registrarFalha(string $evento, Throwable $e): void
    {
        $usuario = $this->authSession->user();
        $this->segurancaRepository->registrarLogSegurancaSeguro($evento, (int)($usuario['id'] ?? 0), [
            'exception' => get_class($e),
            'message' => mb_substr((string)$e->getMessage(), 0, GovernancaConstants::MAX_EXCEPTION_MESSAGE_LENGTH, 'UTF-8'),
            'route' => (string)($_GET['r'] ?? ''),
            'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
        ]);
    }

    private function sanitizeText($value, int $maxLen): string
    {
        $text = trim((string)$value);
        return $text === '' ? '' : mb_substr($text, 0, $maxLen, 'UTF-8');
    }

    private function sanitizeLongText($value, int $maxLen): string
    {
        $text = trim((string)$value);
        if ($text === '') {
            return '';
        }
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        return mb_substr($text, 0, $maxLen, 'UTF-8');
    }

    private function sanitizeEmail($value): string
    {
        $email = trim((string)$value);
        if ($email === '') {
            return '';
        }
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? mb_substr($email, 0, GovernancaConstants::MAX_EMAIL_LENGTH, 'UTF-8') : '';
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
        return preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $input) ? $input : '';
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
