<?php
$reservas = $this->data['reservas'] ?? [];
$filters = $this->data['filters'] ?? [];
$tipo = $this->data['tipo'] ?? 'detalhada';
$colspan = $tipo === 'detalhada' ? 8 : 6;
$totalPax = 0;
foreach ($reservas as $r) {
    $totalPax += (int)($r['pax'] ?? 0);
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Listagem de Reservas Temáticas</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        .meta { margin-bottom: 12px; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f2f2f2; }
        .badge { font-size: 11px; padding: 2px 6px; border-radius: 10px; border: 1px solid #ddd; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <h1>Reservas Temáticas</h1>
    <div class="meta">
        Data: <?= htmlspecialchars($filters['data'] ?? '') ?>
        | Restaurante: <?= htmlspecialchars($filters['restaurante_nome'] ?? 'Todos') ?>
        | Turno: <?= htmlspecialchars($filters['turno_hora'] ?? 'Todos') ?>
        | Tipo: <?= htmlspecialchars($tipo) ?>
    </div>
    <table>
        <thead>
            <tr>
                <th>Restaurante</th>
                <th>Data</th>
                <th>Turno</th>
                <th>UH</th>
                <th>PAX</th>
                <th>Status</th>
                <?php if ($tipo === 'detalhada'): ?>
                    <th>Observação original</th>
                    <th>Observação operacional</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reservas as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['restaurante']) ?></td>
                    <td><?= htmlspecialchars($row['data_reserva']) ?></td>
                    <td><?= htmlspecialchars($row['turno_hora']) ?></td>
                    <td><?= htmlspecialchars($row['uh_numero']) ?></td>
                    <td><?= htmlspecialchars($row['pax']) ?></td>
                    <td><?= htmlspecialchars($row['status']) ?><?= !empty($row['excedente']) ? ' (Excedente)' : '' ?></td>
                    <?php if ($tipo === 'detalhada'): ?>
                        <td><?= htmlspecialchars($row['observacao_reserva'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['observacao_operacao'] ?? '-') ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($reservas)): ?>
                <tr><td colspan="<?= (int)$colspan ?>">Nenhuma reserva encontrada.</td></tr>
            <?php else: ?>
                <tr>
                    <td colspan="<?= (int)($colspan - 3) ?>" style="font-weight:700;">Total de PAX</td>
                    <td style="font-weight:700;"><?= (int)$totalPax ?></td>
                    <td colspan="2"></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="no-print" style="margin-top: 12px;">
        <button onclick="window.print()">Imprimir</button>
    </div>
</body>
</html>
