<div class="topbar">
    <div class="topbar-title">
        <div class="topbar-eyebrow">Gestão Inteligente de A&amp;B</div>
        <div class="topbar-product"><?= h($appName) ?> <span>v<?= h($appVersion) ?></span></div>
    </div>
    <div class="d-flex align-items-center gap-3 topbar-actions">
        <div class="theme-chip js-theme-label topbar-theme">
            <i class="bi bi-palette2"></i>
            Tema
        </div>
        <div class="theme-switch topbar-theme" role="group" aria-label="Selecionar tema">
            <?php require __DIR__ . '/theme_switch_buttons.php'; ?>
        </div>
        <?php if ($showGuidedTutorial): ?>
            <button class="btn btn-outline-primary btn-sm js-open-tour topbar-theme" type="button">
                <i class="bi bi-question-circle me-1"></i>
                Guia
            </button>
        <?php endif; ?>
        <?php if (($user['perfil'] ?? '') === 'admin'): ?>
            <form method="post" action="/?r=demo/toggle" class="d-flex align-items-center">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="return_to" value="<?= h(sanitize_local_redirect_path((string)($_SERVER['REQUEST_URI'] ?? '/?r=home'))) ?>">
                <input type="hidden" name="demo_mode" value="<?= app_demo_mode_enabled() ? '0' : '1' ?>">
                <button class="btn <?= app_demo_mode_enabled() ? 'btn-warning' : 'btn-outline-secondary' ?> btn-sm topbar-theme" type="submit" title="Ignora validações de horário nesta sessão admin para treinamento">
                    <i class="bi bi-mortarboard"></i>
                    <?= app_demo_mode_enabled() ? 'Demo ON' : 'Demo' ?>
                </button>
            </form>
        <?php endif; ?>
        <form method="post" action="/?r=auth/logout" class="logout-inline-form">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <button class="btn btn-outline-dark btn-sm topbar-logout" type="submit">
                <i class="bi bi-box-arrow-right me-1"></i>
                Sair
            </button>
        </form>
        <?php if ($activeShift): ?>
            <div class="turno-pill topbar-runtime">
                <i class="bi bi-clock-history"></i>
                Turno: <?= h($activeShift['restaurante']) ?> - <?= h($activeShift['operacao']) ?>
            </div>
            <div class="stat-chip topbar-runtime"><i class="bi bi-shop-window"></i><?= h($activeShift['restaurante']) ?></div>
            <div class="stat-chip topbar-runtime"><i class="bi bi-collection"></i><?= h($activeShift['operacao']) ?></div>
            <?php if (!empty($activeShift['porta'])): ?>
                <div class="stat-chip topbar-runtime"><i class="bi bi-door-open"></i><?= h($activeShift['porta']) ?></div>
            <?php endif; ?>
        <?php endif; ?>
        <div class="user-pill desktop-only">
            <i class="bi bi-person-circle"></i>
            <div>
                <div class="fw-semibold"><?= h($user['nome']) ?></div>
                <div class="text-muted small"><?= h($user['perfil']) ?></div>
            </div>
        </div>
    </div>
</div>
