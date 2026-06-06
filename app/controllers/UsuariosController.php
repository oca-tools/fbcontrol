<?php
class UsuariosController extends Controller
{
    public function index(): void
    {
        $viewer = $this->requireUserManagementAccess();

        $model = new UserModel();
        $restaurantModel = new RestaurantModel();
        $operationModel = new RestaurantOperationModel();
        $items = $model->all();
        $canManagePrivilegedProfiles = ($viewer['perfil'] ?? '') === 'admin';
        if (!$canManagePrivilegedProfiles) {
            $items = array_values(array_filter($items, static fn($item) => in_array(($item['perfil'] ?? ''), ['hostess', 'supervisor'], true)));
        }
        $itemsAtivos = array_values(array_filter($items, static fn($item) => (int)($item['ativo'] ?? 0) === 1));
        $itemsDesativados = array_values(array_filter($items, static fn($item) => (int)($item['ativo'] ?? 0) !== 1));

        $assignModel = new UserRestaurantModel();
        $assignOpModel = new UserRestaurantOperationModel();
        $userIds = array_map(fn($u) => (int)$u['id'], $items);

        $restaurants = $restaurantModel->all();
        $assignedRestaurants = $assignModel->mapByUsers($userIds);
        $assignedOperations = $assignOpModel->mapByUsers($userIds);

        $this->view('crud/usuarios', [
            'items' => $items,
            'items_ativos' => $itemsAtivos,
            'items_desativados' => $itemsDesativados,
            'can_manage_privileged_profiles' => $canManagePrivilegedProfiles,
            'restaurantes' => $restaurants,
            'assignment_options' => $this->buildAssignmentOptions($restaurants, $operationModel),
            'assigned_restaurants' => $assignedRestaurants,
            'assigned_operations' => $assignedOperations,
            'flash' => get_flash(),
        ]);
    }

    public function create(): void
    {
        $viewer = $this->requireUserManagementAccess();

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
        [$restaurantes, $restaurantesOperacoes] = $this->parseAssignmentSelections($_POST['assignments'] ?? []);

        if ($nome === '' || $email === '' || $senha === '') {
            set_flash('danger', 'Preencha todos os campos obrigatórios.');
            $this->redirect('/?r=usuarios/index');
        }
        if (!$this->mayAssignProfile($viewer, $perfil)) {
            set_flash('danger', 'A gerente pode criar apenas usuários hostess ou supervisor.');
            $this->redirect('/?r=usuarios/index');
        }

        $model = new UserModel();
        if ($model->emailPasswordExists($email, $senha)) {
            set_flash('danger', 'Ja existe um usuario com este e-mail e esta senha.');
            $this->redirect('/?r=usuarios/index');
        }

        $adminId = (int)$viewer['id'];
        $userId = $model->create([
            'nome' => $nome,
            'email' => $email,
            'senha' => $senha,
            'perfil' => $perfil,
            'ativo' => 1,
        ], $adminId);

        $assignModel = new UserRestaurantModel();
        foreach ($restaurantes as $restId) {
            $assignModel->assign($userId, (int)$restId, $adminId);
        }

        if (!empty($restaurantesOperacoes)) {
            $assignOpModel = new UserRestaurantOperationModel();
            foreach ($restaurantesOperacoes as $restId => $ops) {
                foreach ($ops as $opId) {
                    $assignOpModel->assign($userId, (int)$restId, (int)$opId, $adminId);
                }
            }
        }

        set_flash('success', 'Usuário criado.');
        $this->redirect('/?r=usuarios/index');
    }

    public function edit(): void
    {
        $viewer = $this->requireUserManagementAccess();

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
        [$restaurantes, $restaurantesOperacoes] = $this->parseAssignmentSelections($_POST['assignments'] ?? []);

        if ($id <= 0 || $nome === '' || $email === '') {
            set_flash('danger', 'Dados inválidos.');
            $this->redirect('/?r=usuarios/index');
        }

        $model = new UserModel();
        $current = $model->find($id);
        if (!$current || !$this->mayManageTarget($viewer, $current) || !$this->mayAssignProfile($viewer, $perfil)) {
            set_flash('danger', 'Você não tem permissão para alterar este usuário ou perfil.');
            $this->redirect('/?r=usuarios/index');
        }
        if ($senha !== '' && $model->emailPasswordExists($email, $senha, $id)) {
            set_flash('danger', 'Ja existe outro usuario com este e-mail e esta senha.');
            $this->redirect('/?r=usuarios/index');
        }

        $adminId = (int)$viewer['id'];
        $model->update($id, [
            'nome' => $nome,
            'email' => $email,
            'senha' => $senha,
            'perfil' => $perfil,
            'ativo' => $ativo,
        ], $adminId);

        $assignModel = new UserRestaurantModel();
        $assignOpModel = new UserRestaurantOperationModel();
        $assignModel->clearByUser($id, $adminId);
        $assignOpModel->clearByUser($id, $adminId);

        foreach ($restaurantes as $restId) {
            $assignModel->assign($id, (int)$restId, $adminId);
        }

        foreach ($restaurantesOperacoes as $restId => $ops) {
            foreach ($ops as $opId) {
                $assignOpModel->assign($id, (int)$restId, (int)$opId, $adminId);
            }
        }

        set_flash('success', 'Usuário atualizado.');
        $this->redirect('/?r=usuarios/index&tab=' . ($ativo === 1 ? 'ativos' : 'desativados'));
    }

    public function delete(): void
    {
        $viewer = $this->requireUserManagementAccess();

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
        if ($id === (int)$viewer['id']) {
            set_flash('warning', 'Você não pode desativar seu próprio usuário.');
            $this->redirect('/?r=usuarios/index');
        }

        $model = new UserModel();
        $target = $model->find($id);
        if (!$target || !$this->mayManageTarget($viewer, $target)) {
            set_flash('danger', 'Você não tem permissão para desativar este usuário.');
            $this->redirect('/?r=usuarios/index');
        }
        $adminId = (int)$viewer['id'];
        $assignModel = new UserRestaurantModel();
        $assignOpModel = new UserRestaurantOperationModel();
        $assignModel->clearByUser($id, $adminId);
        $assignOpModel->clearByUser($id, $adminId);
        $model->deactivate($id, $adminId);

        set_flash('success', 'Usuário desativado. Nome e histórico foram preservados para auditoria.');
        $this->redirect('/?r=usuarios/index&tab=desativados');
    }

    private function requireUserManagementAccess(): array
    {
        $this->requireAuth();
        Auth::requireRole(['admin', 'gerente']);
        return Auth::user() ?? [];
    }

    private function mayAssignProfile(array $viewer, string $perfil): bool
    {
        return ($viewer['perfil'] ?? '') === 'admin' || in_array($perfil, ['hostess', 'supervisor'], true);
    }

    private function mayManageTarget(array $viewer, array $target): bool
    {
        return ($viewer['perfil'] ?? '') === 'admin' || in_array(($target['perfil'] ?? ''), ['hostess', 'supervisor'], true);
    }

    private function normalizeForCompare(string $value): string
    {
        $value = mb_strtolower(normalize_mojibake($value), 'UTF-8');
        return strtr($value, [
            'á' => 'a',
            'à' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'é' => 'e',
            'ê' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ú' => 'u',
            'ç' => 'c',
        ]);
    }

    private function buildAssignmentOptions(array $restaurants, RestaurantOperationModel $operationModel): array
    {
        $options = [];
        foreach ($restaurants as $rest) {
            $restId = (int)$rest['id'];
            $restName = (string)($rest['nome'] ?? '');
            $restNameNorm = $this->normalizeForCompare($restName);
            $isLaBrasa = strpos($restNameNorm, 'la brasa') !== false;

            if (!$isLaBrasa) {
                $options[] = [
                    'key' => 'r' . $restId,
                    'restaurante_id' => $restId,
                    'operacao_id' => null,
                    'label' => $restName,
                ];
                continue;
            }

            $ops = $operationModel->byRestaurant($restId);
            $hasSpecial = false;
            foreach ($ops as $op) {
                $opId = (int)($op['operacao_id'] ?? 0);
                $opName = (string)($op['operacao'] ?? '');
                $opNorm = $this->normalizeForCompare($opName);
                if ($opId <= 0) {
                    continue;
                }

                if (strpos($opNorm, 'almoco') !== false) {
                    $options[] = [
                        'key' => 'r' . $restId . '_o' . $opId,
                        'restaurante_id' => $restId,
                        'operacao_id' => $opId,
                        'label' => 'La Brasa (Almoço)',
                    ];
                    $hasSpecial = true;
                    continue;
                }

                if (strpos($opNorm, 'tematico') !== false) {
                    $options[] = [
                        'key' => 'r' . $restId . '_o' . $opId,
                        'restaurante_id' => $restId,
                        'operacao_id' => $opId,
                        'label' => 'La Brasa (Temático)',
                    ];
                    $hasSpecial = true;
                }
            }

            if (!$hasSpecial) {
                $options[] = [
                    'key' => 'r' . $restId,
                    'restaurante_id' => $restId,
                    'operacao_id' => null,
                    'label' => $restName,
                ];
            }
        }

        return $options;
    }

    private function parseAssignmentSelections(array $rawSelections): array
    {
        $restaurantIds = [];
        $operationMap = [];
        $allOpsByRestaurant = [];

        foreach ($rawSelections as $value) {
            $value = trim((string)$value);
            if ($value === '') {
                continue;
            }

            if (preg_match('/^r(\d+)$/', $value, $m)) {
                $restId = (int)$m[1];
                if ($restId > 0) {
                    $restaurantIds[$restId] = $restId;
                    $allOpsByRestaurant[$restId] = true;
                }
                continue;
            }

            if (preg_match('/^r(\d+)_o(\d+)$/', $value, $m)) {
                $restId = (int)$m[1];
                $opId = (int)$m[2];
                if ($restId > 0 && $opId > 0) {
                    $restaurantIds[$restId] = $restId;
                    if (!isset($operationMap[$restId])) {
                        $operationMap[$restId] = [];
                    }
                    $operationMap[$restId][$opId] = $opId;
                }
            }
        }

        foreach (array_keys($allOpsByRestaurant) as $restId) {
            unset($operationMap[$restId]);
        }

        $normalizedOps = [];
        foreach ($operationMap as $restId => $ops) {
            $normalizedOps[$restId] = array_values(array_unique(array_map('intval', $ops)));
        }

        return [
            array_values(array_unique(array_map('intval', $restaurantIds))),
            $normalizedOps,
        ];
    }
}
