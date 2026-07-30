<?php
declare(strict_types=1);

class ReservasTematicasController extends Controller
{
    private function requireModuleAccess(): void
    {
        $this->requireAuth();
        $user = Auth::user();
        if (!$user) {
            Auth::requireRole(['admin']);
        }
        if ((new TematicAccessService())->canAccessModule($user)) {
            return;
        }
        $this->forbidden();
    }

    private function requireReservaAccess(): void
    {
        $this->requireModuleAccess();
    }

    private function requireOperacaoAccess(): void
    {
        $this->requireModuleAccess();
    }

    private function hostessTematicRestaurants(int $userId): array
    {
        return (new TematicAccessService())->tematicRestaurantsForUser($userId);
    }

    private function getTematicRestaurants(): array
    {
        return (new TematicAccessService())->allTematicRestaurants();
    }

    private function isWithinReservaWindow(array $periodos): bool
    {
        if (app_demo_mode_enabled()) {
            return true;
        }

        $tz = new DateTimeZone(date_default_timezone_get());
        $now = new DateTime('now', $tz);
        foreach ($periodos as $periodo) {
            $start = DateTime::createFromFormat('H:i:s', $periodo['hora_inicio'], $tz);
            $end = DateTime::createFromFormat('H:i:s', $periodo['hora_fim'], $tz);
            if (!$start || !$end) {
                continue;
            }
            $start->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));
            $end->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));
            if ($now >= $start && $now <= $end) {
                return true;
            }
        }
        return false;
    }

    /**
     * Criação de reservas em 2 passos (remake visual, etapa 7): disponibilidade como interface.
     */
    public function reservas(): void
    {
        $this->executarReservas('reservas');
    }

    /**
     * Ambiente legado completo de reservas (mapa, cadastro assistido e edição), preservado.
     */
    public function reservasCompleta(): void
    {
        $this->executarReservas('completa');
    }

    private function executarReservas(string $modo): void
    {
        $rotaBase = '/?r=reservasTematicas/' . ($modo === 'completa' ? 'reservasCompleta' : 'reservas');
        $this->requireReservaAccess();
        $user = Auth::user();

        if ($modo === 'reservas' && (int)($_GET['edit'] ?? 0) > 0) {
            // A edição supervisionada continua no ambiente completo.
            $query = $_GET;
            unset($query['r']);
            $this->redirect('/?r=reservasTematicas/reservasCompleta&' . http_build_query($query));
        }

        $reservaModel = new ReservaTematicaModel();
        $turnoModel = new ReservaTematicaTurnoModel();
        $periodoModel = new ReservaTematicaPeriodoModel();
        $configModel = new ReservaTematicaConfigModel();
        $bloqueioDataModel = new ReservaTematicaBloqueioDataModel();
        $unitModel = new UnitModel();
        $confirmacaoService = new ReservaTematicaConfirmacaoService();

        $restaurantes = $this->getTematicRestaurants();
        $turnos = $turnoModel->allActive();
        $periodos = $periodoModel->allActive();
        $isHostess = ($user['perfil'] ?? '') === 'hostess';
        if ($isHostess && !(new TematicAccessService())->hostessHasCorais((int)$user['id'])) {
            $permitidos = array_map(static fn($rest) => (int)$rest['id'], $this->hostessTematicRestaurants((int)$user['id']));
            $restaurantes = array_values(array_filter(
                $restaurantes,
                static fn($rest) => in_array((int)$rest['id'], $permitidos, true)
            ));
        }
        $withinWindow = app_demo_mode_enabled() ? true : $this->isWithinReservaWindow($periodos);
        $canReserveNow = !$isHostess || $withinWindow;

        $filters = [
            'data' => $_GET['data'] ?? date('Y-m-d'),
            'restaurante_id' => $_GET['restaurante_id'] ?? '',
            'turno_id' => $_GET['turno_id'] ?? '',
            'uh_numero' => $_GET['uh_numero'] ?? '',
            'titular' => $_GET['titular'] ?? '',
        ];

        $buildAvailability = function (string $date) use ($restaurantes, $turnos, $configModel, $reservaModel, $bloqueioDataModel): array {
            $availability = [];
            foreach ($restaurantes as $rest) {
                $restId = (int)$rest['id'];
                $fechado = $bloqueioDataModel->isClosed($restId, $date);
                $turnoCaps = $configModel->turnosConfigForDate($restId, $date);
                foreach ($turnos as $turno) {
                    $capacidade = 0;
                    foreach ($turnoCaps as $cfg) {
                        if ((int)$cfg['turno_id'] === (int)$turno['id']) {
                            $capacidade = (int)$cfg['capacidade'];
                            break;
                        }
                    }
                    $sum = $reservaModel->sumPax($restId, $date, (int)$turno['id']);
                    $availability[$restId][(int)$turno['id']] = [
                        'capacidade' => $fechado ? 0 : $capacidade,
                        'reservado' => $sum,
                        'restante' => $fechado ? 0 : max(0, $capacidade - $sum),
                        'fechado' => $fechado,
                    ];
                }
            }
            return $availability;
        };

        if (($_GET['ajax'] ?? '') === 'reservation_status') {
            $correlationId = CriarReservaCommand::normalizeCorrelationId((string)($_GET['correlation_id'] ?? ''));
            if ($correlationId === '') {
                json_response([
                    'ok' => false,
                    'status' => 'invalid_request',
                    'message' => 'Referência de tentativa inválida.',
                ], 400);
            }

            $commandStatus = new CriarReservaCommand([
                'usuario_id' => (int)$user['id'],
                'usuario' => $user,
                'correlation_id' => $correlationId,
            ]);
            $confirmation = $confirmacaoService->confirmarPorCorrelacao($commandStatus);
            json_response([
                'ok' => true,
                'status' => $confirmation !== [] ? 'confirmed' : 'not_found',
                'correlation_id' => $correlationId,
                'confirmation' => $confirmation,
            ]);
        }

        if (($_GET['ajax'] ?? '') === 'editable_reservation') {
            $editableId = (int)($_GET['id'] ?? 0);
            $editable = $editableId > 0 ? $reservaModel->find($editableId) : null;
            if (!$editable || !ReservaTematicaPolicy::canEdit($editable, $user)) {
                json_response([
                    'ok' => false,
                    'message' => 'Esta reserva não está disponível para edição pelo seu usuário.',
                ], 403);
            }

            $uhEditable = $unitModel->find((int)($editable['uh_id'] ?? 0));
            $idadesEditaveis = $reservaModel->getChdAgesMap([$editableId]);
            json_response([
                'ok' => true,
                'reservation' => [
                    'id' => $editableId,
                    'data_reserva' => (string)($editable['data_reserva'] ?? ''),
                    'restaurante_id' => (int)($editable['restaurante_id'] ?? 0),
                    'turno_id' => (int)($editable['turno_id'] ?? 0),
                    'uh_numero' => (string)($uhEditable['numero'] ?? ''),
                    'titular_nome' => normalize_mojibake((string)($editable['titular_nome'] ?? '')),
                    'pax' => max(1, (int)($editable['pax'] ?? 1)),
                    'chd_idades' => !empty($idadesEditaveis[$editableId]) ? implode('', $idadesEditaveis[$editableId]) : '',
                    'grupo_id' => (int)($editable['grupo_id'] ?? 0),
                    'grupo_nome' => normalize_mojibake((string)($editable['grupo_nome'] ?? '')),
                    'observacao_reserva' => normalize_mojibake((string)($editable['observacao_reserva'] ?? '')),
                    'observacao_tags' => array_values(array_filter(array_map('trim', explode(',', (string)($editable['observacao_tags'] ?? ''))))),
                ],
            ]);
        }

        if (($_GET['ajax'] ?? '') === 'availability') {
            $dateAjax = sanitize_date_param($_GET['data'] ?? '', date('Y-m-d'));
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');
            echo json_encode([
                'ok' => true,
                'date' => $dateAjax,
                'availability' => $buildAvailability($dateAjax),
                'reconciliacao' => $confirmacaoService->reconciliarData(
                    $dateAjax,
                    array_map(static fn(array $restaurante): int => (int)$restaurante['id'], $restaurantes)
                ),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (($_GET['ajax'] ?? '') === 'availability_detail') {
            $dateAjax = sanitize_date_param($_GET['data'] ?? '', date('Y-m-d'));
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');
            $restauranteId = (int)($_GET['restaurante_id'] ?? 0);
            $turnoId = (int)($_GET['turno_id'] ?? 0);
            $restaurantesPermitidosIds = array_map(static fn(array $restaurante): int => (int)($restaurante['id'] ?? 0), $restaurantes);
            if ($restauranteId <= 0 || $turnoId <= 0 || !in_array($restauranteId, $restaurantesPermitidosIds, true)) {
                json_response([
                    'ok' => false,
                    'message' => 'Parâmetros inválidos para detalhamento.',
                ], 400);
            }

            $rows = $reservaModel->listByFilters([
                'data' => $dateAjax,
                'restaurante_id' => $restauranteId,
                'turno_id' => $turnoId,
                // O detalhe deve refletir a mesma base usada no calculo de ocupacao.
                // Reservas finalizadas e no-show continuam compondo o historico do turno.
                'status' => '',
                'order' => 'status',
            ]);
            $availabilityMap = $buildAvailability($dateAjax);
            $availabilityInfo = $availabilityMap[$restauranteId][$turnoId] ?? [
                'capacidade' => 0,
                'reservado' => 0,
                'restante' => 0,
            ];
            $items = [];
            $totalPax = 0;
            $totalChd = 0;
            foreach ($rows as $row) {
                $pax = (int)($row['pax'] ?? 0);
                $statusReserva = $this->normalizeReservaStatus((string)($row['status'] ?? ''));
                if ($statusReserva === ReservasTematicasConstants::STATUS_CANCELADA) {
                    continue;
                }
                $qtdChd = max((int)($row['qtd_chd_calc'] ?? 0), (int)($row['pax_chd_calc'] ?? 0));
                $totalPax += $pax;
                $totalChd += $qtdChd;
                $items[] = [
                    'id' => (int)($row['id'] ?? 0),
                    'grupo_id' => (int)($row['grupo_id'] ?? 0),
                    'grupo_nome' => normalize_mojibake((string)($row['grupo_nome_display'] ?? $row['grupo_nome'] ?? $row['grupo_responsavel'] ?? '')),
                    'uh_numero' => $statusReserva === ReservasTematicasConstants::STATUS_PRE_RESERVA ? '' : (string)($row['uh_numero'] ?? ''),
                    'titular_nome' => normalize_mojibake((string)($row['titular_nome_display'] ?? $row['titular_nome'] ?? '')),
                    'pax' => $pax,
                    'qtd_chd' => $qtdChd,
                    'status' => $statusReserva,
                    'restaurante' => normalize_mojibake((string)($row['restaurante'] ?? '')),
                    'turno_hora' => (string)($row['turno_hora'] ?? ''),
                    'usuario' => normalize_mojibake((string)($row['usuario'] ?? '')),
                    'edit_url' => ReservaTematicaPolicy::canEdit($row, $user) ? '/?r=reservasTematicas/reservasCompleta&edit=' . (int)($row['id'] ?? 0) : '',
                ];
            }

            json_response([
                'ok' => true,
                'date' => $dateAjax,
                'restaurante_id' => $restauranteId,
                'turno_id' => $turnoId,
                'capacidade' => (int)($availabilityInfo['capacidade'] ?? 0),
                'reservado' => (int)($availabilityInfo['reservado'] ?? 0),
                'restante' => (int)($availabilityInfo['restante'] ?? 0),
                'count' => count($items),
                'total_pax' => $totalPax,
                'total_chd' => $totalChd,
                'items' => $items,
            ]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tentativaService = new RegistrarTentativaReservaTematicaService();
            $correlationId = CriarReservaCommand::normalizeCorrelationId((string)($_POST['correlation_id'] ?? ''));
            if ($correlationId === '') {
                $correlationId = $tentativaService->novaCorrelacao();
            }
            $comandoReserva = new CriarReservaCommand([
                'acao' => $_POST['action'] ?? 'create',
                'usuario_id' => (int)$user['id'],
                'usuario' => $user,
                'hostess_fora_da_janela' => $isHostess && !$withinWindow,
                'restaurantes_permitidos' => $restaurantes,
                'reserva_id' => $_POST['id'] ?? 0,
                'restaurante_id' => $_POST['restaurante_id'] ?? 0,
                'data_reserva' => $_POST['data_reserva'] ?? date('Y-m-d'),
                'turno_id' => $_POST['turno_id'] ?? 0,
                'uh_numero' => $_POST['uh_numero'] ?? '',
                'titular_nome' => $_POST['titular_nome'] ?? '',
                'grupo_nome' => $_POST['grupo_nome'] ?? '',
                'pax' => $_POST['pax'] ?? 0,
                'chd_idades' => $_POST['chd_idades'] ?? '',
                'observacao_reserva' => $_POST['observacao_reserva'] ?? '',
                'observacao_tags' => $_POST['observacao_tags'] ?? [],
                'batch_uh_numero' => $_POST['batch_uh_numero'] ?? [],
                'batch_pax' => $_POST['batch_pax'] ?? [],
                'batch_chd_idades' => $_POST['batch_chd_idades'] ?? [],
                'grupo_responsavel' => $_POST['grupo_responsavel'] ?? '',
                'correlation_id' => $correlationId,
            ]);
            $tentativaService->registrarInicio($comandoReserva);

            if (!csrf_validate($_POST['csrf_token'] ?? '')) {
                $mensagemCsrf = 'Sessão expirada. Atualize a página e tente novamente.';
                $tentativaService->registrarRecusa(
                    $comandoReserva,
                    ServiceResult::failure('csrf_invalido', $mensagemCsrf),
                    $mensagemCsrf
                );
                if (request_expects_json()) {
                    json_response([
                        'ok' => false,
                        'type' => 'danger',
                        'code' => 'csrf_invalido',
                        'message' => $mensagemCsrf . ' Referência da tentativa: ' . $comandoReserva->correlationId . '.',
                        'correlation_id' => $comandoReserva->correlationId,
                    ], 401);
                }
                set_flash('danger', $mensagemCsrf . ' Referência da tentativa: ' . $comandoReserva->correlationId . '.');
                $this->redirect($rotaBase);
            }

            $resultadoReserva = (new CriarReservaService())->executar($comandoReserva);
            if (!$resultadoReserva->isSuccess()) {
                $mensagemRecusa = $this->mensagemReservaTematicaParaUsuario($resultadoReserva);
                $tentativaService->registrarRecusa(
                    $comandoReserva,
                    $resultadoReserva,
                    $mensagemRecusa
                );
                $resultadoReserva = ServiceResult::failure(
                    $resultadoReserva->code(),
                    $mensagemRecusa . ' Referência da tentativa: ' . $comandoReserva->correlationId . '.',
                    array_merge($resultadoReserva->payload(), [
                        'correlation_id' => $comandoReserva->correlationId,
                        'message_resolved' => true,
                    ])
                );
            } else {
                $confirmacao = $confirmacaoService->confirmarPersistencia($comandoReserva, $resultadoReserva);
                $resultadoReserva = ServiceResult::success(
                    $resultadoReserva->message(),
                    array_merge($resultadoReserva->payload(), [
                        'correlation_id' => $comandoReserva->correlationId,
                        'confirmacao' => $confirmacao,
                    ]),
                    $resultadoReserva->code()
                );
                $tentativaService->registrarSucesso($comandoReserva, $resultadoReserva, $confirmacao);
            }
            $this->aplicarResultadoReservaTematica($resultadoReserva, $rotaBase);
        }

        $availability = $buildAvailability((string)($filters['data'] ?? date('Y-m-d')));

        $editId = (int)($_GET['edit'] ?? 0);
        $editItem = $editId > 0 ? $reservaModel->find($editId) : null;
        if ($editItem) {
            if (!ReservaTematicaPolicy::canEdit($editItem, $user)) {
                set_flash('danger', 'Você só pode editar reservas criadas por você. A administração pode acompanhar as alterações pela auditoria.');
                $this->redirect($rotaBase);
            }
            $uhRow = $unitModel->find((int)$editItem['uh_id']);
            $editItem['uh_numero'] = (string)($editItem['status'] ?? '') === ReservasTematicasConstants::STATUS_PRE_RESERVA
                ? ''
                : ($uhRow['numero'] ?? '');
            $agesMap = $reservaModel->getChdAgesMap([$editId]);
            $editItem['chd_idades'] = isset($agesMap[$editId]) && !empty($agesMap[$editId]) ? implode('', $agesMap[$editId]) : '';
            $editItem['qtd_chd'] = (int)($editItem['qtd_chd'] ?? 0);
            $editItem['pax_adulto'] = (int)($editItem['pax_adulto'] ?? max(0, (int)($editItem['pax'] ?? 0) - (int)$editItem['qtd_chd']));
        }

        $reservasDoTurno = [];
        if ($modo === 'reservas' && !empty($filters['restaurante_id']) && !empty($filters['turno_id'])) {
            $reservasDoTurno = $reservaModel->listByFilters([
                'data' => (string)$filters['data'],
                'restaurante_id' => (int)$filters['restaurante_id'],
                'turno_id' => (int)$filters['turno_id'],
                'status' => '',
                'order' => 'status',
            ]);
        }

        $minhasReservasEditaveis = [];
        if ($modo === 'reservas' && $isHostess) {
            $rowsEditaveis = $reservaModel->listByFilters([
                'usuario_id' => (int)($user['id'] ?? 0),
                'data_inicio' => date('Y-m-d'),
                'data_fim' => date('Y-m-d', strtotime('+365 days')),
                'order' => 'date_asc',
            ], 30);
            foreach ($rowsEditaveis as $rowEditavel) {
                $statusEditavel = $this->normalizeReservaStatus((string)($rowEditavel['status'] ?? ''));
                if (!in_array($statusEditavel, [
                    ReservasTematicasConstants::STATUS_RESERVADA,
                    ReservasTematicasConstants::STATUS_PRE_RESERVA,
                ], true)) {
                    continue;
                }
                if (!ReservaTematicaPolicy::canEdit($rowEditavel, $user)) {
                    continue;
                }
                $minhasReservasEditaveis[] = $rowEditavel;
            }
        }

        $this->view('reservas_tematicas/' . ($modo === 'completa' ? 'reservas_completa' : 'reservas'), [
            'restaurantes' => $restaurantes,
            'turnos' => $turnos,
            'periodos' => $periodos,
            'availability' => $availability,
            'filters' => $filters,
            'flash' => get_flash(),
            'can_reserve' => $canReserveNow,
            'edit_item' => $editItem,
            'is_hostess' => $isHostess,
            'reservas_do_turno' => $reservasDoTurno,
            'minhas_reservas_editaveis' => $minhasReservasEditaveis,
            'reservas_recentes' => $confirmacaoService->listarRecentes((int)$user['id']),
            'reconciliacao' => $confirmacaoService->reconciliarData(
                (string)$filters['data'],
                array_map(static fn(array $restaurante): int => (int)$restaurante['id'], $restaurantes)
            ),
        ]);
    }

    /**
     * Check-in da operação temática (remake visual, etapa 7): fila de cartões mobile-first.
     */
    public function operacao(): void
    {
        $this->executarOperacao('operacao');
    }

    /**
     * Ambiente legado de conferência e impressão, preservado para o fechamento do dia.
     */
    public function conferencia(): void
    {
        $this->executarOperacao('conferencia');
    }

    private function executarOperacao(string $modo): void
    {
        $rotaBase = '/?r=reservasTematicas/' . ($modo === 'conferencia' ? 'conferencia' : 'operacao');
        $this->requireOperacaoAccess();
        $user = Auth::user();
        $closedByTimeout = (new ShiftAutoCloseService())->closeForCurrentUser();
        if ($closedByTimeout > 0 && !isset($_SESSION['flash'])) {
            set_flash('warning', 'Turno encerrado automaticamente por tempo excedido (limite + 10 min).');
        }

        $reservaModel = new ReservaTematicaModel();
        $turnoModel = new ReservaTematicaTurnoModel();
        $fechamentoModel = new ReservaTematicaFechamentoModel();

        $printRestaurants = $this->getTematicRestaurants();
        $restaurantes = $printRestaurants;
        $restrictedRestaurant = null;
        $allowedHostessRestaurantIds = [];
        if (($user['perfil'] ?? '') === 'hostess') {
            $assigned = $this->hostessTematicRestaurants((int)$user['id']);
            if (!empty($assigned)) {
                $restaurantes = $assigned;
                $allowedHostessRestaurantIds = array_map(static fn($r) => (int)$r['id'], $assigned);
                if (count($assigned) === 1) {
                    $restrictedRestaurant = $assigned[0] ?? null;
                }
            }
        }
        $turnos = $turnoModel->allActive();

        $filters = [
            'data' => $_GET['data'] ?? date('Y-m-d'),
            'restaurante_id' => $_GET['restaurante_id'] ?? '',
            'turno_id' => $_GET['turno_id'] ?? '',
            'uh_numero' => $_GET['uh_numero'] ?? '',
            'titular' => $_GET['titular'] ?? '',
            'q' => $_GET['q'] ?? '',
            'status' => $_GET['status'] ?? '',
            'order' => $_GET['order'] ?? '',
        ];
        if ($restrictedRestaurant) {
            $allowedIds = array_map(fn($r) => (int)$r['id'], $restaurantes);
            if (empty($filters['restaurante_id']) || !in_array((int)$filters['restaurante_id'], $allowedIds, true)) {
                $filters['restaurante_id'] = (string)$allowedIds[0];
            }
        } elseif (!empty($allowedHostessRestaurantIds)) {
            if (!empty($filters['restaurante_id']) && !in_array((int)$filters['restaurante_id'], $allowedHostessRestaurantIds, true)) {
                $filters['restaurante_id'] = '';
            }
            $filters['restaurante_ids'] = $allowedHostessRestaurantIds;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_validate($_POST['csrf_token'] ?? '')) {
                if (request_expects_json()) {
                    json_response([
                        'ok' => false,
                        'type' => 'danger',
                        'code' => 'csrf_invalido',
                        'message' => 'Sessão expirada. Atualize a página e tente novamente.',
                    ], 401);
                }
                set_flash('danger', 'Token inválido.');
                $this->redirect($rotaBase);
            }
            $resultadoOperacao = (new OperarReservaService())->executar(new OperarReservaCommand([
                'acao' => $_POST['action'] ?? '',
                'usuario_id' => (int)$user['id'],
                'usuario' => $user,
                'restaurantes_permitidos' => $restaurantes,
                'turnos_permitidos' => $turnos,
                'reserva_id' => $_POST['id'] ?? 0,
                'restaurante_id' => $_POST['restaurante_id'] ?? 0,
                'turno_id' => $_POST['turno_id'] ?? 0,
                'data_reserva' => $_POST['data_reserva'] ?? date('Y-m-d'),
                'uh_numero' => $_POST['uh_numero'] ?? '',
                'status' => $_POST['status'] ?? ReservasTematicasConstants::STATUS_RESERVADA,
                'observacao_operacao' => $_POST['observacao_operacao'] ?? '',
                'pax_real' => $_POST['pax_real'] ?? '',
                'justificativa' => $_POST['justificativa'] ?? '',
                'confirmou_status_final' => (int)($_POST['confirm_final'] ?? 0) === 1,
                'acao_rapida' => $_POST['quick_action'] ?? '',
            ]));
            $redirectOperacao = $rotaBase;
            if ($resultadoOperacao->isSuccess() && !empty($resultadoOperacao->payload()['redirect_query'])) {
                $redirectOperacao .= '&' . $resultadoOperacao->payload()['redirect_query'];
            }
            $this->aplicarResultadoReservaTematica($resultadoOperacao, $redirectOperacao);
        }

        // A fila operacional renderiza o turno completo e filtra busca/status no
        // cliente (instantâneo, sem reload) — isso também mantém os contadores dos
        // chips corretos. A conferência/impressão preserva o filtro do servidor.
        $listFilters = $filters;
        if ($modo === 'operacao') {
            unset($listFilters['q'], $listFilters['status'], $listFilters['titular'], $listFilters['uh_numero']);
        }
        $reservas = $reservaModel->listByFilters($listFilters);
        $summary = [
            'total' => count($reservas),
            'pre_reserva' => 0,
            'reservada' => 0,
            'finalizada' => 0,
            'nao_compareceu' => 0,
            'cancelada' => 0,
            'divergencia' => 0,
        ];
        foreach ($reservas as $row) {
            $status = $this->normalizeReservaStatus((string)($row['status_reserva'] ?? ($row['status'] ?? '')));
            if ($status === ReservasTematicasConstants::STATUS_PRE_RESERVA) {
                $summary['pre_reserva']++;
            } elseif ($status === ReservasTematicasConstants::STATUS_RESERVADA) {
                $summary['reservada']++;
            } elseif ($status === ReservasTematicasConstants::STATUS_FINALIZADA) {
                $summary['finalizada']++;
            } elseif ($status === ReservasTematicasConstants::STATUS_NO_SHOW) {
                $summary['nao_compareceu']++;
            } elseif ($status === ReservasTematicasConstants::STATUS_CANCELADA) {
                $summary['cancelada']++;
            } elseif ($status === ReservasTematicasConstants::STATUS_DIVERGENCIA) {
                $summary['divergencia']++;
            }
        }
        $closed = false;
        if (!empty($filters['restaurante_id']) && !empty($filters['turno_id'])) {
            $closed = $fechamentoModel->isClosed((int)$filters['restaurante_id'], $filters['data'], (int)$filters['turno_id']);
        }

        $capacidadeTurno = null;
        if (!empty($filters['restaurante_id']) && !empty($filters['turno_id'])) {
            $configTurnos = (new ReservaTematicaConfigModel())->turnosConfigForDate(
                (int)$filters['restaurante_id'],
                (string)$filters['data']
            );
            foreach ($configTurnos as $configTurno) {
                if ((int)($configTurno['turno_id'] ?? 0) === (int)$filters['turno_id']) {
                    $capacidadeTurno = (int)($configTurno['capacidade'] ?? 0);
                    break;
                }
            }
        }

        $this->view('reservas_tematicas/' . ($modo === 'conferencia' ? 'conferencia' : 'operacao'), [
            'capacidade_turno' => $capacidadeTurno,
            'restaurantes' => $restaurantes,
            'print_restaurantes' => $printRestaurants,
            'turnos' => $turnos,
            'reservas' => $reservas,
            'filters' => $filters,
            'flash' => get_flash(),
            'closed' => $closed,
            'user' => $user,
            'restricted_restaurant' => $restrictedRestaurant,
            'summary' => $summary,
        ]);
    }

    /**
     * Configuração temática como calendário por restaurante (remake visual, etapa 7).
     */
    public function admin(): void
    {
        $this->executarAdmin('admin');
    }

    /**
     * Ambiente legado completo de configuração (capacidades, turnos, períodos), preservado.
     */
    public function adminCompleta(): void
    {
        $this->executarAdmin('completa');
    }

    private function executarAdmin(string $modo): void
    {
        $rotaBase = '/?r=reservasTematicas/' . ($modo === 'completa' ? 'adminCompleta' : 'admin');
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'gerente']);

        $configModel = new ReservaTematicaConfigModel();
        $turnoModel = new ReservaTematicaTurnoModel();
        $periodoModel = new ReservaTematicaPeriodoModel();
        $bloqueioDataModel = new ReservaTematicaBloqueioDataModel();
        $bloqueioSemanalModel = new ReservaTematicaBloqueioSemanalModel();
        $bloqueioSemanalModel->seedDefaultsIfEmpty();
        $perfilAtual = Auth::user()['perfil'] ?? '';
        $canManageBloqueios = in_array($perfilAtual, ['admin', 'gerente'], true);

        if (($_GET['ajax'] ?? '') === 'capacity_date') {
            $capacidadeDataAjax = sanitize_date_param($_GET['cap_data'] ?? '', date('Y-m-d'));
            $restaurantesAjax = $this->getTematicRestaurants();
            $payload = [];
            foreach ($restaurantesAjax as $rest) {
                $restId = (int)$rest['id'];
                $rows = $configModel->turnosConfigForDate($restId, $capacidadeDataAjax);
                $turnosPayload = [];
                $total = 0;
                foreach ($rows as $row) {
                    $capacidade = (int)($row['capacidade'] ?? 0);
                    $total += $capacidade;
                    $turnosPayload[(int)$row['turno_id']] = $capacidade;
                }
                $payload[$restId] = [
                    'total' => $total,
                    'turnos' => $turnosPayload,
                ];
            }
            json_response([
                'ok' => true,
                'date' => $capacidadeDataAjax,
                'restaurants' => $payload,
            ]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_validate($_POST['csrf_token'] ?? '')) {
                set_flash('danger', 'Token inválido.');
                $this->redirect($rotaBase);
            }

            $removeTurnoId = (int)($_POST['remove_turno_id'] ?? 0);
            if ($removeTurnoId > 0) {
                $turno = $turnoModel->find($removeTurnoId);
                if (!$turno) {
                    set_flash('warning', 'Turno não encontrado.');
                    $this->redirect($rotaBase);
                }

                $result = $turnoModel->removeOrInactivate($removeTurnoId, (int)Auth::user()['id']);
                if (($result['result'] ?? '') === 'deleted') {
                    set_flash('success', 'Turno removido com sucesso.');
                } else {
                    set_flash('info', 'Turno com histórico foi inativado para preservar os dados.');
                }
                $this->redirect($rotaBase);
            }

            $action = $_POST['action'] ?? '';
            if ($action === 'bloqueio_data') {
                if (!$canManageBloqueios) {
                    set_flash('danger', 'Somente admin e gerente podem alterar fechamentos dos temáticos.');
                    $this->redirect($rotaBase);
                }
                $dataBloqueio = sanitize_date_param($_POST['data_bloqueio'] ?? '', '');
                $restauranteId = (int)($_POST['restaurante_id'] ?? 0);
                $modo = (string)($_POST['modo'] ?? ((int)($_POST['fechar'] ?? 1) === 1 ? 'fechado' : 'remover'));
                $motivo = normalize_mojibake(trim((string)($_POST['motivo'] ?? '')));
                $restIds = array_map(static fn($rest) => (int)$rest['id'], $this->getTematicRestaurants());
                if ($dataBloqueio === '' || !in_array($restauranteId, $restIds, true)) {
                    set_flash('warning', 'Informe uma data e um restaurante temático válidos.');
                    $this->redirect($rotaBase);
                }
                if (!in_array($modo, ['fechado', 'aberto', 'remover'], true)) {
                    set_flash('warning', 'Selecione uma ação válida para a data.');
                    $this->redirect($rotaBase . '&cap_data=' . urlencode($dataBloqueio));
                }
                if ($modo !== 'remover' && $motivo === '') {
                    set_flash('warning', 'Informe o motivo desta alteração de disponibilidade.');
                    $this->redirect($rotaBase . '&cap_data=' . urlencode($dataBloqueio));
                }
                if ($modo === 'remover') {
                    $bloqueioDataModel->removeOverride($restauranteId, $dataBloqueio, (int)Auth::user()['id']);
                    set_flash('success', 'Exceção removida. O restaurante voltou a seguir o cronograma normal.');
                } else {
                    $bloqueioDataModel->setOverride($restauranteId, $dataBloqueio, $modo, $motivo, (int)Auth::user()['id']);
                    set_flash('success', $modo === 'aberto'
                        ? 'Restaurante aberto excepcionalmente nesta data.'
                        : 'Restaurante fechado para a data selecionada.');
                }
                $this->redirect($rotaBase . '&cap_data=' . urlencode($dataBloqueio));
            }
            if ($action === 'bloqueio_semana') {
                if (!$canManageBloqueios) {
                    set_flash('danger', 'Somente admin e gerente podem fechar períodos dos temáticos.');
                    $this->redirect($rotaBase);
                }
                $dataInicio = sanitize_date_param($_POST['data_inicio'] ?? '', '');
                $restauranteId = (int)($_POST['restaurante_id'] ?? 0);
                $motivo = normalize_mojibake(trim((string)($_POST['motivo'] ?? '')));
                $restIds = array_map(static fn($rest) => (int)$rest['id'], $this->getTematicRestaurants());
                if ($dataInicio === '' || !in_array($restauranteId, $restIds, true)) {
                    set_flash('warning', 'Informe a data inicial e um restaurante temático válidos.');
                    $this->redirect($rotaBase);
                }
                if ($motivo === '') {
                    set_flash('warning', 'Informe o motivo do fechamento do período.');
                    $this->redirect($rotaBase . '&cap_data=' . urlencode($dataInicio));
                }

                $inicio = new DateTimeImmutable($dataInicio);
                for ($dia = 0; $dia < 7; $dia++) {
                    $data = $inicio->modify('+' . $dia . ' days')->format('Y-m-d');
                    $bloqueioDataModel->setOverride($restauranteId, $data, 'fechado', $motivo, (int)Auth::user()['id']);
                }
                $dataFim = $inicio->modify('+6 days')->format('d/m/Y');
                set_flash('success', 'Restaurante fechado por sete dias, até ' . $dataFim . '.');
                $this->redirect($rotaBase . '&cap_data=' . urlencode($dataInicio));
            }
            if ($action === 'bloqueio_semanal') {
                if (!$canManageBloqueios) {
                    set_flash('danger', 'Somente admin e gerente podem alterar fechamentos semanais dos temáticos.');
                    $this->redirect($rotaBase);
                }
                $restauranteId = (int)($_POST['restaurante_id'] ?? 0);
                $diaSemana = (int)($_POST['dia_semana'] ?? -1);
                $fechar = (int)($_POST['fechar'] ?? 1) === 1;
                $motivo = normalize_mojibake(trim((string)($_POST['motivo'] ?? '')));
                $restIds = array_map(static fn($rest) => (int)$rest['id'], $this->getTematicRestaurants());
                if (!in_array($restauranteId, $restIds, true) || $diaSemana < 0 || $diaSemana > 6) {
                    set_flash('warning', 'Informe restaurante e dia da semana válidos.');
                    $this->redirect($rotaBase);
                }
                if ($fechar && $motivo === '') {
                    set_flash('warning', 'Informe o motivo do fechamento semanal.');
                    $this->redirect($rotaBase);
                }
                $bloqueioSemanalModel->setClosed($restauranteId, $diaSemana, $fechar, $motivo, (int)Auth::user()['id']);
                set_flash('success', $fechar ? 'Fechamento semanal salvo.' : 'Fechamento semanal removido.');
                $this->redirect($rotaBase);
            }
            if ($action === 'config_capacidade') {
                $totais = $_POST['capacidade_total'] ?? [];
                $autoCancelNoShow = $_POST['auto_cancel_no_show_min'] ?? [];
                $turnosAtivos = $turnoModel->allActive();
                $turnosCount = max(1, count($turnosAtivos));
                foreach ($totais as $restId => $capTotal) {
                    $capacidadeTotal = max(0, (int)$capTotal);
                    $base = intdiv($capacidadeTotal, $turnosCount);
                    $remainder = $capacidadeTotal % $turnosCount;
                    $turnoCaps = [];
                    foreach ($turnosAtivos as $index => $turno) {
                        $turnoCaps[(int)$turno['id']] = $base + ($index < $remainder ? 1 : 0);
                    }
                    $autoCancelMin = (int)($autoCancelNoShow[$restId] ?? 0);
                    $configModel->updateConfig((int)$restId, $capacidadeTotal, $turnoCaps, (int)Auth::user()['id'], $autoCancelMin);
                }
                set_flash('success', 'Capacidades atualizadas e distribuídas entre os turnos ativos.');
                $this->redirect($rotaBase);
            }

            if ($action === 'config_capacidade_data') {
                $dataCapacidade = sanitize_date_param($_POST['capacidade_data'] ?? '', '');
                if ($dataCapacidade === '') {
                    set_flash('warning', 'Informe uma data válida para a capacidade futura.');
                    $this->redirect($rotaBase);
                }
                $configModel->updateDateConfig($dataCapacidade, $_POST['capacidade_data_turno'] ?? [], (int)Auth::user()['id']);
                set_flash('success', 'Capacidade específica da data atualizada.');
                $this->redirect($rotaBase . '&cap_data=' . urlencode($dataCapacidade));
            }

            if ($action === 'config_turnos') {
                $items = $_POST['turnos'] ?? [];
                $turnoModel->updateBatch($items, (int)Auth::user()['id']);
                set_flash('success', 'Turnos atualizados.');
                $this->redirect($rotaBase);
            }

            if ($action === 'add_turno') {
                $horaInput = trim((string)($_POST['novo_turno_hora'] ?? ''));
                $ordem = (int)($_POST['novo_turno_ordem'] ?? 0);
                $ativo = (int)($_POST['novo_turno_ativo'] ?? 1) === 1 ? 1 : 0;

                if ($horaInput === '') {
                    set_flash('warning', 'Informe o horário do novo turno.');
                    $this->redirect($rotaBase);
                }

                $hora = strlen($horaInput) === 5 ? ($horaInput . ':00') : $horaInput;
                $horaValida = DateTime::createFromFormat('H:i:s', $hora);
                if (!$horaValida || $horaValida->format('H:i:s') !== $hora) {
                    set_flash('warning', 'Horário inválido para novo turno.');
                    $this->redirect($rotaBase);
                }

                $turnoModel->create($hora, $ativo, $ordem, (int)Auth::user()['id']);
                set_flash('success', 'Novo turno adicionado.');
                $this->redirect($rotaBase);
            }

            if ($action === 'config_periodos') {
                $items = $_POST['periodos'] ?? [];
                $periodoModel->updateBatch($items, (int)Auth::user()['id']);
                set_flash('success', 'Períodos atualizados.');
                $this->redirect($rotaBase);
            }

            if ($action === 'add_periodo') {
                $inicioInput = trim((string)($_POST['novo_periodo_inicio'] ?? ''));
                $fimInput = trim((string)($_POST['novo_periodo_fim'] ?? ''));
                $ordem = (int)($_POST['novo_periodo_ordem'] ?? 0);
                $ativo = (int)($_POST['novo_periodo_ativo'] ?? 1) === 1 ? 1 : 0;

                if ($inicioInput === '' || $fimInput === '') {
                    set_flash('warning', 'Informe início e fim do novo período.');
                    $this->redirect($rotaBase);
                }

                $inicio = strlen($inicioInput) === 5 ? ($inicioInput . ':00') : $inicioInput;
                $fim = strlen($fimInput) === 5 ? ($fimInput . ':00') : $fimInput;

                $inicioValido = DateTime::createFromFormat('H:i:s', $inicio);
                $fimValido = DateTime::createFromFormat('H:i:s', $fim);
                if (
                    !$inicioValido || $inicioValido->format('H:i:s') !== $inicio
                    || !$fimValido || $fimValido->format('H:i:s') !== $fim
                ) {
                    set_flash('warning', 'Horário inválido para novo período.');
                    $this->redirect($rotaBase);
                }

                if ($inicio >= $fim) {
                    set_flash('warning', 'O horário final deve ser maior que o inicial.');
                    $this->redirect($rotaBase);
                }

                $periodoModel->create($inicio, $fim, $ativo, $ordem, (int)Auth::user()['id']);
                set_flash('success', 'Novo período adicionado.');
                $this->redirect($rotaBase);
            }
        }

        $restaurantes = $this->getTematicRestaurants();
        $configs = $configModel->configs();
        $turnos = $turnoModel->allActive();
        $periodos = $periodoModel->all();
        $capacidadeData = sanitize_date_param($_GET['cap_data'] ?? '', date('Y-m-d'));

        $turnosConfig = [];
        $turnosConfigData = [];
        foreach ($restaurantes as $rest) {
            $turnosConfig[(int)$rest['id']] = $configModel->turnosConfig((int)$rest['id']);
            $turnosConfigData[(int)$rest['id']] = $configModel->turnosConfigForDate((int)$rest['id'], $capacidadeData);
        }
        $bloqueiosData = $bloqueioDataModel->activeByDate($capacidadeData);
        $bloqueiosSemanais = $bloqueioSemanalModel->all();

        $calendario = [];
        $calendarioRestauranteId = 0;
        if ($modo === 'admin') {
            $calendarioRestauranteId = (int)($_GET['restaurante_id'] ?? 0);
            $idsTematicos = array_map(static fn($rest) => (int)$rest['id'], $restaurantes);
            if (!in_array($calendarioRestauranteId, $idsTematicos, true)) {
                $calendarioRestauranteId = $idsTematicos[0] ?? 0;
            }

            if ($calendarioRestauranteId > 0) {
                $capacidadePadraoTotal = 0;
                foreach ($turnosConfig[$calendarioRestauranteId] ?? [] as $cfgPadrao) {
                    $capacidadePadraoTotal += (int)($cfgPadrao['capacidade'] ?? 0);
                }

                $inicioMes = new DateTimeImmutable(substr($capacidadeData, 0, 7) . '-01');
                $diasNoMes = (int)$inicioMes->format('t');
                for ($diaMes = 1; $diaMes <= $diasNoMes; $diaMes++) {
                    $dataDia = $inicioMes->modify('+' . ($diaMes - 1) . ' days');
                    $dataStr = $dataDia->format('Y-m-d');
                    $override = $bloqueioDataModel->find($calendarioRestauranteId, $dataStr);
                    $capacidadeDia = 0;
                    foreach ($configModel->turnosConfigForDate($calendarioRestauranteId, $dataStr) as $cfgDia) {
                        $capacidadeDia += (int)($cfgDia['capacidade'] ?? 0);
                    }
                    $calendario[$dataStr] = [
                        'dia' => $diaMes,
                        'dia_semana' => (int)$dataDia->format('w'),
                        'fechado_semanal' => $bloqueioSemanalModel->isClosed($calendarioRestauranteId, $dataStr),
                        'override' => $override,
                        'capacidade' => $capacidadeDia,
                        'capacidade_especial' => $capacidadePadraoTotal > 0 && $capacidadeDia !== $capacidadePadraoTotal,
                    ];
                }
            }
        }

        $this->view('reservas_tematicas/' . ($modo === 'completa' ? 'admin_completa' : 'admin'), [
            'restaurantes' => $restaurantes,
            'configs' => $configs,
            'turnos' => $turnos,
            'periodos' => $periodos,
            'turnos_config' => $turnosConfig,
            'turnos_config_data' => $turnosConfigData,
            'capacidade_data' => $capacidadeData,
            'bloqueios_data' => $bloqueiosData,
            'bloqueios_semanais' => $bloqueiosSemanais,
            'can_manage_bloqueios' => $canManageBloqueios,
            'calendario' => $calendario,
            'calendario_restaurante_id' => $calendarioRestauranteId,
            'flash' => get_flash(),
        ]);
    }

    public function print(): void
    {
        $this->requireModuleAccess();

        $reservaModel = new ReservaTematicaModel();
        $restaurantModel = new RestaurantModel();
        $turnoModel = new ReservaTematicaTurnoModel();
        $filters = [
            'data' => sanitize_date_param($_GET['data'] ?? '', date('Y-m-d')),
            'restaurante_id' => sanitize_int_param($_GET['restaurante_id'] ?? ''),
            'turno_id' => sanitize_int_param($_GET['turno_id'] ?? ''),
            'uh_numero' => sanitize_uh_param($_GET['uh_numero'] ?? ''),
            'titular' => normalize_mojibake(trim((string)($_GET['titular'] ?? ''))),
            'q' => normalize_mojibake(trim((string)($_GET['q'] ?? ''))),
            'status' => normalize_mojibake(trim((string)($_GET['status'] ?? ''))),
            'order' => in_array((string)($_GET['order'] ?? ''), ['hora', 'status', 'turno'], true) ? (string)$_GET['order'] : '',
        ];
        $tipo = $_GET['tipo'] ?? 'detalhada';
        $reservas = $reservaModel->listByFilters($filters);
        $idadesPorReserva = $reservaModel->getChdAgesMap(array_column($reservas, 'id'));
        foreach ($reservas as &$reserva) {
            $reservaId = (int)($reserva['id'] ?? 0);
            $reserva['chd_idades_display'] = isset($idadesPorReserva[$reservaId]) ? implode(', ', $idadesPorReserva[$reservaId]) : '';
        }
        unset($reserva);

        $filters['restaurante_nome'] = 'Todos';
        if (!empty($filters['restaurante_id'])) {
            $rest = $restaurantModel->find((int)$filters['restaurante_id']);
            if ($rest) {
                $filters['restaurante_nome'] = $rest['nome'];
            }
        }
        $filters['turno_hora'] = 'Todos';
        if (!empty($filters['turno_id'])) {
            $turno = $turnoModel->find((int)$filters['turno_id']);
            if ($turno) {
                $filters['turno_hora'] = $turno['hora'];
            }
        }

        $this->data = [
            'reservas' => $reservas,
            'filters' => $filters,
            'tipo' => $tipo,
        ];
        require __DIR__ . '/../views/reservas_tematicas/print.php';
    }

    private function aplicarResultadoReservaTematica(ServiceResult $resultado, string $redirect): void
    {
        if ($resultado->isSuccess()) {
            if (request_expects_json()) {
                json_response([
                    'ok' => true,
                    'type' => 'success',
                    'message' => $resultado->message(),
                    'redirect' => $redirect,
                    'payload' => $resultado->payload(),
                ]);
            }
            set_flash('success', $resultado->message());
            $this->redirect($redirect);
        }

        $warningCodes = [
            ReservasTematicasConstants::CODE_FORA_JANELA_RESERVA,
            ReservasTematicasConstants::CODE_RESTAURANTE_FECHADO,
            ReservasTematicasConstants::CODE_IDADES_CHD_INVALIDAS,
            ReservasTematicasConstants::CODE_CHD_MAIOR_QUE_PAX,
            ReservasTematicasConstants::CODE_UH_DUPLICADA_GRUPO,
            ReservasTematicasConstants::CODE_PAX_GRUPO_INVALIDO,
            ReservasTematicasConstants::CODE_CHD_GRUPO_MAIOR_QUE_PAX,
            ReservasTematicasConstants::CODE_GRUPO_UH_MINIMO,
            ReservasTematicasConstants::CODE_RESERVA_DUPLICADA_UH,
            ReservasTematicasConstants::CODE_CONFIRMAR_STATUS_DEFINITIVO,
            ReservasTematicasConstants::CODE_STATUS_DEFINITIVO_BLOQUEADO,
            ReservasTematicasConstants::CODE_TURNO_FECHADO_BLOQUEADO,
            ReservasTematicasConstants::CODE_JUSTIFICATIVA_OBRIGATORIA,
            ReservasTematicasConstants::CODE_CAPACIDADE_NAO_CONFIGURADA,
            ReservasTematicasConstants::CODE_CAPACIDADE_TURNO_ATINGIDA,
            ReservasTematicasConstants::CODE_CAPACIDADE_DESTINO_NAO_CONFIGURADA,
            ReservasTematicasConstants::CODE_CAPACIDADE_DESTINO_ATINGIDA,
            ReservasTematicasConstants::CODE_PAX_REAL_INVALIDO,
            ReservasTematicasConstants::CODE_PAX_REAL_FORA_LIMITE,
            ReservasTematicasConstants::CODE_FECHAMENTO_SEM_TURNO,
            ReservasTematicasConstants::CODE_ACAO_RAPIDA_INVALIDA,
        ];

        $type = in_array($resultado->code(), $warningCodes, true) ? 'warning' : 'danger';
        $message = $this->mensagemReservaTematicaParaUsuario($resultado);
        if (request_expects_json()) {
            json_response([
                'ok' => false,
                'type' => $type,
                'code' => $resultado->code(),
                'message' => $message,
                'payload' => $resultado->payload(),
            ], $type === 'danger' ? 422 : 409);
        }

        set_flash($type, $message);
        $this->redirect($redirect);
    }

    private function mensagemReservaTematicaParaUsuario(ServiceResult $resultado): string
    {
        $payload = $resultado->payload();
        if (!empty($payload['message_resolved'])) {
            return $resultado->message();
        }
        if (in_array($resultado->code(), [
            ReservasTematicasConstants::CODE_CAPACIDADE_TURNO_ATINGIDA,
            ReservasTematicasConstants::CODE_CAPACIDADE_DESTINO_ATINGIDA,
        ], true)) {
            $disponivel = max(0, (int)($payload['pax_disponivel'] ?? 0));
            $tentativa = max(0, (int)($payload['pax_tentativa'] ?? 0));
            $capacidade = max(0, (int)($payload['capacidade'] ?? 0));
            $reservado = max(0, (int)($payload['pax_reservado'] ?? 0));
            $prefixo = $resultado->code() === ReservasTematicasConstants::CODE_CAPACIDADE_DESTINO_ATINGIDA
                ? 'Limite excedido no turno de destino.'
                : 'Limite de reservas excedido para este turno.';

            return $prefixo . ' Disponíveis: ' . $disponivel . ' vaga(s). Tentativa: ' . $tentativa . ' PAX. Capacidade: ' . $capacidade . ' PAX, já reservados: ' . $reservado . ' PAX.';
        }

        if (in_array($resultado->code(), [
            ReservasTematicasConstants::CODE_CAPACIDADE_NAO_CONFIGURADA,
            ReservasTematicasConstants::CODE_CAPACIDADE_DESTINO_NAO_CONFIGURADA,
        ], true)) {
            $tentativa = max(0, (int)($payload['pax_tentativa'] ?? 0));
            $sufixo = $tentativa > 0 ? ' Tentativa atual: ' . $tentativa . ' PAX.' : '';
            return $resultado->message() . $sufixo;
        }

        $messages = [
            ReservasTematicasConstants::CODE_CAPACIDADE_TURNO_ATINGIDA => ReservasTematicasConstants::MESSAGE_CAPACIDADE_TURNO_ATINGIDA,
            ReservasTematicasConstants::CODE_CAPACIDADE_NAO_CONFIGURADA => ReservasTematicasConstants::MESSAGE_CAPACIDADE_NAO_CONFIGURADA,
            ReservasTematicasConstants::CODE_CAPACIDADE_DESTINO_ATINGIDA => ReservasTematicasConstants::MESSAGE_CAPACIDADE_DESTINO_ATINGIDA,
            ReservasTematicasConstants::CODE_CAPACIDADE_DESTINO_NAO_CONFIGURADA => ReservasTematicasConstants::MESSAGE_CAPACIDADE_DESTINO_NAO_CONFIGURADA,
        ];

        $message = $messages[$resultado->code()] ?? $resultado->message();
        return trim($message) !== '' ? $message : 'Não foi possível salvar a reserva. Revise os dados e tente novamente.';
    }

    private function normalizeReservaStatus(string $status): string
    {
        $status = trim(normalize_mojibake($status));
        $map = [
            'Pré-reserva' => ReservasTematicasConstants::STATUS_PRE_RESERVA,
            ReservasTematicasConstants::STATUS_NO_SHOW_ACCENTED => ReservasTematicasConstants::STATUS_NO_SHOW,
            ReservasTematicasConstants::STATUS_DIVERGENCIA_ACCENTED => ReservasTematicasConstants::STATUS_DIVERGENCIA,
            ReservasTematicasConstants::STATUS_OPERACAO_ACCENTED => ReservasTematicasConstants::STATUS_OPERACAO,
            ReservasTematicasConstants::STATUS_CONFERIDA => ReservasTematicasConstants::STATUS_RESERVADA,
            ReservasTematicasConstants::STATUS_EM_ATENDIMENTO => ReservasTematicasConstants::STATUS_RESERVADA,
        ];
        return $map[$status] ?? $status;
    }
}
