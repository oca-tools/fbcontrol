<?php
declare(strict_types=1);

final class GovernancaConstants
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_GERENTE = 'gerente';

    public const ROUTE_LOGIN = '/?r=auth/login';
    public const ROUTE_HOME = '/?r=dashboard/index';
    public const ROUTE_LGPD_INDEX = '/?r=lgpd/index';
    public const ROUTE_PRIVACIDADE_INDEX = '/?r=privacidade/index';

    public const FLASH_SUCCESS = 'success';
    public const FLASH_WARNING = 'warning';
    public const FLASH_DANGER = 'danger';

    public const AUDIT_LOGIN_SUCCESS = 'auth_login_success';
    public const AUDIT_LOGIN_FAILED = 'auth_login_failed';
    public const AUDIT_LOGIN_AMBIGUOUS = 'auth_login_ambiguous';
    public const AUDIT_LOGIN_BLOCKED = 'auth_blocked';
    public const AUDIT_LOGOUT = 'auth_logout';
    public const AUDIT_SHIFT_FORCE_CLOSE = 'shift_force_close';
    public const AUDIT_SPECIAL_SHIFT_FORCE_CLOSE = 'special_shift_force_close';
    public const AUDIT_AUTO_CLOSE_FAILED = 'audit_auto_close_failed';
    public const AUDIT_LGPD_PANEL_LOAD_FAILED = 'lgpd_panel_load_failed';
    public const AUDIT_LGPD_CONFIG_SAVE_FAILED = 'lgpd_config_save_failed';
    public const AUDIT_LGPD_REQUEST_CREATE_FAILED = 'lgpd_request_create_failed';
    public const AUDIT_LGPD_REQUEST_UPDATE_FAILED = 'lgpd_request_update_failed';
    public const AUDIT_LGPD_INCIDENT_CREATE_FAILED = 'lgpd_incident_create_failed';
    public const AUDIT_LGPD_INCIDENT_UPDATE_FAILED = 'lgpd_incident_update_failed';
    public const AUDIT_LGPD_RETENTION_POLICY_SAVE_FAILED = 'lgpd_retention_policy_save_failed';
    public const AUDIT_LGPD_RETENTION_RUN_FAILED = 'lgpd_retention_run_failed';
    public const AUDIT_LGPD_RETENTION_RUN_ERRORS = 'lgpd_retention_run_errors';

    public const RETENCAO_LOGS_DIAS = 90;
    public const ANONIMIZACAO_AUTOMATICA_DIAS = 180;
    public const PRAZO_TITULAR_DIAS = 15;
    public const PRAZO_INCIDENTE_DIAS_UTEIS = 3;
    public const MAX_PRAZO_TITULAR_DIAS = 180;
    public const MAX_PRAZO_INCIDENTE_DIAS_UTEIS = 30;
    public const PRAZO_ALERTA_SOLICITACAO_DIAS = 2;
    public const AUDITORIA_TURNOS_GRACE_MINUTES = 10;
    public const AUDITORIA_PAGE_SIZE = 20;
    public const AUDITORIA_QUERY_LIMIT = 200;
    public const LGPD_EVENT_LIMIT = 40;
    public const LGPD_EVENT_DEFAULT_LIMIT = 50;
    public const LGPD_EVENT_LIMIT_MAX = 200;
    public const LGPD_REQUEST_QUERY_LIMIT = 200;
    public const LGPD_INCIDENT_QUERY_LIMIT = 150;
    public const MAX_CONTROLADOR_NOME_LENGTH = 160;
    public const MAX_EMAIL_LENGTH = 190;
    public const MAX_PHONE_LENGTH = 40;
    public const MAX_URL_LENGTH = 255;
    public const MAX_DOCUMENTO_LENGTH = 40;
    public const MAX_LGPD_LONG_TEXT_LENGTH = 2000;
    public const MAX_TABELA_RETENCAO_LENGTH = 100;
    public const MAX_DESCRICAO_RETENCAO_LENGTH = 190;
    public const MAX_INCIDENT_CATEGORY_LENGTH = 80;
    public const MAX_AUDIT_ACTION_LENGTH = 120;
    public const MAX_LGPD_EVENT_TYPE_LENGTH = 30;
    public const MAX_LGPD_EVENT_REFERENCE_LENGTH = 120;
    public const MAX_LGPD_EVENT_ACTION_LENGTH = 40;
    public const MAX_EXCEPTION_MESSAGE_LENGTH = 300;

    public const MESSAGE_TOKEN_INVALIDO = 'Token inválido.';
    public const MESSAGE_ACCESS_DENIED = 'Acesso negado.';
    public const MESSAGE_CREDENCIAIS_INCOMPLETAS = 'Informe e-mail e senha.';
    public const MESSAGE_LOGIN_SUCESSO = 'Login realizado.';
    public const MESSAGE_LOGIN_BLOQUEADO_PREFIX = 'Muitas tentativas. Aguarde ';
    public const MESSAGE_LOGIN_BLOQUEADO_SUFFIX = ' minuto(s) e tente novamente.';
    public const MESSAGE_CREDENCIAL_AMBIGUA = 'Conflito de credenciais para este e-mail. Contate o administrador.';
    public const MESSAGE_CREDENCIAIS_INVALIDAS = 'Credenciais inválidas.';
    public const MESSAGE_LGPD_DB_MISSING = 'Estrutura LGPD não encontrada. Execute o SQL: sql/migration_v2_1_lgpd.sql';
    public const MESSAGE_LGPD_CONFIG_SALVA = 'Configuração LGPD salva com sucesso.';
    public const MESSAGE_LGPD_CONFIG_FALHA = 'Falha ao salvar configuracao LGPD. Verifique os logs.';
    public const MESSAGE_CONTROLADOR_OBRIGATORIO = 'Informe o nome do controlador.';
    public const MESSAGE_TITULAR_OBRIGATORIO = 'Informe o nome do titular.';
    public const MESSAGE_SOLICITACAO_REGISTRADA = 'Solicitação LGPD registrada.';
    public const MESSAGE_SOLICITACAO_FALHA = 'Falha ao registrar solicitacao LGPD. Verifique os logs.';
    public const MESSAGE_SOLICITACAO_INVALIDA = 'Solicitação inválida.';
    public const MESSAGE_SOLICITACAO_NAO_ENCONTRADA = 'Solicitação não encontrada.';
    public const MESSAGE_SOLICITACAO_ATUALIZADA = 'Solicitação atualizada.';
    public const MESSAGE_SOLICITACAO_ATUALIZACAO_FALHA = 'Falha ao atualizar solicitacao LGPD. Verifique os logs.';
    public const MESSAGE_INCIDENTE_TITULO_OBRIGATORIO = 'Informe um título para o incidente.';
    public const MESSAGE_INCIDENTE_REGISTRADO = 'Incidente registrado.';
    public const MESSAGE_INCIDENTE_REGISTRO_FALHA = 'Falha ao registrar incidente LGPD. Verifique os logs.';
    public const MESSAGE_INCIDENTE_INVALIDO = 'Incidente inválido.';
    public const MESSAGE_INCIDENTE_NAO_ENCONTRADO = 'Incidente não encontrado.';
    public const MESSAGE_INCIDENTE_ATUALIZADO = 'Incidente atualizado.';
    public const MESSAGE_INCIDENTE_ATUALIZACAO_FALHA = 'Falha ao atualizar incidente LGPD. Verifique os logs.';
    public const MESSAGE_TABELA_RETENCAO_OBRIGATORIA = 'Informe a tabela da política.';
    public const MESSAGE_POLITICA_RETENCAO_SALVA = 'Política de retenção salva.';
    public const MESSAGE_POLITICA_RETENCAO_FALHA = 'Nao foi possivel salvar a politica de retencao. Verifique os logs.';
    public const MESSAGE_RETENCAO_ALERTAS = ' Houve falhas em algumas politicas. Consulte os logs.';
    public const MESSAGE_RETENCAO_FALHA = 'Falha ao executar retencao LGPD. Verifique os logs.';
    public const MESSAGE_TABELA_RETENCAO_NAO_SUPORTADA = 'Tabela de retencao nao suportada.';

    public const DEFAULT_CONTROLADOR_NOME = 'Controlador da operação';
    public const DEFAULT_REQUEST_STATUS = 'aberta';
    public const DEFAULT_INCIDENT_STATUS = 'aberto';
    public const DEFAULT_INCIDENT_RISK = 'medio';
    public const STATUS_REGISTRO_NAO_ENCONTRADO = 'registro_nao_encontrado';
    public const STATUS_RETENCAO_COM_ALERTAS = 'retencao_com_alertas';
}
