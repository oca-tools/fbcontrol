<?php
class ReservasTematicasController extends Controller
{
    private function requireModuleAccess(): void
    {
        $this->requireAuth();
        $user = Auth::user();
        if (!$user) {
            Auth::requireRole(['admin']);
        }
        $perfil = $user['perfil'] ?? '';
        if (in_array($perfil, ['admin', 'supervisor'], true)) {
            return;
        }
        if ($perfil === 'hostess' && $this->hostessHasCorais((int)$user['id'])) {
            return;
        }
        $this->forbidden();
    }

    private function requireReservaAccess(): void
    {
        $this->requireAuth();
        $user = Auth::user();
        if (!$user) {
            Auth::requireRole(['admin']);
        }
        $perfil = $user['perfil'] ?? '';
        if (in_array($perfil, ['admin', 'supervisor'], true)) {
            return;
        }
        if ($perfil === 'hostess' && $this->hostessHasCorais((int)$user['id'])) {
            return;
        }
        $this->forbidden();
    }

    private function requireOperacaoAccess(): void
    {
        $this->requireAuth();
        $user = Auth::user();
        if (!$user) {
            Auth::requireRole(['admin']);
        }
        $perfil = $user['perfil'] ?? '';
        if (in_array($perfil, ['admin', 'supervisor'], true)) {
            return;
        }
        if ($perfil === 'hostess' && ($this->hostessHasCorais((int)$user['id']) || $this->hostessHasTematico((int)$user['id']))) {
            return;
        }
        $this->forbidden();
    }

    private function hostessHasCorais(int $userId): bool
    {
        $assignModel = new UserRestaurantModel();
        $restaurants = $assignModel->byUser($userId);
        foreach ($restaurants as $rest) {
            if (stripos($rest['nome'], 'Corais') !== false) {
                return true;
            }
        }
        return false;
    }

    private function hostessHasTematico(int $userId): bool
    {
        $assignModel = new UserRestaurantModel();
        $restaurants = $assignModel->byUser($userId);
        foreach ($restaurants as $rest) {
            $name = mb_strtolower($rest['nome'], 'UTF-8');
            if (strpos($name, 'giardino') !== false) {
                return true;
            }
            if (strpos($name, 'la brasa') !== false) {
                return true;
            }
            if (strpos($name, 'ix') !== false || strpos($name, 'ixu') !== false) {
                return true;
            }
        }
        return false;
    }

    private function hostessTematicRestaurants(int $userId): array
    {
        $assignModel = new UserRestaurantModel();
        $restaurants = $assignModel->byUser($userId);
        $filtered = [];
        foreach ($restaurants as $rest) {
            $name = mb_strtolower($rest['nome'], 'UTF-8');
            if (strpos($name, 'giardino') !== false || strpos($name, 'la brasa') !== false || strpos($name, 'ix') !== false || strpos($name, 'ixu') !== false) {
                $filtered[] = $rest;
            }
        }
        return $filtered;
    }

    private function getTematicRestaurants(): array
    {
        $model = new RestaurantModel();
        $all = $model->all();
        $allowed = ['giardino', 'la brasa', 'ix\'u', 'ixu'];
        $filtered = [];
        foreach ($all as $rest) {
            $name = mb_strtolower($rest['nome'], 'UTF-8');
            foreach ($allowed as $term) {
                if (strpos($name, $term) !== false) {
                    $filtered[] = $rest;
                    break;
                }
            }
        }
        return $filtered;
    }

    private function isWithinReservaWindow(array $periodos): bool
    {
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
        $logModel = new ReservaTematicaLogModel();
        $unitModel = new UnitModel();

        $restaurantes = $this->getTematicRestaurants();
        $turnos = $turnoModel->allActive();
        $periodos = $periodoModel->allActive();
        $isHostess = ($user['perfil'] ?? '') === 'hostess';
        $withinWindow = $this->isWithinReservaWindow($periodos);
        $canReserveNow = !$isHostess || $withinWindow;

        $filters = [
            'data' => $_GET['data'] ?? date('Y-m-d'),
            'restaurante_id' => $_GET['restaurante_id'] ?? '',
            'turno_id' => $_GET['turno_id'] ?? '',
            'uh_numero' => $_GET['uh_numero'] ?? '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_validate($_POST['csrf_token'] ?? '')) {
                set_flash('danger', 'Token inválido.');
                $this->redirect('/?r=reservasTematicas/reservas');
            }

            $action = $_POST['action'] ?? 'create';
            if ($isHostess && !$withinWindow) {
                set_flash('warning', 'Fora do horário permitido para reservas.');
                $this->redirect('/?r=reservasTematicas/reservas');
            }

            $restauranteId = (int)($_POST['restaurante_id'] ?? 0);
            $dataReserva = $_POST['data_reserva'] ?? date('Y-m-d');
            $turnoId = (int)($_POST['turno_id'] ?? 0);
            $uhNumero = trim($_POST['uh_numero'] ?? '');
            $pax = (int)($_POST['pax'] ?? 0);
            $obs = trim($_POST['observacao_reserva'] ?? '');
            $tags = $_POST['observacao_tags'] ?? [];
            $excedenteChecked = (int)($_POST['excedente'] ?? 0) === 1;
            $excedenteMotivo = trim($_POST['excedente_motivo'] ?? '');

            $restIds = array_map(fn($r) => (int)$r['id'], $restaurantes);
            if ($restauranteId <= 0 || !in_array($restauranteId, $restIds, true)) {
                set_flash('danger', 'Restaurante inválido.');
                $this->redirect('/?r=reservasTematicas/reservas');
            }
            if ($turnoId <= 0) {
                set_flash('danger', 'Selecione o turno.');
                $this->redirect('/?r=reservasTematicas/reservas');
            }
            if ($uhNumero === '') {
                set_flash('danger', 'Informe a UH.');
                $this->redirect('/?r=reservasTematicas/reservas');
            }
            $uh = $unitModel->findByNumero($uhNumero);
            if (!$uh) {
                set_flash('danger', 'UH inválida.');
                $this->redirect('/?r=reservasTematicas/reservas');
            }
            if ($pax <= 0) {
                set_flash('danger', 'Quantidade de PAX inválida.');
                $this->redirect('/?r=reservasTematicas/reservas');
            }
            $maxPax = $unitModel->maxPaxForNumero($uhNumero);
            if ($maxPax && $pax > $maxPax) {
                set_flash('danger', 'PAX acima do limite da UH.');
                $this->redirect('/?r=reservasTematicas/reservas');
            }

            $tagsText = '';
            if (is_array($tags)) {
                $cleanTags = array_filter(array_map('trim', $tags));
                $tagsText = implode(', ', $cleanTags);
            }

            if ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $current = $reservaModel->find($id);
                if (!$current) {
                    set_flash('danger', 'Reserva não encontrada.');
                    $this->redirect('/?r=reservasTematicas/reservas');
                }

                $duplicateId = $reservaModel->findDuplicateId((int)$uh['id'], $dataReserva, $turnoId, $restauranteId);
                if ($duplicateId && (int)$duplicateId !== (int)$current['id']) {
                    set_flash('warning', 'Já existe reserva para esta UH neste turno.');
                    $this->redirect('/?r=reservasTematicas/reservas');
                }

                $capacidadeTurno = 0;
                foreach ($configModel->turnosConfig($restauranteId) as $cfg) {
                    if ((int)$cfg['turno_id'] === $turnoId) {
                        $capacidadeTurno = (int)$cfg['capacidade'];
                        break;
                    }
                }
                if ($capacidadeTurno <= 0 && !in_array($user['perfil'], ['admin', 'supervisor'], true)) {
                    set_flash('danger', 'Capacidade não configurada para este turno.');
                    $this->redirect('/?r=reservasTematicas/reservas');
                }
                $sum = $reservaModel->sumPax($restauranteId, $dataReserva, $turnoId);
                $sumWithout = $sum - ((($current['status'] ?? '') !== 'Cancelada') ? (int)$current['pax'] : 0);
                $excede = ($capacidadeTurno > 0) && (($sumWithout + $pax) > $capacidadeTurno);

                $dataUpdate = [
                    'restaurante_id' => $restauranteId,
                    'data_reserva' => $dataReserva,
                    'turno_id' => $turnoId,
                    'uh_id' => $uh['id'],
                    'pax' => $pax,
                    'observacao_reserva' => $obs,
                    'observacao_tags' => $tagsText,
                    'status' => 'Reservada',
                    'excedente' => 0,
                    'excedente_motivo' => null,
                    'excedente_autor_id' => null,
                    'excedente_em' => null,
                ];

                if ($excede) {
                    if (!in_array($user['perfil'], ['admin', 'supervisor'], true)) {
                        set_flash('danger', 'Capacidade do turno atingida.');
                        $this->redirect('/?r=reservasTematicas/reservas');
                    }
                    if ($excedenteMotivo === '') {
                        set_flash('warning', 'Informe o motivo do excedente.');
                        $this->redirect('/?r=reservasTematicas/reservas&edit=' . $id);
                    }
                    $dataUpdate['excedente'] = 1;
                    $dataUpdate['excedente_motivo'] = $excedenteMotivo;
                    $dataUpdate['excedente_autor_id'] = (int)$user['id'];
                    $dataUpdate['excedente_em'] = date('Y-m-d H:i:s');
                } elseif ($capacidadeTurno <= 0 && in_array($user['perfil'], ['admin', 'supervisor'], true)) {
                    if ($excedenteMotivo === '') {
                        set_flash('warning', 'Informe o motivo para inserir sem capacidade configurada.');
                        $this->redirect('/?r=reservasTematicas/reservas&edit=' . $id);
                    }
                    $dataUpdate['excedente'] = 1;
                    $dataUpdate['excedente_motivo'] = $excedenteMotivo;
                    $dataUpdate['excedente_autor_id'] = (int)$user['id'];
                    $dataUpdate['excedente_em'] = date('Y-m-d H:i:s');
                } elseif ($excedenteChecked && in_array($user['perfil'], ['admin', 'supervisor'], true)) {
                    if ($excedenteMotivo === '') {
                        set_flash('warning', 'Informe o motivo do excedente.');
                        $this->redirect('/?r=reservasTematicas/reservas&edit=' . $id);
                    }
                    $dataUpdate['excedente'] = 1;
                    $dataUpdate['excedente_motivo'] = $excedenteMotivo;
                    $dataUpdate['excedente_autor_id'] = (int)$user['id'];
                    $dataUpdate['excedente_em'] = date('Y-m-d H:i:s');
                }

                $before = $current;
                $reservaModel->update($id, $dataUpdate, (int)$user['id']);
                $after = $reservaModel->find($id) ?? [];
                $logModel->log($id, 'update', (int)$user['id'], $before, $after);

                if (!empty($dataUpdate['excedente'])) {
                    set_flash('warning', 'Reserva excedente registrada e sinalizada.');
                } else {
                    set_flash('success', 'Reserva atualizada.');
                }
                $this->redirect('/?r=reservasTematicas/reservas');
            }

            $duplicateId = $reservaModel->findDuplicateId((int)$uh['id'], $dataReserva, $turnoId, $restauranteId);
            if ($duplicateId) {
                set_flash('warning', 'Já existe reserva para esta UH neste turno.');
                $this->redirect('/?r=reservasTematicas/reservas');
            }

            $capacidadeTurno = 0;
            foreach ($configModel->turnosConfig($restauranteId) as $cfg) {
                if ((int)$cfg['turno_id'] === $turnoId) {
                    $capacidadeTurno = (int)$cfg['capacidade'];
                    break;
                }
            }
            if ($capacidadeTurno <= 0 && !in_array($user['perfil'], ['admin', 'supervisor'], true)) {
                set_flash('danger', 'Capacidade não configurada para este turno.');
                $this->redirect('/?r=reservasTematicas/reservas');
            }
            $sum = $reservaModel->sumPax($restauranteId, $dataReserva, $turnoId);
            $excede = ($capacidadeTurno > 0) && (($sum + $pax) > $capacidadeTurno);

            $dataInsert = [
                'restaurante_id' => $restauranteId,
                'data_reserva' => $dataReserva,
                'turno_id' => $turnoId,
                'uh_id' => $uh['id'],
                'pax' => $pax,
                'observacao_reserva' => $obs,
                'observacao_tags' => $tagsText,
                'status' => 'Reservada',
                'excedente' => 0,
            ];

            if ($excede) {
                if (!in_array($user['perfil'], ['admin', 'supervisor'], true)) {
                    set_flash('danger', 'Capacidade do turno atingida.');
                    $this->redirect('/?r=reservasTematicas/reservas');
                }
                if ($excedenteMotivo === '') {
                    set_flash('warning', 'Informe o motivo do excedente.');
                    $this->redirect('/?r=reservasTematicas/reservas');
                }
                $dataInsert['excedente'] = 1;
                $dataInsert['excedente_motivo'] = $excedenteMotivo;
                $dataInsert['excedente_autor_id'] = (int)$user['id'];
                $dataInsert['excedente_em'] = date('Y-m-d H:i:s');
            } elseif ($capacidadeTurno <= 0 && in_array($user['perfil'], ['admin', 'supervisor'], true)) {
                if ($excedenteMotivo === '') {
                    set_flash('warning', 'Informe o motivo para inserir sem capacidade configurada.');
                    $this->redirect('/?r=reservasTematicas/reservas');
                }
                $dataInsert['excedente'] = 1;
                $dataInsert['excedente_motivo'] = $excedenteMotivo;
                $dataInsert['excedente_autor_id'] = (int)$user['id'];
                $dataInsert['excedente_em'] = date('Y-m-d H:i:s');
            } elseif ($excedenteChecked && in_array($user['perfil'], ['admin', 'supervisor'], true)) {
                if ($excedenteMotivo === '') {
                    set_flash('warning', 'Informe o motivo do excedente.');
                    $this->redirect('/?r=reservasTematicas/reservas');
                }
                $dataInsert['excedente'] = 1;
                $dataInsert['excedente_motivo'] = $excedenteMotivo;
                $dataInsert['excedente_autor_id'] = (int)$user['id'];
                $dataInsert['excedente_em'] = date('Y-m-d H:i:s');
            }

            $id = $reservaModel->create($dataInsert, (int)$user['id']);
            $logModel->log($id, 'create', (int)$user['id'], [], $reservaModel->find($id) ?? []);

            if (!empty($dataInsert['excedente'])) {
                set_flash('warning', 'Reserva excedente registrada e sinalizada.');
            } else {
                set_flash('success', 'Reserva registrada.');
            }
            $this->redirect('/?r=reservasTematicas/reservas');
        }

        $reservas = $reservaModel->listByFilters($filters);

        $availability = [];
        foreach ($restaurantes as $rest) {
            $restId = (int)$rest['id'];
            $turnoCaps = $configModel->turnosConfig($restId);
            foreach ($turnos as $turno) {
                $capacidade = 0;
                foreach ($turnoCaps as $cfg) {
                    if ((int)$cfg['turno_id'] === (int)$turno['id']) {
                        $capacidade = (int)$cfg['capacidade'];
                        break;
                    }
                }
                $sum = $reservaModel->sumPax($restId, $filters['data'], (int)$turno['id']);
                $availability[$restId][(int)$turno['id']] = [
                    'capacidade' => $capacidade,
                    'reservado' => $sum,
                    'restante' => max(0, $capacidade - $sum),
                ];
            }
        }

        $editId = (int)($_GET['edit'] ?? 0);
        $editItem = $editId > 0 ? $reservaModel->find($editId) : null;
        if ($editItem) {
            $uhRow = $unitModel->find((int)$editItem['uh_id']);
            $editItem['uh_numero'] = $uhRow['numero'] ?? '';
        }

        $this->view('reservas_tematicas/reservas', [
            'restaurantes' => $restaurantes,
            'turnos' => $turnos,
            'periodos' => $periodos,
            'reservas' => $reservas,
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

        $reservaModel = new ReservaTematicaModel();
        $turnoModel = new ReservaTematicaTurnoModel();
        $fechamentoModel = new ReservaTematicaFechamentoModel();
        $logModel = new ReservaTematicaLogModel();

        $restaurantes = $this->getTematicRestaurants();
        $restrictedRestaurant = null;
        if (($user['perfil'] ?? '') === 'hostess') {
            $assigned = $this->hostessTematicRestaurants((int)$user['id']);
            if (!empty($assigned)) {
                $restaurantes = $assigned;
                $restrictedRestaurant = $assigned[0] ?? null;
            }
        }
        $turnos = $turnoModel->allActive();

        $filters = [
            'data' => $_GET['data'] ?? date('Y-m-d'),
            'restaurante_id' => $_GET['restaurante_id'] ?? '',
            'turno_id' => $_GET['turno_id'] ?? '',
            'uh_numero' => $_GET['uh_numero'] ?? '',
            'status' => $_GET['status'] ?? '',
            'order' => $_GET['order'] ?? '',
        ];
        if ($restrictedRestaurant) {
            $allowedIds = array_map(fn($r) => (int)$r['id'], $restaurantes);
            if (empty($filters['restaurante_id']) || !in_array((int)$filters['restaurante_id'], $allowedIds, true)) {
                $filters['restaurante_id'] = (string)$allowedIds[0];
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_validate($_POST['csrf_token'] ?? '')) {
                set_flash('danger', 'Token inválido.');
                $this->redirect('/?r=reservasTematicas/operacao');
            }
            $action = $_POST['action'] ?? '';

            if ($action === 'close_turno') {
                $restauranteId = (int)($_POST['restaurante_id'] ?? 0);
                $turnoId = (int)($_POST['turno_id'] ?? 0);
                $data = $_POST['data_reserva'] ?? date('Y-m-d');
                if ($restauranteId <= 0 || $turnoId <= 0) {
                    set_flash('warning', 'Selecione restaurante e turno para encerrar.');
                    $this->redirect('/?r=reservasTematicas/operacao&restaurante_id=' . $restauranteId . '&turno_id=' . $turnoId . '&data=' . $data);
                }
                if (!$fechamentoModel->isClosed($restauranteId, $data, $turnoId)) {
                    $fechamentoModel->close($restauranteId, $data, $turnoId, (int)$user['id']);
                }
                set_flash('success', 'Turno encerrado.');
                $this->redirect('/?r=reservasTematicas/operacao&restaurante_id=' . $restauranteId . '&turno_id=' . $turnoId . '&data=' . $data);
            }

            if ($action === 'update_status') {
                $id = (int)($_POST['id'] ?? 0);
                $status = $this->normalizeReservaStatus(normalize_mojibake(trim($_POST['status'] ?? 'Reservada')));
                $obs = trim($_POST['observacao_operacao'] ?? '');
                $paxRealRaw = trim((string)($_POST['pax_real'] ?? ''));
                $justificativa = trim($_POST['justificativa'] ?? '');
                $confirmFinal = (int)($_POST['confirm_final'] ?? 0) === 1;
                $isFinalStatus = in_array($status, ['Finalizada', 'Não compareceu', 'Cancelada'], true);
                $allowedStatuses = [
                    'Reservada',
                    'Conferida',
                    'Em atendimento',
                    'Finalizada',
                    'Não compareceu',
                    'Cancelada',
                    'Divergência',
                    'Excedente',
                ];
                if (!in_array($status, $allowedStatuses, true)) {
                    set_flash('danger', 'Status inválido.');
                    $this->redirect('/?r=reservasTematicas/operacao');
                }
                if ($isFinalStatus && !$confirmFinal) {
                    set_flash('warning', 'Confirme o status definitivo para continuar.');
                    $this->redirect('/?r=reservasTematicas/operacao');
                }

                $current = $reservaModel->find($id);
                if (!$current) {
                    set_flash('danger', 'Reserva não encontrada.');
                    $this->redirect('/?r=reservasTematicas/operacao');
                }
                $currentStatus = $this->normalizeReservaStatus(normalize_mojibake((string)($current['status'] ?? '')));
                $currentIsFinal = in_array($currentStatus, ['Finalizada', 'Não compareceu', 'Cancelada'], true);
                if ($currentIsFinal && $status !== $currentStatus && !in_array($user['perfil'], ['admin', 'supervisor'], true)) {
                    set_flash('warning', 'Status definitivo não pode ser alterado pela hostess.');
                    $this->redirect('/?r=reservasTematicas/operacao');
                }
                $paxAtual = (int)($current['pax'] ?? 0);
                $paxReal = null;
                if ($paxRealRaw !== '') {
                    if (!ctype_digit($paxRealRaw)) {
                        set_flash('warning', 'PAX real inválido.');
                        $this->redirect('/?r=reservasTematicas/operacao');
                    }
                    $paxReal = (int)$paxRealRaw;
                    if ($paxReal < 0 || $paxReal > $paxAtual) {
                        set_flash('warning', 'PAX real deve estar entre 0 e ' . $paxAtual . '.');
                        $this->redirect('/?r=reservasTematicas/operacao');
                    }
                }
                $isNoShowStatus = ($status === 'Não compareceu');
                $isConferidaStatus = ($status === 'Conferida');
                if ($isConferidaStatus && $paxReal === null) {
                    set_flash('warning', 'Informe o PAX real para conferir a reserva.');
                    $this->redirect('/?r=reservasTematicas/operacao');
                }
                if ($isNoShowStatus && $paxReal === null) {
                    $paxReal = 0;
                }
                if (($isNoShowStatus || $isConferidaStatus) && $paxReal !== null && $paxReal < $paxAtual) {
                    $prefixo = 'No-show parcial: reservado ' . $paxAtual . ', real ' . $paxReal . '.';
                    $obs = $obs !== '' ? ($prefixo . ' ' . $obs) : $prefixo;
                }

                $restauranteId = (int)$current['restaurante_id'];
                $turnoId = (int)$current['turno_id'];
                $dataReserva = $current['data_reserva'];
                $closed = $fechamentoModel->isClosed($restauranteId, $dataReserva, $turnoId);

                if ($closed && !in_array($user['perfil'], ['admin', 'supervisor'], true)) {
                    set_flash('warning', 'Turno encerrado. Somente supervisão pode alterar.');
                    $this->redirect('/?r=reservasTematicas/operacao&restaurante_id=' . $restauranteId . '&turno_id=' . $turnoId . '&data=' . $dataReserva);
                }
                if ($closed && in_array($user['perfil'], ['admin', 'supervisor'], true) && $justificativa === '') {
                    set_flash('warning', 'Informe a justificativa para alterar turno encerrado.');
                    $this->redirect('/?r=reservasTematicas/operacao&restaurante_id=' . $restauranteId . '&turno_id=' . $turnoId . '&data=' . $dataReserva);
                }

                $before = $current;
                $reservaModel->updateOperacao($id, $status, $obs, (int)$user['id'], $paxReal);
                $after = $reservaModel->find($id) ?? [];
                $logModel->log($id, 'status', (int)$user['id'], $before, $after, $justificativa ?: null);

                set_flash('success', 'Status atualizado.');
                $this->redirect('/?r=reservasTematicas/operacao');
            }
        }

        $reservas = $reservaModel->listByFilters($filters);
        $closed = false;
        if (!empty($filters['restaurante_id']) && !empty($filters['turno_id'])) {
            $closed = $fechamentoModel->isClosed((int)$filters['restaurante_id'], $filters['data'], (int)$filters['turno_id']);
        }

        $this->view('reservas_tematicas/operacao', [
            'restaurantes' => $restaurantes,
            'turnos' => $turnos,
            'reservas' => $reservas,
            'filters' => $filters,
            'flash' => get_flash(),
            'closed' => $closed,
            'user' => $user,
            'restricted_restaurant' => $restrictedRestaurant,
        ]);
    }

    public function admin(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);

        $configModel = new ReservaTematicaConfigModel();
        $turnoModel = new ReservaTematicaTurnoModel();
        $periodoModel = new ReservaTematicaPeriodoModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_validate($_POST['csrf_token'] ?? '')) {
                set_flash('danger', 'Token inválido.');
                $this->redirect('/?r=reservasTematicas/admin');
            }

            $action = $_POST['action'] ?? '';
            if ($action === 'config_capacidade') {
                $totais = $_POST['capacidade_total'] ?? [];
                $turnos = $_POST['capacidade_turno'] ?? [];
                foreach ($totais as $restId => $capTotal) {
                    $turnoCaps = $turnos[$restId] ?? [];
                    $configModel->updateConfig((int)$restId, (int)$capTotal, $turnoCaps, (int)Auth::user()['id']);
                }
                set_flash('success', 'Configurações atualizadas.');
                $this->redirect('/?r=reservasTematicas/admin');
            }

            if ($action === 'config_turnos') {
                $items = $_POST['turnos'] ?? [];
                $turnoModel->updateBatch($items);
                set_flash('success', 'Turnos atualizados.');
                $this->redirect('/?r=reservasTematicas/admin');
            }

            if ($action === 'config_periodos') {
                $items = $_POST['periodos'] ?? [];
                $periodoModel->updateBatch($items);
                set_flash('success', 'Períodos atualizados.');
                $this->redirect('/?r=reservasTematicas/admin');
            }
        }

        $restaurantes = $this->getTematicRestaurants();
        $configs = $configModel->configs();
        $turnos = $turnoModel->all();
        $periodos = $periodoModel->all();

        $turnosConfig = [];
        foreach ($restaurantes as $rest) {
            $turnosConfig[(int)$rest['id']] = $configModel->turnosConfig((int)$rest['id'], false);
        }

        $this->view('reservas_tematicas/admin', [
            'restaurantes' => $restaurantes,
            'configs' => $configs,
            'turnos' => $turnos,
            'periodos' => $periodos,
            'turnos_config' => $turnosConfig,
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
            'data' => $_GET['data'] ?? date('Y-m-d'),
            'restaurante_id' => $_GET['restaurante_id'] ?? '',
            'turno_id' => $_GET['turno_id'] ?? '',
            'uh_numero' => $_GET['uh_numero'] ?? '',
            'status' => $_GET['status'] ?? '',
            'order' => $_GET['order'] ?? '',
        ];
        $tipo = $_GET['tipo'] ?? 'detalhada';
        $reservas = $reservaModel->listByFilters($filters);

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

    private function normalizeReservaStatus(string $status): string
    {
        $status = trim(normalize_mojibake($status));
        $map = [
            'Nao compareceu' => 'Não compareceu',
            'Não compareceu' => 'Não compareceu',
            'Divergencia' => 'Divergência',
            'Divergência' => 'Divergência',
            'Operacao' => 'Operação',
        ];
        return $map[$status] ?? $status;
    }
}

