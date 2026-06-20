<?php
declare(strict_types=1);

interface ShiftRepositoryInterface
{
    /**
     * Recupera o turno de salão em aberto do usuário.
     */
    public function turnoAtivoDoUsuario(int $usuarioId): ?array;

    /**
     * Abre um turno de salão para início dos registros operacionais.
     */
    public function abrirTurno(array $dadosTurno, int $usuarioId): int;

    /**
     * Encerra um turno de salão e registra a mudança para auditoria.
     */
    public function encerrarTurno(
        int $turnoId,
        int $usuarioId,
        string $acaoAuditoria = ControleSalaoConstants::ACTION_AUDIT_UPDATE
    ): void;

    /**
     * Retorna os totais operacionais do turno encerrado.
     */
    public function resumoDoTurno(int $turnoId): array;
}
