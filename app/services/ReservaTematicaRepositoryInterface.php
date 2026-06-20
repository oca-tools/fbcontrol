<?php
declare(strict_types=1);

interface ReservaTematicaRepositoryInterface
{
    /**
     * Persiste uma reserva tematica validada para a agenda do restaurante.
     */
    public function criarReserva(array $dadosReserva, int $usuarioId): int;

    /**
     * Atualiza os dados de uma reserva mantendo a auditoria do operador.
     */
    public function atualizarReserva(int $reservaId, array $dadosReserva, int $usuarioId): void;

    /**
     * Cria o agrupador operacional de reservas para UHs vinculadas ao mesmo responsavel.
     */
    public function criarGrupo(array $dadosGrupo, int $usuarioId): int;

    /**
     * Substitui a lista de idades CHD usada na conferencia de ocupacao da reserva.
     */
    public function substituirIdadesChd(int $reservaId, array $idades): void;

    /**
     * Recupera a reserva com os dados necessarios para manutencao e operacao.
     */
    public function buscarReserva(int $reservaId): ?array;

    /**
     * Soma o PAX reservado no turno para validar disponibilidade antes de novas reservas.
     */
    public function somarPaxDoTurno(int $restauranteId, string $dataReserva, int $turnoId): int;

    /**
     * Localiza reserva ativa da mesma UH no turno para evitar duplicidade operacional.
     */
    public function buscarReservaDuplicadaDaUh(int $uhId, string $dataReserva, int $turnoId, int $restauranteId): ?int;

    /**
     * Consulta a capacidade configurada para o restaurante, data e turno informados.
     */
    public function capacidadeDoTurno(int $restauranteId, string $dataReserva, int $turnoId): int;

    /**
     * Verifica se o restaurante esta bloqueado para reservas na data selecionada.
     */
    public function restauranteFechadoNaData(int $restauranteId, string $dataReserva): bool;

    /**
     * Informa se o turno ja foi encerrado pela operacao de A&B.
     */
    public function turnoOperacionalFechado(int $restauranteId, string $dataReserva, int $turnoId): bool;

    /**
     * Encerra o turno operacional para bloquear novas alteracoes sem autorizacao.
     */
    public function fecharTurnoOperacional(int $restauranteId, string $dataReserva, int $turnoId, int $usuarioId): void;

    /**
     * Registra o status de atendimento da reserva durante a operacao do restaurante.
     */
    public function atualizarStatusOperacao(int $reservaId, string $status, ?string $observacao, int $usuarioId, ?int $paxReal = null): void;

    /**
     * Ajusta dados operacionais da reserva, como destino, status, observacao e PAX real.
     */
    public function atualizarDetalhesOperacao(int $reservaId, array $dadosOperacao, int $usuarioId): void;

    /**
     * Grava a trilha de auditoria para mudancas relevantes na jornada da reserva.
     */
    public function registrarLog(int $reservaId, string $acao, int $usuarioId, array $antes = [], array $depois = [], ?string $justificativa = null): void;

    /**
     * Lista reservas elegiveis para no-show automatico conforme tolerancia configurada.
     */
    public function listarCandidatasAutoNoShow(string $agora, ?string $dataReserva = null, ?int $restauranteId = null): array;

    /**
     * Executa uma operacao atomica para preservar consistencia entre reserva, grupo e auditoria.
     */
    public function executarTransacao(callable $operacao): void;
}
