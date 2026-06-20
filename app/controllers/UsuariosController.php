<?php
declare(strict_types=1);

class UsuariosController extends Controller
{
    /**
     * Exibe os colaboradores cadastrados e suas permissoes operacionais de A&B.
     */
    public function index(): void
    {
        $viewer = $this->requireUserManagementAccess();

        $restaurantModel = new RestaurantModel();
        $operationModel = new RestaurantOperationModel();
        $items = (new ProtocolosEquipeRepository())->listarUsuariosParaGestor($viewer);
        $canManagePrivilegedProfiles = ($viewer['perfil'] ?? '') === ProtocolosEquipeConstants::PROFILE_ADMIN;
        $itemsAtivos = array_values(array_filter($items, static fn(array $item): bool => (int)($item['ativo'] ?? 0) === ProtocolosEquipeConstants::USER_STATUS_ACTIVE));
        $itemsDesativados = array_values(array_filter($items, static fn(array $item): bool => (int)($item['ativo'] ?? 0) !== ProtocolosEquipeConstants::USER_STATUS_ACTIVE));

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

    /**
     * Registra os dados de um novo colaborador da equipe de A&B e vincula suas permissoes operacionais.
     */
    public function create(): void
    {
        $viewer = $this->requireUserManagementAccess();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(ProtocolosEquipeConstants::ROUTE_USUARIOS_INDEX);
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash(ProtocolosEquipeConstants::FLASH_DANGER, ProtocolosEquipeConstants::MESSAGE_TOKEN_INVALID);
            $this->redirect(ProtocolosEquipeConstants::ROUTE_USUARIOS_INDEX);
        }

        $resultado = (new GerenciarUsuarioEquipeService())->executar(new GerenciarUsuarioEquipeCommand([
            'acao' => ProtocolosEquipeConstants::ACTION_CREATE,
            'gestor' => $viewer,
            'nome' => $_POST['nome'] ?? '',
            'email' => $_POST['email'] ?? '',
            'senha' => $_POST['senha'] ?? '',
            'perfil' => $_POST['perfil'] ?? ProtocolosEquipeConstants::PROFILE_HOSTESS,
            'assignments' => $_POST['assignments'] ?? [],
        ]));

        $this->aplicarResultadoEquipe($resultado);
        $this->redirect(ProtocolosEquipeConstants::ROUTE_USUARIOS_INDEX);
    }

    /**
     * Atualiza cadastro, status e permissoes de um colaborador da equipe de A&B.
     */
    public function edit(): void
    {
        $viewer = $this->requireUserManagementAccess();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(ProtocolosEquipeConstants::ROUTE_USUARIOS_INDEX);
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash(ProtocolosEquipeConstants::FLASH_DANGER, ProtocolosEquipeConstants::MESSAGE_TOKEN_INVALID);
            $this->redirect(ProtocolosEquipeConstants::ROUTE_USUARIOS_INDEX);
        }

        $resultado = (new GerenciarUsuarioEquipeService())->executar(new GerenciarUsuarioEquipeCommand([
            'acao' => ProtocolosEquipeConstants::ACTION_UPDATE,
            'gestor' => $viewer,
            'usuario_id' => $_POST['id'] ?? 0,
            'nome' => $_POST['nome'] ?? '',
            'email' => $_POST['email'] ?? '',
            'senha' => $_POST['senha'] ?? '',
            'perfil' => $_POST['perfil'] ?? ProtocolosEquipeConstants::PROFILE_HOSTESS,
            'ativo' => $_POST['ativo'] ?? ProtocolosEquipeConstants::USER_STATUS_ACTIVE,
            'assignments' => $_POST['assignments'] ?? [],
        ]));

        $this->aplicarResultadoEquipe($resultado);
        $tab = (string)($resultado->payload()['tab'] ?? ProtocolosEquipeConstants::TAB_ACTIVE);
        $this->redirect(ProtocolosEquipeConstants::ROUTE_USUARIOS_INDEX . '&tab=' . urlencode($tab));
    }

    /**
     * Desativa um colaborador preservando historico operacional e removendo permissoes ativas.
     */
    public function delete(): void
    {
        $viewer = $this->requireUserManagementAccess();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(ProtocolosEquipeConstants::ROUTE_USUARIOS_INDEX);
        }
        if (!csrf_validate($_POST['csrf_token'] ?? '')) {
            set_flash(ProtocolosEquipeConstants::FLASH_DANGER, ProtocolosEquipeConstants::MESSAGE_TOKEN_INVALID);
            $this->redirect(ProtocolosEquipeConstants::ROUTE_USUARIOS_INDEX);
        }

        $resultado = (new GerenciarUsuarioEquipeService())->executar(new GerenciarUsuarioEquipeCommand([
            'acao' => ProtocolosEquipeConstants::ACTION_DEACTIVATE,
            'gestor' => $viewer,
            'usuario_id' => $_POST['id'] ?? 0,
        ]));

        $this->aplicarResultadoEquipe($resultado);
        $this->redirect(ProtocolosEquipeConstants::ROUTE_USUARIOS_INDEX . '&tab=' . ProtocolosEquipeConstants::TAB_INACTIVE);
    }

    private function requireUserManagementAccess(): array
    {
        $this->requireAuth();
        Auth::requireRole([
            ProtocolosEquipeConstants::PROFILE_ADMIN,
            ProtocolosEquipeConstants::PROFILE_MANAGER,
        ]);
        return Auth::user() ?? [];
    }

    private function aplicarResultadoEquipe(ServiceResult $resultado): void
    {
        $tipoFlash = $resultado->isSuccess()
            ? ProtocolosEquipeConstants::FLASH_SUCCESS
            : ($resultado->code() === ProtocolosEquipeConstants::CODE_SELF_DEACTIVATE
                ? ProtocolosEquipeConstants::FLASH_WARNING
                : ProtocolosEquipeConstants::FLASH_DANGER);
        set_flash($tipoFlash, $resultado->message());
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

}
