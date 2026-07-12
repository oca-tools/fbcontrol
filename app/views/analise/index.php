<?php
/* Hub Análise (remake visual, etapa 5): shell com abas. O conteúdo vive nos partials _visao/_tematicos/_kpis. */
$aba = $this->data['aba'] ?? 'visao';
$datasAtuais = array_filter([
    'data' => sanitize_date_param($_GET['data'] ?? ''),
    'data_inicio' => sanitize_date_param($_GET['data_inicio'] ?? ''),
    'data_fim' => sanitize_date_param($_GET['data_fim'] ?? ''),
], static fn($v) => $v !== '');
$abaLink = static function (string $abaDestino) use ($datasAtuais): string {
    return '/?' . http_build_query(array_merge($datasAtuais, ['r' => 'analise/index', 'aba' => $abaDestino]));
};
$perfilHub = (string)(Auth::user()['perfil'] ?? '');
$subtitulos = [
    'visao' => 'Fluxo, alertas e distribuição — um filtro só para tudo.',
    'tematicos' => 'Reservas, presença e no-show dos restaurantes temáticos.',
    'kpis' => 'Qualidade, ocupação e performance da equipe.',
];
?>
<div class="saas-page analise-hub-page">
    <section class="fb-page-head">
        <div class="fb-page-head__meta">
            <div>
                <p class="fb-card__eyebrow">Inteligência A&amp;B</p>
                <h3 class="fb-page-head__title">Análise</h3>
                <p class="fb-page-head__subtitle"><?= h($subtitulos[$aba] ?? $subtitulos['visao']) ?></p>
            </div>
            <div class="fb-page-head__actions">
                <a class="fb-btn" href="/?<?= h(http_build_query(array_merge($datasAtuais, ['r' => 'relatorios/index']))) ?>"><i class="bi bi-download"></i> Exportar recorte</a>
            </div>
        </div>
    </section>

    <div class="fb-chiprow fb-mt">
        <a class="fb-chip<?= $aba === 'visao' ? ' fb-chip--active' : '' ?>" href="<?= h($abaLink('visao')) ?>">Visão geral</a>
        <a class="fb-chip<?= $aba === 'tematicos' ? ' fb-chip--active' : '' ?>" href="<?= h($abaLink('tematicos')) ?>">Temáticos</a>
        <?php if (in_array($perfilHub, ['admin', 'gerente'], true)): ?>
            <a class="fb-chip<?= $aba === 'kpis' ? ' fb-chip--active' : '' ?>" href="<?= h($abaLink('kpis')) ?>">KPIs</a>
        <?php endif; ?>
    </div>

    <?php if ($aba === 'tematicos'): ?>
        <?php require __DIR__ . '/_tematicos.php'; ?>
    <?php elseif ($aba === 'kpis'): ?>
        <?php require __DIR__ . '/_kpis.php'; ?>
    <?php else: ?>
        <?php require __DIR__ . '/_visao.php'; ?>
    <?php endif; ?>
</div>
