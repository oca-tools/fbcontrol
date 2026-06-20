<?php
declare(strict_types=1);

class AccessController extends Controller
{
    private const RESERVA_STATUS_FINAIS = ['Finalizada', 'Nao compareceu', 'Cancelada'];
    private const TEMATICA_CONFLICT_KEY = 'access_tematica_conflict';
    private const DUPLICATE_CONFIRM_KEY = 'access_duplicate_confirm';

    /**
     * Mantem hostess tematica no fluxo de registro padrao.
     *
     * @param array|null $user Usuario autenticado, quando disponivel.
     * @return bool Sempre falso no fluxo atual.
     */
    private function redirectIfTematicoOnlyHostess(?array $user = null): bool
    {
        // Fluxo v2: hostess de tematico tambem opera pelo modulo Registro.
        return false;
    }

    private function saveTematicaConflict(array $payload): void
    {
        $_SESSION[self::TEMATICA_CONFLICT_KEY] = $payload;
    }

    private function pullTematicaConflict(): ?array
    {
        $payload = $_SESSION[self::TEMATICA_CONFLICT_KEY] ?? null;
        unset($_SESSION[self::TEMATICA_CONFLICT_KEY]);
        return is_array($payload) ? $payload : null;
    }

    private function clearTematicaConflict(): void
    {
        unset($_SESSION[self::TEMATICA_CONFLICT_KEY]);
    }

    private function saveDuplicateConfirm(array $payload): void
    {
        $_SESSION[self::DUPLICATE_CONFIRM_KEY] = $payload;
    }

    private function pullDuplicateConfirm(): ?array
    {
        $payload = $_SESSION[self::DUPLICATE_CONFIRM_KEY] ?? null;
        unset($_SESSION[self::DUPLICATE_CONFIRM_KEY]);
        return is_array($payload) ? $payload : null;
    }

    private function clearDuplicateConfirm(): void
    {
        unset($_SESSION[self::DUPLICATE_CONFIRM_KEY]);
    }

    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'hostess', 'gerente']);
        $user = Auth::user();
        if ($this->redirectIfTematicoOnlyHostess($user)) {
            return;
        }

        $closedByTimeout = (new ShiftAutoCloseService())->closeForCurrentUser();
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

        if (TematicAccessService::isTematicShift($shift)) {
            $dateRef = trim((string)($_GET['data'] ?? date('Y-m-d')));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateRef)) {
                $dateRef = date('Y-m-d');
            }
            $search = normalize_mojibake(trim((string)($_GET['q'] ?? '')));
            $status = normalize_mojibake(trim((string)($_GET['status'] ?? '')));

            $reservaModel = new ReservaTematicaModel();
            $reservas = $reservaModel->listByFilters([
                'data' => $dateRef,
                'restaurante_id' => (int)$shift['restaurante_id'],
                'turno_id' => '',
                'uh_numero' => '',
                'titular' => $search,
                'status' => $status,
                'order' => 'turno',
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
        $lastEditableAccesses = $accessModel->findEditableByTurnoUser((int)$shift['id'], (int)Auth::user()['id'], 2, 2);
        $lastEditableAccess = $lastEditableAccesses[0] ?? null;

        $this->view('access/index', [
            'mode' => 'register',
            'turno' => $shift,
            'restOp' => $restOp,
            'tolerance_alert' => $toleranceAlert,
            'recentes' => $accessModel->listRecent(10),
            'flash' => get_flash(),
            'tematica_conflict' => $this->pullTematicaConflict(),
            'duplicate_confirm' => $this->pullDuplicateConfirm(),
            'is_corais' => ($shift['restaurante'] ?? '') === 'Restaurante Corais',
            'is_corais_jantar' => (($shift['restaurante'] ?? '') === 'Restaurante Corais') && (stripos($shift['operacao'] ?? '', 'Jantar') !== false),
            'can_cancel' => $canCancel,
            'last_editable_access' => $lastEditableAccess,
            'last_editable_accesses' => $lastEditableAccesses,
            'allow_hostess_tutorial' => $allowHostessTutorial,
            'show_hostess_tutorial' => $showHostessTutorial,
        ]);
    }

    public function start(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'hostess', 'gerente']);
        (new ShiftAutoCloseService())->closeForCurrentUser();
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

        $resultado = (new AbrirTurnoService())->executar(new AbrirTurnoCommand([
            'usuario_id' => (int)$user['id'],
            'perfil_usuario' => (string)($user['perfil'] ?? ''),
            'restaurante_id' => $restauranteId,
            'operacao_id' => $operacaoId,
            'porta_id' => $portaId,
            'confirmou_checklist' => (int)($_POST['confirm_start'] ?? 0) === 1,
            'confirmou_fora_horario' => (int)($_POST['confirm_early'] ?? 0) === 1,
            'modo_demo' => app_demo_mode_enabled(),
            'restaurantes_permitidos' => $restaurantes,
            'operacoes_permitidas_por_restaurante' => $allowedOpsByRest,
            'portas_por_restaurante' => $doorsByRestaurant,
            'restringir_la_brasa_ao_almoco' => false,
        ]));

        if (!$resultado->isSuccess()) {
            $tipoFlash = in_array($resultado->code(), ['confirmar_fora_horario', 'confirmar_checklist'], true) ? 'warning' : 'danger';
            set_flash($tipoFlash, $resultado->message());
            if (in_array($resultado->code(), ['confirmar_fora_horario', 'confirmar_checklist'], true)) {
                $this->view('access/index', [
                    'mode' => 'start',
                    'restaurantes' => $restaurantes,
                    'restOps' => $restOps,
                    'doorsByRestaurant' => $doorsByRestaurant,
                    'flash' => get_flash(),
                    'need_confirm' => $resultado->code() === 'confirmar_fora_horario',
                    'preselect' => [
                        'restaurante_id' => $restauranteId,
                        'operacao_id' => $operacaoId,
                        'porta_id' => $portaId,
                    ],
                ]);
                return;
            }
            $this->redirect('/?r=access/index');
        }

        $this->redirect('/?r=access/index');
    }

    public function register(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'hostess', 'gerente']);
        (new ShiftAutoCloseService())->closeForCurrentUser();
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
        $turno = (new ShiftRepository())->turnoAtivoDoUsuario((int)$user['id']);
        $resultado = (new RegistrarAcessoService())->executar(new RegistrarAcessoCommand([
            'turno' => $turno ?? [],
            'usuario_id' => (int)$user['id'],
            'uh_numero' => $_POST['uh_numero'] ?? '',
            'pax' => $_POST['pax'] ?? 0,
            'confirmou_duplicidade' => (int)($_POST['confirm_duplicate'] ?? 0) === 1,
            'reserva_tematica_ja_processada' => (int)($_POST['tematica_processed'] ?? 0) === 1,
            'acao_reserva_tematica' => $_POST['tematica_action'] ?? '',
            'reserva_tematica_id' => $_POST['tematica_reserva_id'] ?? 0,
            'pax_real_tematico' => $_POST['tematica_pax_real'] ?? -1,
            'confirmou_no_show_tematico' => (int)($_POST['confirm_no_show'] ?? 0) === 1,
        ]));

        $this->aplicarResultadoRegistroSalao($resultado);

        $this->redirect('/?r=access/index');
    }

    private function aplicarResultadoRegistroSalao(ServiceResult $resultado): void
    {
        if ($resultado->code() === 'conflito_reserva_tematica') {
            $this->clearDuplicateConfirm();
            $this->saveTematicaConflict($resultado->payload());
            set_flash('warning', $resultado->message());
            return;
        }

        if ($resultado->code() === 'confirmar_duplicidade') {
            $this->saveDuplicateConfirm($resultado->payload());
            set_flash('warning', $resultado->message());
            return;
        }

        if ($resultado->code() === 'confirmar_no_show_tematico') {
            set_flash('warning', $resultado->message());
            return;
        }

        if ($resultado->isSuccess()) {
            $this->clearDuplicateConfirm();
            $this->clearTematicaConflict();
            $tipoFlash = in_array($resultado->code(), [
                'registro_no_show_tematico',
                'registro_duplicado_confirmado',
                'alerta_duplicidade',
                'fora_horario',
            ], true) ? 'warning' : 'success';
            set_flash($tipoFlash, $resultado->message());
            return;
        }

        $tipoFlash = in_array($resultado->code(), [
            'reserva_tematica_mudou',
            'pax_real_tematico_invalido',
            'sem_pax_para_buffet',
            'pax_buffet_excede_tematico',
        ], true) ? 'warning' : 'danger';
        set_flash($tipoFlash, $resultado->message());
    }

    public function register_tematica(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'hostess', 'gerente']);

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
        if (!TematicAccessService::isTematicShift($shift)) {
            set_flash('danger', 'Este turno não é temático.');
            $this->redirect('/?r=access/index');
        }

        $reservaId = (int)($_POST['reserva_id'] ?? 0);
        $acao = normalize_mojibake(trim((string)($_POST['acao_tematica'] ?? '')));
        $dateRef = trim((string)($_POST['data_ref'] ?? date('Y-m-d')));

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

        $url = '/?r=access/index&data=' . urlencode($dateRef) . '&updated=' . $reservaId . '&_ts=' . time();
        $this->redirect($url);
    }

    public function correct_last(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'hostess', 'gerente']);
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

        $accessId = (int)($_POST['access_id'] ?? 0);
        $newPax = (int)($_POST['pax_corrigido'] ?? 0);
        $newUhNumero = trim((string)($_POST['uh_corrigida'] ?? ''));
        if ($newPax <= 0) {
            set_flash('danger', 'Informe um valor de PAX válido.');
            $this->redirect('/?r=access/index');
        }
        if ($newUhNumero === '') {
            set_flash('danger', 'Informe a UH corrigida.');
            $this->redirect('/?r=access/index');
        }

        $accessModel = new AccessModel();
        $editable = $accessModel->findEditableByTurnoUser((int)$shift['id'], (int)Auth::user()['id'], 2, 2);
        if (empty($editable)) {
            set_flash('warning', 'Não há lançamento elegível para correção (janela de 2 minutos).');
            $this->redirect('/?r=access/index');
        }

        $last = null;
        foreach ($editable as $candidate) {
            if ((int)$candidate['id'] === $accessId) {
                $last = $candidate;
                break;
            }
        }
        if (!$last) {
            set_flash('danger', 'Lançamento fora da janela de correção.');
            $this->redirect('/?r=access/index');
        }

        $unitModel = new UnitModel();
        $newUh = $unitModel->findByNumero($newUhNumero);
        if (!$newUh) {
            set_flash('danger', 'UH corrigida inválida ou inexistente.');
            $this->redirect('/?r=access/index');
        }

        $newUhNumero = (string)$newUh['numero'];
        $maxPax = $unitModel->maxPaxForNumero($newUhNumero);
        if ($maxPax !== null && $newPax > $maxPax) {
            set_flash('danger', 'PAX corrigido excede o limite da UH. Máximo: ' . $maxPax . '.');
            $this->redirect('/?r=access/index');
        }

        $today = (new DateTime('now', new DateTimeZone(date_default_timezone_get())))->format('Y-m-d');
        $currentTotal = $accessModel->sumPaxByUhOperacaoDate($newUhNumero, (int)$shift['operacao_id'], $today);
        $sameUh = (string)$last['uh_numero'] === $newUhNumero;
        $projectedTotal = $currentTotal - ($sameUh ? (int)$last['pax'] : 0) + $newPax;
        if ($maxPax !== null && $projectedTotal > $maxPax) {
            $resta = max(0, $maxPax - ($currentTotal - ($sameUh ? (int)$last['pax'] : 0)));
            set_flash('danger', 'Correção inválida: excede limite diário da UH nesta operação. Restante permitido: ' . $resta . '.');
            $this->redirect('/?r=access/index');
        }

        $accessModel->updatePaxUh((int)$last['id'], (int)$newUh['id'], $newPax, (int)Auth::user()['id']);
        set_flash('success', 'Lançamento corrigido com sucesso.');
        $this->redirect('/?r=access/index');
    }

    public function register_colaborador(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'hostess', 'gerente']);
        if ($this->redirectIfTematicoOnlyHostess(Auth::user())) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(ConsumosEVouchersConstants::ROUTE_ACCESS_INDEX);
        }

        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash(ConsumosEVouchersConstants::FLASH_DANGER, ConsumosEVouchersConstants::MESSAGE_TOKEN_INVALIDO);
            $this->redirect(ConsumosEVouchersConstants::ROUTE_ACCESS_INDEX);
        }

        $usuario = Auth::user();
        $turno = (new ShiftRepository())->turnoAtivoDoUsuario((int)$usuario['id']);
        $resultado = (new RegistrarRefeicaoColaboradorService())->executar(new RegistrarRefeicaoColaboradorCommand([
            'usuario' => $usuario,
            'turno' => $turno ?? [],
            'nome_colaborador' => $_POST['nome_colaborador'] ?? '',
            'quantidade' => $_POST['quantidade'] ?? 0,
        ]));

        $tipoFlash = $resultado->isSuccess()
            ? ConsumosEVouchersConstants::FLASH_SUCCESS
            : ($resultado->code() === ConsumosEVouchersConstants::CODE_COLABORADOR_MULTIPLO
                ? ConsumosEVouchersConstants::FLASH_WARNING
                : ConsumosEVouchersConstants::FLASH_DANGER);
        set_flash($tipoFlash, $resultado->message());
        $this->redirect(ConsumosEVouchersConstants::ROUTE_ACCESS_INDEX);
    }

    public function register_voucher(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'hostess', 'gerente']);
        $voucherRoute = ConsumosEVouchersConstants::ROUTE_VOUCHERS_INDEX;
        if ($this->redirectIfTematicoOnlyHostess(Auth::user())) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect($voucherRoute);
        }

        if ($_POST === [] && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            $resultado = (new RegistrarVoucherService())->executar(new RegistrarVoucherCommand([
                'usuario' => Auth::user(),
                'turno' => [],
                'post' => $_POST,
                'files' => $_FILES,
                'server' => $_SERVER,
            ]));
            set_flash(ConsumosEVouchersConstants::FLASH_DANGER, $resultado->message());
            $this->redirect($voucherRoute);
        }

        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash(ConsumosEVouchersConstants::FLASH_DANGER, ConsumosEVouchersConstants::MESSAGE_TOKEN_INVALIDO);
            $this->redirect($voucherRoute);
        }

        $usuario = Auth::user();
        $turno = (new ShiftRepository())->turnoAtivoDoUsuario((int)$usuario['id']);
        $resultado = (new RegistrarVoucherService())->executar(new RegistrarVoucherCommand([
            'usuario' => $usuario,
            'turno' => $turno ?? [],
            'post' => $_POST,
            'files' => $_FILES,
            'server' => $_SERVER,
        ]));

        set_flash(
            $resultado->isSuccess() ? ConsumosEVouchersConstants::FLASH_SUCCESS : ConsumosEVouchersConstants::FLASH_DANGER,
            $resultado->message()
        );
        $this->redirect($voucherRoute);
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
