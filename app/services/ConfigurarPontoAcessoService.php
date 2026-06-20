<?php
declare(strict_types=1);

final class ConfigurarPontoAcessoService
{
    private EstruturaRestauranteRepositoryInterface $estruturaRestauranteRepository;

    public function __construct(?EstruturaRestauranteRepositoryInterface $estruturaRestauranteRepository = null)
    {
        $this->estruturaRestauranteRepository = $estruturaRestauranteRepository ?? new EstruturaRestauranteRepository();
    }

    /**
     * Configura as portas fisicas usadas pela equipe para registrar entradas no restaurante.
     */
    public function executar(ConfigurarPontoAcessoCommand $command): ServiceResult
    {
        if (!$this->pontoDeAcessoEstaCompleto($command)) {
            $mensagem = $command->acao === GestaoRestaurantesConstants::ACTION_CREATE
                ? GestaoRestaurantesConstants::MESSAGE_PONTO_ACESSO_OBRIGATORIO
                : GestaoRestaurantesConstants::MESSAGE_DADOS_INVALIDOS;
            return ServiceResult::failure(GestaoRestaurantesConstants::CODE_DADOS_PONTO_ACESSO_INVALIDOS, $mensagem);
        }

        $dadosPontoDeAcesso = [
            'nome' => $command->nomePontoDeAcesso,
            'restaurante_id' => $command->restauranteId,
            'ativo' => $command->pontoDeAcessoAtivo ? GestaoRestaurantesConstants::STATUS_ACTIVE : GestaoRestaurantesConstants::STATUS_INACTIVE,
        ];

        if ($this->estruturaRestauranteRepository->pontoDeAcessoDuplicado(
            $command->restauranteId,
            $command->nomePontoDeAcesso,
            $command->registroId
        )) {
            return ServiceResult::failure(
                GestaoRestaurantesConstants::CODE_PONTO_ACESSO_DUPLICADO,
                GestaoRestaurantesConstants::MESSAGE_PONTO_ACESSO_DUPLICADO
            );
        }

        if ($command->acao === GestaoRestaurantesConstants::ACTION_CREATE) {
            $pontoDeAcessoId = $this->estruturaRestauranteRepository->criarPontoDeAcesso($dadosPontoDeAcesso, $command->usuarioId);
            return ServiceResult::success(GestaoRestaurantesConstants::MESSAGE_PORTA_CADASTRADA, ['ponto_acesso_id' => $pontoDeAcessoId]);
        }

        if ($command->registroId <= 0) {
            return ServiceResult::failure(
                GestaoRestaurantesConstants::CODE_DADOS_PONTO_ACESSO_INVALIDOS,
                GestaoRestaurantesConstants::MESSAGE_DADOS_INVALIDOS
            );
        }

        $this->estruturaRestauranteRepository->atualizarPontoDeAcesso($command->registroId, $dadosPontoDeAcesso, $command->usuarioId);
        return ServiceResult::success(GestaoRestaurantesConstants::MESSAGE_PORTA_ATUALIZADA, ['ponto_acesso_id' => $command->registroId]);
    }

    private function pontoDeAcessoEstaCompleto(ConfigurarPontoAcessoCommand $command): bool
    {
        return $command->nomePontoDeAcesso !== '' && $command->restauranteId > 0;
    }
}
