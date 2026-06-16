<?php
$reservas = $this->data['reservas'] ?? [];
$filters = $this->data['filters'] ?? [];
$tipo = $this->data['tipo'] ?? 'detalhada';

$summary = [
    'reservas' => 0,
    'pax' => 0,
    'chd' => 0,
    'adultos' => 0,
    'uhs' => [],
    'observacoes' => 0,
];
$turnos = [];

foreach ($reservas as $row) {
    $turno = normalize_mojibake((string)($row['turno_hora'] ?? '--:--'));
    if (!isset($turnos[$turno])) {
        $turnos[$turno] = [
            'turno' => $turno,
            'reservas' => [],
            'total_pax' => 0,
            'total_chd' => 0,
            'total_adultos' => 0,
        ];
    }

    $pax = (int)($row['pax'] ?? 0);
    $chd = (int)($row['qtd_chd_calc'] ?? $row['pax_chd_calc'] ?? 0);
    $adultos = (int)($row['pax_adulto_calc'] ?? max(0, $pax - $chd));
    $obsReserva = trim(normalize_mojibake((string)($row['observacao_reserva'] ?? '')));
    $obsOperacao = trim(normalize_mojibake((string)($row['observacao_operacao'] ?? '')));
    $tags = trim(normalize_mojibake((string)($row['observacao_tags'] ?? '')));
    $titular = trim(normalize_mojibake((string)($row['titular_nome_display'] ?? $row['titular_nome'] ?? '')));
    $grupo = trim(normalize_mojibake((string)($row['grupo_nome_display'] ?? $row['grupo_nome'] ?? '')));
    $status = trim(normalize_mojibake((string)($row['status_reserva'] ?? $row['status'] ?? 'Reservada')));

    $turnos[$turno]['reservas'][] = [
        'restaurante' => normalize_mojibake((string)($row['restaurante'] ?? '')),
        'uh' => normalize_mojibake((string)($row['uh_numero'] ?? '')),
        'titular' => $titular !== '' && $titular !== '-' ? $titular : 'Nao informado',
        'grupo' => $grupo !== '' && $grupo !== '-' ? $grupo : '',
        'pax' => $pax,
        'adultos' => $adultos,
        'chd' => $chd,
        'status' => $status !== '' ? $status : 'Reservada',
        'obs_reserva' => $obsReserva,
        'obs_operacao' => $obsOperacao,
        'tags' => $tags,
        'usuario' => normalize_mojibake((string)($row['usuario'] ?? '')),
    ];

    $turnos[$turno]['total_pax'] += $pax;
    $turnos[$turno]['total_chd'] += $chd;
    $turnos[$turno]['total_adultos'] += $adultos;

    $summary['reservas']++;
    $summary['pax'] += $pax;
    $summary['chd'] += $chd;
    $summary['adultos'] += $adultos;
    $summary['uhs'][(string)($row['uh_numero'] ?? '')] = true;
    if ($obsReserva !== '' || $obsOperacao !== '' || $tags !== '') {
        $summary['observacoes']++;
    }
}

$summary['uhs_total'] = count(array_filter(array_keys($summary['uhs']), static function ($value): bool {
    return trim((string)$value) !== '';
}));

ksort($turnos);

$documentTitle = $tipo === 'detalhada' ? 'Impressão operacional de reservas' : 'Resumo de reservas temáticas';
$documentSubtitle = 'Lista preparada para apoio rápido da operação, montagem do ambiente e conferência do serviço.';
$documentMeta = [
    'Data' => !empty($filters['data']) ? format_date_br((string)$filters['data']) : format_date_br(date('Y-m-d')),
    'Restaurante' => normalize_mojibake((string)($filters['restaurante_nome'] ?? 'Todos')),
    'Turno' => normalize_mojibake((string)($filters['turno_hora'] ?? 'Todos')),
    'Status' => !empty($filters['status']) ? normalize_mojibake((string)$filters['status']) : 'Todos',
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title><?= h($documentTitle) ?></title>
    <?php require __DIR__ . '/../partials/export_print_styles.php'; ?>
</head>
<body>
    <div class="export-page">
        <div class="export-shell">
            <header class="export-hero">
                <div class="export-kicker">FBControl · Operação temática</div>
                <h1 class="export-title"><?= h($documentTitle) ?></h1>
                <div class="export-subtitle"><?= h($documentSubtitle) ?></div>
            </header>

            <section class="export-meta">
                <?php foreach ($documentMeta as $label => $value): ?>
                    <article class="meta-card">
                        <div class="meta-label"><?= h($label) ?></div>
                        <div class="meta-value"><?= h($value) ?></div>
                    </article>
                <?php endforeach; ?>
            </section>

            <section class="summary-grid">
                <article class="summary-card">
                    <div class="summary-number"><?= (int)$summary['reservas'] ?></div>
                    <div class="summary-label">Reservas</div>
                    <div class="summary-note">Total de registros impressos</div>
                </article>
                <article class="summary-card">
                    <div class="summary-number"><?= (int)$summary['pax'] ?></div>
                    <div class="summary-label">PAX total</div>
                    <div class="summary-note"><?= (int)$summary['adultos'] ?> adultos e <?= (int)$summary['chd'] ?> CHD</div>
                </article>
                <article class="summary-card">
                    <div class="summary-number"><?= (int)$summary['uhs_total'] ?></div>
                    <div class="summary-label">UHs</div>
                    <div class="summary-note">Habitações diferentes no período</div>
                </article>
                <article class="summary-card">
                    <div class="summary-number"><?= (int)$summary['observacoes'] ?></div>
                    <div class="summary-label">Atenções</div>
                    <div class="summary-note">Reservas com observações ou tags</div>
                </article>
            </section>

            <div class="content-wrap">
                <?php if (empty($turnos)): ?>
                    <div class="empty-state">
                        Nenhuma reserva encontrada para os filtros informados.
                    </div>
                <?php else: ?>
                    <?php foreach ($turnos as $grupo): ?>
                        <section class="turno-section">
                            <div class="turno-header">
                                <div>
                                    <h2 class="turno-title">Turno <?= h($grupo['turno']) ?></h2>
                                    <div class="turno-subtitle">Leitura pensada para salão, cozinha e conferência manual durante o serviço.</div>
                                </div>
                                <div class="turno-stats">
                                    <span class="stat-pill"><strong><?= count($grupo['reservas']) ?></strong> reservas</span>
                                    <span class="stat-pill"><strong><?= (int)$grupo['total_pax'] ?></strong> PAX</span>
                                    <span class="stat-pill"><strong><?= (int)$grupo['total_chd'] ?></strong> CHD</span>
                                </div>
                            </div>

                            <div class="turno-table-wrap">
                                <table class="export-table">
                                    <thead>
                                        <tr>
                                            <th style="width:88px;">UH</th>
                                            <th>Hóspede / grupo</th>
                                            <th style="width:180px;">PAX</th>
                                            <th style="width:120px;">Status</th>
                                            <th>Observações operacionais</th>
                                            <th class="check-column">Checagem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($grupo['reservas'] as $item): ?>
                                            <tr>
                                                <td data-label="UH">
                                                    <span class="uh-badge">UH <?= h($item['uh']) ?></span>
                                                </td>
                                                <td data-label="Hóspede / grupo">
                                                    <div class="guest-name"><?= h($item['titular']) ?></div>
                                                    <div class="chip-row">
                                                        <?php if ($item['grupo'] !== ''): ?>
                                                            <span class="chip">Grupo: <?= h($item['grupo']) ?></span>
                                                        <?php endif; ?>
                                                        <span class="chip">Criado por <?= h($item['usuario']) ?></span>
                                                        <span class="chip">Restaurante <?= h($item['restaurante']) ?></span>
                                                    </div>
                                                </td>
                                                <td data-label="PAX">
                                                    <div class="chip-row">
                                                        <span class="chip"><strong><?= (int)$item['pax'] ?></strong> PAX</span>
                                                        <span class="chip"><strong><?= (int)$item['adultos'] ?></strong> adultos</span>
                                                        <span class="chip"><strong><?= (int)$item['chd'] ?></strong> CHD</span>
                                                    </div>
                                                </td>
                                                <td data-label="Status">
                                                    <span class="chip status"><?= h($item['status']) ?></span>
                                                </td>
                                                <td data-label="Observações">
                                                    <div class="notes-block">
                                                        <?php if ($item['obs_reserva'] !== ''): ?>
                                                            <div class="note-line">
                                                                <span class="note-label">Reserva</span>
                                                                <div><?= h($item['obs_reserva']) ?></div>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($item['obs_operacao'] !== ''): ?>
                                                            <div class="note-line">
                                                                <span class="note-label">Operação</span>
                                                                <div><?= h($item['obs_operacao']) ?></div>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($item['tags'] !== ''): ?>
                                                            <div class="note-line">
                                                                <span class="note-label">Tags</span>
                                                                <div><?= h($item['tags']) ?></div>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($item['obs_reserva'] === '' && $item['obs_operacao'] === '' && $item['tags'] === ''): ?>
                                                            <div class="note-line">
                                                                <span class="note-label">Observações</span>
                                                                <div>Sem apontamentos adicionais.</div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="check-column" data-label="Checagem">
                                                    <div class="check-box"></div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="toolbar no-print">
                <div class="toolbar-note">
                    Esta impressão prioriza leitura rápida, totais por turno e espaço de checagem manual para a equipe operacional.
                </div>
                <div class="toolbar-actions">
                    <button type="button" class="ghost" onclick="window.close()">Fechar</button>
                    <button type="button" onclick="window.print()">Imprimir agora</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
