<?php
declare(strict_types=1);

final class OperarReservaCommand
{
    public string $acao;
    public int $usuarioId;
    public array $usuario;
    public array $restaurantesPermitidos;
    public array $turnosPermitidos;
    public int $reservaId;
    public int $restauranteId;
    public int $turnoId;
    public string $dataReserva;
    public string $status;
    public string $observacaoOperacao;
    public string $paxRealTexto;
    public string $justificativa;
    public bool $confirmouStatusFinal;
    public string $acaoRapida;

    /**
     * Normaliza os dados da operação do salão para alteração de status, ajustes
     * de PAX real, movimentação entre turnos ou fechamento operacional.
     */
    public function __construct(array $dados)
    {
        $this->acao = (string)($dados['acao'] ?? '');
        $this->usuarioId = (int)($dados['usuario_id'] ?? 0);
        $this->usuario = $dados['usuario'] ?? [];
        $this->restaurantesPermitidos = $dados['restaurantes_permitidos'] ?? [];
        $this->turnosPermitidos = $dados['turnos_permitidos'] ?? [];
        $this->reservaId = (int)($dados['reserva_id'] ?? 0);
        $this->restauranteId = (int)($dados['restaurante_id'] ?? 0);
        $this->turnoId = (int)($dados['turno_id'] ?? 0);
        $this->dataReserva = (string)($dados['data_reserva'] ?? date('Y-m-d'));
        $this->status = normalize_mojibake(trim((string)($dados['status'] ?? ReservasTematicasConstants::STATUS_RESERVADA)));
        $this->observacaoOperacao = trim((string)($dados['observacao_operacao'] ?? ''));
        $this->paxRealTexto = trim((string)($dados['pax_real'] ?? ''));
        $this->justificativa = trim((string)($dados['justificativa'] ?? ''));
        $this->confirmouStatusFinal = !empty($dados['confirmou_status_final']);
        $this->acaoRapida = normalize_mojibake(trim((string)($dados['acao_rapida'] ?? '')));
    }
}
