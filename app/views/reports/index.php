<?php
/* Hub Relatórios: central única de exportações + envios automáticos. */
$filters = $this->data['filters'] ?? [];
$restaurantes = $this->data['restaurantes'] ?? [];
$operacoes = $this->data['operacoes'] ?? [];
$totais = $this->data['totais'] ?? [];
$emailResumo = $this->data['email_resumo'] ?? null;
$isAdmin = (Auth::user()['perfil'] ?? '') === 'admin';

$exportQuery = static function (array $extra = []) use ($filters): string {
    return http_build_query(array_merge(array_filter($filters, static fn($v) => $v !== ''), $extra));
};
$consultaQuery = $exportQuery(['r' => 'relatorios/consulta']);
$temFiltroDePeriodo = !empty($filters['data_inicio']) && !empty($filters['data_fim']);

$filtrosAtivos = [];
if (!empty($filters['data'])) {
    $filtrosAtivos[] = ['label' => 'Data unica', 'value' => format_date_br((string)$filters['data'])];
}
if (!empty($filters['data_inicio']) || !empty($filters['data_fim'])) {
    $inicio = !empty($filters['data_inicio']) ? format_date_br((string)$filters['data_inicio']) : '-';
    $fim = !empty($filters['data_fim']) ? format_date_br((string)$filters['data_fim']) : '-';
    $filtrosAtivos[] = ['label' => 'Periodo', 'value' => $inicio . ' a ' . $fim];
}
if (!empty($filters['uh_numero'])) {
    $filtrosAtivos[] = ['label' => 'UH', 'value' => (string)$filters['uh_numero']];
}
if (!empty($filters['restaurante_id'])) {
    foreach ($restaurantes as $rest) {
        if ((int)$rest['id'] === (int)$filters['restaurante_id']) {
            $filtrosAtivos[] = ['label' => 'Restaurante', 'value' => normalize_mojibake((string)$rest['nome'])];
            break;
        }
    }
}
if (!empty($filters['operacao_id'])) {
    foreach ($operacoes as $op) {
        if ((int)$op['id'] === (int)$filters['operacao_id']) {
            $filtrosAtivos[] = ['label' => 'Operacao', 'value' => normalize_mojibake((string)$op['nome'])];
            break;
        }
    }
}
if (!empty($filters['status'])) {
    $filtrosAtivos[] = ['label' => 'Status', 'value' => str_replace('_', ' ', (string)$filters['status'])];
}

$exportCards = [
    [
        'icon' => 'bi-collection',
        'label' => 'Operacao',
        'title' => 'Consolidado operacional',
        'description' => 'Acessos, refeicoes de colaboradores e vouchers no mesmo arquivo.',
        'total_key' => 'consolidado',
        'total_label' => 'registros',
        'links' => [
            ['label' => 'CSV', 'icon' => 'bi-filetype-csv', 'href' => '/?r=relatorios/export&type=csv&' . $exportQuery()],
            ['label' => 'Excel', 'icon' => 'bi-file-earmark-excel', 'href' => '/?r=relatorios/export&type=xlsx&' . $exportQuery()],
        ],
    ],
    [
        'icon' => 'bi-database',
        'label' => 'BI',
        'title' => 'Base completa',
        'description' => 'Todos os acessos do recorte, linha a linha, para analise externa.',
        'total_key' => 'bi',
        'total_label' => 'registros',
        'links' => [
            ['label' => 'CSV', 'icon' => 'bi-filetype-csv', 'href' => '/?r=relatorios/export_bi&type=csv&' . $exportQuery()],
            ['label' => 'Excel', 'icon' => 'bi-file-earmark-excel', 'href' => '/?r=relatorios/export_bi&type=xlsx&' . $exportQuery()],
        ],
    ],
    [
        'icon' => 'bi-map',
        'label' => 'UH',
        'title' => 'Mapa diario por UH',
        'description' => 'Presencas consolidadas por UH no dia selecionado.',
        'total_key' => 'mapa',
        'total_label' => 'UHs',
        'links' => [
            ['label' => 'CSV', 'icon' => 'bi-filetype-csv', 'href' => '/?r=relatorios/export_mapa&type=csv&data=' . rawurlencode((string)($filters['data'] ?? ''))],
            ['label' => 'Excel', 'icon' => 'bi-file-earmark-excel', 'href' => '/?r=relatorios/export_mapa&type=xlsx&data=' . rawurlencode((string)($filters['data'] ?? ''))],
        ],
    ],
    [
        'icon' => 'bi-people',
        'label' => 'Interno',
        'title' => 'Refeicoes de colaborador',
        'description' => 'Consumo interno registrado no Corais, por colaborador.',
        'total_key' => 'colaboradores',
        'total_label' => 'registros',
        'links' => [
            ['label' => 'CSV', 'icon' => 'bi-filetype-csv', 'href' => '/?r=relatorios/export_colaboradores&type=csv&' . $exportQuery()],
            ['label' => 'Excel', 'icon' => 'bi-file-earmark-excel', 'href' => '/?r=relatorios/export_colaboradores&type=xlsx&' . $exportQuery()],
        ],
    ],
    [
        'icon' => 'bi-ticket-perforated',
        'label' => 'Upselling',
        'title' => 'Vouchers',
        'description' => 'Registros de upselling com evidencias anexadas.',
        'total_key' => 'vouchers',
        'total_label' => 'registros',
        'links' => array_values(array_filter([
            ['label' => 'CSV', 'icon' => 'bi-filetype-csv', 'href' => '/?r=relatorios/export_vouchers&type=csv&' . $exportQuery()],
            ['label' => 'Excel', 'icon' => 'bi-file-earmark-excel', 'href' => '/?r=relatorios/export_vouchers&type=xlsx&' . $exportQuery()],
            $temFiltroDePeriodo ? ['label' => 'PDFs', 'icon' => 'bi-file-earmark-zip', 'href' => '/?r=relatorios/export_voucher_pdfs&' . $exportQuery()] : null,
        ])),
        'note' => $temFiltroDePeriodo ? '' : 'PDFs exigem data inicio e data fim.',
    ],
    [
        'icon' => 'bi-calendar-heart',
        'label' => 'Tematicos',
        'title' => 'Reservas tematicas',
        'description' => 'Reservas, presenca e no-show dos restaurantes tematicos.',
        'total_key' => null,
        'total_label' => '',
        'links' => [
            ['label' => 'CSV', 'icon' => 'bi-filetype-csv', 'href' => '/?r=relatoriosTematicos/export&type=csv&' . $exportQuery()],
            ['label' => 'Excel', 'icon' => 'bi-file-earmark-excel', 'href' => '/?r=relatoriosTematicos/export&type=xlsx&' . $exportQuery()],
            ['label' => 'Analise', 'icon' => 'bi-graph-up', 'href' => '/?r=relatoriosTematicos/index', 'ghost' => true],
        ],
    ],
];
?>

<div class="fb-report-hub">
    <section class="fb-page-head">
        <div class="fb-page-head__meta">
            <div>
                <p class="fb-card__eyebrow">Central de dados</p>
                <h3 class="fb-page-head__title">Relatorios</h3>
                <p class="fb-page-head__subtitle">Organize o recorte, consulte em tela ou exporte bases prontas para BI, auditoria e fechamento gerencial.</p>
            </div>
            <div class="fb-page-head__actions">
                <a class="fb-btn fb-btn--primary" href="/?<?= h($consultaQuery) ?>"><i class="bi bi-table"></i> Consultar em tela</a>
            </div>
        </div>

        <p class="fb-filter-line">
            <i class="bi bi-funnel" aria-hidden="true"></i>
            <?php if ($filtrosAtivos === []): ?>
                Sem filtro ativo — use o formulario abaixo para refinar.
            <?php else: ?>
                <?php foreach ($filtrosAtivos as $filtroAtivo): ?>
                    <span class="fb-filter-line__item"><?= h($filtroAtivo['label']) ?>: <?= h($filtroAtivo['value']) ?></span>
                <?php endforeach; ?>
            <?php endif; ?>
        </p>
    </section>

    <section class="fb-card fb-card--flat fb-report-filter">
        <div class="fb-card__head">
            <div>
                <p class="fb-card__eyebrow">Filtro do recorte</p>
                <h5 class="fb-card__title">Aplicado em todas as exportacoes</h5>
            </div>
        </div>

        <form method="get" action="/" class="fb-report-filter__form" data-fb-filters>
            <input type="hidden" name="r" value="relatorios/index">

            <label class="fb-field">
                <span class="fb-label">Data unica</span>
                <input type="date" class="fb-input" name="data" value="<?= h($filters['data'] ?? '') ?>">
            </label>
            <label class="fb-field">
                <span class="fb-label">Data inicio</span>
                <input type="date" class="fb-input" name="data_inicio" value="<?= h($filters['data_inicio'] ?? '') ?>">
            </label>
            <label class="fb-field">
                <span class="fb-label">Data fim</span>
                <input type="date" class="fb-input" name="data_fim" value="<?= h($filters['data_fim'] ?? '') ?>">
            </label>
            <label class="fb-field">
                <span class="fb-label">UH</span>
                <input type="text" inputmode="numeric" class="fb-input" name="uh_numero" value="<?= h($filters['uh_numero'] ?? '') ?>" placeholder="Ex.: 4110">
            </label>
            <label class="fb-field">
                <span class="fb-label">Restaurante</span>
                <select class="fb-select" name="restaurante_id">
                    <option value="">Todos</option>
                    <?php foreach ($restaurantes as $rest): ?>
                        <option value="<?= (int)$rest['id'] ?>" <?= ($filters['restaurante_id'] ?? '') == $rest['id'] ? 'selected' : '' ?>><?= h($rest['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="fb-field">
                <span class="fb-label">Operacao</span>
                <select class="fb-select" name="operacao_id">
                    <option value="">Todas</option>
                    <?php foreach ($operacoes as $op): ?>
                        <option value="<?= (int)$op['id'] ?>" <?= ($filters['operacao_id'] ?? '') == $op['id'] ? 'selected' : '' ?>><?= h($op['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="fb-field">
                <span class="fb-label">Status</span>
                <select class="fb-select" name="status">
                    <option value="">Todos</option>
                    <?php foreach (FiltroOperacionalService::STATUS_FILTERS as $statusOption): ?>
                        <option value="<?= h($statusOption) ?>" <?= ($filters['status'] ?? '') === $statusOption ? 'selected' : '' ?>><?= h(str_replace('_', ' ', $statusOption)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="fb-report-filter__actions">
                <button type="submit" class="fb-btn fb-btn--primary">Aplicar filtro</button>
                <a class="fb-btn fb-btn--ghost" href="/?r=relatorios/index">Limpar</a>
            </div>
        </form>
    </section>

    <section class="fb-report-grid">
        <?php foreach ($exportCards as $card): ?>
            <?php
            $totalKey = $card['total_key'] ?? null;
            $totalValue = $totalKey !== null && isset($totais[$totalKey]) ? (int)$totais[$totalKey] : null;
            ?>
            <article class="fb-report-card">
                <div class="fb-report-card__head">
                    <span class="fb-report-card__icon"><i class="bi <?= h((string)$card['icon']) ?>"></i></span>
                    <span class="fb-badge fb-badge--day-use"><?= h((string)$card['label']) ?></span>
                </div>
                <div>
                    <h5 class="fb-report-card__title"><?= h((string)$card['title']) ?></h5>
                    <p class="fb-report-card__text"><?= h((string)$card['description']) ?></p>
                </div>
                <?php if ($totalValue !== null): ?>
                    <div class="fb-report-card__total">
                        <strong><?= number_format($totalValue, 0, ',', '.') ?></strong>
                        <span><?= h((string)$card['total_label']) ?> no recorte</span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($card['note'])): ?>
                    <p class="fb-report-card__note"><?= h((string)$card['note']) ?></p>
                <?php endif; ?>
                <div class="fb-report-card__actions">
                    <?php foreach ($card['links'] as $link): ?>
                        <a class="fb-btn<?= !empty($link['ghost']) ? ' fb-btn--ghost' : '' ?>" href="<?= h((string)$link['href']) ?>">
                            <i class="bi <?= h((string)$link['icon']) ?>"></i><?= h((string)$link['label']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <?php if ($isAdmin && is_array($emailResumo)): ?>
        <section class="fb-card fb-card--flat fb-report-email">
            <div class="fb-card__head">
                <div>
                    <p class="fb-card__eyebrow">Envios automaticos</p>
                    <h5 class="fb-card__title">Resumo diario de A&amp;B</h5>
                </div>
                <a class="fb-btn" href="/?r=emailRelatorios/index"><i class="bi bi-gear"></i> Gerenciar envio</a>
            </div>
            <div class="fb-report-email__row">
                <div>
                    <strong><?= h(substr((string)($emailResumo['hora_envio'] ?? '23:00:00'), 0, 5)) ?></strong>
                    <span><?= (int)($emailResumo['destinatarios'] ?? 0) ?> destinatario(s)</span>
                    <?php if (!empty($emailResumo['ultimo_envio'])): ?>
                        <span>Ultimo envio: <?= h((string)$emailResumo['ultimo_envio']) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ((int)($emailResumo['ativo'] ?? 0) === 1): ?>
                    <span class="fb-badge fb-badge--ok">Ativo</span>
                <?php else: ?>
                    <span class="fb-badge fb-badge--nao-informado">Inativo</span>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
