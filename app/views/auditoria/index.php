<?php
$filters = $this->data['filters'] ?? [];
$usuarios = $this->data['usuarios'] ?? [];
$generalLogs = $this->data['general_logs'] ?? ['rows' => [], 'page' => 1, 'total_pages' => 1, 'total' => 0, 'param' => 'general_page'];
$attemptLogs = $this->data['attempt_logs'] ?? ['rows' => [], 'page' => 1, 'total_pages' => 1, 'total' => 0, 'param' => 'attempt_page'];
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
    $visible = array_values(array_unique(array_filter($visible, static function ($page) use ($total): bool {
        return $page >= 1 && $page <= $total;
    })));
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

$renderPagination = static function (array $pagination, array $filterValues) use ($paginationPages): void {
    $totalPages = (int)($pagination['total_pages'] ?? 1);
    if ($totalPages <= 1) {
        return;
    }
    $page = (int)($pagination['page'] ?? 1);
    $param = (string)($pagination['param'] ?? 'page');
    $base = array_merge($filterValues, ['r' => 'auditoria/index']);
    ?>
    <nav class="audit-pagination" aria-label="Paginação da auditoria">
        <span><?= (int)($pagination['total'] ?? 0) ?> registros encontrados</span>
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
    </nav>
    <?php
};

$decodeAuditPayload = static function ($payload): array {
    if (is_array($payload)) {
        return $payload;
    }
    if (!is_string($payload) || trim($payload) === '') {
        return [];
    }
    $decoded = json_decode($payload, true);
    return is_array($decoded) ? $decoded : [];
};

$formatDateTime = static function ($value): string {
    $value = trim((string)$value);
    if ($value === '') {
        return '-';
    }
    try {
        return (new DateTimeImmutable($value))->format('d/m/Y H:i');
    } catch (Throwable $e) {
        return $value;
    }
};

$auditActorLabel = static function (array $log, string $fallbackContext = 'geral'): string {
    $name = trim((string)($log['usuario'] ?? ''));
    if ($name !== '') {
        return $name;
    }
    if ((int)($log['usuario_id'] ?? 0) > 0) {
        return 'Usuário #' . (int)$log['usuario_id'] . ' (cadastro indisponível)';
    }

    $action = strtolower(trim((string)($log['acao'] ?? '')));
    if (in_array($action, ['auth_login_failed', 'auth_login_ambiguous', 'auth_blocked'], true)) {
        return 'Visitante não autenticado';
    }
    if ($fallbackContext === 'sistema' || strpos($action, 'auto_') === 0 || strpos($action, 'cron') !== false) {
        return 'Sistema automático';
    }
    return 'Não identificado (registro legado)';
};

$auditActionMeta = static function ($action): array {
    $action = strtolower(trim((string)$action));
    $action = str_replace([' ', '-'], '_', $action);
    $map = [
        'create' => ['Registro criado', 'success', 'bi-plus-circle-fill', 'Criado'],
        'update' => ['Registro atualizado', 'info', 'bi-pencil-square', 'Atualizado'],
        'status' => ['Status alterado', 'warning', 'bi-arrow-repeat', 'Alterado'],
        'delete' => ['Registro removido', 'danger', 'bi-trash3-fill', 'Removido'],
        'cancel' => ['Registro cancelado', 'danger', 'bi-x-octagon-fill', 'Cancelado'],
        'update_pax_2min' => ['PAX corrigido', 'info', 'bi-people-fill', 'Corrigido'],
        'auth_login_success' => ['Login realizado', 'success', 'bi-box-arrow-in-right', 'Permitido'],
        'auth_logout' => ['Sessão encerrada', 'neutral', 'bi-box-arrow-right', 'Encerrada'],
        'auth_login_failed' => ['Login recusado', 'danger', 'bi-shield-x', 'Recusado'],
        'auth_login_ambiguous' => ['Login recusado por conflito', 'danger', 'bi-shield-exclamation', 'Recusado'],
        'auth_blocked' => ['Acesso bloqueado', 'danger', 'bi-shield-lock-fill', 'Bloqueado'],
        'csrf_invalid' => ['Solicitação bloqueada por segurança', 'danger', 'bi-shield-x', 'Bloqueada'],
        'export' => ['Exportação gerada', 'info', 'bi-download', 'Gerada'],
        'export_bi' => ['Exportação da base para BI', 'info', 'bi-file-earmark-spreadsheet-fill', 'Gerada'],
        'export_mapa' => ['Exportação do mapa operacional', 'info', 'bi-map-fill', 'Gerada'],
        'export_relatorios' => ['Exportação de relatório', 'info', 'bi-file-earmark-bar-graph-fill', 'Gerada'],
        'export_relatorios_tematicos' => ['Exportação de relatório temático', 'info', 'bi-file-earmark-bar-graph-fill', 'Gerada'],
        'export_vouchers' => ['Exportação de vouchers', 'info', 'bi-receipt-cutoff', 'Gerada'],
        'export_vouchers_pdfs' => ['Exportação de vouchers em PDF', 'info', 'bi-file-earmark-pdf-fill', 'Gerada'],
        'reservation_attempt_started' => ['Tentativa de reserva recebida', 'info', 'bi-inbox-fill', 'Recebida'],
        'reservation_attempt_accepted' => ['Reserva confirmada', 'success', 'bi-check-circle-fill', 'Confirmada'],
        'reservation_attempt_rejected' => ['Reserva recusada', 'danger', 'bi-exclamation-triangle-fill', 'Recusada'],
        'close_date' => ['Fechamento por data configurado', 'warning', 'bi-calendar-x-fill', 'Fechado'],
        'reopen_date' => ['Restaurante reaberto na data', 'success', 'bi-calendar-check-fill', 'Aberto'],
        'open_date_exception' => ['Exceção de abertura configurada', 'success', 'bi-calendar-plus-fill', 'Aberto'],
        'remove_date_override' => ['Exceção de data removida', 'neutral', 'bi-calendar-minus-fill', 'Removida'],
        'close_weekday' => ['Fechamento semanal configurado', 'warning', 'bi-calendar-week-fill', 'Fechado'],
        'auto_close_timeout' => ['Turno encerrado automaticamente', 'warning', 'bi-clock-history', 'Automático'],
        'anonymize_deactivate' => ['Usuário desativado e anonimizado', 'neutral', 'bi-person-slash', 'Desativado'],
    ];
    if (isset($map[$action])) {
        return $map[$action];
    }
    if (strpos($action, 'export') !== false) {
        return ['Exportação registrada', 'info', 'bi-download', 'Gerada'];
    }
    if (strpos($action, 'reject') !== false || strpos($action, 'denied') !== false || strpos($action, 'failed') !== false || strpos($action, 'blocked') !== false) {
        return ['Ação recusada', 'danger', 'bi-exclamation-triangle-fill', 'Recusada'];
    }
    if (strpos($action, 'accept') !== false || strpos($action, 'confirm') !== false || strpos($action, 'success') !== false) {
        return ['Ação confirmada', 'success', 'bi-check-circle-fill', 'Confirmada'];
    }
    if (strpos($action, 'start') !== false || strpos($action, 'receive') !== false || strpos($action, 'pending') !== false) {
        return ['Solicitação recebida', 'info', 'bi-inbox-fill', 'Recebida'];
    }
    if (strpos($action, 'create') !== false) {
        return ['Registro criado', 'success', 'bi-plus-circle-fill', 'Criado'];
    }
    if (strpos($action, 'update') !== false || strpos($action, 'edit') !== false) {
        return ['Registro atualizado', 'info', 'bi-pencil-square', 'Atualizado'];
    }
    if (strpos($action, 'delete') !== false || strpos($action, 'remove') !== false) {
        return ['Registro removido', 'danger', 'bi-trash3-fill', 'Removido'];
    }
    return ['Evento registrado', 'neutral', 'bi-journal-check', 'Registrado'];
};

$auditAreaLabel = static function ($area): string {
    $areas = [
        'seguranca' => 'Segurança',
        'acessos' => 'Acessos',
        'turnos' => 'Turnos',
        'vouchers' => 'Vouchers',
        'usuarios' => 'Usuários',
        'restaurantes' => 'Restaurantes',
        'operacoes' => 'Operações',
        'portas' => 'Portas',
        'colaborador_refeicoes' => 'Refeições de colaboradores',
        'relatorio_email_config' => 'Configuração de e-mail diário',
        'relatorio_email_destinatarios' => 'Destinatários do e-mail diário',
        'reservas_tematicas_bloqueios_datas' => 'Fechamentos por data',
        'reservas_tematicas_bloqueios_semanais' => 'Fechamentos semanais',
        'reservas_tematicas_tentativas' => 'Tentativas de reserva',
    ];
    $area = trim((string)$area);
    return $areas[$area] ?? ($area !== '' ? ucwords(str_replace('_', ' ', $area)) : 'Sistema');
};

$auditFieldLabel = static function ($key): string {
    $labels = [
        'status' => 'Status', 'uh_id' => 'UH', 'pax' => 'PAX total', 'pax_adulto' => 'Adultos',
        'pax_chd' => 'Crianças', 'pax_real' => 'PAX real', 'titular_nome' => 'Titular',
        'grupo_nome' => 'Grupo', 'data_reserva' => 'Data da reserva', 'turno_id' => 'Turno',
        'restaurante_id' => 'Restaurante', 'observacao_reserva' => 'Observação da reserva',
        'observacao_operacao' => 'Observação operacional', 'observacao_tags' => 'Marcadores',
        'email' => 'E-mail', 'ip' => 'IP', 'motivo' => 'Motivo', 'pax_total_tentado' => 'PAX solicitada',
        'uhs' => 'UHs', 'correlation_id' => 'Protocolo', 'perfil' => 'Perfil', 'ativo' => 'Situação',
    ];
    return $labels[$key] ?? ucwords(str_replace('_', ' ', (string)$key));
};

$auditValue = static function ($value): string {
    if (is_bool($value)) {
        return $value ? 'Sim' : 'Não';
    }
    if ($value === null || $value === '') {
        return 'Não informado';
    }
    if (is_array($value)) {
        return implode(', ', array_map(static function ($item): string {
            return is_scalar($item) ? (string)$item : '...';
        }, $value));
    }
    return (string)$value;
};

$buildChanges = static function (array $before, array $after) use ($auditFieldLabel, $auditValue): array {
    $ignored = ['id', 'grupo_id', 'usuario_id', 'atualizado_por', 'criado_em', 'atualizado_em', 'excedente', 'excedente_motivo', 'excedente_autor_id', 'excedente_em', 'qtd_chd'];
    $keys = array_unique(array_merge(array_keys($before), array_keys($after)));
    $changes = [];
    foreach ($keys as $key) {
        if (in_array($key, $ignored, true)) {
            continue;
        }
        $old = $before[$key] ?? null;
        $new = $after[$key] ?? null;
        if ($old === $new) {
            continue;
        }
        $changes[] = [
            'label' => $auditFieldLabel($key),
            'before' => $auditValue($old),
            'after' => $auditValue($new),
        ];
    }
    return $changes;
};

$renderChanges = static function (array $before, array $after, string $empty = 'Nenhum campo operacional foi alterado', int $max = 3) use ($buildChanges): void {
    $changes = $buildChanges($before, $after);
    if ($changes === []) {
        echo '<span class="audit-muted">' . h($empty) . '</span>';
        return;
    }
    ?>
    <div class="audit-changes" aria-label="Campos alterados">
        <?php foreach (array_slice($changes, 0, $max) as $change): ?>
            <span class="audit-change"><b><?= h($change['label']) ?></b><span><?= h($change['before']) ?></span><i class="bi bi-arrow-right"></i><strong><?= h($change['after']) ?></strong></span>
        <?php endforeach; ?>
    </div>
    <?php if (count($changes) > $max): ?><span class="audit-muted">+<?= count($changes) - $max ?> alteração(ões) no histórico</span><?php endif;
};

$renderEvidence = static function (array $payload, string $label = 'Ver evidência técnica'): void {
    if ($payload === []) {
        return;
    }
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($json) || $json === '') {
        return;
    }
    ?>
    <details class="audit-details audit-technical">
        <summary><?= h($label) ?></summary>
        <pre><?= h($json) ?></pre>
    </details>
    <?php
};

$renderEmpty = static function (string $message): void {
    ?>
    <div class="audit-empty"><i class="bi bi-search"></i><span><?= h($message) ?></span></div>
    <?php
};
?>

<style>
.audit-page { --audit-ink: #162239; --audit-muted: #50637e; --audit-border: #cedbe7; --audit-soft: #f1f6fa; --audit-primary: #0f90b6; --audit-success: #169b5a; --audit-warning: #d76a10; --audit-danger: #d93b42; color: var(--audit-ink); }
.audit-page .audit-hero, .audit-page .audit-filter-card, .audit-page .audit-stream { border: 1px solid var(--audit-border); border-radius: 20px; background: var(--ab-card, #fff); box-shadow: 0 16px 38px rgba(28, 42, 68, .07); }
.audit-page .audit-hero { padding: 1.35rem 1.5rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; background: linear-gradient(120deg, #fff 0%, #f2fbfd 100%); }
.audit-page .audit-eyebrow { color: var(--audit-primary); font-size: .7rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; margin-bottom: .22rem; }
.audit-page h1 { color: var(--audit-ink) !important; font-size: clamp(1.35rem, 2vw, 1.8rem); margin: 0; font-weight: 850; letter-spacing: 0; }
.audit-page .audit-subtitle { color: var(--audit-muted); margin: .32rem 0 0; font-size: .92rem; }
.audit-page .audit-hero-icon { width: 52px; height: 52px; display: grid; place-items: center; border-radius: 16px; color: #fff; background: linear-gradient(135deg, #0b91b8, #25b8cc); box-shadow: 0 10px 22px rgba(15, 144, 182, .25); font-size: 1.4rem; }
.audit-page .audit-metrics { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .7rem; margin-bottom: 1rem; }
.audit-page .audit-metric { min-width: 0; padding: .9rem 1rem; border: 1px solid var(--audit-border); border-radius: 16px; background: var(--audit-soft); }
.audit-page .audit-metric b { display: block; font-size: 1.36rem; line-height: 1; margin-bottom: .35rem; }
.audit-page .audit-metric span { display: block; color: var(--audit-muted); font-size: .76rem; font-weight: 750; }
.audit-page .audit-filter-card { padding: 1.1rem 1.25rem; margin-bottom: 1rem; }
.audit-page .audit-filter-head { display: flex; align-items: center; justify-content: space-between; gap: .8rem; margin-bottom: .9rem; }
.audit-page .audit-filter-head h2, .audit-page .audit-stream-title h2 { margin: 0; font-size: 1rem; font-weight: 850; }
.audit-page .audit-filter-head p { margin: .2rem 0 0; color: var(--audit-muted); font-size: .78rem; }
.audit-page .audit-date-rule { display: inline-flex; align-items: center; gap: .45rem; max-width: 100%; margin: 0 0 .9rem; padding: .48rem .7rem; border: 1px solid #bde7f1; border-radius: 10px; color: #46617d; background: #eefbfe; font-size: .74rem; line-height: 1.3; }.audit-page .audit-date-rule i { color: var(--audit-primary); }.audit-page .audit-date-rule strong { color: #1b5570; }
.audit-page .form-label { color: #485873; font-size: .7rem; letter-spacing: .045em; text-transform: uppercase; font-weight: 850; margin-bottom: .35rem; }
.audit-page .form-text { color: var(--audit-muted); font-size: .72rem; line-height: 1.25; }
.audit-page .form-control, .audit-page .form-select { border-color: #d5e0ed; color: var(--audit-ink); font-weight: 650; box-shadow: none; }
.audit-page .form-control:focus, .audit-page .form-select:focus { border-color: #58b6d0; box-shadow: 0 0 0 .2rem rgba(15, 144, 182, .12); }
.audit-page .btn-primary { background: var(--audit-primary); border-color: var(--audit-primary); font-weight: 800; }
.audit-page .audit-stream { margin-bottom: 1rem; overflow: hidden; }
.audit-page .audit-stream-header { display: flex; align-items: center; justify-content: space-between; gap: .8rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--audit-border); background: linear-gradient(90deg, #fff 0%, #fafcff 100%); }
.audit-page .audit-stream-title { display: flex; align-items: center; gap: .75rem; min-width: 0; }
.audit-page .audit-stream-title > i { display: grid; place-items: center; width: 36px; height: 36px; flex: 0 0 36px; border-radius: 11px; color: var(--audit-primary); background: #e7f7fb; }
.audit-page .audit-stream-title p { color: var(--audit-muted); font-size: .78rem; margin: .15rem 0 0; }
.audit-page .audit-count { border-radius: 999px; padding: .36rem .65rem; color: #42607c; background: #eef4f9; font-size: .72rem; font-weight: 850; white-space: nowrap; }
.audit-page .audit-list { padding: .45rem; }
.audit-page .audit-event { display: grid; grid-template-columns: 38px minmax(230px, 1.1fr) minmax(200px, .92fr) minmax(250px, 1.25fr) minmax(126px, auto); align-items: start; gap: 1rem; padding: .9rem; border-radius: 14px; }
.audit-page .audit-event + .audit-event { border-top: 1px solid #e7edf4; }
.audit-page .audit-event:hover { background: #f8fbfd; }
.audit-page .audit-event-icon { display: grid; place-items: center; width: 36px; height: 36px; border-radius: 11px; font-size: 1rem; }
.audit-page .audit-event-icon.success { background: #e8f8ef; color: var(--audit-success); }.audit-page .audit-event-icon.info { background: #e6f7fb; color: var(--audit-primary); }.audit-page .audit-event-icon.warning { background: #fff4e6; color: var(--audit-warning); }.audit-page .audit-event-icon.danger { background: #fdebed; color: var(--audit-danger); }.audit-page .audit-event-icon.neutral { background: #eef2f7; color: #61718a; }
.audit-page .audit-event-icon { margin-top: .08rem; }.audit-page .audit-event-main { min-width: 0; padding-top: .05rem; }.audit-page .audit-event-title { display: flex; align-items: center; gap: .45rem; flex-wrap: wrap; font-weight: 850; font-size: .9rem; }.audit-page .audit-event-time { color: var(--audit-muted); font-size: .74rem; margin-top: .22rem; line-height: 1.45; }
.audit-page .audit-badge { border: 1px solid transparent; border-radius: 999px; padding: .22rem .5rem; font-size: .68rem; font-weight: 850; white-space: nowrap; }.audit-page .audit-badge.success { color: #087a42; background: #e9f9f0; border-color: #bcebd1; }.audit-page .audit-badge.info { color: #08799a; background: #e8f8fc; border-color: #b9e8f3; }.audit-page .audit-badge.warning { color: #a54d05; background: #fff4e7; border-color: #ffd5ad; }.audit-page .audit-badge.danger { color: #af2930; background: #feecef; border-color: #f7c4c8; }.audit-page .audit-badge.neutral { color: #5a6c84; background: #f0f4f8; border-color: #d9e2eb; }
.audit-page .audit-context { min-width: 0; min-height: 42px; color: #334660; font-size: .8rem; line-height: 1.35; padding-top: .05rem; }.audit-page .audit-context b { display: block; color: #60728c; font-size: .65rem; letter-spacing: .04em; text-transform: uppercase; margin-bottom: .24rem; }.audit-page .audit-context .tag { max-width: 100%; white-space: normal; }
.audit-page .audit-event-context { padding-left: .8rem; border-left: 2px solid #dbe8f1; }.audit-page .audit-event-changes { padding-left: .8rem; border-left: 2px solid #b9e8f3; }.audit-page .audit-changes { display: flex; flex-wrap: wrap; gap: .38rem; }.audit-page .audit-change { display: inline-flex; align-items: center; gap: .3rem; max-width: 100%; min-height: 29px; padding: .34rem .48rem; border: 1px solid #cbdfea; border-radius: 9px; background: #f8fcfe; color: #526a83; font-size: .7rem; font-weight: 700; line-height: 1.2; }.audit-page .audit-change b { color: #36536f; font-size: .63rem; letter-spacing: .035em; text-transform: uppercase; }.audit-page .audit-change strong { color: #087d49; }.audit-page .audit-change i { color: #6487a2; }
.audit-page .audit-event-more { display: flex; align-items: center; justify-content: flex-end; min-width: 0; min-height: 42px; text-align: right; }.audit-page .audit-event-more .audit-details { margin: 0; }.audit-page .audit-muted { display: block; color: var(--audit-muted); font-size: .76rem; line-height: 1.42; }.audit-page .audit-detail-list { display: grid; gap: .35rem; padding: .7rem .1rem 0; }.audit-page .audit-detail-list > div { display: grid; grid-template-columns: minmax(100px, .65fr) 1fr 20px 1fr; gap: .35rem; align-items: center; color: #52647d; font-size: .75rem; }.audit-page .audit-detail-list strong { color: #1d8b5a; }
.audit-page .audit-details { margin-top: .45rem; }.audit-page .audit-details summary { cursor: pointer; color: var(--audit-primary); font-size: .74rem; font-weight: 800; list-style: none; }.audit-page .audit-details summary::-webkit-details-marker { display: none; }.audit-page .audit-details summary::before { content: '+'; display: inline-grid; place-items: center; width: 17px; height: 17px; margin-right: .32rem; border: 1px solid #9fd5e3; border-radius: 50%; }.audit-page .audit-details[open] summary::before { content: '−'; }.audit-page .audit-technical pre { max-height: 260px; margin: .55rem 0 0; padding: .75rem; overflow: auto; border: 1px solid #dce7f0; border-radius: 10px; background: #f6f9fc; color: #52647d; font-size: .68rem; line-height: 1.42; white-space: pre-wrap; overflow-wrap: anywhere; }
.audit-page .audit-empty { display: flex; align-items: center; justify-content: center; gap: .55rem; min-height: 112px; color: var(--audit-muted); font-size: .85rem; }.audit-page .audit-empty i { color: #a9bacb; font-size: 1.05rem; }.audit-page .audit-pagination { display: flex; align-items: center; justify-content: space-between; gap: .7rem; padding: .8rem 1.25rem; border-top: 1px solid var(--audit-border); color: var(--audit-muted); font-size: .75rem; }.audit-page .audit-pagination .page-link { min-width: 2rem; text-align: center; border-radius: 8px; color: #49607a; border-color: #d8e3ed; }.audit-page .audit-pagination .active .page-link { background: var(--audit-primary); border-color: var(--audit-primary); color: #fff; }
html[data-theme="dark"] .audit-page { --audit-ink: #f3f7fc; --audit-muted: #a9b8ca; --audit-border: #2b3a4e; --audit-soft: #182638; }
html[data-theme="dark"] .audit-page .audit-hero { background: linear-gradient(120deg, #172538 0%, #173743 100%); }
html[data-theme="dark"] .audit-page .audit-filter-card,
html[data-theme="dark"] .audit-page .audit-stream { background: #121e2d; }
html[data-theme="dark"] .audit-page .audit-date-rule { border-color: #245469; background: #102a36; color: #c0d3e0; }
html[data-theme="dark"] .audit-page .audit-date-rule strong { color: #e3f2f9; }
html[data-theme="dark"] .audit-page .audit-stream-header { background: linear-gradient(90deg, #142133 0%, #18283a 100%); }
html[data-theme="dark"] .audit-page .audit-metric { background: #182638; }
html[data-theme="dark"] .audit-page .form-label { color: #c6d3e1; }
html[data-theme="dark"] .audit-page .form-control,
html[data-theme="dark"] .audit-page .form-select { color: #f1f6fc; background: #172538; border-color: #31445b; }
html[data-theme="dark"] .audit-page .audit-event:hover { background: #18283a; }
html[data-theme="dark"] .audit-page .audit-event + .audit-event { border-color: #26384b; }
html[data-theme="dark"] .audit-page .audit-event-title,
html[data-theme="dark"] .audit-page .audit-context { color: #e7eef7; }
html[data-theme="dark"] .audit-page .audit-context b,
html[data-theme="dark"] .audit-page .audit-change b { color: #b5c4d6; }
html[data-theme="dark"] .audit-page .audit-event-context { border-left-color: #36516b; } html[data-theme="dark"] .audit-page .audit-event-changes { border-left-color: #26758d; } html[data-theme="dark"] .audit-page .audit-change { background: #1b2b3d; border-color: #33465c; color: #c1cede; }
html[data-theme="dark"] .audit-page .audit-change strong,
html[data-theme="dark"] .audit-page .audit-detail-list strong { color: #6be0a1; }
html[data-theme="dark"] .audit-page .audit-detail-list > div { color: #c8d5e3; border-color: #2c4055; }
html[data-theme="dark"] .audit-page .audit-technical pre { color: #d5e0eb; background: #101a28; border-color: #33465c; }
html[data-theme="dark"] .audit-page .audit-pagination .page-link { color: #d5e4f2; background: #182638; border-color: #34485d; }
@media (max-width: 991px) { .audit-page .audit-event { grid-template-columns: 36px 1fr; gap: .65rem .8rem; }.audit-page .audit-event-context, .audit-page .audit-event-changes, .audit-page .audit-event-more { grid-column: 2; }.audit-page .audit-event-context, .audit-page .audit-event-changes { padding-left: .7rem; }.audit-page .audit-event-more { justify-content: flex-start; text-align: left; }.audit-page .audit-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 576px) { .audit-page { overflow-x: clip; }.audit-page .audit-hero { padding: 1rem; align-items: flex-start; }.audit-page .audit-hero-icon { width: 42px; height: 42px; border-radius: 13px; font-size: 1.15rem; }.audit-page .audit-subtitle { font-size: .8rem; }.audit-page .audit-metrics { gap: .5rem; }.audit-page .audit-metric { padding: .72rem; border-radius: 13px; }.audit-page .audit-metric b { font-size: 1.15rem; }.audit-page .audit-filter-card { padding: .9rem; }.audit-page .audit-stream-header { padding: .85rem .9rem; }.audit-page .audit-stream-title p { display: none; }.audit-page .audit-count { font-size: .66rem; }.audit-page .audit-list { padding: .25rem; }.audit-page .audit-event { padding: .8rem .65rem; }.audit-page .audit-event-title { font-size: .84rem; }.audit-page .audit-context { font-size: .77rem; }.audit-page .audit-change { width: 100%; justify-content: flex-start; }.audit-page .audit-detail-list > div { grid-template-columns: 1fr; gap: .15rem; padding: .4rem 0; border-bottom: 1px solid #e7edf4; }.audit-page .audit-detail-list i { display: none; }.audit-page .audit-pagination { flex-direction: column; align-items: center; padding: .8rem; }.audit-page .pagination { flex-wrap: wrap; justify-content: center; } }
</style>

<div class="audit-page">
    <section class="audit-hero">
        <div>
            <div class="audit-eyebrow">Governança e rastreabilidade</div>
            <h1>Auditoria do sistema</h1>
            <p class="audit-subtitle">Consulte quem fez cada ação, o que mudou e qual foi o resultado.</p>
        </div>
        <div class="audit-hero-icon"><i class="bi bi-shield-check"></i></div>
    </section>

    <section class="audit-metrics" aria-label="Resumo da auditoria no filtro">
        <div class="audit-metric"><b><?= (int)($attemptLogs['total'] ?? 0) ?></b><span>Tentativas de reserva</span></div>
        <div class="audit-metric"><b><?= (int)($thematicLogs['total'] ?? 0) ?></b><span>Eventos temáticos</span></div>
        <div class="audit-metric"><b><?= (int)($shiftLogs['total'] ?? 0) ?></b><span>Turnos registrados</span></div>
        <div class="audit-metric"><b><?= (int)($generalLogs['total'] ?? 0) ?></b><span>Eventos gerais</span></div>
    </section>

    <section class="audit-filter-card">
        <div class="audit-filter-head">
            <div><h2>Consultar trilhas</h2><p>Combine data, pessoa, UH ou área para localizar um evento específico.</p></div>
            <i class="bi bi-funnel-fill text-primary"></i>
        </div>
        <div class="audit-date-rule"><i class="bi bi-calendar-range"></i><span>Escolha <strong>data única</strong> ou <strong>período</strong>. Ao preencher um formato, o outro é limpo automaticamente.</span></div>
        <form class="row g-3 align-items-end" method="get" action="/" data-ajax-filter data-ajax-target=".app-content">
            <input type="hidden" name="r" value="auditoria/index">
            <div class="col-12 col-md-6 col-xl"><label class="form-label">Data única</label><input type="date" class="form-control input-xl" name="data" value="<?= h($filters['data'] ?? '') ?>"></div>
            <div class="col-12 col-md-6 col-xl"><label class="form-label">Período - início</label><input type="date" class="form-control input-xl" name="data_inicio" value="<?= h($filters['data_inicio'] ?? '') ?>"></div>
            <div class="col-12 col-md-6 col-xl"><label class="form-label">Período - fim</label><input type="date" class="form-control input-xl" name="data_fim" value="<?= h($filters['data_fim'] ?? '') ?>"></div>
            <div class="col-12 col-md-6 col-xl"><label class="form-label">Usuário relacionado</label><select class="form-select input-xl" name="usuario_id"><option value="">Todos</option><?php foreach ($usuarios as $usuario): ?><option value="<?= (int)$usuario['id'] ?>" <?= ($filters['usuario_id'] ?? '') == $usuario['id'] ? 'selected' : '' ?>><?= h($usuario['nome']) ?> (<?= h($usuario['perfil']) ?>)</option><?php endforeach; ?></select></div>
            <div class="col-12 col-md-6 col-xl"><label class="form-label">UH da reserva</label><input type="text" inputmode="numeric" class="form-control input-xl" name="uh_numero" value="<?= h($filters['uh_numero'] ?? '') ?>" placeholder="Ex.: 4002"></div>
            <div class="col-12 col-md-6 col-xl"><label class="form-label">Tabela ou área</label><input type="text" class="form-control input-xl" name="tabela" value="<?= h($filters['tabela'] ?? '') ?>" placeholder="Ex.: seguranca"></div>
            <div class="col-12 col-md-auto"><button class="btn btn-primary btn-xl w-100"><i class="bi bi-search me-1"></i> Filtrar</button></div>
        </form>
    </section>

    <section class="audit-stream">
        <header class="audit-stream-header"><div class="audit-stream-title"><i class="bi bi-send-check"></i><div><h2>Tentativas de reserva</h2><p>Solicitações confirmadas ou recusadas, inclusive quando não viraram reserva.</p></div></div><span class="audit-count"><?= (int)($attemptLogs['total'] ?? 0) ?> eventos</span></header>
        <div class="audit-list">
            <?php foreach (($attemptLogs['rows'] ?? []) as $log): ?>
                <?php
                    $payload = $decodeAuditPayload($log['dados_depois'] ?? '');
                    $meta = $auditActionMeta($log['acao'] ?? '');
                    $units = array_values(array_filter(array_map('strval', (array)($payload['uhs'] ?? []))));
                    $request = array_filter([(string)($payload['restaurante'] ?? ''), (string)($payload['turno'] ?? ''), (string)($payload['data_reserva'] ?? '')]);
                    $reason = trim((string)($payload['motivo'] ?? ''));
                ?>
                <article class="audit-event"><div class="audit-event-icon <?= h($meta[1]) ?>"><i class="bi <?= h($meta[2]) ?>"></i></div><div class="audit-event-main"><div class="audit-event-title"><?= h($meta[0]) ?><span class="audit-badge <?= h($meta[1]) ?>"><?= h($meta[3]) ?></span><span class="audit-badge neutral"><?= h($auditActorLabel($log)) ?></span></div><div class="audit-event-time"><?= h($formatDateTime($log['criado_em'] ?? '')) ?><?= !empty($payload['correlation_id']) ? ' · Protocolo ' . h(substr((string)$payload['correlation_id'], 0, 10)) : '' ?></div></div><div class="audit-context audit-event-context"><b>Solicitação</b><?= h(implode(' · ', $request) ?: 'Contexto não informado') ?></div><div class="audit-context audit-event-changes"><b><?= $reason !== '' ? 'Motivo informado' : 'UHs e PAX' ?></b><?= $reason !== '' ? h($reason) : h(($units !== [] ? 'UH ' . implode(', ', $units) . ' · ' : '') . ((int)($payload['pax_total_tentado'] ?? 0)) . ' PAX') ?></div><div class="audit-event-more"><?php $renderEvidence($payload, 'Ver dados da tentativa'); ?></div></article>
            <?php endforeach; ?>
            <?php if (empty($attemptLogs['rows'] ?? [])): $renderEmpty('Nenhuma tentativa de reserva no filtro.'); endif; ?>
        </div>
        <?php $renderPagination($attemptLogs, $filters); ?>
    </section>

    <section class="audit-stream">
        <header class="audit-stream-header"><div class="audit-stream-title"><i class="bi bi-calendar-heart"></i><div><h2>Histórico de reservas temáticas</h2><p>Criações, edições, status, mudanças de restaurante/turno e intervenções.</p></div></div><span class="audit-count"><?= (int)($thematicLogs['total'] ?? 0) ?> eventos</span></header>
        <div class="audit-list">
            <?php foreach (($thematicLogs['rows'] ?? []) as $log): ?>
                <?php
                    $before = $decodeAuditPayload($log['dados_antes'] ?? '');
                    $after = $decodeAuditPayload($log['dados_depois'] ?? '');
                    $displayBefore = $before;
                    $displayAfter = $after;
                    if (!empty($log['uh_antes_numero'])) { $displayBefore['uh_id'] = 'UH ' . $log['uh_antes_numero']; }
                    if (!empty($log['uh_depois_numero'])) { $displayAfter['uh_id'] = 'UH ' . $log['uh_depois_numero']; }
                    if (!empty($log['restaurante_antes'])) { $displayBefore['restaurante_id'] = $log['restaurante_antes']; }
                    if (!empty($log['restaurante_depois'])) { $displayAfter['restaurante_id'] = $log['restaurante_depois']; }
                    if (!empty($log['turno_antes_hora'])) { $displayBefore['turno_id'] = substr((string)$log['turno_antes_hora'], 0, 5); }
                    if (!empty($log['turno_depois_hora'])) { $displayAfter['turno_id'] = substr((string)$log['turno_depois_hora'], 0, 5); }
                    $action = strtolower(trim((string)($log['acao'] ?? '')));
                    $meta = $auditActionMeta($action);
                    $isCreation = $action === 'create';
                    $thematicTitle = $isCreation
                        ? 'Reserva criada'
                        : ($action === 'status' ? 'Status da reserva atualizado' : ($action === 'update' ? 'Reserva ajustada' : $meta[0]));
                    $changes = $buildChanges($displayBefore, $displayAfter);
                    $reservation = 'UH ' . (string)($log['uh_numero'] ?? 'não informada') . ' · ' . (string)($log['data_reserva'] ?? 'data não informada');
                ?>
                <article class="audit-event audit-reservation-event">
                    <div class="audit-event-icon <?= h($meta[1]) ?>"><i class="bi <?= h($meta[2]) ?>"></i></div>
                    <div class="audit-event-main">
                        <div class="audit-event-title"><?= h($thematicTitle) ?><span class="audit-badge <?= h($meta[1]) ?>"><?= h($meta[3]) ?></span><span class="audit-badge neutral"><?= h($auditActorLabel($log, 'sistema')) ?></span></div>
                        <div class="audit-event-time"><?= h($formatDateTime($log['criado_em'] ?? '')) ?> · <?= h($reservation) ?><?= !empty($log['reserva_criador']) ? ' · Criada por ' . h($log['reserva_criador']) : '' ?></div>
                    </div>
                    <div class="audit-context audit-event-context"><b>Reserva direcionada para</b><span class="tag <?= restaurant_badge_class($log['restaurante'] ?? '') ?>"><?= h($log['restaurante'] ?? 'Não informado') ?></span> · <?= h(substr((string)($log['turno_hora'] ?? ''), 0, 5) ?: 'Sem turno') ?></div>
                    <div class="audit-context audit-event-changes">
                        <b><?= !empty($log['justificativa']) ? 'Motivo informado' : ($isCreation ? 'Registro inicial' : 'O que mudou') ?></b>
                        <?php if (!empty($log['justificativa'])): ?>
                            <?= h($log['justificativa']) ?>
                        <?php elseif ($isCreation): ?>
                            <span class="audit-muted">A reserva foi registrada e está disponível para a operação.</span>
                        <?php else: ?>
                            <?php $renderChanges($displayBefore, $displayAfter); ?>
                        <?php endif; ?>
                    </div>
                    <div class="audit-event-more">
                        <?php if (!$isCreation && count($changes) > 3): ?><details class="audit-details"><summary>Ver todas as <?= count($changes) ?> alterações</summary><div class="audit-detail-list"><?php foreach ($changes as $change): ?><div><b><?= h($change['label']) ?></b><span><?= h($change['before']) ?></span><i class="bi bi-arrow-right"></i><strong><?= h($change['after']) ?></strong></div><?php endforeach; ?></div></details><?php endif; ?>
                        <?php if (!$isCreation): ?><?php $renderEvidence(['antes' => $before, 'depois' => $after], 'Dados técnicos'); ?><?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if (empty($thematicLogs['rows'] ?? [])): $renderEmpty('Nenhum histórico de reserva temática no filtro.'); endif; ?>
        </div>
        <?php $renderPagination($thematicLogs, $filters); ?>
    </section>

    <section class="audit-stream">
        <header class="audit-stream-header"><div class="audit-stream-title"><i class="bi bi-clock-history"></i><div><h2>Turnos operacionais</h2><p>Início, encerramento, registros e PAX por responsável.</p></div></div><span class="audit-count"><?= (int)($shiftLogs['total'] ?? 0) ?> turnos</span></header>
        <div class="audit-list">
            <?php foreach (($shiftLogs['rows'] ?? []) as $turno): ?>
                <?php $isOpen = empty($turno['fim_em']); ?>
                <article class="audit-event"><div class="audit-event-icon <?= $isOpen ? 'warning' : 'success' ?>"><i class="bi <?= $isOpen ? 'bi-play-circle-fill' : 'bi-check2-circle' ?>"></i></div><div class="audit-event-main"><div class="audit-event-title"><?= $isOpen ? 'Turno em andamento' : 'Turno encerrado' ?><span class="audit-badge <?= $isOpen ? 'warning' : 'success' ?>"><?= $isOpen ? 'Em andamento' : 'Encerrado' ?></span><span class="audit-badge neutral"><?= h($turno['usuario'] ?? 'Não identificado') ?></span></div><div class="audit-event-time">Início: <?= h($formatDateTime($turno['inicio_em'] ?? '')) ?><?= $isOpen ? '' : ' · Fim: ' . h($formatDateTime($turno['fim_em'])) ?></div></div><div class="audit-context audit-event-context"><b>Operação</b><span class="tag <?= restaurant_badge_class($turno['restaurante'] ?? '') ?>"><?= h($turno['restaurante'] ?? '') ?></span> <span class="tag <?= operation_badge_class($turno['operacao'] ?? '') ?>"><?= h($turno['operacao'] ?? '') ?></span></div><div class="audit-context audit-event-changes"><b>Resultado</b><?= (int)($turno['total_registros'] ?? 0) ?> registros · <strong><?= (int)($turno['total_pax'] ?? 0) ?> PAX</strong></div><div></div></article>
            <?php endforeach; ?>
            <?php if (empty($shiftLogs['rows'] ?? [])): $renderEmpty('Nenhum turno operacional no filtro.'); endif; ?>
        </div>
        <?php $renderPagination($shiftLogs, $filters); ?>
    </section>

    <section class="audit-stream">
        <header class="audit-stream-header"><div class="audit-stream-title"><i class="bi bi-journal-text"></i><div><h2>Segurança, exportações e cadastros</h2><p>Eventos administrativos, autenticação e ações registradas fora das reservas.</p></div></div><span class="audit-count"><?= (int)($generalLogs['total'] ?? 0) ?> eventos</span></header>
        <div class="audit-list">
            <?php foreach (($generalLogs['rows'] ?? []) as $log): ?>
                <?php
                    $payload = $decodeAuditPayload($log['dados_depois'] ?? '');
                    $meta = $auditActionMeta($log['acao'] ?? '');
                    $summary = trim((string)($payload['motivo'] ?? $payload['mensagem'] ?? ''));
                    if ($summary === '') {
                        $bits = [];
                        foreach (['email', 'arquivo', 'formato', 'periodo', 'ip'] as $field) {
                            if (!empty($payload[$field]) && is_scalar($payload[$field])) { $bits[] = $auditFieldLabel($field) . ': ' . $auditValue($payload[$field]); }
                        }
                        $summary = implode(' · ', $bits);
                    }
                ?>
                <article class="audit-event"><div class="audit-event-icon <?= h($meta[1]) ?>"><i class="bi <?= h($meta[2]) ?>"></i></div><div class="audit-event-main"><div class="audit-event-title"><?= h($meta[0]) ?><span class="audit-badge <?= h($meta[1]) ?>"><?= h($meta[3]) ?></span><span class="audit-badge neutral"><?= h($auditActorLabel($log)) ?></span></div><div class="audit-event-time"><?= h($formatDateTime($log['criado_em'] ?? '')) ?><?= !empty($log['registro_id']) ? ' · Registro #' . (int)$log['registro_id'] : '' ?></div></div><div class="audit-context audit-event-context"><b>Área</b><?= h($auditAreaLabel($log['tabela'] ?? '')) ?></div><div class="audit-context audit-event-changes"><b>Contexto</b><?= h($summary !== '' ? $summary : 'Evento registrado sem contexto adicional') ?></div><div class="audit-event-more"><?php $renderEvidence($payload); ?></div></article>
            <?php endforeach; ?>
            <?php if (empty($generalLogs['rows'] ?? [])): $renderEmpty('Nenhum evento geral no filtro.'); endif; ?>
        </div>
        <?php $renderPagination($generalLogs, $filters); ?>
    </section>
</div>
