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
?>

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

<div class="card p-4 mb-4">
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

<div class="card p-4 mb-4">
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
                        <td><?= h($log['criado_em']) ?></td>
                        <td><?= h($log['usuario'] ?? '-') ?></td>
                        <td><span class="badge badge-soft"><?= h($log['acao']) ?></span></td>
                        <td>#<?= (int)$log['reserva_id'] ?> · UH <?= h($log['uh_numero'] ?? '') ?> · <?= h($log['data_reserva'] ?? '') ?></td>
                        <td><span class="tag <?= restaurant_badge_class($log['restaurante'] ?? '') ?>"><?= h($log['restaurante'] ?? '') ?></span></td>
                        <td><?= h(substr((string)($log['turno_hora'] ?? ''), 0, 5)) ?></td>
                        <td class="small text-muted"><?= h($log['justificativa'] ?? '') ?></td>
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

<div class="card p-4 mb-4">
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
                        <td><?= h($turno['usuario']) ?></td>
                        <td><span class="tag <?= restaurant_badge_class($turno['restaurante']) ?>"><?= h($turno['restaurante']) ?></span></td>
                        <td><span class="tag <?= operation_badge_class($turno['operacao']) ?>"><?= h($turno['operacao']) ?></span></td>
                        <td><?= h($turno['inicio_em']) ?></td>
                        <td><?= h($turno['fim_em'] ?? 'Aberto') ?></td>
                        <td><?= (int)$turno['total_registros'] ?></td>
                        <td><?= (int)$turno['total_pax'] ?></td>
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

<div class="card p-4">
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
                        <td><?= h($log['criado_em']) ?></td>
                        <td><?= h($log['usuario'] ?? '-') ?></td>
                        <td><?= h($log['tabela']) ?></td>
                        <td><span class="badge badge-soft"><?= h($log['acao']) ?></span></td>
                        <td><?= h((string)($log['registro_id'] ?? '-')) ?></td>
                        <td class="small text-muted"><?= h(mb_substr((string)($log['dados_depois'] ?? ''), 0, 180, 'UTF-8')) ?></td>
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
