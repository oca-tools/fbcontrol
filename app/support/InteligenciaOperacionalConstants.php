<?php
declare(strict_types=1);

final class InteligenciaOperacionalConstants
{
    public const FORMAT_CSV = 'csv';
    public const FORMAT_XLSX = 'xlsx';
    public const FORMAT_EXCEL = 'excel';
    public const FORMAT_PDF = 'pdf';
    public const FORMAT_ZIP = 'zip';

    public const PERIOD_TODAY = 'hoje';
    public const PERIOD_MTD = 'MTD';
    public const PERIOD_YTD = 'YTD';

    public const ROUTE_DASHBOARD_INDEX = '/?r=dashboard/index';
    public const ROUTE_KPIS_INDEX = '/?r=kpis/index';
    public const ROUTE_RELATORIOS_INDEX = '/?r=relatorios/index';
    public const ROUTE_EMAIL_RELATORIOS_INDEX = '/?r=emailRelatorios/index';

    public const FLASH_DANGER = 'danger';
    public const FLASH_SUCCESS = 'success';
    public const FLASH_WARNING = 'warning';

    public const MESSAGE_TOKEN_INVALIDO = 'Token inválido.';
    public const MESSAGE_DATA_OCUPACAO_INVALIDA = 'Data da ocupação inválida.';
    public const MESSAGE_OCUPACAO_OBRIGATORIA = 'Informe ao menos um campo de ocupação (UH ou PAX).';
    public const MESSAGE_OCUPACAO_SALVA = 'Ocupação diária salva com sucesso.';
    public const MESSAGE_OCUPACAO_NAO_SALVA = 'Não foi possível salvar a ocupação diária.';
    public const MESSAGE_CONFIG_SALVA = 'Configuração salva.';
    public const MESSAGE_EMAIL_INVALIDO = 'Informe um e-mail válido.';
    public const MESSAGE_REMETENTE_INVALIDO = 'E-mail do remetente inválido.';
    public const MESSAGE_HORA_INVALIDA = 'Hora inválida. Use HH:MM.';
    public const MESSAGE_DESTINATARIO_INVALIDO = 'Destinatário inválido.';
    public const MESSAGE_DESTINATARIO_ADICIONADO = 'Destinatário adicionado.';
    public const MESSAGE_DESTINATARIO_REMOVIDO = 'Destinatário removido.';
    public const MESSAGE_PREFERENCIA_ANEXO_ATUALIZADA = 'Preferência de anexo atualizada.';
    public const MESSAGE_FALHA_ENVIO = 'Falha no envio.';
    public const MESSAGE_INTERVALO_VOUCHER_INVALIDO = 'Informe um intervalo de datas válido para baixar os PDFs dos vouchers.';
    public const MESSAGE_SEM_PDFS_VOUCHERS = 'Não há PDFs de vouchers para o período selecionado.';
    public const MESSAGE_IMAGICK_INDISPONIVEL = 'Há imagens de vouchers, mas a extensão Imagick não está disponível para convertê-las em PDF.';
    public const MESSAGE_IMAGEM_VOUCHER_NAO_CONVERTIDA = 'Há imagens de vouchers, mas não foi possível convertê-las em PDF.';
    public const MESSAGE_ZIP_PREPARO_FALHOU = 'Não foi possível preparar o arquivo ZIP dos vouchers.';
    public const MESSAGE_ZIP_CRIACAO_FALHOU = 'Não foi possível criar o arquivo ZIP dos vouchers.';
    public const MESSAGE_ZIP_FINALIZACAO_FALHOU = 'Não foi possível finalizar o arquivo ZIP dos vouchers.';
    public const MESSAGE_ARQUIVO_NAO_ENCONTRADO = 'Arquivo não encontrado.';

    public const DEFAULT_REPORT_PAGE_SIZE = 20;
    public const DEFAULT_RECENT_LIMIT = 15;
    public const DEFAULT_KPI_RANGE_DAYS = 6;
    public const DEFAULT_OCCUPANCY_HISTORY_LIMIT = 120;
    public const DEFAULT_OCCUPANCY_MODEL_HISTORY_LIMIT = 62;
    public const DEFAULT_EMAIL_LOG_LIMIT = 30;
    public const DEFAULT_EMAIL_SEND_TIME = '23:00:00';
    public const DEFAULT_EMAIL_SEND_TIME_SHORT = '23:00';
    public const DEFAULT_DAILY_REPORT_SUBJECT = 'Resumo diário A&B - {data}';
    public const DEFAULT_DAILY_REPORT_SUBJECT_FALLBACK = 'Resumo diário A&B';
    public const DEFAULT_EMAIL_SENDER_NAME = 'FBControl';
    public const MAX_VOUCHER_EMAIL_ATTACHMENTS = 20;

    public const EXPORT_KPIS_TENDENCIA_FILENAME = 'kpis_tendencia';
    public const EXPORT_KPIS_TENDENCIA_TITLE = 'Tendencia diaria de KPIs';
    public const EXPORT_KPIS_TENDENCIA_SUBTITLE = 'Serie historica exportada para analise externa.';
    public const EXPORT_KPIS_TENDENCIA_SHEET = 'KPIs';
    public const HEADERS_KPIS_TENDENCIA = ['data', 'registros', 'pax_total', 'uhs_unicas', 'duplicados', 'fora_horario'];

    public const EXPORT_RELATORIO_ACESSOS_FILENAME = 'relatorio_acessos';
    public const EXPORT_MAPA_DIARIO_UH_FILENAME = 'mapa_diario_uh';
    public const EXPORT_BASE_BI_FILENAME = 'base_bi';
    public const EXPORT_COLABORADORES_FILENAME = 'colaboradores_refeicoes';
    public const EXPORT_VOUCHERS_FILENAME = 'vouchers_registrados';
    public const EXPORT_VOUCHER_PDFS_FILENAME_PREFIX = 'vouchers_pdfs_';

    public const HEADERS_RELATORIO_ACESSOS = [
        'tipo_registro', 'data_hora', 'uh', 'pax', 'restaurante', 'operacao', 'porta', 'usuario',
        'duplicado', 'fora_horario', 'colaborador', 'qtd_refeicoes',
        'hospede', 'data_estadia', 'numero_reserva', 'servico_upselling', 'assinatura', 'data_venda',
    ];
    public const HEADERS_MAPA_DIARIO_UH = ['uh', 'cafe', 'almoco', 'jantar', 'tematico', 'privileged', 'vip_premium'];
    public const HEADERS_BI_MULTIPLO = [
        'status', 'uh', 'primeira_passagem', 'ultima_passagem', 'acessos', 'pax_total', 'pax_min',
        'pax_max', 'dias', 'restaurantes', 'operacoes', 'portas', 'usuarios',
    ];
    public const HEADERS_BI_DETALHADO = ['status', 'data_hora', 'uh', 'pax', 'restaurante', 'operacao', 'porta', 'usuario'];
    public const HEADERS_COLABORADORES = ['data_hora', 'colaborador', 'quantidade', 'restaurante', 'operacao', 'usuario'];
    public const HEADERS_VOUCHERS = [
        'data_hora', 'hospede', 'estadia', 'reserva', 'servico', 'assinatura', 'data_venda',
        'anexo_registrado', 'restaurante', 'operacao', 'usuario',
    ];

    public const EMAIL_HEADER_MIME = 'MIME-Version: 1.0';
    public const EMAIL_HEADER_HTML = 'Content-Type: text/html; charset=UTF-8';
    public const EMAIL_HEADER_TEXT = 'Content-Type: text/plain; charset=UTF-8';
    public const EMAIL_HEADER_TRANSFER_QUOTED = 'Content-Transfer-Encoding: quoted-printable';
    public const EMAIL_HEADER_MAILER_PREFIX = 'X-Mailer: PHP/';

    public const AUDIT_EXPORT_KPIS_TENDENCIA = 'export_kpis_tendencia';
}
