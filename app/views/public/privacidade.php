<?php
$config = $this->data['config'] ?? [];
$flash = $this->data['flash'] ?? null;
?>

<style>
    .privacy-page {
        max-width: 980px;
        margin: 0 auto;
        gap: 1rem;
    }
    .privacy-page .saas-table-card {
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    .privacy-page ul {
        padding-left: 1.15rem;
    }
    @media (max-width: 576px) {
        .privacy-page {
            gap: 0.85rem;
        }
        .privacy-page .saas-hero-card,
        .privacy-page .saas-table-card {
            padding: 0.95rem;
            border-radius: 16px;
        }
    }
</style>

<div class="saas-page privacy-page">
    <section class="saas-hero-card">
        <div class="saas-label">Transparência</div>
        <h3 class="saas-title mb-2">Aviso de Privacidade - OCA FBControl</h3>
        <p class="saas-subtitle mb-0">
            Este aviso explica como dados pessoais são tratados na operação de A&B do resort.
        </p>
    </section>

    <?php if ($flash): ?>
        <div class="alert alert-<?= h($flash['type']) ?> mb-0"><?= h($flash['message']) ?></div>
    <?php endif; ?>

    <section class="saas-table-card">
        <h5 class="mb-3">Quem controla os dados</h5>
        <p class="mb-1"><strong>Controlador:</strong> <?= h((string)($config['controlador_nome'] ?? 'Grand Oca Maragogi Resort')) ?></p>
        <p class="mb-1"><strong>E-mail do controlador:</strong> <?= h((string)($config['controlador_email'] ?? '-')) ?></p>
        <p class="mb-1"><strong>Encarregado (DPO):</strong> <?= h((string)($config['encarregado_nome'] ?? '-')) ?></p>
        <p class="mb-1"><strong>E-mail do encarregado:</strong> <?= h((string)($config['encarregado_email'] ?? '-')) ?></p>
        <p class="mb-0"><strong>Canal do titular:</strong> <?= h((string)($config['canal_titular_email'] ?? '-')) ?></p>
    </section>

    <section class="saas-table-card">
        <h5 class="mb-3">Quais dados são tratados</h5>
        <ul class="mb-0">
            <li>Dados operacionais de acesso a restaurantes (UH, PAX, horário, usuário operador).</li>
            <li>Dados de reservas temáticas para organização do jantar e controle de no-show.</li>
            <li>Dados de usuários internos para autenticação, auditoria e segurança.</li>
            <li>Dados de vouchers e registros de operação para controle administrativo.</li>
        </ul>
    </section>

    <section class="saas-table-card">
        <h5 class="mb-3">Finalidades e bases legais</h5>
        <ul class="mb-0">
            <li>Execução de procedimentos operacionais de hospedagem e alimentação.</li>
            <li>Cumprimento de obrigações legais e regulatórias aplicáveis.</li>
            <li>Legítimo interesse para segurança, auditoria e prevenção a fraudes.</li>
            <li>Exercício regular de direitos em processos administrativos e judiciais.</li>
        </ul>
    </section>

    <section class="saas-table-card">
        <h5 class="mb-3">Direitos do titular</h5>
        <p class="mb-2">
            O titular pode solicitar acesso, correção, anonimização, eliminação, portabilidade, oposição e informações sobre uso compartilhado.
        </p>
        <p class="mb-0">
            Prazo operacional alvo para resposta: <strong><?= (int)($config['prazo_titular_dias'] ?? 15) ?> dias</strong>.
        </p>
    </section>

    <section class="saas-table-card">
        <h5 class="mb-3">Incidentes de segurança</h5>
        <p class="mb-2">
            Em caso de incidente com risco ou dano relevante, será adotado plano de resposta, mitigação e comunicação.
        </p>
        <p class="mb-0">
            Janela de referência para avaliação e comunicação: <strong><?= (int)($config['prazo_incidente_dias_uteis'] ?? 3) ?> dias úteis</strong>.
        </p>
    </section>
</div>
