<?php
declare(strict_types=1);

final class LgpdRepository
{
    private LgpdModel $lgpdModel;

    public function __construct(?LgpdModel $lgpdModel = null)
    {
        $this->lgpdModel = $lgpdModel ?? new LgpdModel();
    }

    /**
     * Resume pendências para orientar priorização jurídica e operacional.
     *
     * @return array<string, mixed>
     */
    public function resumo(): array
    {
        return $this->lgpdModel->summary();
    }

    /**
     * Recupera os dados oficiais do controlador, encarregado e prazos LGPD.
     *
     * @return array<string, mixed>
     */
    public function configuracao(): array
    {
        return $this->lgpdModel->getConfig();
    }

    /**
     * Lista solicitações de titulares para gestão de prazo de resposta.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarSolicitacoes(array $filters): array
    {
        return $this->lgpdModel->listRequests($filters);
    }

    /**
     * Lista incidentes para acompanhar contenção, comunicação e encerramento.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarIncidentes(array $filters): array
    {
        return $this->lgpdModel->listIncidents($filters);
    }

    /**
     * Lista políticas de retenção ativas para governar descarte de dados vencidos.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarPoliticasRetencao(): array
    {
        return $this->lgpdModel->listRetentionPolicies();
    }

    /**
     * Retorna as tabelas autorizadas para retenção, evitando limpeza fora do escopo aprovado.
     *
     * @return array<string, array<string, string>>
     */
    public function opcoesTabelasRetencao(): array
    {
        return $this->lgpdModel->retentionTableOptions();
    }

    /**
     * Lista eventos LGPD sanitizados para consulta administrativa.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listarEventos(int $limit = GovernancaConstants::LGPD_EVENT_LIMIT): array
    {
        return $this->lgpdModel->listEvents($limit);
    }

    /**
     * Salva configurações oficiais de privacidade com trilha de auditoria.
     */
    public function salvarConfiguracao(array $payload, int $usuarioId): void
    {
        $this->lgpdModel->saveConfig($payload, $usuarioId);
    }

    /**
     * Registra uma solicitação de titular com protocolo rastreável.
     */
    public function criarSolicitacao(array $payload, int $usuarioId): int
    {
        return $this->lgpdModel->createRequest($payload, $usuarioId);
    }

    /**
     * Recupera solicitação para validação de existência antes de atualizar decisões.
     *
     * @return array<string, mixed>|null
     */
    public function buscarSolicitacao(int $solicitacaoId): ?array
    {
        return $this->lgpdModel->findRequest($solicitacaoId);
    }

    /**
     * Atualiza tratamento da solicitação mantendo histórico de antes e depois.
     */
    public function atualizarSolicitacao(int $solicitacaoId, array $payload, int $usuarioId): bool
    {
        return $this->lgpdModel->updateRequest($solicitacaoId, $payload, $usuarioId);
    }

    /**
     * Registra incidente de privacidade para governança de risco.
     */
    public function criarIncidente(array $payload, int $usuarioId): int
    {
        return $this->lgpdModel->createIncident($payload, $usuarioId);
    }

    /**
     * Recupera incidente para preservar trilha de alteração.
     *
     * @return array<string, mixed>|null
     */
    public function buscarIncidente(int $incidenteId): ?array
    {
        return $this->lgpdModel->findIncident($incidenteId);
    }

    /**
     * Atualiza incidente e registra medidas de resposta.
     */
    public function atualizarIncidente(int $incidenteId, array $payload, int $usuarioId): bool
    {
        return $this->lgpdModel->updateIncident($incidenteId, $payload, $usuarioId);
    }

    /**
     * Salva política de retenção para descarte de trilhas vencidas.
     */
    public function salvarPoliticaRetencao(array $payload, int $usuarioId): void
    {
        $this->lgpdModel->upsertRetentionPolicy($payload, $usuarioId);
    }

    /**
     * Executa retenção imediata conforme políticas ativas.
     *
     * @return array{processed: int, affected: int, errors: array<int, string>}
     */
    public function executarRetencao(int $usuarioId): array
    {
        return $this->lgpdModel->runRetentionJob($usuarioId);
    }
}
