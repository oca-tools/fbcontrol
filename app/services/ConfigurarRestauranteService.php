<?php
declare(strict_types=1);

final class ConfigurarRestauranteService
{
    private EstruturaRestauranteRepositoryInterface $estruturaRestauranteRepository;

    public function __construct(?EstruturaRestauranteRepositoryInterface $estruturaRestauranteRepository = null)
    {
        $this->estruturaRestauranteRepository = $estruturaRestauranteRepository ?? new EstruturaRestauranteRepository();
    }

    /**
     * Configura restaurantes e operacoes de A&B que compoem a estrutura operacional do hotel.
     */
    public function executar(ConfigurarRestauranteCommand $command): ServiceResult
    {
        if ($command->tipoCadastro === GestaoRestaurantesConstants::REGISTER_TYPE_OPERACAO) {
            return $this->configurarOperacaoDaCasa($command);
        }

        return $this->configurarRestauranteDaCasa($command);
    }

    private function configurarRestauranteDaCasa(ConfigurarRestauranteCommand $command): ServiceResult
    {
        if (!$this->nomeDoCadastroFoiInformado($command->nome)) {
            return ServiceResult::failure(
                GestaoRestaurantesConstants::CODE_NOME_OBRIGATORIO,
                GestaoRestaurantesConstants::MESSAGE_NOME_OBRIGATORIO
            );
        }

        $dadosRestaurante = [
            'nome' => $command->nome,
            'tipo' => $this->normalizarTipoRestaurante($command->tipoRestaurante),
            'seleciona_porta_no_turno' => $command->restauranteSelecionaPortaNoTurno ? GestaoRestaurantesConstants::STATUS_ACTIVE : GestaoRestaurantesConstants::STATUS_INACTIVE,
            'exige_pax' => $command->restauranteExigePax ? GestaoRestaurantesConstants::STATUS_ACTIVE : GestaoRestaurantesConstants::STATUS_INACTIVE,
            'ativo' => $command->cadastroAtivo ? GestaoRestaurantesConstants::STATUS_ACTIVE : GestaoRestaurantesConstants::STATUS_INACTIVE,
        ];

        if ($this->cadastroEstaSendoCriado($command)) {
            $restauranteId = $this->estruturaRestauranteRepository->criarRestaurante($dadosRestaurante, $command->usuarioId);
            return ServiceResult::success(GestaoRestaurantesConstants::MESSAGE_RESTAURANTE_CADASTRADO, ['restaurante_id' => $restauranteId]);
        }

        if (!$this->registroExisteParaEdicao($command->registroId)) {
            return ServiceResult::failure(
                GestaoRestaurantesConstants::CODE_DADOS_INVALIDOS,
                GestaoRestaurantesConstants::MESSAGE_DADOS_INVALIDOS
            );
        }

        $this->estruturaRestauranteRepository->atualizarRestaurante($command->registroId, $dadosRestaurante, $command->usuarioId);
        return ServiceResult::success(GestaoRestaurantesConstants::MESSAGE_RESTAURANTE_ATUALIZADO, ['restaurante_id' => $command->registroId]);
    }

    private function configurarOperacaoDaCasa(ConfigurarRestauranteCommand $command): ServiceResult
    {
        if (!$this->nomeDoCadastroFoiInformado($command->nome)) {
            $mensagem = $this->cadastroEstaSendoCriado($command)
                ? GestaoRestaurantesConstants::MESSAGE_PREENCHA_NOME
                : GestaoRestaurantesConstants::MESSAGE_DADOS_INVALIDOS;
            return ServiceResult::failure(GestaoRestaurantesConstants::CODE_NOME_OBRIGATORIO, $mensagem);
        }

        $dadosOperacao = [
            'nome' => $command->nome,
            'ativo' => $command->cadastroAtivo ? GestaoRestaurantesConstants::STATUS_ACTIVE : GestaoRestaurantesConstants::STATUS_INACTIVE,
        ];

        if ($this->cadastroEstaSendoCriado($command)) {
            $operacaoId = $this->estruturaRestauranteRepository->criarOperacao($dadosOperacao, $command->usuarioId);
            return ServiceResult::success(GestaoRestaurantesConstants::MESSAGE_OPERACAO_CADASTRADA, ['operacao_id' => $operacaoId]);
        }

        if (!$this->registroExisteParaEdicao($command->registroId)) {
            return ServiceResult::failure(
                GestaoRestaurantesConstants::CODE_DADOS_INVALIDOS,
                GestaoRestaurantesConstants::MESSAGE_DADOS_INVALIDOS
            );
        }

        $this->estruturaRestauranteRepository->atualizarOperacao($command->registroId, $dadosOperacao, $command->usuarioId);
        return ServiceResult::success(GestaoRestaurantesConstants::MESSAGE_OPERACAO_ATUALIZADA, ['operacao_id' => $command->registroId]);
    }

    private function cadastroEstaSendoCriado(ConfigurarRestauranteCommand $command): bool
    {
        return $command->acao === GestaoRestaurantesConstants::ACTION_CREATE;
    }

    private function registroExisteParaEdicao(int $registroId): bool
    {
        return $registroId > 0;
    }

    private function nomeDoCadastroFoiInformado(string $nome): bool
    {
        return $nome !== '';
    }

    private function normalizarTipoRestaurante(string $tipoRestaurante): string
    {
        return in_array($tipoRestaurante, GestaoRestaurantesConstants::RESTAURANT_TYPES, true)
            ? $tipoRestaurante
            : GestaoRestaurantesConstants::RESTAURANT_TYPE_BUFFET;
    }
}
