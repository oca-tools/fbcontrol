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
    'has_non_reservada' => false,
];
$turnos = [];

foreach ($reservas as $row) {
    $turno = normalize_mojibake((string)($row['turno_hora'] ?? '--:--'));
    if (!isset($turnos[$turno])) {
        $turnos[$turno] = [
            'turno' => $turno,
            'reservas' => [],
            'individuais' => [],
            'grupos' => [],
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

    $itemReserva = [
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

    $turnos[$turno]['reservas'][] = $itemReserva;

    if ($itemReserva['grupo'] !== '') {
        $groupKey = mb_strtolower($itemReserva['grupo'], 'UTF-8');
        if (!isset($turnos[$turno]['grupos'][$groupKey])) {
            $turnos[$turno]['grupos'][$groupKey] = [
                'nome' => $itemReserva['grupo'],
                'titulares' => [],
                'uhs' => [],
                'usuarios' => [],
                'statuses' => [],
                'itens' => [],
                'total_pax' => 0,
                'total_chd' => 0,
                'total_adultos' => 0,
            ];
        }

        $turnos[$turno]['grupos'][$groupKey]['titulares'][$itemReserva['titular']] = true;
        $turnos[$turno]['grupos'][$groupKey]['uhs'][$itemReserva['uh']] = true;
        $turnos[$turno]['grupos'][$groupKey]['usuarios'][$itemReserva['usuario'] !== '' ? $itemReserva['usuario'] : 'Nao informado'] = true;
        $turnos[$turno]['grupos'][$groupKey]['statuses'][$itemReserva['status']] = true;
        $turnos[$turno]['grupos'][$groupKey]['itens'][] = $itemReserva;
        $turnos[$turno]['grupos'][$groupKey]['total_pax'] += $itemReserva['pax'];
        $turnos[$turno]['grupos'][$groupKey]['total_chd'] += $itemReserva['chd'];
        $turnos[$turno]['grupos'][$groupKey]['total_adultos'] += $itemReserva['adultos'];
    } else {
        $turnos[$turno]['individuais'][] = $itemReserva;
    }

    $turnos[$turno]['total_pax'] += $pax;
    $turnos[$turno]['total_chd'] += $chd;
    $turnos[$turno]['total_adultos'] += $adultos;

    $summary['reservas']++;
    $summary['pax'] += $pax;
    $summary['chd'] += $chd;
    $summary['adultos'] += $adultos;
    $summary['uhs'][(string)($row['uh_numero'] ?? '')] = true;
    $statusKey = mb_strtolower($itemReserva['status'], 'UTF-8');
    if (!in_array($statusKey, ['reservada', 'reservado'], true)) {
        $summary['has_non_reservada'] = true;
    }
    if ($obsReserva !== '' || $obsOperacao !== '' || $tags !== '') {
        $summary['observacoes']++;
    }
}

$summary['uhs_total'] = count(array_filter(array_keys($summary['uhs']), static function ($value): bool {
    return trim((string)$value) !== '';
}));

ksort($turnos);

foreach ($turnos as &$turnoData) {
    if (!empty($turnoData['grupos'])) {
        foreach ($turnoData['grupos'] as &$grupoData) {
            $grupoData['titulares'] = array_values(array_filter(array_keys($grupoData['titulares'])));
            $grupoData['uhs'] = array_values(array_filter(array_keys($grupoData['uhs'])));
            sort($grupoData['uhs']);
            $grupoData['usuarios'] = array_values(array_filter(array_keys($grupoData['usuarios'])));
            $grupoData['statuses'] = array_values(array_filter(array_keys($grupoData['statuses'])));
        }
        unset($grupoData);

        uasort($turnoData['grupos'], static function (array $a, array $b): int {
            return strcasecmp((string)$a['nome'], (string)$b['nome']);
        });
    }
}
unset($turnoData);

$showStatusColumn = !empty($summary['has_non_reservada']);
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
                                    <span class="stat-pill"><strong><?= count($grupo['grupos']) ?></strong> grupos</span>
                                    <span class="stat-pill"><strong><?= (int)$grupo['total_pax'] ?></strong> PAX</span>
                                    <span class="stat-pill"><strong><?= (int)$grupo['total_chd'] ?></strong> CHD</span>
                                </div>
                            </div>

                            <?php if (!empty($grupo['grupos'])): ?>
                                <div class="group-block-list">
                                    <?php foreach ($grupo['grupos'] as $blocoGrupo): ?>
                                        <?php
                                            $titularesGrupo = $blocoGrupo['titulares'];
                                            $titularUnico = count($titularesGrupo) === 1 ? (string)$titularesGrupo[0] : '';
                                            $nomeGrupoNormalizado = mb_strtolower(trim((string)$blocoGrupo['nome']), 'UTF-8');
                                            $titularUnicoNormalizado = mb_strtolower(trim($titularUnico), 'UTF-8');
                                            $titularDiferenteDoGrupo = $titularUnico !== '' && $nomeGrupoNormalizado !== $titularUnicoNormalizado;
                                            $mostrarTitularPorUh = count($titularesGrupo) > 1;
                                            $tituloGrupo = $titularDiferenteDoGrupo
                                                ? $blocoGrupo['nome'] . ' · Titular: ' . $titularUnico
                                                : $blocoGrupo['nome'];
                                        ?>
                                        <article class="group-block">
                                            <div class="group-block-head">
                                                <div>
                                                    <div class="group-block-kicker">Reserva em grupo</div>
                                                    <h3 class="group-block-title"><?= h($tituloGrupo) ?></h3>
                                                    <div class="group-block-subtitle">
                                                        <?= count($blocoGrupo['itens']) ?> reservas vinculadas ·
                                                        <?= count($blocoGrupo['uhs']) ?> UHs ·
                                                        <?= h(implode(', ', array_slice($blocoGrupo['usuarios'], 0, 2))) ?>
                                                    </div>
                                                </div>
                                                <div class="group-block-stats">
                                                    <span class="group-stat-pill"><strong><?= (int)$blocoGrupo['total_pax'] ?></strong> PAX</span>
                                                    <span class="group-stat-pill"><strong><?= (int)$blocoGrupo['total_adultos'] ?></strong> adultos</span>
                                                    <span class="group-stat-pill"><strong><?= (int)$blocoGrupo['total_chd'] ?></strong> CHD</span>
                                                </div>
                                            </div>
                                            <div class="group-block-body">
                                                <div class="group-inline-grid single-panel">
                                                    <section class="group-inline-panel">
                                                        <div class="group-inline-label">UHs do grupo</div>
                                                        <div class="chip-row">
                                                            <?php foreach ($blocoGrupo['uhs'] as $uh): ?>
                                                                <span class="chip chip-uh">UH <?= h($uh) ?></span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </section>
                                                </div>
                                                <div class="group-member-list">
                                                    <?php foreach ($blocoGrupo['itens'] as $itemGrupo): ?>
                                                        <div class="group-member-card">
                                                            <div class="group-member-unit">
                                                                <span class="group-member-uh">UH <?= h($itemGrupo['uh']) ?></span>
                                                                <?php if ($mostrarTitularPorUh): ?>
                                                                    <strong class="group-member-name"><?= h($itemGrupo['titular']) ?></strong>
                                                                <?php endif; ?>
                                                                <?php if ($showStatusColumn && !in_array(mb_strtolower((string)$itemGrupo['status'], 'UTF-8'), ['reservada', 'reservado'], true)): ?>
                                                                    <span class="chip status"><?= h($itemGrupo['status']) ?></span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="group-member-metrics">
                                                                <div>
                                                                    <strong><?= (int)$itemGrupo['pax'] ?></strong>
                                                                    <span>PAX</span>
                                                                </div>
                                                                <div>
                                                                    <strong><?= (int)$itemGrupo['adultos'] ?></strong>
                                                                    <span>adultos</span>
                                                                </div>
                                                                <div>
                                                                    <strong><?= (int)$itemGrupo['chd'] ?></strong>
                                                                    <span>CHD</span>
                                                                </div>
                                                            </div>
                                                            <?php if ($itemGrupo['obs_reserva'] !== '' || $itemGrupo['obs_operacao'] !== '' || $itemGrupo['tags'] !== ''): ?>
                                                                <div class="group-note-stack">
                                                                    <?php if ($itemGrupo['obs_reserva'] !== ''): ?>
                                                                        <div class="group-note-item"><span>Reserva</span><?= h($itemGrupo['obs_reserva']) ?></div>
                                                                    <?php endif; ?>
                                                                    <?php if ($itemGrupo['obs_operacao'] !== ''): ?>
                                                                        <div class="group-note-item"><span>Operação</span><?= h($itemGrupo['obs_operacao']) ?></div>
                                                                    <?php endif; ?>
                                                                    <?php if ($itemGrupo['tags'] !== ''): ?>
                                                                        <div class="group-note-item"><span>Tags</span><?= h($itemGrupo['tags']) ?></div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($grupo['individuais'])): ?>
                            <div class="turno-table-wrap">
                                <table class="export-table">
                                    <thead>
                                        <tr>
                                            <th style="width:88px;">UH</th>
                                            <th>Hóspede / grupo</th>
                                            <th class="pax-column">PAX</th>
                                            <?php if ($showStatusColumn): ?>
                                                <th style="width:120px;">Status</th>
                                            <?php endif; ?>
                                            <th>Observações operacionais</th>
                                            <th class="check-column">Checagem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($grupo['individuais'] as $item): ?>
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
                                                    <div class="chip-row pax-chip-row">
                                                        <span class="chip pax-chip"><strong><?= (int)$item['pax'] ?></strong> PAX</span>
                                                        <span class="chip pax-chip"><strong><?= (int)$item['adultos'] ?></strong> adultos</span>
                                                        <span class="chip pax-chip"><strong><?= (int)$item['chd'] ?></strong> CHD</span>
                                                    </div>
                                                </td>
                                                <?php if ($showStatusColumn): ?>
                                                    <td data-label="Status">
                                                        <?php if (!in_array(mb_strtolower((string)$item['status'], 'UTF-8'), ['reservada', 'reservado'], true)): ?>
                                                            <span class="chip status"><?= h($item['status']) ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
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
                            <?php elseif (empty($grupo['grupos'])): ?>
                                <div class="turno-table-wrap">
                                    <div class="empty-state compact-empty-state">Nenhuma reserva encontrada para este turno.</div>
                                </div>
                            <?php endif; ?>
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
