<?php
/* Aba Temáticos do hub Análise (contrato: AnaliseController::montarAbaTematicos). */
$filters = $this->data['filters'] ?? [];
$summary = $this->data['summary'] ?? [];
$byRestaurant = $this->data['by_restaurant'] ?? [];
$byTurno = $this->data['by_turno'] ?? [];
$byDay = $this->data['by_day'] ?? [];
$list = $this->data['list'] ?? [];
$listPage = (int)($this->data['list_page'] ?? 1);
$listTotalPages = (int)($this->data['list_total_pages'] ?? 1);
$listTotal = (int)($this->data['list_total'] ?? 0);
$taxaComparecimento = (float)($this->data['taxa_comparecimento'] ?? 0);
$restaurantesTematicos = $this->data['restaurantes'] ?? [];
$turnos = $this->data['turnos'] ?? [];
$periodDays = (int)($this->data['period_days'] ?? 0);
$timelineGranularity = (string)($this->data['timeline_granularity'] ?? 'day');
$isLargeQuery = !empty($this->data['is_large_query']);

$statusReserva = ['Reservada', 'Finalizada', 'Nao compareceu', 'Cancelada'];
$exportFilters = array_filter([
    'data' => (string)($filters['data'] ?? ''),
    'data_inicio' => (string)($filters['data_inicio'] ?? ''),
    'data_fim' => (string)($filters['data_fim'] ?? ''),
    'restaurante_id' => (string)($filters['restaurante_id'] ?? ''),
    'turno_id' => (string)($filters['turno_id'] ?? ''),
    'status' => (string)($filters['status'] ?? ''),
    'grupo_nome' => (string)($filters['grupo_nome'] ?? ''),
    'q' => (string)($filters['q'] ?? ''),
], static fn($v) => $v !== '');
$paginaLink = static function (int $pagina) use ($exportFilters): string {
    return '/?' . http_build_query(array_merge($exportFilters, ['r' => 'analise/index', 'aba' => 'tematicos', 'page' => $pagina]));
};
$badgeStatusReserva = static function (string $status): string {
    $flat = mb_strtolower(normalize_mojibake($status), 'UTF-8');
    if (strpos($flat, 'finaliz') !== false) {
        return 'fb-badge fb-badge--ok';
    }
    if (strpos($flat, 'compareceu') !== false) {
        return 'fb-badge fb-badge--solid-danger';
    }
    if (strpos($flat, 'cancel') !== false) {
        return 'fb-badge fb-badge--solid-neutral';
    }
    return 'fb-badge fb-badge--day-use';
};
$porcentagemComparecimento = static function (array $row): float {
    $total = (int)($row['total'] ?? 0);
    $finalizadas = (int)($row['finalizadas'] ?? 0);
    if ($total <= 0) {
        return 0.0;
    }
    return round(($finalizadas / $total) * 100, 1);
};
$periodoLinhaLabel = static function (array $row, string $granularity): string {
    $inicio = (string)($row['periodo_inicio'] ?? ($row['data'] ?? ''));
    $fim = (string)($row['periodo_fim'] ?? $inicio);
    if ($granularity === 'month' && $inicio !== '') {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $inicio);
        if ($dt instanceof DateTimeImmutable) {
            $meses = [
                1 => 'Janeiro',
                2 => 'Fevereiro',
                3 => 'Março',
                4 => 'Abril',
                5 => 'Maio',
                6 => 'Junho',
                7 => 'Julho',
                8 => 'Agosto',
                9 => 'Setembro',
                10 => 'Outubro',
                11 => 'Novembro',
                12 => 'Dezembro',
            ];
            return ($meses[(int)$dt->format('n')] ?? $dt->format('m')) . '/' . $dt->format('Y');
        }
    }
    if ($granularity === 'week' && $inicio !== '' && $fim !== '') {
        return format_date_br($inicio) . ' a ' . format_date_br($fim);
    }

    return format_date_br((string)($row['data'] ?? $inicio));
};
$timelineTitulo = [
    'day' => 'Resumo do período',
    'week' => 'Resumo semanal',
    'month' => 'Resumo mensal',
][$timelineGranularity] ?? 'Resumo do período';
$timelineContagem = [
    'day' => 'dias com movimento',
    'week' => 'semanas agregadas',
    'month' => 'meses agregados',
][$timelineGranularity] ?? 'dias com movimento';
$filtroAtivoResumo = [];
if (!empty($filters['data'])) {
    $filtroAtivoResumo[] = 'Data ' . format_date_br((string)$filters['data']);
}
if (!empty($filters['data_inicio']) || !empty($filters['data_fim'])) {
    $inicio = !empty($filters['data_inicio']) ? format_date_br((string)$filters['data_inicio']) : '-';
    $fim = !empty($filters['data_fim']) ? format_date_br((string)$filters['data_fim']) : '-';
    $filtroAtivoResumo[] = 'Período ' . $inicio . ' a ' . $fim;
}
if (!empty($filters['restaurante_id'])) {
    foreach ($restaurantesTematicos as $rest) {
        if ((int)$rest['id'] === (int)$filters['restaurante_id']) {
            $filtroAtivoResumo[] = normalize_mojibake((string)$rest['nome']);
            break;
        }
    }
}
if (!empty($filters['turno_id'])) {
    foreach ($turnos as $turno) {
        if ((int)$turno['id'] === (int)$filters['turno_id']) {
            $filtroAtivoResumo[] = 'Turno ' . (string)($turno['hora'] ?? $turno['nome'] ?? '');
            break;
        }
    }
}
if (!empty($filters['status'])) {
    $filtroAtivoResumo[] = 'Status ' . normalize_mojibake((string)$filters['status']);
}
if (!empty($filters['grupo_nome'])) {
    $filtroAtivoResumo[] = 'Grupo ' . normalize_mojibake((string)$filters['grupo_nome']);
}
if (!empty($filters['q'])) {
    $filtroAtivoResumo[] = 'Busca ' . normalize_mojibake((string)$filters['q']);
}
?>

<div class="fb-tematic-analysis<?= $isLargeQuery ? ' fb-tematic-analysis--large' : '' ?> fb-mt">
    <section class="fb-card fb-card--flat fb-tematic-analysis__filters">
        <div class="fb-card__head">
            <div>
                <p class="fb-card__eyebrow">Leitura temática</p>
                <h5 class="fb-card__title">Reservas, presença e ocupação</h5>
            </div>
            <div class="fb-page-head__actions">
                <a class="fb-btn" href="/?<?= h(http_build_query(array_merge($exportFilters, ['r' => 'relatoriosTematicos/export', 'type' => 'csv']))) ?>"><i class="bi bi-filetype-csv"></i> Exportar CSV</a>
                <a class="fb-btn" href="/?<?= h(http_build_query(array_merge($exportFilters, ['r' => 'relatoriosTematicos/export', 'type' => 'xlsx']))) ?>"><i class="bi bi-file-earmark-excel"></i> Exportar Excel</a>
            </div>
        </div>

        <?php if ($isLargeQuery): ?>
            <div class="fb-query-notice">
                <div>
                    <strong>Consulta extensa em modo compacto</strong>
                    <span><?= number_format($listTotal, 0, ',', '.') ?> reservas<?= $periodDays > 0 ? ' em ' . $periodDays . ' dia(s)' : '' ?>. A linha temporal foi agregada por <?= h($timelineGranularity === 'month' ? 'mês' : ($timelineGranularity === 'week' ? 'semana' : 'dia')) ?>; use Excel/CSV para análise linha a linha.</span>
                </div>
                <i class="bi bi-speedometer2"></i>
            </div>
        <?php endif; ?>

        <?php if ($filtroAtivoResumo !== []): ?>
            <p class="fb-filter-line">
                <i class="bi bi-funnel" aria-hidden="true"></i>
                <?php foreach ($filtroAtivoResumo as $itemResumo): ?>
                    <span class="fb-filter-line__item"><?= h($itemResumo) ?></span>
                <?php endforeach; ?>
            </p>
        <?php endif; ?>

        <form method="get" action="/" class="fb-tematic-analysis__filter-form" data-fb-filters>
            <input type="hidden" name="r" value="analise/index">
            <input type="hidden" name="aba" value="tematicos">

            <label class="fb-field">
                <span class="fb-label">Data única</span>
                <input type="date" class="fb-input" name="data" value="<?= h($filters['data'] ?? '') ?>">
            </label>
            <label class="fb-field">
                <span class="fb-label">Data início</span>
                <input type="date" class="fb-input" name="data_inicio" value="<?= h($filters['data_inicio'] ?? '') ?>">
            </label>
            <label class="fb-field">
                <span class="fb-label">Data fim</span>
                <input type="date" class="fb-input" name="data_fim" value="<?= h($filters['data_fim'] ?? '') ?>">
            </label>
            <label class="fb-field">
                <span class="fb-label">Status</span>
                <select class="fb-select" name="status">
                    <option value="">Todos</option>
                    <?php foreach ($statusReserva as $statusOption): ?>
                        <option value="<?= h($statusOption) ?>" <?= ($filters['status'] ?? '') === $statusOption ? 'selected' : '' ?>><?= h($statusOption) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="fb-field">
                <span class="fb-label">Restaurante</span>
                <select class="fb-select" name="restaurante_id">
                    <option value="">Todos os temáticos</option>
                    <?php foreach ($restaurantesTematicos as $rest): ?>
                        <option value="<?= (int)$rest['id'] ?>" <?= ($filters['restaurante_id'] ?? '') == $rest['id'] ? 'selected' : '' ?>><?= h($rest['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="fb-field">
                <span class="fb-label">Turno</span>
                <select class="fb-select" name="turno_id">
                    <option value="">Todos</option>
                    <?php foreach ($turnos as $turno): ?>
                        <option value="<?= (int)$turno['id'] ?>" <?= ($filters['turno_id'] ?? '') == $turno['id'] ? 'selected' : '' ?>><?= h((string)($turno['hora'] ?? $turno['nome'] ?? ('Turno ' . (int)$turno['id']))) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="fb-field">
                <span class="fb-label">Grupo</span>
                <input type="text" class="fb-input" name="grupo_nome" value="<?= h($filters['grupo_nome'] ?? '') ?>" placeholder="Nome do grupo">
            </label>
            <label class="fb-field">
                <span class="fb-label">Buscar UH ou titular</span>
                <input type="text" class="fb-input" name="q" value="<?= h($filters['q'] ?? '') ?>" placeholder="Ex.: 4110 ou Matias">
            </label>

            <div class="fb-tematic-analysis__actions">
                <button type="submit" class="fb-btn fb-btn--primary">Aplicar filtro</button>
                <a class="fb-btn fb-btn--ghost" href="/?r=analise/index&aba=tematicos">Voltar para hoje</a>
            </div>
        </form>
    </section>

    <div class="fb-heroline fb-mt">
        <div class="fb-hero">
            <span class="fb-hero__value"><?= number_format((int)($summary['total_reservas'] ?? 0), 0, ',', '.') ?></span>
            <span class="fb-hero__label">reservas · <?= (int)($summary['total_grupos'] ?? 0) ?> grupos · <?= (int)($summary['total_lotes'] ?? 0) ?> lotes</span>
        </div>
        <div class="fb-stat">
            <span class="fb-stat__value"><?= number_format((int)($summary['pax_reservadas'] ?? 0), 0, ',', '.') ?></span>
            <span class="fb-stat__label"><?= (int)($summary['pax_adulto_reservadas'] ?? 0) ?> adultos · <?= (int)($summary['pax_chd_reservadas'] ?? 0) ?> CHD (PAX reservadas)</span>
        </div>
        <div class="fb-stat">
            <span class="fb-stat__value fb-stat__value--ok"><?= number_format($taxaComparecimento, 1, ',', '.') ?>%</span>
            <span class="fb-stat__label"><?= number_format((int)($summary['pax_comparecidas'] ?? 0), 0, ',', '.') ?> PAX compareceram</span>
        </div>
        <div class="fb-stat">
            <span class="fb-stat__value<?= (int)($summary['pax_nao_comparecidas'] ?? 0) > 0 ? ' fb-stat__value--danger' : '' ?>"><?= number_format((int)($summary['pax_nao_comparecidas'] ?? 0), 0, ',', '.') ?></span>
            <span class="fb-stat__label">no-show · <?= (int)($summary['no_shows'] ?? 0) ?> reservas faltaram</span>
        </div>
    </div>

    <div class="fb-tematic-analysis__grid fb-mt">
        <section class="fb-card">
            <div class="fb-card__head">
                <div>
                    <p class="fb-card__eyebrow">Por restaurante</p>
                    <h5 class="fb-card__title">Distribuição da operação</h5>
                </div>
            </div>

            <?php if ($byRestaurant === []): ?>
                <div class="fb-empty">
                    <i class="bi bi-shop-window"></i>
                    <p class="fb-empty__title">Sem reservas no recorte</p>
                    <p style="margin: 0; font-size: 0.85rem;">A distribuição por restaurante aparecerá aqui.</p>
                </div>
            <?php else: ?>
                <div class="fb-tematic-stack">
                    <?php foreach ($byRestaurant as $row): ?>
                        <?php
                        $restNome = normalize_mojibake((string)($row['restaurante'] ?? ''));
                        $identidade = restaurante_identidade($restNome);
                        $comparecimentoRow = $porcentagemComparecimento($row);
                        ?>
                        <article class="fb-tematic-restaurant">
                            <div class="fb-tematic-restaurant__head">
                                <div class="fb-tematic-restaurant__title">
                                    <?= restaurante_selo($restNome) ?>
                                    <strong><?= h($restNome) ?></strong>
                                </div>
                                <span class="fb-badge fb-badge--ok"><?= number_format($comparecimentoRow, 1, ',', '.') ?>% concluído</span>
                            </div>
                            <div class="fb-tematic-restaurant__stats">
                                <div><span>Total</span><strong><?= (int)($row['total'] ?? 0) ?></strong></div>
                                <div><span>Finalizadas</span><strong><?= (int)($row['finalizadas'] ?? 0) ?></strong></div>
                                <div><span>No-show</span><strong><?= (int)($row['no_shows'] ?? 0) ?></strong></div>
                                <div><span>Canceladas</span><strong><?= (int)($row['canceladas'] ?? 0) ?></strong></div>
                            </div>
                            <div class="fb-tematic-restaurant__bar" aria-hidden="true">
                                <span style="width: <?= max(0.0, min(100.0, $comparecimentoRow)) ?>%; background: <?= h((string)$identidade['cor']) ?>;"></span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="fb-card">
            <div class="fb-card__head">
                <div>
                    <p class="fb-card__eyebrow">Por turno</p>
                    <h5 class="fb-card__title">Leitura por janela</h5>
                </div>
            </div>

            <?php if ($byTurno === []): ?>
                <div class="fb-empty">
                    <i class="bi bi-clock-history"></i>
                    <p class="fb-empty__title">Sem turnos no recorte</p>
                    <p style="margin: 0; font-size: 0.85rem;">Os turnos aparecerão agrupados aqui.</p>
                </div>
            <?php else: ?>
                <div class="fb-tematic-turno-list">
                    <?php foreach ($byTurno as $row): ?>
                        <?php $restNome = normalize_mojibake((string)($row['restaurante'] ?? '')); ?>
                        <article class="fb-tematic-turno">
                            <div class="fb-tematic-turno__head">
                                <?= restaurante_selo($restNome) ?>
                                <strong><?= h((string)($row['turno'] ?? '')) ?></strong>
                            </div>
                            <div class="fb-tematic-turno__chips">
                                <span class="fb-badge"><?= (int)($row['total'] ?? 0) ?> reservas</span>
                                <span class="fb-badge fb-badge--ok"><?= (int)($row['finalizadas'] ?? 0) ?> finalizadas</span>
                                <?php if ((int)($row['no_shows'] ?? 0) > 0): ?>
                                    <span class="fb-badge fb-badge--solid-danger"><?= (int)($row['no_shows'] ?? 0) ?> no-show</span>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <section class="fb-card fb-mt">
            <div class="fb-card__head">
                <div>
                    <p class="fb-card__eyebrow">Linha temporal</p>
                    <h5 class="fb-card__title"><?= h($timelineTitulo) ?></h5>
                </div>
                <?php if ($isLargeQuery && $byDay !== []): ?>
                    <span class="fb-badge fb-badge--outline"><?= count($byDay) ?> <?= h($timelineContagem) ?></span>
                <?php endif; ?>
            </div>

        <?php if ($byDay === []): ?>
            <div class="fb-empty">
                <i class="bi bi-calendar-x"></i>
                <p class="fb-empty__title">Nenhum dia com movimento</p>
                <p style="margin: 0; font-size: 0.85rem;">O histórico diário aparecerá quando houver reservas no recorte.</p>
            </div>
        <?php else: ?>
            <div class="fb-tematic-day-list">
                <?php foreach ($byDay as $row): ?>
                    <article class="fb-tematic-day">
                        <div class="fb-tematic-day__date">
                            <strong><?= h($periodoLinhaLabel($row, $timelineGranularity)) ?></strong>
                            <span><?= (int)($row['total'] ?? 0) ?> reservas</span>
                        </div>
                        <div class="fb-tematic-day__stats">
                            <span><strong><?= (int)($row['finalizadas'] ?? 0) ?></strong> finalizadas</span>
                            <span><strong><?= (int)($row['no_shows'] ?? 0) ?></strong> no-show</span>
                            <span><strong><?= (int)($row['pax_adulto_reservadas'] ?? 0) ?></strong> adultos</span>
                            <span><strong><?= (int)($row['pax_chd_reservadas'] ?? 0) ?></strong> CHD</span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="fb-card fb-mt">
        <div class="fb-card__head">
            <div>
                <p class="fb-card__eyebrow">Base detalhada</p>
                <h5 class="fb-card__title">Reservas temáticas</h5>
            </div>
            <span class="fb-muted" style="font-size: 0.78rem;"><?= number_format($listTotal, 0, ',', '.') ?> reservas · página <?= $listPage ?> de <?= $listTotalPages ?></span>
        </div>

        <?php if ($list === []): ?>
            <div class="fb-empty">
                <i class="bi bi-calendar-heart"></i>
                <p class="fb-empty__title">Nenhuma reserva no recorte</p>
                <p style="margin: 0; font-size: 0.85rem;">Ajuste o filtro ou escolha outro período.</p>
            </div>
        <?php else: ?>
            <div class="fb-tematic-reservation-list">
                <?php foreach ($list as $row): ?>
                    <?php
                    $titularDisplay = normalize_mojibake((string)($row['titular_nome_display'] ?? $row['titular_nome'] ?? '-'));
                    $statusRow = (string)($row['status'] ?? '');
                    $restNome = normalize_mojibake((string)($row['restaurante'] ?? ''));
                    $usuario = normalize_mojibake((string)($row['usuario'] ?? ''));
                    $grupoNome = normalize_mojibake((string)($row['grupo_nome_display'] ?? $row['grupo_nome'] ?? $row['grupo_responsavel'] ?? ''));
                    $qtdChd = (int)($row['qtd_chd'] ?? 0);
                    ?>
                    <article class="fb-tematic-reservation">
                        <div class="fb-tematic-reservation__head">
                            <div class="fb-tematic-reservation__identity">
                                <?= restaurante_selo($restNome) ?>
                                <div>
                                    <strong><?= h($titularDisplay) ?></strong>
                                    <span><?= h(format_date_br((string)($row['data_reserva'] ?? ''))) ?> · <?= h((string)($row['turno_hora'] ?? '')) ?></span>
                                </div>
                            </div>
                            <div class="fb-tematic-reservation__state">
                                <span class="<?= $badgeStatusReserva($statusRow) ?>"><?= h(normalize_mojibake($statusRow)) ?></span>
                            </div>
                        </div>

                        <div class="fb-tematic-reservation__meta">
                            <span class="fb-badge"><?= h(uh_label((string)($row['uh_numero'] ?? ''))) ?></span>
                            <span class="fb-badge fb-badge--day-use"><?= (int)($row['pax'] ?? 0) ?> PAX</span>
                            <?php if ($qtdChd > 0): ?>
                                <span class="fb-badge fb-badge--warn"><?= $qtdChd ?> CHD</span>
                            <?php endif; ?>
                            <?php if ($grupoNome !== ''): ?>
                                <span class="fb-badge fb-badge--nao-informado"><?= h($grupoNome) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="fb-tematic-reservation__footer">
                            <span>Criada por <strong><?= h($usuario !== '' ? $usuario : 'Sistema') ?></strong></span>
                            <?php if (!empty($row['criado_em'])): ?>
                                <span><?= h((string)$row['criado_em']) ?></span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($listTotalPages > 1): ?>
            <div class="fb-chiprow fb-mt" style="justify-content: center;">
                <?php if ($listPage > 1): ?>
                    <a class="fb-chip" href="<?= h($paginaLink($listPage - 1)) ?>"><i class="bi bi-chevron-left"></i> Anterior</a>
                <?php endif; ?>
                <?php for ($pagina = 1; $pagina <= $listTotalPages; $pagina++): ?>
                    <?php
                    if ($pagina === 1 || $pagina === $listTotalPages || abs($pagina - $listPage) <= 1) {
                        ?>
                        <a class="fb-chip<?= $pagina === $listPage ? ' fb-chip--active' : '' ?>" href="<?= h($paginaLink($pagina)) ?>"><?= $pagina ?></a>
                        <?php
                    } elseif ($pagina === 2 && $listPage > 4) {
                        echo '<span class="fb-chip" aria-hidden="true">...</span>';
                    } elseif ($pagina === $listTotalPages - 1 && $listPage < $listTotalPages - 3) {
                        echo '<span class="fb-chip" aria-hidden="true">...</span>';
                    }
                    ?>
                <?php endfor; ?>
                <?php if ($listPage < $listTotalPages): ?>
                    <a class="fb-chip" href="<?= h($paginaLink($listPage + 1)) ?>">Próxima <i class="bi bi-chevron-right"></i></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
