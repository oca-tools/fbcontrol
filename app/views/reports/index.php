<?php
$filters = $this->data['filters'] ?? [];
$restaurantes = $this->data['restaurantes'] ?? [];
$operacoes = $this->data['operacoes'] ?? [];
$list = $this->data['list'] ?? [];
$journey = $this->data['journey'] ?? [];
$summary = $this->data['summary'] ?? [];
$dailyMap = $this->data['daily_map'] ?? [];
$colaboradores = $this->data['colaboradores'] ?? [];
$vouchers = $this->data['vouchers'] ?? [];
$totalRegistros = count($list);
$totalPax = array_sum(array_map(fn($r) => (int)$r['pax'], $list));
$duplicados = count(array_filter($list, fn($r) => $r['alerta_duplicidade']));
$foraHorario = count(array_filter($list, fn($r) => $r['fora_do_horario']));
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
            <a class="btn btn-outline-primary" href="/?r=relatorios/export&type=csv&data=<?= h($filters['data']) ?>&data_inicio=<?= h($filters['data_inicio']) ?>&data_fim=<?= h($filters['data_fim']) ?>&uh_numero=<?= h($filters['uh_numero']) ?>&restaurante_id=<?= h($filters['restaurante_id']) ?>&operacao_id=<?= h($filters['operacao_id']) ?>">
                <i class="bi bi-download me-1"></i>Exportar CSV
            </a>
            <a class="btn btn-primary" href="/?r=relatorios/export&type=xlsx&data=<?= h($filters['data']) ?>&data_inicio=<?= h($filters['data_inicio']) ?>&data_fim=<?= h($filters['data_fim']) ?>&uh_numero=<?= h($filters['uh_numero']) ?>&restaurante_id=<?= h($filters['restaurante_id']) ?>&operacao_id=<?= h($filters['operacao_id']) ?>">
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
            const days = parseInt(btn.dataset.range, 10);
            const today = new Date();
            const from = new Date();
            from.setDate(today.getDate() - (days - 1));
            const fmt = (d) => d.toISOString().slice(0,10);
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
</div>

<?php if (!empty($filters['uh_numero'])): ?>
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-6">
            <div class="card p-4">
                <div class="section-title mb-3">
                    <div class="icon"><i class="bi bi-house"></i></div>
                    <div>
                        <div class="text-uppercase text-muted small">Resumo da UH</div>
                        <h5 class="fw-bold mb-0">UH <span class="uh-badge <?= uh_badge_class($filters['uh_numero']) ?>"><?= h($filters['uh_numero']) ?></span></h5>
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
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dailyMap as $row): ?>
                    <tr>
                        <td><span class="uh-badge <?= uh_badge_class($row['uh_numero']) ?>"><?= h($row['uh_numero']) ?></span></td>
                        <td><?= $row['cafe'] ? '<span class="badge badge-success">Sim</span>' : '<span class="badge badge-danger">Não</span>' ?></td>
                        <td><?= $row['almoco'] ? '<span class="badge badge-success">Sim</span>' : '<span class="badge badge-danger">Não</span>' ?></td>
                        <td><?= $row['jantar'] ? '<span class="badge badge-success">Sim</span>' : '<span class="badge badge-danger">Não</span>' ?></td>
                        <td><?= $row['tematico'] ? '<span class="badge badge-success">Sim</span>' : '<span class="badge badge-danger">Não</span>' ?></td>
                        <td><?= $row['privileged'] ? '<span class="badge badge-success">Sim</span>' : '<span class="badge badge-danger">Não</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($dailyMap)): ?>
                    <tr><td colspan="6" class="text-muted">Sem registros no dia.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card p-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-database"></i></div>
        <div>
            <div class="text-uppercase text-muted small">BI & auditoria</div>
            <h5 class="fw-bold mb-0">Base completa (para BI)</h5>
        </div>
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
                <?php foreach ($list as $row): ?>
                    <tr>
                        <td><span class="badge badge-soft">Acesso</span></td>
                        <td>
                            <?php if ($row['alerta_duplicidade']): ?>
                                <span class="badge badge-warning">Duplicado</span>
                            <?php elseif ($row['fora_do_horario']): ?>
                                <span class="badge badge-danger">Fora do horário</span>
                            <?php else: ?>
                                <span class="badge badge-success">OK</span>
                            <?php endif; ?>
                        </td>
                        <td><?= h($row['criado_em']) ?></td>
                        <td><span class="uh-badge <?= uh_badge_class($row['uh_numero']) ?>"><?= h($row['uh_numero']) ?></span></td>
                        <td><?= h($row['pax']) ?></td>
                        <td><span class="tag <?= restaurant_badge_class($row['restaurante']) ?>"><?= h($row['restaurante']) ?></span></td>
                        <td><span class="tag <?= operation_badge_class($row['operacao']) ?>"><?= h($row['operacao']) ?></span></td>
                        <td><?= h($row['porta'] ?? '-') ?></td>
                        <td><?= h($row['usuario']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($list)): ?>
                    <tr><td colspan="9" class="text-muted">Sem registros para o filtro atual.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card p-4 mt-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-person-badge"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Colaboradores</div>
            <h5 class="fw-bold mb-0">Refeições por colaborador</h5>
        </div>
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
                <?php foreach ($colaboradores as $row): ?>
                    <tr>
                        <td><?= h($row['criado_em']) ?></td>
                        <td><?= h($row['nome_colaborador']) ?></td>
                        <td><?= h($row['quantidade']) ?></td>
                        <td><span class="tag <?= restaurant_badge_class($row['restaurante']) ?>"><?= h($row['restaurante']) ?></span></td>
                        <td><span class="tag <?= operation_badge_class($row['operacao']) ?>"><?= h($row['operacao']) ?></span></td>
                        <td><?= h($row['usuario']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($colaboradores)): ?>
                    <tr><td colspan="6" class="text-muted">Sem registros de colaboradores.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card p-4 mt-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-ticket-perforated"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Vouchers</div>
            <h5 class="fw-bold mb-0">Vouchers registrados</h5>
        </div>
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
                <?php foreach ($vouchers as $row): ?>
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
                <?php if (empty($vouchers)): ?>
                    <tr><td colspan="11" class="text-muted">Sem vouchers registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
