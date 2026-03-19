<?php $flash = $this->data['flash'] ?? null; ?>
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-5">
        <div class="card p-4">
            <div class="d-flex align-items-center gap-2 text-uppercase text-muted small mb-1">
                <i class="bi bi-shield-lock"></i>
                Acesso seguro
            </div>
            <h2 class="brand-title fw-bold mb-2">OCA FBControl</h2>
            <p class="text-muted">Acesse com suas credenciais para continuar.</p>

            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
            <?php endif; ?>

            <form method="post" action="/?r=auth/login">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <div class="mb-3">
                    <label class="form-label">E-mail</label>
                    <input type="email" name="email" class="form-control input-xl" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Senha</label>
                    <input type="password" name="senha" class="form-control input-xl" required>
                </div>
                <button type="submit" class="btn btn-primary btn-xl w-100">Entrar</button>
            </form>
        </div>
    </div>
</div>
