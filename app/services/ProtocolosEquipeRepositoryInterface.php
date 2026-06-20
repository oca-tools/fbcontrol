<?php
declare(strict_types=1);

interface ProtocolosEquipeRepositoryInterface
{
    /**
     * Lista colaboradores visiveis para manutencao operacional de acordo com o perfil do gestor.
     */
    public function listarUsuariosParaGestor(array $gestor): array;

    /**
     * Recupera um colaborador para validacao de permissao antes da manutencao.
     */
    public function buscarUsuario(int $usuarioId): ?array;

    /**
     * Verifica se credenciais ja existem para impedir duplicidade operacional.
     */
    public function credenciaisExistem(string $email, string $senha, ?int $ignorarUsuarioId = null): bool;

    /**
     * Registra os dados de um novo colaborador e retorna o identificador criado.
     */
    public function criarUsuario(array $dadosUsuario, int $gestorId): int;

    /**
     * Atualiza os dados cadastrais e status operacional de um colaborador.
     */
    public function atualizarUsuario(int $usuarioId, array $dadosUsuario, int $gestorId): void;

    /**
     * Desativa o colaborador preservando historico de auditoria.
     */
    public function desativarUsuario(int $usuarioId, int $gestorId): void;

    /**
     * Substitui os vinculos de restaurantes e operacoes permitidas ao colaborador.
     */
    public function sincronizarPermissoes(int $usuarioId, array $restaurantes, array $restaurantesOperacoes, int $gestorId): void;

    /**
     * Registra visualizacao ou conclusao do protocolo de onboarding da equipe.
     */
    public function registrarOnboarding(int $usuarioId, string $acao): void;
}
