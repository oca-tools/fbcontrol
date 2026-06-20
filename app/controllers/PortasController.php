<?php
declare(strict_types=1);

class PortasController extends Controller
{
    /**
     * Exibe os pontos de acesso usados para registrar entradas por restaurante.
     */
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole([GestaoRestaurantesConstants::ROLE_ADMIN]);

        $estruturaRestauranteRepository = new EstruturaRestauranteRepository();
        $this->view('crud/portas', [
            'items' => $estruturaRestauranteRepository->listarPontosDeAcesso(),
            'restaurantes' => $estruturaRestauranteRepository->listarRestaurantes(),
            'flash' => get_flash(),
        ]);
    }

    /**
     * Cadastra uma porta fisica vinculada ao restaurante para controle de acesso.
     */
    public function create(): void
    {
        $this->requireAuth();
        Auth::requireRole([GestaoRestaurantesConstants::ROLE_ADMIN]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(GestaoRestaurantesConstants::ROUTE_PORTAS_INDEX);
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash(GestaoRestaurantesConstants::FLASH_DANGER, GestaoRestaurantesConstants::MESSAGE_TOKEN_INVALID);
            $this->redirect(GestaoRestaurantesConstants::ROUTE_PORTAS_INDEX);
        }

        $resultado = (new ConfigurarPontoAcessoService())->executar(new ConfigurarPontoAcessoCommand([
            'acao' => GestaoRestaurantesConstants::ACTION_CREATE,
            'usuario_id' => Auth::user()['id'] ?? 0,
            'nome' => $_POST['nome'] ?? '',
            'restaurante_id' => $_POST['restaurante_id'] ?? 0,
            'ativo' => GestaoRestaurantesConstants::STATUS_ACTIVE,
        ]));

        $this->aplicarResultadoGestaoRestaurante($resultado);
        $this->redirect(GestaoRestaurantesConstants::ROUTE_PORTAS_INDEX);
    }

    /**
     * Atualiza uma porta de acesso e seu vinculo com restaurante.
     */
    public function edit(): void
    {
        $this->requireAuth();
        Auth::requireRole([GestaoRestaurantesConstants::ROLE_ADMIN]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(GestaoRestaurantesConstants::ROUTE_PORTAS_INDEX);
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash(GestaoRestaurantesConstants::FLASH_DANGER, GestaoRestaurantesConstants::MESSAGE_TOKEN_INVALID);
            $this->redirect(GestaoRestaurantesConstants::ROUTE_PORTAS_INDEX);
        }

        $resultado = (new ConfigurarPontoAcessoService())->executar(new ConfigurarPontoAcessoCommand([
            'acao' => GestaoRestaurantesConstants::ACTION_EDIT,
            'id' => $_POST['id'] ?? 0,
            'usuario_id' => Auth::user()['id'] ?? 0,
            'nome' => $_POST['nome'] ?? '',
            'restaurante_id' => $_POST['restaurante_id'] ?? 0,
            'ativo' => $_POST['ativo'] ?? GestaoRestaurantesConstants::STATUS_ACTIVE,
        ]));

        $this->aplicarResultadoGestaoRestaurante($resultado);
        $this->redirect(GestaoRestaurantesConstants::ROUTE_PORTAS_INDEX);
    }

    private function aplicarResultadoGestaoRestaurante(ServiceResult $resultado): void
    {
        set_flash(
            $resultado->isSuccess() ? GestaoRestaurantesConstants::FLASH_SUCCESS : GestaoRestaurantesConstants::FLASH_DANGER,
            $resultado->message()
        );
    }
}
