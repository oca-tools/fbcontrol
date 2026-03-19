<?php
class OperacoesController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);

        $model = new OperationModel();
        $this->view('crud/operacoes', [
            'items' => $model->all(),
            'flash' => get_flash(),
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=operacoes/index');
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=operacoes/index');
        }

        $nome = trim($_POST['nome'] ?? '');
        if ($nome === '') {
            set_flash('danger', 'Preencha o nome.');
            $this->redirect('/?r=operacoes/index');
        }
        $model = new OperationModel();
        $model->create([
            'nome' => $nome,
            'ativo' => 1,
        ], Auth::user()['id']);
        set_flash('success', 'Operação cadastrada.');
        $this->redirect('/?r=operacoes/index');
    }

    public function edit(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=operacoes/index');
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=operacoes/index');
        }

        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $ativo = (int)($_POST['ativo'] ?? 1);

        if ($id <= 0 || $nome === '') {
            set_flash('danger', 'Dados inválidos.');
            $this->redirect('/?r=operacoes/index');
        }
        $model = new OperationModel();
        $model->update($id, [
            'nome' => $nome,
            'ativo' => $ativo,
        ], Auth::user()['id']);
        set_flash('success', 'Operação atualizada.');
        $this->redirect('/?r=operacoes/index');
    }
}

