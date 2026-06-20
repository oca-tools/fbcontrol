<?php
declare(strict_types=1);

final class AutoNoShowService implements AutoNoShowServiceInterface
{
    private ReservaTematicaRepositoryInterface $reservas;

    public function __construct(?ReservaTematicaRepositoryInterface $reservas = null)
    {
        $this->reservas = $reservas ?? new ReservaTematicaRepository();
    }

    /**
     * Marca automaticamente como no-show as reservas que excederam a tolerância
     * do turno, liberando a disponibilidade operacional do restaurante temático.
     */
    public function executar(AutoNoShowCommand $command): ServiceResult
    {
        if ($command->usuarioId <= 0) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_USUARIO_AUDITORIA_INVALIDO,
                ReservasTematicasConstants::MESSAGE_USUARIO_AUDITORIA_INVALIDO
            );
        }

        $reservasMarcadasComoNoShow = ReservasTematicasConstants::DEFAULT_ZERO;
        $candidatas = $this->reservas->listarCandidatasAutoNoShow($command->executadoEm, $command->dataReserva, $command->restauranteId);

        foreach ($candidatas as $candidata) {
            $reservaId = (int)($candidata['id'] ?? 0);
            if ($reservaId <= 0) {
                continue;
            }

            $antes = $this->reservas->buscarReserva($reservaId);
            if (!$antes || $this->normalizarStatus((string)($antes['status'] ?? '')) !== ReservasTematicasConstants::STATUS_RESERVADA) {
                continue;
            }

            $observacaoNoShow = $this->montarObservacaoNoShowAutomatico((string)($antes['observacao_operacao'] ?? ''));
            $this->reservas->atualizarStatusOperacao(
                $reservaId,
                ReservasTematicasConstants::STATUS_NO_SHOW,
                $observacaoNoShow,
                $command->usuarioId,
                ReservasTematicasConstants::DEFAULT_ZERO
            );
            $depois = $this->reservas->buscarReserva($reservaId) ?? [];
            $this->reservas->registrarLog(
                $reservaId,
                ReservasTematicasConstants::ACTION_AUTO_NO_SHOW,
                $command->usuarioId,
                $antes,
                $depois,
                $command->origem === ReservasTematicasConstants::ORIGIN_CRON
                    ? ReservasTematicasConstants::NO_SHOW_CRON_LOG
                    : ReservasTematicasConstants::NO_SHOW_SERVICE_LOG
            );
            $reservasMarcadasComoNoShow++;
        }

        return ServiceResult::success(ReservasTematicasConstants::MESSAGE_NO_SHOW_AUTO_PROCESSADO, [
            'processadas' => $reservasMarcadasComoNoShow,
        ]);
    }

    private function montarObservacaoNoShowAutomatico(string $observacaoAtual): string
    {
        $observacao = ReservasTematicasConstants::NO_SHOW_AUTO_NOTE;
        $observacaoAtual = trim($observacaoAtual);
        return $observacaoAtual !== '' ? ($observacao . ' ' . $observacaoAtual) : $observacao;
    }

    private function normalizarStatus(string $status): string
    {
        $status = trim(normalize_mojibake($status));
        return $status === ReservasTematicasConstants::STATUS_NO_SHOW_ACCENTED
            ? ReservasTematicasConstants::STATUS_NO_SHOW
            : $status;
    }
}
