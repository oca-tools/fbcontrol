<?php
declare(strict_types=1);

final class ConfigurarHorarioCommand
{
    public string $acao;
    public int $registroId;
    public int $usuarioId;
    public int $restauranteId;
    public int $operacaoId;
    public string $horaInicio;
    public string $horaFim;
    public int $toleranciaMinutos;
    public bool $horarioAtivo;

    /**
     * Normaliza a grade de horario de uma operacao de A&B por restaurante.
     */
    public function __construct(array $dados)
    {
        $this->acao = (string)($dados['acao'] ?? GestaoRestaurantesConstants::ACTION_CREATE);
        $this->registroId = (int)($dados['id'] ?? 0);
        $this->usuarioId = (int)($dados['usuario_id'] ?? 0);
        $this->restauranteId = (int)($dados['restaurante_id'] ?? 0);
        $this->operacaoId = (int)($dados['operacao_id'] ?? 0);
        $this->horaInicio = trim((string)($dados['hora_inicio'] ?? ''));
        $this->horaFim = trim((string)($dados['hora_fim'] ?? ''));
        $this->toleranciaMinutos = max(GestaoRestaurantesConstants::DEFAULT_TOLERANCE_MINUTES, (int)($dados['tolerancia_min'] ?? GestaoRestaurantesConstants::DEFAULT_TOLERANCE_MINUTES));
        $this->horarioAtivo = (int)($dados['ativo'] ?? GestaoRestaurantesConstants::STATUS_ACTIVE) === GestaoRestaurantesConstants::STATUS_ACTIVE;
    }
}
