<?php
$flash = $this->data['flash'] ?? null;
?>
<style>
    .auth-login-shell {
        width: min(1180px, 100%);
        margin-inline: auto;
        min-height: calc(100dvh - 158px);
        display: grid;
        align-items: center;
        padding: 0.35rem 0 0.55rem;
    }
    body[data-route="auth/login"] .app-main {
        padding-top: 14px;
        padding-bottom: 10px;
    }
    body[data-route="auth/login"] .app-content {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: calc(100dvh - 178px);
    }
    body[data-route="auth/login"] footer {
        margin-top: 0.5rem !important;
    }
    .auth-login-grid {
        display: grid;
        grid-template-columns: 1.08fr 0.92fr;
        gap: 1.2rem;
        align-items: stretch;
    }
    .auth-showcase {
        position: relative;
        border-radius: 26px;
        border: 1px solid color-mix(in srgb, var(--ab-border) 82%, transparent);
        padding: clamp(1.3rem, 1rem + 1vw, 2rem);
        background:
            radial-gradient(circle at 82% 15%, rgba(251, 146, 60, 0.24), transparent 46%),
            radial-gradient(circle at 12% 88%, rgba(14, 165, 233, 0.16), transparent 52%),
            color-mix(in srgb, var(--ab-card) 92%, #ffffff 8%);
        box-shadow: 0 24px 54px rgba(15, 23, 42, 0.12);
        overflow: hidden;
    }
    html[data-theme='dark'] .auth-showcase {
        background:
            radial-gradient(circle at 82% 15%, rgba(251, 146, 60, 0.18), transparent 50%),
            radial-gradient(circle at 12% 88%, rgba(14, 165, 233, 0.12), transparent 55%),
            color-mix(in srgb, var(--ab-card) 94%, #101927 6%);
    }
    .auth-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        border-radius: 999px;
        padding: 0.4rem 0.8rem;
        font-size: 0.75rem;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        font-weight: 700;
        background: color-mix(in srgb, var(--ab-accent) 12%, transparent);
        color: #b45309;
        border: 1px solid color-mix(in srgb, var(--ab-accent) 42%, transparent);
    }
    html[data-theme='dark'] .auth-chip {
        color: #fdba74;
    }
    .auth-title {
        margin: 1rem 0 0.75rem;
        font-family: "Space Grotesk", sans-serif;
        letter-spacing: -0.02em;
        font-size: clamp(1.65rem, 1.3rem + 1vw, 2.3rem);
        line-height: 1.15;
    }
    .auth-subtitle {
        color: var(--ab-muted);
        max-width: 48ch;
        margin-bottom: 1.1rem;
    }
    .auth-points {
        display: grid;
        gap: 0.6rem;
        margin-top: 1rem;
    }
    .auth-point {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        color: var(--ab-ink);
        font-weight: 560;
    }
    .auth-point i {
        width: 26px;
        height: 26px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: color-mix(in srgb, var(--ab-accent) 12%, transparent);
        color: color-mix(in srgb, var(--ab-accent) 70%, #111827 30%);
    }

    .auth-panel {
        border-radius: 26px;
        border: 1px solid color-mix(in srgb, var(--ab-border) 82%, transparent);
        background: color-mix(in srgb, var(--ab-card) 95%, #ffffff 5%);
        box-shadow: 0 24px 52px rgba(15, 23, 42, 0.12);
        padding: clamp(1rem, 0.75rem + 0.8vw, 1.7rem);
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .auth-kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        font-weight: 700;
        color: var(--ab-muted);
    }
    .auth-panel h2 {
        margin: 0.6rem 0 0.45rem;
        font-family: "Space Grotesk", sans-serif;
        font-size: clamp(1.35rem, 1.15rem + 0.75vw, 1.8rem);
        letter-spacing: -0.02em;
    }
    .auth-panel p {
        color: var(--ab-muted);
        margin-bottom: 1rem;
    }
    .auth-input-wrap {
        position: relative;
    }
    .auth-input-wrap .input-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--ab-muted);
        pointer-events: none;
        width: 1rem;
        text-align: center;
        z-index: 2;
    }
    .auth-input-wrap .form-control {
        min-height: 50px;
        padding-left: 2.75rem !important;
        padding-right: 0.95rem;
        border-radius: 12px;
        font-weight: 580;
        position: relative;
        z-index: 1;
    }
    .auth-input-wrap.has-toggle .form-control {
        padding-right: 2.8rem !important;
    }
    .auth-pass-toggle {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        border: 0;
        background: transparent;
        color: var(--ab-muted);
        width: 34px;
        height: 34px;
        border-radius: 10px;
    }
    .auth-pass-toggle:hover {
        background: color-mix(in srgb, var(--ab-hover-bg) 58%, transparent);
        color: var(--ab-ink);
    }
    .auth-submit {
        min-height: 50px;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 700;
    }
    .auth-footnote {
        margin-top: 0.9rem;
        font-size: 0.78rem;
        color: var(--ab-muted);
        text-align: center;
    }
    @media (max-width: 1100px) {
        .auth-login-grid {
            grid-template-columns: 1fr;
            gap: 0.9rem;
        }
    }
    @media (max-width: 576px) {
        .auth-login-shell {
            min-height: auto;
            padding-top: 0.2rem;
            padding-bottom: 0.2rem;
        }
        .auth-showcase,
        .auth-panel {
            border-radius: 18px;
            padding: 1rem;
        }
        body[data-route="auth/login"] .app-content {
            min-height: auto;
        }
    }
</style>

<div class="auth-login-shell">
    <div class="auth-login-grid">
        <section class="auth-showcase">
            <span class="auth-chip">
                <i class="bi bi-stars"></i>
                Plataforma Operacional A&B
            </span>
            <h1 class="auth-title">OCA FBControl<br>Controle inteligente para restaurantes e áreas especiais.</h1>
            <p class="auth-subtitle">
                Centralize registros, turnos, reservas temáticas e auditoria em uma experiência rápida, segura e preparada para escala.
            </p>
            <div class="auth-points">
                <div class="auth-point"><i class="bi bi-lightning-charge"></i> Registro de acesso em segundos</div>
                <div class="auth-point"><i class="bi bi-shield-check"></i> Rastreabilidade completa por usuário e horário</div>
                <div class="auth-point"><i class="bi bi-graph-up-arrow"></i> Indicadores em tempo real para decisão gerencial</div>
            </div>
        </section>

        <section class="auth-panel">
            <div class="auth-kicker">
                <i class="bi bi-shield-lock"></i>
                Acesso seguro
            </div>
            <h2>Entrar na plataforma</h2>
            <p>Use suas credenciais para continuar.</p>

            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
            <?php endif; ?>

            <form method="post" action="/?r=auth/login" autocomplete="on">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

                <div class="mb-3">
                    <label class="form-label">E-mail</label>
                    <div class="auth-input-wrap">
                        <span class="input-icon"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control input-xl" required autocomplete="username">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Senha</label>
                    <div class="auth-input-wrap has-toggle">
                        <span class="input-icon"><i class="bi bi-lock"></i></span>
                        <input type="password" id="loginSenha" name="senha" class="form-control input-xl" required autocomplete="current-password">
                        <button class="auth-pass-toggle js-toggle-pass" type="button" aria-label="Mostrar senha">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary auth-submit w-100">
                    <i class="bi bi-box-arrow-in-right me-1"></i>
                    Entrar
                </button>
            </form>

            <div class="auth-footnote">
                Ambiente protegido para equipe autorizada. <a href="/?r=privacidade/index">Aviso de privacidade</a>
            </div>
        </section>
    </div>
</div>

<script>
    (function () {
        const btn = document.querySelector('.js-toggle-pass');
        const input = document.getElementById('loginSenha');
        if (!btn || !input) return;

        btn.addEventListener('click', () => {
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            btn.innerHTML = showing ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
            btn.setAttribute('aria-label', showing ? 'Mostrar senha' : 'Ocultar senha');
        });
    })();
</script>
