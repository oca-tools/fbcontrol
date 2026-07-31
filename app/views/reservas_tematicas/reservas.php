<?php
$restaurantes = $this->data['restaurantes'] ?? [];
$turnos = $this->data['turnos'] ?? [];
$availability = $this->data['availability'] ?? [];
$filters = $this->data['filters'] ?? [];
$canReserve = (bool)($this->data['can_reserve'] ?? true);
$isHostess = (bool)($this->data['is_hostess'] ?? false);
$reservasDoTurno = $this->data['reservas_do_turno'] ?? [];
$minhasReservasEditaveis = $this->data['minhas_reservas_editaveis'] ?? [];

$user = Auth::user();
$podePreReserva = in_array((string)($user['perfil'] ?? ''), ['admin', 'supervisor', 'gerente'], true);
$tagsPadrao = ['Cortesia', 'Aniversário', 'Cupcake', 'Reclamação', 'Atenção especial', 'VIP', 'Restrição alimentar'];
$identidadesRestaurantes = [];
foreach ($restaurantes as $restauranteIdentidade) {
    $identidadesRestaurantes[(int)($restauranteIdentidade['id'] ?? 0)] = restaurante_identidade($restauranteIdentidade);
}

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
$subtituloReservaTematica = static function (array $rest): string {
    // O tipo cadastral pode ser buffet por causa do almoço; neste módulo, La Brasa é temática à noite.
    return TematicAccessService::isLaBrasa((string)($rest['nome'] ?? '')) ? 'Temático noturno' : 'Temático';
};

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
        <div class="fb-reserve-topline__actions">
            <?php if ($isHostess): ?>
                <button type="button" class="fb-btn fb-btn--my-reservations" data-editable-reservations-open>
                    <i class="bi bi-pencil-square"></i>
                    Minhas reservas
                    <?php if (!empty($minhasReservasEditaveis)): ?><span><?= count($minhasReservasEditaveis) ?></span><?php endif; ?>
                </button>
            <?php endif; ?>
            <a class="fb-btn fb-btn--complete-workspace" href="/?<?= h(http_build_query(['r' => 'reservasTematicas/reservasCompleta', 'data' => $dataSelecionada])) ?>">
                <i class="bi bi-grid-3x3-gap"></i>
                Ambiente completo
            </a>
        </div>
    </header>

    <?php if ($isHostess): ?>
        <dialog class="fb-editable-reservations-modal" data-editable-reservations-modal aria-labelledby="editableReservationsTitle">
            <header class="fb-editable-reservations-modal__head">
                <div>
                    <p>Minhas reservas</p>
                    <h2 id="editableReservationsTitle">Reservas que você pode editar</h2>
                    <span>Somente reservas futuras ou do dia, criadas por você.</span>
                </div>
                <button type="button" class="fb-editable-reservations-modal__close" data-editable-reservations-close aria-label="Fechar"><i class="bi bi-x-lg"></i></button>
            </header>
            <div class="fb-editable-reservations-modal__body">
                <section data-editable-reservations-list-view>
                    <?php if (empty($minhasReservasEditaveis)): ?>
                        <div class="fb-editable-reservations-empty"><i class="bi bi-calendar-check"></i><strong>Nenhuma reserva editável no momento.</strong><span>As próximas reservas criadas por você aparecerão aqui.</span></div>
                    <?php else: ?>
                        <div class="fb-editable-reservations-list">
                            <?php foreach ($minhasReservasEditaveis as $minhaReserva): ?>
                                <?php
                                $dataReserva = (string)($minhaReserva['data_reserva'] ?? '');
                                $statusMinhaReserva = normalize_mojibake((string)($minhaReserva['status_reserva'] ?? $minhaReserva['status'] ?? 'Reservada'));
                                $restauranteMinhaReserva = normalize_mojibake((string)($minhaReserva['restaurante'] ?? 'Restaurante'));
                                $identMinhaReserva = restaurante_identidade($restauranteMinhaReserva);
                                $chdMinhaReserva = max((int)($minhaReserva['pax_chd_calc'] ?? 0), (int)($minhaReserva['qtd_chd_calc'] ?? 0));
                                $grupoMinhaReserva = normalize_mojibake((string)($minhaReserva['grupo_nome_display'] ?? $minhaReserva['grupo_nome'] ?? ''));
                                $statusClassMinhaReserva = stripos($statusMinhaReserva, 'pré') !== false || stripos($statusMinhaReserva, 'pre-') !== false ? 'is-pre' : 'is-reserved';
                                ?>
                                <button type="button" class="fb-editable-reservation" data-editable-reservation-id="<?= (int)($minhaReserva['id'] ?? 0) ?>">
                                    <span class="fb-editable-reservation__restaurant" style="background:<?= h($identMinhaReserva['bg']) ?>;color:<?= h($identMinhaReserva['cor']) ?>"><i class="bi <?= h($identMinhaReserva['icone']) ?>"></i></span>
                                    <span class="fb-editable-reservation__date"><strong data-editable-reservation-date><?= h(format_date_br($dataReserva)) ?></strong><small><?= h(substr((string)($minhaReserva['turno_hora'] ?? '--:--'), 0, 5)) ?></small></span>
                                    <span class="fb-editable-reservation__main"><strong data-editable-reservation-title><?= h(normalize_mojibake((string)($minhaReserva['titular_nome_display'] ?? $minhaReserva['titular_nome'] ?? 'Sem titular'))) ?></strong><small data-editable-reservation-context><?= h($restauranteMinhaReserva) ?></small><span class="fb-editable-reservation__badges"><b class="fb-mini-badge fb-mini-badge--uh">UH <?= h((string)($minhaReserva['uh_numero'] ?? 'Pendente')) ?></b><b class="fb-mini-badge fb-mini-badge--pax" data-editable-reservation-pax><?= (int)($minhaReserva['pax'] ?? 0) ?> PAX</b><?php if ($chdMinhaReserva > 0): ?><b class="fb-mini-badge fb-mini-badge--chd"><?= $chdMinhaReserva ?> CHD</b><?php endif; ?><?php if ($grupoMinhaReserva !== '' && $grupoMinhaReserva !== '-'): ?><b class="fb-mini-badge fb-mini-badge--group"><i class="bi bi-people"></i> Grupo</b><?php endif; ?></span></span>
                                    <span class="fb-editable-reservation__meta"><small><em class="fb-editable-reservation__status <?= h($statusClassMinhaReserva) ?>"><?= h($statusMinhaReserva) ?></em></small><b>Editar</b></span>
                                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="fb-editable-reservation-editor" data-editable-reservation-editor-view hidden>
                    <button type="button" class="fb-editable-reservation-editor__back" data-editable-reservations-back><i class="bi bi-arrow-left"></i> Voltar para minhas reservas</button>
                    <div class="fb-editable-reservation-editor__identity" data-editable-reservation-summary>
                        <span class="fb-editable-reservation__restaurant" data-editable-reservation-summary-icon><i class="bi bi-calendar-check"></i></span>
                        <div><p>Editar reserva</p><strong data-editable-reservation-summary-title>Carregando reserva...</strong><small data-editable-reservation-summary-context></small><span class="fb-editable-reservation-editor__badges" data-editable-reservation-summary-badges></span></div>
                    </div>
                    <form method="post" action="/?r=reservasTematicas/reservas" class="fb-editable-reservation-form" data-editable-reservation-form>
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="">
                        <input type="hidden" name="correlation_id" value="">

                        <section class="fb-editable-reservation-form__section">
                            <header><span class="fb-editable-reservation-form__section-icon"><i class="bi bi-calendar-event"></i></span><div><strong>Quando e onde</strong><small>Revise data, restaurante e turno.</small></div></header>
                            <div class="fb-editable-reservation-form__grid fb-editable-reservation-form__grid--context">
                                <label class="fb-field"><span>Data <strong class="fb-required">*</strong></span><input class="fb-input" type="date" name="data_reserva" required></label>
                                <label class="fb-field"><span>Restaurante <strong class="fb-required">*</strong></span><select class="fb-input" name="restaurante_id" required><?php foreach ($restaurantes as $rest): ?><option value="<?= (int)$rest['id'] ?>"><?= h(normalize_mojibake((string)$rest['nome'])) ?></option><?php endforeach; ?></select></label>
                                <label class="fb-field"><span>Turno <strong class="fb-required">*</strong></span><select class="fb-input" name="turno_id" required><?php foreach ($turnos as $turno): ?><option value="<?= (int)$turno['id'] ?>"><?= h($rotuloTurnoCurto($turno)) ?></option><?php endforeach; ?></select></label>
                            </div>
                        </section>
                        <section class="fb-editable-reservation-form__section">
                            <header><span class="fb-editable-reservation-form__section-icon"><i class="bi bi-person-vcard"></i></span><div><strong>Quem participa</strong><small>Dados que serão usados na operação.</small></div></header>
                            <div class="fb-editable-reservation-mode" role="group" aria-label="Formato da reserva">
                                <button type="button" class="fb-editable-reservation-mode__option" data-editable-mode="single"><i class="bi bi-person"></i> Individual</button>
                                <button type="button" class="fb-editable-reservation-mode__option" data-editable-mode="group"><i class="bi bi-people"></i> Grupo</button>
                            </div>
                            <p class="fb-editable-reservation-mode__hint" data-editable-mode-hint></p>
                            <div class="fb-editable-reservation-form__grid fb-editable-reservation-form__grid--guest" data-editable-single-fields>
                                <label class="fb-field"><span>UH <strong class="fb-required">*</strong></span><input class="fb-input fb-input--identity fb-input--uh" type="text" inputmode="numeric" name="uh_numero" required></label>
                                <label class="fb-field"><span>Titular <strong class="fb-required">*</strong></span><input class="fb-input fb-input--identity" type="text" name="titular_nome" required></label>
                                <label class="fb-field"><span>PAX <strong class="fb-required">*</strong></span><input class="fb-input fb-num" type="number" min="1" name="pax" required></label>
                                <label class="fb-field"><span>Idades CHD</span><input class="fb-input" type="text" name="chd_idades" placeholder="Ex: 3y ou 3m"></label>
                            </div>
                            <div class="fb-editable-reservation-group" data-editable-group-fields hidden>
                                <label class="fb-field"><span>Titular do grupo <strong class="fb-required">*</strong></span><input class="fb-input fb-input--identity" type="text" name="grupo_responsavel" placeholder="Nome do titular do grupo"></label>
                                <div class="fb-editable-reservation-group__list" data-editable-group-rows></div>
                                <button type="button" class="fb-btn fb-btn--ghost fb-editable-reservation-group__add" data-editable-group-add><i class="bi bi-plus-lg"></i> Adicionar UH</button>
                            </div>
                        </section>
                        <details class="fb-editable-reservation-options">
                            <summary>Detalhes opcionais <span>Grupo, marcadores e observações</span></summary>
                            <div>
                                <label class="fb-field"><span>Grupo</span><input class="fb-input" type="text" name="grupo_nome" maxlength="120"></label>
                                <div class="fb-field"><span>Marcadores rápidos</span><div class="fb-reserve-tags"><?php foreach ($tagsPadrao as $tag): ?><label class="fb-chip fb-chip--select"><input type="checkbox" name="observacao_tags[]" value="<?= h($tag) ?>"><span><?= h($tag) ?></span></label><?php endforeach; ?></div></div>
                                <label class="fb-field"><span>Observações</span><textarea class="fb-input" name="observacao_reserva" rows="3" placeholder="Detalhes operacionais para salão e cozinha"></textarea></label>
                            </div>
                        </details>
                        <div class="fb-editable-reservation-form__actions"><button type="button" class="fb-btn fb-btn--ghost" data-editable-reservations-back>Cancelar</button><button type="submit" class="fb-btn fb-btn--complete-workspace"><i class="bi bi-check2-circle"></i> Salvar alterações</button></div>
                    </form>
                </section>
            </div>
        </dialog>
        <script type="application/json" data-editable-restaurant-identities><?= json_for_html($identidadesRestaurantes) ?></script>
    <?php endif; ?>

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
                $subtitulo = $subtituloReservaTematica($rest);
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
                            <div class="fb-reserve-tile-wrap">
                                <?php if (!$fechado && $canReserve): ?>
                                    <a class="<?= $tileClasses ?>" href="<?= h($linkSelecionar($restId, $turnoId, $dataSelecionada)) ?>">
                                        <?= $tileBody ?>
                                    </a>
                                <?php else: ?>
                                    <div class="<?= $tileClasses ?>" aria-disabled="true">
                                        <?= $tileBody ?>
                                    </div>
                                <?php endif; ?>
                                <button type="button" class="fb-reserve-tile__details" data-reserve-turn-details data-restaurante-id="<?= $restId ?>" data-turno-id="<?= $turnoId ?>" data-data="<?= h($dataSelecionada) ?>" data-restaurante-nome="<?= h(normalize_mojibake((string)$rest['nome'])) ?>" data-turno-hora="<?= h($rotuloTurnoCurto($turno)) ?>" aria-label="Ver detalhes de <?= h($nomeCurto) ?> às <?= h($rotuloTurnoCurto($turno)) ?>"><i class="bi bi-people"></i><span>Detalhes</span></button>
                            </div>
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
                    <input type="hidden" name="correlation_id" value="" id="reservaCorrelationId">
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

                <dialog class="fb-reserve-confirm-modal" data-reserve-confirm-modal aria-labelledby="reserveConfirmTitle">
                    <header class="fb-reserve-confirm-modal__head" style="--fb-confirm-color:<?= h($identSelecionado['cor']) ?>;--fb-confirm-bg:<?= h($identSelecionado['bg']) ?>;--fb-confirm-text:<?= h($identSelecionado['texto']) ?>;">
                        <span class="fb-reserve-confirm-modal__restaurant-mark"><i class="bi <?= h($identSelecionado['icone']) ?>" aria-hidden="true"></i></span>
                        <div>
                            <p>Revisão antes do envio</p>
                            <h2 id="reserveConfirmTitle">Confirmar reserva</h2>
                            <span><?= h(normalize_mojibake((string)$restauranteSelecionado['nome'])) ?> · <?= h($rotuloTurno($turnoSelecionado)) ?></span>
                        </div>
                        <button type="button" class="fb-reserve-confirm-modal__close" data-reserve-confirm-cancel aria-label="Fechar confirmação"><i class="bi bi-x-lg"></i></button>
                    </header>
                    <div class="fb-reserve-confirm-modal__body">
                        <div class="fb-reserve-confirm-modal__date"><i class="bi bi-calendar3"></i><strong><?= h(format_date_br($dataSelecionada)) ?></strong><span>Data da reserva</span></div>
                        <dl class="fb-reserve-confirm-modal__details" data-reserve-confirm-details></dl>
                        <section class="fb-reserve-confirm-modal__notes" data-reserve-confirm-notes hidden>
                            <span><i class="bi bi-chat-left-text"></i> Observações operacionais</span>
                            <strong data-reserve-confirm-notes-value></strong>
                        </section>
                    </div>
                    <footer class="fb-reserve-confirm-modal__actions">
                        <button type="button" class="fb-btn fb-btn--ghost" data-reserve-confirm-cancel>Corrigir dados</button>
                        <button type="button" class="fb-btn fb-btn--primary" data-reserve-confirm-submit><i class="bi bi-check2-circle"></i> Registrar reserva</button>
                    </footer>
                </dialog>
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

<dialog class="fb-turn-details-modal" data-reserve-turn-details-modal aria-labelledby="reserveTurnDetailsTitle">
    <header class="fb-turn-details-modal__head">
        <div>
            <p>Conferência do turno</p>
            <h2 id="reserveTurnDetailsTitle" data-reserve-turn-details-title>Reservas do turno</h2>
            <span data-reserve-turn-details-subtitle>Carregando informações...</span>
        </div>
        <button type="button" class="fb-editable-reservations-modal__close" data-reserve-turn-details-close aria-label="Fechar"><i class="bi bi-x-lg"></i></button>
    </header>
    <div class="fb-turn-details-modal__body" data-reserve-turn-details-body></div>
    <footer class="fb-turn-details-modal__foot"><button type="button" class="fb-btn fb-btn--complete-workspace" data-reserve-turn-details-close><i class="bi bi-check2"></i> Voltar ao mapa</button></footer>
</dialog>

<script>
(function () {
    var modal = document.querySelector('[data-reserve-turn-details-modal]');
    var body = document.querySelector('[data-reserve-turn-details-body]');
    var title = document.querySelector('[data-reserve-turn-details-title]');
    var subtitle = document.querySelector('[data-reserve-turn-details-subtitle]');
    if (!modal || !body || typeof modal.showModal !== 'function') { return; }

    function make(tag, className, text) {
        var node = document.createElement(tag);
        if (className) { node.className = className; }
        if (text !== undefined && text !== null) { node.textContent = text; }
        return node;
    }

    function setLoading(button) {
        title.textContent = (button.getAttribute('data-restaurante-nome') || 'Restaurante') + ' · ' + (button.getAttribute('data-turno-hora') || '--:--');
        subtitle.textContent = 'Carregando reservas e ocupação do turno...';
        body.innerHTML = '';
        var loading = make('div', 'fb-turn-details-loading');
        loading.appendChild(make('i', 'bi bi-arrow-repeat'));
        loading.appendChild(make('strong', '', 'Consultando reservas do turno'));
        loading.appendChild(make('span', '', 'A conferência usa a mesma base de ocupação exibida no mapa.'));
        body.appendChild(loading);
    }

    function statusClass(status) {
        var value = String(status || '').toLowerCase();
        if (value.indexOf('final') !== -1) { return 'is-finalized'; }
        if (value.indexOf('compareceu') !== -1 || value.indexOf('cancel') !== -1) { return 'is-danger'; }
        if (value.indexOf('pré') !== -1 || value.indexOf('pre-') !== -1) { return 'is-pre'; }
        return 'is-reserved';
    }

    function render(payload, button) {
        var items = Array.isArray(payload.items) ? payload.items : [];
        var date = String(payload.date || button.getAttribute('data-data') || '');
        var dateParts = date.split('-');
        var dateLabel = dateParts.length === 3 ? dateParts[2] + '/' + dateParts[1] + '/' + dateParts[0] : date;
        var restaurant = button.getAttribute('data-restaurante-nome') || 'Restaurante';
        var time = button.getAttribute('data-turno-hora') || '--:--';
        title.textContent = restaurant + ' · ' + time;
        subtitle.textContent = dateLabel + ' · ' + items.length + (items.length === 1 ? ' reserva encontrada' : ' reservas encontradas');
        body.innerHTML = '';

        var summary = make('div', 'fb-turn-details-summary');
        [
            { label: 'Capacidade', value: String(payload.capacidade || 0) },
            { label: 'Ocupadas', value: String(payload.reservado || 0) },
            { label: 'Disponíveis', value: String(payload.restante || 0) },
            { label: 'PAX / CHD', value: String(payload.total_pax || 0) + ' / ' + String(payload.total_chd || 0) }
        ].forEach(function (metric) {
            var cell = make('div', 'fb-turn-details-summary__item');
            cell.appendChild(make('small', '', metric.label));
            cell.appendChild(make('strong', 'fb-num', metric.value));
            summary.appendChild(cell);
        });
        body.appendChild(summary);

        if (!items.length) {
            var empty = make('div', 'fb-turn-details-empty');
            empty.appendChild(make('i', 'bi bi-calendar2-check'));
            empty.appendChild(make('strong', '', 'Nenhuma reserva neste turno.'));
            empty.appendChild(make('span', '', 'A capacidade exibida no mapa continua disponível para novos cadastros.'));
            body.appendChild(empty);
            return;
        }

        var list = make('div', 'fb-turn-details-list');
        items.forEach(function (item) {
            var row = make('article', 'fb-turn-details-item');
            var person = make('div', 'fb-turn-details-item__person');
            person.appendChild(make('strong', '', item.titular_nome || 'Sem titular'));
            person.appendChild(make('small', '', item.usuario ? 'Criado por ' + item.usuario : 'Usuário não informado'));
            var badges = make('div', 'fb-turn-details-item__badges');
            badges.appendChild(make('b', 'fb-mini-badge fb-mini-badge--uh', item.uh_numero ? 'UH ' + item.uh_numero : 'UH pendente'));
            badges.appendChild(make('b', 'fb-mini-badge fb-mini-badge--pax', String(item.pax || 0) + ' PAX'));
            if (Number(item.qtd_chd || 0) > 0) { badges.appendChild(make('b', 'fb-mini-badge fb-mini-badge--chd', String(item.qtd_chd) + ' CHD')); }
            var status = make('b', 'fb-turn-details-status ' + statusClass(item.status), item.status || 'Reservada');
            row.appendChild(person);
            row.appendChild(badges);
            row.appendChild(status);
            list.appendChild(row);
        });
        body.appendChild(list);
    }

    document.querySelectorAll('[data-reserve-turn-details]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            setLoading(button);
            modal.showModal();
            var url = new URL('/?r=reservasTematicas/reservas', window.location.origin);
            url.searchParams.set('ajax', 'availability_detail');
            url.searchParams.set('data', button.getAttribute('data-data') || '');
            url.searchParams.set('restaurante_id', button.getAttribute('data-restaurante-id') || '');
            url.searchParams.set('turno_id', button.getAttribute('data-turno-id') || '');
            fetch(url.toString(), { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch' } })
                .then(function (response) { return response.json().then(function (payload) { return { response: response, payload: payload }; }); })
                .then(function (result) {
                    if (!result.response.ok || !result.payload || !result.payload.ok) { throw new Error((result.payload && result.payload.message) || 'Não foi possível consultar este turno.'); }
                    render(result.payload, button);
                })
                .catch(function (error) {
                    subtitle.textContent = 'Não foi possível carregar os dados do turno.';
                    body.innerHTML = '';
                    var errorNode = make('div', 'fb-turn-details-empty is-error');
                    errorNode.appendChild(make('i', 'bi bi-exclamation-triangle'));
                    errorNode.appendChild(make('strong', '', error.message));
                    body.appendChild(errorNode);
                });
        });
    });

    document.querySelectorAll('[data-reserve-turn-details-close]').forEach(function (button) {
        button.addEventListener('click', function () { modal.close(); });
    });
    modal.addEventListener('click', function (event) { if (event.target === modal) { modal.close(); } });
})();

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
    var correlationInput = document.getElementById('reservaCorrelationId');
    var confirmationModal = document.querySelector('[data-reserve-confirm-modal]');
    var confirmationDetails = document.querySelector('[data-reserve-confirm-details]');
    var confirmationNotes = document.querySelector('[data-reserve-confirm-notes]');
    var confirmationNotesValue = document.querySelector('[data-reserve-confirm-notes-value]');
    var confirmationSubmit = document.querySelector('[data-reserve-confirm-submit]');

    function createCorrelationId() {
        if (window.crypto && window.crypto.randomUUID) {
            return window.crypto.randomUUID();
        }
        if (window.crypto && window.crypto.getRandomValues) {
            var values = new Uint32Array(4);
            window.crypto.getRandomValues(values);
            return Array.prototype.map.call(values, function (value) {
                return value.toString(16).padStart(8, '0');
            }).join('-');
        }
        return 'reservation-' + Date.now() + '-' + Math.random().toString(16).slice(2);
    }

    function ensureCorrelationId() {
        if (!correlationInput) { return ''; }
        if (!correlationInput.value) { correlationInput.value = createCorrelationId(); }
        return correlationInput.value;
    }

    function clearCorrelationId() {
        if (correlationInput) { correlationInput.value = ''; }
    }

    function checkReservationStatus(correlationId) {
        if (!correlationId) { return Promise.resolve(null); }
        var url = new URL('/?r=reservasTematicas/reservas', window.location.origin);
        url.searchParams.set('ajax', 'reservation_status');
        url.searchParams.set('correlation_id', correlationId);
        var retries = 0;
        function check() {
            return fetch(url.toString(), {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch' }
            }).then(function (response) {
                return response.json();
            }).then(function (payload) {
                if (payload && payload.status === 'confirmed' && payload.confirmation && payload.confirmation.protocolo) {
                    return payload.confirmation;
                }
                if (retries++ < 2) {
                    return new Promise(function (resolve) { window.setTimeout(resolve, 700); }).then(check);
                }
                return null;
            }).catch(function () {
                if (retries++ < 2) {
                    return new Promise(function (resolve) { window.setTimeout(resolve, 700); }).then(check);
                }
                return null;
            });
        }
        return check();
    }

    function activeMode() {
        var active = document.querySelector('[data-reserve-mode-button].is-active');
        return active && active.getAttribute('data-reserve-mode-button') === 'group' ? 'group' : 'individual';
    }

    function countChildren(values) {
        return values.reduce(function (total, value) {
            return total + (String(value || '').match(/\d+\s*[ym]/gi) || []).length;
        }, 0);
    }

    function addConfirmationDetail(label, value, modifier) {
        if (!confirmationDetails || !value) { return; }
        var item = document.createElement('div');
        if (modifier) { item.className = modifier; }
        var title = document.createElement('dt');
        var content = document.createElement('dd');
        title.textContent = label;
        content.textContent = value;
        item.appendChild(title);
        item.appendChild(content);
        confirmationDetails.appendChild(item);
    }

    function openReservationConfirmation(formData) {
        if (!confirmationModal || typeof confirmationModal.showModal !== 'function') { return Promise.resolve(true); }

        var isGroup = activeMode() === 'group';
        var paxValues = isGroup ? formData.getAll('batch_pax[]') : [formData.get('pax')];
        var chdValues = isGroup ? formData.getAll('batch_chd_idades[]') : [formData.get('chd_idades')];
        var uhs = isGroup ? formData.getAll('batch_uh_numero[]').map(function (value) { return String(value || '').trim(); }).filter(Boolean) : [String(formData.get('uh_numero') || '').trim()].filter(Boolean);
        var paxTotal = paxValues.reduce(function (total, value) { return total + (parseInt(String(value), 10) || 0); }, 0);
        var chdTotal = countChildren(chdValues);
        var tags = formData.getAll('observacao_tags[]').map(function (value) { return String(value || '').trim(); }).filter(Boolean);
        var observation = String(formData.get('observacao_reserva') || '').trim();
        var holder = isGroup ? String(formData.get('grupo_responsavel') || '').trim() : String(formData.get('titular_nome') || '').trim();
        var groupName = String(formData.get('grupo_nome') || '').trim();
        var isPreReservation = String(formData.get('action') || '') === 'create_pre_reservation';

        if (confirmationDetails) { confirmationDetails.innerHTML = ''; }
        addConfirmationDetail(isGroup ? 'Formato' : 'Tipo', isGroup ? 'Reserva em grupo' : (isPreReservation ? 'Pré-reserva' : 'Reserva individual'), 'is-emphasis');
        addConfirmationDetail(isGroup ? 'Responsável' : 'Titular', holder);
        addConfirmationDetail('UHs', isPreReservation ? 'A definir na operação' : (isGroup ? uhs.join(' · ') : ('UH ' + (uhs[0] || 'Pendente'))), isGroup ? 'is-wide' : '');
        addConfirmationDetail('PAX total', paxTotal > 0 ? String(paxTotal) + ' pessoa' + (paxTotal === 1 ? '' : 's') : '');
        addConfirmationDetail('Crianças', chdTotal > 0 ? String(chdTotal) + ' CHD' : 'Sem crianças');
        if (isGroup && groupName) { addConfirmationDetail('Nome do grupo', groupName, 'is-wide'); }

        var notes = [];
        if (tags.length) { notes.push(tags.join(' · ')); }
        if (observation) { notes.push(observation); }
        if (confirmationNotes && confirmationNotesValue) {
            confirmationNotes.hidden = notes.length === 0;
            confirmationNotesValue.textContent = notes.join(' — ');
        }
        if (confirmationSubmit) {
            confirmationSubmit.innerHTML = '<i class="bi bi-check2-circle"></i> ' + (isGroup ? 'Registrar grupo' : 'Registrar reserva');
        }

        return new Promise(function (resolve) {
            var completed = false;
            var finish = function (confirmed) {
                if (completed) { return; }
                completed = true;
                confirmationModal.close();
                resolve(confirmed);
            };
            var cancel = function (event) {
                if (event) { event.preventDefault(); }
                finish(false);
            };
            confirmationModal.querySelectorAll('[data-reserve-confirm-cancel]').forEach(function (button) {
                button.addEventListener('click', cancel, { once: true });
            });
            if (confirmationSubmit) { confirmationSubmit.addEventListener('click', function () { finish(true); }, { once: true }); }
            confirmationModal.addEventListener('cancel', cancel, { once: true });
            confirmationModal.showModal();
            window.setTimeout(function () { (confirmationSubmit || confirmationModal).focus(); }, 30);
        });
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

    function resolveUncertainSubmission(correlationId, formData) {
        return checkReservationStatus(correlationId).then(function (confirmation) {
            if (confirmation) {
                return alertReservation(
                    'success',
                    'Reserva confirmada no banco',
                    'A conexão foi interrompida, mas esta reserva foi gravada com segurança. Protocolo ' + confirmation.protocolo + '.',
                    reservationDetails(formData, { correlation_id: correlationId }),
                    'Conferido'
                ).then(function () {
                    clearCorrelationId();
                    window.location.assign('/?r=reservasTematicas/reservas');
                    return true;
                });
            }
            return alertReservation(
                'warning',
                'Confirmação pendente',
                'O servidor não devolveu uma resposta conclusiva. Os dados foram mantidos; ao tentar novamente, esta mesma referência será usada e não criará uma reserva duplicada.',
                reservationDetails(formData, { correlation_id: correlationId }),
                'Entendi'
            ).then(function () { return false; });
        });
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
            return response.json().then(function (payload) {
                payload.http_status = response.status;
                return payload;
            });
        }

        return response.text().then(function (text) {
            var readable = plainTextFromHtml(text);
            var lower = String(text || '').toLowerCase();

            if (response.status === 401 || lower.indexOf('auth/login') !== -1 || lower.indexOf('tela de login') !== -1) {
                return {
                    ok: false,
                    type: 'danger',
                    code: 'sessao_expirada',
                    http_status: response.status,
                    message: 'Sua sessão expirou ou foi encerrada. Entre novamente antes de registrar a reserva.',
                    redirect: '/?r=auth/login'
                };
            }

            if (response.status === 403 || lower.indexOf('errors/forbidden') !== -1 || lower.indexOf('acesso não autorizado') !== -1) {
                return {
                    ok: false,
                    type: 'danger',
                    code: 'acesso_negado',
                    http_status: response.status,
                    message: 'Seu usuário não tem permissão para concluir esta ação.',
                    redirect: '/?r=errors/forbidden'
                };
            }

            return {
                ok: false,
                type: 'danger',
                code: 'resposta_invalida',
                http_status: response.status,
                message: readable || 'O servidor respondeu em um formato inesperado. Atualize a página e tente novamente.'
            };
        });
    }

    form.addEventListener('submit', function (event) {
        if (!form.dataset.reserveConfirmationAccepted) {
            event.preventDefault();
            if (!form.reportValidity()) { return; }
            openReservationConfirmation(new FormData(form)).then(function (confirmed) {
                if (!confirmed) { return; }
                form.dataset.reserveConfirmationAccepted = '1';
                form.requestSubmit();
            });
            return;
        }
        delete form.dataset.reserveConfirmationAccepted;
        event.preventDefault();
        if (!form.reportValidity()) { return; }

        var submit = form.querySelector('button[type="submit"]');
        var originalHtml = submit ? submit.innerHTML : '';
        var correlationId = ensureCorrelationId();
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
                    if (!result || result.code === 'resposta_invalida' || Number(result.http_status || 0) >= 500) {
                        return resolveUncertainSubmission(correlationId, formData);
                    }
                    return alertReservation(
                        result && result.type === 'warning' ? 'warning' : 'danger',
                        'Reserva não realizada',
                        result && result.message ? result.message : 'Revise os dados e tente novamente.',
                        details,
                        'Corrigir dados'
                    ).then(function () {
                        clearCorrelationId();
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
                    clearCorrelationId();
                    window.location.assign(result.redirect || window.location.href);
                });
            })
            .catch(function () {
                return resolveUncertainSubmission(correlationId, formData);
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
<?php if ($isHostess): ?>
<script>
(function () {
    var modal = document.querySelector('[data-editable-reservations-modal]');
    var opener = document.querySelector('[data-editable-reservations-open]');
    var closer = document.querySelector('[data-editable-reservations-close]');
    var listView = document.querySelector('[data-editable-reservations-list-view]');
    var editorView = document.querySelector('[data-editable-reservation-editor-view]');
    var editForm = document.querySelector('[data-editable-reservation-form]');
    var identitiesNode = document.querySelector('[data-editable-restaurant-identities]');
    var modeButtons = modal ? modal.querySelectorAll('[data-editable-mode]') : [];
    var groupFields = modal ? modal.querySelector('[data-editable-group-fields]') : null;
    var groupRows = modal ? modal.querySelector('[data-editable-group-rows]') : null;
    var groupAdd = modal ? modal.querySelector('[data-editable-group-add]') : null;
    var modeHint = modal ? modal.querySelector('[data-editable-mode-hint]') : null;
    var singleFields = modal ? modal.querySelector('[data-editable-single-fields]') : null;
    var activeReservation = null;
    var restaurantIdentities = {};
    if (!modal || !opener || typeof modal.showModal !== 'function') { return; }

    try { restaurantIdentities = JSON.parse(identitiesNode ? identitiesNode.textContent : '{}') || {}; } catch (e) {}

    function showList() {
        if (editorView) { editorView.hidden = true; }
        if (listView) { listView.hidden = false; }
        var body = modal.querySelector('.fb-editable-reservations-modal__body');
        if (body) { body.scrollTop = 0; }
    }

    function formatDate(value) {
        var parts = String(value || '').split('-');
        return parts.length === 3 ? parts[2] + '/' + parts[1] + '/' + parts[0] : value;
    }

    function setSummary(reservation) {
        var selected = editForm ? editForm.querySelector('[name="restaurante_id"] option:checked') : null;
        var restaurantName = selected ? String(selected.textContent || '').trim() : 'Restaurante';
        var identity = restaurantIdentities[String(reservation.restaurante_id || '')] || {};
        var icon = modal.querySelector('[data-editable-reservation-summary-icon]');
        var title = modal.querySelector('[data-editable-reservation-summary-title]');
        var context = modal.querySelector('[data-editable-reservation-summary-context]');
        var badges = modal.querySelector('[data-editable-reservation-summary-badges]');
        if (icon) {
            icon.style.background = identity.bg || '';
            icon.style.color = identity.cor || '';
            icon.innerHTML = '<i class="bi ' + String(identity.icone || 'bi-calendar-check') + '"></i>';
        }
        if (title) { title.textContent = reservation.titular_nome || 'Reserva sem titular'; }
        if (context) { context.textContent = restaurantName + ' · ' + (reservation.uh_numero ? 'UH ' + reservation.uh_numero : 'UH pendente') + ' · ' + formatDate(reservation.data_reserva); }
        if (badges) {
            badges.innerHTML = '';
            [
                { label: 'UH ' + (reservation.uh_numero || 'pendente'), className: 'fb-mini-badge--uh' },
                { label: String(reservation.pax || 0) + ' PAX', className: 'fb-mini-badge--pax' },
                { label: reservation.chd_idades ? String((reservation.chd_idades.match(/\\d+\\s*[ym]/gi) || []).length) + ' CHD' : '', className: 'fb-mini-badge--chd' }
            ].forEach(function (badge) {
                if (!badge.label) { return; }
                var node = document.createElement('b');
                node.className = 'fb-mini-badge ' + badge.className;
                node.textContent = badge.label;
                badges.appendChild(node);
            });
        }
    }

    function setInputsEnabled(container, enabled) {
        if (!container) { return; }
        container.querySelectorAll('input, textarea, select, button').forEach(function (field) {
            field.disabled = !enabled;
        });
    }

    function groupRowTemplate(item) {
        var row = document.createElement('div');
        row.className = 'fb-editable-reservation-group__row';
        row.innerHTML = '<label class="fb-field"><span>UH <strong class="fb-required">*</strong></span><input class="fb-input fb-input--uh" type="text" inputmode="numeric" name="batch_uh_numero[]" required></label>'
            + '<label class="fb-field"><span>PAX <strong class="fb-required">*</strong></span><input class="fb-input fb-num" type="number" min="1" name="batch_pax[]" required></label>'
            + '<label class="fb-field"><span>Idades CHD</span><input class="fb-input" type="text" name="batch_chd_idades[]" placeholder="Ex: 3y ou 3m"></label>'
            + '<button type="button" class="fb-editable-reservation-group__remove" aria-label="Remover UH"><i class="bi bi-dash-lg"></i></button>';
        row.querySelector('[name="batch_uh_numero[]"]').value = item && item.uh_numero ? item.uh_numero : '';
        row.querySelector('[name="batch_pax[]"]').value = item && item.pax ? item.pax : 1;
        row.querySelector('[name="batch_chd_idades[]"]').value = item && item.chd_idades ? item.chd_idades : '';
        row.querySelector('.fb-editable-reservation-group__remove').addEventListener('click', function () {
            if (groupRows && groupRows.children.length > 2) { row.remove(); }
        });
        return row;
    }

    function ensureGroupRows(reservation) {
        if (!groupRows || groupRows.children.length > 0) { return; }
        groupRows.appendChild(groupRowTemplate(reservation));
        groupRows.appendChild(groupRowTemplate(null));
        var responsible = editForm ? editForm.querySelector('[name="grupo_responsavel"]') : null;
        if (responsible && !responsible.value) { responsible.value = reservation.titular_nome || ''; }
    }

    function setEditMode(mode, reservation) {
        if (!editForm) { return; }
        var originalGroupId = Number(editForm.getAttribute('data-original-group-id') || 0);
        var isExistingGroup = originalGroupId > 0;
        var convertingToGroup = mode === 'group' && !isExistingGroup;
        var convertingToIndividual = mode === 'single' && isExistingGroup;
        var action = editForm.querySelector('[name="action"]');

        if (action) {
            action.value = convertingToGroup ? 'update_to_group' : (convertingToIndividual ? 'update_to_individual' : 'update');
        }
        if (singleFields) { singleFields.hidden = convertingToGroup; }
        if (groupFields) { groupFields.hidden = !convertingToGroup; }
        setInputsEnabled(singleFields, !convertingToGroup);
        setInputsEnabled(groupFields, convertingToGroup);
        if (convertingToGroup) { ensureGroupRows(reservation || activeReservation || {}); }

        modeButtons.forEach(function (button) {
            var selected = button.getAttribute('data-editable-mode') === mode;
            button.classList.toggle('is-active', selected);
            button.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });
        if (modeHint) {
            modeHint.textContent = convertingToGroup
                ? 'Inclua ao menos duas UHs. A primeira linha substitui esta reserva e as demais serão vinculadas ao mesmo grupo.'
                : (convertingToIndividual
                    ? 'Esta UH será separada do grupo. As demais reservas vinculadas permanecerão agrupadas.'
                    : (isExistingGroup
                        ? 'Esta reserva faz parte de um grupo. As alterações desta UH preservam seu vínculo atual.'
                        : 'Reserva individual para uma única UH.'));
        }
    }

    function populateEditor(reservation) {
        if (!editForm) { return; }
        activeReservation = reservation;
        editForm.setAttribute('data-original-group-id', String(reservation.grupo_id || 0));
        var fields = ['id', 'data_reserva', 'restaurante_id', 'turno_id', 'uh_numero', 'titular_nome', 'pax', 'chd_idades', 'grupo_nome', 'observacao_reserva'];
        fields.forEach(function (name) {
            var field = editForm.querySelector('[name="' + name + '"]');
            if (field) { field.value = reservation[name] !== undefined && reservation[name] !== null ? reservation[name] : ''; }
        });
        var selectedTags = Array.isArray(reservation.observacao_tags) ? reservation.observacao_tags : [];
        editForm.querySelectorAll('[name="observacao_tags[]"]').forEach(function (field) {
            field.checked = selectedTags.indexOf(field.value) !== -1;
        });
        setSummary(reservation);
        setEditMode(Number(reservation.grupo_id || 0) > 0 ? 'group' : 'single', reservation);
    }

    function updateListItem(reservation) {
        var item = document.querySelector('[data-editable-reservation-id="' + String(reservation.id) + '"]');
        if (!item) { return; }
        var selected = editForm ? editForm.querySelector('[name="restaurante_id"] option:checked') : null;
        var restaurantName = selected ? String(selected.textContent || '').trim() : 'Restaurante';
        var timeOption = editForm ? editForm.querySelector('[name="turno_id"] option:checked') : null;
        var identity = restaurantIdentities[String(reservation.restaurante_id || '')] || {};
        var icon = item.querySelector('.fb-editable-reservation__restaurant');
        var date = item.querySelector('[data-editable-reservation-date]');
        var title = item.querySelector('[data-editable-reservation-title]');
        var context = item.querySelector('[data-editable-reservation-context]');
        var pax = item.querySelector('[data-editable-reservation-pax]');
        var uh = item.querySelector('.fb-mini-badge--uh');
        var chd = item.querySelector('.fb-mini-badge--chd');
        var groupBadge = item.querySelector('.fb-mini-badge--group');
        if (icon) {
            icon.style.background = identity.bg || '';
            icon.style.color = identity.cor || '';
            icon.innerHTML = '<i class="bi ' + String(identity.icone || 'bi-calendar-check') + '"></i>';
        }
        if (date) { date.textContent = formatDate(reservation.data_reserva); }
        if (date && date.nextElementSibling) { date.nextElementSibling.textContent = timeOption ? String(timeOption.textContent || '').trim() : ''; }
        if (title) { title.textContent = reservation.titular_nome || 'Sem titular'; }
        if (context) { context.textContent = restaurantName; }
        if (uh) { uh.textContent = 'UH ' + (reservation.uh_numero || 'Pendente'); }
        if (pax) { pax.textContent = String(reservation.pax || 0) + ' PAX'; }
        var chdCount = String(reservation.chd_idades || '').match(/\d+\s*[ym]/gi) || [];
        if (chd && chdCount.length === 0) { chd.remove(); }
        if (chd && chdCount.length > 0) { chd.textContent = String(chdCount.length) + ' CHD'; }
        if (!chd && chdCount.length > 0 && pax && pax.parentNode) {
            chd = document.createElement('b');
            chd.className = 'fb-mini-badge fb-mini-badge--chd';
            chd.textContent = String(chdCount.length) + ' CHD';
            pax.parentNode.appendChild(chd);
        }
        if (Number(reservation.grupo_id || 0) > 0 && !groupBadge && pax && pax.parentNode) {
            groupBadge = document.createElement('b');
            groupBadge.className = 'fb-mini-badge fb-mini-badge--group';
            groupBadge.innerHTML = '<i class="bi bi-people"></i> Grupo';
            pax.parentNode.appendChild(groupBadge);
        }
        if (Number(reservation.grupo_id || 0) <= 0 && groupBadge) { groupBadge.remove(); }
    }

    function editableReservationDetails(reservation) {
        var restaurant = editForm ? editForm.querySelector('[name="restaurante_id"] option:checked') : null;
        var turno = editForm ? editForm.querySelector('[name="turno_id"] option:checked') : null;
        var chdCount = String(reservation.chd_idades || '').match(/\d+\s*[ym]/gi) || [];
        return [
            { label: 'Restaurante e turno', value: (restaurant ? String(restaurant.textContent || '').trim() : 'Restaurante') + ' · ' + (turno ? String(turno.textContent || '').trim() : '--:--') },
            { label: 'Data', value: formatDate(reservation.data_reserva) },
            { label: 'UH', value: reservation.uh_numero ? 'UH ' + reservation.uh_numero : 'UH pendente' },
            { label: 'PAX informado', value: String(reservation.pax || 0) + ' pessoas' },
            { label: 'CHD', value: chdCount.length ? String(chdCount.length) + ' criança(s)' : '' }
        ];
    }

    function openEditor(id) {
        var url = new URL('/?r=reservasTematicas/reservas', window.location.origin);
        url.searchParams.set('ajax', 'editable_reservation');
        url.searchParams.set('id', String(id));
        fetch(url.toString(), { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch' } })
            .then(function (response) { return response.json().then(function (payload) { return { response: response, payload: payload }; }); })
            .then(function (result) {
                if (!result.response.ok || !result.payload || !result.payload.ok) { throw new Error((result.payload && result.payload.message) || 'Não foi possível abrir esta reserva para edição.'); }
                populateEditor(result.payload.reservation || {});
                if (listView) { listView.hidden = true; }
                if (editorView) { editorView.hidden = false; }
                var body = modal.querySelector('.fb-editable-reservations-modal__body');
                if (body) { body.scrollTop = 0; }
            })
            .catch(function (error) {
                if (window.fbAlerts && typeof window.fbAlerts.error === 'function') { window.fbAlerts.error(error.message, 'Não foi possível abrir'); }
            });
    }

    opener.addEventListener('click', function () { modal.showModal(); });
    if (closer) {
        closer.addEventListener('click', function () { modal.close(); });
    }
    modal.addEventListener('click', function (event) {
        if (event.target === modal) { modal.close(); }
    });
    modal.querySelectorAll('[data-editable-reservation-id]').forEach(function (item) {
        item.addEventListener('click', function () { openEditor(item.getAttribute('data-editable-reservation-id')); });
    });
    modal.querySelectorAll('[data-editable-reservations-back]').forEach(function (button) {
        button.addEventListener('click', showList);
    });
    modeButtons.forEach(function (button) {
        button.addEventListener('click', function () { setEditMode(button.getAttribute('data-editable-mode') || 'single', activeReservation || {}); });
    });
    if (groupAdd) {
        groupAdd.addEventListener('click', function () {
            if (groupRows) { groupRows.appendChild(groupRowTemplate(null)); }
        });
    }
    if (editForm) {
        editForm.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!editForm.reportValidity()) { return; }
            var submit = editForm.querySelector('[type="submit"]');
            var original = submit ? submit.innerHTML : '';
            if (submit) { submit.disabled = true; submit.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Salvando...'; }
            var formData = new FormData(editForm);
            fetch(editForm.getAttribute('action'), {
                method: 'POST', body: formData, credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch' }
            }).then(function (response) {
                return response.json().then(function (payload) { return { response: response, payload: payload }; });
            }).then(function (result) {
                if (!result.response.ok || !result.payload || !result.payload.ok) { throw new Error((result.payload && result.payload.message) || 'Não foi possível salvar as alterações.'); }
                var updated = {
                    id: editForm.querySelector('[name="id"]').value,
                    data_reserva: editForm.querySelector('[name="data_reserva"]').value,
                    restaurante_id: editForm.querySelector('[name="restaurante_id"]').value,
                    turno_id: editForm.querySelector('[name="turno_id"]').value,
                    uh_numero: editForm.querySelector('[name="uh_numero"]').value,
                    titular_nome: editForm.querySelector('[name="titular_nome"]').value,
                    pax: editForm.querySelector('[name="pax"]').value,
                    chd_idades: editForm.querySelector('[name="chd_idades"]').value,
                    grupo_id: editForm.querySelector('[name="action"]').value === 'update_to_group'
                        ? Number((result.payload.payload || {}).grupo_id || 1)
                        : (editForm.querySelector('[name="action"]').value === 'update_to_individual' ? 0 : Number(editForm.getAttribute('data-original-group-id') || 0))
                };
                updateListItem(updated);
                if (window.fbAlerts && typeof window.fbAlerts.modal === 'function') {
                    // <dialog> ocupa a camada superior do navegador; feche-o antes do popup global.
                    if (modal && modal.open) { modal.close(); }
                    return new Promise(function (resolve) { window.setTimeout(resolve, 0); }).then(function () {
                        return window.fbAlerts.modal({
                            type: 'success',
                            title: 'Alteração salva com sucesso',
                            message: result.payload.message || 'A reserva foi atualizada e a alteração ficou registrada na auditoria.',
                            details: editableReservationDetails(updated),
                            buttonText: 'Voltar para reservas',
                            variant: 'reservation'
                        });
                    }).then(showList);
                }
                showList();
            }).catch(function (error) {
                if (window.fbAlerts && typeof window.fbAlerts.modal === 'function') { window.fbAlerts.modal({ type: 'danger', title: 'Alteração não realizada', message: error.message, buttonText: 'Corrigir' }); }
            }).finally(function () {
                if (submit) { submit.disabled = false; submit.innerHTML = original; }
            });
        });
    }
})();
</script>
<?php endif; ?>
