<?php
declare(strict_types=1);

/**
 * Leitura única do recorte operacional compartilhado por dashboard, KPIs e relatórios.
 * Fonte canônica da lista de status filtráveis (antes triplicada entre os serviços).
 */
final class FiltroOperacionalService
{
    public const STATUS_FILTERS = ['duplicado', 'fora_horario', 'multiplo', 'ok', 'nao_informado', 'day_use'];

    /**
     * Sanitiza os filtros comuns (datas, restaurante, operação e status) vindos da query string.
     *
     * @return array{data: string, data_inicio: string, data_fim: string, restaurante_id: mixed, operacao_id: mixed, status: string}
     */
    public static function lerFiltrosBase(array $query): array
    {
        return [
            'data' => sanitize_date_param($query['data'] ?? ''),
            'data_inicio' => sanitize_date_param($query['data_inicio'] ?? ''),
            'data_fim' => sanitize_date_param($query['data_fim'] ?? ''),
            'restaurante_id' => sanitize_int_param($query['restaurante_id'] ?? ''),
            'operacao_id' => sanitize_int_param($query['operacao_id'] ?? ''),
            'status' => sanitize_enum_param($query['status'] ?? '', self::STATUS_FILTERS),
        ];
    }

    /**
     * Aplica a data padrão quando nenhum recorte de data (única ou período) foi informado.
     */
    public static function aplicarDataPadraoSeVazio(array $filters, string $dataPadrao): array
    {
        if (($filters['data'] ?? '') === '' && ($filters['data_inicio'] ?? '') === '' && ($filters['data_fim'] ?? '') === '') {
            $filters['data'] = $dataPadrao;
        }
        return $filters;
    }
}
