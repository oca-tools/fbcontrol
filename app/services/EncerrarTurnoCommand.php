<?php
declare(strict_types=1);

final class EncerrarTurnoCommand
{
    public array $turno;
    public int $usuarioId;
    public bool $modoDemo;
    public bool $cancelamento;

    public function __construct(array $dados)
    {
        $this->turno = $dados['turno'] ?? [];
        $this->usuarioId = (int)($dados['usuario_id'] ?? 0);
        $this->modoDemo = !empty($dados['modo_demo']);
        $this->cancelamento = !empty($dados['cancelamento']);
    }
}
