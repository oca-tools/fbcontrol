<?php
declare(strict_types=1);

class OperacoesController extends Controller
{
    /**
     * Exibe as operacoes de A&B disponiveis para vinculo aos restaurantes.
     */
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole([GestaoRestaurantesConstants::ROLE_ADMIN]);

        $estruturaRestauranteRepository = new EstruturaRestauranteRepository();
        $this->view('crud/operacoes', [
            'items' => $estruturaRestauranteRepository->listarOperacoes(),
            'flash' => get_flash(),
        ]);
    }

    /**
     * Cadastra uma operacao de A&B que podera ser ativada por restaurante.
     */
    public function create(): void
    {
        $this->requireAuth();
        Auth::requireRole([GestaoRestaurantesConstants::ROLE_ADMIN]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(GestaoRestaurantesConstants::ROUTE_OPERACOES_INDEX);
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash(GestaoRestaurantesConstants::FLASH_DANGER, GestaoRestaurantesConstants::MESSAGE_TOKEN_INVALID);
            $this->redirect(GestaoRestaurantesConstants::ROUTE_OPERACOES_INDEX);
        }

        $resultado = (new ConfigurarRestauranteService())->executar(new ConfigurarRestauranteCommand([
            'tipo_cadastro' => GestaoRestaurantesConstants::REGISTER_TYPE_OPERACAO,
            'acao' => GestaoRestaurantesConstants::ACTION_CREATE,
            'usuario_id' => Auth::user()['id'] ?? 0,
            'nome' => $_POST['nome'] ?? '',
            'ativo' => GestaoRestaurantesConstants::STATUS_ACTIVE,
        ]));

        $this->aplicarResultadoGestaoRestaurante($resultado);
        $this->redirect(GestaoRestaurantesConstants::ROUTE_OPERACOES_INDEX);
    }

    /**
     * Atualiza o cadastro de uma operacao usada na grade dos restaurantes.
     */
    public function edit(): void
    {
        $this->requireAuth();
        Auth::requireRole([GestaoRestaurantesConstants::ROLE_ADMIN]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(GestaoRestaurantesConstants::ROUTE_OPERACOES_INDEX);
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash(GestaoRestaurantesConstants::FLASH_DANGER, GestaoRestaurantesConstants::MESSAGE_TOKEN_INVALID);
            $this->redirect(GestaoRestaurantesConstants::ROUTE_OPERACOES_INDEX);
        }

        $resultado = (new ConfigurarRestauranteService())->executar(new ConfigurarRestauranteCommand([
            'tipo_cadastro' => GestaoRestaurantesConstants::REGISTER_TYPE_OPERACAO,
            'acao' => GestaoRestaurantesConstants::ACTION_EDIT,
            'id' => $_POST['id'] ?? 0,
            'usuario_id' => Auth::user()['id'] ?? 0,
            'nome' => $_POST['nome'] ?? '',
            'ativo' => $_POST['ativo'] ?? GestaoRestaurantesConstants::STATUS_ACTIVE,
        ]));

        $this->aplicarResultadoGestaoRestaurante($resultado);
        $this->redirect(GestaoRestaurantesConstants::ROUTE_OPERACOES_INDEX);
    }

    private function aplicarResultadoGestaoRestaurante(ServiceResult $resultado): void
    {
        set_flash(
            $resultado->isSuccess() ? GestaoRestaurantesConstants::FLASH_SUCCESS : GestaoRestaurantesConstants::FLASH_DANGER,
            $resultado->message()
        );
    }
}
