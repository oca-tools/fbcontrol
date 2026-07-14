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

/* Passo 4 (reservas-bilhete): agrupa as reservas por turno para a fila com cabeçalho-medidor. */
$gruposReserva = [];
foreach ($reservas as $reservaRow) {
    $horaTurno = trim((string) ($reservaRow['turno_hora'] ?? ''));
    $chaveTurno = $horaTurno !== '' ? $horaTurno : 'sem-turno';
    if (!isset($gruposReserva[$chaveTurno])) {
        $gruposReserva[$chaveTurno] = [
            'hora' => $horaTurno,
            'reservas' => [],
            'pax_ativos' => 0,
            'no_show' => 0,
            'aguardando' => 0,
            'restaurantes' => [],
        ];
    }
    $estadoRow = $normalizarStatusCard($reservaRow);
    $gruposReserva[$chaveTurno]['reservas'][] = $reservaRow;
    if (in_array($estadoRow, ['aguardando', 'pre', 'finalizada'], true)) {
        $gruposReserva[$chaveTurno]['pax_ativos'] += (int) ($reservaRow['pax'] ?? 0);
    }
    if ($estadoRow === 'no_show') {
        $gruposReserva[$chaveTurno]['no_show']++;
    }
    if (in_array($estadoRow, ['aguardando', 'pre'], true)) {
        $gruposReserva[$chaveTurno]['aguardando']++;
    }
    $nomeRest = normalize_mojibake((string) ($reservaRow['restaurante'] ?? ''));
    if ($nomeRest !== '') {
        $gruposReserva[$chaveTurno]['restaurantes'][$nomeRest] = true;
    }
}
ksort($gruposReserva);
$turnoUnicoComCapacidade = (count($gruposReserva) === 1 && $percentualLotacao !== null);

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

    <section class="fb-card fb-card--flat fb-checkin-searchbar">
        <div class="fb-checkin-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="text" data-fb-checkin-search placeholder="Buscar por UH ou titular…" autocomplete="off" aria-label="Buscar reserva" value="<?= h((string) ($filters['q'] ?? '')) ?>">
            <span class="fb-checkin-search__count" data-fb-checkin-count aria-live="polite"></span>
        </div>
        <details class="fb-checkin-recorte">
            <summary class="fb-btn fb-btn--ghost"><i class="bi bi-sliders"></i> Trocar recorte</summary>
            <form method="get" action="/" class="fb-checkin-recorte__form">
                <input type="hidden" name="r" value="reservasTematicas/operacao">
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
                </div>
                <div class="fb-reserve-toolbar__controls">
                    <button type="submit" class="fb-btn fb-btn--primary">Aplicar recorte</button>
                    <a class="fb-btn fb-btn--ghost" href="/?r=reservasTematicas/operacao">Hoje</a>
                </div>
            </form>
        </details>
    </section>

    <section class="fb-card fb-card--flat fb-checkin-statusbar">
        <div class="fb-checkin-statusbar__chips" data-fb-checkin-chips>
            <button type="button" class="fb-chip fb-chip--active" data-status-filter="">Todas <?= (int) ($summary['total'] ?? 0) ?></button>
            <button type="button" class="fb-chip" data-status-filter="aguardando">Aguardando <?= (int) ($summary['reservada'] ?? 0) ?></button>
            <?php if ((int) ($summary['pre_reserva'] ?? 0) > 0): ?>
                <button type="button" class="fb-chip" data-status-filter="pre">UH pendente <?= (int) $summary['pre_reserva'] ?></button>
            <?php endif; ?>
            <button type="button" class="fb-chip" data-status-filter="finalizada">Finalizadas <?= (int) ($summary['finalizada'] ?? 0) ?></button>
            <button type="button" class="fb-chip" data-status-filter="no_show">No-show <?= (int) ($summary['nao_compareceu'] ?? 0) ?></button>
            <button type="button" class="fb-chip" data-status-filter="cancelada">Canceladas <?= (int) ($summary['cancelada'] ?? 0) ?></button>
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
        <?php if (empty($reservas)): ?>
            <div class="fb-card fb-empty">
                <i class="bi bi-calendar-heart"></i>
                <p class="fb-empty__title">Nenhuma reserva neste recorte</p>
                <p>Ajuste o recorte ou confira a data e o turno selecionados.</p>
            </div>
        <?php endif; ?>

        <div class="fb-card fb-empty" data-fb-checkin-noresults hidden>
            <i class="bi bi-search"></i>
            <p class="fb-empty__title">Nada encontrado</p>
            <p>Nenhuma reserva bate com a busca ou o filtro atual.</p>
        </div>

        <?php foreach ($gruposReserva as $grupo): ?>
            <?php
            $grupoReservas = $grupo['reservas'];
            $grupoHora = $grupo['hora'] !== '' ? $grupo['hora'] : 'Sem turno';
            $grupoRestNomes = array_keys($grupo['restaurantes']);
            $grupoRestUnico = count($grupoRestNomes) === 1 ? (string) $grupoRestNomes[0] : '';
            $grupoIdent = $grupoRestUnico !== '' ? restaurante_identidade($grupoRestUnico) : null;
            $grupoAccent = $grupoIdent['cor'] ?? 'var(--fb-ink)';
            $grupoLabel = $grupoRestUnico !== ''
                ? (string) preg_replace('/^Restaurante\s+/iu', '', $grupoRestUnico)
                : (count($grupoRestNomes) . ' restaurantes');
            $lugaresLivres = $turnoUnicoComCapacidade ? max(0, (int) $capacidadeTurno - $paxAtivos) : null;
            ?>
            <div class="fb-turno-group">
                <div class="fb-turno-group__head" style="--fb-turno-accent: <?= h($grupoAccent) ?>;">
                    <p class="fb-turno-group__title">
                        <i class="bi <?= h($grupoIdent['icone'] ?? 'bi-clock') ?>" aria-hidden="true"></i>
                        <?= h($grupoHora) ?> · <?= h($grupoLabel) ?>
                    </p>
                    <?php if ($turnoUnicoComCapacidade): ?>
                        <span class="fb-turno-group__count"><?= number_format($paxAtivos, 0, ',', '.') ?><span>/<?= number_format((int) $capacidadeTurno, 0, ',', '.') ?> PAX</span></span>
                        <div class="fb-turno-group__meter"><i style="width: <?= $percentualLotacao ?>%;"></i></div>
                        <div class="fb-turno-group__sub">
                            <span><?= count($grupoReservas) ?> reservas · <?= (int) $grupo['aguardando'] ?> aguardando</span>
                            <span><?= $lugaresLivres ?> lugares livres</span>
                        </div>
                    <?php else: ?>
                        <span class="fb-turno-group__count"><?= number_format((int) $grupo['pax_ativos'], 0, ',', '.') ?><span> PAX · <?= count($grupoReservas) ?> reservas</span></span>
                        <div class="fb-turno-group__sub">
                            <span><?= (int) $grupo['aguardando'] ?> aguardando</span>
                            <?php if ((int) $grupo['no_show'] > 0): ?><span><?= (int) $grupo['no_show'] ?> no-show</span><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php foreach ($grupoReservas as $reserva): ?>
                    <?php
                    $estado = $normalizarStatusCard($reserva);
                    $ehPre = $estado === 'pre';
                    $titular = normalize_mojibake((string) ($reserva['titular_nome_display'] ?? $reserva['titular_nome'] ?? '-'));
                    $grupoDisplay = trim(normalize_mojibake((string) ($reserva['grupo_nome_display'] ?? $reserva['grupo_nome'] ?? '')));
                    $uhNumero = uh_label((string) ($reserva['uh_numero'] ?? ''));
                    $paxReserva = (int) ($reserva['pax'] ?? 0);
                    $chdReserva = (int) ($reserva['qtd_chd'] ?? 0);
                    $paxReal = ($reserva['pax_real'] ?? null);
                    $identReserva = restaurante_identidade((string) ($reserva['restaurante'] ?? ''));
                    $nomeRestauranteCurto = (string) preg_replace('/^Restaurante\s+/iu', '', normalize_mojibake((string) ($reserva['restaurante'] ?? '')));
                    $acionavel = !$closed && in_array($estado, ['aguardando', 'pre'], true);
                    $bilheteClasses = 'fb-bilhete';
                    if (in_array($estado, ['finalizada', 'no_show', 'cancelada'], true)) {
                        $bilheteClasses .= ' is-muted';
                    }
                    $buscaBilhete = mb_strtolower(trim($uhNumero . ' ' . $titular . ' ' . $grupoDisplay . ' ' . $nomeRestauranteCurto), 'UTF-8');
                    ?>
                    <article class="<?= h($bilheteClasses) ?>" style="--fb-bilhete-accent: <?= h($identReserva['cor']) ?>;" data-estado="<?= h($estado) ?>" data-busca="<?= h($buscaBilhete) ?>">
                        <div class="fb-bilhete__rail">
                            <span class="fb-bilhete__hora"><?= h((string) ($reserva['turno_hora'] ?? $grupoHora)) ?></span>
                            <i class="bi <?= h($identReserva['icone']) ?> fb-bilhete__railicon" aria-hidden="true"></i>
                        </div>
                        <div class="fb-bilhete__main">
                            <div class="fb-bilhete__uhrow">
                                <?php if ($ehPre): ?>
                                    <span class="fb-bilhete__uh fb-bilhete__uh--pending">UH?</span>
                                <?php else: ?>
                                    <span class="fb-bilhete__uh"><?= h($uhNumero) ?></span>
                                <?php endif; ?>
                                <span class="fb-bilhete__titular"><?= h($titular) ?></span>
                            </div>
                            <div class="fb-bilhete__chips">
                                <span class="fb-tag"><i class="bi bi-person" aria-hidden="true"></i> <?= $paxReserva ?> PAX</span>
                                <?php if ($chdReserva > 0): ?>
                                    <span class="fb-tag"><i class="bi bi-emoji-smile" aria-hidden="true"></i> <?= $chdReserva ?> CHD</span>
                                <?php endif; ?>
                                <?php if ($grupoRestUnico === ''): ?>
                                    <span class="fb-tag"><?= h($nomeRestauranteCurto) ?></span>
                                <?php endif; ?>
                                <?php if ($grupoDisplay !== '' && $grupoDisplay !== '-'): ?>
                                    <span class="fb-tag"><i class="bi bi-people" aria-hidden="true"></i> <?= h($grupoDisplay) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="fb-bilhete__stub">
                            <?php if ($estado === 'aguardando'): ?>
                                <span class="fb-badge fb-badge--day-use">Reservada</span>
                            <?php elseif ($estado === 'pre'): ?>
                                <span class="fb-badge fb-badge--warn">UH pendente</span>
                            <?php elseif ($estado === 'finalizada'): ?>
                                <span class="fb-badge fb-badge--ok">Finalizada</span>
                                <?php if ($paxReal !== null && $paxReal !== ''): ?>
                                    <span class="fb-tag"><?= (int) $paxReal ?> entraram</span>
                                <?php endif; ?>
                            <?php elseif ($estado === 'no_show'): ?>
                                <span class="fb-badge fb-badge--solid-danger">No-show</span>
                            <?php else: ?>
                                <span class="fb-badge fb-badge--solid-neutral">Cancelada</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($acionavel): ?>
                            <div class="fb-bilhete__foot">
                                <?php if ($ehPre): ?>
                                    <div class="fb-checkin-card__note">
                                        <strong>Pré-reserva sem UH</strong>
                                        <span>Abra a conferência para vincular a habitação antes do check-in.</span>
                                    </div>
                                <?php else: ?>
                                    <form method="post" action="/?r=reservasTematicas/operacao" class="fb-checkin-inline-form">
                                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="quick_status">
                                        <input type="hidden" name="quick_action" value="finalizar">
                                        <input type="hidden" name="id" value="<?= (int) $reserva['id'] ?>">
                                        <div class="fb-checkin-pax">
                                            <label class="fb-label" for="pax-<?= (int) $reserva['id'] ?>">PAX presentes</label>
                                            <input type="number" min="0" inputmode="numeric" class="fb-input" id="pax-<?= (int) $reserva['id'] ?>" name="pax_real" value="<?= $paxReserva ?>">
                                        </div>
                                        <button type="submit" class="fb-btn fb-btn--primary fb-checkin-confirm"><i class="bi bi-check2-circle"></i> Confirmar entrada</button>
                                    </form>
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
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
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
