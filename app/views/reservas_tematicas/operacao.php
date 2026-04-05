<?php
$flash = $this->data['flash'] ?? null;
$restaurantes = $this->data['restaurantes'] ?? [];
$turnos = $this->data['turnos'] ?? [];
$reservas = $this->data['reservas'] ?? [];
$filters = $this->data['filters'] ?? [];
$user = $this->data['user'] ?? Auth::user();
$restrictedRestaurant = $this->data['restricted_restaurant'] ?? null;
$summary = $this->data['summary'] ?? [];

$statusOptions = ['Reservada', 'Finalizada', 'Nao compareceu', 'Cancelada', 'Divergencia', 'Excedente'];
$statusLabels = [
    'Reservada' => 'Reservada',
    'Finalizada' => 'Finalizada',
    'Nao compareceu' => 'Não compareceu',
    'Cancelada' => 'Cancelada',
    'Divergencia' => 'Divergência',
    'Excedente' => 'Excedente',
];

$normalizeStatus = static function (?string $status): string {
    $status = normalize_mojibake(trim((string)$status));
    $map = [
        'Nao compareceu' => 'Nao compareceu',
        'Não compareceu' => 'Nao compareceu',
        'Não compareceu' => 'Nao compareceu',
        'Divergencia' => 'Divergencia',
        'Divergência' => 'Divergencia',
        'Divergência' => 'Divergencia',
        'Conferida' => 'Reservada',
        'Em atendimento' => 'Reservada',
    ];
    return $map[$status] ?? $status;
};
$labelStatus = static function (?string $status) use ($normalizeStatus, $statusLabels): string {
    $canon = $normalizeStatus($status);
    return $statusLabels[$canon] ?? $canon;
};

$reservasOrdenadas = $reservas;
usort($reservasOrdenadas, static function (array $a, array $b) use ($normalizeStatus): int {
    $ta = (string)($a['turno_hora'] ?? '');
    $tb = (string)($b['turno_hora'] ?? '');
    if ($ta !== $tb) {
        return strcmp($ta, $tb);
    }
    $sa = $normalizeStatus((string)($a['status'] ?? ''));
    $sb = $normalizeStatus((string)($b['status'] ?? ''));
    if ($sa !== $sb) {
        return strcmp($sa, $sb);
    }
    return strcmp((string)($a['uh_numero'] ?? ''), (string)($b['uh_numero'] ?? ''));
});
?>

<style>
    .tematic-operacao-page {
        min-width: 0;
    }
    .tematic-operacao-page .summary-grid {
        display: grid;
        gap: 0.75rem;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    }
    .tematic-operacao-page .summary-grid .saas-stat-card {
        min-height: 116px;
    }
    .tematic-operacao-page .summary-grid .saas-stat-card .small {
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-size: 0.68rem;
    }
    .tematic-operacao-page .summary-grid .saas-stat-value {
        margin-top: 0.35rem;
    }
    .tematic-operacao-page .section-block {
        border: 1px solid var(--ab-border);
        border-radius: 22px;
        padding: 1rem;
        background: var(--ab-card);
        box-shadow: var(--ab-shadow-soft);
    }
    .tematic-operacao-page .btn {
        border-radius: 12px;
        font-weight: 650;
        text-decoration: none !important;
        -webkit-text-fill-color: currentColor;
    }
    .tematic-operacao-page .btn span,
    .tematic-operacao-page .btn i {
        color: inherit !important;
        -webkit-text-fill-color: currentColor !important;
    }
    .tematic-operacao-page .btn.btn-xl {
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
    }
    .tematic-operacao-page .btn-primary,
    .tematic-operacao-page .btn-primary:link,
    .tematic-operacao-page .btn-primary:visited {
        color: #fff !important;
        -webkit-text-fill-color: #fff !important;
        border-color: #ea580c !important;
        background: linear-gradient(135deg, #f97316 0%, #fb923c 100%) !important;
        box-shadow: 0 8px 20px rgba(249, 115, 22, 0.24);
    }
    .tematic-operacao-page .btn-primary:hover,
    .tematic-operacao-page .btn-primary:focus,
    .tematic-operacao-page .btn-primary:active {
        color: #fff !important;
        border-color: #c2410c !important;
        background: linear-gradient(135deg, #ea580c 0%, #f97316 100%) !important;
    }
    .tematic-operacao-page .btn-outline-primary,
    .tematic-operacao-page .btn-outline-primary:link,
    .tematic-operacao-page .btn-outline-primary:visited {
        color: #9a3412 !important;
        -webkit-text-fill-color: #9a3412 !important;
        border-color: #fb923c !important;
        background: #fff !important;
    }
    .tematic-operacao-page .btn-outline-primary:hover,
    .tematic-operacao-page .btn-outline-primary:focus,
    .tematic-operacao-page .btn-outline-primary:active {
        color: #fff !important;
        -webkit-text-fill-color: #fff !important;
        border-color: #f97316 !important;
        background: linear-gradient(135deg, #f97316 0%, #fb923c 100%) !important;
    }
    .tematic-operacao-page .btn-primary *,
    .tematic-operacao-page .btn-primary:link *,
    .tematic-operacao-page .btn-primary:visited *,
    .tematic-operacao-page .btn-primary:hover *,
    .tematic-operacao-page .btn-primary:focus *,
    .tematic-operacao-page .btn-primary:active *,
    .tematic-operacao-page .btn-outline-primary *,
    .tematic-operacao-page .btn-outline-primary:link *,
    .tematic-operacao-page .btn-outline-primary:visited * {
        color: inherit !important;
        -webkit-text-fill-color: currentColor !important;
    }
    .tematic-operacao-page .btn-outline-primary:disabled,
    .tematic-operacao-page .btn-outline-primary.disabled {
        color: #94a3b8 !important;
        border-color: #cbd5e1 !important;
        background: #f8fafc !important;
    }
    @media (max-width: 768px) {
        .tematic-operacao-page .row {
            margin-left: 0;
            margin-right: 0;
            --bs-gutter-x: 0.8rem;
        }
        .tematic-operacao-page .row > [class*="col-"] {
            min-width: 0;
            padding-left: calc(var(--bs-gutter-x) * 0.5);
            padding-right: calc(var(--bs-gutter-x) * 0.5);
        }
        .tematic-operacao-page .section-block {
            padding: 0.9rem;
        }
        .tematic-operacao-page .section-block .d-flex.flex-wrap.gap-2 {
            min-width: 0;
            max-width: 100%;
        }
        .tematic-operacao-page .section-block .d-flex.flex-wrap.gap-2 > .btn {
            flex: 1 1 100%;
            min-width: 0;
            justify-content: center;
        }
        .tematic-operacao-page .table-responsive {
            max-width: 100%;
            overflow-x: auto;
        }
    }
</style>

<div class="saas-page tematic-operacao-page">
<div class="card card-soft p-4 mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div class="section-title">
            <div class="icon"><i class="bi bi-printer"></i></div>
            <div>
                <div class="text-uppercase text-muted small">Reservas Temáticas</div>
                <h3 class="fw-bold mb-1">Ambiente de Conferência e Impressão</h3>
                <div class="text-muted">Consulta operacional para cozinha e liderança. Alterações de status são feitas no módulo Registro.</div>
            </div>
        </div>
        <span class="badge badge-soft">Jornada 2</span>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= h($flash['type']) ?> mt-3 mb-0"><?= h($flash['message']) ?></div>
    <?php endif; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="saas-stat-card">
            <div class="text-muted small">Total</div>
            <div class="saas-stat-value"><?= (int)($summary['total'] ?? count($reservas)) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="saas-stat-card">
            <div class="text-muted small">Reservadas</div>
            <div class="saas-stat-value"><?= (int)($summary['reservada'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="saas-stat-card">
            <div class="text-muted small">Finalizadas</div>
            <div class="saas-stat-value status-success"><?= (int)($summary['finalizada'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="saas-stat-card">
            <div class="text-muted small">Não compareceu</div>
            <div class="saas-stat-value status-danger"><?= (int)($summary['nao_compareceu'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="saas-stat-card">
            <div class="text-muted small">Canceladas</div>
            <div class="saas-stat-value"><?= (int)($summary['cancelada'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="saas-stat-card">
            <div class="text-muted small">Excedentes</div>
            <div class="saas-stat-value status-warning"><?= (int)($summary['excedente'] ?? 0) ?></div>
        </div>
    </div>
</div>

<div class="section-block mb-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-funnel"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Filtros operacionais</div>
            <h5 class="fw-bold mb-0">Selecione período e restaurante</h5>
        </div>
    </div>

    <form class="row g-3 align-items-end" method="get" action="/">
        <input type="hidden" name="r" value="reservasTematicas/operacao">
        <div class="col-12 col-md-3">
            <label class="form-label">Data</label>
            <input type="date" class="form-control input-xl" name="data" value="<?= h($filters['data'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Restaurante</label>
            <?php if ($restrictedRestaurant): ?>
                <input type="hidden" name="restaurante_id" value="<?= h($restrictedRestaurant['id']) ?>">
                <div class="form-control input-xl d-flex align-items-center gap-2">
                    <span class="tag <?= restaurant_badge_class($restrictedRestaurant['nome']) ?>"><?= h($restrictedRestaurant['nome']) ?></span>
                </div>
            <?php else: ?>
                <select class="form-select input-xl" name="restaurante_id">
                    <option value="">Todos</option>
                    <?php foreach ($restaurantes as $rest): ?>
                        <option value="<?= (int)$rest['id'] ?>" <?= ($filters['restaurante_id'] ?? '') == $rest['id'] ? 'selected' : '' ?>>
                            <?= h($rest['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>
        <div class="col-12 col-md-2">
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
        <div class="col-12 col-md-2">
            <label class="form-label">UH</label>
            <input type="text" class="form-control input-xl" name="uh_numero" value="<?= h($filters['uh_numero'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-2">
            <label class="form-label">Status</label>
            <select class="form-select input-xl" name="status">
                <option value="">Todos</option>
                <?php foreach ($statusOptions as $status): ?>
                    <option value="<?= h($status) ?>" <?= $normalizeStatus($filters['status'] ?? '') === $status ? 'selected' : '' ?>>
                        <?= h($labelStatus($status)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-2">
            <label class="form-label">Ordenar por</label>
            <select class="form-select input-xl" name="order">
                <option value="">Horário</option>
                <option value="status" <?= ($filters['order'] ?? '') === 'status' ? 'selected' : '' ?>>Status</option>
            </select>
        </div>
        <div class="col-12 d-flex flex-wrap gap-2">
            <button class="btn btn-primary btn-xl">Aplicar filtros</button>
            <a class="btn btn-primary btn-xl" href="/?r=reservasTematicas/operacao">Remover filtro</a>
            <a class="btn btn-outline-primary btn-xl" href="/?r=reservasTematicas/print&tipo=detalhada&data=<?= h($filters['data']) ?>&restaurante_id=<?= h($filters['restaurante_id']) ?>&turno_id=<?= h($filters['turno_id']) ?>&status=<?= h($filters['status']) ?>&order=<?= h($filters['order']) ?>" target="_blank">
                <i class="bi bi-printer"></i> Imprimir lista
            </a>
        </div>
    </form>
</div>

<div class="section-block mb-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-file-earmark-break"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Impressão para cozinha</div>
            <h5 class="fw-bold mb-0">Reservas com status Reservada por restaurante</h5>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($restaurantes as $rest): ?>
            <a
                class="btn btn-outline-primary btn-xl"
                href="/?r=reservasTematicas/print&tipo=detalhada&data=<?= h($filters['data']) ?>&restaurante_id=<?= h($rest['id']) ?>&turno_id=<?= h($filters['turno_id']) ?>&status=Reservada&order=hora"
                target="_blank"
            >
                <i class="bi bi-printer"></i> <?= h($rest['nome']) ?>
            </a>
        <?php endforeach; ?>
        <?php if (!empty($filters['restaurante_id'])): ?>
            <a
                class="btn btn-primary btn-xl"
                href="/?r=reservasTematicas/print&tipo=detalhada&data=<?= h($filters['data']) ?>&restaurante_id=<?= h($filters['restaurante_id']) ?>&turno_id=<?= h($filters['turno_id']) ?>&status=Reservada&order=hora"
                target="_blank"
            >
                <i class="bi bi-printer-fill"></i> Imprimir Reservadas do restaurante selecionado
            </a>
        <?php endif; ?>
    </div>
    <div class="text-muted small mt-2">
        Esta área imprime apenas reservas em <strong>status Reservada</strong>, organizadas para envio à cozinha.
    </div>
</div>

<div class="section-block mb-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-list-ul"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Visualização rápida</div>
            <h5 class="fw-bold mb-0">Nome | UH | PAX | Turno | Status</h5>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>UH</th>
                    <th>PAX</th>
                    <th>Turno</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservasOrdenadas as $item): ?>
                    <?php $status = $normalizeStatus((string)($item['status'] ?? '')); ?>
                    <tr>
                        <td><?= h($item['titular_nome'] ?? '-') ?></td>
                        <td><span class="uh-badge <?= uh_badge_class($item['uh_numero']) ?>"><?= h($item['uh_numero'] ?? '-') ?></span></td>
                        <td><?= h((string)($item['pax'] ?? 0)) ?></td>
                        <td><span class="tag badge-soft"><?= h($item['turno_hora'] ?? '-') ?></span></td>
                        <td><span class="badge badge-soft"><?= h($labelStatus($status)) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($reservasOrdenadas)): ?>
                    <tr><td colspan="5" class="text-muted">Nenhuma reserva encontrada para este período.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="section-block">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-clipboard-check"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Conferência</div>
            <h5 class="fw-bold mb-0">Base detalhada do período selecionado</h5>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>UH</th>
                    <th>PAX reservada</th>
                    <th>PAX real</th>
                    <th>Restaurante</th>
                    <th>Turno</th>
                    <th>Observação original</th>
                    <th>Observação operacional</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservas as $row): ?>
                    <?php $rowStatus = $normalizeStatus((string)($row['status'] ?? '')); ?>
                    <tr>
                        <td>
                            <span class="badge badge-soft"><?= h($labelStatus($rowStatus)) ?></span>
                            <?php if (!empty($row['excedente'])): ?>
                                <span class="badge badge-warning">Excedente</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="uh-badge <?= uh_badge_class($row['uh_numero']) ?>"><?= h($row['uh_numero']) ?></span></td>
                        <td><?= h((string)($row['pax'] ?? 0)) ?></td>
                        <td><?= h((string)($row['pax_real'] ?? '-')) ?></td>
                        <td><span class="tag <?= restaurant_badge_class($row['restaurante']) ?>"><?= h($row['restaurante']) ?></span></td>
                        <td><span class="tag badge-soft"><?= h($row['turno_hora']) ?></span></td>
                        <td>
                            <?= h($row['observacao_reserva'] ?? '-') ?>
                            <?php if (!empty($row['observacao_tags'])): ?>
                                <div class="text-muted small"><?= h($row['observacao_tags']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= h($row['observacao_operacao'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($reservas)): ?>
                    <tr><td colspan="8" class="text-muted">Nenhuma reserva encontrada para este período.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>


