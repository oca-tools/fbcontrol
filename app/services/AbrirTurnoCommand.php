<?php
declare(strict_types=1);

final class AbrirTurnoCommand
{
    public int $usuarioId;
    public string $perfilUsuario;
    public int $restauranteId;
    public int $operacaoId;
    public int $portaId;
    public bool $confirmouChecklist;
    public bool $confirmouForaDoHorario;
    public bool $modoDemo;
    public array $restaurantesPermitidos;
    public array $operacoesPermitidasPorRestaurante;
    public array $portasPorRestaurante;
    public bool $restringirLaBrasaAoAlmoco;

    public function __construct(array $dados)
    {
        $this->usuarioId = (int)($dados['usuario_id'] ?? 0);
        $this->perfilUsuario = (string)($dados['perfil_usuario'] ?? '');
        $this->restauranteId = (int)($dados['restaurante_id'] ?? 0);
        $this->operacaoId = (int)($dados['operacao_id'] ?? 0);
        $this->portaId = (int)($dados['porta_id'] ?? 0);
        $this->confirmouChecklist = !empty($dados['confirmou_checklist']);
        $this->confirmouForaDoHorario = !empty($dados['confirmou_fora_horario']);
        $this->modoDemo = !empty($dados['modo_demo']);
        $this->restaurantesPermitidos = $dados['restaurantes_permitidos'] ?? [];
        $this->operacoesPermitidasPorRestaurante = $dados['operacoes_permitidas_por_restaurante'] ?? [];
        $this->portasPorRestaurante = $dados['portas_por_restaurante'] ?? [];
        $this->restringirLaBrasaAoAlmoco = !empty($dados['restringir_la_brasa_ao_almoco']);
    }
}
