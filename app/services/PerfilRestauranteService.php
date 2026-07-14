<?php
declare(strict_types=1);

/**
 * Centraliza o perfil operacional de cada restaurante (modo temático e recorte de operações).
 * Lê as flags persistidas em `restaurantes`/`operacoes` (migration v3.3) e, quando o valor
 * ainda é NULL (banco não migrado ou cadastro novo), mantém as regras históricas por nome
 * via TematicAccessService — comportamento idêntico ao anterior.
 */
final class PerfilRestauranteService
{
    public const MODO_TEMATICO_NAO = 'nao';
    public const MODO_TEMATICO_SEMPRE = 'sempre';
    public const MODO_TEMATICO_POR_OPERACAO = 'por_operacao';

    public const FILTRO_OPERACAO_TODAS = 'todas';
    public const FILTRO_OPERACAO_BUFFET = 'buffet';
    public const FILTRO_OPERACAO_ALMOCO_TEMATICO = 'almoco_tematico';

    /** Identidade neutra para restaurantes sem cor/nome reconhecido. */
    private const IDENTIDADE_NEUTRA = ['cor' => '#6B6B6A', 'icone' => 'bi-shop'];

    private OperacaoReadModelRepository $operacoes;

    public function __construct(?OperacaoReadModelRepository $operacoes = null)
    {
        $this->operacoes = $operacoes ?? new OperacaoReadModelRepository();
    }

    /**
     * Identidade visual do restaurante (cor + ícone + tons derivados) para selos e cartões.
     * Usa as colunas `cor_hex`/`icone` (migration v3.5); quando NULL, deriva do nome.
     *
     * @param array|string|null $restaurante Linha do restaurante ou apenas o nome.
     * @return array{cor: string, icone: string, bg: string, texto: string}
     */
    public function identidade($restaurante): array
    {
        $rest = is_array($restaurante) ? $restaurante : ['nome' => (string)$restaurante];
        $cor = trim((string)($rest['cor_hex'] ?? ''));
        $icone = trim((string)($rest['icone'] ?? ''));

        if ($cor === '' || $icone === '') {
            $porNome = $this->identidadePorNome((string)($rest['nome'] ?? ''));
            $cor = $cor !== '' ? $cor : $porNome['cor'];
            $icone = $icone !== '' ? $icone : $porNome['icone'];
        }

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $cor)) {
            $cor = self::IDENTIDADE_NEUTRA['cor'];
        }

        return [
            'cor' => $cor,
            'icone' => $icone !== '' ? $icone : self::IDENTIDADE_NEUTRA['icone'],
            'bg' => $this->misturarComBranco($cor, 0.88),   // tom claro para fundo de selo
            'texto' => $this->escurecer($cor, 0.16),         // tom escuro para texto no selo
        ];
    }

    /**
     * Deriva a identidade pelo nome quando o cadastro ainda não tem cor/ícone.
     *
     * @return array{cor: string, icone: string}
     */
    private function identidadePorNome(string $nome): array
    {
        if (TematicAccessService::isCorais($nome)) {
            return ['cor' => '#2E7C9E', 'icone' => 'bi-water'];
        }
        if (TematicAccessService::isLaBrasa($nome)) {
            return ['cor' => '#C0433B', 'icone' => 'bi-fire'];
        }
        $flat = mb_strtolower(normalize_mojibake($nome), 'UTF-8');
        if (strpos($flat, 'giardino') !== false) {
            return ['cor' => '#4E8B3B', 'icone' => 'bi-flower1'];
        }
        if (strpos($flat, 'ix') !== false) {
            return ['cor' => '#6C5CB0', 'icone' => 'bi-star'];
        }
        if (strpos($flat, 'privileg') !== false) {
            return ['cor' => '#2E6FA8', 'icone' => 'bi-award'];
        }
        if (strpos($flat, 'vip') !== false) {
            return ['cor' => '#B07D2A', 'icone' => 'bi-gem'];
        }
        return self::IDENTIDADE_NEUTRA;
    }

    /**
     * Mistura a cor com branco (0..1 = quanto de branco), gerando um tom claro para fundo.
     */
    private function misturarComBranco(string $hex, float $branco): string
    {
        [$r, $g, $b] = $this->hexParaRgb($hex);
        $branco = max(0.0, min(1.0, $branco));
        $r = (int)round($r + (255 - $r) * $branco);
        $g = (int)round($g + (255 - $g) * $branco);
        $b = (int)round($b + (255 - $b) * $branco);
        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }

    /**
     * Escurece a cor (0..1 = quanto de preto) para garantir contraste do texto no selo.
     */
    private function escurecer(string $hex, float $preto): string
    {
        [$r, $g, $b] = $this->hexParaRgb($hex);
        $preto = max(0.0, min(1.0, $preto));
        $r = (int)round($r * (1 - $preto));
        $g = (int)round($g * (1 - $preto));
        $b = (int)round($b * (1 - $preto));
        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function hexParaRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        return [
            (int)hexdec(substr($hex, 0, 2)),
            (int)hexdec(substr($hex, 2, 2)),
            (int)hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Indica se o painel do restaurante deve exibir o filtro de operação.
     */
    public function permiteFiltroOperacao(?array $restaurante): bool
    {
        if (!$restaurante) {
            return false;
        }

        $flag = $restaurante['permite_filtro_operacao'] ?? null;
        if ($flag !== null && $flag !== '') {
            return (int)$flag === 1;
        }

        $nome = (string)($restaurante['nome'] ?? '');
        return TematicAccessService::isCorais($nome) || TematicAccessService::isLaBrasa($nome);
    }

    /**
     * Resolve qual recorte de operações o restaurante oferece nos filtros.
     */
    public function grupoFiltroOperacao(?array $restaurante): string
    {
        if (!$restaurante) {
            return self::FILTRO_OPERACAO_TODAS;
        }

        $grupo = (string)($restaurante['filtro_operacao_grupo'] ?? '');
        if (in_array($grupo, [self::FILTRO_OPERACAO_TODAS, self::FILTRO_OPERACAO_BUFFET, self::FILTRO_OPERACAO_ALMOCO_TEMATICO], true)) {
            return $grupo;
        }

        $nome = (string)($restaurante['nome'] ?? '');
        if (TematicAccessService::isCorais($nome)) {
            return self::FILTRO_OPERACAO_BUFFET;
        }
        if (TematicAccessService::isLaBrasa($nome)) {
            return self::FILTRO_OPERACAO_ALMOCO_TEMATICO;
        }
        return self::FILTRO_OPERACAO_TODAS;
    }

    /**
     * Resolve o modo temático do restaurante (flag configurada ou herdada do nome).
     */
    public function modoTematico(?array $restaurante): string
    {
        if (!$restaurante) {
            return self::MODO_TEMATICO_NAO;
        }

        $modo = (string)($restaurante['modo_tematico'] ?? '');
        if (in_array($modo, [self::MODO_TEMATICO_NAO, self::MODO_TEMATICO_SEMPRE, self::MODO_TEMATICO_POR_OPERACAO], true)) {
            return $modo;
        }

        $nome = (string)($restaurante['nome'] ?? '');
        if (TematicAccessService::isLaBrasa($nome)) {
            return self::MODO_TEMATICO_POR_OPERACAO;
        }
        if (TematicAccessService::isTematicRestaurant($nome)) {
            return self::MODO_TEMATICO_SEMPRE;
        }
        return self::MODO_TEMATICO_NAO;
    }

    /**
     * Indica se o painel do restaurante opera na leitura temática para a operação selecionada.
     */
    public function operaComoTematico(?array $restaurante, ?array $operacaoSelecionada): bool
    {
        $modo = $this->modoTematico($restaurante);
        if ($modo === self::MODO_TEMATICO_SEMPRE) {
            return true;
        }
        if ($modo === self::MODO_TEMATICO_POR_OPERACAO) {
            return $operacaoSelecionada !== null && $this->operacaoEhTematica($operacaoSelecionada);
        }
        return false;
    }

    /**
     * Indica se a operação pertence ao fluxo temático (flag configurada ou nome).
     */
    public function operacaoEhTematica(?array $operacao): bool
    {
        if (!$operacao) {
            return false;
        }

        $flag = $operacao['tematica'] ?? null;
        if ($flag !== null && $flag !== '') {
            return (int)$flag === 1;
        }

        return TematicAccessService::isTematicOperation((string)($operacao['nome'] ?? ''));
    }

    /**
     * Lista as operações oferecidas no filtro do dashboard geral (só o recorte buffet restringe).
     */
    public function operacoesParaFiltroGeral(?array $restaurante): array
    {
        if ($restaurante && $this->grupoFiltroOperacao($restaurante) === self::FILTRO_OPERACAO_BUFFET) {
            return $this->operacoes->listarOperacoesBuffet();
        }
        return $this->operacoes->listarOperacoes();
    }

    /**
     * Lista as operações oferecidas no painel individual do restaurante.
     */
    public function operacoesParaPainelRestaurante(?array $restaurante): array
    {
        $grupo = $this->grupoFiltroOperacao($restaurante);
        if ($grupo === self::FILTRO_OPERACAO_BUFFET) {
            return $this->operacoes->listarOperacoesBuffet();
        }
        if ($grupo === self::FILTRO_OPERACAO_ALMOCO_TEMATICO) {
            return array_values(array_filter($this->operacoes->listarOperacoes(), function (array $operacao): bool {
                $nome = mb_strtolower((string)($operacao['nome'] ?? ''), 'UTF-8');
                return strpos($nome, 'almoço') !== false
                    || strpos($nome, 'almoco') !== false
                    || $this->operacaoEhTematica($operacao);
            }));
        }
        return $this->operacoes->listarOperacoes();
    }
}
