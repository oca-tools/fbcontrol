<?php
class TurnosController extends Controller
{
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

    private function autoCloseTimeoutShiftsForCurrentUser(): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }
        $graceMinutes = 10;
        (new ShiftModel())->autoCloseExpired($graceMinutes, (int)$user['id']);
        (new SpecialShiftModel())->autoCloseExpired($graceMinutes, (int)$user['id']);
    }

    public function start(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'hostess']);
        $this->autoCloseTimeoutShiftsForCurrentUser();

        $shiftModel = new ShiftModel();
        $active = $shiftModel->getActiveByUser(Auth::user()['id']);
        if ($active) {
            $this->redirect('/?r=access/index');
        }

        $user = Auth::user();
        $restaurantModel = new RestaurantModel();
        $opModel = new RestaurantOperationModel();
        $doorModel = new DoorModel();
        $userRestaurantModel = new UserRestaurantModel();

        $restaurantes = $user['perfil'] === 'hostess'
            ? $userRestaurantModel->byUser($user['id'])
            : $restaurantModel->all();

        $restOps = [];
        foreach ($restaurantes as $rest) {
            $ops = $opModel->byRestaurant((int)$rest['id']);
            if (stripos($rest['nome'], 'La Brasa') !== false) {
                $ops = array_filter($ops, static function ($op) {
                    $name = mb_strtolower((string)($op['operacao'] ?? ''), 'UTF-8');
                    return strpos($name, 'almoço') !== false || strpos($name, 'almoco') !== false;
                });
            }
            $restOps[$rest['id']] = array_values($ops);
        }

        $allowedOpsByRest = [];
        if (($user['perfil'] ?? '') === 'hostess') {
            $allowedOpsByRest = (new UserRestaurantOperationModel())->operationsByUser((int)$user['id']);
        }

        $doorsByRestaurant = [];
        foreach ($restaurantes as $rest) {
            $doorsByRestaurant[$rest['id']] = $doorModel->byRestaurant((int)$rest['id']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_validate($_POST['csrf_token'] ?? '')) {
                set_flash('danger', 'Token inválido.');
                $this->redirect('/?r=turnos/start');
            }

            $restauranteId = (int)($_POST['restaurante_id'] ?? 0);
            $operacaoId = (int)($_POST['operacao_id'] ?? 0);
            $portaId = (int)($_POST['porta_id'] ?? 0);
            $confirmStart = (int)($_POST['confirm_start'] ?? 0) === 1;
            $confirmEarly = (int)($_POST['confirm_early'] ?? 0) === 1;

            if ($restauranteId <= 0 || $operacaoId <= 0) {
                set_flash('danger', 'Selecione restaurante e operação.');
                $this->redirect('/?r=turnos/start');
            }

            if (($user['perfil'] ?? '') === 'hostess') {
                $allowedRestaurantIds = array_values(array_unique(array_map(static fn($r) => (int)($r['id'] ?? 0), $restaurantes)));
                if (!in_array($restauranteId, $allowedRestaurantIds, true)) {
                    set_flash('danger', 'Restaurante não autorizado para este usuário.');
                    $this->redirect('/?r=turnos/start');
                }

                $allowedOps = array_values(array_unique(array_map('intval', $allowedOpsByRest[$restauranteId] ?? [])));
                if (!empty($allowedOps) && !in_array($operacaoId, $allowedOps, true)) {
                    set_flash('danger', 'Operação não autorizada para este usuário.');
                    $this->redirect('/?r=turnos/start');
                }
            }

            if ($portaId > 0) {
                $doorIds = array_values(array_unique(array_map(static fn($d) => (int)($d['id'] ?? 0), $doorsByRestaurant[$restauranteId] ?? [])));
                if (!in_array($portaId, $doorIds, true)) {
                    set_flash('danger', 'Porta inválida para o restaurante selecionado.');
                    $this->redirect('/?r=turnos/start');
                }
            }

            $restOp = $opModel->findByRestaurantOperation($restauranteId, $operacaoId);
            if (!$restOp) {
                set_flash('danger', 'Operação inválida para este restaurante.');
                $this->redirect('/?r=turnos/start');
            }
            $outsideHorario = $this->isOutsideHorario($restOp);

            $rest = $restaurantModel->find($restauranteId);
            $opInfo = (new OperationModel())->find($operacaoId);
            if ($rest && stripos((string)$rest['nome'], 'La Brasa') !== false && $opInfo) {
                $opName = mb_strtolower((string)($opInfo['nome'] ?? ''), 'UTF-8');
                if (strpos($opName, 'almoço') === false && strpos($opName, 'almoco') === false) {
                    set_flash('danger', 'No La Brasa o registro é permitido apenas para almoço.');
                    $this->redirect('/?r=turnos/start');
                }
            }
            if ($rest && (int)$rest['seleciona_porta_no_turno'] === 1 && $portaId <= 0) {
                set_flash('danger', 'Selecione a porta.');
                $this->redirect('/?r=turnos/start');
            }

            if ($outsideHorario && !$confirmEarly) {
                if (!isset($_SESSION['flash'])) {
                    set_flash('warning', 'Turno fora do horário. Confirme se deseja continuar.');
                }
                $this->view('turnos/start', [
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
                $this->view('turnos/start', [
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

        $this->view('turnos/start', [
            'restaurantes' => $restaurantes,
            'restOps' => $restOps,
            'doorsByRestaurant' => $doorsByRestaurant,
            'flash' => get_flash(),
            'need_confirm' => false,
            'preselect' => [],
        ]);
    }

    public function end(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'hostess']);
        $this->autoCloseTimeoutShiftsForCurrentUser();

        $shiftModel = new ShiftModel();
        $shift = $shiftModel->getActiveByUser(Auth::user()['id']);
        if (!$shift) {
            $this->redirect('/?r=turnos/start');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=access/index');
        }

        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=access/index');
        }

        $restOpModel = new RestaurantOperationModel();
        $restOp = $restOpModel->findByRestaurantOperation((int)$shift['restaurante_id'], (int)$shift['operacao_id']);
        if ($restOp) {
            $now = new DateTime('now', new DateTimeZone(date_default_timezone_get()));
            $end = DateTime::createFromFormat('H:i:s', $restOp['hora_fim'], new DateTimeZone(date_default_timezone_get()));
            if ($end) {
                $end->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));
                if ($now < $end) {
                    $diffMin = (int)ceil(($end->getTimestamp() - $now->getTimestamp()) / 60);
                    $tol = (int)$restOp['tolerancia_min'];
                    $msg = 'Ainda não é possível encerrar o turno. Faltam ' . $diffMin . ' min para o fim.';
                    if ($tol > 0) {
                        $msg .= ' Tolerância: ' . $tol . ' min antes do fim.';
                    }
                    set_flash('warning', $msg);
                    $this->redirect('/?r=access/index');
                }
            }
        }

        $shiftModel->end((int)$shift['id'], Auth::user()['id']);
        $summary = $shiftModel->summary((int)$shift['id']);
        $isTematico = $this->isTematicoShift($shift);
        $tematicaSummary = null;
        if ($isTematico) {
            $dataRef = date('Y-m-d', strtotime((string)($shift['inicio_em'] ?? 'now')));
            $tematicaSummary = (new ReservaTematicaModel())->summary([
                'data' => $dataRef,
                'restaurante_id' => (int)($shift['restaurante_id'] ?? 0),
            ]);
        }

        $this->view('turnos/summary', [
            'turno' => $shift,
            'summary' => $summary,
            'is_tematica' => $isTematico,
            'tematica_summary' => $tematicaSummary,
        ]);
    }

    public function especial_end(): void
    {
        $this->requireAuth();
        set_flash('info', 'Os turnos especiais foram unificados ao registro padrão.');
        $this->redirect('/?r=access/index');
    }

    public function cancel(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'hostess']);
        $this->autoCloseTimeoutShiftsForCurrentUser();

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
            $this->redirect('/?r=access/index');
        }

        if ($this->isTematicoShift($shift)) {
            $manual = (new ReservaTematicaLogModel())->countManualByUserSince((int)Auth::user()['id'], (string)($shift['inicio_em'] ?? ''));
            if ($manual > 0) {
                set_flash('warning', 'Não é possível cancelar o turno após confirmar reservas temáticas.');
                $this->redirect('/?r=access/index');
            }

            $shiftModel->end((int)$shift['id'], Auth::user()['id']);
            set_flash('success', 'Turno cancelado com sucesso.');
            $this->redirect('/?r=access/index');
        }

        $accessModel = new AccessModel();
        $count = $accessModel->countByTurno((int)$shift['id']);
        if ($count > 0) {
            set_flash('warning', 'Não é possível cancelar o turno após registrar acessos.');
            $this->redirect('/?r=access/index');
        }

        $shiftModel->end((int)$shift['id'], Auth::user()['id']);
        set_flash('success', 'Turno cancelado com sucesso.');
        $this->redirect('/?r=access/index');
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
}
