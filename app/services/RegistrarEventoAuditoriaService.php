<?php
declare(strict_types=1);

final class RegistrarEventoAuditoriaService
{
    private AuditoriaRepository $auditoriaRepository;

    public function __construct(?AuditoriaRepository $auditoriaRepository = null)
    {
        $this->auditoriaRepository = $auditoriaRepository ?? new AuditoriaRepository();
    }

    /**
     * Registra um evento auditável para preservar rastreabilidade de mudanças relevantes.
     */
    public function registrar(
        string $tipoAuditoria,
        ?int $usuarioId,
        array $dadosAntes,
        array $dadosDepois,
        string $tabelaOperacional,
        ?int $registroId = null
    ): void {
        $this->auditoriaRepository->registrarEventoSeguro(
            $tipoAuditoria,
            $usuarioId,
            $dadosAntes,
            $dadosDepois,
            $tabelaOperacional,
            $registroId
        );
    }

    /**
     * Monta o painel de auditoria com trilhas gerais, temáticas e encerramentos de turno.
     *
     * @return array<string, mixed>
     */
    public function montarPainel(array $query): array
    {
        $filters = [
            'data' => sanitize_date_param($query['data'] ?? '', ''),
            'data_inicio' => sanitize_date_param($query['data_inicio'] ?? ''),
            'data_fim' => sanitize_date_param($query['data_fim'] ?? ''),
            'usuario_id' => sanitize_int_param($query['usuario_id'] ?? ''),
            'tabela' => trim((string)($query['tabela'] ?? '')),
            'uh_numero' => sanitize_uh_param($query['uh_numero'] ?? ''),
        ];
        if ($filters['data'] === '' && $filters['data_inicio'] === '' && $filters['data_fim'] === '') {
            $filters['data'] = date('Y-m-d');
        }

        $this->fecharTurnosExpiradosParaAuditoria();
        return [
            'filters' => $filters,
            'usuarios' => $this->auditoriaRepository->listarUsuariosParaFiltro(),
            'general_logs' => $this->paginateQuery($query, 'general_page', function (int $limit, int $offset) use ($filters): array {
                return $this->auditoriaRepository->paginarEventosGerais($filters, $limit, $offset);
            }),
            'attempt_logs' => $this->paginateQuery($query, 'attempt_page', function (int $limit, int $offset) use ($filters): array {
                return $this->auditoriaRepository->paginarTentativasReserva($filters, $limit, $offset);
            }),
            'thematic_logs' => $this->paginateQuery($query, 'thematic_page', function (int $limit, int $offset) use ($filters): array {
                return $this->auditoriaRepository->paginarEventosTematicos($filters, $limit, $offset);
            }),
            'shift_logs' => $this->paginateQuery($query, 'shift_page', function (int $limit, int $offset) use ($filters): array {
                return $this->auditoriaRepository->paginarEventosTurnos($filters, $limit, $offset);
            }),
        ];
    }

    private function fecharTurnosExpiradosParaAuditoria(): void
    {
        try {
            (new ShiftModel())->autoCloseExpired(GovernancaConstants::AUDITORIA_TURNOS_GRACE_MINUTES, null);
            (new SpecialShiftModel())->autoCloseExpired(GovernancaConstants::AUDITORIA_TURNOS_GRACE_MINUTES, null);
        } catch (Throwable $e) {
            (new SegurancaRepository())->registrarLogSegurancaSeguro(
                GovernancaConstants::AUDIT_AUTO_CLOSE_FAILED,
                null,
                ['erro' => $e->getMessage()]
            );
        }
    }

    /**
     * Pagina trilhas de auditoria para revisão administrativa sem truncar a evidência original.
     *
     * @return array{rows: array<int, array<string, mixed>>, page: int, total_pages: int, total: int, param: string}
     */
    private function paginateQuery(array $query, string $param, callable $loader, int $perPage = GovernancaConstants::AUDITORIA_PAGE_SIZE): array
    {
        $page = max(1, (int)($query[$param] ?? 1));
        $result = $loader($perPage, ($page - 1) * $perPage);
        $total = (int)($result['total'] ?? 0);
        $totalPages = max(1, (int)ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            $result = $loader($perPage, ($page - 1) * $perPage);
        }
        return [
            'rows' => $result['rows'] ?? [],
            'page' => $page,
            'total_pages' => $totalPages,
            'total' => $total,
            'param' => $param,
        ];
    }
}
