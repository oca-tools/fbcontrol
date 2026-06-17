<?php
$filters = $this->data['filters'] ?? [];
$usuarios = $this->data['usuarios'] ?? [];
$generalLogs = $this->data['general_logs'] ?? ['rows' => [], 'page' => 1, 'total_pages' => 1, 'total' => 0, 'param' => 'general_page'];
$thematicLogs = $this->data['thematic_logs'] ?? ['rows' => [], 'page' => 1, 'total_pages' => 1, 'total' => 0, 'param' => 'thematic_page'];
$shiftLogs = $this->data['shift_logs'] ?? ['rows' => [], 'page' => 1, 'total_pages' => 1, 'total' => 0, 'param' => 'shift_page'];

$paginationPages = static function (int $current, int $total): array {
    if ($total <= 1) {
        return [];
    }
    $current = max(1, min($current, $total));
    $visible = [1, $total, $current, $current - 1, $current + 1];
    if ($current <= 4) {
        $visible = array_merge($visible, range(2, min(5, $total)));
    }
    if ($current >= $total - 3) {
        $visible = array_merge($visible, range(max(2, $total - 4), $total - 1));
    }
    $visible = array_values(array_unique(array_filter($visible, static fn($page) => $page >= 1 && $page <= $total)));
    sort($visible);

    $pages = [];
    $previous = 0;
    foreach ($visible as $page) {
        if ($previous > 0 && $page - $previous > 1) {
            $pages[] = null;
        }
        $pages[] = $page;
        $previous = $page;
    }
    return $pages;
};

$renderPagination = static function (array $pagination, array $filters) use ($paginationPages): void {
    $totalPages = (int)($pagination['total_pages'] ?? 1);
    if ($totalPages <= 1) {
        return;
    }
    $page = (int)($pagination['page'] ?? 1);
    $param = (string)($pagination['param'] ?? 'page');
    $base = array_merge($filters, ['r' => 'auditoria/index']);
    ?>
    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
        <span class="text-muted small"><?= (int)($pagination['total'] ?? 0) ?> registros (20 por página)</span>
        <ul class="pagination pagination-sm mb-0">
            <?php foreach ($paginationPages($page, $totalPages) as $i): ?>
                <?php if ($i === null): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php continue; ?>
                <?php endif; ?>
                <?php $query = http_build_query(array_merge($base, [$param => $i])); ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="/?<?= h($query) ?>" data-ajax-link data-ajax-target=".app-content"><?= $i ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
};

$renderExpandableText = static function (?string $value, string $empty = '-', bool $forceDetails = false, string $summaryLabel = 'Ver detalhes') : void {
    $text = trim((string)$value);
    if ($text === '') {
        echo '<span class="text-muted">' . h($empty) . '</span>';
        return;
    }

    $prettyText = $text;
    $bodyClass = 'audit-expandable-body';
    $decoded = json_decode($text, true);
    if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_object($decoded))) {
        $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (is_string($pretty) && $pretty !== '') {
            $prettyText = $pretty;
            $bodyClass .= ' is-json';
        }
    }

    if (!$forceDetails && mb_strlen($text, 'UTF-8') <= 120) {
        echo h($text);
        return;
    }
    ?>
    <details class="audit-expandable">
        <summary><?= h($summaryLabel) ?></summary>
        <div class="<?= h($bodyClass) ?>"><?= str_contains($bodyClass, 'is-json') ? '<pre>' . h($prettyText) . '</pre>' : nl2br(h($prettyText)) ?></div>
    </details>
    <?php
};
?>

<style>
.audit-page .audit-filter-card,
.audit-page .audit-table-card {
    border: 1px solid var(--ab-border);
    border-radius: 18px;
    box-shadow: 0 14px 32px rgba(15, 23, 42, 0.06);
}

.audit-page .audit-table-card .table-responsive {
    border: 1px solid color-mix(in srgb, var(--ab-border) 78%, transparent);
    border-radius: 14px;
}

.audit-page .audit-table-card table {
    margin-bottom: 0;
}

.audit-page .audit-table-card thead th {
    color: var(--ab-muted);
    font-size: 0.72rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    white-space: nowrap;
    background: color-mix(in srgb, var(--ab-soft-bg) 74%, var(--ab-card) 26%);
}

@media (max-width: 576px) {
    .audit-page {
        overflow-x: clip;
    }

    .audit-page .card-soft {
        padding: 0.9rem !important;
        margin-bottom: 0.85rem !important;
    }

    .audit-page .card-soft .section-title {
        align-items: flex-start;
        gap: 0.55rem;
    }

    .audit-page .card-soft .section-title .icon {
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
    }

    .audit-page .card-soft h3 {
        font-size: 1.08rem;
        line-height: 1.18;
    }

    .audit-page .card-soft .section-title .text-muted:not(.small) {
        display: none;
    }

    .audit-page .audit-filter-card,
    .audit-page .audit-table-card {
        padding: 0.85rem !important;
        border-radius: 16px;
        overflow: hidden;
    }

    .audit-page .audit-filter-card .row {
        --bs-gutter-x: 0.65rem;
        --bs-gutter-y: 0.65rem;
    }

    .audit-page .audit-table-card .table-responsive {
        border: 0;
        overflow: visible;
    }

    .audit-page .audit-table-card table,
    .audit-page .audit-table-card thead,
    .audit-page .audit-table-card tbody,
    .audit-page .audit-table-card tr,
    .audit-page .audit-table-card td {
        display: block;
        width: 100%;
    }

    .audit-page .audit-table-card thead {
        display: none;
    }

    .audit-page .audit-table-card tbody tr {
        border: 1px solid color-mix(in srgb, var(--ab-border) 82%, transparent);
        border-radius: 14px;
        background: color-mix(in srgb, var(--ab-card) 94%, var(--ab-soft-bg) 6%);
        margin-bottom: 0.65rem;
        padding: 0.65rem;
        overflow: hidden;
    }

    .audit-page .audit-table-card tbody td {
        display: grid;
        grid-template-columns: minmax(78px, 88px) minmax(0, 1fr);
        gap: 0.55rem;
        align-items: start;
        border: 0;
        padding: 0.34rem 0;
        word-break: break-word;
        overflow-wrap: anywhere;
        min-width: 0;
    }

    .audit-page .audit-table-card tbody td > * {
        min-width: 0;
        max-width: 100%;
    }

    .audit-page .audit-table-card tbody td::before {
        content: attr(data-label);
        color: var(--ab-muted);
        font-size: 0.7rem;
        font-weight: 850;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .audit-page .audit-table-card tbody td[colspan] {
        display: block;
        text-align: center;
    }

    .audit-page .audit-table-card tbody td[colspan]::before {
        content: none;
    }

    .audit-page .audit-table-card .badge,
    .audit-page .audit-table-card .tag {
        display: inline-flex;
        align-items: center;
        max-width: 100%;
        white-space: normal;
        overflow-wrap: anywhere;
        line-height: 1.2;
    }

    .audit-page .audit-table-card .small.text-muted {
        overflow-wrap: anywhere;
    }

    .audit-page .audit-table-card tbody td.audit-cell-expandable {
        display: block;
    }

    .audit-page .audit-table-card tbody td.audit-cell-expandable::before {
        display: block;
        margin-bottom: 0.42rem;
    }

    .audit-page .audit-expandable {
        width: 100%;
    }

    .audit-page .audit-expandable summary {
        list-style: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.76rem;
        font-weight: 700;
        color: var(--ab-accent);
        padding: 0.38rem 0.72rem;
        border-radius: 999px;
        border: 1px solid color-mix(in srgb, var(--ab-accent) 30%, transparent);
        background: color-mix(in srgb, var(--ab-accent) 8%, var(--ab-card) 92%);
    }

    .audit-page .audit-expandable summary::-webkit-details-marker {
        display: none;
    }

    .audit-page .audit-expandable summary::after {
        content: '+';
        font-size: 0.92rem;
        line-height: 1;
    }

    .audit-page .audit-expandable[open] summary::after {
        content: '−';
    }

    .audit-page .audit-expandable-body {
        margin-top: 0.5rem;
        padding: 0.65rem 0.75rem;
        border-radius: 12px;
        background: color-mix(in srgb, var(--ab-soft-bg) 76%, var(--ab-card) 24%);
        color: var(--ab-ink);
        font-size: 0.79rem;
        line-height: 1.45;
        overflow-wrap: anywhere;
    }

    .audit-page .audit-expandable-body.is-json {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .audit-page .audit-expandable-body.is-json pre {
        margin: 0;
        white-space: pre-wrap;
        word-break: break-word;
        overflow-wrap: anywhere;
        font-size: 0.74rem;
        line-height: 1.42;
        font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
    }

    .audit-page .pagination {
        justify-content: center;
        width: 100%;
        flex-wrap: wrap;
        row-gap: 0.35rem;
    }

    .audit-page .page-link {
        min-width: 2rem;
        text-align: center;
    }
}
</style>

<div class="audit-page">
<div class="card card-soft p-4 mb-4">
    <div class="section-title">
        <div class="icon"><i class="bi bi-shield-check"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Administração</div>
            <h3 class="fw-bold mb-0">Auditoria do sistema</h3>
            <div class="text-muted">Rastreie ações críticas, reservas temáticas e turnos operacionais.</div>
        </div>
    </div>
</div>

<div class="card audit-filter-card p-4 mb-4">
    <form class="row g-3 align-items-end" method="get" action="/" data-ajax-filter data-ajax-target=".app-content">
        <input type="hidden" name="r" value="auditoria/index">
        <div class="col-12 col-md-2">
            <label class="form-label">Data única</label>
            <input type="date" class="form-control input-xl" name="data" value="<?= h($filters['data'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-2">
            <label class="form-label">Data início</label>
            <input type="date" class="form-control input-xl" name="data_inicio" value="<?= h($filters['data_inicio'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-2">
            <label class="form-label">Data fim</label>
            <input type="date" class="form-control input-xl" name="data_fim" value="<?= h($filters['data_fim'] ?? '') ?>">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label">Usuário</label>
            <select class="form-select input-xl" name="usuario_id">
                <option value="">Todos</option>
                <?php foreach ($usuarios as $usuario): ?>
                    <option value="<?= (int)$usuario['id'] ?>" <?= ($filters['usuario_id'] ?? '') == $usuario['id'] ? 'selected' : '' ?>>
                        <?= h($usuario['nome']) ?> (<?= h($usuario['perfil']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-2">
            <label class="form-label">Tabela/área</label>
            <input type="text" class="form-control input-xl" name="tabela" value="<?= h($filters['tabela'] ?? '') ?>" placeholder="seguranca">
        </div>
        <div class="col-12 col-md-1">
            <button class="btn btn-primary btn-xl w-100">Filtrar</button>
        </div>
    </form>
</div>

<div class="card audit-table-card p-4 mb-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-calendar-heart"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Alterações e supervisão</div>
            <h5 class="fw-bold mb-0">Reservas Temáticas</h5>
            <div class="text-muted small">Logs de criação, status, mudanças de turno/restaurante e intervenções de supervisão.</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle" data-no-auto-pagination="1">
            <thead>
                <tr>
                    <th>Data/hora</th>
                    <th>Usuário</th>
                    <th>Ação</th>
                    <th>Reserva</th>
                    <th>Restaurante</th>
                    <th>Turno</th>
                    <th>Justificativa</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($thematicLogs['rows'] ?? []) as $log): ?>
                    <tr>
                        <td data-label="Data/hora"><?= h($log['criado_em']) ?></td>
                        <td data-label="Usuário"><?= h($log['usuario'] ?? '-') ?></td>
                        <td data-label="Ação"><span class="badge badge-soft"><?= h($log['acao']) ?></span></td>
                        <td data-label="Reserva">#<?= (int)$log['reserva_id'] ?> · UH <?= h($log['uh_numero'] ?? '') ?> · <?= h($log['data_reserva'] ?? '') ?></td>
                        <td data-label="Restaurante"><span class="tag <?= restaurant_badge_class($log['restaurante'] ?? '') ?>"><?= h($log['restaurante'] ?? '') ?></span></td>
                        <td data-label="Turno"><?= h(substr((string)($log['turno_hora'] ?? ''), 0, 5)) ?></td>
                        <td data-label="Justificativa" class="small text-muted audit-cell-expandable"><?php $renderExpandableText($log['justificativa'] ?? '', 'Sem justificativa'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($thematicLogs['rows'] ?? [])): ?>
                    <tr><td colspan="7" class="text-muted">Sem logs temáticos no filtro.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php $renderPagination($thematicLogs, $filters); ?>
</div>

<div class="card audit-table-card p-4 mb-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-clock-history"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Turnos</div>
            <h5 class="fw-bold mb-0">Início, encerramento e PAX por hostess</h5>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle" data-no-auto-pagination="1">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Restaurante</th>
                    <th>Operação</th>
                    <th>Início</th>
                    <th>Fim</th>
                    <th>Registros</th>
                    <th>PAX</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($shiftLogs['rows'] ?? []) as $turno): ?>
                    <tr>
                        <td data-label="Usuário"><?= h($turno['usuario']) ?></td>
                        <td data-label="Restaurante"><span class="tag <?= restaurant_badge_class($turno['restaurante']) ?>"><?= h($turno['restaurante']) ?></span></td>
                        <td data-label="Operação"><span class="tag <?= operation_badge_class($turno['operacao']) ?>"><?= h($turno['operacao']) ?></span></td>
                        <td data-label="Início"><?= h($turno['inicio_em']) ?></td>
                        <td data-label="Fim"><?= h($turno['fim_em'] ?? 'Aberto') ?></td>
                        <td data-label="Registros"><?= (int)$turno['total_registros'] ?></td>
                        <td data-label="PAX"><?= (int)$turno['total_pax'] ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($shiftLogs['rows'] ?? [])): ?>
                    <tr><td colspan="7" class="text-muted">Sem turnos no filtro.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php $renderPagination($shiftLogs, $filters); ?>
</div>

<div class="card audit-table-card p-4">
    <div class="section-title mb-3">
        <div class="icon"><i class="bi bi-journal-text"></i></div>
        <div>
            <div class="text-uppercase text-muted small">Logs gerais</div>
            <h5 class="fw-bold mb-0">Segurança, exports e cadastros</h5>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle" data-no-auto-pagination="1">
            <thead>
                <tr>
                    <th>Data/hora</th>
                    <th>Usuário</th>
                    <th>Área</th>
                    <th>Ação</th>
                    <th>Registro</th>
                    <th>Dados</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($generalLogs['rows'] ?? []) as $log): ?>
                    <tr>
                        <td data-label="Data/hora"><?= h($log['criado_em']) ?></td>
                        <td data-label="Usuário"><?= h($log['usuario'] ?? '-') ?></td>
                        <td data-label="Área"><?= h($log['tabela']) ?></td>
                        <td data-label="Ação"><span class="badge badge-soft"><?= h($log['acao']) ?></span></td>
                        <td data-label="Registro"><?= h((string)($log['registro_id'] ?? '-')) ?></td>
                        <td data-label="Dados" class="small text-muted audit-cell-expandable"><?php $renderExpandableText($log['dados_depois'] ?? '', 'Sem dados', true, 'Ver log'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($generalLogs['rows'] ?? [])): ?>
                    <tr><td colspan="6" class="text-muted">Sem logs gerais no filtro.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php $renderPagination($generalLogs, $filters); ?>
</div>
</div>
