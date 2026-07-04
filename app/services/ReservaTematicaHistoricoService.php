<?php

declare(strict_types=1);

/**
 * Converte a trilha técnica de reservas temáticas em uma linha do tempo operacional legível.
 */
final class ReservaTematicaHistoricoService
{
    private ReservaTematicaModel $reservas;
    private ReservaTematicaLogModel $logs;
    private array $cache = [];

    private const FIELDS = [
        'data_reserva' => ['label' => 'Data da reserva', 'type' => 'date'],
        'restaurante_id' => ['label' => 'Restaurante', 'type' => 'restaurant'],
        'turno_id' => ['label' => 'Turno', 'type' => 'shift'],
        'uh_id' => ['label' => 'UH', 'type' => 'unit'],
        'titular_nome' => ['label' => 'Titular', 'type' => 'text'],
        'grupo_nome' => ['label' => 'Grupo', 'type' => 'text'],
        'pax' => ['label' => 'PAX reservada', 'type' => 'number'],
        'pax_adulto' => ['label' => 'PAX adulto', 'type' => 'number'],
        'pax_chd' => ['label' => 'PAX CHD', 'type' => 'number'],
        'qtd_chd' => ['label' => 'Quantidade de crianças', 'type' => 'number'],
        'pax_real' => ['label' => 'PAX real', 'type' => 'number'],
        'status' => ['label' => 'Status', 'type' => 'status'],
        'observacao_reserva' => ['label' => 'Observação da reserva', 'type' => 'text'],
        'observacao_tags' => ['label' => 'Marcadores', 'type' => 'text'],
        'observacao_operacao' => ['label' => 'Observação operacional', 'type' => 'text'],
    ];

    public function __construct(?ReservaTematicaModel $reservas = null, ?ReservaTematicaLogModel $logs = null)
    {
        $this->reservas = $reservas ?? new ReservaTematicaModel();
        $this->logs = $logs ?? new ReservaTematicaLogModel();
    }

    public function obter(int $reservaId): ?array
    {
        $reserva = $this->reservas->find($reservaId);
        if (!$reserva) {
            return null;
        }

        $eventos = [];
        $temCriacao = false;
        foreach ($this->logs->historyByReservation($reservaId) as $log) {
            $antes = $this->decodeSnapshot($log['dados_antes'] ?? null);
            $depois = $this->decodeSnapshot($log['dados_depois'] ?? null);
            $acao = strtolower(trim((string)($log['acao'] ?? '')));
            $temCriacao = $temCriacao || $acao === 'create';
            $eventos[] = [
                'id' => (int)($log['id'] ?? 0),
                'acao' => $acao,
                'titulo' => $this->actionLabel($acao),
                'usuario' => normalize_mojibake((string)($log['usuario_nome'] ?? 'Usuário não identificado')),
                'criado_em' => (string)($log['criado_em'] ?? ''),
                'criado_em_formatado' => $this->formatDateTime((string)($log['criado_em'] ?? '')),
                'justificativa' => normalize_mojibake(trim((string)($log['justificativa'] ?? ''))),
                'alteracoes' => $this->buildChanges($acao, $antes, $depois),
            ];
        }

        return [
            'reserva_id' => $reservaId,
            'origem' => $temCriacao ? 'nova' : 'historico_incompleto',
            'origem_label' => $temCriacao
                ? 'Criação original registrada'
                : 'Histórico anterior incompleto',
            'origem_mensagem' => $temCriacao
                ? 'A trilha contém o evento de criação desta reserva.'
                : 'Não há evento de criação disponível. A reserva pode ser anterior à implantação completa da auditoria.',
            'reserva' => [
                'uh' => $this->formatValue('unit', $reserva['uh_id'] ?? null),
                'data' => $this->formatValue('date', $reserva['data_reserva'] ?? null),
                'restaurante' => $this->formatValue('restaurant', $reserva['restaurante_id'] ?? null),
                'turno' => $this->formatValue('shift', $reserva['turno_id'] ?? null),
                'titular' => normalize_mojibake(trim((string)($reserva['titular_nome'] ?? ''))),
                'status' => normalize_mojibake(trim((string)($reserva['status'] ?? ''))),
            ],
            'eventos' => $eventos,
        ];
    }

    private function buildChanges(string $acao, array $antes, array $depois): array
    {
        $changes = [];
        foreach (self::FIELDS as $field => $config) {
            $oldExists = array_key_exists($field, $antes);
            $newExists = array_key_exists($field, $depois);
            if (!$oldExists && !$newExists) {
                continue;
            }

            $old = $oldExists ? $antes[$field] : null;
            $new = $newExists ? $depois[$field] : null;
            if ($acao !== 'create' && $this->valuesEqual($old, $new)) {
                continue;
            }
            if ($acao === 'create' && $this->isEmptyValue($new)) {
                continue;
            }

            $changes[] = [
                'campo' => $field,
                'label' => $config['label'],
                'antes' => $acao === 'create' ? '' : $this->formatValue($config['type'], $old),
                'depois' => $this->formatValue($config['type'], $new),
                'criacao' => $acao === 'create',
            ];
        }
        return $changes;
    }

    private function decodeSnapshot($value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function valuesEqual($old, $new): bool
    {
        return (string)($old ?? '') === (string)($new ?? '');
    }

    private function isEmptyValue($value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    private function actionLabel(string $action): string
    {
        $labels = [
            'create' => 'Reserva criada',
            'update' => 'Reserva editada',
            'status' => 'Status ou operação atualizados',
            'cancel' => 'Reserva cancelada',
            'cancelar' => 'Reserva cancelada',
            'delete' => 'Reserva excluída',
            'excluir' => 'Reserva excluída',
            'auto_no_show' => 'No-show automático',
        ];
        return $labels[$action] ?? ('Alteração registrada: ' . ($action !== '' ? $action : 'evento'));
    }

    private function formatValue(string $type, $value): string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return 'Não informado';
        }

        if ($type === 'date') {
            $timestamp = strtotime((string)$value);
            return $timestamp ? date('d/m/Y', $timestamp) : normalize_mojibake((string)$value);
        }
        if ($type === 'restaurant') {
            return $this->resolveEntity('restaurant', (int)$value);
        }
        if ($type === 'shift') {
            return $this->resolveEntity('shift', (int)$value);
        }
        if ($type === 'unit') {
            return $this->resolveEntity('unit', (int)$value);
        }
        if ($type === 'number') {
            return (string)(int)$value;
        }
        return normalize_mojibake(trim((string)$value));
    }

    private function resolveEntity(string $type, int $id): string
    {
        if ($id <= 0) {
            return 'Não informado';
        }
        $key = $type . ':' . $id;
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $row = null;
        $fallback = '#' . $id;
        if ($type === 'restaurant') {
            $row = (new RestaurantModel())->find($id);
            $fallback = 'Restaurante #' . $id;
            $value = $row['nome'] ?? $fallback;
        } elseif ($type === 'shift') {
            $row = (new ReservaTematicaTurnoModel())->find($id);
            $fallback = 'Turno #' . $id;
            $value = isset($row['hora']) ? substr((string)$row['hora'], 0, 5) : $fallback;
        } else {
            $row = (new UnitModel())->find($id);
            $fallback = 'UH #' . $id;
            $value = isset($row['numero']) ? 'UH ' . $row['numero'] : $fallback;
        }

        $this->cache[$key] = normalize_mojibake((string)$value);
        return $this->cache[$key];
    }

    private function formatDateTime(string $value): string
    {
        $timestamp = strtotime($value);
        return $timestamp ? date('d/m/Y H:i:s', $timestamp) : normalize_mojibake($value);
    }
}
