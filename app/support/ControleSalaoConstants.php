<?php
declare(strict_types=1);

/**
 * Centraliza codigos, mensagens e limites do fluxo ControleSalao.
 */
final class ControleSalaoConstants
{
    public const ACTION_AUDIT_CREATE = 'create';
    public const ACTION_AUDIT_UPDATE = 'update';

    public const ACTION_TEMATICA_CANCELAR = 'cancelar';
    public const ACTION_TEMATICA_PAX_REAL = 'pax_real';

    public const CODE_ACAO_TEMATICA_INVALIDA = 'acao_tematica_invalida';
    public const CODE_ALERTA_DUPLICIDADE = 'alerta_duplicidade';
    public const CODE_CAMPO_OBRIGATORIO = 'campos_obrigatorios';
    public const CODE_CANCELAMENTO_COM_ACESSOS = 'cancelamento_com_acessos';
    public const CODE_CANCELAMENTO_TEMATICO_BLOQUEADO = 'cancelamento_tematico_bloqueado';
    public const CODE_CONFIRMAR_CHECKLIST = 'confirmar_checklist';
    public const CODE_CONFIRMAR_DUPLICIDADE = 'confirmar_duplicidade';
    public const CODE_CONFIRMAR_FORA_HORARIO = 'confirmar_fora_horario';
    public const CODE_CONFIRMAR_NO_SHOW_TEMATICO = 'confirmar_no_show_tematico';
    public const CODE_CONFLITO_RESERVA_TEMATICA = 'conflito_reserva_tematica';
    public const CODE_FALHA_REGISTRO = 'falha_registro';
    public const CODE_FIM_ANTECIPADO_BLOQUEADO = 'fim_antecipado_bloqueado';
    public const CODE_FORA_HORARIO = 'fora_horario';
    public const CODE_LA_BRASA_FORA_ALMOCO = 'la_brasa_fora_almoco';
    public const CODE_NO_SHOW_TEMATICO_CONFIRMADO = 'no_show_tematico_confirmado';
    public const CODE_OPERACAO_INVALIDA = 'operacao_invalida';
    public const CODE_OPERACAO_NAO_AUTORIZADA = 'operacao_nao_autorizada';
    public const CODE_PAX_ACIMA_DA_UH = 'pax_acima_da_uh';
    public const CODE_PAX_BUFFET_EXCEDE_TEMATICO = 'pax_buffet_excede_tematico';
    public const CODE_PAX_DIARIO_ACIMA_DA_OPERACAO = 'pax_diario_acima_da_operacao';
    public const CODE_PAX_REAL_TEMATICO_INVALIDO = 'pax_real_tematico_invalido';
    public const CODE_PORTA_INVALIDA = 'porta_invalida';
    public const CODE_PORTA_OBRIGATORIA = 'porta_obrigatoria';
    public const CODE_REGISTRO_DUPLICADO_CONFIRMADO = 'registro_duplicado_confirmado';
    public const CODE_REGISTRO_NO_SHOW_TEMATICO = 'registro_no_show_tematico';
    public const CODE_RESERVA_TEMATICA_MUDOU = 'reserva_tematica_mudou';
    public const CODE_RESERVA_TEMATICA_PROCESSADA = 'reserva_tematica_processada';
    public const CODE_RESTAURANTE_NAO_AUTORIZADO = 'restaurante_nao_autorizado';
    public const CODE_SELECAO_INCOMPLETA = 'selecao_incompleta';
    public const CODE_SEM_PAX_PARA_BUFFET = 'sem_pax_para_buffet';
    public const CODE_TURNO_JA_ABERTO = 'turno_ja_aberto';
    public const CODE_TURNO_NAO_ENCONTRADO = 'turno_nao_encontrado';
    public const CODE_TURNO_NAO_INICIADO = 'turno_nao_iniciado';
    public const CODE_TURNO_TEMATICO = 'turno_tematico';
    public const CODE_UH_INVALIDA = 'uh_invalida';

    public const MESSAGE_ACAO_TEMATICA_INVALIDA = 'Ação temática inválida para o buffet Corais.';
    public const MESSAGE_ACESSO_REGISTRADO = 'Acesso registrado com sucesso.';
    public const MESSAGE_ALERTA_DUPLICIDADE = 'Atenção: possível duplicidade em menos de 10 minutos.';
    public const MESSAGE_CAMPOS_OBRIGATORIOS = 'Preencha todos os campos obrigatórios.';
    public const MESSAGE_CANCELAMENTO_COM_ACESSOS = 'Não é possível cancelar o turno após registrar acessos.';
    public const MESSAGE_CANCELAMENTO_TEMATICO_BLOQUEADO = 'Não é possível cancelar o turno após confirmar reservas temáticas.';
    public const MESSAGE_CHECKLIST_OBRIGATORIO = 'Confirme o checklist para iniciar o turno.';
    public const MESSAGE_DUPLICIDADE_CONFIRMADA = 'Registro duplicado confirmado e salvo.';
    public const MESSAGE_DUPLICIDADE_IMEDIATA = 'Registro idêntico detectado em sequência. Confirme no popup para salvar mesmo assim.';
    public const MESSAGE_FALHA_REGISTRO = 'Falha ao registrar acesso.';
    public const MESSAGE_FORA_HORARIO = 'Atenção: acesso fora do horário da operação.';
    public const MESSAGE_LA_BRASA_FORA_ALMOCO = 'No La Brasa o registro é permitido apenas para almoço.';
    public const MESSAGE_NO_SHOW_TEMATICO_CONFIRMADO = 'Registro permitido por no-show temático confirmado.';
    public const MESSAGE_OPERACAO_INVALIDA = 'Operação inválida para este restaurante.';
    public const MESSAGE_OPERACAO_NAO_AUTORIZADA = 'Operação não autorizada para este usuário.';
    public const MESSAGE_PAX_REAL_TEMATICO_INVALIDO = 'PAX real inválido para ajuste da reserva temática.';
    public const MESSAGE_PORTA_INVALIDA = 'Porta inválida para o restaurante selecionado.';
    public const MESSAGE_PORTA_OBRIGATORIA = 'Selecione a porta.';
    public const MESSAGE_RESERVA_TEMATICA_AJUSTADA = 'Reserva temática ajustada.';
    public const MESSAGE_RESERVA_TEMATICA_MUDOU = 'A reserva temática mudou. Revise antes de continuar.';
    public const MESSAGE_RESERVA_TEMATICA_NO_SHOW = 'Reserva temática marcada como no-show.';
    public const MESSAGE_RESTAURANTE_NAO_AUTORIZADO = 'Restaurante não autorizado para este usuário.';
    public const MESSAGE_SELECAO_INCOMPLETA = 'Selecione restaurante e operação.';
    public const MESSAGE_SEM_PAX_PARA_BUFFET = 'Todos os PAX já foram para o temático. Não registre no buffet.';
    public const MESSAGE_TURNO_CANCELADO = 'Turno cancelado com sucesso.';
    public const MESSAGE_TURNO_ENCERRADO = 'Turno encerrado com sucesso.';
    public const MESSAGE_TURNO_FORA_HORARIO = 'Turno fora do horário. Confirme se deseja continuar.';
    public const MESSAGE_TURNO_INICIADO = 'Turno iniciado com sucesso.';
    public const MESSAGE_TURNO_JA_ABERTO = 'Já existe um turno aberto para este usuário.';
    public const MESSAGE_TURNO_NAO_ENCONTRADO = 'Nenhum turno aberto encontrado.';
    public const MESSAGE_TURNO_NAO_INICIADO = 'Inicie um turno para registrar acessos.';
    public const MESSAGE_TURNO_TEMATICO = 'Neste turno temático, confirme as reservas na tela de operação temática do Registro.';
    public const MESSAGE_UH_INVALIDA = 'UH inválida. Verifique o número do apartamento.';

    public const OPERATION_ALMOCO_KEYWORDS = ['almoço', 'almoco'];
    public const OPERATION_JANTAR_KEYWORD = 'jantar';
    public const RESTAURANT_CORAIS_KEYWORD = 'corais';
    public const RESTAURANT_LA_BRASA_KEYWORD = 'la brasa';

    public const STATUS_DUPLICADO = 'Duplicado';
    public const STATUS_FORA_HORARIO = 'Fora do Horário';
    public const STATUS_MULTIPLO_ACESSO = 'Múltiplo Acesso';
    public const STATUS_NO_SHOW = 'Nao compareceu';
    public const STATUS_NO_SHOW_NORMALIZED = ['não compareceu', 'nao compareceu'];
    public const STATUS_OK = 'OK';
    public const STATUS_TEMATICA_DIVERGENCIA = 'Divergencia';

    public const TECHNICAL_UHS = ['998', '999'];

    public const DEFAULT_ACCESS_LIST_LIMIT = 20;
    public const MIN_ACCESS_LIST_LIMIT = 1;
    public const MAX_ACCESS_LIST_LIMIT = 100;
    public const IMMEDIATE_DUPLICATE_WINDOW_MINUTES = 2;
    public const DUPLICATE_ALERT_WINDOW_MINUTES = 10;
    public const MULTIPLE_ACCESS_WINDOW_MINUTES = 15;
    public const SECONDS_PER_MINUTE = 60;

    public const SQL_TRUE = 1;
    public const SQL_FALSE = 0;
}
