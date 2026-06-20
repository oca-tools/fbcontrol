<?php
declare(strict_types=1);

final class EncerrarTurnoService implements EncerrarTurnoServiceInterface
{
    private ShiftRepositoryInterface $turnos;
    private AccessRepositoryInterface $acessos;

    public function __construct(?ShiftRepositoryInterface $turnos = null, ?AccessRepositoryInterface $acessos = null)
    {
        $this->turnos = $turnos ?? new ShiftRepository();
        $this->acessos = $acessos ?? new AccessRepository();
    }

    /**
     * Encerra ou cancela o turno de salão, protegendo a operação contra
     * cancelamentos após movimento e contra encerramento antes do fim previsto.
     */
    public function executar(EncerrarTurnoCommand $command): ServiceResult
    {
        if (empty($command->turno)) {
            return ServiceResult::failure(
                ControleSalaoConstants::CODE_TURNO_NAO_ENCONTRADO,
                ControleSalaoConstants::MESSAGE_TURNO_NAO_ENCONTRADO
            );
        }

        if ($command->cancelamento) {
            return $this->cancelarTurnoSemMovimento($command);
        }

        $bloqueioHorario = $this->validarFimDaOperacao($command);
        if ($bloqueioHorario) {
            return $bloqueioHorario;
        }

        $this->turnos->encerrarTurno((int)$command->turno['id'], $command->usuarioId);
        return ServiceResult::success(ControleSalaoConstants::MESSAGE_TURNO_ENCERRADO, [
            'summary' => $this->turnos->resumoDoTurno((int)$command->turno['id']),
        ]);
    }

    private function cancelarTurnoSemMovimento(EncerrarTurnoCommand $command): ServiceResult
    {
        if (TematicAccessService::isTematicShift($command->turno)) {
            $movimentosTematicos = (new ReservaTematicaLogModel())->countManualByUserSince(
                $command->usuarioId,
                (string)($command->turno['inicio_em'] ?? '')
            );
            if ($movimentosTematicos > 0) {
                return ServiceResult::failure(
                    ControleSalaoConstants::CODE_CANCELAMENTO_TEMATICO_BLOQUEADO,
                    ControleSalaoConstants::MESSAGE_CANCELAMENTO_TEMATICO_BLOQUEADO
                );
            }
        } elseif ($this->acessos->quantidadeLancamentosDoTurno((int)$command->turno['id']) > 0) {
            return ServiceResult::failure(
                ControleSalaoConstants::CODE_CANCELAMENTO_COM_ACESSOS,
                ControleSalaoConstants::MESSAGE_CANCELAMENTO_COM_ACESSOS
            );
        }

        $this->turnos->encerrarTurno((int)$command->turno['id'], $command->usuarioId);
        return ServiceResult::success(ControleSalaoConstants::MESSAGE_TURNO_CANCELADO);
    }

    private function validarFimDaOperacao(EncerrarTurnoCommand $command): ?ServiceResult
    {
        if ($command->modoDemo) {
            return null;
        }

        $regraOperacao = (new RestaurantOperationModel())->findByRestaurantOperation(
            (int)$command->turno['restaurante_id'],
            (int)$command->turno['operacao_id']
        );
        if (!$regraOperacao) {
            return null;
        }

        $tz = new DateTimeZone(date_default_timezone_get());
        $agora = new DateTime('now', $tz);
        $fimOperacao = DateTime::createFromFormat('H:i:s', (string)$regraOperacao['hora_fim'], $tz);
        if (!$fimOperacao) {
            return null;
        }

        $fimOperacao->setDate((int)$agora->format('Y'), (int)$agora->format('m'), (int)$agora->format('d'));
        if ($agora >= $fimOperacao) {
            return null;
        }

        $minutosRestantes = (int)ceil(
            ($fimOperacao->getTimestamp() - $agora->getTimestamp()) / ControleSalaoConstants::SECONDS_PER_MINUTE
        );
        $tolerancia = (int)($regraOperacao['tolerancia_min'] ?? 0);
        $mensagem = 'Ainda não é possível encerrar o turno. Faltam ' . $minutosRestantes . ' min para o fim.';
        if ($tolerancia > 0) {
            $mensagem .= ' Tolerância: ' . $tolerancia . ' min antes do fim.';
        }

        return ServiceResult::failure(ControleSalaoConstants::CODE_FIM_ANTECIPADO_BLOQUEADO, $mensagem);
    }
}
