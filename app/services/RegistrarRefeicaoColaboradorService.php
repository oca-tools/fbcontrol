<?php
declare(strict_types=1);

final class RegistrarRefeicaoColaboradorService implements RegistrarRefeicaoColaboradorServiceInterface
{
    private RefeicaoColaboradorRepositoryInterface $refeicaoColaboradorRepository;

    public function __construct(?RefeicaoColaboradorRepositoryInterface $refeicaoColaboradorRepository = null)
    {
        $this->refeicaoColaboradorRepository = $refeicaoColaboradorRepository ?? new RefeicaoColaboradorRepository();
    }

    /**
     * Registra a refeicao operacional de um colaborador, vinculando consumo ao turno do Restaurante Corais.
     */
    public function executar(RegistrarRefeicaoColaboradorCommand $command): ServiceResult
    {
        if (!$this->turnoEstaAbertoParaRegistrarEquipe($command->turno)) {
            return ServiceResult::failure(
                ConsumosEVouchersConstants::CODE_TURNO_NAO_INICIADO,
                ConsumosEVouchersConstants::MESSAGE_TURNO_NAO_INICIADO
            );
        }

        if (!$this->turnoPertenceAoRestauranteCorais($command->turno)) {
            return ServiceResult::failure(
                ConsumosEVouchersConstants::CODE_RESTAURANTE_INVALIDO,
                ConsumosEVouchersConstants::MESSAGE_RESTAURANTE_INVALIDO
            );
        }

        if (!$this->lancamentoDaRefeicaoEstaCompleto($command)) {
            return ServiceResult::failure(
                ConsumosEVouchersConstants::CODE_DADOS_COLABORADOR_INVALIDOS,
                ConsumosEVouchersConstants::MESSAGE_DADOS_COLABORADOR_INVALIDOS
            );
        }

        if ($this->nomeIndicaMaisDeUmColaborador($command->nomeColaborador)) {
            return ServiceResult::failure(
                ConsumosEVouchersConstants::CODE_COLABORADOR_MULTIPLO,
                ConsumosEVouchersConstants::MESSAGE_COLABORADOR_MULTIPLO
            );
        }

        $refeicaoEquipe = [
            'turno_id' => (int)$command->turno['id'],
            'restaurante_id' => (int)$command->turno['restaurante_id'],
            'operacao_id' => (int)$command->turno['operacao_id'],
            'nome_colaborador' => $command->nomeColaborador,
            'quantidade' => $command->quantidadeRefeicoes,
        ];

        $registroId = $this->refeicaoColaboradorRepository->registrarRefeicao(
            $refeicaoEquipe,
            (int)($command->usuario['id'] ?? 0)
        );

        return ServiceResult::success(ConsumosEVouchersConstants::MESSAGE_REFEICAO_REGISTRADA, ['refeicao_id' => $registroId]);
    }

    private function turnoEstaAbertoParaRegistrarEquipe(array $turno): bool
    {
        return (int)($turno['id'] ?? 0) > 0;
    }

    private function turnoPertenceAoRestauranteCorais(array $turno): bool
    {
        return (string)($turno['restaurante'] ?? '') === ConsumosEVouchersConstants::RESTAURANTE_CORAIS;
    }

    private function lancamentoDaRefeicaoEstaCompleto(RegistrarRefeicaoColaboradorCommand $command): bool
    {
        return $command->nomeColaborador !== '' && $command->quantidadeRefeicoes > 0;
    }

    private function nomeIndicaMaisDeUmColaborador(string $nomeColaborador): bool
    {
        return preg_match('/[,;\/&+]|\s+e\s+|\r|\n/i', $nomeColaborador) === 1;
    }
}
