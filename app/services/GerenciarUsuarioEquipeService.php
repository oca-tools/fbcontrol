<?php
declare(strict_types=1);

final class GerenciarUsuarioEquipeService implements GerenciarUsuarioEquipeServiceInterface
{
    private ProtocolosEquipeRepositoryInterface $repository;

    public function __construct(?ProtocolosEquipeRepositoryInterface $repository = null)
    {
        $this->repository = $repository ?? new ProtocolosEquipeRepository();
    }

    /**
     * Registra ou atualiza colaboradores da equipe de A&B e vincula permissoes operacionais.
     */
    public function executar(GerenciarUsuarioEquipeCommand $command): ServiceResult
    {
        if ($command->acao === ProtocolosEquipeConstants::ACTION_CREATE) {
            return $this->criarColaborador($command);
        }

        if ($command->acao === ProtocolosEquipeConstants::ACTION_UPDATE) {
            return $this->atualizarColaborador($command);
        }

        if ($command->acao === ProtocolosEquipeConstants::ACTION_DEACTIVATE) {
            return $this->desativarColaborador($command);
        }

        return ServiceResult::failure(
            ProtocolosEquipeConstants::CODE_METHOD_INVALID,
            ProtocolosEquipeConstants::MESSAGE_METHOD_INVALID
        );
    }

    private function criarColaborador(GerenciarUsuarioEquipeCommand $command): ServiceResult
    {
        if ($command->nome === '' || $command->email === '' || $command->senha === '') {
            return ServiceResult::failure(
                ProtocolosEquipeConstants::CODE_REQUIRED_FIELDS,
                ProtocolosEquipeConstants::MESSAGE_REQUIRED_FIELDS
            );
        }

        if (!$this->gestorPodeAtribuirPerfil($command->gestor, $command->perfil)) {
            return ServiceResult::failure(
                ProtocolosEquipeConstants::CODE_PROFILE_FORBIDDEN,
                ProtocolosEquipeConstants::MESSAGE_PROFILE_FORBIDDEN
            );
        }

        if ($this->repository->credenciaisExistem($command->email, $command->senha)) {
            return ServiceResult::failure(
                ProtocolosEquipeConstants::CODE_DUPLICATE_CREDENTIALS,
                ProtocolosEquipeConstants::MESSAGE_DUPLICATE_CREATE
            );
        }

        [$restaurantes, $restaurantesOperacoes] = $this->normalizarPermissoes($command->assignments);
        $gestorId = (int)($command->gestor['id'] ?? 0);
        $usuarioId = $this->repository->criarUsuario([
            'nome' => $command->nome,
            'email' => $command->email,
            'senha' => $command->senha,
            'perfil' => $command->perfil,
            'ativo' => ProtocolosEquipeConstants::USER_STATUS_ACTIVE,
        ], $gestorId);
        $this->repository->sincronizarPermissoes($usuarioId, $restaurantes, $restaurantesOperacoes, $gestorId);

        return ServiceResult::success(ProtocolosEquipeConstants::MESSAGE_USER_CREATED, ['usuario_id' => $usuarioId]);
    }

    private function atualizarColaborador(GerenciarUsuarioEquipeCommand $command): ServiceResult
    {
        if ($command->usuarioId <= 0 || $command->nome === '' || $command->email === '') {
            return ServiceResult::failure(
                ProtocolosEquipeConstants::CODE_USER_INVALID,
                ProtocolosEquipeConstants::MESSAGE_INVALID_DATA
            );
        }

        $atual = $this->repository->buscarUsuario($command->usuarioId);
        if (!$atual || !$this->gestorPodeGerenciarAlvo($command->gestor, $atual) || !$this->gestorPodeAtribuirPerfil($command->gestor, $command->perfil)) {
            return ServiceResult::failure(
                ProtocolosEquipeConstants::CODE_USER_FORBIDDEN,
                ProtocolosEquipeConstants::MESSAGE_USER_UPDATE_FORBIDDEN
            );
        }

        if ($command->senha !== '' && $this->repository->credenciaisExistem($command->email, $command->senha, $command->usuarioId)) {
            return ServiceResult::failure(
                ProtocolosEquipeConstants::CODE_DUPLICATE_CREDENTIALS,
                ProtocolosEquipeConstants::MESSAGE_DUPLICATE_UPDATE
            );
        }

        [$restaurantes, $restaurantesOperacoes] = $this->normalizarPermissoes($command->assignments);
        $gestorId = (int)($command->gestor['id'] ?? 0);
        $this->repository->atualizarUsuario($command->usuarioId, [
            'nome' => $command->nome,
            'email' => $command->email,
            'senha' => $command->senha,
            'perfil' => $command->perfil,
            'ativo' => $command->ativo,
        ], $gestorId);
        $this->repository->sincronizarPermissoes($command->usuarioId, $restaurantes, $restaurantesOperacoes, $gestorId);

        return ServiceResult::success(ProtocolosEquipeConstants::MESSAGE_USER_UPDATED, [
            'tab' => $command->ativo === ProtocolosEquipeConstants::USER_STATUS_ACTIVE
                ? ProtocolosEquipeConstants::TAB_ACTIVE
                : ProtocolosEquipeConstants::TAB_INACTIVE,
        ]);
    }

    private function desativarColaborador(GerenciarUsuarioEquipeCommand $command): ServiceResult
    {
        if ($command->usuarioId <= 0) {
            return ServiceResult::failure(
                ProtocolosEquipeConstants::CODE_USER_INVALID,
                ProtocolosEquipeConstants::MESSAGE_USER_INVALID
            );
        }

        if ($command->usuarioId === (int)($command->gestor['id'] ?? 0)) {
            return ServiceResult::failure(
                ProtocolosEquipeConstants::CODE_SELF_DEACTIVATE,
                ProtocolosEquipeConstants::MESSAGE_SELF_DEACTIVATE
            );
        }

        $alvo = $this->repository->buscarUsuario($command->usuarioId);
        if (!$alvo || !$this->gestorPodeGerenciarAlvo($command->gestor, $alvo)) {
            return ServiceResult::failure(
                ProtocolosEquipeConstants::CODE_USER_FORBIDDEN,
                ProtocolosEquipeConstants::MESSAGE_USER_DEACTIVATE_FORBIDDEN
            );
        }

        $gestorId = (int)($command->gestor['id'] ?? 0);
        $this->repository->sincronizarPermissoes($command->usuarioId, [], [], $gestorId);
        $this->repository->desativarUsuario($command->usuarioId, $gestorId);

        return ServiceResult::success(ProtocolosEquipeConstants::MESSAGE_USER_DEACTIVATED, [
            'tab' => ProtocolosEquipeConstants::TAB_INACTIVE,
        ]);
    }

    private function gestorPodeAtribuirPerfil(array $gestor, string $perfil): bool
    {
        return ($gestor['perfil'] ?? '') === ProtocolosEquipeConstants::PROFILE_ADMIN
            || in_array($perfil, ProtocolosEquipeConstants::MANAGER_ASSIGNABLE_PROFILES, true);
    }

    private function gestorPodeGerenciarAlvo(array $gestor, array $alvo): bool
    {
        return ($gestor['perfil'] ?? '') === ProtocolosEquipeConstants::PROFILE_ADMIN
            || in_array((string)($alvo['perfil'] ?? ''), ProtocolosEquipeConstants::MANAGER_ASSIGNABLE_PROFILES, true);
    }

    private function normalizarPermissoes(array $rawSelections): array
    {
        $restaurantIds = [];
        $operationMap = [];
        $allOpsByRestaurant = [];

        foreach ($rawSelections as $value) {
            $value = trim((string)$value);
            if ($value === '') {
                continue;
            }

            if (preg_match('/^r(\d+)$/', $value, $matches) === 1) {
                $restauranteId = (int)$matches[1];
                if ($restauranteId > 0) {
                    $restaurantIds[$restauranteId] = $restauranteId;
                    $allOpsByRestaurant[$restauranteId] = true;
                }
                continue;
            }

            if (preg_match('/^r(\d+)_o(\d+)$/', $value, $matches) === 1) {
                $restauranteId = (int)$matches[1];
                $operacaoId = (int)$matches[2];
                if ($restauranteId > 0 && $operacaoId > 0) {
                    $restaurantIds[$restauranteId] = $restauranteId;
                    $operationMap[$restauranteId][$operacaoId] = $operacaoId;
                }
            }
        }

        foreach (array_keys($allOpsByRestaurant) as $restauranteId) {
            unset($operationMap[$restauranteId]);
        }

        $normalizedOps = [];
        foreach ($operationMap as $restauranteId => $operacoes) {
            $normalizedOps[$restauranteId] = array_values(array_unique(array_map('intval', $operacoes)));
        }

        return [
            array_values(array_unique(array_map('intval', $restaurantIds))),
            $normalizedOps,
        ];
    }
}
