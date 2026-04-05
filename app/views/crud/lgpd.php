<?php
$flash = $this->data['flash'] ?? null;
$summary = $this->data['summary'] ?? [];
$config = $this->data['config'] ?? [];
$requests = $this->data['requests'] ?? [];
$incidents = $this->data['incidents'] ?? [];
$retention = $this->data['retention'] ?? [];
$events = $this->data['events'] ?? [];
$canEdit = (bool)($this->data['can_edit'] ?? false);
$dbError = (string)($this->data['db_error'] ?? '');
?>

<div class="saas-page lgpd-page">
    <section class="saas-hero-card">
        <div class="saas-headline d-flex flex-wrap gap-3 align-items-start justify-content-between">
            <div>
                <div class="saas-label">Governanca</div>
                <h3 class="saas-title mb-1">Conformidade LGPD</h3>
                <p class="saas-subtitle mb-0">Solicitacoes de titulares, incidentes e retencao de dados.</p>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="/?r=privacidade/index" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right"></i> Aviso de privacidade
            </a>
        </div>
    </section>

    <?php if ($flash): ?>
        <div class="alert alert-<?= h($flash['type']) ?> mb-0"><?= h($flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($dbError !== ''): ?>
        <div class="alert alert-warning mb-0"><?= h($dbError) ?></div>
    <?php endif; ?>

    <section class="saas-kpi-grid">
        <div class="saas-stat-card">
            <div class="small text-muted">Solicitacoes abertas</div>
            <div class="saas-stat-value"><?= (int)($summary['requests_open'] ?? 0) ?></div>
        </div>
        <div class="saas-stat-card">
            <div class="small text-muted">Prazo proximo (48h)</div>
            <div class="saas-stat-value status-warning"><?= (int)($summary['requests_due_soon'] ?? 0) ?></div>
        </div>
        <div class="saas-stat-card">
            <div class="small text-muted">Incidentes em aberto</div>
            <div class="saas-stat-value status-danger"><?= (int)($summary['incidents_open'] ?? 0) ?></div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <section class="saas-table-card h-100">
                <div class="saas-table-head">
                    <h5>Configuracao de governanca</h5>
                    <span class="badge badge-soft">Controlador e DPO</span>
                </div>
                <form method="post" action="/?r=lgpd/saveConfig" class="row g-2">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Controlador</label>
                        <input type="text" name="controlador_nome" class="form-control input-xl" value="<?= h((string)($config['controlador_nome'] ?? '')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">E-mail controlador</label>
                        <input type="email" name="controlador_email" class="form-control input-xl" value="<?= h((string)($config['controlador_email'] ?? '')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Encarregado</label>
                        <input type="text" name="encarregado_nome" class="form-control input-xl" value="<?= h((string)($config['encarregado_nome'] ?? '')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">E-mail encarregado</label>
                        <input type="email" name="encarregado_email" class="form-control input-xl" value="<?= h((string)($config['encarregado_email'] ?? '')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="encarregado_telefone" class="form-control input-xl" value="<?= h((string)($config['encarregado_telefone'] ?? '')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Canal titular (URL)</label>
                        <input type="text" name="canal_titular_url" class="form-control input-xl" value="<?= h((string)($config['canal_titular_url'] ?? '/?r=privacidade/index')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Canal titular (e-mail)</label>
                        <input type="email" name="canal_titular_email" class="form-control input-xl" value="<?= h((string)($config['canal_titular_email'] ?? '')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Prazo titular (dias)</label>
                        <input type="number" min="1" max="180" name="prazo_titular_dias" class="form-control input-xl" value="<?= (int)($config['prazo_titular_dias'] ?? 15) ?>" <?= $canEdit ? '' : 'readonly' ?>>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Prazo incidente (dias uteis)</label>
                        <input type="number" min="1" max="30" name="prazo_incidente_dias_uteis" class="form-control input-xl" value="<?= (int)($config['prazo_incidente_dias_uteis'] ?? 3) ?>" <?= $canEdit ? '' : 'readonly' ?>>
                    </div>
                    <?php if ($canEdit): ?>
                        <div class="col-12"><button class="btn btn-primary btn-xl">Salvar configuracao</button></div>
                    <?php endif; ?>
                </form>
            </section>
        </div>

        <div class="col-12 col-xl-5">
            <section class="saas-table-card h-100">
                <div class="saas-table-head">
                    <h5>Retencao de dados</h5>
                    <span class="badge badge-soft">Minimizacao</span>
                </div>
                <?php if ($canEdit): ?>
                    <form method="post" action="/?r=lgpd/saveRetention" class="row g-2 mb-3">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <div class="col-12"><input type="text" name="tabela_nome" class="form-control input-xl" placeholder="Tabela (ex: auditoria)" required></div>
                        <div class="col-12"><input type="text" name="descricao" class="form-control input-xl" placeholder="Descricao"></div>
                        <div class="col-6"><input type="number" min="1" max="3650" name="retencao_dias" class="form-control input-xl" value="180" required></div>
                        <div class="col-6">
                            <select name="modo" class="form-select input-xl">
                                <option value="eliminar">Eliminar</option>
                                <option value="anonimizar">Anonimizar</option>
                            </select>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="ativo" value="1" checked> <span class="form-check-label">Ativa</span></label>
                            <button class="btn btn-outline-primary btn-xl">Salvar politica</button>
                            <button class="btn btn-outline-danger btn-xl" formaction="/?r=lgpd/runRetentionNow" data-confirm="Executar retencao agora?" data-confirm-type="danger">Executar agora</button>
                        </div>
                    </form>
                <?php endif; ?>
                <div class="saas-table-scroll">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Tabela</th><th>Dias</th><th>Modo</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($retention as $p): ?>
                            <tr>
                                <td><?= h((string)$p['tabela_nome']) ?></td>
                                <td><?= (int)$p['retencao_dias'] ?></td>
                                <td><?= h((string)$p['modo']) ?></td>
                                <td><?= (int)$p['ativo'] === 1 ? 'Ativa' : 'Inativa' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($retention)): ?><tr><td colspan="4" class="text-muted">Sem politicas.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <section class="saas-table-card">
        <div class="saas-table-head"><h5>Solicitacoes de titulares</h5></div>
        <?php if ($canEdit): ?>
            <form method="post" action="/?r=lgpd/addRequest" class="row g-2 mb-3">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <div class="col-12 col-md-2">
                    <select name="tipo" class="form-select input-xl">
                        <option value="acesso">Acesso</option><option value="correcao">Correcao</option><option value="anonimizacao">Anonimizacao</option><option value="eliminacao">Eliminacao</option><option value="portabilidade">Portabilidade</option><option value="oposicao">Oposicao</option><option value="revogacao">Revogacao</option><option value="informacao">Informacao</option>
                    </select>
                </div>
                <div class="col-12 col-md-3"><input type="text" name="titular_nome" class="form-control input-xl" placeholder="Nome do titular" required></div>
                <div class="col-6 col-md-2"><input type="text" name="titular_documento" class="form-control input-xl" placeholder="Documento"></div>
                <div class="col-6 col-md-3"><input type="email" name="titular_email" class="form-control input-xl" placeholder="E-mail"></div>
                <div class="col-6 col-md-1"><input type="datetime-local" name="recebido_em" class="form-control input-xl"></div>
                <div class="col-6 col-md-1"><input type="datetime-local" name="prazo_resposta_em" class="form-control input-xl"></div>
                <div class="col-12"><textarea name="detalhes" rows="2" class="form-control input-xl" placeholder="Detalhes"></textarea></div>
                <div class="col-12"><button class="btn btn-primary btn-xl">Registrar solicitacao</button></div>
            </form>
        <?php endif; ?>
        <div class="saas-table-scroll">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Protocolo</th><th>Tipo</th><th>Titular</th><th>Status</th><th>Prazo</th><?php if ($canEdit): ?><th>Ajustar</th><?php endif; ?></tr></thead>
                <tbody>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?= h((string)$r['protocolo']) ?></td>
                        <td><?= h((string)$r['tipo']) ?></td>
                        <td><?= h((string)$r['titular_nome']) ?></td>
                        <td><span class="badge <?= in_array((string)$r['status'], ['concluida'], true) ? 'badge-success' : ((string)$r['status'] === 'indeferida' ? 'badge-danger' : 'badge-soft') ?>"><?= h((string)$r['status']) ?></span></td>
                        <td><?= h((string)($r['prazo_resposta_em'] ?? '-')) ?></td>
                        <?php if ($canEdit): ?>
                            <td>
                                <form method="post" action="/?r=lgpd/updateRequest" class="d-flex gap-2">
                                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="tipo" value="<?= h((string)$r['tipo']) ?>">
                                    <input type="hidden" name="titular_nome" value="<?= h((string)$r['titular_nome']) ?>">
                                    <input type="hidden" name="titular_documento" value="<?= h((string)$r['titular_documento']) ?>">
                                    <input type="hidden" name="titular_email" value="<?= h((string)$r['titular_email']) ?>">
                                    <input type="hidden" name="detalhes" value="<?= h((string)$r['detalhes']) ?>">
                                    <input type="hidden" name="prazo_resposta_em" value="<?= h((string)$r['prazo_resposta_em']) ?>">
                                    <input type="hidden" name="concluido_em" value="">
                                    <input type="hidden" name="resposta_resumo" value="<?= h((string)$r['resposta_resumo']) ?>">
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="aberta" <?= (($r['status'] ?? '') === 'aberta') ? 'selected' : '' ?>>Aberta</option>
                                        <option value="em_tratamento" <?= (($r['status'] ?? '') === 'em_tratamento') ? 'selected' : '' ?>>Em tratamento</option>
                                        <option value="concluida" <?= (($r['status'] ?? '') === 'concluida') ? 'selected' : '' ?>>Concluida</option>
                                        <option value="indeferida" <?= (($r['status'] ?? '') === 'indeferida') ? 'selected' : '' ?>>Indeferida</option>
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary">OK</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($requests)): ?><tr><td colspan="<?= $canEdit ? '6' : '5' ?>" class="text-muted">Sem solicitacoes.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="saas-table-card">
        <div class="saas-table-head"><h5>Incidentes de seguranca</h5></div>
        <?php if ($canEdit): ?>
            <form method="post" action="/?r=lgpd/addIncident" class="row g-2 mb-3">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <div class="col-12 col-md-3"><input type="text" name="titulo" class="form-control input-xl" placeholder="Titulo" required></div>
                <div class="col-6 col-md-2"><input type="text" name="categoria" class="form-control input-xl" placeholder="Categoria"></div>
                <div class="col-6 col-md-2"><select name="risco_nivel" class="form-select input-xl"><option value="baixo">Baixo</option><option value="medio" selected>Medio</option><option value="alto">Alto</option></select></div>
                <div class="col-6 col-md-2"><input type="datetime-local" name="data_incidente" class="form-control input-xl"></div>
                <div class="col-6 col-md-2"><input type="datetime-local" name="detectado_em" class="form-control input-xl"></div>
                <div class="col-6 col-md-1"><select name="status" class="form-select input-xl"><option value="aberto">Aberto</option><option value="investigacao">Investigacao</option><option value="comunicado">Comunicado</option><option value="encerrado">Encerrado</option></select></div>
                <div class="col-6 col-md-1"><input type="number" min="0" name="titulares_afetados" class="form-control input-xl" value="0"></div>
                <div class="col-12 col-md-4"><textarea name="dados_afetados" class="form-control input-xl" rows="2" placeholder="Dados afetados"></textarea></div>
                <div class="col-12 col-md-4"><textarea name="medidas_adotadas" class="form-control input-xl" rows="2" placeholder="Medidas adotadas"></textarea></div>
                <div class="col-6 col-md-2 d-flex align-items-center"><label class="form-check"><input class="form-check-input" type="checkbox" name="comunicado_anpd" value="1"> <span class="form-check-label">ANPD</span></label></div>
                <div class="col-6 col-md-2 d-flex align-items-center"><label class="form-check"><input class="form-check-input" type="checkbox" name="comunicado_titulares" value="1"> <span class="form-check-label">Titulares</span></label></div>
                <div class="col-6 col-md-2"><input type="datetime-local" name="comunicado_em" class="form-control input-xl"></div>
                <div class="col-6 col-md-2"><input type="datetime-local" name="encerrado_em" class="form-control input-xl"></div>
                <div class="col-12"><button class="btn btn-primary btn-xl">Registrar incidente</button></div>
            </form>
        <?php endif; ?>
        <div class="saas-table-scroll">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Codigo</th><th>Titulo</th><th>Risco</th><th>Status</th><th>Detectado</th><th>Afetados</th><?php if ($canEdit): ?><th>Ajustar</th><?php endif; ?></tr></thead>
                <tbody>
                <?php foreach ($incidents as $i): ?>
                    <tr>
                        <td><?= h((string)$i['codigo']) ?></td>
                        <td><?= h((string)$i['titulo']) ?></td>
                        <td><?= h((string)$i['risco_nivel']) ?></td>
                        <td><span class="badge <?= (string)$i['status'] === 'encerrado' ? 'badge-success' : ((string)$i['status'] === 'aberto' ? 'badge-danger' : 'badge-soft') ?>"><?= h((string)$i['status']) ?></span></td>
                        <td><?= h((string)$i['detectado_em']) ?></td>
                        <td><?= (int)$i['titulares_afetados'] ?></td>
                        <?php if ($canEdit): ?>
                            <td>
                                <form method="post" action="/?r=lgpd/updateIncident" class="d-flex gap-2">
                                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
                                    <input type="hidden" name="titulo" value="<?= h((string)$i['titulo']) ?>">
                                    <input type="hidden" name="categoria" value="<?= h((string)$i['categoria']) ?>">
                                    <input type="hidden" name="risco_nivel" value="<?= h((string)$i['risco_nivel']) ?>">
                                    <input type="hidden" name="data_incidente" value="<?= h((string)$i['data_incidente']) ?>">
                                    <input type="hidden" name="detectado_em" value="<?= h((string)$i['detectado_em']) ?>">
                                    <input type="hidden" name="titulares_afetados" value="<?= (int)$i['titulares_afetados'] ?>">
                                    <input type="hidden" name="dados_afetados" value="<?= h((string)$i['dados_afetados']) ?>">
                                    <input type="hidden" name="medidas_adotadas" value="<?= h((string)$i['medidas_adotadas']) ?>">
                                    <input type="hidden" name="comunicado_anpd" value="<?= (int)$i['comunicado_anpd'] ?>">
                                    <input type="hidden" name="comunicado_titulares" value="<?= (int)$i['comunicado_titulares'] ?>">
                                    <input type="hidden" name="comunicado_em" value="<?= h((string)$i['comunicado_em']) ?>">
                                    <input type="hidden" name="encerrado_em" value="">
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="aberto" <?= (($i['status'] ?? '') === 'aberto') ? 'selected' : '' ?>>Aberto</option>
                                        <option value="investigacao" <?= (($i['status'] ?? '') === 'investigacao') ? 'selected' : '' ?>>Investigacao</option>
                                        <option value="comunicado" <?= (($i['status'] ?? '') === 'comunicado') ? 'selected' : '' ?>>Comunicado</option>
                                        <option value="encerrado" <?= (($i['status'] ?? '') === 'encerrado') ? 'selected' : '' ?>>Encerrado</option>
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary">OK</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($incidents)): ?><tr><td colspan="<?= $canEdit ? '7' : '6' ?>" class="text-muted">Sem incidentes.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="saas-table-card">
        <div class="saas-table-head"><h5>Eventos LGPD</h5></div>
        <div class="saas-table-scroll">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Data</th><th>Tipo</th><th>Referencia</th><th>Acao</th><th>Usuario</th></tr></thead>
                <tbody>
                <?php foreach ($events as $e): ?>
                    <tr>
                        <td><?= h((string)$e['criado_em']) ?></td>
                        <td><?= h((string)$e['tipo']) ?></td>
                        <td><?= h((string)$e['referencia']) ?></td>
                        <td><?= h((string)$e['acao']) ?></td>
                        <td><?= h((string)($e['usuario_nome'] ?? 'sistema')) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($events)): ?><tr><td colspan="5" class="text-muted">Sem eventos.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<style>
    .lgpd-page {
        min-width: 0;
        max-width: 100%;
        overflow-x: hidden;
    }
    .lgpd-page > * {
        min-width: 0;
        max-width: 100%;
    }
    .lgpd-page .saas-kpi-grid {
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }
    .lgpd-page .saas-stat-card {
        min-height: 120px;
    }
    .lgpd-page .form-label {
        font-weight: 620;
    }
    .lgpd-page .row {
        margin-left: 0;
        margin-right: 0;
        --bs-gutter-x: 0.9rem;
    }
    .lgpd-page .row > [class*="col-"] {
        min-width: 0;
        max-width: 100%;
        padding-left: calc(var(--bs-gutter-x) * 0.5);
        padding-right: calc(var(--bs-gutter-x) * 0.5);
    }
    .lgpd-page .form-control,
    .lgpd-page .form-select {
        min-width: 0;
        max-width: 100%;
    }
    .lgpd-page input[type="datetime-local"] {
        min-width: 0;
    }
    .lgpd-page .saas-table-scroll,
    .lgpd-page .table-responsive {
        max-width: 100%;
        overflow-x: auto;
    }
    .lgpd-page .d-flex.gap-2 {
        flex-wrap: wrap;
    }
    .lgpd-page .d-flex.gap-2 .btn,
    .lgpd-page .d-flex.gap-2 .form-select {
        min-width: 0;
    }
    @media (max-width: 768px) {
        .lgpd-page .saas-kpi-grid {
            grid-template-columns: 1fr;
        }
        .lgpd-page .saas-headline .btn {
            width: 100%;
        }
    }
</style>
