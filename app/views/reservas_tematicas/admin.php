<?php
/* Config. Temáticas como calendário (remake visual, etapa 7): um calendário por restaurante
 * e um painel do dia. Posta nas mesmas actions do controller (bloqueio_data, bloqueio_semana,
 * bloqueio_semanal, config_capacidade_data). Capacidade padrão, turnos e períodos seguem no
 * ambiente completo (reservasTematicas/adminCompleta). */
$restaurantes = $this->data['restaurantes'] ?? [];
$turnos = $this->data['turnos'] ?? [];
$turnosConfigData = $this->data['turnos_config_data'] ?? [];
$capacidadeData = (string)($this->data['capacidade_data'] ?? date('Y-m-d'));
$bloqueiosSemanais = $this->data['bloqueios_semanais'] ?? [];
$canManageBloqueios = (bool)($this->data['can_manage_bloqueios'] ?? false);
$calendario = $this->data['calendario'] ?? [];
$restauranteId = (int)($this->data['calendario_restaurante_id'] ?? 0);

$restauranteAtual = null;
foreach ($restaurantes as $rest) {
    if ((int)$rest['id'] === $restauranteId) {
        $restauranteAtual = $rest;
        break;
    }
}

$mesAtual = new DateTimeImmutable(substr($capacidadeData, 0, 7) . '-01');
$mesLabelMap = [1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
$mesLabel = ($mesLabelMap[(int)$mesAtual->format('n')] ?? '') . ' ' . $mesAtual->format('Y');
$mesAnterior = $mesAtual->modify('-1 month')->format('Y-m-d');
$mesSeguinte = $mesAtual->modify('+1 month')->format('Y-m-d');
$offsetPrimeiroDia = (int)$mesAtual->format('w');

$linkDia = static fn(string $data) => '/?' . http_build_query(['r' => 'reservasTematicas/admin', 'restaurante_id' => $restauranteId, 'cap_data' => $data]);
$diaSelecionado = $calendario[$capacidadeData] ?? null;
$overrideSelecionado = $diaSelecionado['override'] ?? null;
$fechadoSemanalSelecionado = (bool)($diaSelecionado['fechado_semanal'] ?? false);
$estadoDia = 'aberto';
if (is_array($overrideSelecionado)) {
    $estadoDia = (string)($overrideSelecionado['modo'] ?? (($overrideSelecionado['fechado'] ?? 1) ? 'fechado' : 'aberto')) === 'fechado' ? 'bloqueado' : 'aberto excepcionalmente';
} elseif ($fechadoSemanalSelecionado) {
    $estadoDia = 'fechamento semanal';
}
$diasSemanaLabels = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
$fechamentoSemanalMap = [];
foreach ($bloqueiosSemanais as $bloqueioSemanal) {
    if ((int)($bloqueioSemanal['restaurante_id'] ?? 0) === $restauranteId && (int)($bloqueioSemanal['fechado'] ?? 0) === 1) {
        $fechamentoSemanalMap[(int)($bloqueioSemanal['dia_semana'] ?? -1)] = (string)($bloqueioSemanal['motivo'] ?? '');
    }
}
?>
<div class="saas-page tematico-config-page">
    <section class="saas-hero-card">
        <div class="saas-headline d-flex flex-wrap gap-3 align-items-start justify-content-between">
            <div>
                <div class="saas-label">Temáticos</div>
                <h3 class="saas-title mb-1">Config. Temáticas</h3>
                <p class="saas-subtitle mb-0">Capacidade, bloqueios e exceções — dia a dia, num lugar só.</p>
            </div>
            <a class="fb-btn" href="/?r=reservasTematicas/adminCompleta"><i class="bi bi-sliders"></i> Ambiente completo</a>
        </div>
    </section>

    <div class="fb-chiprow fb-mt">
        <?php foreach ($restaurantes as $rest): ?>
            <a class="fb-chip<?= (int)$rest['id'] === $restauranteId ? ' fb-chip--active' : '' ?>" href="/?<?= h(http_build_query(['r' => 'reservasTematicas/admin', 'restaurante_id' => (int)$rest['id'], 'cap_data' => $capacidadeData])) ?>"><?= h(normalize_mojibake((string)$rest['nome'])) ?></a>
        <?php endforeach; ?>
    </div>

    <div class="row g-3 fb-mt">
        <div class="col-12 col-lg-7">
            <div class="fb-card h-100">
                <div class="fb-card__head">
                    <h5 class="fb-card__title" style="text-transform: capitalize;"><?= h($mesLabel) ?></h5>
                    <span class="fb-row" style="gap: 6px;">
                        <a class="fb-btn" style="min-height: 34px; padding: 4px 10px;" href="<?= h($linkDia($mesAnterior)) ?>" aria-label="Mês anterior"><i class="bi bi-chevron-left"></i></a>
                        <a class="fb-btn" style="min-height: 34px; padding: 4px 10px;" href="<?= h($linkDia(date('Y-m-d'))) ?>">Hoje</a>
                        <a class="fb-btn" style="min-height: 34px; padding: 4px 10px;" href="<?= h($linkDia($mesSeguinte)) ?>" aria-label="Próximo mês"><i class="bi bi-chevron-right"></i></a>
                    </span>
                </div>
                <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; text-align: center; font-size: 0.72rem; color: var(--fb-muted); margin-bottom: 4px;">
                    <?php foreach ($diasSemanaLabels as $rotuloDia): ?><span><?= h($rotuloDia) ?></span><?php endforeach; ?>
                </div>
                <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px;">
                    <?php for ($vazio = 0; $vazio < $offsetPrimeiroDia; $vazio++): ?><span></span><?php endfor; ?>
                    <?php foreach ($calendario as $dataStr => $infoDia): ?>
                        <?php
                        $overrideDia = $infoDia['override'] ?? null;
                        $estiloDia = 'background: var(--fb-card); border: 1px solid var(--fb-border); color: var(--fb-ink);';
                        $tituloDia = (string)$infoDia['capacidade'] . ' PAX';
                        if (is_array($overrideDia)) {
                            $modoOverride = (string)($overrideDia['modo'] ?? (($overrideDia['fechado'] ?? 1) ? 'fechado' : 'aberto'));
                            if ($modoOverride === 'fechado') {
                                $estiloDia = 'background: var(--fb-danger-bg); border: 1px solid transparent; color: var(--fb-danger);';
                                $tituloDia = 'bloqueado: ' . (string)($overrideDia['motivo'] ?? '');
                            } else {
                                $estiloDia = 'background: var(--fb-ok-bg); border: 1px solid transparent; color: var(--fb-ok);';
                                $tituloDia = 'aberto excepcionalmente';
                            }
                        } elseif (!empty($infoDia['fechado_semanal'])) {
                            $estiloDia = 'background: var(--fb-neutral-bg); border: 1px solid transparent; color: var(--fb-neutral);';
                            $tituloDia = 'fechamento semanal';
                        } elseif (!empty($infoDia['capacidade_especial'])) {
                            $estiloDia = 'background: var(--fb-warn-bg); border: 1px solid transparent; color: var(--fb-warn);';
                            $tituloDia = 'capacidade especial: ' . (string)$infoDia['capacidade'] . ' PAX';
                        }
                        if ($dataStr === $capacidadeData) {
                            $estiloDia .= ' outline: 2px solid var(--fb-brand); outline-offset: 1px; font-weight: 700;';
                        }
                        ?>
                        <a href="<?= h($linkDia((string)$dataStr)) ?>" title="<?= h($tituloDia) ?>" style="display: block; padding: 8px 0; border-radius: 8px; text-align: center; font-size: 0.82rem; text-decoration: none; <?= $estiloDia ?>"><?= (int)$infoDia['dia'] ?></a>
                    <?php endforeach; ?>
                </div>
                <div class="fb-row fb-mt" style="gap: 12px; font-size: 0.72rem; color: var(--fb-muted); flex-wrap: wrap;">
                    <span><span style="display: inline-block; width: 10px; height: 10px; border-radius: 3px; background: var(--fb-card); border: 1px solid var(--fb-border); vertical-align: -1px;"></span> aberto</span>
                    <span><span style="display: inline-block; width: 10px; height: 10px; border-radius: 3px; background: var(--fb-warn-bg); vertical-align: -1px;"></span> capacidade especial</span>
                    <span><span style="display: inline-block; width: 10px; height: 10px; border-radius: 3px; background: var(--fb-danger-bg); vertical-align: -1px;"></span> bloqueado</span>
                    <span><span style="display: inline-block; width: 10px; height: 10px; border-radius: 3px; background: var(--fb-ok-bg); vertical-align: -1px;"></span> aberto (exceção)</span>
                    <span><span style="display: inline-block; width: 10px; height: 10px; border-radius: 3px; background: var(--fb-neutral-bg); vertical-align: -1px;"></span> fechamento semanal</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="fb-card h-100">
                <div class="fb-card__head">
                    <div>
                        <h5 class="fb-card__title"><?= h(format_date_br($capacidadeData)) ?></h5>
                        <span class="fb-muted" style="font-size: 0.78rem;"><?= h(normalize_mojibake((string)($restauranteAtual['nome'] ?? ''))) ?> · <?= h($estadoDia) ?></span>
                    </div>
                    <?php if ($estadoDia === 'bloqueado'): ?>
                        <span class="fb-badge fb-badge--solid-danger">bloqueado</span>
                    <?php elseif ($estadoDia === 'fechamento semanal'): ?>
                        <span class="fb-badge fb-badge--solid-neutral">fechado</span>
                    <?php else: ?>
                        <span class="fb-badge fb-badge--ok">aberto</span>
                    <?php endif; ?>
                </div>

                <form method="post" action="/?r=reservasTematicas/admin">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="action" value="config_capacidade_data">
                    <input type="hidden" name="capacidade_data" value="<?= h($capacidadeData) ?>">
                    <p class="fb-label" style="margin-bottom: 6px;">Capacidade por turno nesta data</p>
                    <ul class="fb-list">
                        <?php foreach ($turnosConfigData[$restauranteId] ?? [] as $cfgTurno): ?>
                            <?php
                            $turnoCfgId = (int)($cfgTurno['turno_id'] ?? 0);
                            $rotuloTurnoCfg = '';
                            foreach ($turnos as $turnoItem) {
                                if ((int)$turnoItem['id'] === $turnoCfgId) {
                                    $rotuloTurnoCfg = (string)($turnoItem['hora'] ?? $turnoItem['nome'] ?? '');
                                    break;
                                }
                            }
                            ?>
                            <li class="fb-list__item">
                                <span style="font-size: 0.88rem; font-weight: 500;" class="fb-num"><?= h($rotuloTurnoCfg !== '' ? $rotuloTurnoCfg : ('Turno ' . $turnoCfgId)) ?></span>
                                <span class="fb-row" style="gap: 6px;">
                                    <input type="number" min="0" class="fb-input fb-num" style="max-width: 96px; min-height: 38px; text-align: center;" name="capacidade_data_turno[<?= $restauranteId ?>][<?= $turnoCfgId ?>]" value="<?= (int)($cfgTurno['capacidade'] ?? 0) ?>">
                                    <span class="fb-muted" style="font-size: 0.78rem;">PAX</span>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="submit" class="fb-btn fb-btn--primary fb-mt" style="width: 100%;">Salvar capacidade desta data</button>
                </form>

                <?php if ($canManageBloqueios): ?>
                    <div class="fb-mt" style="border-top: 1px solid var(--fb-border); padding-top: 12px;">
                        <p class="fb-label" style="margin-bottom: 6px;">Disponibilidade do dia</p>
                        <?php if (is_array($overrideSelecionado)): ?>
                            <form method="post" action="/?r=reservasTematicas/admin" class="fb-mt">
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="action" value="bloqueio_data">
                                <input type="hidden" name="restaurante_id" value="<?= $restauranteId ?>">
                                <input type="hidden" name="data_bloqueio" value="<?= h($capacidadeData) ?>">
                                <input type="hidden" name="modo" value="remover">
                                <button type="submit" class="fb-btn" style="width: 100%;" data-confirm-title="Remover exceção" data-confirm="Voltar este dia ao cronograma normal?" data-confirm-yes="Remover" data-confirm-no="Voltar"><i class="bi bi-arrow-counterclockwise"></i> Remover exceção do dia</button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="/?r=reservasTematicas/admin" class="row g-2">
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="action" value="bloqueio_data">
                                <input type="hidden" name="restaurante_id" value="<?= $restauranteId ?>">
                                <input type="hidden" name="data_bloqueio" value="<?= h($capacidadeData) ?>">
                                <div class="col-12">
                                    <input type="text" class="fb-input" name="motivo" placeholder="Motivo (obrigatório)" required>
                                </div>
                                <div class="col-6">
                                    <button type="submit" name="modo" value="fechado" class="fb-btn fb-btn--danger" style="width: 100%;">Bloquear o dia</button>
                                </div>
                                <div class="col-6">
                                    <button type="submit" name="modo" value="aberto" class="fb-btn" style="width: 100%;" <?= !$fechadoSemanalSelecionado ? 'disabled title="Só faz sentido em dia de fechamento semanal"' : '' ?>>Abrir (exceção)</button>
                                </div>
                            </form>
                        <?php endif; ?>
                        <details class="fb-mt">
                            <summary class="fb-muted" style="cursor: pointer; font-size: 0.8rem; list-style: none;"><i class="bi bi-calendar-week"></i> Fechar 7 dias a partir desta data</summary>
                            <form method="post" action="/?r=reservasTematicas/admin" class="fb-row fb-mt">
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="action" value="bloqueio_semana">
                                <input type="hidden" name="restaurante_id" value="<?= $restauranteId ?>">
                                <input type="hidden" name="data_inicio" value="<?= h($capacidadeData) ?>">
                                <input type="text" class="fb-input fb-grow" name="motivo" placeholder="Motivo (obrigatório)" required>
                                <button type="submit" class="fb-btn" data-confirm-title="Fechar 7 dias" data-confirm="Fechar este restaurante por 7 dias a partir de <?= h(format_date_br($capacidadeData)) ?>?" data-confirm-yes="Fechar" data-confirm-no="Voltar">Fechar</button>
                            </form>
                        </details>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($canManageBloqueios): ?>
        <div class="fb-card fb-mt">
            <div class="fb-card__head">
                <h5 class="fb-card__title">Fechamento semanal — <?= h(normalize_mojibake((string)($restauranteAtual['nome'] ?? ''))) ?></h5>
                <span class="fb-muted" style="font-size: 0.78rem;">recorrente, todas as semanas</span>
            </div>
            <div class="row g-2">
                <?php foreach ($diasSemanaLabels as $indiceDia => $rotuloDiaSemana): ?>
                    <?php $fechadoNoDia = array_key_exists($indiceDia, $fechamentoSemanalMap); ?>
                    <div class="col-6 col-md-3 col-xl-auto" style="flex: 1 1 0;">
                        <form method="post" action="/?r=reservasTematicas/admin">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="action" value="bloqueio_semanal">
                            <input type="hidden" name="restaurante_id" value="<?= $restauranteId ?>">
                            <input type="hidden" name="dia_semana" value="<?= $indiceDia ?>">
                            <input type="hidden" name="fechar" value="<?= $fechadoNoDia ? 0 : 1 ?>">
                            <?php if (!$fechadoNoDia): ?>
                                <input type="hidden" name="motivo" value="Fechamento semanal">
                            <?php endif; ?>
                            <button type="submit" class="fb-btn" style="width: 100%; <?= $fechadoNoDia ? 'background: var(--fb-neutral-bg); color: var(--fb-neutral); border-color: transparent;' : '' ?>" title="<?= $fechadoNoDia ? h($fechamentoSemanalMap[$indiceDia] ?: 'Fechado') : 'Aberto' ?>">
                                <?= h($rotuloDiaSemana) ?> <?= $fechadoNoDia ? '· fechado' : '' ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="fb-muted mb-0 fb-mt" style="font-size: 0.75rem;">Toque num dia para alternar. Capacidade padrão, turnos e janelas de reserva ficam no ambiente completo.</p>
        </div>
    <?php endif; ?>
</div>
