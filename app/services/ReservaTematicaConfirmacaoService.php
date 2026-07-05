<?php
declare(strict_types=1);

final class ReservaTematicaConfirmacaoService
{
    private ReservaTematicaConfirmacaoRepository $repository;

    public function __construct(?ReservaTematicaConfirmacaoRepository $repository = null)
    {
        $this->repository = $repository ?? new ReservaTematicaConfirmacaoRepository();
    }

    public function confirmarPersistencia(CriarReservaCommand $command, ServiceResult $resultado): array
    {
        $payload = $resultado->payload();
        $ids = isset($payload['reservas_ids']) && is_array($payload['reservas_ids'])
            ? $payload['reservas_ids']
            : [$payload['reserva_id'] ?? 0];
        $rows = $this->repository->buscarReservasPorIds($ids);
        if ($rows === []) {
            return [];
        }

        $groupId = (int)($payload['grupo_id'] ?? $rows[0]['grupo_id'] ?? 0);
        $referenceId = $groupId > 0 ? $groupId : (int)$rows[0]['id'];
        $date = (string)$rows[0]['data_reserva'];
        $protocolDate = $groupId > 0 && !empty($rows[0]['grupo_data_reserva'])
            ? (string)$rows[0]['grupo_data_reserva']
            : $date;
        $isNew = $command->acao !== ReservasTematicasConstants::ACTION_UPDATE;

        return [
            'protocolo' => self::protocolo($groupId > 0 ? 'grupo' : 'reserva', $protocolDate, $referenceId),
            'correlation_id' => $command->correlationId,
            'nova_reserva' => $isNew,
            'formato' => $groupId > 0 ? 'grupo' : ($command->acao === ReservasTematicasConstants::ACTION_CREATE_PRE_RESERVATION ? 'pre_reserva' : 'individual'),
            'data_reserva' => $date,
            'restaurante' => normalize_mojibake((string)($rows[0]['restaurante'] ?? '')),
            'turno' => (string)($rows[0]['turno'] ?? ''),
            'titular' => normalize_mojibake((string)($rows[0]['grupo_responsavel'] ?: $rows[0]['titular_nome'] ?? '')),
            'uhs' => array_values(array_map(static fn(array $row): string => (string)($row['uh_numero'] ?? ''), $rows)),
            'pax_total' => array_sum(array_map(static fn(array $row): int => (int)($row['pax'] ?? 0), $rows)),
            'reservas_ids' => array_values(array_map(static fn(array $row): int => (int)$row['id'], $rows)),
            'grupo_id' => $groupId > 0 ? $groupId : null,
            'confirmado_em' => date('Y-m-d H:i:s'),
        ];
    }

    public function listarRecentes(int $usuarioId, int $limit = 8): array
    {
        $rows = $this->repository->listarRecentesDoUsuario($usuarioId, max(30, $limit * 8));
        $grouped = [];
        foreach ($rows as $row) {
            $groupId = (int)($row['grupo_id'] ?? 0);
            $key = $groupId > 0 ? 'g:' . $groupId : 'r:' . (int)$row['id'];
            if (!isset($grouped[$key])) {
                $referenceId = $groupId > 0 ? $groupId : (int)$row['id'];
                $protocolDate = $groupId > 0 && !empty($row['grupo_data_reserva'])
                    ? (string)$row['grupo_data_reserva']
                    : (string)$row['data_reserva'];
                $grouped[$key] = [
                    'protocolo' => self::protocolo($groupId > 0 ? 'grupo' : 'reserva', $protocolDate, $referenceId),
                    'formato' => $groupId > 0 ? 'grupo' : 'individual',
                    'data_reserva' => (string)$row['data_reserva'],
                    'restaurante' => normalize_mojibake((string)($row['restaurante'] ?? '')),
                    'turno' => (string)($row['turno'] ?? ''),
                    'titular' => normalize_mojibake((string)($row['grupo_responsavel'] ?: $row['titular_nome'] ?? '')),
                    'uhs' => [],
                    'pax_total' => 0,
                    'status' => (string)($row['status'] ?? ''),
                    'criado_em' => (string)($row['criado_em'] ?? ''),
                ];
            }
            $grouped[$key]['uhs'][] = (string)($row['uh_numero'] ?? '');
            $grouped[$key]['pax_total'] += (int)($row['pax'] ?? 0);
        }

        return array_slice(array_values($grouped), 0, max(1, $limit));
    }

    public function reconciliarData(string $data, array $restauranteIds): array
    {
        $rows = $this->repository->resumoDaData($data, $restauranteIds);
        $totals = ['reservas' => 0, 'grupos' => 0, 'pax' => 0, 'pendentes' => 0, 'ultimo_cadastro' => null];
        foreach ($rows as $row) {
            $totals['reservas'] += (int)$row['reservas'];
            $totals['grupos'] += (int)$row['grupos'];
            $totals['pax'] += (int)$row['pax'];
            $totals['pendentes'] += (int)$row['pendentes'];
            if (!empty($row['ultimo_cadastro']) && ($totals['ultimo_cadastro'] === null || $row['ultimo_cadastro'] > $totals['ultimo_cadastro'])) {
                $totals['ultimo_cadastro'] = $row['ultimo_cadastro'];
            }
        }
        return ['data' => $data, 'totais' => $totals, 'linhas' => $rows];
    }

    public static function protocolo(string $tipo, string $dataReserva, int $id): string
    {
        $prefix = $tipo === 'grupo' ? 'RG' : 'RT';
        return sprintf('%s-%s-%06d', $prefix, str_replace('-', '', $dataReserva), max(0, $id));
    }
}
