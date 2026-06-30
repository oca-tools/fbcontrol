<?php
declare(strict_types=1);

final class CriarReservaService implements CriarReservaServiceInterface
{
    private ReservaTematicaRepositoryInterface $reservas;
    private UnitRepositoryInterface $unidades;

    public function __construct(?ReservaTematicaRepositoryInterface $reservas = null, ?UnitRepositoryInterface $unidades = null)
    {
        $this->reservas = $reservas ?? new ReservaTematicaRepository();
        $this->unidades = $unidades ?? new UnitRepository();
    }

    /**
     * Cria ou atualiza reservas temáticas do A&B, incluindo reservas em lote,
     * validação de capacidade do turno e bloqueios de duplicidade por UH.
     */
    public function executar(CriarReservaCommand $command): ServiceResult
    {
        try {
            if ($command->hostessForaDaJanela) {
                return ServiceResult::failure(
                    ReservasTematicasConstants::CODE_FORA_JANELA_RESERVA,
                    ReservasTematicasConstants::MESSAGE_FORA_JANELA_RESERVA
                );
            }

            $erroBase = $this->validarRestauranteTurnoEData($command);
            if ($erroBase) {
                return $erroBase;
            }

            if ($command->acao === ReservasTematicasConstants::ACTION_CREATE_BATCH) {
                return $this->criarGrupoDeReservas($command);
            }

            return $command->acao === ReservasTematicasConstants::ACTION_UPDATE
                ? $this->atualizarReserva($command)
                : $this->criarReservaIndividual($command);
        } catch (Throwable $error) {
            return $this->falhaDePersistencia($command, $error);
        }
    }

    private function validarRestauranteTurnoEData(CriarReservaCommand $command): ?ServiceResult
    {
        $restaurantesPermitidos = array_map(static fn(array $restaurante): int => (int)($restaurante['id'] ?? 0), $command->restaurantesPermitidos);
        if ($command->restauranteId <= 0 || !in_array($command->restauranteId, $restaurantesPermitidos, true)) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_RESTAURANTE_INVALIDO,
                ReservasTematicasConstants::MESSAGE_RESTAURANTE_INVALIDO
            );
        }
        if ($command->turnoId <= 0) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_TURNO_OBRIGATORIO,
                ReservasTematicasConstants::MESSAGE_TURNO_OBRIGATORIO
            );
        }
        if ($this->reservas->restauranteFechadoNaData($command->restauranteId, $command->dataReserva)) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_RESTAURANTE_FECHADO,
                ReservasTematicasConstants::MESSAGE_RESTAURANTE_FECHADO
            );
        }

        return null;
    }

    private function criarReservaIndividual(CriarReservaCommand $command): ServiceResult
    {
        $dadosDaReserva = $this->prepararReservaIndividual($command);
        if ($dadosDaReserva instanceof ServiceResult) {
            return $dadosDaReserva;
        }

        $erroCapacidade = $this->validarCapacidadeDoTurno(
            $command->restauranteId,
            $command->dataReserva,
            $command->turnoId,
            (int)$dadosDaReserva['pax']
        );
        if ($erroCapacidade) {
            return $erroCapacidade;
        }

        $reservaId = 0;
        $this->reservas->executarTransacao(function () use ($dadosDaReserva, $command, &$reservaId): void {
            $reservaId = $this->reservas->criarReserva($dadosDaReserva, $command->usuarioId);
            $this->reservas->substituirIdadesChd($reservaId, $dadosDaReserva['idades_chd']);
            $this->reservas->registrarLog($reservaId, ReservasTematicasConstants::ACTION_CREATE, $command->usuarioId, [], $this->reservas->buscarReserva($reservaId) ?? []);
        });

        return ServiceResult::success(ReservasTematicasConstants::MESSAGE_RESERVA_CRIADA, ['reserva_id' => $reservaId]);
    }

    private function atualizarReserva(CriarReservaCommand $command): ServiceResult
    {
        $reservaAtual = $this->reservas->buscarReserva($command->reservaId);
        if (!$reservaAtual) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_RESERVA_NAO_ENCONTRADA,
                ReservasTematicasConstants::MESSAGE_RESERVA_NAO_ENCONTRADA
            );
        }
        if (!ReservaTematicaPolicy::canEdit($reservaAtual, $command->usuario)) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_EDICAO_NAO_AUTORIZADA,
                ReservasTematicasConstants::MESSAGE_EDICAO_NAO_AUTORIZADA
            );
        }

        $dadosDaReserva = $this->prepararReservaIndividual($command);
        if ($dadosDaReserva instanceof ServiceResult) {
            return $dadosDaReserva;
        }

        $erroCapacidade = $this->validarCapacidadeDoTurno(
            $command->restauranteId,
            $command->dataReserva,
            $command->turnoId,
            (int)$dadosDaReserva['pax'],
            $reservaAtual
        );
        if ($erroCapacidade) {
            return $erroCapacidade;
        }

        $antes = $reservaAtual;
        $this->reservas->executarTransacao(function () use ($command, $dadosDaReserva, $antes): void {
            $this->reservas->atualizarReserva($command->reservaId, $dadosDaReserva, $command->usuarioId);
            $this->reservas->substituirIdadesChd($command->reservaId, $dadosDaReserva['idades_chd']);
            $depois = $this->reservas->buscarReserva($command->reservaId) ?? [];
            $this->reservas->registrarLog($command->reservaId, ReservasTematicasConstants::ACTION_UPDATE, $command->usuarioId, $antes, $depois);
        });

        return ServiceResult::success(ReservasTematicasConstants::MESSAGE_RESERVA_ATUALIZADA, ['reserva_id' => $command->reservaId]);
    }

    private function criarGrupoDeReservas(CriarReservaCommand $command): ServiceResult
    {
        if ($command->grupoResponsavel === '') {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_GRUPO_SEM_TITULAR,
                ReservasTematicasConstants::MESSAGE_GRUPO_SEM_TITULAR
            );
        }

        $itensDoGrupo = $this->prepararItensDoGrupo($command);
        if ($itensDoGrupo instanceof ServiceResult) {
            return $itensDoGrupo;
        }
        if (empty($itensDoGrupo)) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_GRUPO_SEM_UH,
                ReservasTematicasConstants::MESSAGE_GRUPO_SEM_UH
            );
        }
        if (count($itensDoGrupo) < 2) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_GRUPO_UH_MINIMO,
                ReservasTematicasConstants::MESSAGE_GRUPO_UH_MINIMO
            );
        }

        $paxTotalDoGrupo = array_sum(array_map(static fn(array $item): int => (int)$item['pax'], $itensDoGrupo));
        $erroCapacidade = $this->validarCapacidadeDoTurno($command->restauranteId, $command->dataReserva, $command->turnoId, $paxTotalDoGrupo);
        if ($erroCapacidade) {
            return $erroCapacidade;
        }

        $idsCriados = [];
        $this->reservas->executarTransacao(function () use ($command, $itensDoGrupo, &$idsCriados): void {
            $grupoId = $this->reservas->criarGrupo([
                'restaurante_id' => $command->restauranteId,
                'data_reserva' => $command->dataReserva,
                'turno_id' => $command->turnoId,
                'responsavel_nome' => $command->grupoResponsavel,
                'observacao_grupo' => $command->observacaoReserva !== '' ? $command->observacaoReserva : null,
            ], $command->usuarioId);

            $grupoNome = $command->grupoNome !== '' ? $command->grupoNome : $command->grupoResponsavel;
            foreach ($itensDoGrupo as $item) {
                $payload = $this->montarPayloadReserva($command, [
                    'uh_id' => $item['uh_id'],
                    'titular_nome' => $command->grupoResponsavel,
                    'pax' => $item['pax'],
                    'pax_adulto' => $item['pax_adulto'],
                    'pax_chd' => $item['qtd_chd'],
                    'qtd_chd' => $item['qtd_chd'],
                    'grupo_id' => $grupoId > ReservasTematicasConstants::DEFAULT_ZERO ? $grupoId : null,
                    'grupo_nome' => $grupoNome,
                ]);
                $reservaId = $this->reservas->criarReserva($payload, $command->usuarioId);
                $this->reservas->substituirIdadesChd($reservaId, $item['idades']);
                $this->reservas->registrarLog($reservaId, ReservasTematicasConstants::ACTION_CREATE, $command->usuarioId, [], $this->reservas->buscarReserva($reservaId) ?? []);
                $idsCriados[] = $reservaId;
            }
        });

        return ServiceResult::success(ReservasTematicasConstants::MESSAGE_GRUPO_CRIADO, ['reservas_ids' => $idsCriados]);
    }

    private function prepararReservaIndividual(CriarReservaCommand $command)
    {
        if ($command->pax <= 0) {
            $sufixoUh = $command->uhNumero !== '' ? ' para a UH ' . $command->uhNumero : '';
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_PAX_INVALIDO,
                'Informe uma quantidade válida de PAX' . $sufixoUh . '.',
                ['uh_numero' => $command->uhNumero]
            );
        }

        try {
            $idadesChd = ReservaTematicaPolicy::parseChdAgeEntries($command->chdIdadesTexto);
        } catch (RuntimeException $e) {
            $prefixoUh = $command->uhNumero !== '' ? 'UH ' . $command->uhNumero . ': ' : '';
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_IDADES_CHD_INVALIDAS,
                $prefixoUh . $e->getMessage(),
                ['uh_numero' => $command->uhNumero]
            );
        }

        $qtdChd = count($idadesChd);
        if ($qtdChd > $command->pax) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_CHD_MAIOR_QUE_PAX,
                ReservasTematicasConstants::MESSAGE_CHD_MAIOR_QUE_PAX . ($command->uhNumero !== '' ? ' UH: ' . $command->uhNumero . '.' : ''),
                ['uh_numero' => $command->uhNumero]
            );
        }
        if ($command->uhNumero === '') {
            return ServiceResult::failure(ReservasTematicasConstants::CODE_UH_OBRIGATORIA, ReservasTematicasConstants::MESSAGE_UH_OBRIGATORIA);
        }

        $uh = $this->unidades->buscarUhPorNumero($command->uhNumero);
        if (!$uh) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_UH_INVALIDA,
                'UH inválida: ' . $command->uhNumero . '. Confira o número informado.',
                ['uh_numero' => $command->uhNumero]
            );
        }
        if ($command->titularNome === '') {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_TITULAR_OBRIGATORIO,
                ReservasTematicasConstants::MESSAGE_TITULAR_OBRIGATORIO
            );
        }

        $erroLimiteUh = $this->validarLimiteDaUh(
            (string)($uh['numero'] ?? $command->uhNumero),
            $command->pax
        );
        if ($erroLimiteUh) {
            return $erroLimiteUh;
        }

        $erroDuplicidade = $this->validarDuplicidadeDaUh((int)$uh['id'], (string)($uh['numero'] ?? $command->uhNumero), $command, $command->reservaId);
        if ($erroDuplicidade) {
            return $erroDuplicidade;
        }

        return $this->montarPayloadReserva($command, [
            'uh_id' => (int)$uh['id'],
            'titular_nome' => $command->titularNome,
            'pax' => $command->pax,
            'pax_adulto' => max(ReservasTematicasConstants::DEFAULT_ZERO, $command->pax - $qtdChd),
            'pax_chd' => $qtdChd,
            'qtd_chd' => $qtdChd,
            'grupo_nome' => $command->grupoNome !== '' ? $command->grupoNome : null,
            'idades_chd' => $idadesChd,
        ]);
    }

    private function prepararItensDoGrupo(CriarReservaCommand $command)
    {
        $itens = [];
        $uhsJaInformadas = [];

        foreach ($command->batchUhs as $indice => $uhRaw) {
            $uhNumero = trim((string)$uhRaw);
            if ($uhNumero === '') {
                continue;
            }

            $uhKey = mb_strtolower($uhNumero, 'UTF-8');
            if (isset($uhsJaInformadas[$uhKey])) {
                return ServiceResult::failure(ReservasTematicasConstants::CODE_UH_DUPLICADA_GRUPO, 'UH duplicada no grupo: ' . $uhNumero);
            }
            $uhsJaInformadas[$uhKey] = true;

            $paxItem = (int)($command->batchPax[$indice] ?? 0);
            if ($paxItem <= 0) {
                return ServiceResult::failure(
                    ReservasTematicasConstants::CODE_PAX_GRUPO_INVALIDO,
                    'Informe uma quantidade válida de PAX para a UH ' . $uhNumero . '.',
                    ['uh_numero' => $uhNumero, 'linha' => $indice + 1]
                );
            }

            try {
                $idadesItem = ReservaTematicaPolicy::parseChdAgeEntries((string)($command->batchChdIdades[$indice] ?? ''));
            } catch (RuntimeException $e) {
                return ServiceResult::failure(
                    ReservasTematicasConstants::CODE_IDADES_CHD_INVALIDAS,
                    'Idades CHD inválidas na UH ' . $uhNumero . ': ' . $e->getMessage(),
                    ['uh_numero' => $uhNumero, 'linha' => $indice + 1]
                );
            }

            $qtdChdItem = count($idadesItem);
            if ($qtdChdItem > $paxItem) {
                return ServiceResult::failure(
                    ReservasTematicasConstants::CODE_CHD_GRUPO_MAIOR_QUE_PAX,
                    'As idades de CHD não podem exceder o total de PAX na UH ' . $uhNumero . '.',
                    ['uh_numero' => $uhNumero, 'linha' => $indice + 1]
                );
            }

            $uh = $this->unidades->buscarUhPorNumero($uhNumero);
            if (!$uh) {
                return ServiceResult::failure(
                    ReservasTematicasConstants::CODE_UH_GRUPO_INVALIDA,
                    'UH inválida: ' . $uhNumero . '. Confira a linha ' . ($indice + 1) . ' do grupo.',
                    ['uh_numero' => $uhNumero, 'linha' => $indice + 1]
                );
            }

            $erroLimiteUh = $this->validarLimiteDaUh((string)($uh['numero'] ?? $uhNumero), $paxItem);
            if ($erroLimiteUh) {
                return $erroLimiteUh;
            }

            $erroDuplicidade = $this->validarDuplicidadeDaUh((int)$uh['id'], (string)($uh['numero'] ?? $uhNumero), $command);
            if ($erroDuplicidade) {
                return ServiceResult::failure($erroDuplicidade->code(), 'Já existe reserva para UH ' . $uhNumero . ' neste turno.');
            }

            $itens[] = [
                'uh_id' => (int)$uh['id'],
                'pax' => $paxItem,
                'qtd_chd' => $qtdChdItem,
                'pax_adulto' => max(ReservasTematicasConstants::DEFAULT_ZERO, $paxItem - $qtdChdItem),
                'idades' => $idadesItem,
            ];
        }

        return $itens;
    }

    private function validarLimiteDaUh(string $uhNumero, int $pax): ?ServiceResult
    {
        $limitePaxDaUh = $this->unidades->limitePaxDaUh($uhNumero);
        if ($limitePaxDaUh !== null && $pax > $limitePaxDaUh) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_PAX_ACIMA_LIMITE_UH,
                'A UH ' . $uhNumero . ' permite no máximo ' . $limitePaxDaUh . ' PAX. Tentativa: ' . $pax . ' PAX.',
                ['uh_numero' => $uhNumero, 'pax_limite' => $limitePaxDaUh, 'pax_tentativa' => $pax]
            );
        }

        return null;
    }

    private function validarDuplicidadeDaUh(int $uhId, string $uhNumero, CriarReservaCommand $command, int $ignorarReservaId = 0): ?ServiceResult
    {
        if (ReservaTematicaPolicy::isDuplicateAllowedUh($uhNumero)) {
            return null;
        }

        $reservaDuplicadaId = $this->reservas->buscarReservaDuplicadaDaUh(
            $uhId,
            $command->dataReserva,
            $command->turnoId,
            $command->restauranteId
        );
        if ($reservaDuplicadaId && $reservaDuplicadaId !== $ignorarReservaId) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_RESERVA_DUPLICADA_UH,
                'Já existe reserva para a UH ' . $uhNumero . ' neste restaurante, data e turno.',
                ['uh_numero' => $uhNumero]
            );
        }

        return null;
    }

    private function falhaDePersistencia(CriarReservaCommand $command, Throwable $error): ServiceResult
    {
        $uhs = $command->acao === ReservasTematicasConstants::ACTION_CREATE_BATCH
            ? array_values(array_filter(array_map('trim', array_map('strval', $command->batchUhs))))
            : array_values(array_filter([$command->uhNumero]));
        $uhs = array_slice(array_values(array_unique($uhs)), 0, 20);
        $referencia = $uhs !== [] ? ' para ' . (count($uhs) === 1 ? 'a UH ' : 'as UHs ') . implode(', ', $uhs) : '';

        error_log('[reserva-tematica-save] ' . json_encode([
            'acao' => $command->acao,
            'usuario_id' => $command->usuarioId,
            'restaurante_id' => $command->restauranteId,
            'turno_id' => $command->turnoId,
            'data_reserva' => $command->dataReserva,
            'uhs' => $uhs,
            'erro' => $error->getMessage(),
        ], JSON_UNESCAPED_UNICODE));

        return ServiceResult::failure(
            ReservasTematicasConstants::CODE_ERRO_PERSISTENCIA_RESERVA,
            ReservasTematicasConstants::MESSAGE_ERRO_PERSISTENCIA_RESERVA . $referencia . ' Nenhuma reserva parcial foi mantida. Tente novamente; se persistir, acione a administração.',
            ['uh_numero' => $uhs[0] ?? '', 'uhs' => $uhs]
        );
    }

    private function validarCapacidadeDoTurno(
        int $restauranteId,
        string $dataReserva,
        int $turnoId,
        int $paxNovo,
        ?array $reservaAtual = null
    ): ?ServiceResult {
        $capacidadeDoTurno = $this->reservas->capacidadeDoTurno($restauranteId, $dataReserva, $turnoId);
        if ($capacidadeDoTurno <= 0) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_CAPACIDADE_NAO_CONFIGURADA,
                ReservasTematicasConstants::MESSAGE_CAPACIDADE_NAO_CONFIGURADA,
                [
                    'capacidade' => $capacidadeDoTurno,
                    'pax_tentativa' => $paxNovo,
                    'data_reserva' => $dataReserva,
                    'restaurante_id' => $restauranteId,
                    'turno_id' => $turnoId,
                ]
            );
        }

        $paxReservadoNoTurno = $this->reservas->somarPaxDoTurno($restauranteId, $dataReserva, $turnoId);
        if ($reservaAtual) {
            $mesmoTurno = (int)$reservaAtual['restaurante_id'] === $restauranteId
                && (string)$reservaAtual['data_reserva'] === $dataReserva
                && (int)$reservaAtual['turno_id'] === $turnoId;
            if ($mesmoTurno && (string)($reservaAtual['status'] ?? '') !== ReservasTematicasConstants::STATUS_CANCELADA) {
                $paxReservadoNoTurno -= (int)($reservaAtual['pax'] ?? 0);
            }
        }

        $capacidadeDoTurnoPermite = ($paxReservadoNoTurno + $paxNovo) <= $capacidadeDoTurno;
        if (!$capacidadeDoTurnoPermite) {
            return ServiceResult::failure(
                ReservasTematicasConstants::CODE_CAPACIDADE_TURNO_ATINGIDA,
                ReservasTematicasConstants::MESSAGE_CAPACIDADE_TURNO_ATINGIDA,
                [
                    'capacidade' => $capacidadeDoTurno,
                    'pax_reservado' => max(0, $paxReservadoNoTurno),
                    'pax_disponivel' => max(0, $capacidadeDoTurno - $paxReservadoNoTurno),
                    'pax_tentativa' => $paxNovo,
                    'pax_projetado' => $paxReservadoNoTurno + $paxNovo,
                    'data_reserva' => $dataReserva,
                    'restaurante_id' => $restauranteId,
                    'turno_id' => $turnoId,
                ]
            );
        }

        return null;
    }

    private function montarPayloadReserva(CriarReservaCommand $command, array $dados): array
    {
        $tagsText = '';
        if (!empty($command->observacaoTags)) {
            $tagsText = implode(', ', array_filter(array_map('trim', $command->observacaoTags)));
        }

        return array_merge([
            'restaurante_id' => $command->restauranteId,
            'data_reserva' => $command->dataReserva,
            'turno_id' => $command->turnoId,
            'observacao_reserva' => $command->observacaoReserva,
            'observacao_tags' => $tagsText,
            'status' => ReservasTematicasConstants::STATUS_RESERVADA,
            'excedente' => ReservasTematicasConstants::DEFAULT_ZERO,
            'excedente_motivo' => null,
            'excedente_autor_id' => null,
            'excedente_em' => null,
            'idades_chd' => [],
        ], $dados);
    }
}
