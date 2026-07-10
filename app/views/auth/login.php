<?php
$flash = $this->data['flash'] ?? null;
$horaLogin = (int)date('G');
$saudacaoLogin = $horaLogin < 12 ? 'Bom dia' : ($horaLogin < 18 ? 'Boa tarde' : 'Boa noite');
?>

<div class="fb-auth-shell">
    <section class="fb-auth-context" aria-labelledby="authContextTitle">
        <div class="fb-auth-brand">
            <?php if (!empty($logoPath)): ?>
                <img
                    src="<?= h($logoPath) ?>?v=20260705g"
                    data-logo-light="<?= h($logoPath) ?>?v=20260705g"
                    data-logo-dark="/assets/logo-fbcontrol-dark.svg?v=20260705g"
                    alt="FBControl"
                    class="fb-auth-logo js-theme-logo"
                >
            <?php else: ?>
                <span class="fb-auth-wordmark">FB<span>Control</span></span>
            <?php endif; ?>
            <span class="fb-auth-version">v<?= h((string)$appVersion) ?></span>
        </div>

        <div class="fb-auth-context__copy">
            <span class="fb-eyebrow"><i class="bi bi-activity"></i> Operação inteligente de A&amp;B</span>
            <h1 id="authContextTitle">Informação clara para uma operação mais segura.</h1>
            <p>Registros, reservas temáticas e decisões gerenciais reunidos em um ambiente rastreável.</p>
        </div>

        <div class="fb-auth-signals" aria-label="Recursos da plataforma">
            <div class="fb-auth-signal">
                <i class="bi bi-lightning-charge"></i>
                <span><strong>Operação ágil</strong>Fluxos objetivos para a rotina.</span>
            </div>
            <div class="fb-auth-signal">
                <i class="bi bi-shield-check"></i>
                <span><strong>Rastreabilidade</strong>Ações vinculadas ao usuário.</span>
            </div>
            <div class="fb-auth-signal">
                <i class="bi bi-bar-chart-line"></i>
                <span><strong>Leitura gerencial</strong>Dados prontos para decisão.</span>
            </div>
        </div>
    </section>

    <section class="fb-auth-panel" aria-labelledby="authTitle">
        <header class="fb-auth-panel__head">
            <span class="fb-auth-panel__icon"><i class="bi bi-person-lock"></i></span>
            <div>
                <span class="fb-eyebrow">Acesso seguro</span>
                <h2 id="authTitle"><?= h($saudacaoLogin) ?>.</h2>
                <p>Use suas credenciais individuais para continuar.</p>
            </div>
        </header>

        <form method="post" action="/?r=auth/login" autocomplete="on" class="fb-auth-form">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

            <label class="fb-field">
                <span class="fb-field__label">E-mail</span>
                <span class="fb-field__control">
                    <i class="bi bi-envelope"></i>
                    <input type="email" name="email" class="form-control" required autocomplete="username" placeholder="seu.email@empresa.com.br" autofocus>
                </span>
            </label>

            <label class="fb-field">
                <span class="fb-field__label">Senha</span>
                <span class="fb-field__control fb-field__control--password">
                    <i class="bi bi-lock"></i>
                    <input type="password" id="loginSenha" name="senha" class="form-control" required autocomplete="current-password" placeholder="Digite sua senha">
                    <button class="fb-auth-pass-toggle js-toggle-pass" type="button" aria-label="Mostrar senha" aria-controls="loginSenha">
                        <i class="bi bi-eye"></i>
                    </button>
                </span>
            </label>

            <button type="submit" class="btn btn-primary fb-auth-submit">
                <span>Entrar no FBControl</span>
                <i class="bi bi-arrow-right"></i>
            </button>
        </form>

        <footer class="fb-auth-panel__foot">
            <i class="bi bi-shield-lock"></i>
            <span>Acesso restrito à equipe autorizada. <a href="/?r=privacidade/index">Aviso de privacidade</a></span>
        </footer>
    </section>
</div>

<script>
    (function () {
        const button = document.querySelector('.js-toggle-pass');
        const input = document.getElementById('loginSenha');
        if (!button || !input) return;

        button.addEventListener('click', function () {
            const isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';
            button.innerHTML = isVisible ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
            button.setAttribute('aria-label', isVisible ? 'Mostrar senha' : 'Ocultar senha');
            input.focus();
        });
    })();
</script>
