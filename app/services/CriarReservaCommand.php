<?php
declare(strict_types=1);

final class CriarReservaCommand
{
    public string $acao;
    public int $usuarioId;
    public array $usuario;
    public bool $hostessForaDaJanela;
    public array $restaurantesPermitidos;
    public int $reservaId;
    public int $restauranteId;
    public string $dataReserva;
    public int $turnoId;
    public string $uhNumero;
    public string $titularNome;
    public string $grupoNome;
    public int $pax;
    public string $chdIdadesTexto;
    public string $observacaoReserva;
    public array $observacaoTags;
    public array $batchUhs;
    public array $batchPax;
    public array $batchChdIdades;
    public string $grupoResponsavel;

    /**
     * Normaliza os dados vindos da tela de reservas para o caso de uso de criação
     * ou edição, incluindo lote de UHs e composição de CHD.
     */
    public function __construct(array $dados)
    {
        $this->acao = (string)($dados['acao'] ?? ReservasTematicasConstants::ACTION_CREATE);
        $this->usuarioId = (int)($dados['usuario_id'] ?? 0);
        $this->usuario = $dados['usuario'] ?? [];
        $this->hostessForaDaJanela = !empty($dados['hostess_fora_da_janela']);
        $this->restaurantesPermitidos = $dados['restaurantes_permitidos'] ?? [];
        $this->reservaId = (int)($dados['reserva_id'] ?? 0);
        $this->restauranteId = (int)($dados['restaurante_id'] ?? 0);
        $this->dataReserva = (string)($dados['data_reserva'] ?? date('Y-m-d'));
        $this->turnoId = (int)($dados['turno_id'] ?? 0);
        $this->uhNumero = trim((string)($dados['uh_numero'] ?? ''));
        $this->titularNome = normalize_mojibake(trim((string)($dados['titular_nome'] ?? '')));
        $this->grupoNome = normalize_mojibake(trim((string)($dados['grupo_nome'] ?? '')));
        if (mb_strlen($this->grupoNome, 'UTF-8') > ReservasTematicasConstants::DEFAULT_GROUP_NAME_LIMIT) {
            $this->grupoNome = mb_substr($this->grupoNome, 0, ReservasTematicasConstants::DEFAULT_GROUP_NAME_LIMIT, 'UTF-8');
        }
        $this->pax = (int)($dados['pax'] ?? 0);
        $this->chdIdadesTexto = trim((string)($dados['chd_idades'] ?? ''));
        $this->observacaoReserva = trim((string)($dados['observacao_reserva'] ?? ''));
        $this->observacaoTags = is_array($dados['observacao_tags'] ?? null) ? $dados['observacao_tags'] : [];
        $this->batchUhs = is_array($dados['batch_uh_numero'] ?? null) ? $dados['batch_uh_numero'] : [];
        $this->batchPax = is_array($dados['batch_pax'] ?? null) ? $dados['batch_pax'] : [];
        $this->batchChdIdades = is_array($dados['batch_chd_idades'] ?? null) ? $dados['batch_chd_idades'] : [];
        $this->grupoResponsavel = normalize_mojibake(trim((string)($dados['grupo_responsavel'] ?? '')));
    }
}
