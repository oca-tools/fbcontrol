<?php
declare(strict_types=1);

final class ConfigurarHorarioService
{
    private EstruturaRestauranteRepositoryInterface $estruturaRestauranteRepository;

    public function __construct(?EstruturaRestauranteRepositoryInterface $estruturaRestauranteRepository = null)
    {
        $this->estruturaRestauranteRepository = $estruturaRestauranteRepository ?? new EstruturaRestauranteRepository();
    }

    /**
     * Define os horarios em que uma operacao de A&B estara ativa para receber hospedes.
     */
    public function executar(ConfigurarHorarioCommand $command): ServiceResult
    {
        if (!$this->gradeDaOperacaoEstaCompleta($command)) {
            $mensagem = $command->acao === GestaoRestaurantesConstants::ACTION_CREATE
                ? GestaoRestaurantesConstants::MESSAGE_HORARIO_OBRIGATORIO
                : GestaoRestaurantesConstants::MESSAGE_DADOS_INVALIDOS;
            return ServiceResult::failure(GestaoRestaurantesConstants::CODE_DADOS_HORARIO_INVALIDOS, $mensagem);
        }

        if (!$this->horariosTemFormatoOperacional($command)) {
            return ServiceResult::failure(
                GestaoRestaurantesConstants::CODE_HORARIO_INVALIDO,
                GestaoRestaurantesConstants::MESSAGE_HORARIO_INVALIDO
            );
        }

        $dadosHorario = [
            'restaurante_id' => $command->restauranteId,
            'operacao_id' => $command->operacaoId,
            'hora_inicio' => $this->normalizarHora($command->horaInicio),
            'hora_fim' => $this->normalizarHora($command->horaFim),
            'tolerancia_min' => $command->toleranciaMinutos,
            'ativo' => $command->horarioAtivo ? GestaoRestaurantesConstants::STATUS_ACTIVE : GestaoRestaurantesConstants::STATUS_INACTIVE,
        ];

        if ($command->acao === GestaoRestaurantesConstants::ACTION_CREATE) {
            $horarioId = $this->estruturaRestauranteRepository->criarHorarioDaOperacao($dadosHorario, $command->usuarioId);
            return ServiceResult::success(GestaoRestaurantesConstants::MESSAGE_HORARIO_CADASTRADO, ['horario_id' => $horarioId]);
        }

        if ($command->registroId <= 0) {
            return ServiceResult::failure(
                GestaoRestaurantesConstants::CODE_DADOS_HORARIO_INVALIDOS,
                GestaoRestaurantesConstants::MESSAGE_DADOS_INVALIDOS
            );
        }

        $this->estruturaRestauranteRepository->atualizarHorarioDaOperacao($command->registroId, $dadosHorario, $command->usuarioId);
        return ServiceResult::success(GestaoRestaurantesConstants::MESSAGE_HORARIO_ATUALIZADO, ['horario_id' => $command->registroId]);
    }

    private function gradeDaOperacaoEstaCompleta(ConfigurarHorarioCommand $command): bool
    {
        return $command->restauranteId > 0
            && $command->operacaoId > 0
            && $command->horaInicio !== ''
            && $command->horaFim !== '';
    }

    private function horariosTemFormatoOperacional(ConfigurarHorarioCommand $command): bool
    {
        return $this->normalizarHora($command->horaInicio) !== ''
            && $this->normalizarHora($command->horaFim) !== '';
    }

    private function normalizarHora(string $horaOperacao): string
    {
        $horaOperacao = trim($horaOperacao);
        if (preg_match('/^\d{2}:\d{2}$/', $horaOperacao)) {
            return $horaOperacao . ':00';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $horaOperacao)) {
            return $horaOperacao;
        }

        return '';
    }
}
