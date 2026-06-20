<?php
declare(strict_types=1);

final class OperarReservaService implements OperarReservaServiceInterface
{
    private ReservaTematicaRepositoryInterface $reservas;

    public function __construct(?ReservaTematicaRepositoryInterface $reservas = null)
    {
        $this->reservas = $reservas ?? new ReservaTematicaRepository();
    }

    /**
     * Conduz a operação diária das reservas temáticas: fechamento de turno,
     * confirmação de presença, no-show, cancelamento e ajustes supervisionados.
     */
    public function executar(OperarReservaCommand $command): ServiceResult
    {
        if ($command->acao === ReservasTematicasConstants::ACTION_CLOSE_TURNO) {
            return $this->fecharTurno($command);
        }
        if ($command->acao === ReservasTematicasConstants::ACTION_QUICK_STATUS) {
            $command = $this->converterAcaoRapidaEmStatus($command);
            if ($command instanceof ServiceResult) {
                return $command;
            }
        }
        if ($command->acao === ReservasTematicasConstants::ACTION_UPDATE_DETAIL) {
            return $this->atualizarDetalhesDaOperacao($command);
        }
        if ($command->acao === ReservasTematicasConstants::ACTION_UPDATE_STATUS) {
            return $this->atualizarStatusDaReserva($command);
        }

        return ServiceResult::failure(
            ReservasTematicasConstants::CODE_ACAO_OPERACAO_INVALIDA,
            ReservasTematicasConstants::MESSAGE_ACAO_OPERACAO_INVALIDA
        );
    }

    private function fecharTurno(OperarReservaCommand $command): ServiceResult
    {
        if ($command->restauranteId <= 0 || $command->turnoId <= 0) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_FECHAMENTO_SEM_TURNO,
                ReservasTematicasConstants::MESSAGE_FECHAMENTO_SEM_TURNO
            );
        }

        $this->reservas->fecharTurnoOperacional($command->restauranteId, $command->dataReserva, $command->turnoId, $command->usuarioId);
        return ServiceResult::success(ReservasTematicasConstants::MESSAGE_TURNO_ENCERRADO, [
            'redirect_query' => $this->queryOperacao($command->restauranteId, $command->turnoId, $command->dataReserva),
        ]);
    }

    private function atualizarStatusDaReserva(OperarReservaCommand $command): ServiceResult
    {
        $reservaAtual = $this->reservas->buscarReserva($command->reservaId);
        if (!$reservaAtual) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_RESERVA_NAO_ENCONTRADA,
                ReservasTematicasConstants::MESSAGE_RESERVA_NAO_ENCONTRADA
            );
        }

        $statusDesejado = $this->normalizarStatus($command->status);
        $erroStatus = $this->validarStatusOperacional($statusDesejado, $command->confirmouStatusFinal);
        if ($erroStatus) {
            return $erroStatus;
        }
        $erroStatusDefinitivo = $this->validarAlteracaoDeStatusDefinitivo($reservaAtual, $statusDesejado, $command->usuario);
        if ($erroStatusDefinitivo) {
            return $erroStatusDefinitivo;
        }

        $paxReal = $this->resolverPaxReal($command->paxRealTexto, (int)($reservaAtual['pax'] ?? 0), $statusDesejado);
        if ($paxReal instanceof ServiceResult) {
            return $paxReal;
        }

        $observacao = $this->montarObservacaoOperacional($statusDesejado, (int)($reservaAtual['pax'] ?? 0), $paxReal, $command->observacaoOperacao);
        $erroFechamento = $this->validarEdicaoDeTurnoFechado(
            (int)$reservaAtual['restaurante_id'],
            (string)$reservaAtual['data_reserva'],
            (int)$reservaAtual['turno_id'],
            $command->usuario,
            $command->justificativa,
            false
        );
        if ($erroFechamento) {
            return $erroFechamento;
        }

        $antes = $reservaAtual;
        $this->reservas->atualizarStatusOperacao($command->reservaId, $statusDesejado, $observacao, $command->usuarioId, $paxReal);
        $depois = $this->reservas->buscarReserva($command->reservaId) ?? [];
        $this->reservas->registrarLog($command->reservaId, 'status', $command->usuarioId, $antes, $depois, $command->justificativa !== '' ? $command->justificativa : null);

        return ServiceResult::success(ReservasTematicasConstants::MESSAGE_STATUS_ATUALIZADO);
    }

    private function atualizarDetalhesDaOperacao(OperarReservaCommand $command): ServiceResult
    {
        $reservaAtual = $this->reservas->buscarReserva($command->reservaId);
        if (!$reservaAtual) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_RESERVA_NAO_ENCONTRADA,
                ReservasTematicasConstants::MESSAGE_RESERVA_NAO_ENCONTRADA
            );
        }

        $restauranteDestino = $command->restauranteId > 0 ? $command->restauranteId : (int)$reservaAtual['restaurante_id'];
        $turnoDestino = $command->turnoId > 0 ? $command->turnoId : (int)$reservaAtual['turno_id'];
        $statusDesejado = $this->normalizarStatus($command->status !== '' ? $command->status : (string)$reservaAtual['status']);

        $erroPermissaoDestino = $this->validarDestinoDaOperacao($restauranteDestino, $turnoDestino, $command);
        if ($erroPermissaoDestino) {
            return $erroPermissaoDestino;
        }
        $erroStatus = $this->validarStatusOperacional($statusDesejado, $command->confirmouStatusFinal);
        if ($erroStatus) {
            return $erroStatus;
        }
        $erroStatusDefinitivo = $this->validarAlteracaoDeStatusDefinitivo($reservaAtual, $statusDesejado, $command->usuario);
        if ($erroStatusDefinitivo) {
            return $erroStatusDefinitivo;
        }

        $paxReal = $this->resolverPaxReal($command->paxRealTexto, (int)($reservaAtual['pax'] ?? 0), $statusDesejado);
        if ($paxReal instanceof ServiceResult) {
            return $paxReal;
        }

        $observacao = $this->montarObservacaoOperacional($statusDesejado, (int)($reservaAtual['pax'] ?? 0), $paxReal, $command->observacaoOperacao);
        $erroFechamento = $this->validarEdicaoDeTurnoFechado(
            (int)$reservaAtual['restaurante_id'],
            (string)$reservaAtual['data_reserva'],
            (int)$reservaAtual['turno_id'],
            $command->usuario,
            $command->justificativa,
            true,
            $restauranteDestino,
            $turnoDestino
        );
        if ($erroFechamento) {
            return $erroFechamento;
        }

        if ($statusDesejado !== ReservasTematicasConstants::STATUS_CANCELADA) {
            $erroCapacidade = $this->validarCapacidadeAoMoverReserva($reservaAtual, $restauranteDestino, $turnoDestino);
            if ($erroCapacidade) {
                return $erroCapacidade;
            }
        }

        $antes = $reservaAtual;
        $this->reservas->atualizarDetalhesOperacao($command->reservaId, [
            'restaurante_id' => $restauranteDestino,
            'turno_id' => $turnoDestino,
            'status' => $statusDesejado,
            'observacao_operacao' => $observacao,
            'pax_real' => $paxReal,
        ], $command->usuarioId);
        $depois = $this->reservas->buscarReserva($command->reservaId) ?? [];
        $this->reservas->registrarLog(
            $command->reservaId,
            ReservasTematicasConstants::ACTION_UPDATE_DETAIL,
            $command->usuarioId,
            $antes,
            $depois,
            $command->justificativa !== '' ? $command->justificativa : null
        );

        return ServiceResult::success(ReservasTematicasConstants::MESSAGE_OPERACAO_ATUALIZADA);
    }

    private function converterAcaoRapidaEmStatus(OperarReservaCommand $command)
    {
        if ($command->acaoRapida === ReservasTematicasConstants::QUICK_FINALIZAR) {
            $command->status = ReservasTematicasConstants::STATUS_FINALIZADA;
        } elseif ($command->acaoRapida === ReservasTematicasConstants::QUICK_NO_SHOW) {
            $command->status = ReservasTematicasConstants::STATUS_NO_SHOW;
        } elseif ($command->acaoRapida === ReservasTematicasConstants::QUICK_CANCELAR) {
            $command->status = ReservasTematicasConstants::STATUS_CANCELADA;
        } else {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_ACAO_RAPIDA_INVALIDA,
                ReservasTematicasConstants::MESSAGE_ACAO_RAPIDA_INVALIDA
            );
        }
        $command->acao = ReservasTematicasConstants::ACTION_UPDATE_STATUS;
        $command->confirmouStatusFinal = true;
        return $command;
    }

    private function validarDestinoDaOperacao(int $restauranteId, int $turnoId, OperarReservaCommand $command): ?ServiceResult
    {
        $restaurantesPermitidos = array_map(static fn(array $restaurante): int => (int)($restaurante['id'] ?? 0), $command->restaurantesPermitidos);
        if ($restauranteId <= 0 || !in_array($restauranteId, $restaurantesPermitidos, true)) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_RESTAURANTE_OPERACAO_INVALIDO,
                ReservasTematicasConstants::MESSAGE_RESTAURANTE_OPERACAO_INVALIDO
            );
        }

        $turnosPermitidos = array_map(static fn(array $turno): int => (int)($turno['id'] ?? 0), $command->turnosPermitidos);
        if ($turnoId <= 0 || !in_array($turnoId, $turnosPermitidos, true)) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_TURNO_OPERACAO_INVALIDO,
                ReservasTematicasConstants::MESSAGE_TURNO_OPERACAO_INVALIDO
            );
        }

        return null;
    }

    private function validarStatusOperacional(string $status, bool $confirmouStatusFinal): ?ServiceResult
    {
        if (!in_array($status, ReservasTematicasConstants::ALLOWED_OPERATION_STATUSES, true)) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_STATUS_INVALIDO,
                ReservasTematicasConstants::MESSAGE_STATUS_INVALIDO
            );
        }
        if (in_array($status, ReservasTematicasConstants::FINAL_STATUSES, true) && !$confirmouStatusFinal) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_CONFIRMAR_STATUS_DEFINITIVO,
                ReservasTematicasConstants::MESSAGE_CONFIRMAR_STATUS_DEFINITIVO
            );
        }
        return null;
    }

    private function validarAlteracaoDeStatusDefinitivo(array $reservaAtual, string $statusDesejado, array $usuario): ?ServiceResult
    {
        $statusAtual = $this->normalizarStatus((string)($reservaAtual['status'] ?? ''));
        $statusAtualDefinitivo = in_array($statusAtual, ReservasTematicasConstants::FINAL_STATUSES, true);
        $usuarioPodeReabrir = in_array((string)($usuario['perfil'] ?? ''), ReservasTematicasConstants::PRIVILEGED_ROLES, true);

        if ($statusAtualDefinitivo && $statusDesejado !== $statusAtual && !$usuarioPodeReabrir) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_STATUS_DEFINITIVO_BLOQUEADO,
                ReservasTematicasConstants::MESSAGE_STATUS_DEFINITIVO_BLOQUEADO
            );
        }
        return null;
    }

    private function validarEdicaoDeTurnoFechado(
        int $restauranteAtual,
        string $dataReserva,
        int $turnoAtual,
        array $usuario,
        string $justificativa,
        bool $verificarDestino = false,
        ?int $restauranteDestino = null,
        ?int $turnoDestino = null
    ): ?ServiceResult {
        $fechadoAtual = $this->reservas->turnoOperacionalFechado($restauranteAtual, $dataReserva, $turnoAtual);
        $fechadoDestino = $verificarDestino && $restauranteDestino !== null && $turnoDestino !== null
            ? $this->reservas->turnoOperacionalFechado($restauranteDestino, $dataReserva, $turnoDestino)
            : false;
        $usuarioPrivilegiado = in_array((string)($usuario['perfil'] ?? ''), ReservasTematicasConstants::PRIVILEGED_ROLES, true);

        if (($fechadoAtual || $fechadoDestino) && !$usuarioPrivilegiado) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_TURNO_FECHADO_BLOQUEADO,
                ReservasTematicasConstants::MESSAGE_TURNO_FECHADO_BLOQUEADO
            );
        }
        if (($fechadoAtual || $fechadoDestino) && $usuarioPrivilegiado && $justificativa === '') {
            $mensagem = $verificarDestino
                ? ReservasTematicasConstants::MESSAGE_JUSTIFICATIVA_TURNO_DESTINO
                : ReservasTematicasConstants::MESSAGE_JUSTIFICATIVA_TURNO;
            return ServiceResult::failure(ReservasTematicasConstants::CODE_JUSTIFICATIVA_OBRIGATORIA, $mensagem);
        }
        return null;
    }

    private function validarCapacidadeAoMoverReserva(array $reservaAtual, int $restauranteDestino, int $turnoDestino): ?ServiceResult
    {
        $dataReserva = (string)$reservaAtual['data_reserva'];
        $capacidadeDestino = $this->reservas->capacidadeDoTurno($restauranteDestino, $dataReserva, $turnoDestino);
        if ($capacidadeDestino <= 0) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_CAPACIDADE_DESTINO_NAO_CONFIGURADA,
                ReservasTematicasConstants::MESSAGE_CAPACIDADE_DESTINO_NAO_CONFIGURADA,
                [
                    'capacidade' => $capacidadeDestino,
                    'pax_tentativa' => (int)$reservaAtual['pax'],
                    'data_reserva' => $dataReserva,
                    'restaurante_id' => $restauranteDestino,
                    'turno_id' => $turnoDestino,
                ]
            );
        }

        $paxReservadoDestino = $this->reservas->somarPaxDoTurno($restauranteDestino, $dataReserva, $turnoDestino);
        $mesmoDestino = (int)$reservaAtual['restaurante_id'] === $restauranteDestino
            && (int)$reservaAtual['turno_id'] === $turnoDestino;
        $paxProjetado = $paxReservadoDestino
            - ($mesmoDestino && $this->normalizarStatus((string)$reservaAtual['status']) !== ReservasTematicasConstants::STATUS_CANCELADA ? (int)$reservaAtual['pax'] : 0)
            + (int)$reservaAtual['pax'];

        if ($paxProjetado > $capacidadeDestino) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_CAPACIDADE_DESTINO_ATINGIDA,
                ReservasTematicasConstants::MESSAGE_CAPACIDADE_DESTINO_ATINGIDA,
                [
                    'capacidade' => $capacidadeDestino,
                    'pax_reservado' => max(0, $paxReservadoDestino - ($mesmoDestino && $this->normalizarStatus((string)$reservaAtual['status']) !== ReservasTematicasConstants::STATUS_CANCELADA ? (int)$reservaAtual['pax'] : 0)),
                    'pax_disponivel' => max(0, $capacidadeDestino - ($paxProjetado - (int)$reservaAtual['pax'])),
                    'pax_tentativa' => (int)$reservaAtual['pax'],
                    'pax_projetado' => $paxProjetado,
                    'data_reserva' => $dataReserva,
                    'restaurante_id' => $restauranteDestino,
                    'turno_id' => $turnoDestino,
                ]
            );
        }
        return null;
    }

    private function resolverPaxReal(string $paxRealTexto, int $paxReservado, string $status)
    {
        if ($paxRealTexto !== '') {
            if (!ctype_digit($paxRealTexto)) {
                return ServiceResult::failure(
                    ReservasTematicasConstants::CODE_PAX_REAL_INVALIDO,
                    ReservasTematicasConstants::MESSAGE_PAX_REAL_INVALIDO
                );
            }
            $paxReal = (int)$paxRealTexto;
            if ($paxReal < 0 || $paxReal > $paxReservado) {
                return ServiceResult::failure(
                    ReservasTematicasConstants::CODE_PAX_REAL_FORA_LIMITE,
                    'PAX real deve estar entre 0 e ' . $paxReservado . '.'
                );
            }
            return $paxReal;
        }
        if ($status === ReservasTematicasConstants::STATUS_FINALIZADA) {
            return $paxReservado;
        }
        if ($status === ReservasTematicasConstants::STATUS_NO_SHOW) {
            return ReservasTematicasConstants::DEFAULT_ZERO;
        }
        return null;
    }

    private function montarObservacaoOperacional(string $status, int $paxReservado, ?int $paxReal, string $observacao): string
    {
        if (in_array($status, [ReservasTematicasConstants::STATUS_NO_SHOW, ReservasTematicasConstants::STATUS_FINALIZADA], true) && $paxReal !== null && $paxReal < $paxReservado) {
            $prefixo = sprintf(ReservasTematicasConstants::PARTIAL_NO_SHOW_PREFIX, $paxReservado, $paxReal);
            return $observacao !== '' ? ($prefixo . ' ' . $observacao) : $prefixo;
        }
        return $observacao;
    }

    private function normalizarStatus(string $status): string
    {
        $status = trim(normalize_mojibake($status));
        $map = [
            ReservasTematicasConstants::STATUS_NO_SHOW_ACCENTED => ReservasTematicasConstants::STATUS_NO_SHOW,
            ReservasTematicasConstants::STATUS_DIVERGENCIA_ACCENTED => ReservasTematicasConstants::STATUS_DIVERGENCIA,
            ReservasTematicasConstants::STATUS_OPERACAO_ACCENTED => ReservasTematicasConstants::STATUS_OPERACAO,
            ReservasTematicasConstants::STATUS_CONFERIDA => ReservasTematicasConstants::STATUS_RESERVADA,
            ReservasTematicasConstants::STATUS_EM_ATENDIMENTO => ReservasTematicasConstants::STATUS_RESERVADA,
        ];
        return $map[$status] ?? $status;
    }

    private function queryOperacao(int $restauranteId, int $turnoId, string $dataReserva): string
    {
        return 'restaurante_id=' . $restauranteId . '&turno_id=' . $turnoId . '&data=' . urlencode($dataReserva);
    }
}
