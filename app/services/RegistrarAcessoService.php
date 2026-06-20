<?php
declare(strict_types=1);

final class RegistrarAcessoService implements RegistrarAcessoServiceInterface
{
    private AccessRepositoryInterface $acessos;

    public function __construct(?AccessRepositoryInterface $acessos = null)
    {
        $this->acessos = $acessos ?? new AccessRepository();
    }

    /**
     * Registra a entrada física de um grupo de hóspedes no salão, valida limites da UH
     * e trata conflitos com reservas temáticas antes de atualizar a ocupação do turno.
     */
    public function executar(RegistrarAcessoCommand $command): ServiceResult
    {
        if (empty($command->turno)) {
            return ServiceResult::failure(
                ControleSalaoConstants::CODE_TURNO_NAO_INICIADO,
                ControleSalaoConstants::MESSAGE_TURNO_NAO_INICIADO
            );
        }
        if (TematicAccessService::isTematicShift($command->turno)) {
            return ServiceResult::failure(
                ControleSalaoConstants::CODE_TURNO_TEMATICO,
                ControleSalaoConstants::MESSAGE_TURNO_TEMATICO
            );
        }

        $paxDoLancamento = $this->normalizarPaxDoLancamento($command);
        $uh = (new UnitModel())->findByNumero($command->uhNumero);
        $erroCampos = $this->validarCamposObrigatorios($command, $paxDoLancamento, $uh);
        if ($erroCampos) {
            return $erroCampos;
        }

        $uhNumeroOficial = (string)($uh['numero'] ?? $command->uhNumero);
        $erroLimiteUh = $this->validarCapacidadeMaximaDaUh($command, $uhNumeroOficial, $paxDoLancamento);
        if ($erroLimiteUh) {
            return $erroLimiteUh;
        }

        $resultadoTematico = $this->validarReservaTematicaNoJantarCorais($command, $uhNumeroOficial, $paxDoLancamento);
        if ($resultadoTematico && !$resultadoTematico->isSuccess()) {
            return $resultadoTematico;
        }
        $noShowTematicoConfirmado = $resultadoTematico
            && $resultadoTematico->code() === ControleSalaoConstants::CODE_NO_SHOW_TEMATICO_CONFIRMADO;

        if (!$command->confirmouDuplicidade && $this->acessos->existeDuplicidadeImediata(
            $uhNumeroOficial,
            (int)$command->turno['restaurante_id'],
            (int)$command->turno['operacao_id'],
            $paxDoLancamento,
            (int)$command->turno['id'],
            $command->usuarioId
        )) {
            return ServiceResult::needsConfirmation(
                ControleSalaoConstants::CODE_CONFIRMAR_DUPLICIDADE,
                ControleSalaoConstants::MESSAGE_DUPLICIDADE_IMEDIATA,
                [
                    'uh_numero' => $uhNumeroOficial,
                    'pax' => $paxDoLancamento,
                    'tematica_processed' => $command->reservaTematicaJaProcessada
                        ? ControleSalaoConstants::SQL_TRUE
                        : ControleSalaoConstants::SQL_FALSE,
                    'confirm_no_show' => $command->confirmouNoShowTematico
                        ? ControleSalaoConstants::SQL_TRUE
                        : ControleSalaoConstants::SQL_FALSE,
                ]
            );
        }

        $resultadoRegistro = $this->acessos->registrarEntradaSalao([
            'uh_numero' => $uhNumeroOficial,
            'pax' => $paxDoLancamento,
            'restaurante_id' => (int)$command->turno['restaurante_id'],
            'porta_id' => $command->turno['porta_id'] ?? null,
            'operacao_id' => (int)$command->turno['operacao_id'],
            'turno_id' => (int)$command->turno['id'],
        ], $command->usuarioId);

        return $this->traduzirResultadoDoRegistro($resultadoRegistro, $command->confirmouDuplicidade, $noShowTematicoConfirmado);
    }

    private function normalizarPaxDoLancamento(RegistrarAcessoCommand $command): int
    {
        if (
            (int)($command->turno['exige_pax'] ?? ControleSalaoConstants::SQL_TRUE)
            === ControleSalaoConstants::SQL_FALSE
        ) {
            return max(ControleSalaoConstants::MIN_ACCESS_LIST_LIMIT, $command->paxInformado);
        }

        return $command->paxInformado;
    }

    private function validarCamposObrigatorios(RegistrarAcessoCommand $command, int $paxDoLancamento, ?array $uh): ?ServiceResult
    {
        $turnoExigePax = (int)($command->turno['exige_pax'] ?? ControleSalaoConstants::SQL_TRUE)
            === ControleSalaoConstants::SQL_TRUE;
        if ($command->uhNumero === '' || ($turnoExigePax && $paxDoLancamento <= 0)) {
            return ServiceResult::failure(
                ControleSalaoConstants::CODE_CAMPO_OBRIGATORIO,
                ControleSalaoConstants::MESSAGE_CAMPOS_OBRIGATORIOS
            );
        }
        if (!$uh) {
            return ServiceResult::failure(
                ControleSalaoConstants::CODE_UH_INVALIDA,
                ControleSalaoConstants::MESSAGE_UH_INVALIDA
            );
        }

        return null;
    }

    private function validarCapacidadeMaximaDaUh(RegistrarAcessoCommand $command, string $uhNumero, int $paxDoLancamento): ?ServiceResult
    {
        if (
            (int)($command->turno['exige_pax'] ?? ControleSalaoConstants::SQL_TRUE)
            !== ControleSalaoConstants::SQL_TRUE
        ) {
            return null;
        }

        $limitePaxDaUh = (new UnitModel())->maxPaxForNumero($uhNumero);
        if ($limitePaxDaUh === null) {
            return null;
        }
        if ($paxDoLancamento > $limitePaxDaUh) {
            return ServiceResult::failure(
                ControleSalaoConstants::CODE_PAX_ACIMA_DA_UH,
                'PAX excede o limite da UH. Máximo permitido: ' . $limitePaxDaUh . '.'
            );
        }

        $dataOperacao = (new DateTime('now', new DateTimeZone(date_default_timezone_get())))->format('Y-m-d');
        $paxJaConsumidoNaOperacao = $this->acessos->totalPaxDaUhNaOperacaoDoDia(
            $uhNumero,
            (int)$command->turno['operacao_id'],
            $dataOperacao
        );
        if (($paxJaConsumidoNaOperacao + $paxDoLancamento) > $limitePaxDaUh) {
            $paxAindaDisponivel = max(0, $limitePaxDaUh - $paxJaConsumidoNaOperacao);
            return ServiceResult::failure(
                ControleSalaoConstants::CODE_PAX_DIARIO_ACIMA_DA_OPERACAO,
                'PAX excede o limite da UH para esta operação no dia. Restante disponível: ' . $paxAindaDisponivel . '.'
            );
        }

        return null;
    }

    private function validarReservaTematicaNoJantarCorais(RegistrarAcessoCommand $command, string $uhNumero, int $paxBuffet): ?ServiceResult
    {
        if ($command->reservaTematicaJaProcessada || !$this->turnoEhJantarCorais($command->turno)) {
            return null;
        }

        $dataOperacao = (new DateTime('now', new DateTimeZone(date_default_timezone_get())))->format('Y-m-d');
        $reservaTematicaModel = new ReservaTematicaModel();
        $reservaTematica = $reservaTematicaModel->findTematicaByUhDate($uhNumero, $dataOperacao);
        if (!$reservaTematica) {
            return null;
        }

        if ($this->statusEhNoShow((string)($reservaTematica['status'] ?? ''))) {
            if (!$command->confirmouNoShowTematico) {
                return ServiceResult::needsConfirmation(
                    ControleSalaoConstants::CODE_CONFIRMAR_NO_SHOW_TEMATICO,
                    'UH ' . $uhNumero . ' está como no-show no temático (' . $reservaTematica['restaurante'] . '). Confirme para registrar no Jantar Corais.'
                );
            }
            return ServiceResult::success(
                ControleSalaoConstants::MESSAGE_NO_SHOW_TEMATICO_CONFIRMADO,
                [],
                ControleSalaoConstants::CODE_NO_SHOW_TEMATICO_CONFIRMADO
            );
        }

        return $this->processarConflitoTematicoCorais($command, $reservaTematica, $uhNumero, $paxBuffet, $reservaTematicaModel);
    }

    private function processarConflitoTematicoCorais(
        RegistrarAcessoCommand $command,
        array $reservaTematica,
        string $uhNumero,
        int $paxBuffet,
        ReservaTematicaModel $reservaTematicaModel
    ): ServiceResult {
        $reservaTematicaId = (int)($reservaTematica['id'] ?? 0);
        $paxReservadoTematica = (int)($reservaTematica['pax'] ?? 0);

        if ($command->acaoReservaTematica === '') {
            $mensagem = 'Atenção: UH ' . $uhNumero . ' possui reserva no ' . $reservaTematica['restaurante'];
            if (!empty($reservaTematica['turno_hora'])) {
                $mensagem .= ' às ' . $reservaTematica['turno_hora'];
            }
            $mensagem .= '. Escolha uma ação antes de registrar no buffet.';

            return ServiceResult::needsConfirmation(ControleSalaoConstants::CODE_CONFLITO_RESERVA_TEMATICA, $mensagem, [
                'reserva_id' => $reservaTematicaId,
                'uh_numero' => $uhNumero,
                'turno_hora' => (string)($reservaTematica['turno_hora'] ?? ''),
                'restaurante' => (string)($reservaTematica['restaurante'] ?? ''),
                'pax_reservado' => $paxReservadoTematica,
                'pax_sugerido' => $paxBuffet,
            ]);
        }

        if ($command->reservaTematicaId !== $reservaTematicaId) {
            return ServiceResult::failure(
                ControleSalaoConstants::CODE_RESERVA_TEMATICA_MUDOU,
                ControleSalaoConstants::MESSAGE_RESERVA_TEMATICA_MUDOU
            );
        }
        if ($command->acaoReservaTematica === ControleSalaoConstants::ACTION_TEMATICA_CANCELAR) {
            return $this->marcarReservaTematicaComoNoShow($reservaTematicaId, $uhNumero, $command, $reservaTematicaModel);
        }
        if ($command->acaoReservaTematica === ControleSalaoConstants::ACTION_TEMATICA_PAX_REAL) {
            return $this->ajustarPaxRealDaReservaTematica($reservaTematicaId, $paxReservadoTematica, $paxBuffet, $command, $reservaTematicaModel);
        }

        return ServiceResult::failure(
            ControleSalaoConstants::CODE_ACAO_TEMATICA_INVALIDA,
            ControleSalaoConstants::MESSAGE_ACAO_TEMATICA_INVALIDA
        );
    }

    private function marcarReservaTematicaComoNoShow(
        int $reservaTematicaId,
        string $uhNumero,
        RegistrarAcessoCommand $command,
        ReservaTematicaModel $reservaTematicaModel
    ): ServiceResult {
        $antes = $reservaTematicaModel->find($reservaTematicaId) ?? [];
        $observacao = 'Cancelada no buffet Corais durante registro de entrada da UH ' . $uhNumero . '.';
        $reservaTematicaModel->updateOperacao(
            $reservaTematicaId,
            ControleSalaoConstants::STATUS_NO_SHOW,
            $observacao,
            $command->usuarioId,
            0
        );
        $depois = $reservaTematicaModel->find($reservaTematicaId) ?? [];
        (new ReservaTematicaLogModel())->log($reservaTematicaId, 'status', $command->usuarioId, $antes, $depois, 'No-show manual a partir do alerta no buffet Corais.');

        return ServiceResult::success(
            ControleSalaoConstants::MESSAGE_RESERVA_TEMATICA_NO_SHOW,
            [],
            ControleSalaoConstants::CODE_RESERVA_TEMATICA_PROCESSADA
        );
    }

    private function ajustarPaxRealDaReservaTematica(
        int $reservaTematicaId,
        int $paxReservadoTematica,
        int $paxBuffet,
        RegistrarAcessoCommand $command,
        ReservaTematicaModel $reservaTematicaModel
    ): ServiceResult {
        if ($paxReservadoTematica <= 0 || $command->paxRealTematico < 0 || $command->paxRealTematico > $paxReservadoTematica) {
            return ServiceResult::failure(
                ControleSalaoConstants::CODE_PAX_REAL_TEMATICO_INVALIDO,
                ControleSalaoConstants::MESSAGE_PAX_REAL_TEMATICO_INVALIDO
            );
        }

        $paxRestanteBuffet = max(0, $paxReservadoTematica - $command->paxRealTematico);
        if ($paxRestanteBuffet <= 0) {
            return ServiceResult::failure(ControleSalaoConstants::CODE_SEM_PAX_PARA_BUFFET, ControleSalaoConstants::MESSAGE_SEM_PAX_PARA_BUFFET);
        }
        if ($paxBuffet > $paxRestanteBuffet) {
            return ServiceResult::failure(
                ControleSalaoConstants::CODE_PAX_BUFFET_EXCEDE_TEMATICO,
                'PAX do buffet excede o restante após PAX real do temático (máx. ' . $paxRestanteBuffet . ').'
            );
        }

        if ($command->paxRealTematico <= 0) {
            $novoStatus = ControleSalaoConstants::STATUS_NO_SHOW;
            $observacao = 'No-show total no temático após opção de buffet Corais.';
            $motivoLog = 'No-show total gerado no buffet Corais.';
        } else {
            $novoStatus = ControleSalaoConstants::STATUS_TEMATICA_DIVERGENCIA;
            $noShowParcial = max(0, $paxReservadoTematica - $command->paxRealTematico);
            $observacao = 'No-show parcial no temático gerado no buffet Corais: reservado=' . $paxReservadoTematica . ', no-show=' . $noShowParcial . ', compareceu=' . $command->paxRealTematico . ', buffet=' . $paxBuffet . '.';
            $motivoLog = 'No-show parcial gerado no buffet Corais.';
        }

        $antes = $reservaTematicaModel->find($reservaTematicaId) ?? [];
        $reservaTematicaModel->updateOperacao($reservaTematicaId, $novoStatus, $observacao, $command->usuarioId, $command->paxRealTematico);
        $depois = $reservaTematicaModel->find($reservaTematicaId) ?? [];
        (new ReservaTematicaLogModel())->log($reservaTematicaId, 'status', $command->usuarioId, $antes, $depois, $motivoLog);

        return ServiceResult::success(
            ControleSalaoConstants::MESSAGE_RESERVA_TEMATICA_AJUSTADA,
            [],
            ControleSalaoConstants::CODE_RESERVA_TEMATICA_PROCESSADA
        );
    }

    private function traduzirResultadoDoRegistro(array $resultadoRegistro, bool $duplicidadeConfirmada, bool $noShowTematicoConfirmado): ServiceResult
    {
        if (($resultadoRegistro['error'] ?? '') === ControleSalaoConstants::CODE_UH_INVALIDA) {
            return ServiceResult::failure(ControleSalaoConstants::CODE_UH_INVALIDA, ControleSalaoConstants::MESSAGE_UH_INVALIDA);
        }
        if ((int)($resultadoRegistro['id'] ?? 0) <= 0) {
            return ServiceResult::failure(ControleSalaoConstants::CODE_FALHA_REGISTRO, ControleSalaoConstants::MESSAGE_FALHA_REGISTRO);
        }
        if ($noShowTematicoConfirmado) {
            return ServiceResult::success(
                ControleSalaoConstants::MESSAGE_NO_SHOW_TEMATICO_CONFIRMADO,
                $resultadoRegistro,
                ControleSalaoConstants::CODE_REGISTRO_NO_SHOW_TEMATICO
            );
        }
        if ($duplicidadeConfirmada) {
            return ServiceResult::success(
                ControleSalaoConstants::MESSAGE_DUPLICIDADE_CONFIRMADA,
                $resultadoRegistro,
                ControleSalaoConstants::CODE_REGISTRO_DUPLICADO_CONFIRMADO
            );
        }
        if (!empty($resultadoRegistro['alerta_duplicidade'])) {
            return ServiceResult::success(
                ControleSalaoConstants::MESSAGE_ALERTA_DUPLICIDADE,
                $resultadoRegistro,
                ControleSalaoConstants::CODE_ALERTA_DUPLICIDADE
            );
        }
        if (!empty($resultadoRegistro['fora_do_horario'])) {
            return ServiceResult::success(
                ControleSalaoConstants::MESSAGE_FORA_HORARIO,
                $resultadoRegistro,
                ControleSalaoConstants::CODE_FORA_HORARIO
            );
        }

        return ServiceResult::success(ControleSalaoConstants::MESSAGE_ACESSO_REGISTRADO, $resultadoRegistro);
    }

    private function turnoEhJantarCorais(array $turno): bool
    {
        return stripos((string)($turno['restaurante'] ?? ''), ControleSalaoConstants::RESTAURANT_CORAIS_KEYWORD) !== false
            && stripos((string)($turno['operacao'] ?? ''), ControleSalaoConstants::OPERATION_JANTAR_KEYWORD) !== false;
    }

    private function statusEhNoShow(string $status): bool
    {
        $normalizado = mb_strtolower(normalize_mojibake(trim($status)), 'UTF-8');
        return in_array($normalizado, ControleSalaoConstants::STATUS_NO_SHOW_NORMALIZED, true);
    }
}
