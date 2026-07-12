<?php
$restaurantes = $this->data['restaurantes'] ?? [];
$turnos = $this->data['turnos'] ?? [];
$availability = $this->data['availability'] ?? [];
$filters = $this->data['filters'] ?? [];
$canReserve = (bool)($this->data['can_reserve'] ?? true);
$isHostess = (bool)($this->data['is_hostess'] ?? false);
$reservasDoTurno = $this->data['reservas_do_turno'] ?? [];

$user = Auth::user();
$podePreReserva = in_array((string)($user['perfil'] ?? ''), ['admin', 'supervisor', 'gerente'], true);
$tagsPadrao = ['Cortesia', 'Aniversário', 'Cupcake', 'Reclamação', 'Atenção especial', 'VIP', 'Restrição alimentar'];

$dataSelecionada = (string)($filters['data'] ?? date('Y-m-d'));
$restauranteSelecionadoId = (int)($filters['restaurante_id'] ?? 0);
$turnoSelecionadoId = (int)($filters['turno_id'] ?? 0);
$temSelecao = $restauranteSelecionadoId > 0 && $turnoSelecionadoId > 0;

$restauranteSelecionado = null;
foreach ($restaurantes as $rest) {
    if ((int)($rest['id'] ?? 0) === $restauranteSelecionadoId) {
        $restauranteSelecionado = $rest;
        break;
    }
}

$turnoSelecionado = null;
foreach ($turnos as $turno) {
    if ((int)($turno['id'] ?? 0) === $turnoSelecionadoId) {
        $turnoSelecionado = $turno;
        break;
    }
}

if ($temSelecao && (!$restauranteSelecionado || !$turnoSelecionado)) {
    $temSelecao = false;
}

$rotuloTurno = static fn(array $turno): string => (string)($turno['hora'] ?? $turno['nome'] ?? ('Turno ' . (int)($turno['id'] ?? 0)));
$rotuloTurnoCurto = static function (array $turno) use ($rotuloTurno): string {
    $rotulo = $rotuloTurno($turno);
    return preg_match('/^\d{2}:\d{2}/', $rotulo, $match) === 1 ? $match[0] : $rotulo;
};
$nomeCurtoRestaurante = static fn(array $rest): string => preg_replace('/^Restaurante\s+/iu', '', normalize_mojibake((string)($rest['nome'] ?? '')));
$tipoLabel = ['buffet' => 'Buffet', 'tematico' => 'Temático', 'area' => 'Área especial'];

$linkMapa = static function (string $data) : string {
    return '/?' . http_build_query([
        'r' => 'reservasTematicas/reservas',
        'data' => $data,
    ]);
};

$linkSelecionar = static function (int $restId, int $turnoId, string $data): string {
    return '/?' . http_build_query([
        'r' => 'reservasTematicas/reservas',
        'data' => $data,
        'restaurante_id' => $restId,
        'turno_id' => $turnoId,
    ]);
};

$classeStatusTurno = static function (array $info): string {
    $capacidade = (int)($info['capacidade'] ?? 0);
    $restante = (int)($info['restante'] ?? 0);
    $fechado = (bool)($info['fechado'] ?? false);

    if ($fechado) {
        return 'is-closed';
    }

    if ($capacidade > 0 && $restante <= 0) {
        return 'is-full';
    }

    if ($restante <= 10) {
        return 'is-warning';
    }

    return 'is-open';
};

$textoStatusTurno = static function (array $info): string {
    $capacidade = (int)($info['capacidade'] ?? 0);
    $restante = (int)($info['restante'] ?? 0);
    $fechado = (bool)($info['fechado'] ?? false);

    if ($fechado) {
        return 'Fechado';
    }

    if ($capacidade > 0 && $restante <= 0) {
        return 'Lotado';
    }

    if ($restante <= 10) {
        return 'Ultimas vagas';
    }

    return 'Disponivel';
};

$resumoMapa = [
    'restaurantes' => 0,
    'turnos' => 0,
    'capacidade' => 0,
    'livres' => 0,
    'fechados' => 0,
];
$resumoPorRestaurante = [];

foreach ($restaurantes as $rest) {
    $restId = (int)($rest['id'] ?? 0);
    $capacidadeTotal = 0;
    $livresTotal = 0;
    $turnosAtivos = 0;
    $turnosFechados = 0;

    foreach ($turnos as $turno) {
        $turnoId = (int)($turno['id'] ?? 0);
        $info = $availability[$restId][$turnoId] ?? null;
        if ($info === null) {
            continue;
        }

        $capacidade = (int)($info['capacidade'] ?? 0);
        $restante = (int)($info['restante'] ?? 0);
        $fechado = (bool)($info['fechado'] ?? false);

        if (!$fechado && $capacidade <= 0) {
            continue;
        }

        if ($fechado) {
            $turnosFechados++;
            $resumoMapa['fechados']++;
            continue;
        }

        $turnosAtivos++;
        $capacidadeTotal += $capacidade;
        $livresTotal += max(0, $restante);
        $resumoMapa['turnos']++;
        $resumoMapa['capacidade'] += $capacidade;
        $resumoMapa['livres'] += max(0, $restante);
    }

    $resumoPorRestaurante[$restId] = [
        'capacidade' => $capacidadeTotal,
        'livres' => $livresTotal,
        'reservadas' => max(0, $capacidadeTotal - $livresTotal),
        'turnos_ativos' => $turnosAtivos,
        'turnos_fechados' => $turnosFechados,
    ];

    if ($turnosAtivos > 0 || $turnosFechados > 0) {
        $resumoMapa['restaurantes']++;
    }
}

$disponibilidadeSelecionada = $temSelecao
    ? ($availability[$restauranteSelecionadoId][$turnoSelecionadoId] ?? null)
    : null;

$turnosRestauranteSelecionado = [];
if ($temSelecao && $restauranteSelecionado) {
    foreach ($turnos as $turno) {
        $turnoId = (int)($turno['id'] ?? 0);
        $info = $availability[$restauranteSelecionadoId][$turnoId] ?? null;
        if ($info === null) {
            continue;
        }

        $capacidade = (int)($info['capacidade'] ?? 0);
        $fechado = (bool)($info['fechado'] ?? false);
        if (!$fechado && $capacidade <= 0) {
            continue;
        }

        $turnosRestauranteSelecionado[] = [
            'id' => $turnoId,
            'rotulo' => $rotuloTurno($turno),
            'info' => $info,
        ];
    }
}
?>
<div class="tematico-reservar-page fb-reserve-page <?= $temSelecao ? 'fb-reserve-page--selected' : 'fb-reserve-page--selecting' ?>">
    <header class="fb-reserve-topline">
        <div>
            <p class="fb-card__eyebrow">Reservas temáticas · Passo <?= $temSelecao ? '2' : '1' ?> de 2</p>
            <h1><?= $temSelecao ? 'Cadastrar reserva' : 'Escolha um turno' ?></h1>
            <p><?= $temSelecao ? 'Preencha os dados abaixo. O contexto escolhido permanece fixo durante o cadastro.' : 'Selecione a data e toque em um turno disponível para avançar.' ?></p>
        </div>
        <a class="fb-btn fb-btn--ghost" href="/?<?= h(http_build_query(['r' => 'reservasTematicas/reservasCompleta', 'data' => $dataSelecionada])) ?>">
            <i class="bi bi-grid-3x3-gap"></i>
            Ambiente completo
        </a>
    </header>

    <?php if ($isHostess && !$canReserve): ?>
        <div class="fb-alert-inline fb-alert-inline--warn">
            <i class="bi bi-clock-history"></i>
            <div>
                <strong>Janela da hostess encerrada.</strong>
                <span>Consulte a supervisao para lancamentos fora do horario padrao.</span>
            </div>
        </div>
    <?php endif; ?>

    <section class="fb-reserve-datebar">
        <form method="get" action="/" class="fb-reserve-datebar__form" data-reserve-date-form>
            <input type="hidden" name="r" value="reservasTematicas/reservas">
            <div class="fb-reserve-datebar__field">
                <i class="bi bi-calendar3"></i>
                <div><span>Data da reserva</span><strong><?= h(format_date_br($dataSelecionada)) ?></strong></div>
                <input type="date" class="fb-input" name="data" value="<?= h($dataSelecionada) ?>" aria-label="Data da reserva" data-reserve-date-input>
            </div>
            <span class="fb-reserve-datebar__availability">
                <i class="bi bi-people"></i>
                <span><strong class="fb-num"><?= (int)$resumoMapa['livres'] ?></strong><small>vagas em <?= (int)$resumoMapa['restaurantes'] ?> restaurantes</small></span>
            </span>
            <?php if ($temSelecao): ?>
                <a class="fb-btn fb-btn--ghost" href="<?= h($linkMapa($dataSelecionada)) ?>">
                    <i class="bi bi-arrow-left"></i>
                    Alterar turno
                </a>
            <?php endif; ?>
        </form>
    </section>

    <?php if (!$temSelecao): ?>
        <section class="fb-reserve-grid">
            <?php foreach ($restaurantes as $rest): ?>
                <?php
                $restId = (int)($rest['id'] ?? 0);
                $ident = restaurante_identidade($rest);
                $nomeCurto = $nomeCurtoRestaurante($rest);
                $subtitulo = $tipoLabel[(string)($rest['tipo'] ?? '')] ?? 'Temático';
                $resumoRest = $resumoPorRestaurante[$restId] ?? ['capacidade' => 0, 'livres' => 0, 'reservadas' => 0, 'turnos_ativos' => 0, 'turnos_fechados' => 0];
                ?>
                <article
                    class="fb-reserve-board"
                    style="--fb-rest-color: <?= h($ident['cor']) ?>; --fb-rest-bg: <?= h($ident['bg']) ?>; --fb-rest-text: <?= h($ident['texto']) ?>;"
                >
                    <header class="fb-reserve-card__header">
                        <div class="fb-reserve-card__identity">
                            <span class="fb-reserve-card__icon"><i class="bi <?= h($ident['icone']) ?>" aria-hidden="true"></i></span>
                            <div class="fb-reserve-card__titles">
                                <p class="fb-reserve-card__name"><?= h($nomeCurto) ?></p>
                                <p class="fb-reserve-card__kind"><?= h($subtitulo) ?></p>
                            </div>
                        </div>
                        <div class="fb-reserve-card__status">
                            <span class="fb-badge fb-badge--ok"><?= (int)$resumoRest['livres'] ?> livres</span>
                        </div>
                    </header>

                    <?php
                    $ocupacaoRest = (int)$resumoRest['capacidade'] > 0
                        ? min(100, max(0, round(((int)$resumoRest['reservadas'] / (int)$resumoRest['capacidade']) * 100)))
                        : 0;
                    ?>
                    <div class="fb-reserve-card__overview">
                        <div>
                            <span>Ocupação atual</span>
                            <strong class="fb-num"><?= (int)$resumoRest['reservadas'] ?>/<?= (int)$resumoRest['capacidade'] ?></strong>
                        </div>
                        <div class="fb-reserve-card__progress" role="progressbar" aria-label="Ocupação de <?= h($nomeCurto) ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $ocupacaoRest ?>">
                            <span style="width: <?= $ocupacaoRest ?>%"></span>
                        </div>
                        <div class="fb-reserve-card__overview-meta">
                            <span><?= (int)$resumoRest['turnos_ativos'] ?> turnos ativos</span>
                            <?php if ((int)$resumoRest['turnos_fechados'] > 0): ?>
                                <span><?= (int)$resumoRest['turnos_fechados'] ?> fechado(s)</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="fb-reserve-card__turnos">
                        <?php foreach ($turnos as $turno): ?>
                            <?php
                            $turnoId = (int)($turno['id'] ?? 0);
                            $info = $availability[$restId][$turnoId] ?? null;
                            if ($info === null) {
                                continue;
                            }

                            $capacidade = (int)($info['capacidade'] ?? 0);
                            $restante = (int)($info['restante'] ?? 0);
                            $fechado = (bool)($info['fechado'] ?? false);
                            if (!$fechado && $capacidade <= 0) {
                                continue;
                            }

                            $tileClasses = 'fb-reserve-tile ' . $classeStatusTurno($info);
                            $tileBody = '
                                <span class="fb-reserve-tile__time fb-num">' . h($rotuloTurnoCurto($turno)) . '</span>
                                <strong class="fb-num">' . ($fechado ? '—' : (string)$restante) . '</strong>
                                <span class="fb-reserve-tile__status">' . h($fechado ? 'fechado' : ($restante <= 0 ? 'lotado' : 'vagas')) . '</span>
                            ';
                            ?>
                            <?php if (!$fechado && $canReserve): ?>
                                <a class="<?= $tileClasses ?>" href="<?= h($linkSelecionar($restId, $turnoId, $dataSelecionada)) ?>">
                                    <?= $tileBody ?>
                                </a>
                            <?php else: ?>
                                <div class="<?= $tileClasses ?>" aria-disabled="true">
                                    <?= $tileBody ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if (empty($restaurantes)): ?>
                <div class="fb-card fb-empty">
                    <i class="bi bi-calendar2-x"></i>
                    <p class="fb-empty__title">Nenhum restaurante disponivel para o seu perfil.</p>
                    <p class="mb-0">Revise o escopo do usuario ou a configuracao dos tematicos.</p>
                </div>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <?php
        $identSelecionado = restaurante_identidade($restauranteSelecionado);
        $resumoSelecionado = $resumoPorRestaurante[$restauranteSelecionadoId] ?? ['capacidade' => 0, 'livres' => 0, 'reservadas' => 0, 'turnos_ativos' => 0, 'turnos_fechados' => 0];
        $restanteSelecionado = (int)($disponibilidadeSelecionada['restante'] ?? 0);
        $capacidadeSelecionada = (int)($disponibilidadeSelecionada['capacidade'] ?? 0);
        $lotadoSelecionado = $capacidadeSelecionada > 0 && $restanteSelecionado <= 0;
        ?>
        <section class="fb-reserve-selected" style="--fb-rest-color: <?= h($identSelecionado['cor']) ?>; --fb-rest-bg: <?= h($identSelecionado['bg']) ?>; --fb-rest-text: <?= h($identSelecionado['texto']) ?>;">
            <header class="fb-reserve-selected__context">
                <span class="fb-reserve-context__icon"><i class="bi <?= h($identSelecionado['icone']) ?>"></i></span>
                <div class="fb-reserve-selected__identity">
                    <span>Turno selecionado</span>
                    <strong><?= h(normalize_mojibake((string)$restauranteSelecionado['nome'])) ?> · <?= h($rotuloTurno($turnoSelecionado)) ?></strong>
                    <small><?= h(format_date_br($dataSelecionada)) ?></small>
                </div>
                <div class="fb-reserve-selected__capacity">
                    <strong class="fb-num"><?= $restanteSelecionado ?></strong>
                    <span><?= $lotadoSelecionado ? 'turno lotado' : 'vagas livres' ?></span>
                    <small><?= max(0, $capacidadeSelecionada - $restanteSelecionado) ?>/<?= $capacidadeSelecionada ?> ocupadas</small>
                </div>
            </header>

            <section class="fb-card fb-reserve-form-stage">
                <header class="fb-reserve-form-stage__head">
                    <div>
                        <p class="fb-card__eyebrow">Cadastro</p>
                        <h2 id="reservaFormTitle">Reserva individual</h2>
                        <p id="reservaFormHint">Informe a UH, o titular e a quantidade de pessoas.</p>
                    </div>
                    <div class="fb-reserve-mode" role="group" aria-label="Formato da reserva">
                        <button type="button" class="is-active" data-reserve-mode-button="individual" aria-pressed="true"><i class="bi bi-person"></i> Individual</button>
                        <button type="button" data-reserve-mode-button="group" aria-pressed="false"><i class="bi bi-people"></i> Grupo</button>
                    </div>
                </header>

                <form method="post" action="/?r=reservasTematicas/reservas" class="fb-reserve-form" id="reservaCadastroForm" data-reserva-context="<?= h(normalize_mojibake((string)$restauranteSelecionado['nome'])) ?> · <?= h($rotuloTurno($turnoSelecionado)) ?>" data-reserva-date="<?= h(format_date_br($dataSelecionada)) ?>">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="action" value="create" id="reservaActionInput">
                    <input type="hidden" name="restaurante_id" value="<?= $restauranteSelecionadoId ?>">
                    <input type="hidden" name="turno_id" value="<?= $turnoSelecionadoId ?>">
                    <input type="hidden" name="data_reserva" value="<?= h($dataSelecionada) ?>">

                    <div data-reserve-mode-panel="individual">
                        <div class="fb-reserve-form__grid">
                            <div class="fb-field">
                                <label class="fb-label">UH <strong class="fb-required">*</strong></label>
                                <input type="text" inputmode="numeric" class="fb-input fb-input--big fb-input--identity fb-input--uh" name="uh_numero" id="reservaUhInput" required>
                            </div>
                            <div class="fb-field">
                                <label class="fb-label">Titular da reserva <strong class="fb-required">*</strong></label>
                                <input type="text" class="fb-input fb-input--identity" name="titular_nome" required>
                            </div>
                        </div>
                        <div class="fb-reserve-form__grid fb-reserve-form__grid--compact">
                            <div class="fb-field">
                                <label class="fb-label">PAX total <strong class="fb-required">*</strong></label>
                                <div class="fb-reserve-stepper">
                                    <button type="button" class="fb-stepper__btn" onclick="fbAjustarPax(-1)" aria-label="Diminuir PAX"><i class="bi bi-dash"></i></button>
                                    <input type="number" min="1" class="fb-input fb-num" name="pax" id="reservaPaxInput" value="2" required>
                                    <button type="button" class="fb-stepper__btn" onclick="fbAjustarPax(1)" aria-label="Aumentar PAX"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                            <div class="fb-field">
                                <label class="fb-label">Idades CHD</label>
                                <input type="text" class="fb-input" name="chd_idades" placeholder="Ex: 3y ou 3m">
                            </div>
                        </div>
                    </div>

                    <div data-reserve-mode-panel="group" hidden>
                        <div class="fb-reserve-form__grid">
                            <div class="fb-field"><label class="fb-label">Nome do grupo</label><input type="text" class="fb-input fb-input--identity" name="grupo_nome" placeholder="Ex: Família Costa" disabled></div>
                            <div class="fb-field"><label class="fb-label">Responsável <strong class="fb-required">*</strong></label><input type="text" class="fb-input fb-input--identity" name="grupo_responsavel" required disabled></div>
                        </div>
                        <div class="fb-reserve-batch-list" id="loteLinhas">
                            <div class="fb-reserve-batch-row" data-lote-linha>
                                <div class="fb-field"><label class="fb-label">UH <strong class="fb-required">*</strong></label><input type="text" inputmode="numeric" class="fb-input fb-input--identity fb-input--uh" name="batch_uh_numero[]" required disabled></div>
                                <div class="fb-field"><label class="fb-label">PAX <strong class="fb-required">*</strong></label><input type="number" min="1" class="fb-input" name="batch_pax[]" value="2" required disabled></div>
                                <div class="fb-field"><label class="fb-label">CHD</label><input type="text" class="fb-input" name="batch_chd_idades[]" placeholder="Ex: 3y ou 3m" disabled></div>
                            </div>
                        </div>
                        <button type="button" class="fb-btn fb-btn--ghost fb-reserve-add-uh" onclick="fbAdicionarLinhaLote()" disabled><i class="bi bi-plus"></i> Adicionar UH</button>
                    </div>

                    <div class="fb-field">
                        <label class="fb-label">Marcadores rápidos</label>
                        <div class="fb-reserve-tags">
                            <?php foreach ($tagsPadrao as $tag): ?>
                                <label class="fb-chip fb-chip--select"><input type="checkbox" name="observacao_tags[]" value="<?= h($tag) ?>"><span><?= h($tag) ?></span></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="fb-field"><label class="fb-label">Observações</label><input type="text" class="fb-input" name="observacao_reserva" placeholder="Detalhes operacionais para salão e cozinha"></div>

                    <?php if ($podePreReserva): ?>
                        <label class="fb-reserve-toggle" id="preReservaWrap"><input type="checkbox" id="preReservaToggle"><span>Registrar sem UH definida (pré-reserva)</span></label>
                    <?php endif; ?>

                    <button type="submit" class="fb-btn fb-btn--primary fb-btn--lg" <?= !$canReserve ? 'disabled' : '' ?>><i class="bi bi-check2-circle"></i><span id="reservaSubmitLabel">Confirmar reserva</span></button>
                </form>
            </section>

            <?php if (!empty($reservasDoTurno)): ?>
                <details class="fb-reserve-current">
                    <summary><span><i class="bi bi-people"></i> Conferir reservas deste turno</span><span class="fb-badge fb-badge--nao-informado"><?= count($reservasDoTurno) ?> reservas</span></summary>
                    <div class="fb-reserve-turn-list">
                        <?php foreach ($reservasDoTurno as $reservaTurno): ?>
                            <?php $statusTurno = mb_strtolower(normalize_mojibake((string)($reservaTurno['status_reserva'] ?? $reservaTurno['status'] ?? '')), 'UTF-8'); if (strpos($statusTurno, 'cancel') !== false) { continue; } $ehPreTurno = strpos($statusTurno, 'pre') === 0; ?>
                            <article class="fb-reserve-turn-list__item"><div class="fb-reserve-turn-list__body"><div class="fb-reserve-turn-list__head"><strong><?= $ehPreTurno ? 'UH pendente' : h(uh_label((string)($reservaTurno['uh_numero'] ?? ''))) ?></strong><span class="fb-badge <?= strpos($statusTurno, 'finaliz') !== false ? 'fb-badge--ok' : (strpos($statusTurno, 'compareceu') !== false ? 'fb-badge--danger' : 'fb-badge--day-use') ?>"><?= strpos($statusTurno, 'finaliz') !== false ? 'finalizada' : (strpos($statusTurno, 'compareceu') !== false ? 'no-show' : 'reservada') ?></span></div><p class="fb-reserve-turn-list__title"><?= h(normalize_mojibake((string)($reservaTurno['titular_nome_display'] ?? $reservaTurno['titular_nome'] ?? '-'))) ?></p><div class="fb-reserve-turn-list__meta"><span class="fb-badge fb-badge--nao-informado"><?= (int)($reservaTurno['pax'] ?? 0) ?> PAX</span></div></div></article>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<script>
function fbAjustarPax(delta) {
    var input = document.getElementById('reservaPaxInput');
    if (!input) { return; }
    input.value = Math.max(1, (parseInt(input.value, 10) || 1) + delta);
}

function fbAdicionarLinhaLote() {
    var wrap = document.getElementById('loteLinhas');
    if (!wrap) { return; }
    var modelo = wrap.querySelector('[data-lote-linha]');
    var clone = modelo.cloneNode(true);
    clone.querySelectorAll('input').forEach(function (campo) {
        campo.value = campo.name === 'batch_pax[]' ? '2' : '';
    });
    wrap.appendChild(clone);
}

(function () {
    var form = document.getElementById('reservaCadastroForm');
    var modeButtons = Array.prototype.slice.call(document.querySelectorAll('[data-reserve-mode-button]'));
    var modePanels = Array.prototype.slice.call(document.querySelectorAll('[data-reserve-mode-panel]'));
    var action = document.getElementById('reservaActionInput');
    var formTitle = document.getElementById('reservaFormTitle');
    var formHint = document.getElementById('reservaFormHint');
    var submitLabel = document.getElementById('reservaSubmitLabel');
    var preReservaWrap = document.getElementById('preReservaWrap');
    var toggle = document.getElementById('preReservaToggle');
    var uh = document.getElementById('reservaUhInput');

    function syncPreReserva() {
        if (!toggle || !uh || !action) { return; }
        if (toggle.checked) {
            action.value = 'create_pre_reservation';
            uh.value = '';
            uh.disabled = true;
            uh.required = false;
        } else {
            action.value = 'create';
            uh.disabled = false;
            uh.required = true;
        }
    }

    function setMode(mode) {
        var isGroup = mode === 'group';
        modeButtons.forEach(function (button) {
            var isActive = button.getAttribute('data-reserve-mode-button') === mode;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
        modePanels.forEach(function (panel) {
            var isActive = panel.getAttribute('data-reserve-mode-panel') === mode;
            panel.hidden = !isActive;
            panel.querySelectorAll('input, select, textarea, button').forEach(function (field) {
                field.disabled = !isActive;
            });
        });
        if (preReservaWrap) {
            preReservaWrap.hidden = isGroup;
        }
        if (toggle) {
            toggle.disabled = isGroup;
            if (isGroup) { toggle.checked = false; }
        }
        if (action) { action.value = isGroup ? 'create_batch' : 'create'; }
        if (formTitle) { formTitle.textContent = isGroup ? 'Reserva em grupo' : 'Reserva individual'; }
        if (formHint) { formHint.textContent = isGroup ? 'Adicione todas as UHs do grupo e confira o total antes de confirmar.' : 'Informe a UH, o titular e a quantidade de pessoas.'; }
        if (submitLabel) { submitLabel.textContent = isGroup ? 'Confirmar grupo' : 'Confirmar reserva'; }
        if (!isGroup) { syncPreReserva(); }
    }

    modeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            setMode(button.getAttribute('data-reserve-mode-button') || 'individual');
        });
    });

    if (toggle) {
        toggle.addEventListener('change', syncPreReserva);
    }

    var dateForm = document.querySelector('[data-reserve-date-form]');
    var dateInput = document.querySelector('[data-reserve-date-input]');
    if (dateForm && dateInput) {
        dateInput.addEventListener('change', function () { dateForm.submit(); });
    }

    if (!form) { return; }
    setMode('individual');

    form.addEventListener('submit', function () {
        // Mantém a ação correta mesmo se o formulário for enviado pelo teclado.
        var active = document.querySelector('[data-reserve-mode-button].is-active');
        if (active && active.getAttribute('data-reserve-mode-button') === 'group' && action) {
            action.value = 'create_batch';
        }
    });

})();

(function () {
    var form = document.getElementById('reservaCadastroForm');
    if (!form || !window.fetch) { return; }

    function activeMode() {
        var active = document.querySelector('[data-reserve-mode-button].is-active');
        return active && active.getAttribute('data-reserve-mode-button') === 'group' ? 'group' : 'individual';
    }

    function reservationDetails(formData, payload) {
        var isGroup = activeMode() === 'group';
        var pax = 0;
        if (isGroup) {
            formData.getAll('batch_pax[]').forEach(function (value) { pax += parseInt(String(value), 10) || 0; });
        } else {
            pax = parseInt(String(formData.get('pax') || '0'), 10) || 0;
        }
        var reference = payload && payload.correlation_id ? String(payload.correlation_id) : '';
        return [
            { label: 'Turno', value: form.dataset.reservaContext || '' },
            { label: 'Data', value: form.dataset.reservaDate || '' },
            { label: isGroup ? 'Formato' : 'UH', value: isGroup ? 'Grupo · ' + formData.getAll('batch_uh_numero[]').filter(Boolean).length + ' UHs' : String(formData.get('uh_numero') || 'Pré-reserva') },
            { label: 'PAX informado', value: pax > 0 ? String(pax) + ' pessoas' : '' },
            { label: 'Referência', value: reference }
        ];
    }

    function alertReservation(type, title, message, details, buttonText) {
        if (window.fbAlerts && typeof window.fbAlerts.modal === 'function') {
            return window.fbAlerts.modal({
                type: type,
                title: title,
                message: message,
                details: details,
                buttonText: buttonText,
                variant: 'reservation'
            });
        }
        window.alert(message);
        return Promise.resolve();
    }

    function plainTextFromHtml(html) {
        var doc;
        try {
            doc = new DOMParser().parseFromString(String(html || ''), 'text/html');
        } catch (e) {
            return '';
        }
        var title = doc.querySelector('.app-alert-title, .alert, h1, h2, title');
        return title ? String(title.textContent || '').trim().replace(/\s+/g, ' ') : '';
    }

    function parseReservationResponse(response) {
        var contentType = String(response.headers.get('content-type') || '').toLowerCase();
        if (contentType.indexOf('application/json') !== -1) {
            return response.json();
        }

        return response.text().then(function (text) {
            var readable = plainTextFromHtml(text);
            var lower = String(text || '').toLowerCase();

            if (response.status === 401 || lower.indexOf('auth/login') !== -1 || lower.indexOf('tela de login') !== -1) {
                return {
                    ok: false,
                    type: 'danger',
                    code: 'sessao_expirada',
                    message: 'Sua sessão expirou ou foi encerrada. Entre novamente antes de registrar a reserva.',
                    redirect: '/?r=auth/login'
                };
            }

            if (response.status === 403 || lower.indexOf('errors/forbidden') !== -1 || lower.indexOf('acesso não autorizado') !== -1) {
                return {
                    ok: false,
                    type: 'danger',
                    code: 'acesso_negado',
                    message: 'Seu usuário não tem permissão para concluir esta ação.',
                    redirect: '/?r=errors/forbidden'
                };
            }

            return {
                ok: false,
                type: 'danger',
                code: 'resposta_invalida',
                message: readable || 'O servidor respondeu em um formato inesperado. Atualize a página e tente novamente.'
            };
        });
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!form.reportValidity()) { return; }

        var submit = form.querySelector('button[type="submit"]');
        var originalHtml = submit ? submit.innerHTML : '';
        var formData = new FormData(form);
        if (submit) {
            submit.disabled = true;
            submit.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Registrando...';
        }

        // form.action é sombreado pelo <input name="action">, retornando o elemento
        // em vez da URL. getAttribute garante a rota correta (senão: 404 NOT FOUND).
        fetch(form.getAttribute('action'), {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'fetch'
            },
            credentials: 'same-origin'
        })
            .then(parseReservationResponse)
            .then(function (result) {
                var payload = result && result.payload ? result.payload : {};
                var details = reservationDetails(formData, payload);
                if (!result || !result.ok) {
                    return alertReservation(
                        result && result.type === 'warning' ? 'warning' : 'danger',
                        'Reserva não realizada',
                        result && result.message ? result.message : 'Revise os dados e tente novamente.',
                        details,
                        'Corrigir dados'
                    ).then(function () {
                        if (result && result.redirect && ['sessao_expirada', 'acesso_negado'].indexOf(result.code || '') !== -1) {
                            window.location.assign(result.redirect);
                        }
                    });
                }

                return alertReservation(
                    'success',
                    activeMode() === 'group' ? 'Grupo registrado com sucesso' : 'Reserva registrada com sucesso',
                    result.message || 'A reserva foi salva e já está disponível para conferência.',
                    details,
                    'Registrar outra reserva'
                ).then(function () {
                    window.location.assign(result.redirect || window.location.href);
                });
            })
            .catch(function () {
                return alertReservation(
                    'danger',
                    'Não foi possível concluir a reserva',
                    'A conexão foi interrompida antes da confirmação. Os dados preenchidos foram mantidos para nova tentativa.',
                    reservationDetails(formData, {}),
                    'Corrigir dados'
                );
            })
            .finally(function () {
                if (submit) {
                    submit.disabled = false;
                    submit.innerHTML = originalHtml;
                }
            });
    });
})();
</script>
