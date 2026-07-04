<?php

declare(strict_types=1);

/**
 * Preserva o contexto operacional de tentativas recusadas antes de existir uma reserva.
 */
final class RegistrarTentativaReservaTematicaService
{
    private RegistrarEventoAuditoriaService $auditoria;

    public function __construct(?RegistrarEventoAuditoriaService $auditoria = null)
    {
        $this->auditoria = $auditoria ?? new RegistrarEventoAuditoriaService();
    }

    public function registrarRecusa(CriarReservaCommand $command, ServiceResult $resultado, string $mensagem): void
    {
        if ($resultado->isSuccess()) {
            return;
        }

        try {
            $itens = $this->buildItems($command);
            $payload = [
                'correlation_id' => $this->correlationId(),
                'acao_solicitada' => $this->cleanValue($command->acao, 40),
                'formato' => $this->reservationFormat($command),
                'resultado_codigo' => $this->cleanValue($resultado->code(), 80),
                'motivo' => normalize_mojibake(mb_substr(trim($mensagem), 0, 500, 'UTF-8')),
                'data_reserva' => $this->cleanValue($command->dataReserva, 10),
                'restaurante_id' => $command->restauranteId,
                'restaurante' => $this->restaurantName($command),
                'turno_id' => $command->turnoId,
                'turno' => $this->shiftTime($command->turnoId),
                'uhs' => array_column($itens, 'uh'),
                'itens' => $itens,
                'pax_total_tentado' => array_sum(array_column($itens, 'pax')),
                'quantidade_uhs' => count($itens),
            ];

            foreach (['pax_disponivel', 'pax_tentativa', 'pax_reservado', 'capacidade'] as $field) {
                if (array_key_exists($field, $resultado->payload())) {
                    $payload[$field] = max(0, (int)$resultado->payload()[$field]);
                }
            }

            $this->auditoria->registrar(
                ReservasTematicasConstants::AUDIT_ACTION_ATTEMPT_REJECTED,
                $command->usuarioId > 0 ? $command->usuarioId : null,
                [],
                $payload,
                ReservasTematicasConstants::AUDIT_TABLE_ATTEMPTS,
                $command->reservaId > 0 ? $command->reservaId : null
            );
        } catch (Throwable $error) {
            error_log('[reservation-attempt-audit] ' . json_encode([
                'usuario_id' => $command->usuarioId,
                'resultado_codigo' => $resultado->code(),
                'erro' => $error->getMessage(),
            ], JSON_UNESCAPED_UNICODE));
        }
    }

    private function buildItems(CriarReservaCommand $command): array
    {
        $isGroup = $command->acao === ReservasTematicasConstants::ACTION_CREATE_BATCH;
        $rawUnits = $isGroup ? $command->batchUhs : [$command->uhNumero];
        $rawPax = $isGroup ? $command->batchPax : [$command->pax];
        $limit = min(100, max(count($rawUnits), count($rawPax)));
        $items = [];

        for ($index = 0; $index < $limit; $index++) {
            $unit = $this->cleanValue((string)($rawUnits[$index] ?? ''), 24);
            $pax = max(0, min(9999, (int)($rawPax[$index] ?? 0)));
            if ($unit === '' && $pax === 0) {
                continue;
            }
            $items[] = ['uh' => $unit !== '' ? $unit : 'Não informada', 'pax' => $pax];
        }

        if ($items === [] && $command->acao === ReservasTematicasConstants::ACTION_CREATE_PRE_RESERVATION) {
            $items[] = ['uh' => 'Pré-reserva sem UH', 'pax' => max(0, min(9999, $command->pax))];
        }
        return $items;
    }

    private function reservationFormat(CriarReservaCommand $command): string
    {
        if ($command->acao === ReservasTematicasConstants::ACTION_CREATE_BATCH) {
            return 'grupo';
        }
        if ($command->acao === ReservasTematicasConstants::ACTION_CREATE_PRE_RESERVATION) {
            return 'pre_reserva';
        }
        if ($command->acao === ReservasTematicasConstants::ACTION_UPDATE || $command->reservaId > 0) {
            return 'edicao';
        }
        return 'individual';
    }

    private function restaurantName(CriarReservaCommand $command): string
    {
        foreach ($command->restaurantesPermitidos as $restaurant) {
            if ((int)($restaurant['id'] ?? 0) === $command->restauranteId) {
                return normalize_mojibake(trim((string)($restaurant['nome'] ?? '')));
            }
        }
        $restaurant = $command->restauranteId > 0 ? (new RestaurantModel())->find($command->restauranteId) : null;
        return normalize_mojibake(trim((string)($restaurant['nome'] ?? 'Não identificado')));
    }

    private function shiftTime(int $shiftId): string
    {
        $shift = $shiftId > 0 ? (new ReservaTematicaTurnoModel())->find($shiftId) : null;
        return !empty($shift['hora']) ? substr((string)$shift['hora'], 0, 5) : 'Não identificado';
    }

    private function cleanValue(string $value, int $limit): string
    {
        $value = normalize_mojibake(trim($value));
        $value = preg_replace('/[^\p{L}\p{N}\s.,_\/-]/u', '', $value) ?? '';
        return mb_substr($value, 0, $limit, 'UTF-8');
    }

    private function correlationId(): string
    {
        $cloudflareRay = $this->cleanValue((string)($_SERVER['HTTP_CF_RAY'] ?? ''), 40);
        if ($cloudflareRay !== '') {
            return $cloudflareRay;
        }
        try {
            return bin2hex(random_bytes(8));
        } catch (Throwable $ignored) {
            return str_replace('.', '', uniqid('attempt_', true));
        }
    }
}
