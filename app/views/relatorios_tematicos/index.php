<?php
$filters = $this->data['filters'] ?? [];
$summary = $this->data['summary'] ?? [];
$byRestaurant = $this->data['by_restaurant'] ?? [];
$byTurno = $this->data['by_turno'] ?? [];
$byDay = $this->data['by_day'] ?? [];
$list = $this->data['list'] ?? [];
$taxaComparecimento = $this->data['taxa_comparecimento'] ?? 0;
$restaurantes = $this->data['restaurantes'] ?? [];
$turnos = $this->data['turnos'] ?? [];

$statuses = [
    'Reservada',
    'Conferida',
    'Em atendimento',
    'Finalizada',
    'Não compareceu',
    'Cancelada',
    'Divergência',
    'Excedente',
];
?>

<div class="saas-page relatorios-tematicos-page">
<div class="card card-soft p-4 mb-4">
    <div class="section-title">
        <div class="icon"><i class="bi bi-clipboard-data"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Relatórios Temáticos</div>
            <h3 class="fw-bold mb-0">Relatórios das Reservas Temáticas</h3>
            <div class="text-muted">Acompanhe reservas, comparecimentos e no-shows.</div>
        </div>
    </div>
</div>

<div class="card p-4 mb-4">
    <div class="d-flex justify-content-end gap-2 mb-3">
        <a class="btn btn-outline-primary js-export-btn" data-toast="Exportado com sucesso. O download CSV foi iniciado." href="/?r=relatoriosTematicos/export&type=csv&data=<?= h($filters['data']) ?>&data_inicio=<?= h($filters['data_inicio']) ?>&data_fim=<?= h($filters['data_fim']) ?>&restaurante_id=<?= h($filters['restaurante_id']) ?>&turno_id=<?= h($filters['turno_id']) ?>&status=<?= h($filters['status']) ?>&grupo_nome=<?= h($filters['grupo_nome'] ?? '') ?>">
            <i class="bi bi-download me-1"></i>Exportar CSV
        </a>
        <a class="btn btn-primary js-export-btn" data-toast="Exportado com sucesso. O download Excel foi iniciado." href="/?r=relatoriosTematicos/export&type=xlsx&data=<?= h($filters['data']) ?>&data_inicio=<?= h($filters['data_inicio']) ?>&data_fim=<?= h($filters['data_fim']) ?>&restaurante_id=<?= h($filters['restaurante_id']) ?>&turno_id=<?= h($filters['turno_id']) ?>&status=<?= h($filters['status']) ?>&grupo_nome=<?= h($filters['grupo_nome'] ?? '') ?>">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Exportar Excel
        </a>
    </div>
    <form class="row g-3 align-items-end" method="get" action="/" data-ajax-filter data-ajax-target=".app-content">
        <input type="hidden" name="r" value="relatoriosTematicos/index">
        <div class="col-12 col-md-3">
            <label class="form-label">Data (única)</label>
            <input type="date" class="form-control input-xl" name="data" value="<?= h($filters['data'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Data início</label>
            <input type="date" class="form-control input-xl" name="data_inicio" value="<?= h($filters['data_inicio'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Data fim</label>
            <input type="date" class="form-control input-xl" name="data_fim" value="<?= h($filters['data_fim'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Restaurante</label>
            <select class="form-select input-xl" name="restaurante_id">
                <option value="">Todos</option>
                <?php foreach ($restaurantes as $rest): ?>
                    <option value="<?= (int)$rest['id'] ?>" <?= ($filters['restaurante_id'] ?? '') == $rest['id'] ? 'selected' : '' ?>>
                        <?= h($rest['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Turno</label>
            <select class="form-select input-xl" name="turno_id">
                <option value="">Todos</option>
                <?php foreach ($turnos as $turno): ?>
                    <option value="<?= (int)$turno['id'] ?>" <?= ($filters['turno_id'] ?? '') == $turno['id'] ? 'selected' : '' ?>>
                        <?= h($turno['hora']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select input-xl" name="status">
                <option value="">Todos</option>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= h($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>>
                        <?= h($status) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Grupo (nome)</label>
            <input type="text" class="form-control input-xl" name="grupo_nome" value="<?= h($filters['grupo_nome'] ?? '') ?>" placeholder="Ex: Famtour, Família, Evento...">
        </div>
        <div class="col-12 d-flex flex-wrap gap-2">
            <button class="btn btn-outline-primary" type="button" data-range="1">Ontem</button>
            <button class="btn btn-outline-primary" type="button" data-range="7">Últimos 7 dias</button>
            <button class="btn btn-outline-primary" type="button" data-range="30">Últimos 30 dias</button>
            <button class="btn btn-primary btn-xl">Aplicar filtros</button>
                    <a class="btn btn-primary btn-xl" href="/?r=relatoriosTematicos/index" data-ajax-link data-ajax-target=".app-content">Remover filtro</a>
        </div>
    </form>
</div>
<script>
(() => {
    const start = document.querySelector('input[name="data_inicio"]');
    const end = document.querySelector('input[name="data_fim"]');
    if (!start || !end) return;
    document.querySelectorAll('[data-range]').forEach(btn => {
        btn.addEventListener('click', () => {
            const fmt = (d) => d.toISOString().slice(0, 10);
            const days = parseInt(btn.dataset.range, 10);
            const today = new Date();
            const from = new Date();
            if (days === 1) {
                from.setDate(today.getDate() - 1);
                start.value = fmt(from);
                end.value = fmt(from);
                return;
            }
            from.setDate(today.getDate() - (days - 1));
            start.value = fmt(from);
            end.value = fmt(today);
        });
    });
})();
</script>

<div class="row g-4 mb-4">
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-calendar-check"></i></div>
                <div>
                    <div class="text-muted small">Reservas</div>
                    <div class="display-6 fw-bold"><?= (int)($summary['total_reservas'] ?? 0) ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-activity"></i>Total geral</span>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="text-muted small">PAX adulto (reservada)</div>
                    <div class="display-6 fw-bold"><?= (int)($summary['pax_adulto_reservadas'] ?? 0) ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-graph-up"></i>Total geral <?= (int)($summary['pax_reservadas'] ?? 0) ?></span>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-people"></i></div>
                <div>
                    <div class="text-muted small">PAX comparecidas</div>
                    <div class="display-6 fw-bold status-success"><?= (int)($summary['pax_comparecidas'] ?? 0) ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-check2-circle"></i>Taxa <?= h($taxaComparecimento) ?>%</span>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-person-x"></i></div>
                <div>
                    <div class="text-muted small">PAX CHD (reservada)</div>
                    <div class="display-6 fw-bold"><?= (int)($summary['pax_chd_reservadas'] ?? 0) ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-people"></i>Qtd CHD <?= (int)($summary['qtd_chd_reservadas'] ?? 0) ?></span>
        </div>
    </div>
</div>

<div class="card p-3 mb-4">
    <div class="d-flex flex-wrap gap-3">
        <span class="stat-chip"><i class="bi bi-diagram-3"></i>Lotes técnicos: <?= (int)($summary['total_lotes'] ?? $summary['total_grupos'] ?? 0) ?></span>
        <span class="stat-chip"><i class="bi bi-people"></i>Grupos nomeados: <?= (int)($summary['total_grupos_nomeados'] ?? 0) ?></span>
        <span class="stat-chip"><i class="bi bi-list-check"></i>Itens: <?= (int)($summary['total_reservas'] ?? 0) ?></span>
        <span class="stat-chip"><i class="bi bi-person-x"></i>PAX não comparecidas: <?= (int)($summary['pax_nao_comparecidas'] ?? 0) ?></span>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-lg-6">
        <div class="card p-4">
            <div class="section-title mb-3">
                <div class="icon"><i class="bi bi-shop-window"></i></div>
                <div>
                    <div class="text-uppercase text-muted small">Por restaurante</div>
                    <h5 class="fw-bold mb-0">Distribuição de reservas</h5>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Restaurante</th>
                            <th>Total</th>
                            <th>Lotes</th>
                            <th>Grupos nomeados</th>
                            <th>Finalizadas</th>
                            <th>No-show</th>
                            <th>Canceladas</th>
                            <th>PAX adulto</th>
                            <th>PAX CHD</th>
                            <th>PAX reservadas</th>
                            <th>PAX comparecidas</th>
                            <th>PAX faltantes</th>
                        </tr>
                    </thead>
                    <tbody id="rtByRestaurantBody" class="js-rt-paginated-body" data-page-size="8">
                        <?php foreach ($byRestaurant as $row): ?>
                            <tr>
                                <td><span class="tag <?= restaurant_badge_class($row['restaurante']) ?>"><?= h($row['restaurante']) ?></span></td>
                                <td><?= (int)$row['total'] ?></td>
                                <td><?= (int)($row['total_lotes'] ?? $row['total_grupos'] ?? 0) ?></td>
                                <td><?= (int)($row['total_grupos_nomeados'] ?? 0) ?></td>
                                <td><?= (int)$row['finalizadas'] ?></td>
                                <td><?= (int)$row['no_shows'] ?></td>
                                <td><?= (int)$row['canceladas'] ?></td>
                                <td><?= (int)($row['pax_adulto_reservadas'] ?? 0) ?></td>
                                <td><?= (int)($row['pax_chd_reservadas'] ?? 0) ?></td>
                                <td><?= (int)$row['pax_reservadas'] ?></td>
                                <td><?= (int)$row['pax_comparecidas'] ?></td>
                                <td><?= max(0, (int)$row['pax_reservadas'] - (int)$row['pax_comparecidas']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($byRestaurant)): ?>
                            <tr><td colspan="12" class="text-muted">Sem dados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div id="rtByRestaurantPagination" class="d-flex flex-wrap gap-2 mt-2"></div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card p-4">
            <div class="section-title mb-3">
                <div class="icon"><i class="bi bi-clock-history"></i></div>
                <div>
                    <div class="text-uppercase text-muted small">Por turno</div>
                    <h5 class="fw-bold mb-0">Fluxo por horário</h5>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Restaurante</th>
                            <th>Turno</th>
                            <th>Total</th>
                            <th>Lotes</th>
                            <th>Grupos nomeados</th>
                            <th>Finalizadas</th>
                            <th>No-show</th>
                            <th>Canceladas</th>
                            <th>PAX adulto</th>
                            <th>PAX CHD</th>
                        </tr>
                    </thead>
                    <tbody id="rtByTurnoBody" class="js-rt-paginated-body" data-page-size="8">
                        <?php foreach ($byTurno as $row): ?>
                            <tr>
                                <td><span class="tag <?= restaurant_badge_class($row['restaurante'] ?? '') ?>"><?= h($row['restaurante'] ?? '') ?></span></td>
                                <td><span class="tag badge-soft"><?= h($row['turno']) ?></span></td>
                                <td><?= (int)$row['total'] ?></td>
                                <td><?= (int)($row['total_lotes'] ?? $row['total_grupos'] ?? 0) ?></td>
                                <td><?= (int)($row['total_grupos_nomeados'] ?? 0) ?></td>
                                <td><?= (int)$row['finalizadas'] ?></td>
                                <td><?= (int)$row['no_shows'] ?></td>
                                <td><?= (int)$row['canceladas'] ?></td>
                                <td><?= (int)($row['pax_adulto_reservadas'] ?? 0) ?></td>
                                <td><?= (int)($row['pax_chd_reservadas'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($byTurno)): ?>
                            <tr><td colspan="10" class="text-muted">Sem dados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div id="rtByTurnoPagination" class="d-flex flex-wrap gap-2 mt-2"></div>
        </div>
    </div>
</div>

<div class="card p-4 mb-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-calendar3"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Por data</div>
            <h5 class="fw-bold mb-0">Resumo diário</h5>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Total</th>
                    <th>Lotes</th>
                    <th>Grupos nomeados</th>
                    <th>Finalizadas</th>
                    <th>No-show</th>
                    <th>Canceladas</th>
                    <th>PAX adulto</th>
                    <th>PAX CHD</th>
                    <th>PAX reservadas</th>
                    <th>PAX comparecidas</th>
                    <th>PAX faltantes</th>
                </tr>
            </thead>
            <tbody id="rtByDayBody" class="js-rt-paginated-body" data-page-size="10">
                <?php foreach ($byDay as $row): ?>
                    <tr>
                        <td><?= h($row['data']) ?></td>
                        <td><?= (int)$row['total'] ?></td>
                        <td><?= (int)($row['total_lotes'] ?? $row['total_grupos'] ?? 0) ?></td>
                        <td><?= (int)($row['total_grupos_nomeados'] ?? 0) ?></td>
                        <td><?= (int)$row['finalizadas'] ?></td>
                        <td><?= (int)$row['no_shows'] ?></td>
                        <td><?= (int)$row['canceladas'] ?></td>
                        <td><?= (int)($row['pax_adulto_reservadas'] ?? 0) ?></td>
                        <td><?= (int)($row['pax_chd_reservadas'] ?? 0) ?></td>
                        <td><?= (int)$row['pax_reservadas'] ?></td>
                        <td><?= (int)$row['pax_comparecidas'] ?></td>
                        <td><?= max(0, (int)$row['pax_reservadas'] - (int)$row['pax_comparecidas']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($byDay)): ?>
                    <tr><td colspan="12" class="text-muted">Sem dados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div id="rtByDayPagination" class="d-flex flex-wrap gap-2 mt-2"></div>
</div>

<div class="card p-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-list-check"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Base detalhada</div>
            <h5 class="fw-bold mb-0">Reservas temáticas</h5>
        </div>
    </div>
    <div class="row g-2 mb-3">
        <div class="col-12 col-md-6">
            <label class="form-label mb-1">Filtro da tabela</label>
            <input type="text" class="form-control" id="rtDetailFilter" placeholder="Nome, UH, grupo, restaurante, turno, status...">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label mb-1">Status</label>
            <select class="form-select" id="rtDetailStatus">
                <option value="">Todos</option>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= h($status) ?>"><?= h($status) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label mb-1">Restaurante</label>
            <select class="form-select" id="rtDetailRestaurant">
                <option value="">Todos</option>
                <?php foreach ($restaurantes as $rest): ?>
                    <option value="<?= h(mb_strtolower((string)$rest['nome'], 'UTF-8')) ?>"><?= h($rest['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Restaurante</th>
                    <th>Turno</th>
                    <th>Grupo</th>
                    <th>UH</th>
                    <th>Titular</th>
                    <th>PAX adulto</th>
                    <th>PAX CHD</th>
                    <th>PAX reservada</th>
                    <th>PAX real</th>
                    <th>Status</th>
                    <th>Usuário</th>
                    <th>Observação</th>
                </tr>
            </thead>
            <tbody id="rtDetailBody" class="js-rt-paginated-body" data-page-size="12">
                <?php foreach ($list as $row): ?>
                    <?php
                        $grupoDisplay = normalize_mojibake((string)($row['grupo_nome_display'] ?? $row['grupo_nome'] ?? $row['grupo_responsavel'] ?? ''));
                        if (trim($grupoDisplay) === '' || $grupoDisplay === '-') {
                            $grupoDisplay = '-';
                        }
                        $titularDisplay = normalize_mojibake((string)($row['titular_nome_display'] ?? $row['titular_nome'] ?? '-'));
                        $searchStr = mb_strtolower(trim(implode(' ', [
                            (string)($row['data_reserva'] ?? ''),
                            normalize_mojibake((string)($row['restaurante'] ?? '')),
                            (string)($row['turno_hora'] ?? ''),
                            (string)$grupoDisplay,
                            (string)($row['uh_numero'] ?? ''),
                            (string)$titularDisplay,
                            (string)($row['status'] ?? ''),
                            normalize_mojibake((string)($row['usuario'] ?? '')),
                            normalize_mojibake((string)($row['observacao_reserva'] ?? '')),
                        ])), 'UTF-8');
                    ?>
                    <tr class="js-rt-detail-row"
                        data-search="<?= h($searchStr) ?>"
                        data-status="<?= h((string)($row['status'] ?? '')) ?>"
                        data-rest="<?= h(mb_strtolower(normalize_mojibake((string)($row['restaurante'] ?? '')), 'UTF-8')) ?>">
                        <td><?= h($row['data_reserva']) ?></td>
                        <td><span class="tag <?= restaurant_badge_class($row['restaurante']) ?>"><?= h($row['restaurante']) ?></span></td>
                        <td><span class="tag badge-soft"><?= h($row['turno_hora']) ?></span></td>
                        <td>
                            <?php if ($grupoDisplay !== '-'): ?>
                                <div><?= h($grupoDisplay) ?></div>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                            <?php if (!empty($row['grupo_id'])): ?>
                                <div class="text-muted small">Lote #<?= (int)$row['grupo_id'] ?></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="uh-badge <?= uh_badge_class($row['uh_numero']) ?>"><?= h($row['uh_numero']) ?></span></td>
                        <td><?= h($titularDisplay) ?></td>
                        <td><?= h((string)($row['pax_adulto_calc'] ?? '-')) ?></td>
                        <td><?= h((string)($row['pax_chd_calc'] ?? '-')) ?></td>
                        <td><?= h($row['pax']) ?></td>
                        <td><?= h($row['pax_real'] ?? '-') ?></td>
                        <td><span class="badge badge-soft"><?= h($row['status']) ?></span></td>
                        <td><?= h($row['usuario'] ?? '-') ?></td>
                        <td><?= h($row['observacao_reserva'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($list)): ?>
                    <tr><td colspan="13" class="text-muted">Sem reservas para o filtro atual.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div id="rtDetailPagination" class="d-flex flex-wrap gap-2 mt-2"></div>
</div>


<script>
(() => {
    const normalize = (value) => (value || '')
        .toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();

    const renderPager = (container, totalPages, currentPage, onSelect) => {
        if (!container) return;
        container.innerHTML = '';
        if (totalPages <= 1) return;

        const appendPageBtn = (page) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `btn btn-sm ${page === currentPage ? 'btn-primary' : 'btn-outline-primary'}`;
            btn.textContent = String(page);
            btn.addEventListener('click', () => onSelect(page));
            container.appendChild(btn);
        };

        const appendDots = () => {
            const dots = document.createElement('span');
            dots.className = 'text-muted px-1 align-self-center';
            dots.textContent = '...';
            container.appendChild(dots);
        };

        if (totalPages <= 9) {
            for (let i = 1; i <= totalPages; i++) {
                appendPageBtn(i);
            }
            return;
        }

        const visiblePages = new Set([1, totalPages, currentPage, currentPage - 1, currentPage + 1]);
        if (currentPage <= 4) {
            for (let i = 2; i <= 5 && i < totalPages; i++) visiblePages.add(i);
        }
        if (currentPage >= totalPages - 3) {
            for (let i = totalPages - 4; i < totalPages; i++) {
                if (i > 1) visiblePages.add(i);
            }
        }

        const orderedPages = Array.from(visiblePages)
            .filter((n) => n >= 1 && n <= totalPages)
            .sort((a, b) => a - b);

        let prev = 0;
        for (const page of orderedPages) {
            if (prev > 0 && page - prev > 1) {
                if (page - prev === 2) {
                    appendPageBtn(prev + 1);
                } else {
                    appendDots();
                }
            }
            appendPageBtn(page);
            prev = page;
        }
    };

    const paginateRows = (rows, page, pageSize) => {
        const totalPages = Math.max(1, Math.ceil(rows.length / pageSize));
        const current = Math.min(Math.max(1, page), totalPages);
        const start = (current - 1) * pageSize;
        const end = start + pageSize;
        rows.forEach((row, idx) => {
            row.style.display = (idx >= start && idx < end) ? '' : 'none';
        });
        return { totalPages, current };
    };

    const simpleTables = [
        ['rtByRestaurantBody', 'rtByRestaurantPagination'],
        ['rtByTurnoBody', 'rtByTurnoPagination'],
        ['rtByDayBody', 'rtByDayPagination'],
    ];
    simpleTables.forEach(([bodyId, pagerId]) => {
        const body = document.getElementById(bodyId);
        const pager = document.getElementById(pagerId);
        if (!body) return;
        const allRows = Array.from(body.querySelectorAll('tr')).filter((tr) => !tr.querySelector('td[colspan]'));
        if (allRows.length === 0) return;
        let page = 1;
        const pageSize = parseInt(body.getAttribute('data-page-size') || '10', 10) || 10;
        const paint = () => {
            const result = paginateRows(allRows, page, pageSize);
            page = result.current;
            renderPager(pager, result.totalPages, page, (next) => {
                page = next;
                paint();
            });
        };
        paint();
    });

    const detailBody = document.getElementById('rtDetailBody');
    const detailPager = document.getElementById('rtDetailPagination');
    if (detailBody) {
        const detailRows = Array.from(detailBody.querySelectorAll('tr.js-rt-detail-row'));
        const input = document.getElementById('rtDetailFilter');
        const status = document.getElementById('rtDetailStatus');
        const rest = document.getElementById('rtDetailRestaurant');
        let page = 1;
        const pageSize = parseInt(detailBody.getAttribute('data-page-size') || '12', 10) || 12;

        const apply = (resetPage = true) => {
            if (resetPage) page = 1;
            const term = normalize(input?.value || '');
            const st = (status?.value || '').trim();
            const rs = normalize(rest?.value || '');
            const filtered = detailRows.filter((row) => {
                const okTerm = !term || normalize(row.dataset.search || '').includes(term);
                const okStatus = !st || (row.dataset.status || '') === st;
                const okRest = !rs || normalize(row.dataset.rest || '') === rs;
                return okTerm && okStatus && okRest;
            });
            detailRows.forEach((row) => { row.style.display = 'none'; });
            filtered.forEach((row) => detailBody.appendChild(row));
            const result = paginateRows(filtered, page, pageSize);
            page = result.current;
            renderPager(detailPager, result.totalPages, page, (next) => {
                page = next;
                apply(false);
            });
        };

        input?.addEventListener('input', () => apply(true));
        status?.addEventListener('change', () => apply(true));
        rest?.addEventListener('change', () => apply(true));
        apply(true);
    }
})();
</script>

</div>
