<?php
declare(strict_types=1);

final class AbrirTurnoService implements AbrirTurnoServiceInterface
{
    private ShiftRepositoryInterface $turnos;

    public function __construct(?ShiftRepositoryInterface $turnos = null)
    {
        $this->turnos = $turnos ?? new ShiftRepository();
    }

    /**
     * Abre a operação de salão para uma hostess, validando escala, porta,
     * checklist e horário antes de liberar os registros de acesso.
     */
    public function executar(AbrirTurnoCommand $command): ServiceResult
    {
        if ($this->turnos->turnoAtivoDoUsuario($command->usuarioId)) {
            return ServiceResult::failure(
                ControleSalaoConstants::CODE_TURNO_JA_ABERTO,
                ControleSalaoConstants::MESSAGE_TURNO_JA_ABERTO
            );
        }

        $erroSelecao = $this->validarSelecaoInicial($command);
        if ($erroSelecao) {
            return $erroSelecao;
        }

        $erroPermissao = $this->validarPermissaoDaHostess($command);
        if ($erroPermissao) {
            return $erroPermissao;
        }

        $erroPorta = $this->validarPortaDaOperacao($command);
        if ($erroPorta) {
            return $erroPorta;
        }

        $restaurante = (new RestaurantModel())->find($command->restauranteId);
        $operacao = (new OperationModel())->find($command->operacaoId);
        if ($command->restringirLaBrasaAoAlmoco && !$this->laBrasaEstaNoAlmoco($restaurante, $operacao)) {
            return ServiceResult::failure(
                ControleSalaoConstants::CODE_LA_BRASA_FORA_ALMOCO,
                ControleSalaoConstants::MESSAGE_LA_BRASA_FORA_ALMOCO
            );
        }

        if ($this->restauranteExigePorta($restaurante) && $command->portaId <= 0) {
            return ServiceResult::failure(
                ControleSalaoConstants::CODE_PORTA_OBRIGATORIA,
                ControleSalaoConstants::MESSAGE_PORTA_OBRIGATORIA
            );
        }

        $regraOperacao = (new RestaurantOperationModel())->findByRestaurantOperation($command->restauranteId, $command->operacaoId);
        if (!$regraOperacao) {
            return ServiceResult::failure(
                ControleSalaoConstants::CODE_OPERACAO_INVALIDA,
                ControleSalaoConstants::MESSAGE_OPERACAO_INVALIDA
            );
        }

        if (!$command->modoDemo && !$command->confirmouForaDoHorario && $this->foraDoHorarioDaOperacao($regraOperacao)) {
            return ServiceResult::needsConfirmation(
                ControleSalaoConstants::CODE_CONFIRMAR_FORA_HORARIO,
                ControleSalaoConstants::MESSAGE_TURNO_FORA_HORARIO
            );
        }

        if (!$command->confirmouChecklist) {
            return ServiceResult::needsConfirmation(
                ControleSalaoConstants::CODE_CONFIRMAR_CHECKLIST,
                ControleSalaoConstants::MESSAGE_CHECKLIST_OBRIGATORIO
            );
        }

        $turnoId = $this->turnos->abrirTurno([
            'restaurante_id' => $command->restauranteId,
            'operacao_id' => $command->operacaoId,
            'porta_id' => $command->portaId > 0 ? $command->portaId : null,
            'modo_demo' => $command->modoDemo
                ? ControleSalaoConstants::SQL_TRUE
                : ControleSalaoConstants::SQL_FALSE,
        ], $command->usuarioId);

        return ServiceResult::success(ControleSalaoConstants::MESSAGE_TURNO_INICIADO, ['turno_id' => $turnoId]);
    }

    private function validarSelecaoInicial(AbrirTurnoCommand $command): ?ServiceResult
    {
        if ($command->restauranteId <= 0 || $command->operacaoId <= 0) {
            return ServiceResult::failure(
                ControleSalaoConstants::CODE_SELECAO_INCOMPLETA,
                ControleSalaoConstants::MESSAGE_SELECAO_INCOMPLETA
            );
        }

        return null;
    }

    private function validarPermissaoDaHostess(AbrirTurnoCommand $command): ?ServiceResult
    {
        if ($command->perfilUsuario !== AppConstants::ROLE_HOSTESS) {
            return null;
        }

        $restaurantesPermitidos = array_values(array_unique(array_map(
            static fn(array $restaurante): int => (int)($restaurante['id'] ?? 0),
            $command->restaurantesPermitidos
        )));
        if (!in_array($command->restauranteId, $restaurantesPermitidos, true)) {
            return ServiceResult::failure(
                ControleSalaoConstants::CODE_RESTAURANTE_NAO_AUTORIZADO,
                ControleSalaoConstants::MESSAGE_RESTAURANTE_NAO_AUTORIZADO
            );
        }

        $operacoesPermitidas = array_values(array_unique(array_map(
            'intval',
            $command->operacoesPermitidasPorRestaurante[$command->restauranteId] ?? []
        )));
        if (!empty($operacoesPermitidas) && !in_array($command->operacaoId, $operacoesPermitidas, true)) {
            return ServiceResult::failure(
                ControleSalaoConstants::CODE_OPERACAO_NAO_AUTORIZADA,
                ControleSalaoConstants::MESSAGE_OPERACAO_NAO_AUTORIZADA
            );
        }

        return null;
    }

    private function validarPortaDaOperacao(AbrirTurnoCommand $command): ?ServiceResult
    {
        if ($command->portaId <= 0) {
            return null;
        }

        $portasValidas = array_values(array_unique(array_map(
            static fn(array $porta): int => (int)($porta['id'] ?? 0),
            $command->portasPorRestaurante[$command->restauranteId] ?? []
        )));
        if (!in_array($command->portaId, $portasValidas, true)) {
            return ServiceResult::failure(
                ControleSalaoConstants::CODE_PORTA_INVALIDA,
                ControleSalaoConstants::MESSAGE_PORTA_INVALIDA
            );
        }

        return null;
    }

    private function restauranteExigePorta(?array $restaurante): bool
    {
        return $restaurante && (int)($restaurante['seleciona_porta_no_turno'] ?? 0) === 1;
    }

    private function laBrasaEstaNoAlmoco(?array $restaurante, ?array $operacao): bool
    {
        if (
            !$restaurante
            || stripos((string)($restaurante['nome'] ?? ''), ControleSalaoConstants::RESTAURANT_LA_BRASA_KEYWORD) === false
        ) {
            return true;
        }

        $nomeOperacao = mb_strtolower((string)($operacao['nome'] ?? ''), 'UTF-8');
        foreach (ControleSalaoConstants::OPERATION_ALMOCO_KEYWORDS as $keyword) {
            if (strpos($nomeOperacao, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    private function foraDoHorarioDaOperacao(array $regraOperacao): bool
    {
        $tz = new DateTimeZone(date_default_timezone_get());
        $agora = new DateTime('now', $tz);
        $inicio = DateTime::createFromFormat('H:i:s', (string)$regraOperacao['hora_inicio'], $tz);
        $fim = DateTime::createFromFormat('H:i:s', (string)$regraOperacao['hora_fim'], $tz);
        if (!$inicio) {
            return false;
        }
        $inicio->setDate((int)$agora->format('Y'), (int)$agora->format('m'), (int)$agora->format('d'));
        if (!$fim) {
            return $agora < $inicio;
        }
        $fim->setDate((int)$agora->format('Y'), (int)$agora->format('m'), (int)$agora->format('d'));
        if ($fim < $inicio) {
            $fim->modify('+1 day');
        }
        return $agora < $inicio || $agora > $fim;
    }
}
