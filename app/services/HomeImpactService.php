<?php
declare(strict_types=1);

final class HomeImpactService
{
    private HomeImpactRepository $repository;

    public function __construct(?HomeImpactRepository $repository = null)
    {
        $this->repository = $repository ?? new HomeImpactRepository();
    }

    public function summary(): array
    {
        $reservations = $this->repository->reservasTematicas();
        $reach = $this->repository->alcance();
        $period = $this->repository->periodo();

        $total = (int)($reservations['total'] ?? 0);
        $start = $this->normalizeDate($period['inicio'] ?? null);
        $end = $this->normalizeDate($period['fim'] ?? null);

        return [
            'reservas' => $total,
            'reservas_ativas' => (int)($reservations['ativas'] ?? 0),
            'pax_planejado' => (int)($reservations['pax_planejado'] ?? 0),
            'reservas_finalizadas' => (int)($reservations['finalizadas'] ?? 0),
            'reservas_canceladas' => (int)($reservations['canceladas'] ?? 0),
            'grupos' => (int)($reservations['grupos'] ?? 0),
            'servicos_planejados' => (int)($reservations['servicos_planejados'] ?? 0),
            'eventos_historico' => (int)($reservations['eventos_historico'] ?? 0),
            'rastreabilidade' => $this->percentage((int)($reservations['com_log_criacao'] ?? 0), $total),
            'autoria_identificada' => $this->percentage((int)($reservations['com_autoria'] ?? 0), $total),
            'restaurantes' => (int)($reach['restaurantes'] ?? 0),
            'operadores' => (int)($reach['operadores'] ?? 0),
            'inicio' => $start,
            'fim' => $end,
            'dias' => $this->daysBetween($start, $end),
        ];
    }

    private function percentage(int $part, int $total): float
    {
        return $total > 0 ? round(($part / $total) * 100, 1) : 0.0;
    }

    private function normalizeDate($value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        $timestamp = strtotime($value);
        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
    }

    private function daysBetween(?string $start, ?string $end): int
    {
        if ($start === null || $end === null) {
            return 0;
        }
        $startDate = new DateTimeImmutable($start);
        $endDate = new DateTimeImmutable($end);
        return max(1, (int)$startDate->diff($endDate)->days + 1);
    }
}
