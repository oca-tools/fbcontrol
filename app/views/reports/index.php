<?php
$filters = $this->data['filters'] ?? [];
$restaurantes = $this->data['restaurantes'] ?? [];
$operacoes = $this->data['operacoes'] ?? [];
$list = $this->data['list'] ?? [];
$journey = $this->data['journey'] ?? [];
$summary = $this->data['summary'] ?? [];
$dailyMap = $this->data['daily_map'] ?? [];
$dailyMapPaged = $this->data['daily_map_paged'] ?? $dailyMap;
$mapPage = (int)($this->data['map_page'] ?? 1);
$mapTotalPages = (int)($this->data['map_total_pages'] ?? 1);
$mapTotal = (int)($this->data['map_total'] ?? count($dailyMap));
$listPaged = $this->data['list_paged'] ?? $list;
$biPage = (int)($this->data['bi_page'] ?? 1);
$biTotalPages = (int)($this->data['bi_total_pages'] ?? 1);
$biTotal = (int)($this->data['bi_total'] ?? count($list));
$colaboradores = $this->data['colaboradores'] ?? [];
$vouchers = $this->data['vouchers'] ?? [];
$colaboradoresPaged = $this->data['colaboradores_paged'] ?? $colaboradores;
$colabPage = (int)($this->data['colab_page'] ?? 1);
$colabTotalPages = (int)($this->data['colab_total_pages'] ?? 1);
$colabTotal = (int)($this->data['colab_total'] ?? count($colaboradores));
$vouchersPaged = $this->data['vouchers_paged'] ?? $vouchers;
$voucherPage = (int)($this->data['voucher_page'] ?? 1);
$voucherTotalPages = (int)($this->data['voucher_total_pages'] ?? 1);
$voucherTotal = (int)($this->data['voucher_total'] ?? count($vouchers));
$totalRegistros = count($list);
$totalPax = array_sum(array_map(fn($r) => (int)$r['pax'], $list));
$duplicados = count(array_filter($list, fn($r) => $r['alerta_duplicidade']));
$foraHorario = count(array_filter($list, fn($r) => $r['fora_do_horario']));
$multiplos = count(array_filter($list, fn($r) => ($r['status_operacional'] ?? '') === 'Múltiplo Acesso'));
$naoInformadoAcessos = count(array_filter($list, fn($r) => (string)($r['uh_numero'] ?? '') === '998'));
$naoInformadoPax = array_sum(array_map(fn($r) => ((string)($r['uh_numero'] ?? '') === '998') ? (int)$r['pax'] : 0, $list));
$dayUseAcessos = count(array_filter($list, fn($r) => (string)($r['uh_numero'] ?? '') === '999'));
$dayUsePax = array_sum(array_map(fn($r) => ((string)($r['uh_numero'] ?? '') === '999') ? (int)$r['pax'] : 0, $list));
$vipPremiumAcessos = count(array_filter($list, fn($r) => strpos(mb_strtolower(($r['restaurante'] ?? '') . ' ' . ($r['operacao'] ?? ''), 'UTF-8'), 'vip premium') !== false));
$vipPremiumPax = array_sum(array_map(fn($r) => (strpos(mb_strtolower(($r['restaurante'] ?? '') . ' ' . ($r['operacao'] ?? ''), 'UTF-8'), 'vip premium') !== false) ? (int)$r['pax'] : 0, $list));
$dupPercent = $totalRegistros > 0 ? round(($duplicados / $totalRegistros) * 100) : 0;
$foraPercent = $totalRegistros > 0 ? round(($foraHorario / $totalRegistros) * 100) : 0;
?>
<div class="card card-soft p-4 mb-4">
    <div class="d-flex justify-content-between align-items-start">
            <div class="section-title">
            <div class="icon"><i class="bi bi-file-earmark-text"></i></div>
            <div>
                <div class="text-uppercase text-muted small">Relatórios</div>
                <h3 class="fw-bold mb-1">Relatórios Operacionais</h3>
                <div class="text-muted">Filtre por data, UH, restaurante e operação.</div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="/?r=relatorios/export&type=csv&data=<?= h($filters['data']) ?>&data_inicio=<?= h($filters['data_inicio']) ?>&data_fim=<?= h($filters['data_fim']) ?>&uh_numero=<?= h($filters['uh_numero']) ?>&restaurante_id=<?= h($filters['restaurante_id']) ?>&operacao_id=<?= h($filters['operacao_id']) ?>&status=<?= h($filters['status'] ?? '') ?>">
                <i class="bi bi-download me-1"></i>Exportar CSV
            </a>
            <a class="btn btn-primary" href="/?r=relatorios/export&type=xlsx&data=<?= h($filters['data']) ?>&data_inicio=<?= h($filters['data_inicio']) ?>&data_fim=<?= h($filters['data_fim']) ?>&uh_numero=<?= h($filters['uh_numero']) ?>&restaurante_id=<?= h($filters['restaurante_id']) ?>&operacao_id=<?= h($filters['operacao_id']) ?>&status=<?= h($filters['status'] ?? '') ?>">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Exportar Excel
            </a>
        </div>
    </div>
    <form class="row g-3 align-items-end mt-2" method="get" action="/">
        <input type="hidden" name="r" value="relatorios/index">
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
            <label class="form-label">UH</label>
            <input type="text" class="form-control input-xl" name="uh_numero" placeholder="Ex: 101" value="<?= h($filters['uh_numero'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Restaurante</label>
            <select class="form-select input-xl" name="restaurante_id">
                <option value="">Todos</option>
                <?php foreach ($restaurantes as $item): ?>
                    <option value="<?= (int)$item['id'] ?>" <?= ($filters['restaurante_id'] ?? '') == $item['id'] ? 'selected' : '' ?>>
                        <?= h($item['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Operação</label>
            <select class="form-select input-xl" name="operacao_id">
                <option value="">Todas</option>
                <?php foreach ($operacoes as $item): ?>
                    <option value="<?= (int)$item['id'] ?>" <?= ($filters['operacao_id'] ?? '') == $item['id'] ? 'selected' : '' ?>>
                        <?= h($item['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select input-xl" name="status">
                <option value="">Todos</option>
                <option value="ok" <?= ($filters['status'] ?? '') === 'ok' ? 'selected' : '' ?>>OK</option>
                <option value="duplicado" <?= ($filters['status'] ?? '') === 'duplicado' ? 'selected' : '' ?>>Duplicado</option>
                <option value="fora_horario" <?= ($filters['status'] ?? '') === 'fora_horario' ? 'selected' : '' ?>>Fora do horário</option>
                <option value="multiplo" <?= ($filters['status'] ?? '') === 'multiplo' ? 'selected' : '' ?>>Múltiplo acesso</option>
                <option value="nao_informado" <?= ($filters['status'] ?? '') === 'nao_informado' ? 'selected' : '' ?>>Não informado</option>
                <option value="day_use" <?= ($filters['status'] ?? '') === 'day_use' ? 'selected' : '' ?>>Day use</option>
            </select>
        </div>
        <div class="col-12 d-flex flex-wrap gap-2">
            <button class="btn btn-outline-primary" type="button" data-range="1">Ontem</button>
            <button class="btn btn-outline-primary" type="button" data-range="7">Últimos 7 dias</button>
            <button class="btn btn-outline-primary" type="button" data-range="30">Últimos 30 dias</button>
            <button class="btn btn-primary btn-xl">Aplicar filtros</button>
            <a class="btn btn-primary btn-xl" href="/?r=relatorios/index">Remover filtro</a>
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
            const fmt = (d) => d.toISOString().slice(0,10);
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

<?php if (!empty($filters['data_inicio']) && !empty($filters['data_fim']) && $filters['data_inicio'] !== $filters['data_fim']): ?>
    <div class="alert alert-warning">Mapa diário é exibido apenas para uma data única. Para visualizar o mapa, informe apenas a Data (única).</div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-clipboard-data"></i></div>
                <div>
                    <div class="text-muted small">Registros</div>
                    <div class="display-6 fw-bold"><?= $totalRegistros ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-filter"></i> Filtro atual</span>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-people"></i></div>
                <div>
                    <div class="text-muted small">Total de PAX</div>
                    <div class="display-6 fw-bold"><?= $totalPax ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-graph-up"></i> Consolidação</span>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-exclamation-triangle"></i></div>
                <div>
                    <div class="text-muted small">Duplicidades</div>
                    <div class="display-6 fw-bold status-warning"><?= $duplicados ?></div>
                </div>
            </div>
            <div class="progress mt-3" style="height:6px;">
                <div class="progress-bar bg-warning" style="width: <?= $dupPercent ?>%"></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-clock-history"></i></div>
                <div>
                    <div class="text-muted small">Fora do horário</div>
                    <div class="display-6 fw-bold status-danger"><?= $foraHorario ?></div>
                </div>
            </div>
            <div class="progress mt-3" style="height:6px;">
                <div class="progress-bar bg-danger" style="width: <?= $foraPercent ?>%"></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-arrow-repeat"></i></div>
                <div>
                    <div class="text-muted small">Múltiplos acessos</div>
                    <div class="display-6 fw-bold"><?= $multiplos ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-repeat"></i> UH repetente</span>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-question-circle"></i></div>
                <div>
                    <div class="text-muted small">Não informado</div>
                    <div class="display-6 fw-bold"><?= $naoInformadoAcessos ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-people"></i> PAX <?= $naoInformadoPax ?></span>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-sun"></i></div>
                <div>
                    <div class="text-muted small">Day use</div>
                    <div class="display-6 fw-bold"><?= $dayUseAcessos ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-people"></i> PAX <?= $dayUsePax ?></span>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon"><i class="bi bi-gem"></i></div>
                <div>
                    <div class="text-muted small">VIP Premium</div>
                    <div class="display-6 fw-bold"><?= $vipPremiumAcessos ?></div>
                </div>
            </div>
            <span class="stat-chip mt-3"><i class="bi bi-people"></i> PAX <?= $vipPremiumPax ?></span>
        </div>
    </div>
</div>

<?php if (!empty($filters['uh_numero'])): ?>
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-6">
            <div class="card p-4">
                <div class="section-title mb-3">
                    <div class="icon"><i class="bi bi-house"></i></div>
                    <div>
                        <div class="text-uppercase text-muted small">Resumo da UH</div>
                        <h5 class="fw-bold mb-0">UH <span class="uh-badge <?= uh_badge_class($filters['uh_numero']) ?>"><?= h(uh_label($filters['uh_numero'])) ?></span></h5>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Restaurante</th>
                                <th>Operação</th>
                                <th>Primeira passagem</th>
                                <th>Última passagem</th>
                                <th>Acessos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($summary as $row): ?>
                                <tr>
                                    <td><span class="tag <?= restaurant_badge_class($row['restaurante']) ?>"><?= h($row['restaurante']) ?></span></td>
                                    <td><span class="tag <?= operation_badge_class($row['operacao']) ?>"><?= h($row['operacao']) ?></span></td>
                                    <td><?= h($row['primeira_passagem']) ?></td>
                                    <td><?= h($row['ultima_passagem']) ?></td>
                                    <td><?= h($row['acessos']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($summary)): ?>
                                <tr><td colspan="5" class="text-muted">Sem registros para a UH.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card p-4">
                <div class="section-title mb-3">
                    <div class="icon"><i class="bi bi-clock-history"></i></div>
                    <div>
                        <div class="text-uppercase text-muted small">Linha do tempo</div>
                        <h5 class="fw-bold mb-0">Movimentação da UH</h5>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Horário</th>
                                <th>Restaurante</th>
                                <th>Operação</th>
                                <th>Usuário</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($journey as $row): ?>
                                <tr>
                                    <td><?= h($row['criado_em']) ?></td>
                                    <td><span class="tag <?= restaurant_badge_class($row['restaurante']) ?>"><?= h($row['restaurante']) ?></span></td>
                                    <td><span class="tag <?= operation_badge_class($row['operacao']) ?>"><?= h($row['operacao']) ?></span></td>
                                    <td><?= h($row['usuario']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($journey)): ?>
                                <tr><td colspan="4" class="text-muted">Sem registros para a UH.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card p-4 mb-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-map"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Mapa diário</div>
            <h5 class="fw-bold mb-0">Mapa diário por UH (<?= h($filters['data']) ?>)</h5>
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2 mb-3">
        <a class="btn btn-outline-primary" href="/?r=relatorios/export_mapa&type=csv&data=<?= h($filters['data']) ?>">
            <i class="bi bi-download me-1"></i>Exportar CSV
        </a>
        <a class="btn btn-primary" href="/?r=relatorios/export_mapa&type=xlsx&data=<?= h($filters['data']) ?>">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Exportar Excel
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>UH</th>
                    <th>Café</th>
                    <th>Almoço</th>
                    <th>Jantar</th>
                    <th>Temático</th>
                    <th>Privileged</th>
                    <th>VIP Premium</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dailyMapPaged as $row): ?>
                    <tr>
                        <td><span class="uh-badge <?= uh_badge_class($row['uh_numero']) ?>"><?= h(uh_label($row['uh_numero'])) ?></span></td>
                        <td><?= $row['cafe'] ? '<span class="badge badge-success">Sim</span>' : '<span class="badge badge-danger">Não</span>' ?></td>
                        <td><?= $row['almoco'] ? '<span class="badge badge-success">Sim</span>' : '<span class="badge badge-danger">Não</span>' ?></td>
                        <td><?= $row['jantar'] ? '<span class="badge badge-success">Sim</span>' : '<span class="badge badge-danger">Não</span>' ?></td>
                        <td><?= $row['tematico'] ? '<span class="badge badge-success">Sim</span>' : '<span class="badge badge-danger">Não</span>' ?></td>
                        <td><?= $row['privileged'] ? '<span class="badge badge-success">Sim</span>' : '<span class="badge badge-danger">Não</span>' ?></td>
                        <td><?= !empty($row['vip_premium']) ? '<span class="badge badge-success">Sim</span>' : '<span class="badge badge-danger">Não</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($dailyMapPaged)): ?>
                    <tr><td colspan="7" class="text-muted">Sem registros no dia.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($mapTotalPages > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <span class="text-muted small">Mapa diário: <?= $mapTotal ?> UHs (20 por página)</span>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= $mapTotalPages; $i++): ?>
                    <?php $mapQuery = http_build_query(array_merge($filters, ['r' => 'relatorios/index', 'map_page' => $i, 'bi_page' => $biPage])); ?>
                    <li class="page-item <?= $i === $mapPage ? 'active' : '' ?>">
                        <a class="page-link" href="/?<?= h($mapQuery) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<div class="card p-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-database"></i></div>
        <div>
            <div class="text-uppercase text-muted small">BI & auditoria</div>
            <h5 class="fw-bold mb-0">Base completa (para BI)</h5>
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2 mb-3">
        <a class="btn btn-outline-primary" href="/?r=relatorios/export_bi&type=csv&data=<?= h($filters['data']) ?>&data_inicio=<?= h($filters['data_inicio']) ?>&data_fim=<?= h($filters['data_fim']) ?>&uh_numero=<?= h($filters['uh_numero']) ?>&restaurante_id=<?= h($filters['restaurante_id']) ?>&operacao_id=<?= h($filters['operacao_id']) ?>&status=<?= h($filters['status'] ?? '') ?>">
            <i class="bi bi-download me-1"></i>Exportar CSV
        </a>
        <a class="btn btn-primary" href="/?r=relatorios/export_bi&type=xlsx&data=<?= h($filters['data']) ?>&data_inicio=<?= h($filters['data_inicio']) ?>&data_fim=<?= h($filters['data_fim']) ?>&uh_numero=<?= h($filters['uh_numero']) ?>&restaurante_id=<?= h($filters['restaurante_id']) ?>&operacao_id=<?= h($filters['operacao_id']) ?>&status=<?= h($filters['status'] ?? '') ?>">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Exportar Excel
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th>Data/Hora</th>
                    <th>UH</th>
                    <th>PAX</th>
                    <th>Restaurante</th>
                    <th>Operação</th>
                    <th>Porta</th>
                    <th>Usuário</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listPaged as $row): ?>
                    <tr>
                        <td><span class="badge badge-soft">Acesso</span></td>
                        <td>
                            <?php if (($row['status_operacional'] ?? '') === 'Duplicado'): ?>
                                <span class="badge badge-warning">Duplicado</span>
                            <?php elseif (($row['status_operacional'] ?? '') === 'Fora do Horário'): ?>
                                <span class="badge badge-danger">Fora do horário</span>
                            <?php elseif (($row['status_operacional'] ?? '') === 'Múltiplo Acesso'): ?>
                                <span class="badge badge-soft">Múltiplo acesso</span>
                            <?php else: ?>
                                <span class="badge badge-success">OK</span>
                            <?php endif; ?>
                        </td>
                        <td><?= h($row['criado_em']) ?></td>
                        <td><span class="uh-badge <?= uh_badge_class($row['uh_numero']) ?>"><?= h(uh_label($row['uh_numero'])) ?></span></td>
                        <td><?= h($row['pax']) ?></td>
                        <td><span class="tag <?= restaurant_badge_class($row['restaurante']) ?>"><?= h($row['restaurante']) ?></span></td>
                        <td><span class="tag <?= operation_badge_class($row['operacao']) ?>"><?= h($row['operacao']) ?></span></td>
                        <td><?= h($row['porta'] ?? '-') ?></td>
                        <td><?= h($row['usuario']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($listPaged)): ?>
                    <tr><td colspan="9" class="text-muted">Sem registros para o filtro atual.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($biTotalPages > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <span class="text-muted small">Base BI: <?= $biTotal ?> registros (20 por página)</span>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= $biTotalPages; $i++): ?>
                    <?php $biQuery = http_build_query(array_merge($filters, ['r' => 'relatorios/index', 'bi_page' => $i, 'map_page' => $mapPage])); ?>
                    <li class="page-item <?= $i === $biPage ? 'active' : '' ?>">
                        <a class="page-link" href="/?<?= h($biQuery) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<div class="card p-4 mt-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-person-badge"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Colaboradores</div>
            <h5 class="fw-bold mb-0">Refeições por colaborador</h5>
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2 mb-3">
        <a class="btn btn-outline-primary" href="/?r=relatorios/export_colaboradores&type=csv&data=<?= h($filters['data']) ?>&data_inicio=<?= h($filters['data_inicio']) ?>&data_fim=<?= h($filters['data_fim']) ?>&restaurante_id=<?= h($filters['restaurante_id']) ?>&operacao_id=<?= h($filters['operacao_id']) ?>">
            <i class="bi bi-download me-1"></i>Exportar CSV
        </a>
        <a class="btn btn-primary" href="/?r=relatorios/export_colaboradores&type=xlsx&data=<?= h($filters['data']) ?>&data_inicio=<?= h($filters['data_inicio']) ?>&data_fim=<?= h($filters['data_fim']) ?>&restaurante_id=<?= h($filters['restaurante_id']) ?>&operacao_id=<?= h($filters['operacao_id']) ?>">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Exportar Excel
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Colaborador</th>
                    <th>Quantidade</th>
                    <th>Restaurante</th>
                    <th>Operação</th>
                    <th>Usuário</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($colaboradoresPaged as $row): ?>
                    <tr>
                        <td><?= h($row['criado_em']) ?></td>
                        <td><?= h($row['nome_colaborador']) ?></td>
                        <td><?= h($row['quantidade']) ?></td>
                        <td><span class="tag <?= restaurant_badge_class($row['restaurante']) ?>"><?= h($row['restaurante']) ?></span></td>
                        <td><span class="tag <?= operation_badge_class($row['operacao']) ?>"><?= h($row['operacao']) ?></span></td>
                        <td><?= h($row['usuario']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($colaboradoresPaged)): ?>
                    <tr><td colspan="6" class="text-muted">Sem registros de colaboradores.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($colabTotalPages > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <span class="text-muted small">Colaboradores: <?= $colabTotal ?> registros (20 por página)</span>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= $colabTotalPages; $i++): ?>
                    <?php $colabQuery = http_build_query(array_merge($filters, ['r' => 'relatorios/index', 'colab_page' => $i, 'map_page' => $mapPage, 'bi_page' => $biPage, 'voucher_page' => $voucherPage])); ?>
                    <li class="page-item <?= $i === $colabPage ? 'active' : '' ?>">
                        <a class="page-link" href="/?<?= h($colabQuery) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<div class="card p-4 mt-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-ticket-perforated"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Vouchers</div>
            <h5 class="fw-bold mb-0">Vouchers registrados</h5>
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2 mb-3">
        <a class="btn btn-outline-primary" href="/?r=relatorios/export_vouchers&type=csv&data=<?= h($filters['data']) ?>&data_inicio=<?= h($filters['data_inicio']) ?>&data_fim=<?= h($filters['data_fim']) ?>&restaurante_id=<?= h($filters['restaurante_id']) ?>&operacao_id=<?= h($filters['operacao_id']) ?>">
            <i class="bi bi-download me-1"></i>Exportar CSV
        </a>
        <a class="btn btn-primary" href="/?r=relatorios/export_vouchers&type=xlsx&data=<?= h($filters['data']) ?>&data_inicio=<?= h($filters['data_inicio']) ?>&data_fim=<?= h($filters['data_fim']) ?>&restaurante_id=<?= h($filters['restaurante_id']) ?>&operacao_id=<?= h($filters['operacao_id']) ?>">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Exportar Excel
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Hóspede</th>
                    <th>Estadia</th>
                    <th>Reserva</th>
                    <th>Serviço</th>
                    <th>Assinatura</th>
                    <th>Data da venda</th>
                    <th>Anexo</th>
                    <th>Restaurante</th>
                    <th>Operação</th>
                    <th>Usuário</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vouchersPaged as $row): ?>
                    <tr>
                        <td><?= h($row['criado_em']) ?></td>
                        <td><?= h($row['nome_hospede']) ?></td>
                        <td><?= h($row['data_estadia']) ?></td>
                        <td><?= h($row['numero_reserva']) ?></td>
                        <td><?= h($row['servico_upselling']) ?></td>
                        <td><?= h($row['assinatura']) ?></td>
                        <td><?= h($row['data_venda']) ?></td>
                        <td>
                            <?php if (!empty($row['voucher_anexo_path'])): ?>
                                <a class="btn btn-outline-primary btn-sm" href="<?= h($row['voucher_anexo_path']) ?>" target="_blank">Abrir</a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="tag <?= restaurant_badge_class($row['restaurante']) ?>"><?= h($row['restaurante']) ?></span></td>
                        <td><span class="tag <?= operation_badge_class($row['operacao']) ?>"><?= h($row['operacao']) ?></span></td>
                        <td><?= h($row['usuario']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($vouchersPaged)): ?>
                    <tr><td colspan="11" class="text-muted">Sem vouchers registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($voucherTotalPages > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <span class="text-muted small">Vouchers: <?= $voucherTotal ?> registros (20 por página)</span>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= $voucherTotalPages; $i++): ?>
                    <?php $voucherQuery = http_build_query(array_merge($filters, ['r' => 'relatorios/index', 'voucher_page' => $i, 'map_page' => $mapPage, 'bi_page' => $biPage, 'colab_page' => $colabPage])); ?>
                    <li class="page-item <?= $i === $voucherPage ? 'active' : '' ?>">
                        <a class="page-link" href="/?<?= h($voucherQuery) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>



