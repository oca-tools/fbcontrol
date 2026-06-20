<?php
declare(strict_types=1);

final class ProtocolosEquipeRepository implements ProtocolosEquipeRepositoryInterface
{
    /**
     * Lista colaboradores visiveis para manutencao operacional de acordo com o perfil do gestor.
     */
    public function listarUsuariosParaGestor(array $gestor): array
    {
        $usuarios = (new UserModel())->all();
        if (($gestor['perfil'] ?? '') === ProtocolosEquipeConstants::PROFILE_ADMIN) {
            return $usuarios;
        }

        return array_values(array_filter(
            $usuarios,
            static fn(array $usuario): bool => in_array(
                (string)($usuario['perfil'] ?? ''),
                ProtocolosEquipeConstants::MANAGER_ASSIGNABLE_PROFILES,
                true
            )
        ));
    }

    /**
     * Recupera um colaborador para validacao de permissao antes da manutencao.
     */
    public function buscarUsuario(int $usuarioId): ?array
    {
        return (new UserModel())->find($usuarioId);
    }

    /**
     * Verifica se credenciais ja existem para impedir duplicidade operacional.
     */
    public function credenciaisExistem(string $email, string $senha, ?int $ignorarUsuarioId = null): bool
    {
        return (new UserModel())->emailPasswordExists($email, $senha, $ignorarUsuarioId);
    }

    /**
     * Registra os dados de um novo colaborador e retorna o identificador criado.
     */
    public function criarUsuario(array $dadosUsuario, int $gestorId): int
    {
        return (new UserModel())->create($dadosUsuario, $gestorId);
    }

    /**
     * Atualiza os dados cadastrais e status operacional de um colaborador.
     */
    public function atualizarUsuario(int $usuarioId, array $dadosUsuario, int $gestorId): void
    {
        (new UserModel())->update($usuarioId, $dadosUsuario, $gestorId);
    }

    /**
     * Desativa o colaborador preservando historico de auditoria.
     */
    public function desativarUsuario(int $usuarioId, int $gestorId): void
    {
        (new UserModel())->deactivate($usuarioId, $gestorId);
    }

    /**
     * Substitui os vinculos de restaurantes e operacoes permitidas ao colaborador.
     */
    public function sincronizarPermissoes(int $usuarioId, array $restaurantes, array $restaurantesOperacoes, int $gestorId): void
    {
        $assignModel = new UserRestaurantModel();
        $assignOpModel = new UserRestaurantOperationModel();
        $assignModel->clearByUser($usuarioId, $gestorId);
        $assignOpModel->clearByUser($usuarioId, $gestorId);

        foreach ($restaurantes as $restauranteId) {
            $assignModel->assign($usuarioId, (int)$restauranteId, $gestorId);
        }

        foreach ($restaurantesOperacoes as $restauranteId => $operacoes) {
            foreach ($operacoes as $operacaoId) {
                $assignOpModel->assign($usuarioId, (int)$restauranteId, (int)$operacaoId, $gestorId);
            }
        }
    }

    /**
     * Registra visualizacao ou conclusao do protocolo de onboarding da equipe.
     */
    public function registrarOnboarding(int $usuarioId, string $acao): void
    {
        $model = new UserOnboardingModel();
        if ($acao === ProtocolosEquipeConstants::ACTION_ONBOARDING_COMPLETE) {
            $model->completeHostessTutorial($usuarioId);
            return;
        }

        $model->markHostessSeen($usuarioId);
    }
}
