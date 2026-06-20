<?php
declare(strict_types=1);

/**
 * Centraliza mensagens, status e labels do modulo GestaoRestaurantes.
 */
final class GestaoRestaurantesConstants
{
    public const ROLE_ADMIN = 'admin';

    public const ROUTE_RESTAURANTES_INDEX = '/?r=restaurantes/index';
    public const ROUTE_OPERACOES_INDEX = '/?r=operacoes/index';
    public const ROUTE_PORTAS_INDEX = '/?r=portas/index';
    public const ROUTE_HORARIOS_INDEX = '/?r=horarios/index';

    public const ACTION_CREATE = 'criar';
    public const ACTION_EDIT = 'editar';

    public const REGISTER_TYPE_RESTAURANTE = 'restaurante';
    public const REGISTER_TYPE_OPERACAO = 'operacao';

    public const RESTAURANT_TYPE_BUFFET = 'buffet';
    public const RESTAURANT_TYPE_TEMATICO = 'tematico';
    public const RESTAURANT_TYPE_AREA = 'area';
    public const RESTAURANT_TYPES = [
        self::RESTAURANT_TYPE_BUFFET,
        self::RESTAURANT_TYPE_TEMATICO,
        self::RESTAURANT_TYPE_AREA,
    ];

    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;
    public const DEFAULT_TOLERANCE_MINUTES = 0;

    public const LABEL_JANTAR_CORAIS = 'Jantar Corais';
    public const LABEL_LA_BRASA_ALMOCO = 'La Brasa (Almoço)';
    public const LABEL_LA_BRASA_TEMATICO = 'La Brasa (Temático)';

    public const CODE_NOME_OBRIGATORIO = 'nome_obrigatorio';
    public const CODE_DADOS_INVALIDOS = 'dados_invalidos';
    public const CODE_DADOS_PONTO_ACESSO_INVALIDOS = 'dados_ponto_acesso_invalidos';
    public const CODE_PONTO_ACESSO_DUPLICADO = 'ponto_acesso_duplicado';
    public const CODE_DADOS_HORARIO_INVALIDOS = 'dados_horario_invalidos';
    public const CODE_HORARIO_INVALIDO = 'horario_invalido';

    public const MESSAGE_TOKEN_INVALID = 'Token inválido.';
    public const MESSAGE_NOME_OBRIGATORIO = 'Nome é obrigatório.';
    public const MESSAGE_PREENCHA_NOME = 'Preencha o nome.';
    public const MESSAGE_DADOS_INVALIDOS = 'Dados inválidos.';
    public const MESSAGE_RESTAURANTE_CADASTRADO = 'Restaurante cadastrado.';
    public const MESSAGE_RESTAURANTE_ATUALIZADO = 'Restaurante atualizado.';
    public const MESSAGE_OPERACAO_CADASTRADA = 'Operação cadastrada.';
    public const MESSAGE_OPERACAO_ATUALIZADA = 'Operação atualizada.';
    public const MESSAGE_PONTO_ACESSO_OBRIGATORIO = 'Nome e restaurante são obrigatórios.';
    public const MESSAGE_PONTO_ACESSO_DUPLICADO = 'Este restaurante já possui esta porta.';
    public const MESSAGE_PORTA_CADASTRADA = 'Porta cadastrada.';
    public const MESSAGE_PORTA_ATUALIZADA = 'Porta atualizada.';
    public const MESSAGE_HORARIO_OBRIGATORIO = 'Preencha todos os campos obrigatórios.';
    public const MESSAGE_HORARIO_INVALIDO = 'Informe horários válidos para início e fim da operação.';
    public const MESSAGE_HORARIO_CADASTRADO = 'Horário cadastrado.';
    public const MESSAGE_HORARIO_ATUALIZADO = 'Horário atualizado.';

    public const FLASH_DANGER = 'danger';
    public const FLASH_SUCCESS = 'success';

    public const AUDIT_CREATE = 'create';
    public const AUDIT_UPDATE = 'update';
    public const ENTITY_RESTAURANTES = 'restaurantes';
    public const ENTITY_OPERACOES = 'operacoes';
    public const ENTITY_PORTAS = 'portas';
    public const ENTITY_RESTAURANTE_OPERACOES = 'restaurante_operacoes';
}
