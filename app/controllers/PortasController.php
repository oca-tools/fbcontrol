<?php
class PortasController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);

        $model = new DoorModel();
        $restaurantModel = new RestaurantModel();
        $this->view('crud/portas', [
            'items' => $model->all(),
            'restaurantes' => $restaurantModel->all(),
            'flash' => get_flash(),
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=portas/index');
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=portas/index');
        }

        $nome = trim($_POST['nome'] ?? '');
        $restauranteId = (int)($_POST['restaurante_id'] ?? 0);
        if ($nome === '' || $restauranteId <= 0) {
            set_flash('danger', 'Nome e restaurante são obrigatórios.');
            $this->redirect('/?r=portas/index');
        }

        $model = new DoorModel();
        $model->create(['nome' => $nome, 'restaurante_id' => $restauranteId, 'ativo' => 1], Auth::user()['id']);
        set_flash('success', 'Porta cadastrada.');
        $this->redirect('/?r=portas/index');
    }

    public function edit(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=portas/index');
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=portas/index');
        }

        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $restauranteId = (int)($_POST['restaurante_id'] ?? 0);
        $ativo = (int)($_POST['ativo'] ?? 1);
        if ($id <= 0 || $nome === '' || $restauranteId <= 0) {
            set_flash('danger', 'Dados inválidos.');
            $this->redirect('/?r=portas/index');
        }

        $model = new DoorModel();
        $model->update($id, ['nome' => $nome, 'restaurante_id' => $restauranteId, 'ativo' => $ativo], Auth::user()['id']);
        set_flash('success', 'Porta atualizada.');
        $this->redirect('/?r=portas/index');
    }
}

