<?php
declare(strict_types=1);

class RestaurantesController extends Controller
{
    /**
     * Exibe os restaurantes configurados para as operacoes de A&B.
     */
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole([GestaoRestaurantesConstants::ROLE_ADMIN]);

        $estruturaRestauranteRepository = new EstruturaRestauranteRepository();
        $this->view('crud/restaurantes', [
            'items' => $estruturaRestauranteRepository->listarRestaurantes(),
            'flash' => get_flash(),
        ]);
    }

    /**
     * Cadastra um restaurante operacional para receber configuracoes de turno e acesso.
     */
    public function create(): void
    {
        $this->requireAuth();
        Auth::requireRole([GestaoRestaurantesConstants::ROLE_ADMIN]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(GestaoRestaurantesConstants::ROUTE_RESTAURANTES_INDEX);
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash(GestaoRestaurantesConstants::FLASH_DANGER, GestaoRestaurantesConstants::MESSAGE_TOKEN_INVALID);
            $this->redirect(GestaoRestaurantesConstants::ROUTE_RESTAURANTES_INDEX);
        }

        $resultado = (new ConfigurarRestauranteService())->executar(new ConfigurarRestauranteCommand([
            'tipo_cadastro' => GestaoRestaurantesConstants::REGISTER_TYPE_RESTAURANTE,
            'acao' => GestaoRestaurantesConstants::ACTION_CREATE,
            'usuario_id' => Auth::user()['id'] ?? 0,
            'nome' => $_POST['nome'] ?? '',
            'tipo' => $_POST['tipo'] ?? GestaoRestaurantesConstants::RESTAURANT_TYPE_BUFFET,
            'seleciona_porta_no_turno' => $_POST['seleciona_porta_no_turno'] ?? 0,
            'exige_pax' => $_POST['exige_pax'] ?? 0,
            'ativo' => GestaoRestaurantesConstants::STATUS_ACTIVE,
        ]));

        $this->aplicarResultadoGestaoRestaurante($resultado);
        $this->redirect(GestaoRestaurantesConstants::ROUTE_RESTAURANTES_INDEX);
    }

    /**
     * Atualiza status e regras operacionais de um restaurante de A&B.
     */
    public function edit(): void
    {
        $this->requireAuth();
        Auth::requireRole([GestaoRestaurantesConstants::ROLE_ADMIN]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(GestaoRestaurantesConstants::ROUTE_RESTAURANTES_INDEX);
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash(GestaoRestaurantesConstants::FLASH_DANGER, GestaoRestaurantesConstants::MESSAGE_TOKEN_INVALID);
            $this->redirect(GestaoRestaurantesConstants::ROUTE_RESTAURANTES_INDEX);
        }

        $resultado = (new ConfigurarRestauranteService())->executar(new ConfigurarRestauranteCommand([
            'tipo_cadastro' => GestaoRestaurantesConstants::REGISTER_TYPE_RESTAURANTE,
            'acao' => GestaoRestaurantesConstants::ACTION_EDIT,
            'id' => $_POST['id'] ?? 0,
            'usuario_id' => Auth::user()['id'] ?? 0,
            'nome' => $_POST['nome'] ?? '',
            'tipo' => $_POST['tipo'] ?? GestaoRestaurantesConstants::RESTAURANT_TYPE_BUFFET,
            'seleciona_porta_no_turno' => $_POST['seleciona_porta_no_turno'] ?? 0,
            'exige_pax' => $_POST['exige_pax'] ?? 0,
            'ativo' => $_POST['ativo'] ?? GestaoRestaurantesConstants::STATUS_ACTIVE,
        ]));

        $this->aplicarResultadoGestaoRestaurante($resultado);
        $this->redirect(GestaoRestaurantesConstants::ROUTE_RESTAURANTES_INDEX);
    }

    private function aplicarResultadoGestaoRestaurante(ServiceResult $resultado): void
    {
        set_flash(
            $resultado->isSuccess() ? GestaoRestaurantesConstants::FLASH_SUCCESS : GestaoRestaurantesConstants::FLASH_DANGER,
            $resultado->message()
        );
    }
}
