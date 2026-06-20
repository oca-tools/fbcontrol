<?php
declare(strict_types=1);

/**
 * Centraliza perfis, status e mensagens operacionais do modulo ProtocolosEquipe.
 */
final class ProtocolosEquipeConstants
{
    public const ROUTE_USUARIOS_INDEX = '/?r=usuarios/index';

    public const PROFILE_ADMIN = 'admin';
    public const PROFILE_SUPERVISOR = 'supervisor';
    public const PROFILE_HOSTESS = 'hostess';
    public const PROFILE_MANAGER = 'gerente';

    public const PROFILE_LABEL_MAITRE = 'Maitre';
    public const PROFILE_LABEL_HOSTESS = 'Hostess';
    public const PROFILE_LABEL_GERENTE_AB = 'Gerente A&B';

    public const PRIVILEGED_PROFILES = [
        self::PROFILE_ADMIN,
        self::PROFILE_MANAGER,
    ];

    public const MANAGER_ASSIGNABLE_PROFILES = [
        self::PROFILE_HOSTESS,
        self::PROFILE_SUPERVISOR,
    ];

    public const USER_STATUS_ACTIVE = 1;
    public const USER_STATUS_INACTIVE = 0;
    public const TAB_ACTIVE = 'ativos';
    public const TAB_INACTIVE = 'desativados';

    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DEACTIVATE = 'deactivate';
    public const ACTION_ONBOARDING_SEEN = 'onboarding_seen';
    public const ACTION_ONBOARDING_COMPLETE = 'onboarding_complete';

    public const CODE_METHOD_INVALID = 'metodo_invalido';
    public const CODE_TOKEN_INVALID = 'token_invalido';
    public const CODE_REQUIRED_FIELDS = 'campos_obrigatorios';
    public const CODE_PROFILE_FORBIDDEN = 'perfil_nao_autorizado';
    public const CODE_DUPLICATE_CREDENTIALS = 'credenciais_duplicadas';
    public const CODE_USER_INVALID = 'usuario_invalido';
    public const CODE_USER_FORBIDDEN = 'usuario_nao_autorizado';
    public const CODE_SELF_DEACTIVATE = 'auto_desativacao_bloqueada';
    public const CODE_ONBOARDING_REGISTERED = 'onboarding_registrado';

    public const MESSAGE_METHOD_INVALID = 'Método inválido.';
    public const MESSAGE_TOKEN_INVALID = 'Token inválido.';
    public const MESSAGE_REQUIRED_FIELDS = 'Preencha todos os campos obrigatórios.';
    public const MESSAGE_PROFILE_FORBIDDEN = 'A gerente pode criar apenas usuários hostess ou supervisor.';
    public const MESSAGE_USER_UPDATE_FORBIDDEN = 'Você não tem permissão para alterar este usuário ou perfil.';
    public const MESSAGE_USER_DEACTIVATE_FORBIDDEN = 'Você não tem permissão para desativar este usuário.';
    public const MESSAGE_DUPLICATE_CREATE = 'Ja existe um usuario com este e-mail e esta senha.';
    public const MESSAGE_DUPLICATE_UPDATE = 'Ja existe outro usuario com este e-mail e esta senha.';
    public const MESSAGE_USER_CREATED = 'Usuário criado.';
    public const MESSAGE_USER_UPDATED = 'Usuário atualizado.';
    public const MESSAGE_USER_INVALID = 'Usuário inválido.';
    public const MESSAGE_INVALID_DATA = 'Dados inválidos.';
    public const MESSAGE_SELF_DEACTIVATE = 'Você não pode desativar seu próprio usuário.';
    public const MESSAGE_USER_DEACTIVATED = 'Usuário desativado. Nome e histórico foram preservados para auditoria.';
    public const MESSAGE_ONBOARDING_SEEN = 'Protocolo de boas-vindas visualizado.';
    public const MESSAGE_ONBOARDING_COMPLETE = 'Onboarding concluído com sucesso.';

    public const FLASH_DANGER = 'danger';
    public const FLASH_SUCCESS = 'success';
    public const FLASH_WARNING = 'warning';

    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
}
