<?php
declare(strict_types=1);

$formatNumber = static fn($value): string => number_format((int)$value, 0, ',', '.');
$formatPercent = static function ($value): string {
    $number = (float)$value;
    return number_format($number, $number === floor($number) ? 0 : 1, ',', '.') . '%';
};
$formatDate = static fn($value): string => $value ? date('d/m/Y', strtotime((string)$value)) : '—';
$asset = static function (string $path): string {
    $path = ltrim($path, '/');
    $file = __DIR__ . '/../../../public/' . $path;
    return '/' . $path . (is_file($file) ? '?v=' . filemtime($file) : '');
};
$loginLabel = $isAuthenticated ? 'Voltar ao sistema' : 'Acesso administrativo';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Um agradecimento a todas as pessoas que fizeram parte da história do FBControl.">
    <meta name="theme-color" content="#123746">
    <title>FBControl · Obrigado</title>
    <link rel="icon" href="<?= h($asset('assets/favicon-fb-white.svg')) ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;family=Space+Grotesk:wght@600;700&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --ink: #172936;
            --muted: #667b86;
            --line: #dbe5e9;
            --surface: #ffffff;
            --soft: #f3f7f8;
            --navy: #123746;
            --orange: #c95d3f;
            --teal: #128c88;
            --cyan: #1d9ed1;
            --green: #21895d;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            background: var(--soft);
            color: var(--ink);
            font-family: "Inter", system-ui, sans-serif;
            letter-spacing: 0;
        }
        a { color: inherit; }
        button, a { -webkit-tap-highlight-color: transparent; }

        .page-shell {
            width: min(1180px, calc(100% - 40px));
            margin-inline: auto;
        }

        .topbar {
            min-height: 74px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            font-family: "Space Grotesk", sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
        }
        .brand img { width: 42px; height: 42px; border-radius: 8px; }
        .brand b { color: var(--orange); }
        .top-actions { display: flex; align-items: center; gap: 0.75rem; }
        .cycle-state {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: var(--green);
            font-size: 0.78rem;
            font-weight: 700;
        }
        .cycle-state::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--green);
        }
        .admin-link {
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.55rem 0.85rem;
            border: 1px solid #b9c9cf;
            border-radius: 8px;
            background: var(--surface);
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 700;
        }
        .admin-link:hover { border-color: var(--orange); color: var(--orange); }

        .hero-band {
            min-height: 590px;
            display: grid;
            align-content: center;
            padding: 5.5rem 0 4.5rem;
            border-top: 1px solid var(--line);
        }
        .eyebrow {
            margin: 0 0 1rem;
            color: var(--orange);
            font-size: 0.78rem;
            font-weight: 800;
            text-transform: uppercase;
        }
        h1, h2, h3 { font-family: "Space Grotesk", sans-serif; letter-spacing: 0; }
        h1 {
            margin: 0;
            max-width: 900px;
            font-size: 4.8rem;
            line-height: 0.98;
        }
        .hero-message {
            max-width: 740px;
            margin: 1.6rem 0 0;
            color: var(--muted);
            font-size: 1.2rem;
            line-height: 1.65;
        }
        .hero-message strong { color: var(--ink); }
        .hero-period {
            display: flex;
            flex-wrap: wrap;
            gap: 1.25rem;
            margin-top: 2rem;
            color: var(--muted);
            font-size: 0.9rem;
        }
        .hero-period span { display: inline-flex; align-items: center; gap: 0.5rem; }
        .hero-period i { color: var(--teal); }

        .hero-numbers {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-top: 3.5rem;
            border-block: 1px solid var(--line);
        }
        .hero-number { padding: 1.35rem 1.5rem 1.35rem 0; }
        .hero-number + .hero-number { border-left: 1px solid var(--line); padding-left: 1.5rem; }
        .hero-number strong { display: block; font-size: 1.75rem; }
        .hero-number span { color: var(--muted); font-size: 0.82rem; }

        .impact-band { background: var(--surface); border-block: 1px solid var(--line); }
        .section { padding-block: 5rem; }
        .section-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .section-head h2 { margin: 0.35rem 0 0; font-size: 2.2rem; }
        .section-head p { max-width: 520px; margin: 0; color: var(--muted); line-height: 1.6; }

        .impact-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.9rem;
        }
        .impact-item {
            min-height: 180px;
            padding: 1.25rem;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
        }
        .impact-icon {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            margin-bottom: 1.4rem;
            border-radius: 8px;
            background: #eef6f6;
            color: var(--teal);
            font-size: 1.05rem;
        }
        .impact-item:nth-child(2) .impact-icon { background: #fff2ed; color: var(--orange); }
        .impact-item:nth-child(3) .impact-icon { background: #edf7fb; color: var(--cyan); }
        .impact-item:nth-child(4) .impact-icon { background: #eef8f2; color: var(--green); }
        .impact-value { display: block; font-size: 2rem; line-height: 1; }
        .impact-label { display: block; margin-top: 0.6rem; color: var(--muted); font-size: 0.84rem; line-height: 1.45; }

        .quality-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 3rem;
        }
        .quality-block h3 { margin: 0; font-size: 1.35rem; }
        .quality-value { display: block; margin-top: 0.85rem; font-size: 3.4rem; line-height: 1; }
        .quality-copy { margin: 0.85rem 0 1rem; color: var(--muted); line-height: 1.6; }
        .quality-track { height: 8px; overflow: hidden; border-radius: 4px; background: #dde7ea; }
        .quality-track span { display: block; width: var(--progress); height: 100%; background: var(--teal); }
        .quality-block:last-child .quality-track span { background: var(--orange); }

        .legacy-band { background: var(--navy); color: #f5fbfc; }
        .legacy-band .eyebrow { color: #ef9b7d; }
        .legacy-grid { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(280px, 0.75fr); gap: 4rem; align-items: start; }
        .legacy-copy h2 { margin: 0.5rem 0 1rem; font-size: 2.6rem; }
        .legacy-copy p { max-width: 680px; color: #bdd0d7; line-height: 1.8; font-size: 1.03rem; }
        .legacy-list { display: grid; gap: 0; border-top: 1px solid #375969; }
        .legacy-row { display: grid; grid-template-columns: 42px 1fr; gap: 0.8rem; padding: 1rem 0; border-bottom: 1px solid #375969; }
        .legacy-row i { color: #52c5bd; font-size: 1.1rem; }
        .legacy-row strong { display: block; }
        .legacy-row span { display: block; margin-top: 0.25rem; color: #9fb7c0; font-size: 0.82rem; }

        .closing { text-align: center; padding-block: 6rem; }
        .closing-mark {
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            margin: 0 auto 1.4rem;
            border-radius: 50%;
            background: #fff0e9;
            color: var(--orange);
            font-size: 1.35rem;
        }
        .closing h2 { max-width: 760px; margin: 0 auto; font-size: 2.5rem; }
        .closing p { max-width: 670px; margin: 1.25rem auto 0; color: var(--muted); line-height: 1.8; }
        .signature { margin-top: 2rem; font-family: "Space Grotesk", sans-serif; font-weight: 700; color: var(--navy); }

        footer { border-top: 1px solid var(--line); padding: 1.4rem 0; color: var(--muted); font-size: 0.78rem; }
        .footer-inner { display: flex; justify-content: space-between; gap: 1rem; }
        footer a { text-decoration: none; }

        @media (max-width: 900px) {
            h1 { font-size: 3.6rem; }
            .impact-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .legacy-grid { grid-template-columns: 1fr; gap: 2.5rem; }
        }
        @media (max-width: 640px) {
            .page-shell { width: min(100% - 28px, 1180px); }
            .topbar { min-height: 66px; }
            .cycle-state { display: none; }
            .admin-link span { display: none; }
            .hero-band { min-height: auto; padding: 4rem 0 3rem; }
            h1 { font-size: 3rem; }
            .hero-message { font-size: 1.02rem; }
            .hero-numbers { grid-template-columns: 1fr; }
            .hero-number, .hero-number + .hero-number { border-left: 0; border-top: 1px solid var(--line); padding: 1rem 0; }
            .hero-number:first-child { border-top: 0; }
            .section { padding-block: 3.5rem; }
            .section-head { display: block; }
            .section-head p { margin-top: 1rem; }
            .impact-grid, .quality-grid { grid-template-columns: 1fr; }
            .quality-grid { gap: 2rem; }
            .legacy-copy h2, .closing h2 { font-size: 2rem; }
            .footer-inner { flex-direction: column; }
        }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
        }
    </style>
</head>
<body>
    <header class="page-shell topbar">
        <a class="brand" href="/" aria-label="FBControl">
            <img src="<?= h($asset('assets/icon-192.png')) ?>" alt="">
            <span><b>FB</b>Control</span>
        </a>
        <div class="top-actions">
            <span class="cycle-state">Ciclo concluído</span>
            <a class="admin-link" href="/?r=auth/login">
                <i class="bi bi-shield-lock" aria-hidden="true"></i>
                <span><?= h($loginLabel) ?></span>
            </a>
        </div>
    </header>

    <main>
        <section class="page-shell hero-band">
            <p class="eyebrow">Uma história construída pela operação</p>
            <h1>FBControl</h1>
            <p class="hero-message">
                <strong>Obrigado por fazer parte desta história.</strong>
                Cada reserva temática ajudou a transformar planejamento em informação, atendimento em aprendizado e trabalho diário em um legado mensurável.
            </p>
            <div class="hero-period">
                <span><i class="bi bi-calendar3"></i><?= h($formatDate($stats['inicio'] ?? null)) ?> a <?= h($formatDate($stats['fim'] ?? null)) ?></span>
                <span><i class="bi bi-check2-circle"></i>Dados consolidados sem informações pessoais</span>
            </div>
            <div class="hero-numbers">
                <div class="hero-number"><strong data-count="<?= (int)($stats['dias'] ?? 0) ?>"><?= h($formatNumber($stats['dias'] ?? 0)) ?></strong><span>dias de história registrada</span></div>
                <div class="hero-number"><strong data-count="<?= (int)($stats['restaurantes'] ?? 0) ?>"><?= h($formatNumber($stats['restaurantes'] ?? 0)) ?></strong><span>restaurantes temáticos organizados</span></div>
                <div class="hero-number"><strong data-count="<?= (int)($stats['operadores'] ?? 0) ?>"><?= h($formatNumber($stats['operadores'] ?? 0)) ?></strong><span>profissionais que registraram reservas</span></div>
            </div>
        </section>

        <section class="impact-band">
            <div class="page-shell section">
                <div class="section-head">
                    <div><p class="eyebrow">Impacto acumulado</p><h2>O trabalho virou número</h2></div>
                    <p>Uma visão consolidada do trabalho realizado exclusivamente na gestão das reservas temáticas.</p>
                </div>
                <div class="impact-grid">
                    <article class="impact-item"><div class="impact-icon"><i class="bi bi-people"></i></div><strong class="impact-value" data-count="<?= (int)($stats['pax_planejado'] ?? 0) ?>"><?= h($formatNumber($stats['pax_planejado'] ?? 0)) ?></strong><span class="impact-label">PAX planejados em reservas válidas</span></article>
                    <article class="impact-item"><div class="impact-icon"><i class="bi bi-calendar-heart"></i></div><strong class="impact-value" data-count="<?= (int)($stats['reservas'] ?? 0) ?>"><?= h($formatNumber($stats['reservas'] ?? 0)) ?></strong><span class="impact-label">reservas temáticas administradas</span></article>
                    <article class="impact-item"><div class="impact-icon"><i class="bi bi-people-fill"></i></div><strong class="impact-value" data-count="<?= (int)($stats['grupos'] ?? 0) ?>"><?= h($formatNumber($stats['grupos'] ?? 0)) ?></strong><span class="impact-label">grupos organizados pelo sistema</span></article>
                    <article class="impact-item"><div class="impact-icon"><i class="bi bi-clock-history"></i></div><strong class="impact-value" data-count="<?= (int)($stats['servicos_planejados'] ?? 0) ?>"><?= h($formatNumber($stats['servicos_planejados'] ?? 0)) ?></strong><span class="impact-label">serviços e turnos temáticos planejados</span></article>
                </div>
            </div>
        </section>

        <section class="page-shell section">
            <div class="section-head">
                <div><p class="eyebrow">Qualidade operacional</p><h2>Mais do que volume</h2></div>
                <p>Os percentuais usam critérios distintos e transparentes para não transformar indicadores diferentes em uma única leitura.</p>
            </div>
            <div class="quality-grid">
                <article class="quality-block">
                    <h3>Rastreabilidade das reservas</h3>
                    <strong class="quality-value"><?= h($formatPercent($stats['rastreabilidade'] ?? 0)) ?></strong>
                    <p class="quality-copy">Percentual de reservas com evento de criação preservado no histórico temático.</p>
                    <div class="quality-track" aria-label="Rastreabilidade das reservas" style="--progress: <?= min(100, max(0, (float)($stats['rastreabilidade'] ?? 0))) ?>%;"><span></span></div>
                </article>
                <article class="quality-block">
                    <h3>Autoria identificada</h3>
                    <strong class="quality-value"><?= h($formatPercent($stats['autoria_identificada'] ?? 0)) ?></strong>
                    <p class="quality-copy">Percentual de reservas vinculadas à pessoa responsável pelo registro no sistema.</p>
                    <div class="quality-track" aria-label="Autoria identificada" style="--progress: <?= min(100, max(0, (float)($stats['autoria_identificada'] ?? 0))) ?>%;"><span></span></div>
                </article>
            </div>
        </section>

        <section class="legacy-band">
            <div class="page-shell section legacy-grid">
                <div class="legacy-copy">
                    <p class="eyebrow">O que permanece</p>
                    <h2>Um sistema termina.<br>O aprendizado fica.</h2>
                    <p>O FBControl nasceu de problemas reais, cresceu ao lado de quem organizava as reservas e mostrou que tecnologia também pode ser construída perto do salão, da recepção e das pessoas que fazem o serviço acontecer.</p>
                    <p>Este ciclo chega ao fim, mas cada melhoria, cada conversa e cada desafio resolvido continuam fazendo parte da história de quem acreditou que era possível fazer melhor.</p>
                </div>
                <div class="legacy-list">
                    <div class="legacy-row"><i class="bi bi-shield-check"></i><div><strong><?= h($formatNumber($stats['eventos_historico'] ?? 0)) ?> eventos no histórico</strong><span>Criações e alterações preservadas na trilha temática.</span></div></div>
                    <div class="legacy-row"><i class="bi bi-people-fill"></i><div><strong><?= h($formatNumber($stats['grupos'] ?? 0)) ?> grupos organizados</strong><span>UHs reunidas para facilitar planejamento e atendimento.</span></div></div>
                    <div class="legacy-row"><i class="bi bi-check2-all"></i><div><strong><?= h($formatNumber($stats['reservas_finalizadas'] ?? 0)) ?> reservas finalizadas</strong><span>Reservas acompanhadas até a confirmação operacional.</span></div></div>
                    <div class="legacy-row"><i class="bi bi-stars"></i><div><strong>Versão 3.0</strong><span>A fase mais madura de um projeto feito com propósito.</span></div></div>
                </div>
            </div>
        </section>

        <section class="page-shell closing">
            <div class="closing-mark"><i class="bi bi-heart-fill"></i></div>
            <h2>Às pessoas que usaram, testaram, questionaram e ajudaram a melhorar: obrigado.</h2>
            <p>O FBControl não foi apenas código. Foi escuta, tentativa, responsabilidade e vontade de transformar a operação por meio de soluções construídas de dentro para fora.</p>
        </section>
    </main>

    <footer>
        <div class="page-shell footer-inner">
            <span>FBControl · Gestão Inteligente de A&amp;B</span>
            <span>Grand Oca Maragogi Resort · <a href="/?r=privacidade/index">Privacidade</a></span>
        </div>
    </footer>

    <script>
        (() => {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            const formatter = new Intl.NumberFormat('pt-BR');
            const counters = Array.from(document.querySelectorAll('[data-count]'));
            const animate = (element) => {
                const target = Math.max(0, Number(element.dataset.count || 0));
                const started = performance.now();
                const duration = 900;
                const frame = (now) => {
                    const progress = Math.min(1, (now - started) / duration);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    element.textContent = formatter.format(Math.round(target * eased));
                    if (progress < 1) requestAnimationFrame(frame);
                };
                requestAnimationFrame(frame);
            };
            if (!('IntersectionObserver' in window)) {
                counters.forEach(animate);
                return;
            }
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    animate(entry.target);
                    observer.unobserve(entry.target);
                });
            }, { threshold: 0.35 });
            counters.forEach((counter) => observer.observe(counter));
        })();
    </script>
</body>
</html>
