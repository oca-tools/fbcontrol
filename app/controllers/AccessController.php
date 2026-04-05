<?php
class AccessController extends Controller
{
    private const RESERVA_STATUS_FINAIS = ['Finalizada', 'Nao compareceu', 'Cancelada'];

    private function isTematicoRestaurantName(string $name): bool
    {
        $name = mb_strtolower(normalize_mojibake($name), 'UTF-8');
        return strpos($name, 'giardino') !== false
            || strpos($name, 'la brasa') !== false
            || strpos($name, "ix'u") !== false
            || strpos($name, 'ixu') !== false
            || strpos($name, 'ix') !== false;
    }

    private function isTematicoOperationName(string $name): bool
    {
        $name = mb_strtolower(normalize_mojibake($name), 'UTF-8');
        $name = strtr($name, [
            'á' => 'a',
            'à' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'é' => 'e',
            'ê' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ú' => 'u',
            'ç' => 'c',
        ]);
        return strpos($name, 'tematico') !== false;
    }

    private function isLaBrasaRestaurantName(string $name): bool
    {
        $name = mb_strtolower(normalize_mojibake($name), 'UTF-8');
        return strpos($name, 'la brasa') !== false;
    }

    private function isTematicoShift(array $shift): bool
    {
        $restaurante = (string)($shift['restaurante'] ?? '');
        $operacao = (string)($shift['operacao'] ?? '');
        if (!$this->isTematicoRestaurantName($restaurante)) {
            return false;
        }
        if ($this->isLaBrasaRestaurantName($restaurante)) {
            return $this->isTematicoOperationName($operacao);
        }
        return true;
    }

    private function isHostessTematicoOnlyUser(?array $user): bool
    {
        if (!$user || ($user['perfil'] ?? '') !== 'hostess') {
            return false;
        }

        $assigned = (new UserRestaurantModel())->byUser((int)$user['id']);
        if (empty($assigned)) {
            return false;
        }

        $hasTematico = false;
        $hasRegistroBuffet = false;
        foreach ($assigned as $rest) {
            $name = (string)($rest['nome'] ?? '');
            if ($this->isTematicoRestaurantName($name)) {
                $hasTematico = true;
                continue;
            }
            $hasRegistroBuffet = true;
        }

        return $hasTematico && !$hasRegistroBuffet;
    }

    private function redirectIfTematicoOnlyHostess(?array $user = null): bool
    {
        // Fluxo v2: hostess de tematico tambem opera pelo modulo Registro.
        return false;
    }

    private function autoCloseTimeoutShiftsForCurrentUser(): int
    {
        $user = Auth::user();
        if (!$user) {
            return 0;
        }
        $graceMinutes = 10;
        $closed = 0;
        $closed += (new ShiftModel())->autoCloseExpired($graceMinutes, (int)$user['id']);
        $closed += (new SpecialShiftModel())->autoCloseExpired($graceMinutes, (int)$user['id']);
        return $closed;
    }

    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'hostess']);
        $user = Auth::user();
        if ($this->redirectIfTematicoOnlyHostess($user)) {
            return;
        }

        $closedByTimeout = $this->autoCloseTimeoutShiftsForCurrentUser();
        if ($closedByTimeout > 0 && !isset($_SESSION['flash'])) {
            set_flash('warning', 'Turno encerrado automaticamente por tempo excedido (limite + 10 min).');
        }

        $allowHostessTutorial = (($user['perfil'] ?? '') === 'hostess');
        $showHostessTutorial = false;
        if (($user['perfil'] ?? '') === 'hostess') {
            $onboarding = (new UserOnboardingModel())->getByUser((int)$user['id']);
            $showHostessTutorial = (int)($onboarding['hostess_tutorial_completed'] ?? 0) !== 1;
        }

        $shiftModel = new ShiftModel();
        $shift = $shiftModel->getActiveByUser(Auth::user()['id']);

        if (!$shift) {
            $restaurantModel = new RestaurantModel();
            $opModel = new RestaurantOperationModel();
            $doorModel = new DoorModel();
            $userRestaurantModel = new UserRestaurantModel();

            $restaurantes = $user['perfil'] === 'hostess'
                ? $userRestaurantModel->byUser($user['id'])
                : $restaurantModel->all();

            $allowedOpsByRest = [];
            if ($user['perfil'] === 'hostess') {
                $allowedOpsByRest = (new UserRestaurantOperationModel())->operationsByUser((int)$user['id']);
            }

            $restOps = [];
            foreach ($restaurantes as $rest) {
                $ops = $opModel->byRestaurant((int)$rest['id']);
                $restId = (int)$rest['id'];
                if (!empty($allowedOpsByRest[$restId])) {
                    $allowed = array_values(array_unique(array_map('intval', $allowedOpsByRest[$restId])));
                    $ops = array_filter($ops, static function (array $op) use ($allowed): bool {
                        return in_array((int)($op['operacao_id'] ?? 0), $allowed, true);
                    });
                }
                $restOps[$rest['id']] = array_values($ops);
            }

            $doorsByRestaurant = [];
            foreach ($restaurantes as $rest) {
                $doorsByRestaurant[$rest['id']] = $doorModel->byRestaurant((int)$rest['id']);
            }

            $this->view('access/index', [
                'mode' => 'start',
                'restaurantes' => $restaurantes,
                'restOps' => $restOps,
                'doorsByRestaurant' => $doorsByRestaurant,
                'flash' => get_flash(),
                'need_confirm' => false,
                'preselect' => [],
                'allow_hostess_tutorial' => $allowHostessTutorial,
                'show_hostess_tutorial' => $showHostessTutorial,
            ]);
            return;
        }

        $restOpModel = new RestaurantOperationModel();
        $restOp = $restOpModel->findByRestaurantOperation((int)$shift['restaurante_id'], (int)$shift['operacao_id']);
        $toleranceAlert = null;
        if ($restOp) {
            $now = new DateTime('now', new DateTimeZone(date_default_timezone_get()));
            $end = DateTime::createFromFormat('H:i:s', $restOp['hora_fim'], new DateTimeZone(date_default_timezone_get()));
            $tol = (int)$restOp['tolerancia_min'];
            if ($end && $tol > 0) {
                $end->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));
                $startTol = clone $end;
                $startTol->modify("-{$tol} minutes");
                if ($now >= $startTol && $now < $end) {
                    $minsToEnd = (int)ceil(($end->getTimestamp() - $now->getTimestamp()) / 60);
                    $toleranceAlert = 'Atenção: faltam ' . $minsToEnd . ' min para o fim do turno (tolerância ativa).';
                }
            }
        }

        if ($this->isTematicoShift($shift)) {
            $dateRef = trim((string)($_GET['data'] ?? date('Y-m-d')));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateRef)) {
                $dateRef = date('Y-m-d');
            }
            $search = normalize_mojibake(trim((string)($_GET['q'] ?? '')));
            $status = normalize_mojibake(trim((string)($_GET['status'] ?? '')));

            $autoNoShowCount = $this->applyTematicaAutoNoShow((int)$shift['restaurante_id'], $dateRef, (int)$user['id']);
            if ($autoNoShowCount > 0 && !isset($_SESSION['flash'])) {
                set_flash('warning', $autoNoShowCount . ' reserva(s) foram marcadas como Não compareceu automaticamente.');
            }

            $reservaModel = new ReservaTematicaModel();
            $reservas = $reservaModel->listByFilters([
                'data' => $dateRef,
                'restaurante_id' => (int)$shift['restaurante_id'],
                'turno_id' => '',
                'uh_numero' => '',
                'titular' => $search,
                'status' => $status,
                'order' => 'status',
            ]);
            $canCancelTematico = $this->canCancelTematicoShift($shift, (int)$user['id']);

            $this->view('access/tematica', [
                'turno' => $shift,
                'restOp' => $restOp,
                'tolerance_alert' => $toleranceAlert,
                'reservas' => $reservas,
                'filters' => [
                    'data' => $dateRef,
                    'q' => $search,
                    'status' => $status,
                ],
                'can_cancel' => $canCancelTematico,
                'flash' => get_flash(),
                'allow_hostess_tutorial' => $allowHostessTutorial,
                'show_hostess_tutorial' => $showHostessTutorial,
            ]);
            return;
        }

        $accessModel = new AccessModel();
        $canCancel = $accessModel->countByTurno((int)$shift['id']) === 0;
        $lastEditableAccess = $accessModel->findLastEditableByTurnoUser((int)$shift['id'], (int)Auth::user()['id'], 2);

        $this->view('access/index', [
            'mode' => 'register',
            'turno' => $shift,
            'restOp' => $restOp,
            'tolerance_alert' => $toleranceAlert,
            'recentes' => $accessModel->listRecent(10),
            'flash' => get_flash(),
            'is_corais' => ($shift['restaurante'] ?? '') === 'Restaurante Corais',
            'is_corais_jantar' => (($shift['restaurante'] ?? '') === 'Restaurante Corais') && (stripos($shift['operacao'] ?? '', 'Jantar') !== false),
            'can_cancel' => $canCancel,
            'last_editable_access' => $lastEditableAccess,
            'allow_hostess_tutorial' => $allowHostessTutorial,
            'show_hostess_tutorial' => $showHostessTutorial,
        ]);
    }

    public function start(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'hostess']);
        $this->autoCloseTimeoutShiftsForCurrentUser();
        if ($this->redirectIfTematicoOnlyHostess(Auth::user())) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=access/index');
        }

        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=access/index');
        }

        $user = Auth::user();
        $shiftModel = new ShiftModel();
        $active = $shiftModel->getActiveByUser($user['id']);
        if ($active) {
            $this->redirect('/?r=access/index');
        }

        $restaurantModel = new RestaurantModel();
        $opModel = new RestaurantOperationModel();
        $doorModel = new DoorModel();
        $userRestaurantModel = new UserRestaurantModel();

        $restaurantes = $user['perfil'] === 'hostess'
            ? $userRestaurantModel->byUser($user['id'])
            : $restaurantModel->all();

        $allowedOpsByRest = [];
        if ($user['perfil'] === 'hostess') {
            $allowedOpsByRest = (new UserRestaurantOperationModel())->operationsByUser((int)$user['id']);
        }

        $restOps = [];
        foreach ($restaurantes as $rest) {
            $ops = $opModel->byRestaurant((int)$rest['id']);
            $restId = (int)$rest['id'];
            if (!empty($allowedOpsByRest[$restId])) {
                $allowed = array_values(array_unique(array_map('intval', $allowedOpsByRest[$restId])));
                $ops = array_filter($ops, static function (array $op) use ($allowed): bool {
                    return in_array((int)($op['operacao_id'] ?? 0), $allowed, true);
                });
            }
            $restOps[$rest['id']] = array_values($ops);
        }

        $doorsByRestaurant = [];
        foreach ($restaurantes as $rest) {
            $doorsByRestaurant[$rest['id']] = $doorModel->byRestaurant((int)$rest['id']);
        }

        $restauranteId = (int)($_POST['restaurante_id'] ?? 0);
        $operacaoId = (int)($_POST['operacao_id'] ?? 0);
        $portaId = (int)($_POST['porta_id'] ?? 0);
        $confirmStart = (int)($_POST['confirm_start'] ?? 0) === 1;
        $confirmEarly = (int)($_POST['confirm_early'] ?? 0) === 1;

        if ($restauranteId <= 0 || $operacaoId <= 0) {
            set_flash('danger', 'Selecione restaurante e operação.');
            $this->redirect('/?r=access/index');
        }

        if (($user['perfil'] ?? '') === 'hostess') {
            $allowedRestaurantIds = array_values(array_unique(array_map(static fn($r) => (int)($r['id'] ?? 0), $restaurantes)));
            if (!in_array($restauranteId, $allowedRestaurantIds, true)) {
                set_flash('danger', 'Restaurante não autorizado para este usuário.');
                $this->redirect('/?r=access/index');
            }

            $allowedOps = array_values(array_unique(array_map('intval', $allowedOpsByRest[$restauranteId] ?? [])));
            if (!empty($allowedOps) && !in_array($operacaoId, $allowedOps, true)) {
                set_flash('danger', 'Operação não autorizada para este usuário.');
                $this->redirect('/?r=access/index');
            }
        }

        if ($portaId > 0) {
            $doorIds = array_values(array_unique(array_map(static fn($d) => (int)($d['id'] ?? 0), $doorsByRestaurant[$restauranteId] ?? [])));
            if (!in_array($portaId, $doorIds, true)) {
                set_flash('danger', 'Porta inválida para o restaurante selecionado.');
                $this->redirect('/?r=access/index');
            }
        }

        $restOp = $opModel->findByRestaurantOperation($restauranteId, $operacaoId);
        if (!$restOp) {
            set_flash('danger', 'Operação inválida para este restaurante.');
            $this->redirect('/?r=access/index');
        }
        $outsideHorario = $this->isOutsideHorario($restOp);

        $rest = $restaurantModel->find($restauranteId);
        if ($rest && (int)$rest['seleciona_porta_no_turno'] === 1 && $portaId <= 0) {
            set_flash('danger', 'Selecione a porta.');
            $this->redirect('/?r=access/index');
        }

        if ($outsideHorario && !$confirmEarly) {
            if (!isset($_SESSION['flash'])) {
                set_flash('warning', 'Turno fora do horário. Confirme se deseja continuar.');
            }
            $this->view('access/index', [
                'mode' => 'start',
                'restaurantes' => $restaurantes,
                'restOps' => $restOps,
                'doorsByRestaurant' => $doorsByRestaurant,
                'flash' => get_flash(),
                'need_confirm' => true,
                'preselect' => [
                    'restaurante_id' => $restauranteId,
                    'operacao_id' => $operacaoId,
                    'porta_id' => $portaId,
                ],
            ]);
            return;
        }

        if (!$confirmStart) {
            set_flash('warning', 'Confirme o checklist para iniciar o turno.');
            $this->view('access/index', [
                'mode' => 'start',
                'restaurantes' => $restaurantes,
                'restOps' => $restOps,
                'doorsByRestaurant' => $doorsByRestaurant,
                'flash' => get_flash(),
                'need_confirm' => false,
                'preselect' => [
                    'restaurante_id' => $restauranteId,
                    'operacao_id' => $operacaoId,
                    'porta_id' => $portaId,
                ],
            ]);
            return;
        }

        $shiftModel->start([
            'restaurante_id' => $restauranteId,
            'operacao_id' => $operacaoId,
            'porta_id' => $portaId > 0 ? $portaId : null,
        ], $user['id']);

        $this->redirect('/?r=access/index');
    }

    public function register(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'hostess']);
        $this->autoCloseTimeoutShiftsForCurrentUser();
        if ($this->redirectIfTematicoOnlyHostess(Auth::user())) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=access/index');
        }

        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=access/index');
        }

        $shiftModel = new ShiftModel();
        $shift = $shiftModel->getActiveByUser(Auth::user()['id']);
        if (!$shift) {
            set_flash('danger', 'Inicie um turno para registrar acessos.');
            $this->redirect('/?r=access/index');
        }
        if ($this->isTematicoShift($shift)) {
            set_flash('info', 'Neste turno temático, confirme as reservas na tela de operação temática do Registro.');
            $this->redirect('/?r=access/index');
        }

        $uhNumero = trim($_POST['uh_numero'] ?? '');
        $pax = (int)($_POST['pax'] ?? 0);

        if ($shift['exige_pax'] == 0) {
            $pax = max(1, $pax);
        }

        if ($uhNumero === '' || ($shift['exige_pax'] == 1 && $pax <= 0)) {
            set_flash('danger', 'Preencha todos os campos obrigatórios.');
            $this->redirect('/?r=access/index');
        }

        if ($shift['exige_pax'] == 1) {
            $unitModel = new UnitModel();
            $maxPax = $unitModel->maxPaxForNumero($uhNumero);
            if ($maxPax !== null && $pax > $maxPax) {
                set_flash('danger', 'PAX excede o limite da UH. Máximo permitido: ' . $maxPax . '.');
                $this->redirect('/?r=access/index');
            }
        }

        $accessModel = new AccessModel();
        if ($shift['exige_pax'] == 1) {
            $unitModel = new UnitModel();
            $maxPax = $unitModel->maxPaxForNumero($uhNumero);
            if ($maxPax !== null) {
                $today = (new DateTime('now', new DateTimeZone(date_default_timezone_get())))->format('Y-m-d');
                $currentTotal = $accessModel->sumPaxByUhOperacaoDate($uhNumero, (int)$shift['operacao_id'], $today);
                if (($currentTotal + $pax) > $maxPax) {
                    $resta = max(0, $maxPax - $currentTotal);
                    set_flash('danger', 'PAX excede o limite da UH para esta operação no dia. Restante disponível: ' . $resta . '.');
                    $this->redirect('/?r=access/index');
                }
            }
        }

        $coraisNoShowAllowed = false;
        $isCorais = stripos($shift['restaurante'] ?? '', 'Corais') !== false;
        $isJantar = stripos($shift['operacao'] ?? '', 'Jantar') !== false;
        if ($isCorais && $isJantar) {
            $tematicaModel = new ReservaTematicaModel();
            $today = (new DateTime('now', new DateTimeZone(date_default_timezone_get())))->format('Y-m-d');
            $tematica = $tematicaModel->findTematicaByUhDate($uhNumero, $today);
            if ($tematica) {
                $confirmNoShow = (int)($_POST['confirm_no_show'] ?? 0) === 1;
                if ($this->isNoShowStatus((string)($tematica['status'] ?? ''))) {
                    if (!$confirmNoShow) {
                        $mensagem = 'UH ' . $uhNumero . ' está como no-show no temático (' . $tematica['restaurante'] . '). Confirme para registrar no Jantar Corais.';
                        set_flash('warning', $mensagem);
                        $this->redirect('/?r=access/index');
                    }
                    $coraisNoShowAllowed = true;
                } else {
                    $mensagem = 'Atenção: UH ' . $uhNumero . ' possui reserva no ' . $tematica['restaurante'];
                    if (!empty($tematica['turno_hora'])) {
                        $mensagem .= ' às ' . $tematica['turno_hora'];
                    }
                    $mensagem .= '. Não é permitido registrar no buffet.';
                    set_flash('danger', $mensagem);
                    $this->redirect('/?r=access/index');
                }
            }
        }
        $result = $accessModel->register([
            'uh_numero' => $uhNumero,
            'pax' => $pax,
            'restaurante_id' => $shift['restaurante_id'],
            'porta_id' => $shift['porta_id'] ?? null,
            'operacao_id' => $shift['operacao_id'],
            'turno_id' => $shift['id'],
        ], Auth::user()['id']);

        if (!empty($result['error']) && $result['error'] === 'uh_invalida') {
            set_flash('danger', 'UH inválida. Verifique o número do apartamento.');
        } elseif (($result['id'] ?? 0) > 0) {
            if ($coraisNoShowAllowed) {
                set_flash('warning', 'Registro permitido por no-show temático confirmado.');
            } elseif (!empty($result['alerta_duplicidade'])) {
                set_flash('warning', 'Atenção: possível duplicidade em menos de 10 minutos.');
            } elseif (!empty($result['fora_do_horario'])) {
                set_flash('warning', 'Atenção: acesso fora do horário da operação.');
            } else {
                set_flash('success', 'Acesso registrado com sucesso.');
            }
        } else {
            set_flash('danger', 'Falha ao registrar acesso.');
        }

        $this->redirect('/?r=access/index');
    }

    public function register_tematica(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'hostess']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=access/index');
        }

        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=access/index');
        }

        $shift = (new ShiftModel())->getActiveByUser((int)Auth::user()['id']);
        if (!$shift) {
            set_flash('danger', 'Inicie um turno para operar reservas temáticas.');
            $this->redirect('/?r=access/index');
        }
        if (!$this->isTematicoShift($shift)) {
            set_flash('danger', 'Este turno não é temático.');
            $this->redirect('/?r=access/index');
        }

        $reservaId = (int)($_POST['reserva_id'] ?? 0);
        $acao = normalize_mojibake(trim((string)($_POST['acao_tematica'] ?? '')));
        $dateRef = trim((string)($_POST['data_ref'] ?? date('Y-m-d')));
        $query = normalize_mojibake(trim((string)($_POST['q'] ?? '')));
        $statusFilter = normalize_mojibake(trim((string)($_POST['status'] ?? '')));

        if ($reservaId <= 0 || !in_array($acao, ['confirmar', 'cancelar'], true)) {
            set_flash('danger', 'Selecione uma reserva e a ação desejada.');
            $this->redirect('/?r=access/index&data=' . urlencode($dateRef));
        }

        $reservaModel = new ReservaTematicaModel();
        $logModel = new ReservaTematicaLogModel();
        $before = $reservaModel->find($reservaId);
        if (!$before) {
            set_flash('danger', 'Reserva não encontrada.');
            $this->redirect('/?r=access/index&data=' . urlencode($dateRef));
        }
        if ((int)($before['restaurante_id'] ?? 0) !== (int)$shift['restaurante_id']) {
            set_flash('danger', 'Reserva fora do restaurante do turno ativo.');
            $this->redirect('/?r=access/index&data=' . urlencode($dateRef));
        }
        if (($before['data_reserva'] ?? '') !== $dateRef) {
            set_flash('warning', 'Reserva fora da data selecionada.');
            $this->redirect('/?r=access/index&data=' . urlencode($dateRef));
        }

        $statusAtual = $this->normalizeReservaStatus((string)($before['status'] ?? ''));
        if (in_array($statusAtual, self::RESERVA_STATUS_FINAIS, true)) {
            set_flash('warning', 'Essa reserva já está em status definitivo.');
            $this->redirect('/?r=access/index&data=' . urlencode($dateRef));
        }

        $paxReservado = (int)($before['pax'] ?? 0);
        $paxReal = $paxReservado;
        $obs = normalize_mojibake(trim((string)($_POST['observacao_operacao'] ?? '')));
        $novoStatus = 'Finalizada';

        if ($acao === 'cancelar') {
            $novoStatus = 'Nao compareceu';
            $paxReal = 0;
            $prefixo = 'No-show manual em operação temática.';
            $obs = $obs !== '' ? ($prefixo . ' ' . $obs) : $prefixo;
        } else {
            $paxRealRaw = trim((string)($_POST['pax_real'] ?? ''));
            if ($paxRealRaw !== '') {
                if (!ctype_digit($paxRealRaw)) {
                    set_flash('warning', 'PAX real inválido.');
                    $this->redirect('/?r=access/index&data=' . urlencode($dateRef));
                }
                $paxReal = (int)$paxRealRaw;
                if ($paxReal < 0 || $paxReal > $paxReservado) {
                    set_flash('warning', 'PAX real deve estar entre 0 e ' . $paxReservado . '.');
                    $this->redirect('/?r=access/index&data=' . urlencode($dateRef));
                }
            }
            if ($paxReal < $paxReservado) {
                $prefixo = 'No-show parcial: reservado ' . $paxReservado . ', real ' . $paxReal . '.';
                $obs = $obs !== '' ? ($prefixo . ' ' . $obs) : $prefixo;
            }
        }

        $reservaModel->updateOperacao($reservaId, $novoStatus, $obs, (int)Auth::user()['id'], $paxReal);
        $after = $reservaModel->find($reservaId) ?? [];
        $logModel->log(
            $reservaId,
            'status',
            (int)Auth::user()['id'],
            $before,
            $after,
            $acao === 'cancelar' ? 'No-show manual em turno temático.' : 'Confirmação de entrada em turno temático.'
        );

        if ($acao === 'cancelar') {
            set_flash('warning', 'Reserva marcada como Não compareceu.');
        } else {
            set_flash('success', 'Reserva confirmada e finalizada.');
        }

        $url = '/?r=access/index&data=' . urlencode($dateRef);
        if ($query !== '') {
            $url .= '&q=' . urlencode($query);
        }
        if ($statusFilter !== '') {
            $url .= '&status=' . urlencode($statusFilter);
        }
        $this->redirect($url);
    }

    public function correct_last(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'hostess']);
        if ($this->redirectIfTematicoOnlyHostess(Auth::user())) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=access/index');
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=access/index');
        }

        $shiftModel = new ShiftModel();
        $shift = $shiftModel->getActiveByUser((int)Auth::user()['id']);
        if (!$shift) {
            set_flash('danger', 'Inicie um turno para corrigir lançamento.');
            $this->redirect('/?r=access/index');
        }

        $newPax = (int)($_POST['pax_corrigido'] ?? 0);
        if ($newPax <= 0) {
            set_flash('danger', 'Informe um valor de PAX válido.');
            $this->redirect('/?r=access/index');
        }

        $accessModel = new AccessModel();
        $last = $accessModel->findLastEditableByTurnoUser((int)$shift['id'], (int)Auth::user()['id'], 2);
        if (!$last) {
            set_flash('warning', 'Não há lançamento elegível para correção (janela de 2 minutos).');
            $this->redirect('/?r=access/index');
        }

        $uhNumero = (string)$last['uh_numero'];
        $unitModel = new UnitModel();
        $maxPax = $unitModel->maxPaxForNumero($uhNumero);
        if ($maxPax !== null && $newPax > $maxPax) {
            set_flash('danger', 'PAX corrigido excede o limite da UH. Máximo: ' . $maxPax . '.');
            $this->redirect('/?r=access/index');
        }

        $today = (new DateTime('now', new DateTimeZone(date_default_timezone_get())))->format('Y-m-d');
        $currentTotal = $accessModel->sumPaxByUhOperacaoDate($uhNumero, (int)$shift['operacao_id'], $today);
        $projectedTotal = $currentTotal - (int)$last['pax'] + $newPax;
        if ($maxPax !== null && $projectedTotal > $maxPax) {
            $resta = max(0, $maxPax - ($currentTotal - (int)$last['pax']));
            set_flash('danger', 'Correção inválida: excede limite diário da UH nesta operação. Restante permitido: ' . $resta . '.');
            $this->redirect('/?r=access/index');
        }

        $accessModel->updatePax((int)$last['id'], $newPax, (int)Auth::user()['id']);
        set_flash('success', '?ltimo lançamento corrigido com sucesso.');
        $this->redirect('/?r=access/index');
    }

    public function register_colaborador(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'hostess']);
        if ($this->redirectIfTematicoOnlyHostess(Auth::user())) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=access/index');
        }

        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=access/index');
        }

        $shiftModel = new ShiftModel();
        $shift = $shiftModel->getActiveByUser(Auth::user()['id']);
        if (!$shift) {
            set_flash('danger', 'Inicie um turno para registrar colaboradores.');
            $this->redirect('/?r=access/index');
        }

        if (($shift['restaurante'] ?? '') !== 'Restaurante Corais') {
            set_flash('danger', 'Registro de colaboradores disponível apenas no Restaurante Corais.');
            $this->redirect('/?r=access/index');
        }

        $nome = trim($_POST['nome_colaborador'] ?? '');
        $quantidade = (int)($_POST['quantidade'] ?? 0);
        if ($nome === '' || $quantidade <= 0) {
            set_flash('danger', 'Preencha o nome do colaborador e a quantidade de refeições.');
            $this->redirect('/?r=access/index');
        }

        $model = new CollaboratorMealModel();
        $model->create([
            'turno_id' => $shift['id'],
            'restaurante_id' => $shift['restaurante_id'],
            'operacao_id' => $shift['operacao_id'],
            'nome_colaborador' => $nome,
            'quantidade' => $quantidade,
        ], Auth::user()['id']);

        set_flash('success', 'Refeição de colaborador registrada.');
        $this->redirect('/?r=access/index');
    }

    public function register_voucher(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'hostess']);
        if ($this->redirectIfTematicoOnlyHostess(Auth::user())) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=access/index');
        }

        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=access/index');
        }

        $shiftModel = new ShiftModel();
        $shift = $shiftModel->getActiveByUser(Auth::user()['id']);

        $nomeHospede = trim(strip_tags((string)($_POST['nome_hospede'] ?? '')));
        $dataEstadia = trim((string)($_POST['data_estadia'] ?? ''));
        $numeroReserva = trim(strip_tags((string)($_POST['numero_reserva'] ?? '')));
        $servico = trim(strip_tags((string)($_POST['servico_upselling'] ?? '')));
        $assinatura = trim(strip_tags((string)($_POST['assinatura'] ?? '')));
        $dataVenda = trim((string)($_POST['data_venda'] ?? ''));

        if ($nomeHospede === '' || $dataEstadia === '' || $numeroReserva === '' || $servico === '' || $assinatura === '' || $dataVenda === '') {
            set_flash('danger', 'Preencha todos os campos do voucher.');
            $this->redirect('/?r=access/index');
        }

        $voucherPath = null;
        if (!empty($_FILES['voucher_anexo']) && $_FILES['voucher_anexo']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['voucher_anexo']['error'] !== UPLOAD_ERR_OK) {
                set_flash('danger', 'Falha ao enviar o anexo do voucher.');
                $this->redirect('/?r=access/index');
            }
            $file = $_FILES['voucher_anexo'];
            if (!is_uploaded_file($file['tmp_name'])) {
                set_flash('danger', 'Upload inválido.');
                $this->redirect('/?r=access/index');
            }
            if ($file['size'] > 5 * 1024 * 1024) {
                set_flash('danger', 'Anexo muito grande. Máximo 5MB.');
                $this->redirect('/?r=access/index');
            }
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
            if (!in_array($ext, $allowed, true)) {
                set_flash('danger', 'Formato inválido. Use JPG, PNG, WEBP ou PDF.');
                $this->redirect('/?r=access/index');
            }
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']) ?: '';
            $allowedMimeByExt = [
                'jpg' => ['image/jpeg'],
                'jpeg' => ['image/jpeg'],
                'png' => ['image/png'],
                'webp' => ['image/webp'],
                'pdf' => ['application/pdf'],
            ];
            if (!in_array($mime, $allowedMimeByExt[$ext] ?? [], true)) {
                set_flash('danger', 'Conteúdo de arquivo inválido para o formato enviado.');
                $this->redirect('/?r=access/index');
            }
            $uploadDir = __DIR__ . '/../../public/uploads/vouchers';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $filename = 'voucher_' . date('Ymd_His') . '_' . Auth::user()['id'] . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $dest = $uploadDir . '/' . $filename;
            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                set_flash('danger', 'Não foi possível salvar o anexo do voucher.');
                $this->redirect('/?r=access/index');
            }
            $voucherPath = '/uploads/vouchers/' . $filename;
        }

        $restauranteId = (int)($_POST['restaurante_id'] ?? ($shift['restaurante_id'] ?? 0));
        $operacaoId = (int)($_POST['operacao_id'] ?? ($shift['operacao_id'] ?? 0));
        $user = Auth::user();
        if (($user['perfil'] ?? '') === 'hostess') {
            $restauranteId = (int)($shift['restaurante_id'] ?? 0);
            $operacaoId = (int)($shift['operacao_id'] ?? 0);
        }
        if ($restauranteId <= 0 || $operacaoId <= 0) {
            set_flash('danger', 'Selecione restaurante e operação para o voucher.');
            $this->redirect('/?r=vouchers/index');
        }

        $model = new VoucherModel();
        $model->create([
            'turno_id' => $shift['id'] ?? null,
            'restaurante_id' => $restauranteId,
            'operacao_id' => $operacaoId,
            'nome_hospede' => $nomeHospede,
            'data_estadia' => $dataEstadia,
            'numero_reserva' => $numeroReserva,
            'servico_upselling' => $servico,
            'assinatura' => $assinatura,
            'data_venda' => $dataVenda,
            'voucher_anexo_path' => $voucherPath,
        ], Auth::user()['id']);

        set_flash('success', 'Voucher registrado com sucesso.');
        $this->redirect('/?r=vouchers/index');
    }

    private function normalizeReservaStatus(string $status): string
    {
        $status = trim(normalize_mojibake($status));
        $map = [
            'Nao compareceu' => 'Nao compareceu',
            'Não compareceu' => 'Nao compareceu',
            'Não compareceu' => 'Nao compareceu',
            'Não compareceu' => 'Nao compareceu',
            'Divergencia' => 'Divergencia',
            'Divergência' => 'Divergencia',
            'Divergência' => 'Divergencia',
            'Divergência' => 'Divergencia',
            'Conferida' => 'Reservada',
            'Em atendimento' => 'Reservada',
        ];
        return $map[$status] ?? $status;
    }

    private function applyTematicaAutoNoShow(int $restauranteId, string $dateRef, int $userId): int
    {
        if ($restauranteId <= 0 || $userId <= 0) {
            return 0;
        }

        $reservaModel = new ReservaTematicaModel();
        $logModel = new ReservaTematicaLogModel();
        $agora = date('Y-m-d H:i:s');
        $candidatas = $reservaModel->findAutoNoShowCandidates($agora, $dateRef, $restauranteId);
        $processed = 0;

        foreach ($candidatas as $cand) {
            $id = (int)($cand['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $before = $reservaModel->find($id);
            if (!$before) {
                continue;
            }
            $statusAtual = $this->normalizeReservaStatus((string)($before['status'] ?? ''));
            if ($statusAtual !== 'Reservada') {
                continue;
            }

            $obsAtual = trim((string)($before['observacao_operacao'] ?? ''));
            $obsAuto = 'No-show automático por expiração da tolerância da reserva.';
            if ($obsAtual !== '') {
                $obsAuto .= ' ' . $obsAtual;
            }

            $reservaModel->updateOperacao($id, 'Nao compareceu', $obsAuto, $userId, 0);
            $after = $reservaModel->find($id) ?? [];
            $logModel->log($id, 'auto_no_show', $userId, $before, $after, 'Aplicado automaticamente pela configuração de tolerância.');
            $processed++;
        }

        return $processed;
    }

    private function canCancelTematicoShift(array $shift, int $userId): bool
    {
        $startedAt = (string)($shift['inicio_em'] ?? '');
        if ($startedAt === '' || $userId <= 0) {
            return true;
        }
        $manualUpdates = (new ReservaTematicaLogModel())->countManualByUserSince($userId, $startedAt);
        return $manualUpdates === 0;
    }

    private function isOutsideHorario(array $restOp): bool
    {
        $tz = new DateTimeZone(date_default_timezone_get());
        $now = new DateTime('now', $tz);
        $start = DateTime::createFromFormat('H:i:s', $restOp['hora_inicio'], $tz);
        $end = DateTime::createFromFormat('H:i:s', $restOp['hora_fim'], $tz);
        if (!$start) {
            return false;
        }
        $start->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));
        if (!$end) {
            return $now < $start;
        }
        $end->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));
        if ($end < $start) {
            $end->modify('+1 day');
        }
        return $now < $start || $now > $end;
    }

    private function isNoShowStatus(string $status): bool
    {
        $normalized = mb_strtolower(normalize_mojibake(trim($status)), 'UTF-8');
        return in_array($normalized, ['não compareceu', 'nao compareceu'], true);
    }
}



