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
    .tematic-operacao-page .js-row-clickable {
        cursor: pointer;
    }
    .tematic-operacao-page .js-row-clickable:hover {
        background: rgba(249, 115, 22, 0.08);
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
                <div class="text-muted">Consulta operacional para cozinha e liderança, com confirmação rápida de entrada, no-show e cancelamento.</div>
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
            <h5 class="fw-bold mb-0">Nome | UH | PAX | Turno | Restaurante | Status</h5>
        </div>
    </div>

    <form class="row g-2 mb-3" method="get" action="/">
        <input type="hidden" name="r" value="reservasTematicas/operacao">
        <div class="col-12 col-md-3">
            <label class="form-label mb-1">Data da operação</label>
            <input type="date" class="form-control" name="data" value="<?= h($filters['data'] ?? date('Y-m-d')) ?>">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label mb-1">Restaurante</label>
            <?php if ($restrictedRestaurant): ?>
                <input type="hidden" name="restaurante_id" value="<?= h($restrictedRestaurant['id']) ?>">
                <div class="form-control d-flex align-items-center">
                    <?= h($restrictedRestaurant['nome']) ?>
                </div>
            <?php else: ?>
                <select class="form-select" name="restaurante_id">
                    <option value="">Todos</option>
                    <?php foreach ($restaurantes as $rest): ?>
                        <option value="<?= (int)$rest['id'] ?>" <?= ((string)($filters['restaurante_id'] ?? '') === (string)$rest['id']) ? 'selected' : '' ?>>
                            <?= h($rest['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label mb-1">Turno</label>
            <select class="form-select" name="turno_id">
                <option value="">Todos</option>
                <?php foreach ($turnos as $turno): ?>
                    <option value="<?= (int)$turno['id'] ?>" <?= ((string)($filters['turno_id'] ?? '') === (string)$turno['id']) ? 'selected' : '' ?>>
                        <?= h($turno['hora']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-3 d-flex align-items-end gap-2">
            <button class="btn btn-primary btn-xl w-100">Atualizar contexto</button>
            <a class="btn btn-outline-primary btn-xl" href="/?r=reservasTematicas/operacao">Hoje</a>
        </div>
    </form>

    <div class="row g-2 mb-3">
        <div class="col-12 col-md-4">
            <label class="form-label mb-1">Filtro da tabela rápida</label>
            <input type="text" class="form-control" id="quickLocalFilter" placeholder="Digite nome, UH, turno, restaurante ou status">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label mb-1">Status</label>
            <select class="form-select" id="quickStatusFilter">
                <option value="">Todos</option>
                <?php foreach ($statusOptions as $status): ?>
                    <option value="<?= h($status) ?>"><?= h($labelStatus($status)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label mb-1">Restaurante</label>
            <select class="form-select" id="quickRestaurantFilter">
                <option value="">Todos</option>
                <?php foreach ($restaurantes as $rest): ?>
                    <option value="<?= h(mb_strtolower((string)$rest['nome'], 'UTF-8')) ?>"><?= h($rest['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-2">
            <label class="form-label mb-1">Ordenação</label>
            <select class="form-select" id="quickSort">
                <option value="restaurante">Restaurante (A-Z)</option>
                <option value="nome">Nome (A-Z)</option>
                <option value="turno">Turno</option>
                <option value="status">Status</option>
            </select>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle" id="quickTable">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>UH</th>
                    <th>PAX</th>
                    <th>Turno</th>
                    <th>Restaurante</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="quickTableBody">
                <?php foreach ($reservasOrdenadas as $item): ?>
                    <?php $status = $normalizeStatus((string)($item['status'] ?? '')); ?>
                    <?php
                        $titularDisplay = normalize_mojibake((string)($item['titular_nome_display'] ?? $item['titular_nome'] ?? '-'));
                        $restDisplay = normalize_mojibake((string)($item['restaurante'] ?? ''));
                        $statusDisplay = $labelStatus($status);
                    ?>
                    <?php
                        $searchRow = mb_strtolower(trim(implode(' ', [
                            $titularDisplay,
                            (string)($item['uh_numero'] ?? ''),
                            (string)($item['turno_hora'] ?? ''),
                            (string)$statusDisplay,
                            $restDisplay,
                        ])), 'UTF-8');
                    ?>
                    <tr
                        class="js-quick-row js-open-reserva"
                        data-search="<?= h($searchRow) ?>"
                        data-status="<?= h($status) ?>"
                        data-rest="<?= h(mb_strtolower($restDisplay, 'UTF-8')) ?>"
                        data-sort-rest="<?= h(mb_strtolower($restDisplay, 'UTF-8')) ?>"
                        data-sort-nome="<?= h(mb_strtolower($titularDisplay, 'UTF-8')) ?>"
                        data-sort-turno="<?= h((string)($item['turno_hora'] ?? '')) ?>"
                        data-sort-status="<?= h(mb_strtolower((string)$statusDisplay, 'UTF-8')) ?>"
                        data-id="<?= (int)($item['id'] ?? 0) ?>"
                        data-titular="<?= h($titularDisplay) ?>"
                        data-uh="<?= h((string)($item['uh_numero'] ?? '')) ?>"
                        data-pax="<?= h((string)($item['pax'] ?? 0)) ?>"
                        data-pax-real="<?= h((string)($item['pax_real'] ?? '')) ?>"
                        data-restaurante-id="<?= (int)($item['restaurante_id'] ?? 0) ?>"
                        data-restaurante-nome="<?= h($restDisplay) ?>"
                        data-turno-id="<?= (int)($item['turno_id'] ?? 0) ?>"
                        data-turno-hora="<?= h((string)($item['turno_hora'] ?? '')) ?>"
                        data-status-atual="<?= h($status) ?>"
                        data-obs-operacao="<?= h(normalize_mojibake((string)($item['observacao_operacao'] ?? ''))) ?>"
                    >
                        <td><?= h($titularDisplay) ?></td>
                        <td><span class="uh-badge <?= uh_badge_class($item['uh_numero']) ?>"><?= h($item['uh_numero'] ?? '-') ?></span></td>
                        <td><?= h((string)($item['pax'] ?? 0)) ?></td>
                        <td><span class="tag badge-soft"><?= h($item['turno_hora'] ?? '-') ?></span></td>
                        <td><span class="tag <?= restaurant_badge_class($restDisplay) ?>"><?= h($restDisplay) ?></span></td>
                        <td><span class="badge badge-soft"><?= h($statusDisplay) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($reservasOrdenadas)): ?>
                    <tr><td colspan="6" class="text-muted">Nenhuma reserva encontrada para este período.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div id="quickPagination" class="d-flex flex-wrap gap-2 mt-2"></div>
    <div class="text-muted small mt-2">Clique em uma linha para abrir detalhes e editar restaurante, turno e status.</div>
</div>
<div class="section-block">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-clipboard-check"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Conferência</div>
            <h5 class="fw-bold mb-0">Base detalhada do período selecionado</h5>
        </div>
    </div>
    <div class="row g-2 mb-3">
        <div class="col-12 col-md-6">
            <label class="form-label mb-1">Filtro da base detalhada</label>
            <input type="text" class="form-control" id="detailedLocalFilter" placeholder="Nome, UH, observações, turno ou status">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label mb-1">Status</label>
            <select class="form-select" id="detailedStatusFilter">
                <option value="">Todos</option>
                <?php foreach ($statusOptions as $status): ?>
                    <option value="<?= h($status) ?>"><?= h($labelStatus($status)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label mb-1">Restaurante</label>
            <select class="form-select" id="detailedRestaurantFilter">
                <option value="">Todos</option>
                <?php foreach ($restaurantes as $rest): ?>
                    <option value="<?= h(mb_strtolower((string)$rest['nome'], 'UTF-8')) ?>"><?= h($rest['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle" id="detailedTable">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Nome</th>
                    <th>UH</th>
                    <th>PAX reservada</th>
                    <th>PAX real</th>
                    <th>Restaurante</th>
                    <th>Turno</th>
                    <th>Observação original</th>
                    <th>Observação operacional</th>
                </tr>
            </thead>
            <tbody id="detailedTableBody">
                <?php foreach ($reservas as $row): ?>
                    <?php $rowStatus = $normalizeStatus((string)($row['status'] ?? '')); ?>
                    <?php
                        $rowTitular = normalize_mojibake((string)($row['titular_nome_display'] ?? $row['titular_nome'] ?? '-'));
                        $rowRest = normalize_mojibake((string)($row['restaurante'] ?? ''));
                        $rowSearch = mb_strtolower(trim(implode(' ', [
                            (string)$rowTitular,
                            (string)($row['uh_numero'] ?? ''),
                            (string)($row['turno_hora'] ?? ''),
                            (string)$labelStatus($rowStatus),
                            (string)$rowRest,
                            normalize_mojibake((string)($row['observacao_reserva'] ?? '')),
                            normalize_mojibake((string)($row['observacao_operacao'] ?? '')),
                        ])), 'UTF-8');
                    ?>
                    <tr class="js-detailed-row" data-search="<?= h($rowSearch) ?>" data-status="<?= h($rowStatus) ?>" data-rest="<?= h(mb_strtolower($rowRest, 'UTF-8')) ?>">
                        <td>
                            <span class="badge badge-soft"><?= h($labelStatus($rowStatus)) ?></span>
                            <?php if (!empty($row['excedente'])): ?>
                                <span class="badge badge-warning">Excedente</span>
                            <?php endif; ?>
                        </td>
                        <td><?= h($rowTitular) ?></td>
                        <td><span class="uh-badge <?= uh_badge_class($row['uh_numero']) ?>"><?= h($row['uh_numero']) ?></span></td>
                        <td><?= h((string)($row['pax'] ?? 0)) ?></td>
                        <td><?= h((string)($row['pax_real'] ?? '-')) ?></td>
                        <td><span class="tag <?= restaurant_badge_class($rowRest) ?>"><?= h($rowRest) ?></span></td>
                        <td><span class="tag badge-soft"><?= h($row['turno_hora']) ?></span></td>
                        <td>
                            <?= h(normalize_mojibake((string)($row['observacao_reserva'] ?? '-'))) ?>
                            <?php if (!empty($row['observacao_tags'])): ?>
                                <div class="text-muted small"><?= h(normalize_mojibake((string)$row['observacao_tags'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= h(normalize_mojibake((string)($row['observacao_operacao'] ?? '-'))) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($reservas)): ?>
                    <tr><td colspan="9" class="text-muted">Nenhuma reserva encontrada para este período.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div id="detailedPagination" class="d-flex flex-wrap gap-2 mt-2"></div>
</div>
</div>

<div class="modal fade" id="reservaDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="post" action="/?r=reservasTematicas/operacao" id="reservaDetailForm">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="update_detail">
                <input type="hidden" name="id" id="modalReservaId" value="">
                <input type="hidden" name="confirm_final" id="modalConfirmFinal" value="0">
                <div class="modal-header">
                    <h5 class="modal-title">Detalhes da reserva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-2">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Titular</label>
                            <input class="form-control" id="modalTitular" readonly>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">UH</label>
                            <input class="form-control" id="modalUh" readonly>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">PAX reservada</label>
                            <input class="form-control" id="modalPax" readonly>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">PAX real</label>
                            <input class="form-control" type="number" min="0" name="pax_real" id="modalPaxReal">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="modalStatus" required>
                                <?php foreach ($statusOptions as $status): ?>
                                    <option value="<?= h($status) ?>"><?= h($labelStatus($status)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Restaurante</label>
                            <select class="form-select" name="restaurante_id" id="modalRestaurante" required>
                                <?php foreach ($restaurantes as $rest): ?>
                                    <option value="<?= (int)$rest['id'] ?>"><?= h($rest['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Turno</label>
                            <select class="form-select" name="turno_id" id="modalTurno" required>
                                <?php foreach ($turnos as $turno): ?>
                                    <option value="<?= (int)$turno['id'] ?>"><?= h($turno['hora']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Observação operacional</label>
                        <textarea class="form-control" name="observacao_operacao" id="modalObsOperacao" rows="3"></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Justificativa (obrigatória em turno encerrado)</label>
                        <input class="form-control" type="text" name="justificativa" id="modalJustificativa" placeholder="Descreva o motivo da alteração">
                    </div>
                </div>
                <div class="modal-footer d-flex flex-wrap gap-2 justify-content-between">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-primary" data-final-status="Finalizada">Finalizar</button>
                        <button type="button" class="btn btn-outline-primary" data-final-status="Nao compareceu">Não compareceu</button>
                        <button type="button" class="btn btn-outline-primary" data-final-status="Cancelada">Cancelar</button>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Fechar</button>
                        <button type="submit" class="btn btn-primary">Salvar alterações</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(() => {
    const normalize = (value) => (value || '')
        .toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
    const paginateRows = (rows, container, page = 1, perPage = 12) => {
        const total = rows.length;
        const pages = Math.max(1, Math.ceil(total / perPage));
        const current = Math.min(Math.max(1, page), pages);
        const start = (current - 1) * perPage;
        const end = start + perPage;
        rows.forEach((row, idx) => {
            row.style.display = (idx >= start && idx < end) ? '' : 'none';
        });
        if (!container) return current;
        container.innerHTML = '';
        if (pages <= 1) return current;
        for (let i = 1; i <= pages; i++) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `btn btn-sm ${i === current ? 'btn-primary' : 'btn-outline-primary'} js-page-btn`;
            btn.dataset.page = String(i);
            btn.textContent = String(i);
            container.appendChild(btn);
        }
        return current;
    };

    const quickRows = Array.from(document.querySelectorAll('.js-quick-row'));
    const quickInput = document.getElementById('quickLocalFilter');
    const quickStatus = document.getElementById('quickStatusFilter');
    const quickRestaurant = document.getElementById('quickRestaurantFilter');
    const quickSort = document.getElementById('quickSort');
    const quickPagination = document.getElementById('quickPagination');
    const quickBody = document.getElementById('quickTableBody');
    let quickPage = 1;

    const applyQuickFilters = (resetPage = true) => {
        if (resetPage) quickPage = 1;
        const term = normalize(quickInput?.value || '');
        const status = (quickStatus?.value || '').trim();
        const rest = normalize(quickRestaurant?.value || '');
        const sort = quickSort?.value || 'restaurante';

        let filtered = quickRows.filter((row) => {
            const okTerm = !term || normalize(row.dataset.search || '').includes(term);
            const okStatus = !status || (row.dataset.status || '') === status;
            const okRest = !rest || normalize(row.dataset.rest || '') === rest;
            return okTerm && okStatus && okRest;
        });

        const sortKeyMap = {
            restaurante: 'sortRest',
            nome: 'sortNome',
            turno: 'sortTurno',
            status: 'sortStatus',
        };
        const dsKey = sortKeyMap[sort] || 'sortRest';
        filtered.sort((a, b) => {
            const av = normalize(a.dataset[dsKey] || '');
            const bv = normalize(b.dataset[dsKey] || '');
            return av.localeCompare(bv, 'pt-BR');
        });

        if (quickBody) {
            quickRows.forEach((row) => { row.style.display = 'none'; });
            filtered.forEach((row) => quickBody.appendChild(row));
        }
        quickPage = paginateRows(filtered, quickPagination, quickPage, 10);
    };

    quickInput?.addEventListener('input', () => applyQuickFilters(true));
    quickStatus?.addEventListener('change', () => applyQuickFilters(true));
    quickRestaurant?.addEventListener('change', () => applyQuickFilters(true));
    quickSort?.addEventListener('change', () => applyQuickFilters(true));
    quickPagination?.addEventListener('click', (event) => {
        const btn = event.target.closest('.js-page-btn');
        if (!btn) return;
        quickPage = parseInt(btn.dataset.page || '1', 10) || 1;
        applyQuickFilters(false);
    });

    const detailedRows = Array.from(document.querySelectorAll('.js-detailed-row'));
    const detailedInput = document.getElementById('detailedLocalFilter');
    const detailedStatus = document.getElementById('detailedStatusFilter');
    const detailedRestaurant = document.getElementById('detailedRestaurantFilter');
    const detailedPagination = document.getElementById('detailedPagination');
    const detailedBody = document.getElementById('detailedTableBody');
    let detailedPage = 1;

    const applyDetailedFilters = (resetPage = true) => {
        if (resetPage) detailedPage = 1;
        const term = normalize(detailedInput?.value || '');
        const status = (detailedStatus?.value || '').trim();
        const rest = normalize(detailedRestaurant?.value || '');

        const filtered = detailedRows.filter((row) => {
            const okTerm = !term || normalize(row.dataset.search || '').includes(term);
            const okStatus = !status || (row.dataset.status || '') === status;
            const okRest = !rest || normalize(row.dataset.rest || '') === rest;
            return okTerm && okStatus && okRest;
        });

        if (detailedBody) {
            detailedRows.forEach((row) => { row.style.display = 'none'; });
            filtered.forEach((row) => detailedBody.appendChild(row));
        }
        detailedPage = paginateRows(filtered, detailedPagination, detailedPage, 12);
    };

    detailedInput?.addEventListener('input', () => applyDetailedFilters(true));
    detailedStatus?.addEventListener('change', () => applyDetailedFilters(true));
    detailedRestaurant?.addEventListener('change', () => applyDetailedFilters(true));
    detailedPagination?.addEventListener('click', (event) => {
        const btn = event.target.closest('.js-page-btn');
        if (!btn) return;
        detailedPage = parseInt(btn.dataset.page || '1', 10) || 1;
        applyDetailedFilters(false);
    });

    const modalEl = document.getElementById('reservaDetailModal');
    let modal = null;
    const getModal = () => {
        if (!modalEl) return null;
        if (!modal && window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            modal = new window.bootstrap.Modal(modalEl);
        }
        return modal;
    };
    const modalId = document.getElementById('modalReservaId');
    const modalTitular = document.getElementById('modalTitular');
    const modalUh = document.getElementById('modalUh');
    const modalPax = document.getElementById('modalPax');
    const modalPaxReal = document.getElementById('modalPaxReal');
    const modalStatus = document.getElementById('modalStatus');
    const modalRest = document.getElementById('modalRestaurante');
    const modalTurno = document.getElementById('modalTurno');
    const modalObs = document.getElementById('modalObsOperacao');
    const modalConfirmFinal = document.getElementById('modalConfirmFinal');
    const detailForm = document.getElementById('reservaDetailForm');

    document.querySelectorAll('.js-open-reserva').forEach((row) => {
        row.classList.add('js-row-clickable');
        row.addEventListener('click', () => {
            const modalInstance = getModal();
            if (!modalInstance) return;
            modalId.value = row.dataset.id || '';
            modalTitular.value = row.dataset.titular || '-';
            modalUh.value = row.dataset.uh || '-';
            modalPax.value = row.dataset.pax || '0';
            modalPaxReal.value = row.dataset.paxReal || '';
            modalStatus.value = row.dataset.statusAtual || 'Reservada';
            modalRest.value = row.dataset.restauranteId || '';
            modalTurno.value = row.dataset.turnoId || '';
            modalObs.value = row.dataset.obsOperacao || '';
            modalConfirmFinal.value = '0';
            modalInstance.show();
        });
    });

    detailForm?.addEventListener('submit', () => {
        const status = modalStatus?.value || '';
        if (['Finalizada', 'Nao compareceu', 'Cancelada'].includes(status)) {
            modalConfirmFinal.value = '1';
        } else {
            modalConfirmFinal.value = '0';
        }
    });

    document.querySelectorAll('[data-final-status]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const finalStatus = btn.getAttribute('data-final-status') || 'Finalizada';
            if (modalStatus) modalStatus.value = finalStatus;
            if (modalConfirmFinal) modalConfirmFinal.value = '1';
            detailForm?.requestSubmit();
        });
    });

    applyQuickFilters(true);
    applyDetailedFilters(true);
})();
</script>
