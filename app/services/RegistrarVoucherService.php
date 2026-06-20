<?php
declare(strict_types=1);

final class RegistrarVoucherService implements RegistrarVoucherServiceInterface
{
    private VoucherRepositoryInterface $voucherRepository;

    public function __construct(?VoucherRepositoryInterface $voucherRepository = null)
    {
        $this->voucherRepository = $voucherRepository ?? new VoucherRepository();
    }

    /**
     * Registra um voucher operacional com comprovante anexado para conferencia de A&B e upselling.
     */
    public function executar(RegistrarVoucherCommand $command): ServiceResult
    {
        if ($this->requestExcedeLimiteDeEnvioDoServidor($command->server)) {
            $this->registrarFalhaUploadVoucher(ConsumosEVouchersConstants::CODE_POST_LIMIT_EXCEEDED, $command);
            return ServiceResult::failure(
                ConsumosEVouchersConstants::CODE_POST_LIMIT_EXCEEDED,
                sprintf(ConsumosEVouchersConstants::MESSAGE_POST_LIMIT_EXCEEDED, $this->limiteRecebimentoLabel())
            );
        }

        $dadosVoucher = $this->extrairDadosOperacionaisDoVoucher($command);
        $validacaoCadastro = $this->validarCadastroBasicoDoVoucher($dadosVoucher);
        if (!$validacaoCadastro->isSuccess()) {
            return $validacaoCadastro;
        }

        $resultadoAnexo = $this->armazenarComprovanteDoVoucher($command);
        if (!$resultadoAnexo->isSuccess()) {
            return $resultadoAnexo;
        }

        $dadosVoucher['voucher_anexo_path'] = (string)($resultadoAnexo->payload()['voucher_anexo_path'] ?? '');

        $voucherId = $this->voucherRepository->registrarVoucher(
            $dadosVoucher,
            (int)($command->usuario['id'] ?? 0)
        );

        return ServiceResult::success(ConsumosEVouchersConstants::MESSAGE_VOUCHER_REGISTRADO, ['voucher_id' => $voucherId]);
    }

    private function extrairDadosOperacionaisDoVoucher(RegistrarVoucherCommand $command): array
    {
        $usuario = $command->usuario;
        $turno = $command->turno;
        $post = $command->post;

        $restauranteId = (int)($post['restaurante_id'] ?? ($turno['restaurante_id'] ?? 0));
        $operacaoId = (int)($post['operacao_id'] ?? ($turno['operacao_id'] ?? 0));
        if (($usuario['perfil'] ?? '') === AppConstants::ROLE_HOSTESS) {
            $restauranteId = (int)($turno['restaurante_id'] ?? 0);
            $operacaoId = (int)($turno['operacao_id'] ?? 0);
        }

        $servicoVendido = $this->limparTextoCurto((string)($post['servico_upselling'] ?? ''));
        $responsavelPeloVoucher = $this->limparTextoCurto((string)($post['assinatura'] ?? ''));

        return [
            'turno_id' => (int)($turno['id'] ?? 0) > 0 ? (int)$turno['id'] : null,
            'restaurante_id' => $restauranteId,
            'operacao_id' => $operacaoId,
            'nome_hospede' => $this->limparTextoCurto((string)($post['nome_hospede'] ?? '')),
            'data_estadia' => trim((string)($post['data_estadia'] ?? '')) !== '' ? trim((string)$post['data_estadia']) : ConsumosEVouchersConstants::DEFAULT_DATA_ESTADIA,
            'numero_reserva' => $this->limparTextoCurto((string)($post['numero_reserva'] ?? '')),
            'servico_upselling' => $servicoVendido !== '' ? $servicoVendido : ConsumosEVouchersConstants::DEFAULT_SERVICO_UPSELLING,
            'assinatura' => $responsavelPeloVoucher !== '' ? $responsavelPeloVoucher : (string)($usuario['nome'] ?? ConsumosEVouchersConstants::DEFAULT_ASSINATURA),
            'data_venda' => trim((string)($post['data_venda'] ?? '')),
        ];
    }

    private function validarCadastroBasicoDoVoucher(array $dadosVoucher): ServiceResult
    {
        if ((string)$dadosVoucher['data_venda'] === '') {
            return ServiceResult::failure(
                ConsumosEVouchersConstants::CODE_DATA_VENDA_OBRIGATORIA,
                ConsumosEVouchersConstants::MESSAGE_DATA_VENDA_OBRIGATORIA
            );
        }

        if ((string)$dadosVoucher['nome_hospede'] === '' || (string)$dadosVoucher['numero_reserva'] === '') {
            return ServiceResult::failure(
                ConsumosEVouchersConstants::CODE_DADOS_VOUCHER_INCOMPLETOS,
                ConsumosEVouchersConstants::MESSAGE_DADOS_VOUCHER_INCOMPLETOS
            );
        }

        if ((int)$dadosVoucher['restaurante_id'] <= 0 || (int)$dadosVoucher['operacao_id'] <= 0) {
            return ServiceResult::failure(
                ConsumosEVouchersConstants::CODE_RESTAURANTE_OPERACAO_INVALIDOS,
                ConsumosEVouchersConstants::MESSAGE_RESTAURANTE_OPERACAO_INVALIDOS
            );
        }

        return ServiceResult::success(ConsumosEVouchersConstants::MESSAGE_CADASTRO_VALIDO);
    }

    private function armazenarComprovanteDoVoucher(RegistrarVoucherCommand $command): ServiceResult
    {
        $comprovanteVoucher = $command->files['voucher_anexo'] ?? null;
        if (!$this->comprovanteFoiEnviado($comprovanteVoucher)) {
            return ServiceResult::failure(
                ConsumosEVouchersConstants::CODE_COMPROVANTE_OBRIGATORIO,
                ConsumosEVouchersConstants::MESSAGE_COMPROVANTE_OBRIGATORIO
            );
        }

        $erroUpload = (int)($comprovanteVoucher['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($erroUpload !== UPLOAD_ERR_OK) {
            $this->registrarFalhaUploadVoucher(ConsumosEVouchersConstants::UPLOAD_REASON_PHP_UPLOAD_ERROR, $command, [
                'upload_error' => $erroUpload,
                'file_size' => (int)($comprovanteVoucher['size'] ?? 0),
            ]);
            return ServiceResult::failure(
                ConsumosEVouchersConstants::CODE_PHP_UPLOAD_ERROR,
                $this->mensagemErroUpload($erroUpload)
            );
        }

        if (!is_uploaded_file((string)($comprovanteVoucher['tmp_name'] ?? ''))) {
            $this->registrarFalhaUploadVoucher(ConsumosEVouchersConstants::UPLOAD_REASON_INVALID_TMP_FILE, $command, [
                'file_size' => (int)($comprovanteVoucher['size'] ?? 0),
            ]);
            return ServiceResult::failure(
                ConsumosEVouchersConstants::CODE_UPLOAD_INVALIDO,
                ConsumosEVouchersConstants::MESSAGE_UPLOAD_INVALIDO
            );
        }

        $validacaoArquivo = $this->validarArquivoRecebido($comprovanteVoucher, $command);
        if (!$validacaoArquivo->isSuccess()) {
            return $validacaoArquivo;
        }

        $dadosArquivo = $validacaoArquivo->payload();
        $extensaoFinal = (string)$dadosArquivo['extensao'];
        $arquivoTemporario = (string)$comprovanteVoucher['tmp_name'];

        if (
            (bool)$dadosArquivo['arquivo_e_imagem']
            && (int)$comprovanteVoucher['size'] > ConsumosEVouchersConstants::VOUCHER_TARGET_BYTES
        ) {
            $resultadoCompactacao = $this->compactarImagemDoVoucher($arquivoTemporario, $extensaoFinal, $command);
            if (!$resultadoCompactacao->isSuccess()) {
                return $resultadoCompactacao;
            }
        }

        $pastaAnexos = dirname(__DIR__, 2) . ConsumosEVouchersConstants::VOUCHER_UPLOAD_ABSOLUTE_DIR;
        $resultadoPasta = $this->garantirPastaDeComprovantes($pastaAnexos, $command);
        if (!$resultadoPasta->isSuccess()) {
            return $resultadoPasta;
        }

        $nomeArquivo = ConsumosEVouchersConstants::VOUCHER_FILE_PREFIX
            . date('Ymd_His')
            . '_'
            . (int)($command->usuario['id'] ?? ConsumosEVouchersConstants::DEFAULT_ZERO)
            . '_'
            . bin2hex(random_bytes(6))
            . '.'
            . $extensaoFinal;
        $destinoFinal = rtrim($pastaAnexos, '/\\') . '/' . $nomeArquivo;
        if (!move_uploaded_file($arquivoTemporario, $destinoFinal)) {
            $this->registrarFalhaUploadVoucher(ConsumosEVouchersConstants::UPLOAD_REASON_MOVE_FAILED, $command, [
                'upload_dir' => $pastaAnexos,
                'file_size' => (int)($comprovanteVoucher['size'] ?? 0),
            ]);
            return ServiceResult::failure(
                ConsumosEVouchersConstants::CODE_FALHA_SALVAR_COMPROVANTE,
                ConsumosEVouchersConstants::MESSAGE_FALHA_SALVAR_COMPROVANTE
            );
        }

        return ServiceResult::success(ConsumosEVouchersConstants::MESSAGE_COMPROVANTE_ARMAZENADO, [
            'voucher_anexo_path' => ConsumosEVouchersConstants::VOUCHER_UPLOAD_PUBLIC_PATH . $nomeArquivo,
        ]);
    }

    private function comprovanteFoiEnviado($comprovanteVoucher): bool
    {
        return is_array($comprovanteVoucher)
            && (int)($comprovanteVoucher['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    private function validarArquivoRecebido(array $comprovanteVoucher, RegistrarVoucherCommand $command): ServiceResult
    {
        $tamanhoArquivo = (int)($comprovanteVoucher['size'] ?? 0);
        if ($tamanhoArquivo > $this->limiteRecebimentoBytes()) {
            $this->registrarFalhaUploadVoucher(
                ConsumosEVouchersConstants::UPLOAD_REASON_APP_LIMIT_EXCEEDED,
                $command,
                ['file_size' => $tamanhoArquivo]
            );
            return ServiceResult::failure(
                ConsumosEVouchersConstants::CODE_ANEXO_MUITO_GRANDE,
                sprintf(ConsumosEVouchersConstants::MESSAGE_ANEXO_MUITO_GRANDE, $this->limiteRecebimentoLabel())
            );
        }

        $extensao = strtolower((string)pathinfo((string)($comprovanteVoucher['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extensao, ConsumosEVouchersConstants::ALLOWED_VOUCHER_EXTENSIONS, true)) {
            $this->registrarFalhaUploadVoucher(ConsumosEVouchersConstants::UPLOAD_REASON_INVALID_EXTENSION, $command, [
                'extension' => $extensao,
                'file_size' => $tamanhoArquivo,
            ]);
            return ServiceResult::failure(
                ConsumosEVouchersConstants::CODE_FORMATO_INVALIDO,
                ConsumosEVouchersConstants::MESSAGE_FORMATO_INVALIDO
            );
        }

        if (!class_exists('finfo')) {
            $this->registrarFalhaUploadVoucher(ConsumosEVouchersConstants::UPLOAD_REASON_FILEINFO_UNAVAILABLE, $command);
            return ServiceResult::failure(
                ConsumosEVouchersConstants::CODE_VALIDACAO_INDISPONIVEL,
                ConsumosEVouchersConstants::MESSAGE_VALIDACAO_INDISPONIVEL
            );
        }

        $mimeDetectado = (new finfo(FILEINFO_MIME_TYPE))->file((string)$comprovanteVoucher['tmp_name']) ?: '';
        if (!in_array($mimeDetectado, ConsumosEVouchersConstants::ALLOWED_MIME_BY_EXTENSION[$extensao] ?? [], true)) {
            $this->registrarFalhaUploadVoucher(ConsumosEVouchersConstants::UPLOAD_REASON_INVALID_MIME, $command, [
                'extension' => $extensao,
                'mime' => $mimeDetectado,
                'file_size' => $tamanhoArquivo,
            ]);
            return ServiceResult::failure(
                ConsumosEVouchersConstants::CODE_CONTEUDO_INVALIDO,
                ConsumosEVouchersConstants::MESSAGE_CONTEUDO_INVALIDO
            );
        }

        $arquivoEImagem = in_array($extensao, ConsumosEVouchersConstants::IMAGE_EXTENSIONS, true);
        if (!$arquivoEImagem && $tamanhoArquivo > ConsumosEVouchersConstants::VOUCHER_TARGET_BYTES) {
            $this->registrarFalhaUploadVoucher(
                ConsumosEVouchersConstants::UPLOAD_REASON_PDF_LIMIT_EXCEEDED,
                $command,
                ['file_size' => $tamanhoArquivo]
            );
            return ServiceResult::failure(
                ConsumosEVouchersConstants::CODE_PDF_MUITO_GRANDE,
                sprintf(ConsumosEVouchersConstants::MESSAGE_PDF_MUITO_GRANDE, $this->limiteAlvoLabel())
            );
        }

        return ServiceResult::success(ConsumosEVouchersConstants::MESSAGE_ARQUIVO_VALIDADO, [
            'extensao' => $extensao,
            'arquivo_e_imagem' => $arquivoEImagem,
        ]);
    }

    private function compactarImagemDoVoucher(string $arquivoTemporario, string &$extensaoFinal, RegistrarVoucherCommand $command): ServiceResult
    {
        if ($this->tentarCompactarComImagick($arquivoTemporario, $extensaoFinal, $command)) {
            return ServiceResult::success(ConsumosEVouchersConstants::MESSAGE_IMAGEM_COMPACTADA);
        }

        if ($this->tentarCompactarComGd($arquivoTemporario, $extensaoFinal)) {
            return ServiceResult::success(ConsumosEVouchersConstants::MESSAGE_IMAGEM_COMPACTADA);
        }

        $this->registrarFalhaUploadVoucher(ConsumosEVouchersConstants::UPLOAD_REASON_IMAGE_COMPRESS_FAILED, $command, [
            'file_size' => is_file($arquivoTemporario) ? (int)filesize($arquivoTemporario) : ConsumosEVouchersConstants::DEFAULT_ZERO,
        ]);

        return ServiceResult::failure(
            ConsumosEVouchersConstants::CODE_FALHA_COMPACTAR_IMAGEM,
            sprintf(ConsumosEVouchersConstants::MESSAGE_FALHA_COMPACTAR_IMAGEM, $this->limiteAlvoLabel())
        );
    }

    private function tentarCompactarComImagick(string $arquivoTemporario, string &$extensaoFinal, RegistrarVoucherCommand $command): bool
    {
        if (!class_exists('Imagick') || !is_file($arquivoTemporario)) {
            return false;
        }

        try {
            $imagemVoucher = new Imagick($arquivoTemporario);
            if ($imagemVoucher->getNumberImages() > 1) {
                $imagemVoucher = $imagemVoucher->coalesceImages();
                $imagemVoucher->setIteratorIndex(0);
            }
            $imagemVoucher->setImageColorspace(Imagick::COLORSPACE_SRGB);
            $imagemVoucher->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
            $imagemVoucher->setImageBackgroundColor('white');
            $imagemVoucher->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

            $geometry = $imagemVoucher->getImageGeometry();
            $maiorLado = max((int)($geometry['width'] ?? 0), (int)($geometry['height'] ?? 0));
            if ($maiorLado > ConsumosEVouchersConstants::VOUCHER_IMAGE_MAX_SIDE) {
                $imagemVoucher->thumbnailImage(
                    ConsumosEVouchersConstants::VOUCHER_IMAGE_MAX_SIDE,
                    ConsumosEVouchersConstants::VOUCHER_IMAGE_MAX_SIDE,
                    true,
                    true
                );
            }

            foreach (ConsumosEVouchersConstants::VOUCHER_IMAGE_QUALITIES as $qualidadeDoComprovante) {
                $imagemVoucher->setImageFormat('jpeg');
                $imagemVoucher->setImageCompressionQuality($qualidadeDoComprovante);
                $imagemVoucher->stripImage();
                $imagemVoucher->writeImage($arquivoTemporario);
                clearstatcache(true, $arquivoTemporario);
                if ((int)filesize($arquivoTemporario) <= ConsumosEVouchersConstants::VOUCHER_TARGET_BYTES) {
                    $imagemVoucher->clear();
                    $imagemVoucher->destroy();
                    $extensaoFinal = ConsumosEVouchersConstants::VOUCHER_JPEG_EXTENSION;
                    return true;
                }
            }

            $imagemVoucher->clear();
            $imagemVoucher->destroy();
        } catch (Throwable $e) {
            $this->registrarFalhaUploadVoucher(
                ConsumosEVouchersConstants::UPLOAD_REASON_IMAGICK_COMPRESS_FAILED,
                $command,
                ['error' => $e->getMessage()]
            );
        }

        return false;
    }

    private function tentarCompactarComGd(string $arquivoTemporario, string &$extensaoFinal): bool
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
            return false;
        }

        $bytesOriginais = @file_get_contents($arquivoTemporario);
        if ($bytesOriginais === false) {
            return false;
        }

        $imagemOriginal = @imagecreatefromstring($bytesOriginais);
        if (!$imagemOriginal) {
            return false;
        }

        $larguraOriginal = imagesx($imagemOriginal);
        $alturaOriginal = imagesy($imagemOriginal);
        $escala = min(
            ConsumosEVouchersConstants::MIN_POSITIVE_INT,
            ConsumosEVouchersConstants::VOUCHER_IMAGE_MAX_SIDE / max(ConsumosEVouchersConstants::MIN_POSITIVE_INT, $larguraOriginal, $alturaOriginal)
        );
        $larguraFinal = max(ConsumosEVouchersConstants::MIN_POSITIVE_INT, (int)round($larguraOriginal * $escala));
        $alturaFinal = max(ConsumosEVouchersConstants::MIN_POSITIVE_INT, (int)round($alturaOriginal * $escala));

        $canvasVoucher = imagecreatetruecolor($larguraFinal, $alturaFinal);
        $fundoBranco = imagecolorallocate(
            $canvasVoucher,
            ConsumosEVouchersConstants::RGB_WHITE,
            ConsumosEVouchersConstants::RGB_WHITE,
            ConsumosEVouchersConstants::RGB_WHITE
        );
        imagefill($canvasVoucher, ConsumosEVouchersConstants::DEFAULT_ZERO, ConsumosEVouchersConstants::DEFAULT_ZERO, $fundoBranco);
        imagecopyresampled(
            $canvasVoucher,
            $imagemOriginal,
            ConsumosEVouchersConstants::DEFAULT_ZERO,
            ConsumosEVouchersConstants::DEFAULT_ZERO,
            ConsumosEVouchersConstants::DEFAULT_ZERO,
            ConsumosEVouchersConstants::DEFAULT_ZERO,
            $larguraFinal,
            $alturaFinal,
            $larguraOriginal,
            $alturaOriginal
        );

        $ficouDentroDoLimite = false;
        foreach (ConsumosEVouchersConstants::VOUCHER_IMAGE_QUALITIES as $qualidadeDoComprovante) {
            if (@imagejpeg($canvasVoucher, $arquivoTemporario, $qualidadeDoComprovante)) {
                clearstatcache(true, $arquivoTemporario);
                if ((int)filesize($arquivoTemporario) <= ConsumosEVouchersConstants::VOUCHER_TARGET_BYTES) {
                    $ficouDentroDoLimite = true;
                    break;
                }
            }
        }

        imagedestroy($canvasVoucher);
        imagedestroy($imagemOriginal);

        if ($ficouDentroDoLimite) {
            $extensaoFinal = ConsumosEVouchersConstants::VOUCHER_JPEG_EXTENSION;
        }

        return $ficouDentroDoLimite;
    }

    private function garantirPastaDeComprovantes(string $pastaAnexos, RegistrarVoucherCommand $command): ServiceResult
    {
        if (!is_dir($pastaAnexos) && !mkdir($pastaAnexos, ConsumosEVouchersConstants::UPLOAD_DIR_PERMISSIONS, true) && !is_dir($pastaAnexos)) {
            $this->registrarFalhaUploadVoucher(ConsumosEVouchersConstants::UPLOAD_REASON_DIR_CREATE_FAILED, $command, ['upload_dir' => $pastaAnexos]);
            return ServiceResult::failure(
                ConsumosEVouchersConstants::CODE_PASTA_INDISPONIVEL,
                ConsumosEVouchersConstants::MESSAGE_PASTA_INDISPONIVEL
            );
        }

        if (!is_writable($pastaAnexos)) {
            $this->registrarFalhaUploadVoucher(ConsumosEVouchersConstants::UPLOAD_REASON_DIR_NOT_WRITABLE, $command, ['upload_dir' => $pastaAnexos]);
            return ServiceResult::failure(
                ConsumosEVouchersConstants::CODE_PASTA_SEM_PERMISSAO,
                ConsumosEVouchersConstants::MESSAGE_PASTA_SEM_PERMISSAO
            );
        }

        return ServiceResult::success(ConsumosEVouchersConstants::MESSAGE_PASTA_DISPONIVEL);
    }

    private function requestExcedeLimiteDeEnvioDoServidor(array $server): bool
    {
        $tamanhoDoPost = (int)($server['CONTENT_LENGTH'] ?? 0);
        $limitePostServidor = ini_size_to_bytes((string)ini_get('post_max_size'));
        return $tamanhoDoPost > 0
            && $limitePostServidor > 0
            && $limitePostServidor < PHP_INT_MAX
            && $tamanhoDoPost > $limitePostServidor;
    }

    private function mensagemErroUpload(int $erroUpload): string
    {
        $limite = $this->limiteRecebimentoLabel();
        switch ($erroUpload) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return sprintf(ConsumosEVouchersConstants::MESSAGE_UPLOAD_EXCEDE_LIMITE, $limite);
            case UPLOAD_ERR_PARTIAL:
                return ConsumosEVouchersConstants::MESSAGE_UPLOAD_PARCIAL;
            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
            case UPLOAD_ERR_EXTENSION:
                return ConsumosEVouchersConstants::MESSAGE_UPLOAD_SERVIDOR;
            default:
                return ConsumosEVouchersConstants::MESSAGE_UPLOAD_FALHA;
        }
    }

    private function registrarFalhaUploadVoucher(string $motivo, RegistrarVoucherCommand $command, array $contexto = []): void
    {
        $dadosBase = [
            'user_id' => $command->usuario['id'] ?? null,
            'reason' => $motivo,
            'content_length' => (int)($command->server['CONTENT_LENGTH'] ?? 0),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'receive_limit' => $this->limiteRecebimentoLabel(),
            'target_limit' => $this->limiteAlvoLabel(),
        ];

        error_log(ConsumosEVouchersConstants::VOUCHER_UPLOAD_LOG_PREFIX . json_encode(array_merge($dadosBase, $contexto), JSON_UNESCAPED_UNICODE));
    }

    private function limiteRecebimentoBytes(): int
    {
        return upload_limit_bytes(ConsumosEVouchersConstants::VOUCHER_RECEIVE_BYTES);
    }

    private function limiteRecebimentoLabel(): string
    {
        return format_bytes_ptbr($this->limiteRecebimentoBytes());
    }

    private function limiteAlvoLabel(): string
    {
        return format_bytes_ptbr(ConsumosEVouchersConstants::VOUCHER_TARGET_BYTES);
    }

    private function limparTextoCurto(string $valor): string
    {
        return trim(strip_tags($valor));
    }
}
