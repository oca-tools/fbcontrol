<?php
class UsuariosController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);

        $model = new UserModel();
        $restaurantModel = new RestaurantModel();
        $items = $model->all();
        $assignModel = new UserRestaurantModel();
        $map = $assignModel->mapByUsers(array_map(fn($u) => (int)$u['id'], $items));
        $this->view('crud/usuarios', [
            'items' => $items,
            'restaurantes' => $restaurantModel->all(),
            'assigned' => $map,
            'flash' => get_flash(),
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=usuarios/index');
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=usuarios/index');
        }

        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $perfil = $_POST['perfil'] ?? 'hostess';
        $restaurantes = $_POST['restaurantes'] ?? [];

        if ($nome === '' || $email === '' || $senha === '') {
            set_flash('danger', 'Preencha todos os campos obrigatórios.');
            $this->redirect('/?r=usuarios/index');
        }

        $model = new UserModel();
        if ($model->emailExists($email)) {
            set_flash('danger', 'Este e-mail já está cadastrado.');
            $this->redirect('/?r=usuarios/index');
        }
        $userId = $model->create([
            'nome' => $nome,
            'email' => $email,
            'senha' => $senha,
            'perfil' => $perfil,
            'ativo' => 1,
        ], Auth::user()['id']);

        if (!empty($restaurantes)) {
            $assignModel = new UserRestaurantModel();
            foreach ($restaurantes as $restId) {
                $assignModel->assign($userId, (int)$restId, Auth::user()['id']);
            }
        }

        set_flash('success', 'Usuário criado.');
        $this->redirect('/?r=usuarios/index');
    }

    public function edit(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=usuarios/index');
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=usuarios/index');
        }

        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $perfil = $_POST['perfil'] ?? 'hostess';
        $ativo = (int)($_POST['ativo'] ?? 1);
        $restaurantes = $_POST['restaurantes'] ?? [];

        if ($id <= 0 || $nome === '' || $email === '') {
            set_flash('danger', 'Dados inválidos.');
            $this->redirect('/?r=usuarios/index');
        }

        $model = new UserModel();
        if ($model->emailExists($email, $id)) {
            set_flash('danger', 'Este e-mail já está cadastrado para outro usuário.');
            $this->redirect('/?r=usuarios/index');
        }
        $model->update($id, [
            'nome' => $nome,
            'email' => $email,
            'senha' => $senha,
            'perfil' => $perfil,
            'ativo' => $ativo,
        ], Auth::user()['id']);

        $assignModel = new UserRestaurantModel();
        $assignModel->clearByUser($id, Auth::user()['id']);
        if (!empty($restaurantes)) {
            foreach ($restaurantes as $restId) {
                $assignModel->assign($id, (int)$restId, Auth::user()['id']);
            }
        }

        set_flash('success', 'Usuário atualizado.');
        $this->redirect('/?r=usuarios/index');
    }

    public function delete(): void
    {
        $this->requireAuth();
        Auth::requireRole(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/?r=usuarios/index');
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash('danger', 'Token inválido.');
            $this->redirect('/?r=usuarios/index');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            set_flash('danger', 'Usuário inválido.');
            $this->redirect('/?r=usuarios/index');
        }
        if ($id === (int)Auth::user()['id']) {
            set_flash('warning', 'Você não pode excluir seu próprio usuário.');
            $this->redirect('/?r=usuarios/index');
        }

        $model = new UserModel();
        $assignModel = new UserRestaurantModel();
        $assignModel->clearByUser($id, Auth::user()['id']);
        $model->anonymizeAndDeactivate($id, Auth::user()['id']);

        set_flash('success', 'Usuário excluído com anonimização e mantido para auditoria.');
        $this->redirect('/?r=usuarios/index');
    }
}

