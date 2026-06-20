<?php
$flash = $this->data['flash'] ?? null;
$restaurantes = $this->data['restaurantes'] ?? [];
$assignmentOptions = $this->data['assignment_options'] ?? [];
$assignedRestaurants = $this->data['assigned_restaurants'] ?? [];
$assignedOperations = $this->data['assigned_operations'] ?? [];
$itemsAtivos = $this->data['items_ativos'] ?? [];
$itemsDesativados = $this->data['items_desativados'] ?? [];
$canManagePrivilegedProfiles = !empty($this->data['can_manage_privileged_profiles']);
$tabAtual = ($_GET['tab'] ?? 'ativos') === 'desativados' ? 'desativados' : 'ativos';
$viewer = Auth::user() ?? [];
$viewerId = (int)($viewer['id'] ?? 0);
$secaoAtual = $_GET['secao'] ?? '';
if (!in_array($secaoAtual, ['cadastro', 'base'], true)) {
    $flashMessage = mb_strtolower((string)($flash['message'] ?? ''), 'UTF-8');
    $secaoAtual = strpos($flashMessage, 'usuário criado') !== false || strpos($flashMessage, 'usuario criado') !== false
        ? 'cadastro'
        : 'base';
}

$userTabs = [
    'ativos' => ['label' => 'Ativos', 'items' => $itemsAtivos],
    'desativados' => ['label' => 'Desativados', 'items' => $itemsDesativados],
];

$countProfile = static function (array $items, string $perfil): int {
    $total = 0;
    foreach ($items as $item) {
        if (($item['perfil'] ?? '') === $perfil) {
            $total++;
        }
    }
    return $total;
};

$allUsers = array_merge($itemsAtivos, $itemsDesativados);
$statsCards = [
    ['label' => 'Ativos', 'value' => count($itemsAtivos), 'tone' => 'success'],
    ['label' => 'Hostess', 'value' => $countProfile($itemsAtivos, 'hostess'), 'tone' => 'info'],
    ['label' => 'Supervisão', 'value' => $countProfile($itemsAtivos, 'supervisor'), 'tone' => 'warning'],
    ['label' => 'Gestão', 'value' => $countProfile($itemsAtivos, 'gerente') + $countProfile($itemsAtivos, 'admin'), 'tone' => 'accent'],
];

$buildAssignmentState = static function (int $uid, array $opt) use ($assignedRestaurants, $assignedOperations): bool {
    $rid = (int)($opt['restaurante_id'] ?? 0);
    $oid = isset($opt['operacao_id']) && $opt['operacao_id'] !== null ? (int)$opt['operacao_id'] : null;
    $restSelected = in_array($rid, $assignedRestaurants[$uid] ?? [], true);
    $opsSelected = $assignedOperations[$uid][$rid] ?? [];

    if ($oid === null) {
        return $restSelected;
    }

    return in_array($oid, $opsSelected, true) || ($restSelected && empty($opsSelected));
};

$renderAssignmentChoices = static function (string $prefix, int $uid = 0) use ($assignmentOptions, $buildAssignmentState): void {
    foreach ($assignmentOptions as $opt) {
        $key = preg_replace('/[^a-zA-Z0-9_]/', '_', (string)$opt['key']);
        $checked = $uid > 0 && $buildAssignmentState($uid, $opt);
        ?>
        <div class="tag-choice">
            <input
                type="checkbox"
                id="<?= h($prefix . '_' . $key) ?>"
                name="assignments[]"
                value="<?= h((string)$opt['key']) ?>"
                <?= $checked ? 'checked' : '' ?>
            >
            <label for="<?= h($prefix . '_' . $key) ?>"><?= h((string)$opt['label']) ?></label>
        </div>
        <?php
    }
};

$assignmentSummary = static function (int $uid) use ($assignedRestaurants, $assignedOperations): array {
    $restaurants = $assignedRestaurants[$uid] ?? [];
    $operationsMap = $assignedOperations[$uid] ?? [];
    $operations = 0;
    foreach ($operationsMap as $ops) {
        $operations += count($ops);
    }
    return [
        'restaurants' => count($restaurants),
        'operations' => $operations,
    ];
};
?>

<style>
    .usuarios-page {
        display: grid;
        gap: 1.25rem;
    }
    .usuarios-page .min-w-0 {
        min-width: 0;
    }
    .usuarios-layout-card,
    .usuarios-panel-card,
    .usuarios-manage-card,
    .usuarios-guide-card,
    .usuario-modal-panel {
        border: 1px solid var(--ab-border);
        border-radius: 22px;
        box-shadow: 0 18px 38px rgba(15, 23, 42, 0.08);
    }
    .usuarios-layout-card {
        overflow: hidden;
        background:
            linear-gradient(135deg, color-mix(in srgb, var(--ab-card) 97%, #ffffff 3%) 0%, color-mix(in srgb, var(--ab-soft-bg) 74%, var(--ab-card) 26%) 100%);
    }
    .usuarios-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }
    .usuarios-header-copy {
        max-width: 700px;
    }
    .usuarios-header-copy p {
        max-width: 60ch;
    }
    .usuarios-section-switch {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        padding: 0.45rem;
        border-radius: 999px;
        background: color-mix(in srgb, var(--ab-soft-bg) 82%, var(--ab-card) 18%);
        border: 1px solid color-mix(in srgb, var(--ab-border) 78%, transparent);
    }
    .usuarios-section-switch .nav-link {
        border: 0;
        border-radius: 999px;
        color: var(--ab-muted);
        font-weight: 800;
        padding: 0.65rem 1rem;
    }
    .usuarios-section-switch .nav-link.active {
        background: linear-gradient(135deg, var(--ab-accent), #fb923c);
        color: #fff;
        box-shadow: 0 12px 24px rgba(249, 115, 22, 0.22);
    }
    .usuarios-stat-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.85rem;
        margin-top: 1rem;
    }
    .usuarios-stat-card {
        padding: 1rem 1.05rem;
        border-radius: 18px;
        background: color-mix(in srgb, var(--ab-card) 92%, var(--ab-soft-bg) 8%);
        border: 1px solid color-mix(in srgb, var(--ab-border) 82%, transparent);
    }
    .usuarios-stat-value {
        font-size: 1.65rem;
        font-weight: 900;
        line-height: 1;
        color: var(--ab-ink);
    }
    .usuarios-stat-label {
        margin-top: 0.3rem;
        color: var(--ab-muted);
        font-size: 0.83rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .usuarios-stat-card[data-tone="success"] .usuarios-stat-value { color: #15803d; }
    .usuarios-stat-card[data-tone="info"] .usuarios-stat-value { color: #0369a1; }
    .usuarios-stat-card[data-tone="warning"] .usuarios-stat-value { color: #c2410c; }
    .usuarios-stat-card[data-tone="accent"] .usuarios-stat-value { color: var(--ab-accent); }
    .usuarios-content-card {
        padding: 1.2rem;
        border-top: 1px solid color-mix(in srgb, var(--ab-border) 74%, transparent);
    }
    .usuarios-panel-card {
        background:
            linear-gradient(180deg, color-mix(in srgb, var(--ab-card) 98%, #ffffff 2%) 0%, color-mix(in srgb, var(--ab-soft-bg) 42%, var(--ab-card) 58%) 100%);
    }
    .usuarios-panel-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .usuarios-guide-card {
        padding: 1rem;
        background: color-mix(in srgb, var(--ab-soft-bg) 80%, var(--ab-card) 20%);
    }
    .usuarios-guide-card ul {
        margin: 0.75rem 0 0;
        padding-left: 1.1rem;
        color: var(--ab-muted);
    }
    .usuarios-guide-card li + li {
        margin-top: 0.38rem;
    }
    .usuarios-assignment-frame {
        border: 1px solid color-mix(in srgb, var(--ab-border) 82%, transparent);
        border-radius: 18px;
        background: color-mix(in srgb, var(--ab-card) 88%, var(--ab-soft-bg) 12%);
        padding: 0.9rem;
    }
    .usuarios-assignment-frame .tag-grid {
        margin-top: 0.85rem;
    }
    .usuarios-base-card {
        padding: 1rem;
    }
    .usuarios-toolbar {
        background: color-mix(in srgb, var(--ab-soft-bg) 80%, var(--ab-card) 20%);
        border: 1px solid color-mix(in srgb, var(--ab-border) 78%, transparent);
        border-radius: 18px;
        padding: 0.9rem;
        margin-bottom: 1rem;
    }
    .usuarios-status-tabs {
        display: inline-flex;
        gap: 0.45rem;
        padding: 0.35rem;
        border-radius: 999px;
        background: color-mix(in srgb, var(--ab-soft-bg) 82%, var(--ab-card) 18%);
        border: 1px solid color-mix(in srgb, var(--ab-border) 80%, transparent);
    }
    .usuarios-status-tabs .nav-link {
        border-radius: 999px;
        border: 0;
        color: var(--ab-muted);
        font-weight: 800;
        padding: 0.55rem 0.9rem;
    }
    .usuarios-status-tabs .nav-link.active {
        background: color-mix(in srgb, var(--ab-accent) 10%, var(--ab-card) 90%);
        color: var(--ab-accent);
    }
    .usuarios-card-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }
    .usuarios-manage-card {
        width: 100%;
        appearance: none;
        border: 1px solid color-mix(in srgb, var(--ab-border) 80%, transparent);
        background:
            linear-gradient(180deg, color-mix(in srgb, var(--ab-card) 97%, #ffffff 3%) 0%, color-mix(in srgb, var(--ab-soft-bg) 32%, var(--ab-card) 68%) 100%);
        padding: 1rem;
        text-align: left;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .usuarios-manage-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 18px 38px rgba(15, 23, 42, 0.12);
        border-color: color-mix(in srgb, var(--ab-accent) 26%, var(--ab-border) 74%);
    }
    .usuarios-card-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.75rem;
    }
    .usuarios-avatar {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        font-size: 1rem;
        color: var(--ab-accent);
        background: color-mix(in srgb, var(--ab-accent) 12%, var(--ab-card) 88%);
        border: 1px solid color-mix(in srgb, var(--ab-accent) 22%, transparent);
        flex: 0 0 auto;
    }
    .usuarios-card-name {
        color: var(--ab-ink);
        font-size: 1rem;
        font-weight: 900;
        line-height: 1.2;
    }
    .usuarios-card-email {
        color: var(--ab-muted);
        font-size: 0.84rem;
        margin-top: 0.22rem;
        overflow-wrap: anywhere;
    }
    .usuarios-card-tags,
    .usuarios-card-metrics {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
    }
    .usuarios-card-tags {
        margin-top: 0.9rem;
    }
    .usuarios-card-metrics {
        margin-top: 0.9rem;
    }
    .usuarios-metric-chip {
        min-width: 0;
        padding: 0.58rem 0.75rem;
        border-radius: 14px;
        border: 1px solid color-mix(in srgb, var(--ab-border) 80%, transparent);
        background: color-mix(in srgb, var(--ab-soft-bg) 76%, var(--ab-card) 24%);
        flex: 1 1 140px;
    }
    .usuarios-metric-chip strong {
        display: block;
        color: var(--ab-ink);
        font-size: 0.95rem;
        line-height: 1.1;
    }
    .usuarios-metric-chip span {
        display: block;
        color: var(--ab-muted);
        font-size: 0.76rem;
        margin-top: 0.2rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .usuarios-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-top: 1rem;
        padding-top: 0.9rem;
        border-top: 1px solid color-mix(in srgb, var(--ab-border) 76%, transparent);
    }
    .usuarios-card-action {
        color: var(--ab-accent);
        font-weight: 800;
        font-size: 0.84rem;
    }
    .usuarios-page-empty {
        padding: 1.3rem;
        border: 1px dashed color-mix(in srgb, var(--ab-border) 78%, transparent);
        border-radius: 18px;
        background: color-mix(in srgb, var(--ab-soft-bg) 76%, var(--ab-card) 24%);
        text-align: center;
        color: var(--ab-muted);
    }
    .usuarios-pagination .btn {
        min-width: 38px;
    }
    .usuarios-pagination .btn.active {
        pointer-events: none;
    }
    .usuario-modal .modal-dialog {
        max-width: 920px;
        margin: 1rem auto;
    }
    .usuario-modal .modal-content {
        border: 0;
        background: transparent;
        max-height: calc(100vh - 2rem);
    }
    .usuario-modal-panel {
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - 2rem);
        overflow: hidden;
        background:
            linear-gradient(180deg, color-mix(in srgb, var(--ab-card) 98%, #ffffff 2%) 0%, color-mix(in srgb, var(--ab-soft-bg) 34%, var(--ab-card) 66%) 100%);
    }
    .usuario-modal-header {
        padding: 1.2rem 1.25rem;
        border-bottom: 1px solid color-mix(in srgb, var(--ab-border) 76%, transparent);
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: flex-start;
    }
    .usuario-modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 1.15rem 1.25rem 1.25rem;
        -webkit-overflow-scrolling: touch;
    }
    .usuario-modal-summary {
        display: grid;
        grid-template-columns: minmax(0, 1.1fr) minmax(260px, 0.9fr);
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .usuario-modal-identity,
    .usuario-modal-insights {
        border: 1px solid color-mix(in srgb, var(--ab-border) 80%, transparent);
        border-radius: 18px;
        padding: 1rem;
        background: color-mix(in srgb, var(--ab-card) 90%, var(--ab-soft-bg) 10%);
    }
    .usuario-modal-insights-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
        margin-top: 0.8rem;
    }
    .usuario-modal-insights-grid .usuarios-metric-chip {
        flex: initial;
    }
    .usuario-modal .modal-footer {
        flex: 0 0 auto;
        position: sticky;
        bottom: 0;
        border-top: 1px solid color-mix(in srgb, var(--ab-border) 76%, transparent);
        padding: 1rem 1.25rem 1.2rem;
        background: color-mix(in srgb, var(--ab-card) 94%, var(--ab-soft-bg) 6%);
        z-index: 2;
    }
    .usuario-modal .btn-icon.btn-outline-danger {
        color: #dc2626;
        border-color: color-mix(in srgb, #dc2626 28%, var(--ab-border) 72%);
        background: color-mix(in srgb, #dc2626 5%, var(--ab-card) 95%);
    }
    .usuario-modal .btn-icon.btn-outline-danger:hover,
    .usuario-modal .btn-icon.btn-outline-danger:focus {
        color: #fff;
        background: #dc2626;
        border-color: #dc2626;
    }
    @media (max-width: 991.98px) {
        .usuarios-stat-grid,
        .usuarios-card-grid,
        .usuario-modal-summary {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 767.98px) {
        .usuarios-layout-card {
            border-radius: 18px;
        }
        .usuarios-header {
            flex-direction: column;
        }
        .usuarios-content-card {
            padding: 0.9rem;
        }
        .usuarios-section-switch,
        .usuarios-status-tabs {
            width: 100%;
        }
        .usuarios-section-switch .nav-link,
        .usuarios-status-tabs .nav-link {
            flex: 1 1 0;
            text-align: center;
        }
        .usuarios-panel-card,
        .usuarios-guide-card,
        .usuarios-manage-card,
        .usuario-modal-panel {
            border-radius: 18px;
        }
        .usuarios-toolbar,
        .usuarios-base-card,
        .usuarios-guide-card {
            padding: 0.85rem;
        }
        .usuarios-stat-card {
            padding: 0.9rem;
        }
        .usuarios-stat-value {
            font-size: 1.4rem;
        }
        .usuarios-card-top,
        .usuarios-card-footer {
            flex-direction: column;
            align-items: flex-start;
        }
        .usuarios-card-tags,
        .usuarios-card-metrics {
            width: 100%;
        }
        .usuario-modal-header {
            align-items: center;
        }
        .usuario-modal-header > div {
            min-width: 0;
            flex: 1 1 auto;
        }
        .usuario-modal-header,
        .usuario-modal-body,
        .usuario-modal .modal-footer {
            padding-left: 0.9rem;
            padding-right: 0.9rem;
        }
        .usuario-modal-header .btn-icon {
            width: 42px;
            min-width: 42px;
            height: 42px;
            border-radius: 14px;
            padding: 0;
            flex: 0 0 auto;
        }
        .usuario-modal .modal-content,
        .usuario-modal-panel {
            max-height: calc(100vh - 1rem);
        }
        .usuario-modal .modal-dialog {
            margin: 0.5rem;
        }
        .usuario-modal .modal-footer {
            flex-direction: column-reverse;
            align-items: stretch;
        }
        .usuario-modal .modal-footer > .d-flex {
            width: 100%;
        }
        .usuario-modal .modal-footer > .d-flex:last-child {
            flex-direction: column;
        }
        .usuario-modal .modal-footer > .d-flex:last-child .btn,
        .usuario-modal .modal-footer > .d-flex:first-child .btn {
            width: 100%;
        }
        .usuario-modal .modal-footer > .d-flex:first-child {
            justify-content: stretch;
        }
        .usuario-modal .tag-grid {
            flex-direction: column;
            gap: 0.55rem;
        }
        .usuario-modal .tag-choice label {
            width: 100%;
            justify-content: center;
            padding: 0.65rem 0.8rem;
        }
        .usuario-modal-insights-grid {
            grid-template-columns: 1fr;
        }
        .usuarios-pagination {
            justify-content: center !important;
        }
        .usuarios-pagination .usuarios-page-info {
            width: 100%;
            text-align: center;
        }
    }
</style>

<div class="usuarios-page">
    <div class="card card-soft usuarios-layout-card p-4">
        <div class="usuarios-header">
            <div class="usuarios-header-copy">
                <div class="text-uppercase text-muted small">Cadastros e acessos</div>
                <h3 class="fw-bold mb-1">Usuários e permissões</h3>
                <p class="text-muted mb-0">Separe o cadastro da gestão diária. Crie novos acessos em um fluxo mais claro e mantenha a base existente organizada em cards com edição por popup.</p>
            </div>
            <ul class="nav usuarios-section-switch" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $secaoAtual === 'cadastro' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#usuarios-cadastro-pane" type="button" role="tab">Cadastro de usuários</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $secaoAtual === 'base' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#usuarios-base-pane" type="button" role="tab">Usuários existentes</button>
                </li>
            </ul>
        </div>
        <div class="usuarios-stat-grid">
            <?php foreach ($statsCards as $stat): ?>
                <div class="usuarios-stat-card" data-tone="<?= h($stat['tone']) ?>">
                    <div class="usuarios-stat-value"><?= (int)$stat['value'] ?></div>
                    <div class="usuarios-stat-label"><?= h($stat['label']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="usuarios-content-card tab-content">
            <div class="tab-pane fade <?= $secaoAtual === 'cadastro' ? 'show active' : '' ?>" id="usuarios-cadastro-pane" role="tabpanel">
                <div class="row g-4">
                    <div class="col-12 col-xl-7">
                        <div class="card usuarios-panel-card p-4">
                            <div class="usuarios-panel-head">
                                <div>
                                    <div class="text-uppercase text-muted small">Novo acesso</div>
                                    <h4 class="fw-bold mb-1">Cadastro de usuário</h4>
                                    <div class="text-muted">Preencha identidade, credenciais e escopo operacional em um único fluxo.</div>
                                </div>
                                <span class="badge badge-soft">Ambiente interno</span>
                            </div>
                            <form method="post" action="/?r=usuarios/create">
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Nome completo</label>
                                        <input type="text" name="nome" class="form-control input-xl" placeholder="Ex.: Amanda Silva" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">E-mail de acesso</label>
                                        <input type="email" name="email" class="form-control input-xl" placeholder="usuario@empresa.com" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Senha inicial</label>
                                        <input type="password" name="senha" class="form-control input-xl" placeholder="Defina a senha inicial" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Perfil</label>
                                        <select name="perfil" class="form-select input-xl">
                                            <option value="hostess">Hostess</option>
                                            <?php if (!$canManagePrivilegedProfiles): ?>
                                                <option value="supervisor">Supervisor</option>
                                            <?php endif; ?>
                                            <?php if ($canManagePrivilegedProfiles): ?>
                                                <option value="gerente">Gerente</option>
                                                <option value="supervisor">Supervisor</option>
                                                <option value="admin">Admin</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <div class="usuarios-assignment-frame">
                                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                                <div>
                                                    <div class="fw-bold">Restaurantes e operações</div>
                                                    <div class="text-muted small">Use este bloco para limitar o acesso a contextos específicos de operação.</div>
                                                </div>
                                                <span class="badge badge-soft">Escopo operacional</span>
                                            </div>
                                            <div class="tag-grid">
                                                <?php $renderAssignmentChoices('novo_assign'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end">
                                        <button class="btn btn-success btn-xl px-4">Cadastrar usuário</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-12 col-xl-5">
                        <div class="usuarios-guide-card">
                            <div class="text-uppercase text-muted small">Boas práticas</div>
                            <h5 class="fw-bold mb-1">Cadastro mais limpo e consistente</h5>
                            <div class="text-muted small">Deixe esta área focada em criação. Ajustes finos, reativação e manutenção diária ficam na aba da base existente.</div>
                            <ul>
                                <li>Cadastre o perfil pensando no nível real de autonomia da pessoa.</li>
                                <li>Use o escopo operacional quando a atuação for restrita a restaurantes ou operações específicas.</li>
                                <li>Evite reaproveitar senhas iguais para pessoas com o mesmo e-mail compartilhado.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade <?= $secaoAtual === 'base' ? 'show active' : '' ?>" id="usuarios-base-pane" role="tabpanel">
                <div class="card usuarios-panel-card usuarios-base-card">
                    <div class="usuarios-panel-head">
                        <div>
                            <div class="text-uppercase text-muted small">Base operacional</div>
                            <h4 class="fw-bold mb-1">Usuários existentes</h4>
                            <div class="text-muted">Pesquise, filtre e abra cada usuário em popup para editar dados, permissões e status sem poluir a listagem.</div>
                        </div>
                        <ul class="nav usuarios-status-tabs" role="tablist">
                            <?php foreach ($userTabs as $tabKey => $tab): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $tabAtual === $tabKey ? 'active' : '' ?>" data-bs-toggle="tab" data-role="user-status-tab" data-bs-target="#usuarios-<?= h($tabKey) ?>" type="button" role="tab">
                                        <?= h($tab['label']) ?> <span class="badge badge-soft ms-1"><?= count($tab['items']) ?></span>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>


                    <div class="usuarios-toolbar row g-2 align-items-end">
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted mb-1">Buscar usuário</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="search" class="form-control" id="usuariosSearch" placeholder="Nome, e-mail ou perfil">
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small text-muted mb-1">Ordenar</label>
                            <select class="form-select" id="usuariosSort">
                                <option value="nome">Nome A-Z</option>
                                <option value="perfil">Perfil</option>
                                <option value="email">E-mail</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small text-muted mb-1">Por página</label>
                            <select class="form-select" id="usuariosPerPage">
                                <option value="4">4</option>
                                <option value="6">6</option>
                                <option value="8" selected>8</option>
                                <option value="12">12</option>
                            </select>
                        </div>
                    </div>

                    <div class="tab-content mt-3">
                        <?php foreach ($userTabs as $tabKey => $tab): ?>
                            <div class="tab-pane fade <?= $tabAtual === $tabKey ? 'show active' : '' ?>" id="usuarios-<?= h($tabKey) ?>" role="tabpanel">
                                <div class="usuarios-card-grid js-usuarios-list" data-tab="<?= h($tabKey) ?>">
                                    <?php foreach ($tab['items'] as $item): ?>
                                        <?php
                                        $uid = (int)$item['id'];
                                        $summary = $assignmentSummary($uid);
                                        $perfilLabel = ucfirst((string)($item['perfil'] ?? ''));
                                        $isRemovedUser =
                                            (strpos((string)($item['email'] ?? ''), '@anon.local') !== false) ||
                                            (stripos((string)($item['nome'] ?? ''), 'removido') !== false);
                                        $nomeBusca = (string)($item['nome'] ?? '');
                                        $emailBusca = (string)($item['email'] ?? '');
                                        $perfilBusca = (string)($item['perfil'] ?? '');
                                        $hasSecondName = $nomeBusca !== '' && strpos($nomeBusca, ' ') !== false;
                                        $initials = trim(mb_strtoupper(mb_substr($nomeBusca, 0, 1, 'UTF-8') . ($hasSecondName ? mb_substr(trim(substr($nomeBusca, (int)strrpos($nomeBusca, ' '))), 0, 1, 'UTF-8') : ''), 'UTF-8'));
                                        ?>
                                        <button
                                            type="button"
                                            class="usuarios-manage-card js-usuario-card"
                                            data-bs-toggle="modal"
                                            data-bs-target="#usuarioModal-<?= $uid ?>"
                                            data-user-name="<?= h($nomeBusca) ?>"
                                            data-user-email="<?= h($emailBusca) ?>"
                                            data-user-profile="<?= h($perfilBusca) ?>"
                                            data-user-text="<?= h($nomeBusca . ' ' . $emailBusca . ' ' . $perfilBusca) ?>"
                                        >
                                            <div class="usuarios-card-top">
                                                <div class="d-flex gap-3">
                                                    <div class="usuarios-avatar"><?= h($initials !== '' ? $initials : 'U') ?></div>
                                                    <div class="min-w-0">
                                                        <div class="usuarios-card-name"><?= h($item['nome']) ?></div>
                                                        <div class="usuarios-card-email"><?= h($item['email']) ?></div>
                                                    </div>
                                                </div>
                                                <span class="badge <?= (int)($item['ativo'] ?? 0) === 1 ? 'badge-success' : 'badge-danger' ?>">
                                                    <?= (int)($item['ativo'] ?? 0) === 1 ? 'Ativo' : 'Desativado' ?>
                                                </span>
                                            </div>
                                            <div class="usuarios-card-tags">
                                                <span class="badge badge-soft"><?= h($perfilLabel) ?></span>
                                                <?php if ($isRemovedUser): ?>
                                                    <span class="badge badge-danger">Registro legado anonimizado</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="usuarios-card-metrics">
                                                <div class="usuarios-metric-chip">
                                                    <strong><?= (int)$summary['restaurants'] ?></strong>
                                                    <span>Restaurantes</span>
                                                </div>
                                                <div class="usuarios-metric-chip">
                                                    <strong><?= (int)$summary['operations'] ?></strong>
                                                    <span>Operações</span>
                                                </div>
                                            </div>
                                            <div class="usuarios-card-footer">
                                                <span class="text-muted small">Toque para editar permissões, senha e status.</span>
                                                <span class="usuarios-card-action">Gerenciar <i class="bi bi-arrow-up-right"></i></span>
                                            </div>
                                        </button>
                                    <?php endforeach; ?>
                                    <?php if (empty($tab['items'])): ?>
                                        <div class="usuarios-page-empty">Nenhum usuário encontrado nesta aba.</div>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($tab['items'])): ?>
                                    <div class="usuarios-page-empty js-usuarios-filter-empty d-none" data-tab="<?= h($tabKey) ?>">
                                        Nenhum usuário corresponde aos filtros atuais.
                                    </div>
                                <?php endif; ?>
                                <div class="usuarios-pagination js-usuarios-pagination d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3" data-tab="<?= h($tabKey) ?>"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php foreach ($allUsers as $item): ?>
    <?php
    $uid = (int)$item['id'];
    $perfilLabel = ucfirst((string)($item['perfil'] ?? ''));
    $summary = $assignmentSummary($uid);
    $isRemovedUser =
        (strpos((string)($item['email'] ?? ''), '@anon.local') !== false) ||
        (stripos((string)($item['nome'] ?? ''), 'removido') !== false);
    ?>
    <div class="modal fade usuario-modal" id="usuarioModal-<?= $uid ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form method="post" action="/?r=usuarios/edit" class="usuario-modal-panel">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= $uid ?>">
                    <div class="usuario-modal-header">
                        <div>
                            <div class="text-uppercase text-muted small">Gestão de usuário</div>
                            <h4 class="fw-bold mb-1"><?= h($item['nome']) ?></h4>
                            <div class="text-muted"><?= h($item['email']) ?> · <?= h($perfilLabel) ?></div>
                        </div>
                        <button type="button" class="btn btn-icon btn-outline-danger" data-bs-dismiss="modal" aria-label="Fechar">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="usuario-modal-body">
                        <div class="usuario-modal-summary">
                            <div class="usuario-modal-identity">
                                <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                                    <span class="badge <?= (int)($item['ativo'] ?? 0) === 1 ? 'badge-success' : 'badge-danger' ?>">
                                        <?= (int)($item['ativo'] ?? 0) === 1 ? 'Ativo' : 'Desativado' ?>
                                    </span>
                                    <span class="badge badge-soft"><?= h($perfilLabel) ?></span>
                                    <?php if ($isRemovedUser): ?>
                                        <span class="badge badge-danger">Registro legado anonimizado</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted small">Altere dados de acesso, perfil e escopo operacional sem misturar a edição com a listagem principal.</div>
                            </div>
                            <div class="usuario-modal-insights">
                                <div class="text-uppercase text-muted small">Escopo atual</div>
                                <div class="usuario-modal-insights-grid">
                                    <div class="usuarios-metric-chip">
                                        <strong><?= (int)$summary['restaurants'] ?></strong>
                                        <span>Restaurantes</span>
                                    </div>
                                    <div class="usuarios-metric-chip">
                                        <strong><?= (int)$summary['operations'] ?></strong>
                                        <span>Operações</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Nome</label>
                                <input type="text" name="nome" class="form-control input-xl" value="<?= h($item['nome']) ?>" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">E-mail</label>
                                <input type="email" name="email" class="form-control input-xl" value="<?= h($item['email']) ?>" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Perfil</label>
                                <select name="perfil" class="form-select input-xl">
                                    <option value="hostess" <?= $item['perfil'] === 'hostess' ? 'selected' : '' ?>>Hostess</option>
                                    <?php if (!$canManagePrivilegedProfiles): ?>
                                        <option value="supervisor" <?= $item['perfil'] === 'supervisor' ? 'selected' : '' ?>>Supervisor</option>
                                    <?php endif; ?>
                                    <?php if ($canManagePrivilegedProfiles): ?>
                                        <option value="gerente" <?= $item['perfil'] === 'gerente' ? 'selected' : '' ?>>Gerente</option>
                                        <option value="supervisor" <?= $item['perfil'] === 'supervisor' ? 'selected' : '' ?>>Supervisor</option>
                                        <option value="admin" <?= $item['perfil'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Status</label>
                                <select name="ativo" class="form-select input-xl">
                                    <option value="1" <?= !empty($item['ativo']) ? 'selected' : '' ?>>Ativo</option>
                                    <option value="0" <?= empty($item['ativo']) ? 'selected' : '' ?>>Desativado</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Nova senha</label>
                                <input type="password" name="senha" class="form-control input-xl" placeholder="Preencha apenas se for trocar">
                            </div>
                            <div class="col-12">
                                <div class="usuarios-assignment-frame">
                                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                        <div>
                                            <div class="fw-bold">Restaurantes e operações</div>
                                            <div class="text-muted small">Atualize aqui o que esta pessoa consegue acessar.</div>
                                        </div>
                                        <span class="badge badge-soft">Escopo operacional</span>
                                    </div>
                                    <div class="tag-grid">
                                        <?php $renderAssignmentChoices('edit_' . $uid . '_assign', $uid); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex flex-wrap justify-content-between gap-2">
                        <div class="d-flex flex-wrap gap-2">
                            <?php if ($uid !== $viewerId): ?>
                                <button
                                    class="btn btn-outline-danger"
                                    type="submit"
                                    formaction="/?r=usuarios/delete"
                                    data-confirm="Desativar usuário? O acesso será bloqueado, mas nome e histórico serão preservados para auditoria."
                                    data-confirm-title="Desativar usuário"
                                    data-confirm-type="danger"
                                >
                                    Desativar
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                            <button class="btn btn-primary px-4">Salvar alterações</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('usuariosSearch');
    const sort = document.getElementById('usuariosSort');
    const perPage = document.getElementById('usuariosPerPage');
    const state = {};

    const normalize = function (value) {
        return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    };

    const activeStatusTabKey = function () {
        const activePane = document.querySelector('#usuarios-base-pane .tab-pane.active[id^="usuarios-"]');
        return activePane ? activePane.id.replace('usuarios-', '') : 'ativos';
    };

    const render = function (tabKey, page) {
        const list = document.querySelector('.js-usuarios-list[data-tab="' + tabKey + '"]');
        const pager = document.querySelector('.js-usuarios-pagination[data-tab="' + tabKey + '"]');
        const emptyState = document.querySelector('.js-usuarios-filter-empty[data-tab="' + tabKey + '"]');
        if (!list || !pager) {
            return;
        }

        const cards = Array.from(list.querySelectorAll('.js-usuario-card'));
        const term = normalize(search ? search.value : '');
        const sortBy = sort ? sort.value : 'nome';
        const pageSize = Math.max(1, parseInt(perPage ? perPage.value : '8', 10) || 8);

        cards.sort(function (a, b) {
            const key = sortBy === 'email' ? 'userEmail' : (sortBy === 'perfil' ? 'userProfile' : 'userName');
            return normalize(a.dataset[key]).localeCompare(normalize(b.dataset[key]), 'pt-BR');
        }).forEach(function (card) {
            list.appendChild(card);
        });

        const filtered = cards.filter(function (card) {
            return !term || normalize(card.dataset.userText).includes(term);
        });
        const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
        const currentPage = Math.min(Math.max(1, page || state[tabKey] || 1), totalPages);
        state[tabKey] = currentPage;

        cards.forEach(function (card) {
            card.classList.add('d-none');
        });
        filtered.slice((currentPage - 1) * pageSize, currentPage * pageSize).forEach(function (card) {
            card.classList.remove('d-none');
        });

        if (emptyState) {
            emptyState.classList.toggle('d-none', filtered.length !== 0);
        }

        if (!cards.length) {
            pager.innerHTML = '';
            return;
        }

        const start = filtered.length ? ((currentPage - 1) * pageSize + 1) : 0;
        const end = Math.min(currentPage * pageSize, filtered.length);
        let buttons = '';
        for (let i = 1; i <= totalPages; i++) {
            if (totalPages > 7 && i !== 1 && i !== totalPages && Math.abs(i - currentPage) > 1) {
                if (i === 2 || i === totalPages - 1) {
                    buttons += '<span class="btn btn-sm btn-outline-secondary disabled">...</span>';
                }
                continue;
            }
            buttons += '<button type="button" class="btn btn-sm ' + (i === currentPage ? 'btn-primary active' : 'btn-outline-primary') + '" data-page="' + i + '">' + i + '</button>';
        }

        pager.innerHTML =
            '<span class="usuarios-page-info text-muted small">' + start + '-' + end + ' de ' + filtered.length + ' usuários</span>' +
            '<div class="btn-group flex-wrap" role="group">' + buttons + '</div>';

        pager.querySelectorAll('[data-page]').forEach(function (button) {
            button.addEventListener('click', function () {
                render(tabKey, parseInt(button.dataset.page, 10));
            });
        });
    };

    const renderActive = function () {
        const key = activeStatusTabKey();
        state[key] = 1;
        render(key, 1);
    };

    document.querySelectorAll('[data-role="user-status-tab"]').forEach(function (tab) {
        tab.addEventListener('shown.bs.tab', function () {
            render(activeStatusTabKey());
        });
        tab.addEventListener('click', function () {
            window.setTimeout(function () { render(activeStatusTabKey()); }, 80);
        });
    });

    document.querySelectorAll('#usuarios-cadastro-pane [data-bs-toggle="tab"], #usuarios-base-pane [data-bs-toggle="tab"]').forEach(function (tab) {
        tab.addEventListener('shown.bs.tab', function () {
            if (document.getElementById('usuarios-base-pane').classList.contains('active')) {
                render(activeStatusTabKey());
            }
        });
    });

    [search, sort, perPage].forEach(function (input) {
        if (input) {
            input.addEventListener('input', renderActive);
            input.addEventListener('change', renderActive);
        }
    });

    document.querySelectorAll('.js-usuarios-list').forEach(function (list) {
        render(list.dataset.tab);
    });
});
</script>
