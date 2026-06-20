<?php
declare(strict_types=1);

final class RegistrarAcessoCommand
{
    public array $turno;
    public int $usuarioId;
    public string $uhNumero;
    public int $paxInformado;
    public bool $confirmouDuplicidade;
    public bool $reservaTematicaJaProcessada;
    public string $acaoReservaTematica;
    public int $reservaTematicaId;
    public int $paxRealTematico;
    public bool $confirmouNoShowTematico;

    public function __construct(array $dados)
    {
        $this->turno = $dados['turno'] ?? [];
        $this->usuarioId = (int)($dados['usuario_id'] ?? 0);
        $this->uhNumero = trim((string)($dados['uh_numero'] ?? ''));
        $this->paxInformado = (int)($dados['pax'] ?? 0);
        $this->confirmouDuplicidade = !empty($dados['confirmou_duplicidade']);
        $this->reservaTematicaJaProcessada = !empty($dados['reserva_tematica_ja_processada']);
        $this->acaoReservaTematica = normalize_mojibake(trim((string)($dados['acao_reserva_tematica'] ?? '')));
        $this->reservaTematicaId = (int)($dados['reserva_tematica_id'] ?? 0);
        $this->paxRealTematico = (int)($dados['pax_real_tematico'] ?? -1);
        $this->confirmouNoShowTematico = !empty($dados['confirmou_no_show_tematico']);
    }
}
