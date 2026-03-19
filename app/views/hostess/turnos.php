<?php
$turnos = $this->data['turnos'] ?? [];
$completed = $this->data['completed'] ?? 0;
$level = $this->data['level'] ?? 'Bronze';
$flash = $this->data['flash'] ?? null;
$user = Auth::user();
?>
<div class="card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-start">
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
                <?php if (!empty($user['foto_path'])): ?>
                    <img src="<?= h($user['foto_path']) ?>" alt="Foto" style="width:64px;height:64px;border-radius:50%;object-fit:cover;">
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center bg-light" style="width:64px;height:64px;border-radius:50%;">
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

            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['type']) ?> mt-3"><?= h($flash['message']) ?></div>
            <?php endif; ?>

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
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Restaurante</th>
                    <th>Operação</th>
                    <th>Início</th>
                    <th>Fim</th>
                    <th>Total PAX</th>
                    <th>Acessos</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($turnos as $turno): ?>
                    <tr>
                        <td><span class="tag <?= restaurant_badge_class($turno['restaurante']) ?>"><?= h($turno['restaurante']) ?></span></td>
                        <td><span class="tag <?= operation_badge_class($turno['operacao']) ?>"><?= h($turno['operacao']) ?></span></td>
                        <td><?= h($turno['inicio_em']) ?></td>
                        <td><?= h($turno['fim_em'] ?? '-') ?></td>
                        <td><?= (int)$turno['total_pax'] ?></td>
                        <td><?= (int)$turno['total_acessos'] ?></td>
                        <td>
                            <?php if ($turno['fim_em']): ?>
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

