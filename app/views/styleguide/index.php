<?php /* Galeria interna dos componentes do remake visual. Validação apenas — nenhuma tela de produção depende desta view. */ ?>
<div class="fb-page" style="max-width: 860px;">

    <div class="fb-card" style="margin-bottom: 1rem;">
        <div class="fb-card__head">
            <div>
                <h4 class="fb-card__title" style="font-size: 1.2rem;">Styleguide — identidade "Do mar à mesa"</h4>
                <p class="fb-muted" style="margin: 4px 0 0; font-size: 0.85rem;">
                    Tokens e componentes do remake visual. Troque o tema no menu do usuário para validar
                    Dia, Modo Jantar (escuro), Entardecer (sand) e Galés (ocean).
                </p>
            </div>
        </div>
        <div class="fb-row">
            <img src="/assets/logo-fbcontrol.svg?v=20260704" alt="Logo FBControl claro" style="height: 56px; background: #F6F1E7; border-radius: 12px; padding: 6px 14px;">
            <img src="/assets/logo-fbcontrol-dark.svg?v=20260704" alt="Logo FBControl escuro" style="height: 56px; background: #0E262E; border-radius: 12px; padding: 6px 14px;">
            <img src="/assets/favicon-fb-white.svg?v=20260704" alt="Favicon FBControl" style="height: 40px;">
        </div>
    </div>

    <div class="fb-card" style="margin-bottom: 1rem;">
        <h5 class="fb-card__title" style="margin-bottom: 0.75rem;">Paleta (tema atual)</h5>
        <div class="fb-row">
            <span class="fb-badge" style="background: var(--fb-bg); color: var(--fb-ink); border: 1px solid var(--fb-border);">fundo</span>
            <span class="fb-badge" style="background: var(--fb-card); color: var(--fb-ink); border: 1px solid var(--fb-border);">cartão</span>
            <span class="fb-badge" style="background: var(--fb-brand); color: #FDFBF6;">turquesa Galés</span>
            <span class="fb-badge" style="background: var(--fb-action); color: var(--fb-on-action);">terracota Oca</span>
            <span class="fb-badge" style="background: var(--fb-ink); color: var(--fb-bg);">tinta do Recife</span>
        </div>
    </div>

    <div class="fb-card" style="margin-bottom: 1rem;">
        <h5 class="fb-card__title" style="margin-bottom: 0.75rem;">Botões</h5>
        <div class="fb-row">
            <button type="button" class="fb-btn fb-btn--primary">Registrar acesso</button>
            <button type="button" class="fb-btn">Exportar recorte</button>
            <button type="button" class="fb-btn fb-btn--ghost">Cancelar</button>
            <button type="button" class="fb-btn fb-btn--danger">Não compareceu</button>
        </div>
        <div class="fb-mt">
            <button type="button" class="fb-btn fb-btn--primary fb-btn--lg">Ação principal (52px, largura total no mobile)</button>
        </div>
    </div>

    <div class="fb-card" style="margin-bottom: 1rem;">
        <h5 class="fb-card__title" style="margin-bottom: 0.75rem;">Métricas</h5>
        <div class="fb-metric-grid">
            <div class="fb-metric">
                <p class="fb-metric__label">PAX no período</p>
                <p class="fb-metric__value">12.480</p>
                <p class="fb-metric__delta fb-metric__delta--up"><i class="bi bi-graph-up-arrow"></i> +8% vs anterior</p>
            </div>
            <div class="fb-metric">
                <p class="fb-metric__label">UHs atendidas</p>
                <p class="fb-metric__value">1.203</p>
                <p class="fb-metric__delta">86% da ocupação</p>
            </div>
            <div class="fb-metric">
                <p class="fb-metric__label">Alertas</p>
                <p class="fb-metric__value" style="color: var(--fb-danger);">14</p>
                <p class="fb-metric__delta fb-metric__delta--bad">6 duplicidades</p>
            </div>
            <div class="fb-metric">
                <p class="fb-metric__label">No-show temático</p>
                <p class="fb-metric__value">4,2%</p>
                <p class="fb-metric__delta fb-metric__delta--up">−1,1 p.p.</p>
            </div>
        </div>
    </div>

    <div class="fb-card" style="margin-bottom: 1rem;">
        <h5 class="fb-card__title" style="margin-bottom: 0.75rem;">Status operacionais</h5>
        <div class="fb-row">
            <span class="fb-badge fb-badge--ok">ok · Coqueiro</span>
            <span class="fb-badge fb-badge--duplicado">duplicado · Pitanga</span>
            <span class="fb-badge fb-badge--fora-horario">fora de horário · Manga</span>
            <span class="fb-badge fb-badge--multiplo">múltiplo · Jambo</span>
            <span class="fb-badge fb-badge--day-use">day use · Água</span>
            <span class="fb-badge fb-badge--nao-informado">não informado · Concha</span>
        </div>
    </div>

    <div class="fb-card" style="margin-bottom: 1rem;">
        <h5 class="fb-card__title" style="margin-bottom: 0.75rem;">Filtros (chips) e abas</h5>
        <div class="fb-chiprow">
            <span class="fb-chip"><i class="bi bi-calendar3"></i> 27 jun – 3 jul</span>
            <span class="fb-chip fb-chip--active">Visão geral</span>
            <span class="fb-chip">Temáticos</span>
            <span class="fb-chip">Equipe</span>
            <span class="fb-chip"><i class="bi bi-sliders"></i> Filtros</span>
        </div>
    </div>

    <div class="fb-card" style="margin-bottom: 1rem;">
        <h5 class="fb-card__title" style="margin-bottom: 0.75rem;">Formulário operacional</h5>
        <div class="fb-field">
            <label class="fb-label" for="sg-uh">UH (entrada grande, teclado numérico)</label>
            <input type="text" inputmode="numeric" class="fb-input fb-input--big" id="sg-uh" value="1203" style="max-width: 220px;">
        </div>
        <div class="fb-field">
            <label class="fb-label">PAX (stepper com alvo de 44px)</label>
            <span class="fb-stepper">
                <button type="button" class="fb-stepper__btn" aria-label="Diminuir PAX"><i class="bi bi-dash"></i></button>
                <span class="fb-stepper__value">3</span>
                <button type="button" class="fb-stepper__btn" aria-label="Aumentar PAX"><i class="bi bi-plus"></i></button>
            </span>
        </div>
        <div class="fb-field" style="max-width: 320px;">
            <label class="fb-label" for="sg-rest">Restaurante</label>
            <select class="fb-select" id="sg-rest">
                <option>Restaurante Corais</option>
                <option>Restaurante Giardino</option>
            </select>
        </div>
    </div>

    <div class="fb-card" style="margin-bottom: 1rem;">
        <h5 class="fb-card__title" style="margin-bottom: 0.75rem;">Lista com lotação</h5>
        <ul class="fb-list">
            <li class="fb-list__item">
                <div class="fb-grow">
                    <div class="fb-row" style="justify-content: space-between;">
                        <strong>Corais</strong>
                        <span class="fb-muted fb-num">1.120 PAX</span>
                    </div>
                    <div class="fb-progress fb-mt" style="margin-top: 6px;"><div class="fb-progress__bar" style="width: 82%;"></div></div>
                </div>
                <i class="bi bi-chevron-right fb-muted"></i>
            </li>
            <li class="fb-list__item">
                <div class="fb-grow">
                    <div class="fb-row" style="justify-content: space-between;">
                        <strong>Giardino · temático</strong>
                        <span class="fb-muted fb-num">42/60 PAX</span>
                    </div>
                    <div class="fb-progress" style="margin-top: 6px;"><div class="fb-progress__bar fb-progress__bar--warn" style="width: 70%;"></div></div>
                </div>
                <i class="bi bi-chevron-right fb-muted"></i>
            </li>
        </ul>
    </div>

    <div class="fb-card" style="margin-bottom: 1rem;">
        <h5 class="fb-card__title" style="margin-bottom: 0.75rem;">Tabela responsiva (cartões no celular, tabela no desktop)</h5>
        <table class="fb-table">
            <thead>
                <tr><th>UH</th><th>PAX</th><th>Horário</th><th>Status</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td data-label="UH" class="fb-num">1203</td>
                    <td data-label="PAX" class="fb-num">3</td>
                    <td data-label="Horário" class="fb-num">19h41</td>
                    <td data-label="Status"><span class="fb-badge fb-badge--ok">ok</span></td>
                </tr>
                <tr>
                    <td data-label="UH" class="fb-num">987</td>
                    <td data-label="PAX" class="fb-num">2</td>
                    <td data-label="Horário" class="fb-num">19h38</td>
                    <td data-label="Status"><span class="fb-badge fb-badge--duplicado">duplicado</span></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="fb-card" style="margin-bottom: 1rem;">
        <h5 class="fb-card__title" style="margin-bottom: 0.75rem;">Identidade por restaurante</h5>
        <p class="fb-muted" style="margin: 0 0 0.75rem; font-size: 0.85rem;">Cor + ícone vindos do cadastro (<code>cor_hex</code>, <code>icone</code>), com derivação por nome quando vazios.</p>
        <div class="fb-row" style="gap: 8px; margin-bottom: 0.9rem;">
            <?php foreach (['Restaurante Corais', 'Restaurante La Brasa', 'Restaurante Giardino', "Restaurante IX'u", 'Privileged'] as $nomeSelo): ?>
                <?= restaurante_selo($nomeSelo) ?>
            <?php endforeach; ?>
        </div>
        <div class="fb-row" style="gap: 12px;">
            <?php foreach (['Restaurante Corais', 'Restaurante La Brasa', 'Restaurante Giardino', "Restaurante IX'u"] as $nomeSelo): ?>
                <span class="fb-row" style="gap: 8px;">
                    <?= restaurante_selo($nomeSelo, 'icon') ?>
                    <span style="font-size: 0.85rem; font-weight: 600;"><?= h(preg_replace('/^Restaurante\s+/', '', $nomeSelo)) ?></span>
                </span>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="fb-card">
        <h5 class="fb-card__title" style="margin-bottom: 0.75rem;">Estado vazio</h5>
        <div class="fb-empty">
            <i class="bi bi-cup-hot"></i>
            <p class="fb-empty__title">Salão tranquilo por enquanto</p>
            <p style="margin: 0; font-size: 0.85rem;">Os registros do turno aparecem aqui assim que a operação começar.</p>
        </div>
    </div>

</div>
