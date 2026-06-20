<?php
declare(strict_types=1);

final class RegistrarVoucherCommand
{
    public array $usuario;
    public array $turno;
    public array $post;
    public array $files;
    public array $server;

    /**
     * Transporta dados de formulario, anexos e contexto do turno para registrar o voucher.
     */
    public function __construct(array $dados)
    {
        $this->usuario = $dados['usuario'] ?? [];
        $this->turno = $dados['turno'] ?? [];
        $this->post = $dados['post'] ?? [];
        $this->files = $dados['files'] ?? [];
        $this->server = $dados['server'] ?? [];
    }
}
