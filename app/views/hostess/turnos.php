<?php
$turnos = $this->data['turnos'] ?? [];
$completed = (int)($this->data['completed'] ?? 0);
$level = (string)($this->data['level'] ?? 'Bronze');
$reservas = $this->data['reservas'] ?? [];
$reservasTotal = (int)($this->data['reservas_total'] ?? 0);
$reservasPage = (int)($this->data['reservas_page'] ?? 1);
$reservasTotalPages = (int)($this->data['reservas_total_pages'] ?? 1);
$reservationFilters = $this->data['reservation_filters'] ?? [];
$user = Auth::user() ?? [];
$activeTab = strtolower(trim((string)($_GET['aba'] ?? 'reservas')));
$activeTab = in_array($activeTab, ['reservas', 'turnos', 'conta'], true) ? $activeTab : 'reservas';
$monthShort = [1 => 'JAN', 2 => 'FEV', 3 => 'MAR', 4 => 'ABR', 5 => 'MAI', 6 => 'JUN', 7 => 'JUL', 8 => 'AGO', 9 => 'SET', 10 => 'OUT', 11 => 'NOV', 12 => 'DEZ'];
$profileQuery = static function (array $changes = []) use ($reservationFilters, $activeTab): string {
    $params = array_filter(array_merge([
        'r' => 'hostess/perfil',
        'aba' => $activeTab,
        'periodo' => $reservationFilters['periodo'] ?? '',
        'data' => $reservationFilters['data'] ?? '',
        'status' => $reservationFilters['status'] ?? '',
        'q' => $reservationFilters['q'] ?? '',
    ], $changes), static fn($value) => $value !== '' && $value !== null);
    return '/?' . http_build_query($params);
};
$statusClass = static function (string $status): string {
    return match ($status) {
        'Finalizada' => 'success',
        'Nao compareceu', 'Não compareceu' => 'warning',
        'Cancelada' => 'danger',
        'Pre-reserva' => 'info',
        default => 'neutral',
    };
};
$tabLink = static function (string $tab) use ($profileQuery): string {
    return $profileQuery(['aba' => $tab, 'page' => '']);
};
?>

<style>
.hostess-space { --hs-ink:#17243a; --hs-muted:#63758c; --hs-line:#dfe8f0; --hs-bg:#f6fafc; --hs-cyan:#109bb5; --hs-cyan-soft:#e5f8fb; --hs-green:#138557; --hs-orange:#d86a13; color:var(--hs-ink); }
.hostess-space * { min-width:0; }.hostess-space__shell { max-width:1040px; margin:0 auto; }.hostess-space__header { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.35rem 0 1rem; }.hostess-space__identity { display:flex; align-items:center; gap:.8rem; }.hostess-space__photo,.hostess-space__fallback { width:52px; height:52px; flex:0 0 52px; border-radius:16px; object-fit:cover; }.hostess-space__fallback { display:grid; place-items:center; color:var(--hs-cyan); background:var(--hs-cyan-soft); font-size:1.35rem; }.hostess-space__identity small { display:block; color:var(--hs-muted); font-size:.69rem; font-weight:850; letter-spacing:.07em; text-transform:uppercase; }.hostess-space__identity h1 { margin:.12rem 0 0; font-size:1.28rem; font-weight:850; }.hostess-space__role { display:inline-flex; align-items:center; gap:.34rem; padding:.45rem .68rem; border:1px solid #bce5ed; border-radius:999px; color:#08778f; background:#fff; font-size:.73rem; font-weight:850; white-space:nowrap; }
.hostess-space__tabs { display:flex; gap:.3rem; padding:.35rem; border:1px solid var(--hs-line); border-radius:14px; background:var(--ab-card,#fff); box-shadow:0 8px 24px rgba(25,46,70,.05); }.hostess-space__tabs a { display:flex; align-items:center; justify-content:center; gap:.42rem; min-height:42px; padding:.55rem .8rem; border-radius:10px; color:#64748b; font-size:.78rem; font-weight:850; text-decoration:none; }.hostess-space__tabs a.is-active { color:#087a92; background:var(--hs-cyan-soft); box-shadow:inset 0 0 0 1px #c4e9ef; }.hostess-space__view { display:none; margin-top:1rem; }.hostess-space__view.is-active { display:block; }.hostess-space__card { border:1px solid var(--hs-line); border-radius:16px; background:var(--ab-card,#fff); box-shadow:0 10px 28px rgba(25,46,70,.055); }.hostess-space__card + .hostess-space__card { margin-top:1rem; }
.hostess-space__section-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1.05rem 1.15rem .85rem; }.hostess-space__eyebrow { margin:0 0 .18rem; color:var(--hs-cyan); font-size:.68rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; }.hostess-space h2 { margin:0; font-size:1.06rem; font-weight:850; }.hostess-space__section-head p:not(.hostess-space__eyebrow) { margin:.25rem 0 0; color:var(--hs-muted); font-size:.78rem; }.hostess-space__count { display:inline-flex; align-items:center; gap:.35rem; padding:.4rem .6rem; border-radius:999px; color:#08778f; background:var(--hs-cyan-soft); font-size:.7rem; font-weight:850; white-space:nowrap; }
.hostess-space__periods { display:flex; flex-wrap:wrap; align-items:center; gap:.45rem; padding:0 1.15rem 1rem; }.hostess-space__periods a { padding:.4rem .64rem; border:1px solid var(--hs-line); border-radius:999px; color:#5d7088; background:#fff; font-size:.72rem; font-weight:850; text-decoration:none; }.hostess-space__periods a.is-active { color:#fff; border-color:var(--hs-cyan); background:var(--hs-cyan); }.hostess-space__filter { margin-left:auto; padding:.4rem .64rem; border:1px solid #c9dce7; border-radius:999px; color:#08778f; background:#fff; font-size:.72rem; font-weight:850; cursor:pointer; }.hostess-space__filter i { margin-right:.25rem; }.hostess-space__advanced { padding:0 1.15rem 1rem; }.hostess-space__advanced[hidden] { display:none; }.hostess-space__filter-grid { display:grid; grid-template-columns:150px 150px minmax(0,1fr) auto; gap:.65rem; padding:.85rem; border:1px solid #d7e6ee; border-radius:13px; background:#f8fcfd; }.hostess-space label { display:block; margin:0 0 .28rem; color:#4f627a; font-size:.67rem; font-weight:900; letter-spacing:.04em; text-transform:uppercase; }.hostess-space .form-control,.hostess-space .form-select { min-height:42px; color:var(--hs-ink); border-color:#d3e0ea; font-weight:650; }.hostess-space__filter-submit { align-self:end; min-height:42px; font-weight:850; }
.hostess-reservations { padding:0 .55rem .55rem; }.hostess-reservation { display:grid; grid-template-columns:52px minmax(175px,1fr) minmax(140px,.8fr) minmax(120px,.65fr) auto; gap:.75rem; align-items:center; padding:.82rem .7rem; border-radius:13px; }.hostess-reservation + .hostess-reservation { border-top:1px solid #e8eef4; }.hostess-reservation:hover { background:#f7fbfd; }.hostess-reservation__date { display:grid; place-items:center; width:48px; min-height:48px; border:1px solid #c6e5eb; border-radius:14px; color:#08778f; background:var(--hs-cyan-soft); text-align:center; }.hostess-reservation__date b { display:block; font-size:1.1rem; line-height:1; }.hostess-reservation__date span { display:block; margin-top:.14rem; font-size:.59rem; font-weight:900; letter-spacing:.06em; text-transform:uppercase; }.hostess-reservation__person { font-size:.9rem; font-weight:850; }.hostess-reservation__person span { display:block; margin-top:.16rem; color:var(--hs-muted); font-size:.74rem; font-weight:700; }.hostess-reservation__place { color:#4f627a; font-size:.77rem; line-height:1.7; }.hostess-reservation__place .tag { font-size:.69rem; }.hostess-reservation__pax b { display:block; font-size:.96rem; }.hostess-reservation__pax span { display:block; margin-top:.16rem; color:var(--hs-muted); font-size:.7rem; font-weight:750; }.hostess-reservation__actions { display:flex; align-items:center; justify-content:flex-end; gap:.45rem; }.hostess-status { display:inline-flex; align-items:center; padding:.32rem .52rem; border-radius:999px; font-size:.67rem; font-weight:850; white-space:nowrap; }.hostess-status.success { color:#087c45; background:#e8f8ef; }.hostess-status.warning { color:#9d510c; background:#fff2e4; }.hostess-status.danger { color:#b33138; background:#ffeaed; }.hostess-status.info { color:#087b99; background:#e8f8fc; }.hostess-status.neutral { color:#5d6d84; background:#eef2f6; }.hostess-history-button { display:grid; place-items:center; width:34px; height:34px; border:1px solid #cbdce7; border-radius:10px; color:#08778f; background:#fff; }.hostess-space__empty { display:grid; place-items:center; min-height:175px; padding:1rem; color:var(--hs-muted); text-align:center; font-size:.84rem; }.hostess-space__empty i { display:block; margin-bottom:.45rem; color:#93b3c2; font-size:1.35rem; }.hostess-space__pagination { display:flex; align-items:center; justify-content:space-between; gap:.8rem; padding:.8rem 1.15rem; border-top:1px solid var(--hs-line); color:var(--hs-muted); font-size:.74rem; }.hostess-space__pagination nav { display:flex; gap:.35rem; }.hostess-space__pagination a { display:grid; place-items:center; min-width:32px; height:32px; border:1px solid var(--hs-line); border-radius:9px; color:#44607b; text-decoration:none; font-weight:850; }.hostess-space__pagination a.is-active { color:#fff; border-color:var(--hs-cyan); background:var(--hs-cyan); }
.hostess-turns { padding:0 .55rem .55rem; }.hostess-turn { display:grid; grid-template-columns:1.1fr minmax(115px,.7fr) minmax(110px,.6fr) auto; gap:.8rem; align-items:center; padding:.85rem .7rem; border-radius:13px; }.hostess-turn + .hostess-turn { border-top:1px solid #e8eef4; }.hostess-turn__restaurant { font-size:.88rem; font-weight:850; }.hostess-turn__restaurant span { display:block; margin-top:.14rem; color:var(--hs-muted); font-size:.74rem; font-weight:700; }.hostess-turn__time { color:#50647d; font-size:.77rem; }.hostess-turn__pax b { display:block; font-size:.96rem; }.hostess-turn__pax span { color:var(--hs-muted); font-size:.69rem; font-weight:750; }
.hostess-account { display:grid; grid-template-columns:minmax(0,1.25fr) minmax(250px,.75fr); gap:1rem; }.hostess-account__card { padding:1.1rem; }.hostess-account__card h3 { margin:0; font-size:.96rem; font-weight:850; }.hostess-account__card p { margin:.25rem 0 0; color:var(--hs-muted); font-size:.77rem; }.hostess-account__password { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.65rem; margin-top:1rem; }.hostess-account__password .btn { align-self:end; min-height:42px; font-weight:850; }.hostess-account__photo { display:flex; flex-direction:column; gap:.75rem; margin-top:1rem; }.hostess-account__photo .btn { min-height:42px; font-weight:850; }.hostess-space__trust { display:flex; gap:.5rem; align-items:flex-start; margin-top:.9rem; padding:.7rem; border-radius:11px; color:#386079; background:#eff8fb; font-size:.74rem; line-height:1.4; }.hostess-space__trust i { color:#08778f; }
.hostess-history-modal { width:min(620px,calc(100vw - 1.5rem)); max-height:min(720px,calc(100vh - 1.5rem)); border:0; border-radius:18px; color:var(--hs-ink); background:var(--ab-card,#fff); box-shadow:0 24px 80px rgba(15,30,50,.32); padding:0; }.hostess-history-modal::backdrop { background:rgba(10,24,42,.58); backdrop-filter:blur(2px); }.hostess-history-modal__head { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; padding:1.15rem 1.2rem; border-bottom:1px solid var(--hs-line); }.hostess-history-modal__head h2 { margin:.15rem 0 0; font-size:1.08rem; font-weight:850; }.hostess-history-modal__head p { margin:0; color:var(--hs-muted); font-size:.72rem; font-weight:850; letter-spacing:.04em; text-transform:uppercase; }.hostess-history-modal__close { display:grid; place-items:center; width:34px; height:34px; border:1px solid #f0b5bb; border-radius:10px; color:#b22d38; background:#fff6f7; font-size:1rem; }.hostess-history-modal__body { max-height:calc(min(720px,100vh - 1.5rem) - 82px); overflow:auto; padding:1rem 1.2rem 1.2rem; }.hostess-history-modal__summary { margin-bottom:.9rem; padding:.75rem .85rem; border:1px solid var(--hs-line); border-radius:12px; background:#f7fbfd; color:#536a82; font-size:.8rem; }.hostess-history-event { position:relative; padding:0 0 1rem 1.15rem; border-left:2px solid #c9e8ee; }.hostess-history-event:last-child { padding-bottom:0; }.hostess-history-event::before { position:absolute; left:-.38rem; top:.15rem; width:.62rem; height:.62rem; border-radius:50%; background:var(--hs-cyan); content:""; }.hostess-history-event time { display:block; color:var(--hs-muted); font-size:.7rem; font-weight:750; }.hostess-history-event strong { display:block; margin-top:.18rem; font-size:.87rem; }.hostess-history-event span { display:block; margin-top:.18rem; color:#596d84; font-size:.76rem; line-height:1.42; }.hostess-history-change { margin-top:.4rem; padding:.42rem .55rem; border-radius:8px; background:#f2f7fa; color:#3c536b; font-size:.72rem; }.hostess-history-loading { padding:1rem 0; color:var(--hs-muted); text-align:center; font-size:.86rem; }
html[data-theme="dark"] .hostess-space { --hs-ink:#eef5fb; --hs-muted:#afbed0; --hs-line:#2d4055; --hs-bg:#111d2c; --hs-cyan-soft:#153b45; }html[data-theme="dark"] .hostess-space__card,.hostess-space__tabs,html[data-theme="dark"] .hostess-history-modal { background:var(--ab-card,#152235); }html[data-theme="dark"] .hostess-space__periods a,.hostess-space__filter,.hostess-space .form-control,.hostess-space .form-select,.hostess-history-button { color:#e8f3fb; border-color:#3c5268; background:#172638; }html[data-theme="dark"] .hostess-space__filter-grid { border-color:#324b5b; background:#142836; }html[data-theme="dark"] .hostess-reservation:hover { background:#192b3c; }html[data-theme="dark"] .hostess-history-modal__summary,html[data-theme="dark"] .hostess-history-change { color:#c6d6e4; background:#172b3c; }
@media (max-width:767px) { .hostess-space__header { align-items:flex-start; }.hostess-space__role { font-size:0; width:38px; height:38px; justify-content:center; padding:0; }.hostess-space__role i { font-size:.92rem; }.hostess-space__tabs { width:100%; }.hostess-space__tabs a { flex:1; padding:.52rem .35rem; font-size:.71rem; }.hostess-space__tabs a i { display:none; }.hostess-space__section-head { padding:.95rem .9rem .75rem; }.hostess-space__section-head p:not(.hostess-space__eyebrow) { max-width:230px; }.hostess-space__count { font-size:.65rem; }.hostess-space__periods,.hostess-space__advanced { padding-left:.9rem; padding-right:.9rem; }.hostess-space__filter { margin-left:0; }.hostess-space__filter-grid { grid-template-columns:1fr 1fr; }.hostess-space__filter-grid .hostess-space__search { grid-column:1 / -1; }.hostess-space__filter-submit { grid-column:1 / -1; width:100%; }.hostess-reservation { grid-template-columns:50px minmax(0,1fr) auto; gap:.45rem .65rem; padding:.8rem .45rem; }.hostess-reservation__date { grid-row:1 / span 2; }.hostess-reservation__person { grid-column:2; }.hostess-reservation__place { grid-column:2; }.hostess-reservation__pax { grid-column:3; grid-row:2; text-align:right; }.hostess-reservation__actions { grid-column:3; grid-row:1; }.hostess-space__pagination { flex-direction:column; }.hostess-turn { grid-template-columns:minmax(0,1fr) auto; gap:.5rem .7rem; }.hostess-turn__time { grid-column:1; }.hostess-turn__pax { grid-column:2; grid-row:2; text-align:right; }.hostess-account { grid-template-columns:1fr; }.hostess-account__password { grid-template-columns:1fr; }.hostess-history-modal__head,.hostess-history-modal__body { padding-left:1rem; padding-right:1rem; } }
</style>

<div class="hostess-space">
    <div class="hostess-space__shell">
        <header class="hostess-space__header">
            <div class="hostess-space__identity">
                <?php $photo = safe_public_upload_url((string)($user['foto_path'] ?? ''), 'profiles'); ?>
                <?php if ($photo !== ''): ?><img src="<?= h($photo) ?>" alt="" class="hostess-space__photo"><?php else: ?><span class="hostess-space__fallback"><i class="bi bi-person"></i></span><?php endif; ?>
                <div><small>Meu espaço</small><h1><?= h((string)($user['nome'] ?? 'Hostess')) ?></h1></div>
            </div>
            <span class="hostess-space__role"><i class="bi bi-person-check"></i> Hostess</span>
        </header>

        <nav class="hostess-space__tabs" aria-label="Navegação do perfil">
            <a href="<?= h($tabLink('reservas')) ?>" data-profile-tab="reservas" class="<?= $activeTab === 'reservas' ? 'is-active' : '' ?>"><i class="bi bi-calendar-heart"></i>Minhas reservas</a>
            <a href="<?= h($tabLink('turnos')) ?>" data-profile-tab="turnos" class="<?= $activeTab === 'turnos' ? 'is-active' : '' ?>"><i class="bi bi-clock-history"></i>Meus turnos</a>
            <a href="<?= h($tabLink('conta')) ?>" data-profile-tab="conta" class="<?= $activeTab === 'conta' ? 'is-active' : '' ?>"><i class="bi bi-shield-check"></i>Conta</a>
        </nav>

        <section class="hostess-space__view <?= $activeTab === 'reservas' ? 'is-active' : '' ?>" data-profile-view="reservas">
            <article class="hostess-space__card">
                <header class="hostess-space__section-head"><div><p class="hostess-space__eyebrow">Minha atividade</p><h2>Reservas criadas por mim</h2><p>Consulte suas reservas, inclusive finalizadas, canceladas e no-show.</p></div><span class="hostess-space__count"><i class="bi bi-calendar-check"></i><?= $reservasTotal ?> no filtro</span></header>
                <div class="hostess-space__periods" aria-label="Períodos rápidos">
                    <a class="<?= (int)($reservationFilters['periodo'] ?? 0) === 0 && ($reservationFilters['data'] ?? '') === date('Y-m-d') ? 'is-active' : '' ?>" href="<?= h($profileQuery(['periodo' => '', 'data' => '', 'page' => ''])) ?>">Hoje</a>
                    <?php foreach ([7, 15, 30] as $days): ?><a class="<?= (int)($reservationFilters['periodo'] ?? 0) === $days ? 'is-active' : '' ?>" href="<?= h($profileQuery(['periodo' => $days, 'data' => '', 'page' => ''])) ?>"><?= $days ?> dias</a><?php endforeach; ?>
                    <button type="button" class="hostess-space__filter" data-profile-filter><i class="bi bi-sliders"></i>Filtros</button>
                </div>
                <div class="hostess-space__advanced" data-profile-advanced hidden>
                    <form class="hostess-space__filter-grid" method="get" action="/" data-ajax-filter data-ajax-target=".app-content">
                        <input type="hidden" name="r" value="hostess/perfil"><input type="hidden" name="aba" value="reservas"><input type="hidden" name="periodo" value="">
                        <div><label for="profileDate">Data</label><input id="profileDate" name="data" type="date" class="form-control" value="<?= h($reservationFilters['data'] ?? '') ?>"></div>
                        <div><label for="profileStatus">Status</label><select id="profileStatus" name="status" class="form-select"><option value="">Todos</option><?php foreach (['Reservada','Finalizada','Nao compareceu','Cancelada','Pre-reserva'] as $option): ?><option value="<?= h($option) ?>" <?= ($reservationFilters['status'] ?? '') === $option ? 'selected' : '' ?>><?= h($option === 'Nao compareceu' ? 'Não compareceu' : $option) ?></option><?php endforeach; ?></select></div>
                        <div class="hostess-space__search"><label for="profileSearch">Buscar</label><input id="profileSearch" name="q" type="search" class="form-control" value="<?= h($reservationFilters['q'] ?? '') ?>" placeholder="UH, titular ou restaurante"></div>
                        <button class="btn btn-primary hostess-space__filter-submit" type="submit"><i class="bi bi-search me-1"></i>Aplicar</button>
                    </form>
                </div>
                <div class="hostess-reservations">
                    <?php foreach ($reservas as $reserva): ?>
                        <?php
                        $status = (string)($reserva['status_reserva'] ?? $reserva['status'] ?? 'Reservada');
                        $dateTime = strtotime((string)($reserva['data_reserva'] ?? ''));
                        ?>
                        <article class="hostess-reservation">
                            <div class="hostess-reservation__date"><b><?= $dateTime ? date('d', $dateTime) : '-' ?></b><span><?= $dateTime ? ($monthShort[(int)date('n', $dateTime)] ?? 'DATA') : 'DATA' ?></span></div>
                            <div class="hostess-reservation__person"><?= h((string)($reserva['titular_nome_display'] ?? 'Sem titular')) ?><span>UH <?= h((string)($reserva['uh_numero'] ?? 'Pendente')) ?><?= !empty($reserva['grupo_nome_display']) && $reserva['grupo_nome_display'] !== '-' ? ' · Grupo ' . h((string)$reserva['grupo_nome_display']) : '' ?></span></div>
                            <div class="hostess-reservation__place"><span class="tag <?= restaurant_badge_class($reserva['restaurante'] ?? '') ?>"><?= h((string)($reserva['restaurante'] ?? 'Restaurante')) ?></span><br><?= h((string)($reserva['turno_hora'] ?? '--:--')) ?></div>
                            <div class="hostess-reservation__pax"><b><?= (int)($reserva['pax'] ?? 0) ?> PAX</b><span><?= (int)($reserva['pax_adulto_calc'] ?? 0) ?> adultos · <?= (int)($reserva['pax_chd_calc'] ?? 0) ?> CHD</span></div>
                            <div class="hostess-reservation__actions"><span class="hostess-status <?= h($statusClass($status)) ?>"><?= h($status === 'Nao compareceu' ? 'Não compareceu' : $status) ?></span><button type="button" class="hostess-history-button" data-hostess-history="<?= (int)($reserva['id'] ?? 0) ?>" aria-label="Ver histórico da reserva"><i class="bi bi-clock-history"></i></button></div>
                        </article>
                    <?php endforeach; ?>
                    <?php if ($reservas === []): ?><div class="hostess-space__empty"><div><i class="bi bi-calendar-x"></i>Nenhuma reserva criada por você neste filtro.</div></div><?php endif; ?>
                </div>
                <?php if ($reservasTotalPages > 1): ?><footer class="hostess-space__pagination"><span>Página <?= $reservasPage ?> de <?= $reservasTotalPages ?></span><nav aria-label="Paginação das minhas reservas"><?php for ($i = 1; $i <= $reservasTotalPages; $i++): ?><?php if ($i === 1 || $i === $reservasTotalPages || abs($i - $reservasPage) <= 1): ?><a class="<?= $i === $reservasPage ? 'is-active' : '' ?>" href="<?= h($profileQuery(['page' => $i])) ?>" data-ajax-link data-ajax-target=".app-content"><?= $i ?></a><?php endif; ?><?php endfor; ?></nav></footer><?php endif; ?>
            </article>
        </section>

        <section class="hostess-space__view <?= $activeTab === 'turnos' ? 'is-active' : '' ?>" data-profile-view="turnos">
            <article class="hostess-space__card">
                <header class="hostess-space__section-head"><div><p class="hostess-space__eyebrow">Meu histórico</p><h2>Turnos realizados</h2><p><?= $completed ?> turnos concluídos · nível <?= h($level) ?>.</p></div><span class="hostess-space__count"><i class="bi bi-clock-history"></i><?= count($turnos) ?> recentes</span></header>
                <div class="hostess-turns">
                    <?php foreach ($turnos as $turno): ?><article class="hostess-turn"><div class="hostess-turn__restaurant"><?= h((string)($turno['restaurante'] ?? 'Restaurante')) ?><span><?= h((string)($turno['operacao'] ?? 'Operação')) ?></span></div><div class="hostess-turn__time">Início: <?= h((string)($turno['inicio_em'] ?? '-')) ?><br><?= !empty($turno['fim_em']) ? 'Fim: ' . h((string)$turno['fim_em']) : 'Em andamento' ?></div><div class="hostess-turn__pax"><b><?= (int)($turno['total_pax'] ?? 0) ?> PAX</b><span><?= (int)($turno['total_acessos'] ?? 0) ?> registros</span></div><span class="hostess-status <?= empty($turno['fim_em']) ? 'warning' : 'success' ?>"><?= empty($turno['fim_em']) ? 'Em andamento' : 'Encerrado' ?></span></article><?php endforeach; ?>
                    <?php if ($turnos === []): ?><div class="hostess-space__empty"><div><i class="bi bi-clock"></i>Sem turnos registrados nesta conta.</div></div><?php endif; ?>
                </div>
            </article>
        </section>

        <section class="hostess-space__view <?= $activeTab === 'conta' ? 'is-active' : '' ?>" data-profile-view="conta">
            <div class="hostess-account">
                <article class="hostess-space__card hostess-account__card"><p class="hostess-space__eyebrow">Segurança</p><h3>Alterar minha senha</h3><p>Use uma senha individual. Contas que compartilham e-mail não podem compartilhar a mesma senha.</p><form class="hostess-account__password" method="post" action="/?r=hostess/senha"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><div><label>Senha atual</label><input class="form-control" type="password" name="senha_atual" autocomplete="current-password" required></div><div><label>Nova senha</label><input class="form-control" type="password" name="nova_senha" autocomplete="new-password" minlength="8" required></div><div><label>Confirmar senha</label><input class="form-control" type="password" name="confirmacao_senha" autocomplete="new-password" minlength="8" required></div><button class="btn btn-primary" type="submit"><i class="bi bi-key me-1"></i>Salvar senha</button></form><div class="hostess-space__trust"><i class="bi bi-shield-check"></i><span>A troca fica registrada na auditoria, sem armazenar sua senha ou o hash dela.</span></div></article>
                <article class="hostess-space__card hostess-account__card"><p class="hostess-space__eyebrow">Identificação</p><h3>Foto do perfil</h3><p>Ajuda a equipe a reconhecer sua conta na operação.</p><form class="hostess-account__photo" method="post" action="/?r=hostess/foto" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><div><label>Nova foto</label><input type="file" name="foto" class="form-control" accept="image/png,image/jpeg,image/webp" required></div><button class="btn btn-outline-primary" type="submit"><i class="bi bi-upload me-1"></i>Atualizar foto</button></form></article>
            </div>
        </section>
    </div>
</div>

<dialog class="hostess-history-modal" id="hostessHistoryModal" aria-labelledby="hostessHistoryTitle">
    <header class="hostess-history-modal__head"><div><p>Auditoria da reserva</p><h2 id="hostessHistoryTitle">Histórico da reserva</h2></div><button class="hostess-history-modal__close" type="button" aria-label="Fechar histórico" data-hostess-history-close><i class="bi bi-x-lg"></i></button></header>
    <div class="hostess-history-modal__body" data-hostess-history-body><div class="hostess-history-loading">Carregando histórico...</div></div>
</dialog>
<script>
(() => {
    const root = document.querySelector('.hostess-space');
    const modal = document.getElementById('hostessHistoryModal');
    const body = modal?.querySelector('[data-hostess-history-body]');
    if (!root || !modal || !body) return;
    const selectTab = (name) => {
        root.querySelectorAll('[data-profile-tab]').forEach((item) => item.classList.toggle('is-active', item.dataset.profileTab === name));
        root.querySelectorAll('[data-profile-view]').forEach((item) => item.classList.toggle('is-active', item.dataset.profileView === name));
    };
    root.querySelectorAll('[data-profile-tab]').forEach((link) => link.addEventListener('click', (event) => { event.preventDefault(); const tab = link.dataset.profileTab; selectTab(tab); const url = new URL(link.href, window.location.origin); window.history.replaceState({}, '', url); }));
    root.querySelector('[data-profile-filter]')?.addEventListener('click', () => { const advanced = root.querySelector('[data-profile-advanced]'); if (advanced) advanced.hidden = !advanced.hidden; });
    const safe = (value) => String(value ?? '—');
    const create = (tag, className, text) => { const node = document.createElement(tag); if (className) node.className = className; if (text !== undefined) node.textContent = text; return node; };
    const render = (history) => { body.replaceChildren(); const reservation = history.reserva || {}; body.append(create('div', 'hostess-history-modal__summary', `${safe(reservation.uh)} · ${safe(reservation.data)} · ${safe(reservation.restaurante)} · ${safe(reservation.turno)}`)); const events = Array.isArray(history.eventos) ? history.eventos : []; if (!events.length) { body.append(create('div', 'hostess-history-loading', 'Ainda não há eventos registrados para esta reserva.')); return; } events.forEach((event) => { const item = create('article', 'hostess-history-event'); item.append(create('time', '', `${safe(event.criado_em_formatado)} · ${safe(event.usuario)}`)); item.append(create('strong', '', safe(event.titulo))); if (event.justificativa) item.append(create('span', '', event.justificativa)); (Array.isArray(event.alteracoes) ? event.alteracoes : []).forEach((change) => item.append(create('div', 'hostess-history-change', change.criacao ? `${safe(change.label)}: ${safe(change.depois)}` : `${safe(change.label)}: ${safe(change.antes)} → ${safe(change.depois)}`))); body.append(item); }); };
    document.addEventListener('click', async (event) => { const button = event.target.closest('[data-hostess-history]'); if (!button) return; const id = Number(button.dataset.hostessHistory || 0); if (!id) return; body.replaceChildren(create('div', 'hostess-history-loading', 'Carregando histórico...')); modal.showModal(); try { const response = await fetch(`/?r=relatoriosTematicos/historico&id=${encodeURIComponent(id)}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' }); const payload = await response.json(); if (!response.ok || !payload.ok) throw new Error(payload.message || 'Não foi possível carregar este histórico.'); render(payload.historico || {}); } catch (error) { body.replaceChildren(create('div', 'hostess-history-loading', error.message || 'Não foi possível carregar este histórico.')); } });
    modal.querySelector('[data-hostess-history-close]')?.addEventListener('click', () => modal.close());
    modal.addEventListener('click', (event) => { if (event.target === modal) modal.close(); });
})();
</script>
