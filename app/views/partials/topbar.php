<div class="topbar">
    <div>
            <div class="text-muted small">Gestao operacional para hotelaria</div>
        <div class="h5 mb-0"><?= h($appName) ?> <span class="text-muted small">v<?= h($appVersion) ?></span></div>
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
