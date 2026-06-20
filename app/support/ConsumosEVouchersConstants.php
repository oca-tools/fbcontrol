<?php
declare(strict_types=1);

/**
 * Centraliza regras operacionais, limites e mensagens do modulo ConsumosEVouchers.
 */
final class ConsumosEVouchersConstants
{
    public const ROUTE_VOUCHERS_INDEX = '/?r=vouchers/index';
    public const ROUTE_ACCESS_INDEX = '/?r=access/index';

    public const VOUCHER_UPLOAD_PUBLIC_DIR = 'vouchers';
    public const VOUCHER_UPLOAD_ABSOLUTE_DIR = '/public/uploads/vouchers';
    public const VOUCHER_UPLOAD_PUBLIC_PATH = '/uploads/vouchers/';
    public const VOUCHER_FILE_PREFIX = 'voucher_';
    public const VOUCHER_DOWNLOAD_FALLBACK_NAME = 'voucher';
    public const VOUCHER_UPLOAD_LOG_PREFIX = '[voucher-upload] ';

    public const VOUCHER_TARGET_BYTES = 5242880;
    public const VOUCHER_RECEIVE_BYTES = 10485760;
    public const VOUCHER_STREAM_CHUNK_BYTES = 1048576;
    public const VOUCHER_IMAGE_MAX_SIDE = 2200;
    public const VOUCHER_IMAGE_QUALITIES = [82, 74, 66, 58];
    public const VOUCHER_JPEG_EXTENSION = 'jpg';
    public const UPLOAD_DIR_PERMISSIONS = 0755;
    public const RGB_WHITE = 255;
    public const MIN_POSITIVE_INT = 1;
    public const DEFAULT_ZERO = 0;

    public const VOUCHER_CATEGORY_COMPENSACAO = 'Compensação';
    public const VOUCHER_CATEGORY_CORTESIA = 'Cortesia';

    public const DEFAULT_DATA_ESTADIA = 'Não informado';
    public const DEFAULT_SERVICO_UPSELLING = 'Voucher anexado';
    public const DEFAULT_ASSINATURA = 'Operador';
    public const RESTAURANTE_CORAIS = 'Restaurante Corais';
    public const PRIVILEGED_NAME = 'Privileged';

    public const AUDIT_ACTION_CREATE = 'create';
    public const AUDIT_ENTITY_VOUCHERS = 'vouchers';
    public const AUDIT_ENTITY_COLABORADOR_REFEICOES = 'colaborador_refeicoes';

    public const CODE_POST_LIMIT_EXCEEDED = 'post_limit_exceeded';
    public const CODE_DATA_VENDA_OBRIGATORIA = 'data_venda_obrigatoria';
    public const CODE_DADOS_VOUCHER_INCOMPLETOS = 'dados_voucher_incompletos';
    public const CODE_RESTAURANTE_OPERACAO_INVALIDOS = 'restaurante_operacao_invalidos';
    public const CODE_COMPROVANTE_OBRIGATORIO = 'comprovante_obrigatorio';
    public const CODE_PHP_UPLOAD_ERROR = 'php_upload_error';
    public const CODE_UPLOAD_INVALIDO = 'upload_invalido';
    public const CODE_ANEXO_MUITO_GRANDE = 'anexo_muito_grande';
    public const CODE_FORMATO_INVALIDO = 'formato_invalido';
    public const CODE_VALIDACAO_INDISPONIVEL = 'validacao_indisponivel';
    public const CODE_CONTEUDO_INVALIDO = 'conteudo_invalido';
    public const CODE_PDF_MUITO_GRANDE = 'pdf_muito_grande';
    public const CODE_FALHA_COMPACTAR_IMAGEM = 'falha_compactar_imagem';
    public const CODE_FALHA_SALVAR_COMPROVANTE = 'falha_salvar_comprovante';
    public const CODE_PASTA_INDISPONIVEL = 'pasta_indisponivel';
    public const CODE_PASTA_SEM_PERMISSAO = 'pasta_sem_permissao';
    public const CODE_TURNO_NAO_INICIADO = 'turno_nao_iniciado';
    public const CODE_RESTAURANTE_INVALIDO = 'restaurante_invalido';
    public const CODE_DADOS_COLABORADOR_INVALIDOS = 'dados_colaborador_invalidos';
    public const CODE_COLABORADOR_MULTIPLO = 'colaborador_multiplo';

    public const UPLOAD_REASON_PHP_UPLOAD_ERROR = 'php_upload_error';
    public const UPLOAD_REASON_INVALID_TMP_FILE = 'invalid_tmp_file';
    public const UPLOAD_REASON_APP_LIMIT_EXCEEDED = 'app_limit_exceeded';
    public const UPLOAD_REASON_INVALID_EXTENSION = 'invalid_extension';
    public const UPLOAD_REASON_FILEINFO_UNAVAILABLE = 'fileinfo_unavailable';
    public const UPLOAD_REASON_INVALID_MIME = 'invalid_mime';
    public const UPLOAD_REASON_PDF_LIMIT_EXCEEDED = 'pdf_limit_exceeded';
    public const UPLOAD_REASON_IMAGE_COMPRESS_FAILED = 'image_compress_unavailable_or_failed';
    public const UPLOAD_REASON_IMAGICK_COMPRESS_FAILED = 'imagick_compress_failed';
    public const UPLOAD_REASON_MOVE_FAILED = 'move_uploaded_file_failed';
    public const UPLOAD_REASON_DIR_CREATE_FAILED = 'upload_dir_create_failed';
    public const UPLOAD_REASON_DIR_NOT_WRITABLE = 'upload_dir_not_writable';

    public const MESSAGE_POST_LIMIT_EXCEEDED = 'O anexo do voucher ultrapassa o limite de envio do servidor (%s). Envie uma imagem menor ou gere um PDF mais leve.';
    public const MESSAGE_VOUCHER_REGISTRADO = 'Voucher registrado com sucesso.';
    public const MESSAGE_CADASTRO_VALIDO = 'Cadastro de voucher válido.';
    public const MESSAGE_DATA_VENDA_OBRIGATORIA = 'Informe a data da venda do voucher.';
    public const MESSAGE_DADOS_VOUCHER_INCOMPLETOS = 'Informe nome do hóspede e localizador do voucher.';
    public const MESSAGE_RESTAURANTE_OPERACAO_INVALIDOS = 'Selecione restaurante e operação para o voucher.';
    public const MESSAGE_COMPROVANTE_OBRIGATORIO = 'Anexe o voucher para concluir o registro.';
    public const MESSAGE_UPLOAD_INVALIDO = 'Upload inválido.';
    public const MESSAGE_ANEXO_MUITO_GRANDE = 'Anexo muito grande. Máximo para envio: %s.';
    public const MESSAGE_FORMATO_INVALIDO = 'Formato inválido. Use JPG, PNG, WEBP ou PDF.';
    public const MESSAGE_VALIDACAO_INDISPONIVEL = 'Não foi possível validar o anexo no servidor. Avise o suporte.';
    public const MESSAGE_CONTEUDO_INVALIDO = 'Conteúdo de arquivo inválido para o formato enviado.';
    public const MESSAGE_ARQUIVO_VALIDADO = 'Arquivo validado.';
    public const MESSAGE_PDF_MUITO_GRANDE = 'PDF muito grande. Máximo %s. Gere um PDF mais leve ou envie imagem.';
    public const MESSAGE_IMAGEM_COMPACTADA = 'Imagem compactada.';
    public const MESSAGE_FALHA_COMPACTAR_IMAGEM = 'A imagem é maior que %s e o servidor não conseguiu compactar. Avise o suporte ou envie uma imagem menor.';
    public const MESSAGE_FALHA_SALVAR_COMPROVANTE = 'Não foi possível salvar o anexo do voucher.';
    public const MESSAGE_COMPROVANTE_ARMAZENADO = 'Comprovante armazenado.';
    public const MESSAGE_PASTA_INDISPONIVEL = 'Pasta de anexos de voucher indisponível. Avise o suporte.';
    public const MESSAGE_PASTA_SEM_PERMISSAO = 'Pasta de anexos de voucher sem permissão de gravação. Avise o suporte.';
    public const MESSAGE_PASTA_DISPONIVEL = 'Pasta de comprovantes disponível.';
    public const MESSAGE_UPLOAD_EXCEDE_LIMITE = 'O anexo do voucher excede o limite de envio (%s). Envie uma imagem menor ou gere um PDF mais leve.';
    public const MESSAGE_UPLOAD_PARCIAL = 'O anexo do voucher foi enviado parcialmente. Tente novamente.';
    public const MESSAGE_UPLOAD_SERVIDOR = 'Não foi possível receber o anexo do voucher no servidor. Avise o suporte.';
    public const MESSAGE_UPLOAD_FALHA = 'Falha ao enviar o anexo do voucher.';
    public const MESSAGE_ANEXO_NAO_ENCONTRADO = 'Anexo não encontrado.';
    public const MESSAGE_FORMATO_ANEXO_NAO_PERMITIDO = 'Formato de anexo não permitido.';
    public const MESSAGE_TOKEN_INVALIDO = 'Token inválido.';

    public const MESSAGE_TURNO_NAO_INICIADO = 'Inicie um turno para registrar colaboradores.';
    public const MESSAGE_RESTAURANTE_INVALIDO = 'Registro de colaboradores disponível apenas no Restaurante Corais.';
    public const MESSAGE_DADOS_COLABORADOR_INVALIDOS = 'Preencha o nome do colaborador e a quantidade de refeições.';
    public const MESSAGE_COLABORADOR_MULTIPLO = 'Informe apenas um colaborador por lançamento. Registre cada nome separadamente.';
    public const MESSAGE_REFEICAO_REGISTRADA = 'Refeição de colaborador registrada.';

    public const FLASH_DANGER = 'danger';
    public const FLASH_SUCCESS = 'success';
    public const FLASH_WARNING = 'warning';

    public const ALLOWED_VOUCHER_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    public const ALLOWED_ATTACHMENT_MIME_BY_EXTENSION = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];
    public const ALLOWED_MIME_BY_EXTENSION = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'pdf' => ['application/pdf'],
    ];
    public const ALLOWED_ATTACHMENT_MIMES = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
}
