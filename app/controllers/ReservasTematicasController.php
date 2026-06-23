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

    public function reservas(): void
    {
        $this->requireReservaAccess();
        $user = Auth::user();

        $reservaModel = new ReservaTematicaModel();
        $turnoModel = new ReservaTematicaTurnoModel();
        $periodoModel = new ReservaTematicaPeriodoModel();
        $configModel = new ReservaTematicaConfigModel();
        $bloqueioDataModel = new ReservaTematicaBloqueioDataModel();
        $unitModel = new UnitModel();

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

        if (($_GET['ajax'] ?? '') === 'availability') {
            $dateAjax = sanitize_date_param($_GET['data'] ?? '', date('Y-m-d'));
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'date' => $dateAjax,
                'availability' => $buildAvailability($dateAjax),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (($_GET['ajax'] ?? '') === 'availability_detail') {
            $dateAjax = sanitize_date_param($_GET['data'] ?? '', date('Y-m-d'));
            $restauranteId = (int)($_GET['restaurante_id'] ?? 0);
            $turnoId = (int)($_GET['turno_id'] ?? 0);
            if ($restauranteId <= 0 || $turnoId <= 0) {
                json_response([
                    'ok' => false,
                    'message' => 'Parâmetros inválidos para detalhamento.',
                ], 400);
            }

            $rows = $reservaModel->listByFilters([
                'data' => $dateAjax,
                'restaurante_id' => $restauranteId,
                'turno_id' => $turnoId,
                'status' => ReservasTematicasConstants::STATUS_RESERVADA,
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
                $qtdChd = max((int)($row['qtd_chd_calc'] ?? 0), (int)($row['pax_chd_calc'] ?? 0));
                $totalPax += $pax;
                $totalChd += $qtdChd;
                $items[] = [
                    'id' => (int)($row['id'] ?? 0),
                    'uh_numero' => (string)($row['uh_numero'] ?? ''),
                    'titular_nome' => normalize_mojibake((string)($row['titular_nome_display'] ?? $row['titular_nome'] ?? '')),
                    'pax' => $pax,
                    'qtd_chd' => $qtdChd,
                    'status' => $this->normalizeReservaStatus((string)($row['status'] ?? '')),
                    'restaurante' => normalize_mojibake((string)($row['restaurante'] ?? '')),
                    'turno_hora' => (string)($row['turno_hora'] ?? ''),
                    'usuario' => normalize_mojibake((string)($row['usuario'] ?? '')),
                    'edit_url' => ReservaTematicaPolicy::canEdit($row, $user) ? '/?r=reservasTematicas/reservas&edit=' . (int)($row['id'] ?? 0) : '',
                ];
            }

            json_response([
                'ok' => true,
                'date' => $dateAjax,
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
            if (!csrf_validate($_POST['csrf_token'] ?? '')) {
                if (request_expects_json()) {
                    json_response([
                        'ok' => false,
                        'type' => 'danger',
                        'code' => 'csrf_invalido',
                        'message' => 'Sessão expirada. Atualize a página e tente novamente.',
                    ], 419);
                }
                set_flash('danger', 'Token inválido.');
                $this->redirect('/?r=reservasTematicas/reservas');
            }

            $resultadoReserva = (new CriarReservaService())->executar(new CriarReservaCommand([
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
            ]));
            $this->aplicarResultadoReservaTematica($resultadoReserva, '/?r=reservasTematicas/reservas');
        }

        $availability = $buildAvailability((string)($filters['data'] ?? date('Y-m-d')));

        $editId = (int)($_GET['edit'] ?? 0);
        $editItem = $editId > 0 ? $reservaModel->find($editId) : null;
        if ($editItem) {
            if (!ReservaTematicaPolicy::canEdit($editItem, $user)) {
                set_flash('danger', 'Você só pode editar reservas criadas por você. A administração pode acompanhar as alterações pela auditoria.');
                $this->redirect('/?r=reservasTematicas/reservas');
            }
            $uhRow = $unitModel->find((int)$editItem['uh_id']);
            $editItem['uh_numero'] = $uhRow['numero'] ?? '';
            $agesMap = $reservaModel->getChdAgesMap([$editId]);
            $editItem['chd_idades'] = isset($agesMap[$editId]) && !empty($agesMap[$editId]) ? implode('', $agesMap[$editId]) : '';
            $editItem['qtd_chd'] = (int)($editItem['qtd_chd'] ?? 0);
            $editItem['pax_adulto'] = (int)($editItem['pax_adulto'] ?? max(0, (int)($editItem['pax'] ?? 0) - (int)$editItem['qtd_chd']));
        }

        $this->view('reservas_tematicas/reservas', [
            'restaurantes' => $restaurantes,
            'turnos' => $turnos,
            'periodos' => $periodos,
            'availability' => $availability,
            'filters' => $filters,
            'flash' => get_flash(),
            'can_reserve' => $canReserveNow,
            'edit_item' => $editItem,
            'is_hostess' => $isHostess,
        ]);
    }

    public function operacao(): void
    {
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
                    ], 419);
                }
                set_flash('danger', 'Token inválido.');
                $this->redirect('/?r=reservasTematicas/operacao');
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
                'status' => $_POST['status'] ?? ReservasTematicasConstants::STATUS_RESERVADA,
                'observacao_operacao' => $_POST['observacao_operacao'] ?? '',
                'pax_real' => $_POST['pax_real'] ?? '',
                'justificativa' => $_POST['justificativa'] ?? '',
                'confirmou_status_final' => (int)($_POST['confirm_final'] ?? 0) === 1,
                'acao_rapida' => $_POST['quick_action'] ?? '',
            ]));
            $redirectOperacao = '/?r=reservasTematicas/operacao';
            if ($resultadoOperacao->isSuccess() && !empty($resultadoOperacao->payload()['redirect_query'])) {
                $redirectOperacao .= '&' . $resultadoOperacao->payload()['redirect_query'];
            }
            $this->aplicarResultadoReservaTematica($resultadoOperacao, $redirectOperacao);
        }

        $reservas = $reservaModel->listByFilters($filters);
        $summary = [
            'total' => count($reservas),
            'reservada' => 0,
            'finalizada' => 0,
            'nao_compareceu' => 0,
            'cancelada' => 0,
            'divergencia' => 0,
        ];
        foreach ($reservas as $row) {
            $status = $this->normalizeReservaStatus((string)($row['status_reserva'] ?? ($row['status'] ?? '')));
            if ($status === ReservasTematicasConstants::STATUS_RESERVADA) {
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

        $this->view('reservas_tematicas/operacao', [
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

    public function admin(): void
    {
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
                $this->redirect('/?r=reservasTematicas/admin');
            }

            $removeTurnoId = (int)($_POST['remove_turno_id'] ?? 0);
            if ($removeTurnoId > 0) {
                $turno = $turnoModel->find($removeTurnoId);
                if (!$turno) {
                    set_flash('warning', 'Turno não encontrado.');
                    $this->redirect('/?r=reservasTematicas/admin');
                }

                $result = $turnoModel->removeOrInactivate($removeTurnoId, (int)Auth::user()['id']);
                if (($result['result'] ?? '') === 'deleted') {
                    set_flash('success', 'Turno removido com sucesso.');
                } else {
                    set_flash('info', 'Turno com histórico foi inativado para preservar os dados.');
                }
                $this->redirect('/?r=reservasTematicas/admin');
            }

            $action = $_POST['action'] ?? '';
            if ($action === 'bloqueio_data') {
                if (!$canManageBloqueios) {
                    set_flash('danger', 'Somente admin e gerente podem alterar fechamentos dos temáticos.');
                    $this->redirect('/?r=reservasTematicas/admin');
                }
                $dataBloqueio = sanitize_date_param($_POST['data_bloqueio'] ?? '', '');
                $restauranteId = (int)($_POST['restaurante_id'] ?? 0);
                $fechar = (int)($_POST['fechar'] ?? 1) === 1;
                $motivo = normalize_mojibake(trim((string)($_POST['motivo'] ?? '')));
                $restIds = array_map(static fn($rest) => (int)$rest['id'], $this->getTematicRestaurants());
                if ($dataBloqueio === '' || !in_array($restauranteId, $restIds, true)) {
                    set_flash('warning', 'Informe uma data e um restaurante temático válidos.');
                    $this->redirect('/?r=reservasTematicas/admin');
                }
                if ($fechar && $motivo === '') {
                    set_flash('warning', 'Informe o motivo do fechamento.');
                    $this->redirect('/?r=reservasTematicas/admin&cap_data=' . urlencode($dataBloqueio));
                }
                $bloqueioDataModel->setClosed($restauranteId, $dataBloqueio, $fechar, $motivo, (int)Auth::user()['id']);
                set_flash('success', $fechar ? 'Restaurante fechado para a data selecionada.' : 'Restaurante reaberto para a data selecionada.');
                $this->redirect('/?r=reservasTematicas/admin&cap_data=' . urlencode($dataBloqueio));
            }
            if ($action === 'bloqueio_semanal') {
                if (!$canManageBloqueios) {
                    set_flash('danger', 'Somente admin e gerente podem alterar fechamentos semanais dos temáticos.');
                    $this->redirect('/?r=reservasTematicas/admin');
                }
                $restauranteId = (int)($_POST['restaurante_id'] ?? 0);
                $diaSemana = (int)($_POST['dia_semana'] ?? -1);
                $fechar = (int)($_POST['fechar'] ?? 1) === 1;
                $motivo = normalize_mojibake(trim((string)($_POST['motivo'] ?? '')));
                $restIds = array_map(static fn($rest) => (int)$rest['id'], $this->getTematicRestaurants());
                if (!in_array($restauranteId, $restIds, true) || $diaSemana < 0 || $diaSemana > 6) {
                    set_flash('warning', 'Informe restaurante e dia da semana válidos.');
                    $this->redirect('/?r=reservasTematicas/admin');
                }
                if ($fechar && $motivo === '') {
                    set_flash('warning', 'Informe o motivo do fechamento semanal.');
                    $this->redirect('/?r=reservasTematicas/admin');
                }
                $bloqueioSemanalModel->setClosed($restauranteId, $diaSemana, $fechar, $motivo, (int)Auth::user()['id']);
                set_flash('success', $fechar ? 'Fechamento semanal salvo.' : 'Fechamento semanal removido.');
                $this->redirect('/?r=reservasTematicas/admin');
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
                $this->redirect('/?r=reservasTematicas/admin');
            }

            if ($action === 'config_capacidade_data') {
                $dataCapacidade = sanitize_date_param($_POST['capacidade_data'] ?? '', '');
                if ($dataCapacidade === '') {
                    set_flash('warning', 'Informe uma data válida para a capacidade futura.');
                    $this->redirect('/?r=reservasTematicas/admin');
                }
                $configModel->updateDateConfig($dataCapacidade, $_POST['capacidade_data_turno'] ?? [], (int)Auth::user()['id']);
                set_flash('success', 'Capacidade específica da data atualizada.');
                $this->redirect('/?r=reservasTematicas/admin&cap_data=' . urlencode($dataCapacidade));
            }

            if ($action === 'config_turnos') {
                $items = $_POST['turnos'] ?? [];
                $turnoModel->updateBatch($items, (int)Auth::user()['id']);
                set_flash('success', 'Turnos atualizados.');
                $this->redirect('/?r=reservasTematicas/admin');
            }

            if ($action === 'add_turno') {
                $horaInput = trim((string)($_POST['novo_turno_hora'] ?? ''));
                $ordem = (int)($_POST['novo_turno_ordem'] ?? 0);
                $ativo = (int)($_POST['novo_turno_ativo'] ?? 1) === 1 ? 1 : 0;

                if ($horaInput === '') {
                    set_flash('warning', 'Informe o horário do novo turno.');
                    $this->redirect('/?r=reservasTematicas/admin');
                }

                $hora = strlen($horaInput) === 5 ? ($horaInput . ':00') : $horaInput;
                $horaValida = DateTime::createFromFormat('H:i:s', $hora);
                if (!$horaValida || $horaValida->format('H:i:s') !== $hora) {
                    set_flash('warning', 'Horário inválido para novo turno.');
                    $this->redirect('/?r=reservasTematicas/admin');
                }

                $turnoModel->create($hora, $ativo, $ordem, (int)Auth::user()['id']);
                set_flash('success', 'Novo turno adicionado.');
                $this->redirect('/?r=reservasTematicas/admin');
            }

            if ($action === 'config_periodos') {
                $items = $_POST['periodos'] ?? [];
                $periodoModel->updateBatch($items, (int)Auth::user()['id']);
                set_flash('success', 'Períodos atualizados.');
                $this->redirect('/?r=reservasTematicas/admin');
            }

            if ($action === 'add_periodo') {
                $inicioInput = trim((string)($_POST['novo_periodo_inicio'] ?? ''));
                $fimInput = trim((string)($_POST['novo_periodo_fim'] ?? ''));
                $ordem = (int)($_POST['novo_periodo_ordem'] ?? 0);
                $ativo = (int)($_POST['novo_periodo_ativo'] ?? 1) === 1 ? 1 : 0;

                if ($inicioInput === '' || $fimInput === '') {
                    set_flash('warning', 'Informe início e fim do novo período.');
                    $this->redirect('/?r=reservasTematicas/admin');
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
                    $this->redirect('/?r=reservasTematicas/admin');
                }

                if ($inicio >= $fim) {
                    set_flash('warning', 'O horário final deve ser maior que o inicial.');
                    $this->redirect('/?r=reservasTematicas/admin');
                }

                $periodoModel->create($inicio, $fim, $ativo, $ordem, (int)Auth::user()['id']);
                set_flash('success', 'Novo período adicionado.');
                $this->redirect('/?r=reservasTematicas/admin');
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

        $this->view('reservas_tematicas/admin', [
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
            ReservasTematicasConstants::STATUS_NO_SHOW_ACCENTED => ReservasTematicasConstants::STATUS_NO_SHOW,
            ReservasTematicasConstants::STATUS_DIVERGENCIA_ACCENTED => ReservasTematicasConstants::STATUS_DIVERGENCIA,
            ReservasTematicasConstants::STATUS_OPERACAO_ACCENTED => ReservasTematicasConstants::STATUS_OPERACAO,
            ReservasTematicasConstants::STATUS_CONFERIDA => ReservasTematicasConstants::STATUS_RESERVADA,
            ReservasTematicasConstants::STATUS_EM_ATENDIMENTO => ReservasTematicasConstants::STATUS_RESERVADA,
        ];
        return $map[$status] ?? $status;
    }
}
