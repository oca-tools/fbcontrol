<?php
class HorariosController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);

        $model = new RestaurantOperationModel();
        $restaurantModel = new RestaurantModel();
        $operationModel = new OperationModel();

        $this->view('crud/horarios', [
            'items' => $model->all(),
            'restaurantes' => $restaurantModel->all(),
            'operacoes' => $operationModel->all(),
            'flash' => get_flash(),
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=horarios/index');
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=horarios/index');
        }

        $restauranteId = (int)($_POST['restaurante_id'] ?? 0);
        $operacaoId = (int)($_POST['operacao_id'] ?? 0);
        $horaInicio = $_POST['hora_inicio'] ?? '';
        $horaFim = $_POST['hora_fim'] ?? '';
        $tolerancia = (int)($_POST['tolerancia_min'] ?? 0);

        if ($restauranteId <= 0 || $operacaoId <= 0 || $horaInicio === '' || $horaFim === '') {
            set_flash('danger', 'Preencha todos os campos obrigatórios.');
            $this->redirect('/?r=horarios/index');
        }

        $model = new RestaurantOperationModel();
        $model->create([
            'restaurante_id' => $restauranteId,
            'operacao_id' => $operacaoId,
            'hora_inicio' => $horaInicio,
            'hora_fim' => $horaFim,
            'tolerancia_min' => $tolerancia,
            'ativo' => 1,
        ], Auth::user()['id']);

        set_flash('success', 'Horário cadastrado.');
        $this->redirect('/?r=horarios/index');
    }

    public function edit(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=horarios/index');
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=horarios/index');
        }

        $id = (int)($_POST['id'] ?? 0);
        $restauranteId = (int)($_POST['restaurante_id'] ?? 0);
        $operacaoId = (int)($_POST['operacao_id'] ?? 0);
        $horaInicio = $_POST['hora_inicio'] ?? '';
        $horaFim = $_POST['hora_fim'] ?? '';
        $tolerancia = (int)($_POST['tolerancia_min'] ?? 0);
        $ativo = (int)($_POST['ativo'] ?? 1);

        if ($id <= 0 || $restauranteId <= 0 || $operacaoId <= 0 || $horaInicio === '' || $horaFim === '') {
            set_flash('danger', 'Dados inválidos.');
            $this->redirect('/?r=horarios/index');
        }

        $model = new RestaurantOperationModel();
        $model->update($id, [
            'restaurante_id' => $restauranteId,
            'operacao_id' => $operacaoId,
            'hora_inicio' => $horaInicio,
            'hora_fim' => $horaFim,
            'tolerancia_min' => $tolerancia,
            'ativo' => $ativo,
        ], Auth::user()['id']);

        set_flash('success', 'Horário atualizado.');
        $this->redirect('/?r=horarios/index');
    }
}

