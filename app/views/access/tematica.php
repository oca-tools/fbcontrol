<?php
/* Registro Temático da hostess (remake: identidade 3.0 + fila de bilhetes).
   Mesmo backend de sempre: POST /?r=access/register_tematica com
   reserva_id + acao_tematica (confirmar|cancelar) + data_ref + pax_real + observacao_operacao.
   Busca e chips filtram no cliente via fb-checkin.js (data-busca/data-estado nos bilhetes). */
$flash = $this->data['flash'] ?? null;
$turno = $this->data['turno'] ?? null;
$restOp = $this->data['restOp'] ?? null;
$reservas = $this->data['reservas'] ?? [];
$filters = $this->data['filters'] ?? [];
$toleranceAlert = $this->data['tolerance_alert'] ?? null;
$canCancel = (bool)($this->data['can_cancel'] ?? false);

$finalStatuses = ['Finalizada', 'Nao compareceu', 'Cancelada'];

$normalizeStatus = static function (?string $status): string {
    $status = normalize_mojibake(trim((string)$status));
    $map = [
        'Não compareceu' => 'Nao compareceu',
        'Divergência' => 'Divergencia',
        'Conferida' => 'Reservada',
        'Em atendimento' => 'Reservada',
    ];
    return $map[$status] ?? $status;
};
$estadoDe = static function (string $canon): string {
    return [
        'Finalizada' => 'finalizada',
        'Nao compareceu' => 'no_show',
        'Cancelada' => 'cancelada',
        'Divergencia' => 'divergencia',
    ][$canon] ?? 'aguardando';
};
$rowHorario = static function ($hora): string {
    $hora = trim((string)$hora);
    return $hora === '' ? '--:--' : substr($hora, 0, 5);
};

$identTem = restaurante_identidade($turno['restaurante'] ?? '');
$nomeTem = (string)preg_replace('/^Restaurante\s+/iu', '', normalize_mojibake((string)($turno['restaurante'] ?? 'Restaurante')));
$operacaoTem = normalize_mojibake((string)($turno['operacao'] ?? 'Temático'));
$janelaTem = '';
if (!empty($restOp['hora_inicio']) && !empty($restOp['hora_fim'])) {
    $janelaTem = substr((string)$restOp['hora_inicio'], 0, 5) . ' – ' . substr((string)$restOp['hora_fim'], 0, 5);
}
$dataRef = (string)($filters['data'] ?? date('Y-m-d'));

$contagem = ['aguardando' => 0, 'finalizada' => 0, 'no_show' => 0, 'cancelada' => 0, 'divergencia' => 0];
foreach ($reservas as $rowContagem) {
    $estadoContagem = $estadoDe($normalizeStatus((string)($rowContagem['status_reserva'] ?? ($rowContagem['status'] ?? ''))));
    $contagem[$estadoContagem] = ($contagem[$estadoContagem] ?? 0) + 1;
}
?>

<div class="tematico-registro-page fb-checkin-page">
    <section class="fb-card fb-card--flat fb-tematica-head">
        <div class="fb-rest-hero">
            <span class="fb-rest-hero__icon" style="--rest-cor: <?= h($identTem['cor']) ?>;"><i class="bi <?= h($identTem['icone']) ?>" aria-hidden="true"></i></span>
            <div>
                <span class="fb-rest-hero__eyebrow">Você está em</span>
                <span class="fb-rest-hero__name"><?= h($nomeTem) ?></span>
                <span class="fb-rest-hero__op"><i class="bi bi-clock-history" aria-hidden="true"></i> <?= h($operacaoTem) ?><?= $janelaTem !== '' ? ' · ' . h($janelaTem) : '' ?></span>
            </div>
        </div>
        <div class="fb-page-head__actions">
            <form method="post" action="/?r=turnos/end">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <button class="fb-btn fb-btn--danger" data-confirm="Confirma encerramento do turno?" data-confirm-title="Encerrar turno" data-confirm-type="danger"><i class="bi bi-box-arrow-right"></i> Encerrar turno</button>
            </form>
            <?php if ($canCancel): ?>
                <form method="post" action="/?r=turnos/cancel">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <button class="fb-btn" data-confirm="Confirma cancelamento do turno sem registros?" data-confirm-title="Cancelar turno"><i class="bi bi-x-circle"></i> Cancelar turno</button>
                </form>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($toleranceAlert): ?>
        <div class="app-inline-note is-warning"><?= h($toleranceAlert) ?></div>
    <?php endif; ?>

    <section class="fb-card fb-card--flat fb-checkin-searchbar">
        <div class="fb-checkin-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="text" data-fb-checkin-search placeholder="Buscar por UH ou titular…" autocomplete="off" inputmode="numeric" aria-label="Buscar reserva">
            <span class="fb-checkin-search__count" data-fb-checkin-count aria-live="polite"></span>
        </div>
        <details class="fb-checkin-recorte">
            <summary class="fb-btn fb-btn--ghost"><i class="bi bi-sliders"></i> Trocar data</summary>
            <form method="get" action="/" class="fb-checkin-recorte__form">
                <input type="hidden" name="r" value="access/index">
                <div class="fb-checkin-toolbar__grid">
                    <input type="date" class="fb-input" name="data" value="<?= h($dataRef) ?>">
                </div>
                <div class="fb-reserve-toolbar__controls">
                    <button type="submit" class="fb-btn">Aplicar</button>
                    <a class="fb-btn fb-btn--ghost" href="/?r=access/index">Hoje</a>
                </div>
            </form>
        </details>
    </section>

    <section class="fb-card fb-card--flat fb-checkin-statusbar">
        <div class="fb-checkin-statusbar__chips" data-fb-checkin-chips>
            <button type="button" class="fb-chip fb-chip--active" data-status-filter="">Todas <?= count($reservas) ?></button>
            <button type="button" class="fb-chip" data-status-filter="aguardando">Reservadas <?= (int)$contagem['aguardando'] ?></button>
            <button type="button" class="fb-chip" data-status-filter="finalizada">Finalizadas <?= (int)$contagem['finalizada'] ?></button>
            <button type="button" class="fb-chip" data-status-filter="no_show">Não compareceu <?= (int)$contagem['no_show'] ?></button>
            <?php if ($contagem['divergencia'] > 0): ?>
                <button type="button" class="fb-chip" data-status-filter="divergencia">Divergência <?= (int)$contagem['divergencia'] ?></button>
            <?php endif; ?>
            <?php if ($contagem['cancelada'] > 0): ?>
                <button type="button" class="fb-chip" data-status-filter="cancelada">Canceladas <?= (int)$contagem['cancelada'] ?></button>
            <?php endif; ?>
        </div>
    </section>

    <section class="fb-checkin-list">
        <?php if (empty($reservas)): ?>
            <div class="fb-card fb-empty">
                <i class="bi bi-calendar-heart"></i>
                <p class="fb-empty__title">Nenhuma reserva nesta data</p>
                <p>Confira a data selecionada ou aguarde novas reservas.</p>
            </div>
        <?php endif; ?>

        <div class="fb-card fb-empty" data-fb-checkin-noresults hidden>
            <i class="bi bi-search"></i>
            <p class="fb-empty__title">Nada encontrado</p>
            <p>Nenhuma reserva bate com a busca ou o filtro atual.</p>
        </div>

        <?php foreach ($reservas as $reserva): ?>
            <?php
            $canonStatus = $normalizeStatus((string)($reserva['status_reserva'] ?? ($reserva['status'] ?? '')));
            $estado = $estadoDe($canonStatus);
            $ehFinal = in_array($canonStatus, $finalStatuses, true);
            $uhReserva = uh_label((string)($reserva['uh_numero'] ?? '-'));
            $titularReserva = normalize_mojibake((string)($reserva['titular_nome'] ?? '-'));
            $paxReserva = (int)($reserva['pax'] ?? 0);
            $horaReserva = $rowHorario($reserva['turno_hora'] ?? '');
            $buscaReserva = mb_strtolower(trim($uhReserva . ' ' . $titularReserva), 'UTF-8');
            $bilheteClasses = 'fb-bilhete';
            if ($ehFinal) {
                $bilheteClasses .= ' is-muted';
            }
            ?>
            <article class="<?= h($bilheteClasses) ?>" style="--fb-bilhete-accent: <?= h($identTem['cor']) ?>;" data-estado="<?= h($estado) ?>" data-busca="<?= h($buscaReserva) ?>">
                <div class="fb-bilhete__rail">
                    <span class="fb-bilhete__hora"><?= h($horaReserva) ?></span>
                    <i class="bi <?= h($identTem['icone']) ?> fb-bilhete__railicon" aria-hidden="true"></i>
                </div>
                <div class="fb-bilhete__main">
                    <div class="fb-bilhete__uhrow">
                        <span class="fb-bilhete__uh"><?= h($uhReserva) ?></span>
                        <span class="fb-bilhete__titular"><?= h($titularReserva) ?></span>
                    </div>
                    <div class="fb-bilhete__chips">
                        <span class="fb-tag"><i class="bi bi-person" aria-hidden="true"></i> <?= $paxReserva ?> PAX</span>
                        <?php if ($estado === 'finalizada' && ($reserva['pax_real'] ?? null) !== null && (string)$reserva['pax_real'] !== ''): ?>
                            <span class="fb-tag"><i class="bi bi-check2" aria-hidden="true"></i> <?= (int)$reserva['pax_real'] ?> entraram</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="fb-bilhete__stub">
                    <?php if ($estado === 'aguardando'): ?>
                        <span class="fb-badge fb-badge--day-use">Reservada</span>
                    <?php elseif ($estado === 'finalizada'): ?>
                        <span class="fb-badge fb-badge--ok">Finalizada</span>
                    <?php elseif ($estado === 'no_show'): ?>
                        <span class="fb-badge fb-badge--solid-danger">Não compareceu</span>
                    <?php elseif ($estado === 'cancelada'): ?>
                        <span class="fb-badge fb-badge--solid-neutral">Cancelada</span>
                    <?php else: ?>
                        <span class="fb-badge fb-badge--danger">Divergência</span>
                    <?php endif; ?>
                </div>

                <?php if (!$ehFinal): ?>
                    <div class="fb-bilhete__foot">
                        <div class="fb-tematica-actions">
                            <form method="post" action="/?r=access/register_tematica" class="fb-tematica-confirm">
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="reserva_id" value="<?= (int)$reserva['id'] ?>">
                                <input type="hidden" name="acao_tematica" value="confirmar">
                                <input type="hidden" name="data_ref" value="<?= h($dataRef) ?>">
                                <div class="fb-checkin-pax">
                                    <label class="fb-label" for="paxreal-<?= (int)$reserva['id'] ?>">PAX real</label>
                                    <input type="number" min="0" max="<?= $paxReserva ?>" inputmode="numeric" class="fb-input" id="paxreal-<?= (int)$reserva['id'] ?>" name="pax_real" value="<?= $paxReserva ?>">
                                </div>
                                <div class="fb-tematica-obs">
                                    <label class="fb-label" for="obs-<?= (int)$reserva['id'] ?>">Observação</label>
                                    <input type="text" class="fb-input" id="obs-<?= (int)$reserva['id'] ?>" name="observacao_operacao" placeholder="Opcional">
                                </div>
                                <button type="submit" class="fb-btn fb-btn--primary fb-checkin-confirm"><i class="bi bi-check2-circle"></i> Confirmar entrada</button>
                            </form>
                            <form method="post" action="/?r=access/register_tematica" class="fb-tematica-noshow">
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="reserva_id" value="<?= (int)$reserva['id'] ?>">
                                <input type="hidden" name="acao_tematica" value="cancelar">
                                <input type="hidden" name="data_ref" value="<?= h($dataRef) ?>">
                                <button type="submit" class="fb-btn fb-btn--ghost" data-confirm-title="Confirmar no-show" data-confirm="Marcar a reserva de <?= h($titularReserva) ?> (UH <?= h($uhReserva) ?>) como não compareceu?" data-confirm-yes="Sim, marcar" data-confirm-no="Voltar"><i class="bi bi-person-x"></i> Não compareceu</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>
</div>
