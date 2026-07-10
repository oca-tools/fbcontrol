<?php
$restaurantes = $this->data['restaurantes'] ?? [];
$turnos = $this->data['turnos'] ?? [];
$reservas = $this->data['reservas'] ?? [];
$filters = $this->data['filters'] ?? [];
$closed = (bool)($this->data['closed'] ?? false);
$user = $this->data['user'] ?? Auth::user();
$restrictedRestaurant = $this->data['restricted_restaurant'] ?? null;
$summary = $this->data['summary'] ?? [];
$capacidadeTurno = $this->data['capacidade_turno'] ?? null;

$perfilOperacao = (string)($user['perfil'] ?? '');
$podeFecharTurno = in_array($perfilOperacao, ['admin', 'supervisor', 'gerente'], true);
$dataSelecionada = (string)($filters['data'] ?? date('Y-m-d'));

$queryFiltros = array_filter([
    'data' => $dataSelecionada,
    'restaurante_id' => (string)($filters['restaurante_id'] ?? ''),
    'turno_id' => (string)($filters['turno_id'] ?? ''),
    'q' => (string)($filters['q'] ?? ''),
    'status' => (string)($filters['status'] ?? ''),
], static fn($v) => $v !== '');

$statusLink = static function (string $status) use ($queryFiltros): string {
    $query = $queryFiltros;
    unset($query['status']);
    if ($status !== '') {
        $query['status'] = $status;
    }

    return '/?' . http_build_query(array_merge($query, ['r' => 'reservasTematicas/operacao']));
};

$normalizarStatusCard = static function (array $row): string {
    $status = normalize_mojibake((string)($row['status_reserva'] ?? $row['status'] ?? ''));
    $flat = mb_strtolower($status, 'UTF-8');
    if (strpos($flat, 'pre') === 0) {
        return 'pre';
    }
    if (strpos($flat, 'finaliz') !== false) {
        return 'finalizada';
    }
    if (strpos($flat, 'compareceu') !== false) {
        return 'no_show';
    }
    if (strpos($flat, 'cancel') !== false) {
        return 'cancelada';
    }

    return 'aguardando';
};

$paxAtivos = 0;
foreach ($reservas as $reservaRow) {
    $estadoRow = $normalizarStatusCard($reservaRow);
    if (in_array($estadoRow, ['aguardando', 'pre', 'finalizada'], true)) {
        $paxAtivos += (int)($reservaRow['pax'] ?? 0);
    }
}

$percentualLotacao = ($capacidadeTurno !== null && (int) $capacidadeTurno > 0)
    ? min(100, (int) round(($paxAtivos / (int) $capacidadeTurno) * 100))
    : null;

$restauranteSelecionadoNome = 'Todos os restaurantes';
if ($restrictedRestaurant) {
    $restauranteSelecionadoNome = normalize_mojibake((string) $restrictedRestaurant['nome']);
} else {
    foreach ($restaurantes as $restauranteRow) {
        if ((string) ($filters['restaurante_id'] ?? '') !== '' && (string) $restauranteRow['id'] === (string) $filters['restaurante_id']) {
            $restauranteSelecionadoNome = normalize_mojibake((string) $restauranteRow['nome']);
            break;
        }
    }
}

$turnoSelecionadoNome = 'Todos os turnos';
foreach ($turnos as $turnoRow) {
    if ((string) ($filters['turno_id'] ?? '') !== '' && (string) $turnoRow['id'] === (string) $filters['turno_id']) {
        $turnoSelecionadoNome = (string) ($turnoRow['hora'] ?? $turnoRow['nome'] ?? ('Turno ' . (int) $turnoRow['id']));
        break;
    }
}

$resumoCards = [
    ['label' => 'Em fila', 'value' => (int) ($summary['reservada'] ?? 0)],
    ['label' => 'UH pendente', 'value' => (int) ($summary['pre_reserva'] ?? 0)],
    ['label' => 'Finalizadas', 'value' => (int) ($summary['finalizada'] ?? 0)],
    ['label' => 'No-show', 'value' => (int) ($summary['nao_compareceu'] ?? 0)],
];
if ($capacidadeTurno !== null) {
    $resumoCards[] = [
        'label' => 'Capacidade',
        'value' => number_format((int) $capacidadeTurno, 0, ',', '.'),
    ];
}
?>
<div class="tematico-checkin-page fb-checkin-page">
    <section class="fb-page-head">
        <div class="fb-page-head__meta">
            <div>
                <p class="fb-card__eyebrow">Operação temática</p>
                <h1 class="fb-page-head__title">Conferência do turno</h1>
                <p class="fb-page-head__subtitle">
                    <?= h(format_date_br($dataSelecionada)) ?> · <?= h($restauranteSelecionadoNome) ?> · <?= h($turnoSelecionadoNome) ?>
                </p>
            </div>
            <div class="fb-page-head__actions">
                <a class="fb-btn" href="/?<?= h(http_build_query(array_merge($queryFiltros, ['r' => 'reservasTematicas/conferencia']))) ?>">
                    <i class="bi bi-printer"></i> Conferência e impressão
                </a>
            </div>
        </div>
        <div class="fb-summary-bar">
            <?php foreach ($resumoCards as $item): ?>
                <div class="fb-summary-bar__item">
                    <span><?= h((string) $item['label']) ?></span>
                    <strong><?= h((string) $item['value']) ?></strong>
                </div>
            <?php endforeach; ?>
            <div class="fb-summary-bar__item">
                <span>Contexto</span>
                <strong><?= number_format((int) ($summary['total'] ?? count($reservas)), 0, ',', '.') ?> reservas</strong>
            </div>
        </div>
    </section>

    <section class="fb-card fb-card--flat fb-reserve-toolbar fb-checkin-toolbar">
        <form method="get" action="/" class="fb-reserve-toolbar__form">
            <input type="hidden" name="r" value="reservasTematicas/operacao">
            <div class="fb-reserve-toolbar__field">
                <label class="fb-label">Recorte operacional</label>
                <div class="fb-checkin-toolbar__grid">
                    <input type="date" class="fb-input" name="data" value="<?= h($dataSelecionada) ?>">
                    <?php if ($restrictedRestaurant): ?>
                        <input type="hidden" name="restaurante_id" value="<?= (int) $restrictedRestaurant['id'] ?>">
                        <div class="fb-input fb-checkin-toolbar__static"><?= h($restauranteSelecionadoNome) ?></div>
                    <?php else: ?>
                        <select class="fb-select" name="restaurante_id">
                            <option value="">Todos os restaurantes</option>
                            <?php foreach ($restaurantes as $rest): ?>
                                <option value="<?= (int) $rest['id'] ?>" <?= ($filters['restaurante_id'] ?? '') == $rest['id'] ? 'selected' : '' ?>><?= h((string) $rest['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                    <select class="fb-select" name="turno_id">
                        <option value="">Todos os turnos</option>
                        <?php foreach ($turnos as $turno): ?>
                            <option value="<?= (int) $turno['id'] ?>" <?= ($filters['turno_id'] ?? '') == $turno['id'] ? 'selected' : '' ?>><?= h((string) ($turno['hora'] ?? $turno['nome'] ?? ('Turno ' . (int) $turno['id']))) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" class="fb-input" name="q" value="<?= h((string) ($filters['q'] ?? '')) ?>" placeholder="Buscar por UH ou titular">
                </div>
            </div>
            <div class="fb-reserve-toolbar__controls">
                <button type="submit" class="fb-btn fb-btn--primary">Filtrar</button>
                <a class="fb-btn fb-btn--ghost" href="/?r=reservasTematicas/operacao">Hoje</a>
            </div>
        </form>
    </section>

    <?php if ($percentualLotacao !== null): ?>
        <section class="fb-card fb-card--flat fb-checkin-capacity">
            <div class="fb-checkin-capacity__head">
                <div>
                    <p class="fb-card__eyebrow">Capacidade do turno</p>
                    <h2 class="fb-card__title">Ocupação acompanhada em tempo real</h2>
                </div>
                <strong class="fb-num"><?= number_format($paxAtivos, 0, ',', '.') ?> / <?= number_format((int) $capacidadeTurno, 0, ',', '.') ?> PAX</strong>
            </div>
            <div class="fb-progress">
                <div class="fb-progress__bar <?= $percentualLotacao >= 100 ? 'fb-progress__bar--full' : ($percentualLotacao >= 85 ? 'fb-progress__bar--warn' : '') ?>" style="width: <?= $percentualLotacao ?>%;"></div>
            </div>
        </section>
    <?php endif; ?>

    <section class="fb-card fb-card--flat fb-checkin-statusbar">
        <div class="fb-checkin-statusbar__chips">
            <a class="fb-chip<?= ($filters['status'] ?? '') === '' ? ' fb-chip--active' : '' ?>" href="<?= h($statusLink('')) ?>">Todas <?= (int) ($summary['total'] ?? 0) ?></a>
            <a class="fb-chip<?= ($filters['status'] ?? '') === 'Reservada' ? ' fb-chip--active' : '' ?>" href="<?= h($statusLink('Reservada')) ?>">Aguardando <?= (int) ($summary['reservada'] ?? 0) ?></a>
            <?php if ((int) ($summary['pre_reserva'] ?? 0) > 0): ?>
                <a class="fb-chip<?= ($filters['status'] ?? '') === 'Pre-reserva' ? ' fb-chip--active' : '' ?>" href="<?= h($statusLink('Pre-reserva')) ?>">UH pendente <?= (int) $summary['pre_reserva'] ?></a>
            <?php endif; ?>
            <a class="fb-chip<?= ($filters['status'] ?? '') === 'Finalizada' ? ' fb-chip--active' : '' ?>" href="<?= h($statusLink('Finalizada')) ?>">Finalizadas <?= (int) ($summary['finalizada'] ?? 0) ?></a>
            <a class="fb-chip<?= ($filters['status'] ?? '') === 'Nao compareceu' ? ' fb-chip--active' : '' ?>" href="<?= h($statusLink('Nao compareceu')) ?>">No-show <?= (int) ($summary['nao_compareceu'] ?? 0) ?></a>
            <a class="fb-chip<?= ($filters['status'] ?? '') === 'Cancelada' ? ' fb-chip--active' : '' ?>" href="<?= h($statusLink('Cancelada')) ?>">Canceladas <?= (int) ($summary['cancelada'] ?? 0) ?></a>
        </div>
    </section>

    <?php if ($closed): ?>
        <div class="fb-alert-inline fb-alert-inline--warn">
            <i class="bi bi-lock"></i>
            <div>
                <strong>Turno fechado para alterações</strong>
                <span>A conferência foi encerrada. Novos ajustes exigem justificativa no fluxo de conferência.</span>
            </div>
        </div>
    <?php endif; ?>

    <section class="fb-checkin-list">
        <?php foreach ($reservas as $reserva): ?>
            <?php
            $estado = $normalizarStatusCard($reserva);
            $ehPre = $estado === 'pre';
            $titular = normalize_mojibake((string) ($reserva['titular_nome_display'] ?? $reserva['titular_nome'] ?? '-'));
            $grupoDisplay = trim(normalize_mojibake((string) ($reserva['grupo_nome_display'] ?? $reserva['grupo_nome'] ?? '')));
            $uhDisplay = $ehPre ? 'UH pendente' : ('UH ' . uh_label((string) ($reserva['uh_numero'] ?? '')));
            $paxReserva = (int) ($reserva['pax'] ?? 0);
            $chdReserva = (int) ($reserva['qtd_chd'] ?? 0);
            $paxReal = ($reserva['pax_real'] ?? null);
            $identReserva = restaurante_identidade((string) ($reserva['restaurante'] ?? ''));
            $nomeRestauranteCurto = (string) preg_replace('/^Restaurante\s+/iu', '', normalize_mojibake((string) ($reserva['restaurante'] ?? '')));
            $classes = 'fb-card fb-checkin-card';
            if (in_array($estado, ['finalizada', 'no_show', 'cancelada'], true)) {
                $classes .= ' is-muted';
            }
            ?>
            <article class="<?= h($classes) ?>" style="--fb-checkin-accent: <?= h($identReserva['cor']) ?>;">
                <div class="fb-checkin-card__head">
                    <div class="fb-checkin-card__identity">
                        <span class="fb-checkin-card__icon"><i class="bi <?= h($identReserva['icone']) ?>" aria-hidden="true"></i></span>
                        <div>
                            <p class="fb-checkin-card__title"><?= h($titular) ?></p>
                            <div class="fb-checkin-card__meta">
                                <span class="fb-badge fb-badge--outline"><?= h($uhDisplay) ?></span>
                                <span class="fb-badge fb-badge--outline"><?= $paxReserva ?> PAX<?= $chdReserva > 0 ? ' · ' . $chdReserva . ' CHD' : '' ?></span>
                                <span class="fb-badge fb-badge--outline"><?= h((string) ($reserva['turno_hora'] ?? '')) ?></span>
                                <span class="fb-badge fb-badge--outline"><?= h($nomeRestauranteCurto) ?></span>
                                <?php if ($grupoDisplay !== '' && $grupoDisplay !== '-'): ?>
                                    <span class="fb-badge fb-badge--outline">Grupo <?= h($grupoDisplay) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="fb-checkin-card__state">
                        <?php if ($estado === 'aguardando'): ?>
                            <span class="fb-badge fb-badge--day-use">Reservada</span>
                        <?php elseif ($estado === 'pre'): ?>
                            <span class="fb-badge fb-badge--warn">UH pendente</span>
                        <?php elseif ($estado === 'finalizada'): ?>
                            <span class="fb-badge fb-badge--ok">Finalizada</span>
                        <?php elseif ($estado === 'no_show'): ?>
                            <span class="fb-badge fb-badge--danger">No-show</span>
                        <?php else: ?>
                            <span class="fb-badge fb-badge--nao-informado">Cancelada</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="fb-checkin-card__body">
                    <?php if ($estado === 'finalizada' && $paxReal !== null && $paxReal !== ''): ?>
                        <div class="fb-checkin-card__note">
                            <strong>Entrada confirmada</strong>
                            <span><?= (int) $paxReal ?> PAX registrados no check-in.</span>
                        </div>
                    <?php endif; ?>

                    <?php if (!$closed && in_array($estado, ['aguardando', 'pre'], true)): ?>
                        <?php if ($ehPre): ?>
                            <div class="fb-checkin-card__note">
                                <strong>Pré-reserva sem UH</strong>
                                <span>Abra a conferência para vincular a habitação antes do check-in.</span>
                            </div>
                        <?php else: ?>
                            <details class="fb-checkin-card__details">
                                <summary class="fb-btn fb-btn--primary"><i class="bi bi-check2-circle"></i> Confirmar entrada</summary>
                                <form method="post" action="/?r=reservasTematicas/operacao" class="fb-checkin-inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="quick_status">
                                    <input type="hidden" name="quick_action" value="finalizar">
                                    <input type="hidden" name="id" value="<?= (int) $reserva['id'] ?>">
                                    <div>
                                        <label class="fb-label">PAX presentes</label>
                                        <input type="number" min="0" inputmode="numeric" class="fb-input" name="pax_real" value="<?= $paxReserva ?>">
                                    </div>
                                    <button type="submit" class="fb-btn fb-btn--primary">Salvar confirmação</button>
                                </form>
                            </details>
                        <?php endif; ?>

                        <details class="fb-checkin-card__details">
                            <summary class="fb-checkin-card__summary"><i class="bi bi-three-dots"></i> Ações complementares</summary>
                            <div class="fb-checkin-card__actions">
                                <form method="post" action="/?r=reservasTematicas/operacao">
                                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="quick_status">
                                    <input type="hidden" name="quick_action" value="nao_compareceu">
                                    <input type="hidden" name="id" value="<?= (int) $reserva['id'] ?>">
                                    <button type="submit" class="fb-btn fb-btn--danger" data-confirm-title="Confirmar no-show" data-confirm="Marcar a reserva de <?= h($titular) ?> como não compareceu?" data-confirm-yes="Sim, marcar" data-confirm-no="Voltar">Não compareceu</button>
                                </form>
                                <form method="post" action="/?r=reservasTematicas/operacao">
                                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="quick_status">
                                    <input type="hidden" name="quick_action" value="cancelar">
                                    <input type="hidden" name="id" value="<?= (int) $reserva['id'] ?>">
                                    <button type="submit" class="fb-btn" data-confirm-title="Cancelar reserva" data-confirm="Cancelar a reserva de <?= h($titular) ?>?" data-confirm-yes="Sim, cancelar" data-confirm-no="Voltar">Cancelar</button>
                                </form>
                                <a class="fb-btn fb-btn--ghost" href="/?<?= h(http_build_query(array_merge($queryFiltros, ['r' => 'reservasTematicas/conferencia', 'q' => (string) ($reserva['uh_numero'] ?? '')]))) ?>">Abrir na conferência</a>
                            </div>
                        </details>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if (empty($reservas)): ?>
            <div class="fb-card fb-empty">
                <i class="bi bi-calendar-heart"></i>
                <p class="fb-empty__title">Nenhuma reserva neste recorte</p>
                <p>Ajuste o filtro ou confira a data e o turno selecionados.</p>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($podeFecharTurno && !$closed && !empty($filters['restaurante_id']) && !empty($filters['turno_id'])): ?>
        <section class="fb-card fb-card--flat fb-checkin-close">
            <form method="post" action="/?r=reservasTematicas/operacao" class="fb-checkin-close__form">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="close_turno">
                <input type="hidden" name="restaurante_id" value="<?= (int) $filters['restaurante_id'] ?>">
                <input type="hidden" name="turno_id" value="<?= (int) $filters['turno_id'] ?>">
                <input type="hidden" name="data_reserva" value="<?= h($dataSelecionada) ?>">
                <div>
                    <p class="fb-card__eyebrow">Fechamento operacional</p>
                    <h2 class="fb-card__title">Encerrar este turno para novas alterações</h2>
                    <p class="fb-muted mb-0">Depois do fechamento, a hostess só consegue ajustar via conferência com justificativa.</p>
                </div>
                <button type="submit" class="fb-btn" data-confirm-title="Fechar turno" data-confirm="Fechar este turno para alterações?" data-confirm-yes="Fechar" data-confirm-no="Voltar"><i class="bi bi-lock"></i> Fechar turno</button>
            </form>
        </section>
    <?php endif; ?>
</div>
