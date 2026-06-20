<?php
declare(strict_types=1);

final class AutoNoShowCommand
{
    public int $usuarioId;
    public ?string $dataReserva;
    public ?int $restauranteId;
    public string $executadoEm;
    public string $origem;

    /**
     * Normaliza o contexto de execução do no-show automático para cron ou tela.
     */
    public function __construct(array $dados)
    {
        $this->usuarioId = (int)($dados['usuario_id'] ?? 0);
        $this->dataReserva = !empty($dados['data_reserva']) ? (string)$dados['data_reserva'] : null;
        $this->restauranteId = !empty($dados['restaurante_id']) ? (int)$dados['restaurante_id'] : null;
        $this->executadoEm = (string)($dados['executado_em'] ?? date('Y-m-d H:i:s'));
        $this->origem = (string)($dados['origem'] ?? ReservasTematicasConstants::ORIGIN_SERVICE);
    }
}
