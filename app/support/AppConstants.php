<?php
declare(strict_types=1);

/**
 * Centraliza constantes reutilizaveis da aplicacao.
 */
final class AppConstants
{
    public const APP_NAME = 'FBControl';

    public const ROUTE_HOME = '/?r=home';
    public const ROUTE_LOGIN = '/?r=auth/login';
    public const ROUTE_ACCESS_INDEX = '/?r=access/index';
    public const ROUTE_DASHBOARD_INDEX = '/?r=dashboard/index';
    public const ROUTE_FORBIDDEN = '/?r=errors/forbidden';

    public const FLASH_DANGER = 'danger';
    public const FLASH_WARNING = 'warning';

    public const MESSAGE_FORBIDDEN = 'OOps, acesso não autorizado.';
    public const MESSAGE_NOT_FOUND = 'OOps, página não encontrada.';
    public const MESSAGE_VIEW_NOT_FOUND = 'View não encontrada.';

    public const ROLE_ADMIN = 'admin';
    public const ROLE_SUPERVISOR = 'supervisor';
    public const ROLE_MANAGER = 'gerente';
    public const ROLE_HOSTESS = 'hostess';

    public const MANAGEMENT_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_SUPERVISOR,
        self::ROLE_MANAGER,
    ];

    public const ACCESS_HOME_ROLES = [
        self::ROLE_HOSTESS,
        self::ROLE_ADMIN,
        self::ROLE_SUPERVISOR,
    ];

    public const TRUTHY_ENV_VALUES = ['1', 'true', 'yes', 'on'];

    public const DEFAULT_SESSION_TIMEOUT_MINUTES = 30;
    public const USER_STATE_REFRESH_SECONDS = 60;
    public const MIN_IDLE_TIMEOUT_SECONDS = 60;
    public const MIN_USER_STATE_REFRESH_SECONDS = 15;

    public const SESSION_TOKEN_BYTES = 16;
    public const CSRF_TOKEN_BYTES = 32;
    public const SESSION_COOKIE_EXPIRE_PAST_SECONDS = 42000;
    public const AUTH_THROTTLE_WINDOW_SECONDS = 900;
    public const AUTH_THROTTLE_LIMIT = 5;
    public const AUTH_THROTTLE_MAX_BACKOFF_SECONDS = 1800;
    public const AUTH_THROTTLE_MIN_BACKOFF_SECONDS = 60;
    public const AUTH_THROTTLE_DIR = 'ocafbcontrol_auth_throttle';

    public const IPV4_CONTEXT_SEGMENTS = 2;
    public const IPV6_CONTEXT_SEGMENTS = 3;
    public const DB_IP_MAX_LENGTH = 45;
    public const DB_USER_AGENT_MAX_LENGTH = 255;

    public const DEFAULT_SHIFT_GRACE_MINUTES = 10;
    public const DUPLICATE_ALLOWED_UHS = ['998', '999'];

    public const VOUCHER_TARGET_BYTES = 5242880;
    public const VOUCHER_RECEIVE_BYTES = 10485760;
    public const VOUCHER_IMAGE_MAX_SIDE = 2200;
    public const VOUCHER_IMAGE_QUALITIES = [82, 74, 66, 58];
    public const VOUCHER_JPEG_EXTENSION = 'jpg';

    public const EXPORT_FORMAT_CSV = 'csv';
    public const EXPORT_FORMAT_XLSX = 'xlsx';
    public const EXPORT_FORMAT_XLSXML = 'xlsxml';
    public const EXPORT_DEFAULT_FILENAME = 'relatorio';
    public const EXPORT_DEFAULT_SHEET_NAME = 'Exportacao';
    public const EXPORT_DEFAULT_TITLE = 'Exportacao FBControl';
    public const EXPORT_DEFAULT_SUBTITLE = 'Arquivo gerado pelo sistema.';
    public const EXPORT_TEMP_PREFIX = 'fbexp_';
    public const EXPORT_ROWS_TEMP_PREFIX = 'fbexp_rows_';
    public const EXPORT_XLSX_MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    public const EXPORT_XLS_MIME = 'application/vnd.ms-excel';
    public const EXPORT_CSV_MIME = 'text/csv; charset=utf-8';
    public const EXPORT_UTF8_BOM = "\xEF\xBB\xBF";
    public const EXPORT_MAX_SHEET_NAME_LENGTH = 31;
    public const EXPORT_MIN_COLUMN_WIDTH = 6;
    public const EXPORT_MAX_COLUMN_WIDTH = 42;
    public const EXPORT_COLUMN_PADDING = 2;
    public const EXPORT_DEFAULT_COLUMN_WIDTH = 18;
    public const EXPORT_XML_WIDTH_FACTOR = 6.2;

    public const TEMATIC_RESTAURANT_KEYWORDS = ['giardino', 'la brasa', "ix'u", 'ixu', 'ix'];
    public const TEMATIC_CORAIS_KEYWORD = 'corais';
    public const TEMATIC_OPERATION_KEYWORD = 'tematico';
}
