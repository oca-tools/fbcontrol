<?php
declare(strict_types=1);

class HorariosController extends Controller
{
    /**
     * Exibe a grade de horarios em que cada restaurante atende suas operacoes.
     */
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole([GestaoRestaurantesConstants::ROLE_ADMIN]);

        $estruturaRestauranteRepository = new EstruturaRestauranteRepository();

        $this->view('crud/horarios', [
            'items' => $estruturaRestauranteRepository->listarHorariosDaOperacao(),
            'restaurantes' => $estruturaRestauranteRepository->listarRestaurantes(),
            'operacoes' => $estruturaRestauranteRepository->listarOperacoes(),
            'flash' => get_flash(),
        ]);
    }

    /**
     * Define os dias/horarios em que uma operacao de restaurante estara ativa para receber hospedes.
     */
    public function create(): void
    {
        $this->requireAuth();
        Auth::requireRole([GestaoRestaurantesConstants::ROLE_ADMIN]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(GestaoRestaurantesConstants::ROUTE_HORARIOS_INDEX);
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash(GestaoRestaurantesConstants::FLASH_DANGER, GestaoRestaurantesConstants::MESSAGE_TOKEN_INVALID);
            $this->redirect(GestaoRestaurantesConstants::ROUTE_HORARIOS_INDEX);
        }

        $resultado = (new ConfigurarHorarioService())->executar(new ConfigurarHorarioCommand([
            'acao' => GestaoRestaurantesConstants::ACTION_CREATE,
            'usuario_id' => Auth::user()['id'] ?? 0,
            'restaurante_id' => $_POST['restaurante_id'] ?? 0,
            'operacao_id' => $_POST['operacao_id'] ?? 0,
            'hora_inicio' => $_POST['hora_inicio'] ?? '',
            'hora_fim' => $_POST['hora_fim'] ?? '',
            'tolerancia_min' => $_POST['tolerancia_min'] ?? GestaoRestaurantesConstants::DEFAULT_TOLERANCE_MINUTES,
            'ativo' => GestaoRestaurantesConstants::STATUS_ACTIVE,
        ]));

        $this->aplicarResultadoGestaoRestaurante($resultado);
        $this->redirect(GestaoRestaurantesConstants::ROUTE_HORARIOS_INDEX);
    }

    /**
     * Atualiza horario, tolerancia e status de uma operacao de restaurante.
     */
    public function edit(): void
    {
        $this->requireAuth();
        Auth::requireRole([GestaoRestaurantesConstants::ROLE_ADMIN]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(GestaoRestaurantesConstants::ROUTE_HORARIOS_INDEX);
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash(GestaoRestaurantesConstants::FLASH_DANGER, GestaoRestaurantesConstants::MESSAGE_TOKEN_INVALID);
            $this->redirect(GestaoRestaurantesConstants::ROUTE_HORARIOS_INDEX);
        }

        $resultado = (new ConfigurarHorarioService())->executar(new ConfigurarHorarioCommand([
            'acao' => GestaoRestaurantesConstants::ACTION_EDIT,
            'id' => $_POST['id'] ?? 0,
            'usuario_id' => Auth::user()['id'] ?? 0,
            'restaurante_id' => $_POST['restaurante_id'] ?? 0,
            'operacao_id' => $_POST['operacao_id'] ?? 0,
            'hora_inicio' => $_POST['hora_inicio'] ?? '',
            'hora_fim' => $_POST['hora_fim'] ?? '',
            'tolerancia_min' => $_POST['tolerancia_min'] ?? GestaoRestaurantesConstants::DEFAULT_TOLERANCE_MINUTES,
            'ativo' => $_POST['ativo'] ?? GestaoRestaurantesConstants::STATUS_ACTIVE,
        ]));

        $this->aplicarResultadoGestaoRestaurante($resultado);
        $this->redirect(GestaoRestaurantesConstants::ROUTE_HORARIOS_INDEX);
    }

    private function aplicarResultadoGestaoRestaurante(ServiceResult $resultado): void
    {
        set_flash(
            $resultado->isSuccess() ? GestaoRestaurantesConstants::FLASH_SUCCESS : GestaoRestaurantesConstants::FLASH_DANGER,
            $resultado->message()
        );
    }
}
