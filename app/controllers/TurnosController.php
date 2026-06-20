<?php
class TurnosController extends Controller
{
    public function start(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'supervisor', 'hostess', 'gerente']);
        (new ShiftAutoCloseService())->closeForCurrentUser();

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
                'restringir_la_brasa_ao_almoco' => true,
            ]));

            if (!$resultado->isSuccess()) {
                $tipoFlash = in_array($resultado->code(), ['confirmar_fora_horario', 'confirmar_checklist'], true) ? 'warning' : 'danger';
                set_flash($tipoFlash, $resultado->message());
                if (in_array($resultado->code(), ['confirmar_fora_horario', 'confirmar_checklist'], true)) {
                    $this->view('turnos/start', [
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
                $this->redirect('/?r=turnos/start');
            }

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
        Auth::requireRole(['admin', 'supervisor', 'hostess', 'gerente']);
        (new ShiftAutoCloseService())->closeForCurrentUser();

        $user = Auth::user();
        $shift = (new ShiftRepository())->turnoAtivoDoUsuario((int)$user['id']);
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

        $resultado = (new EncerrarTurnoService())->executar(new EncerrarTurnoCommand([
            'turno' => $shift,
            'usuario_id' => (int)$user['id'],
            'modo_demo' => app_demo_mode_enabled(),
            'cancelamento' => false,
        ]));

        if (!$resultado->isSuccess()) {
            set_flash('warning', $resultado->message());
            $this->redirect('/?r=access/index');
        }

        $isTematico = TematicAccessService::isTematicShift($shift);
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
            'summary' => $resultado->payload()['summary'] ?? [],
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
        Auth::requireRole(['admin', 'supervisor', 'hostess', 'gerente']);
        (new ShiftAutoCloseService())->closeForCurrentUser();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=access/index');
        }

        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=access/index');
        }

        $user = Auth::user();
        $shift = (new ShiftRepository())->turnoAtivoDoUsuario((int)$user['id']);
        if (!$shift) {
            $this->redirect('/?r=access/index');
        }

        $resultado = (new EncerrarTurnoService())->executar(new EncerrarTurnoCommand([
            'turno' => $shift,
            'usuario_id' => (int)$user['id'],
            'modo_demo' => app_demo_mode_enabled(),
            'cancelamento' => true,
        ]));

        set_flash($resultado->isSuccess() ? 'success' : 'warning', $resultado->message());
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
