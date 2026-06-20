<?php
$turnos = $this->data['turnos'] ?? [];
$completed = $this->data['completed'] ?? 0;
$level = $this->data['level'] ?? 'Bronze';
$flash = $this->data['flash'] ?? null;
$user = Auth::user();
?>
<style>
    .hostess-turnos-page,
    .hostess-turnos-page .row,
    .hostess-turnos-page [class*="col-"] {
        min-width: 0;
    }
    .hostess-turnos-page .card {
        overflow: hidden;
    }
    .hostess-profile-photo {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        object-fit: cover;
        flex: 0 0 64px;
    }
    .hostess-profile-placeholder {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        flex: 0 0 64px;
    }
    .hostess-turnos-table td,
    .hostess-turnos-table th {
        vertical-align: middle;
    }
    @media (max-width: 991.98px) {
        .hostess-turnos-page .card.p-4 {
            padding: 1rem !important;
            border-radius: 18px;
        }
        .hostess-turnos-hero {
            gap: 1rem;
        }
        .hostess-turnos-hero .btn {
            width: 100%;
            justify-content: center;
        }
    }
    @media (max-width: 575.98px) {
        .hostess-turnos-page h3 {
            font-size: 1.35rem;
        }
        .hostess-turnos-page h5 {
            font-size: 1rem;
        }
        .hostess-turnos-page .stat-chip {
            width: 100%;
            justify-content: center;
            white-space: normal;
            text-align: center;
        }
        .hostess-turnos-table,
        .hostess-turnos-table tbody,
        .hostess-turnos-table tr,
        .hostess-turnos-table td {
            display: block;
            width: 100%;
        }
        .hostess-turnos-table thead {
            display: none;
        }
        .hostess-turnos-table tr {
            border: 1px solid var(--ab-border);
            border-radius: 16px;
            background: var(--ab-card);
            padding: .85rem;
            margin-bottom: .75rem;
            box-shadow: 0 10px 22px rgba(15, 23, 42, .06);
        }
        .hostess-turnos-table td {
            border: 0;
            padding: .35rem 0 !important;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            text-align: right;
            overflow-wrap: anywhere;
        }
        .hostess-turnos-table td::before {
            content: attr(data-label);
            color: var(--ab-muted);
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
            text-align: left;
        }
        .hostess-turnos-table td .tag,
        .hostess-turnos-table td .badge {
            max-width: 62%;
            white-space: normal;
            text-align: center;
        }
        .hostess-turnos-table td[colspan] {
            display: block;
            text-align: left;
        }
        .hostess-turnos-table td[colspan]::before {
            content: "";
            display: none;
        }
    }
</style>

<div class="hostess-turnos-page">
<div class="card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap hostess-turnos-hero">
        <div>
            <div class="text-uppercase text-muted small">Minha operação</div>
            <h3 class="fw-bold mb-1">Histórico de turnos</h3>
            <div class="text-muted">Acompanhe turnos finalizados e em andamento.</div>
        </div>
        <a class="btn btn-outline-primary" href="/?r=access/index"><i class="bi bi-clipboard-check me-1"></i>Registrar acesso</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-lg-4">
        <div class="card p-4">
            <div class="d-flex align-items-center gap-3">
                <?php $safeHostessPhoto = safe_public_upload_url((string)($user['foto_path'] ?? ''), 'profiles'); ?>
                <?php if ($safeHostessPhoto !== ''): ?>
                    <img src="<?= h($safeHostessPhoto) ?>" alt="Foto" class="hostess-profile-photo">
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center bg-light hostess-profile-placeholder">
                        <i class="bi bi-person" style="font-size:1.5rem;"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <div class="fw-semibold"><?= h($user['nome']) ?></div>
                    <div class="text-muted small">Perfil: Hostess</div>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2 flex-wrap">
                <span class="stat-chip"><i class="bi bi-award"></i>Nível <?= h($level) ?></span>
                <span class="stat-chip"><i class="bi bi-flag"></i><?= (int)$completed ?> turnos concluídos</span>
            </div>


            <form class="mt-3" method="post" action="/?r=hostess/foto" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <label class="form-label">Atualizar foto de perfil</label>
                <input type="file" name="foto" class="form-control" accept="image/png,image/jpeg,image/webp" required>
                <button class="btn btn-primary btn-sm mt-2"><i class="bi bi-upload me-1"></i>Enviar foto</button>
            </form>
        </div>
    </div>
    <div class="col-12 col-lg-8">
        <div class="card p-4">
            <h5 class="fw-bold mb-3">Indicadores pessoais</h5>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <div class="stat-chip"><i class="bi bi-check2-circle"></i>Turnos concluídos: <?= (int)$completed ?></div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="stat-chip"><i class="bi bi-stars"></i>Nível atual: <?= h($level) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card p-4">
    <div class="table-responsive">
        <table class="table table-sm align-middle hostess-turnos-table">
            <thead>
                <tr>
                    <th>Restaurante</th>
                    <th>Operação</th>
                    <th>Início</th>
                    <th>Fim</th>
                    <th>PAX</th>
                    <th>Registros</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($turnos as $turno): ?>
                    <?php
                    $isEncerrado = !empty($turno['fim_em']);
                    $isTematica = (int)($turno['is_tematica'] ?? 0) === 1;
                    $totalAcessos = (int)($turno['total_acessos'] ?? 0);
                    $reservasConferidas = (int)($turno['reservas_conferidas'] ?? 0);
                    $paxRegistradas = (int)($turno['pax_registradas'] ?? 0);
                    $displayPax = $isTematica ? $paxRegistradas : (int)($turno['total_pax'] ?? 0);
                    $displayRegistros = $isTematica ? $reservasConferidas : $totalAcessos;
                    $isCancelado = $isEncerrado && $displayRegistros === 0;
                    ?>
                    <tr>
                        <td data-label="Restaurante"><span class="tag <?= restaurant_badge_class($turno['restaurante']) ?>"><?= h($turno['restaurante']) ?></span></td>
                        <td data-label="Operação"><span class="tag <?= operation_badge_class($turno['operacao']) ?>"><?= h($turno['operacao']) ?></span></td>
                        <td data-label="Início"><?= h($turno['inicio_em']) ?></td>
                        <td data-label="Fim"><?= h($turno['fim_em'] ?? '-') ?></td>
                        <td data-label="PAX">
                            <div class="fw-semibold"><?= $displayPax ?></div>
                            <div class="text-muted small"><?= $isTematica ? 'PAX registradas' : 'Total PAX' ?></div>
                        </td>
                        <td data-label="Registros">
                            <div class="fw-semibold"><?= $displayRegistros ?></div>
                            <div class="text-muted small"><?= $isTematica ? 'Reservas conferidas' : 'Acessos' ?></div>
                        </td>
                        <td data-label="Status">
                            <?php if ($isCancelado): ?>
                                <span class="badge badge-danger">Cancelado</span>
                            <?php elseif ($isEncerrado): ?>
                                <span class="badge badge-success">Encerrado</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Em andamento</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($turnos)): ?>
                    <tr><td colspan="7" class="text-muted">Sem turnos registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
