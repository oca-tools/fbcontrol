<?php
class RestaurantesController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);

        $model = new RestaurantModel();
        $this->view('crud/restaurantes', [
            'items' => $model->all(),
            'flash' => get_flash(),
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=restaurantes/index');
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=restaurantes/index');
        }

        $nome = trim($_POST['nome'] ?? '');
        $tipo = $_POST['tipo'] ?? 'buffet';
        $selecionaPorta = (int)($_POST['seleciona_porta_no_turno'] ?? 0);
        $exigePax = (int)($_POST['exige_pax'] ?? 0);

        if ($nome === '') {
            set_flash('danger', 'Nome é obrigatório.');
            $this->redirect('/?r=restaurantes/index');
        }

        $model = new RestaurantModel();
        $model->create([
            'nome' => $nome,
            'tipo' => $tipo,
            'seleciona_porta_no_turno' => $selecionaPorta,
            'exige_pax' => $exigePax,
            'ativo' => 1,
        ], Auth::user()['id']);
        set_flash('success', 'Restaurante cadastrado.');
        $this->redirect('/?r=restaurantes/index');
    }

    public function edit(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=restaurantes/index');
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=restaurantes/index');
        }

        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $tipo = $_POST['tipo'] ?? 'buffet';
        $selecionaPorta = (int)($_POST['seleciona_porta_no_turno'] ?? 0);
        $exigePax = (int)($_POST['exige_pax'] ?? 0);
        $ativo = (int)($_POST['ativo'] ?? 1);

        if ($id <= 0 || $nome === '') {
            set_flash('danger', 'Dados inválidos.');
            $this->redirect('/?r=restaurantes/index');
        }

        $model = new RestaurantModel();
        $model->update($id, [
            'nome' => $nome,
            'tipo' => $tipo,
            'seleciona_porta_no_turno' => $selecionaPorta,
            'exige_pax' => $exigePax,
            'ativo' => $ativo,
        ], Auth::user()['id']);
        set_flash('success', 'Restaurante atualizado.');
        $this->redirect('/?r=restaurantes/index');
    }
}

