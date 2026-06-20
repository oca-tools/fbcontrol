<?php
declare(strict_types=1);

/**
 * Centraliza codigos, status e mensagens operacionais de ReservasTematicas.
 */
final class ReservasTematicasConstants
{
    public const ACTION_AUTO_NO_SHOW = 'auto_no_show';
    public const ACTION_CLOSE_TURNO = 'close_turno';
    public const ACTION_CREATE = 'create';
    public const ACTION_CREATE_BATCH = 'create_batch';
    public const ACTION_QUICK_STATUS = 'quick_status';
    public const ACTION_UPDATE = 'update';
    public const ACTION_UPDATE_DETAIL = 'update_detail';
    public const ACTION_UPDATE_STATUS = 'update_status';

    public const QUICK_FINALIZAR = 'finalizar';
    public const QUICK_NO_SHOW = 'nao_compareceu';
    public const QUICK_CANCELAR = 'cancelar';

    public const ORIGIN_CRON = 'cron';
    public const ORIGIN_CONTROLLER = 'controller';
    public const ORIGIN_SERVICE = 'servico';

    public const STATUS_RESERVADA = 'Reservada';
    public const STATUS_FINALIZADA = 'Finalizada';
    public const STATUS_NO_SHOW = 'Nao compareceu';
    public const STATUS_NO_SHOW_ACCENTED = 'Não compareceu';
    public const STATUS_CANCELADA = 'Cancelada';
    public const STATUS_DIVERGENCIA = 'Divergencia';
    public const STATUS_DIVERGENCIA_ACCENTED = 'Divergência';
    public const STATUS_OPERACAO = 'Operacao';
    public const STATUS_OPERACAO_ACCENTED = 'Operação';
    public const STATUS_CONFERIDA = 'Conferida';
    public const STATUS_EM_ATENDIMENTO = 'Em atendimento';

    public const ALLOWED_OPERATION_STATUSES = [
        self::STATUS_RESERVADA,
        self::STATUS_FINALIZADA,
        self::STATUS_NO_SHOW,
        self::STATUS_CANCELADA,
        self::STATUS_DIVERGENCIA,
    ];

    public const FINAL_STATUSES = [
        self::STATUS_FINALIZADA,
        self::STATUS_NO_SHOW,
        self::STATUS_CANCELADA,
    ];

    public const PRIVILEGED_ROLES = [
        AppConstants::ROLE_ADMIN,
        AppConstants::ROLE_SUPERVISOR,
        AppConstants::ROLE_MANAGER,
    ];

    public const CODE_ACAO_OPERACAO_INVALIDA = 'acao_operacao_invalida';
    public const CODE_ACAO_RAPIDA_INVALIDA = 'acao_rapida_invalida';
    public const CODE_CAPACIDADE_DESTINO_ATINGIDA = 'capacidade_destino_atingida';
    public const CODE_CAPACIDADE_DESTINO_NAO_CONFIGURADA = 'capacidade_destino_nao_configurada';
    public const CODE_CAPACIDADE_NAO_CONFIGURADA = 'capacidade_nao_configurada';
    public const CODE_CAPACIDADE_TURNO_ATINGIDA = 'capacidade_turno_atingida';
    public const CODE_CHD_GRUPO_MAIOR_QUE_PAX = 'chd_grupo_maior_que_pax';
    public const CODE_CHD_MAIOR_QUE_PAX = 'chd_maior_que_pax';
    public const CODE_CONFIRMAR_STATUS_DEFINITIVO = 'confirmar_status_definitivo';
    public const CODE_EDICAO_NAO_AUTORIZADA = 'edicao_nao_autorizada';
    public const CODE_FECHAMENTO_SEM_TURNO = 'fechamento_sem_turno';
    public const CODE_FORA_JANELA_RESERVA = 'fora_janela_reserva';
    public const CODE_GRUPO_SEM_TITULAR = 'grupo_sem_titular';
    public const CODE_GRUPO_SEM_UH = 'grupo_sem_uh';
    public const CODE_IDADES_CHD_INVALIDAS = 'idades_chd_invalidas';
    public const CODE_JUSTIFICATIVA_OBRIGATORIA = 'justificativa_obrigatoria';
    public const CODE_PAX_ACIMA_LIMITE_UH = 'pax_acima_limite_uh';
    public const CODE_PAX_GRUPO_INVALIDO = 'pax_grupo_invalido';
    public const CODE_PAX_INVALIDO = 'pax_invalido';
    public const CODE_PAX_REAL_FORA_LIMITE = 'pax_real_fora_limite';
    public const CODE_PAX_REAL_INVALIDO = 'pax_real_invalido';
    public const CODE_RESERVA_DUPLICADA_UH = 'reserva_duplicada_uh';
    public const CODE_RESERVA_NAO_ENCONTRADA = 'reserva_nao_encontrada';
    public const CODE_RESTAURANTE_FECHADO = 'restaurante_fechado';
    public const CODE_RESTAURANTE_INVALIDO = 'restaurante_invalido';
    public const CODE_RESTAURANTE_OPERACAO_INVALIDO = 'restaurante_operacao_invalido';
    public const CODE_STATUS_DEFINITIVO_BLOQUEADO = 'status_definitivo_bloqueado';
    public const CODE_STATUS_INVALIDO = 'status_invalido';
    public const CODE_TITULAR_OBRIGATORIO = 'titular_obrigatorio';
    public const CODE_TURNO_FECHADO_BLOQUEADO = 'turno_fechado_bloqueado';
    public const CODE_TURNO_OBRIGATORIO = 'turno_obrigatorio';
    public const CODE_TURNO_OPERACAO_INVALIDO = 'turno_operacao_invalido';
    public const CODE_UH_DUPLICADA_GRUPO = 'uh_duplicada_grupo';
    public const CODE_UH_GRUPO_INVALIDA = 'uh_grupo_invalida';
    public const CODE_UH_INVALIDA = 'uh_invalida';
    public const CODE_UH_OBRIGATORIA = 'uh_obrigatoria';
    public const CODE_USUARIO_AUDITORIA_INVALIDO = 'usuario_auditoria_invalido';

    public const MESSAGE_ACAO_OPERACAO_INVALIDA = 'Ação de operação inválida.';
    public const MESSAGE_ACAO_RAPIDA_INVALIDA = 'Ação rápida inválida.';
    public const MESSAGE_CAPACIDADE_DESTINO_ATINGIDA = 'Capacidade do turno atingida para o destino selecionado. Ajuste a capacidade do turno se necessário.';
    public const MESSAGE_CAPACIDADE_DESTINO_NAO_CONFIGURADA = 'Capacidade não configurada para o destino selecionado. Ajuste a capacidade antes de mover a reserva.';
    public const MESSAGE_CAPACIDADE_NAO_CONFIGURADA = 'Capacidade não configurada para este turno. Ajuste a capacidade antes de registrar reservas.';
    public const MESSAGE_CAPACIDADE_TURNO_ATINGIDA = 'Capacidade do turno atingida. Ajuste a capacidade do turno se necessário.';
    public const MESSAGE_CHD_MAIOR_QUE_PAX = 'As idades de CHD não podem exceder a quantidade total de PAX.';
    public const MESSAGE_CONFIRMAR_STATUS_DEFINITIVO = 'Confirme o status definitivo para continuar.';
    public const MESSAGE_EDICAO_NAO_AUTORIZADA = 'Você só pode editar reservas criadas por você. A administração pode acompanhar as alterações pela auditoria.';
    public const MESSAGE_FECHAMENTO_SEM_TURNO = 'Selecione restaurante e turno para encerrar.';
    public const MESSAGE_FORA_JANELA_RESERVA = 'Fora do horário permitido para reservas.';
    public const MESSAGE_GRUPO_CRIADO = 'Grupo de reservas registrado com sucesso.';
    public const MESSAGE_GRUPO_SEM_TITULAR = 'Informe o titular do grupo.';
    public const MESSAGE_GRUPO_SEM_UH = 'Adicione ao menos uma UH no grupo.';
    public const MESSAGE_JUSTIFICATIVA_TURNO = 'Informe a justificativa para alterar turno encerrado.';
    public const MESSAGE_JUSTIFICATIVA_TURNO_DESTINO = 'Informe a justificativa para alterar reserva de turno encerrado.';
    public const MESSAGE_NO_SHOW_AUTO_PROCESSADO = 'No-show automático processado.';
    public const MESSAGE_OPERACAO_ATUALIZADA = 'Reserva atualizada na operação.';
    public const MESSAGE_PAX_ACIMA_LIMITE_UH = 'PAX acima do limite da UH.';
    public const MESSAGE_PAX_GRUPO_INVALIDO = 'Preencha a quantidade de PAX em todas as UHs do grupo.';
    public const MESSAGE_PAX_INVALIDO = 'Quantidade de PAX inválida.';
    public const MESSAGE_PAX_REAL_INVALIDO = 'PAX real inválido.';
    public const MESSAGE_RESERVA_ATUALIZADA = 'Reserva atualizada.';
    public const MESSAGE_RESERVA_CRIADA = 'Reserva registrada.';
    public const MESSAGE_RESERVA_DUPLICADA_UH = 'Já existe reserva para esta UH neste turno.';
    public const MESSAGE_RESERVA_NAO_ENCONTRADA = 'Reserva não encontrada.';
    public const MESSAGE_RESTAURANTE_FECHADO = 'Este restaurante está fechado para a data selecionada. Escolha outro restaurante ou outra data.';
    public const MESSAGE_RESTAURANTE_INVALIDO = 'Restaurante inválido.';
    public const MESSAGE_RESTAURANTE_OPERACAO_INVALIDO = 'Restaurante inválido para esta operação.';
    public const MESSAGE_STATUS_ATUALIZADO = 'Status atualizado.';
    public const MESSAGE_STATUS_DEFINITIVO_BLOQUEADO = 'Status definitivo não pode ser alterado pela hostess.';
    public const MESSAGE_STATUS_INVALIDO = 'Status inválido.';
    public const MESSAGE_TITULAR_OBRIGATORIO = 'Informe o titular da reserva.';
    public const MESSAGE_TURNO_ENCERRADO = 'Turno encerrado.';
    public const MESSAGE_TURNO_FECHADO_BLOQUEADO = 'Turno encerrado. Somente supervisão pode alterar.';
    public const MESSAGE_TURNO_OBRIGATORIO = 'Selecione o turno.';
    public const MESSAGE_TURNO_OPERACAO_INVALIDO = 'Turno inválido para esta operação.';
    public const MESSAGE_UH_INVALIDA = 'UH inválida.';
    public const MESSAGE_UH_OBRIGATORIA = 'Informe a UH.';
    public const MESSAGE_USUARIO_AUDITORIA_INVALIDO = 'Usuário de auditoria inválido para no-show automático.';

    public const NO_SHOW_AUTO_NOTE = 'No-show automático por expiração da tolerância da reserva.';
    public const NO_SHOW_CRON_LOG = 'Aplicado automaticamente via cron.';
    public const NO_SHOW_SERVICE_LOG = 'Aplicado automaticamente pela configuração de tolerância.';
    public const PARTIAL_NO_SHOW_PREFIX = 'No-show parcial: reservado %d, real %d.';

    public const DEFAULT_GROUP_NAME_LIMIT = 120;
    public const DEFAULT_ZERO = 0;
    public const DEFAULT_ONE = 1;
}
