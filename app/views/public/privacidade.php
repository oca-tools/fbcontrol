<?php
$config = $this->data['config'] ?? [];
$flash = $this->data['flash'] ?? null;
$canalEmail = trim((string)($config['canal_titular_email'] ?? ''));
$controladorEmail = trim((string)($config['controlador_email'] ?? ''));
$canalTitular = $canalEmail !== '' ? $canalEmail : ($controladorEmail !== '' ? $controladorEmail : 'Canal a ser informado pela liderança ou recepção.');
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
        <h3 class="saas-title mb-2">Aviso de Privacidade - FBControl</h3>
        <p class="saas-subtitle mb-0">
            Este aviso explica como dados pessoais são tratados no uso operacional do FBControl por equipes internas e na rotina de A&B do resort.
        </p>
    </section>

    <?php if ($flash): ?>
        <div class="alert alert-<?= h($flash['type']) ?> mb-0"><?= h($flash['message']) ?></div>
    <?php endif; ?>

    <section class="saas-table-card">
        <h5 class="mb-3">Quem controla os dados</h5>
        <p class="mb-1"><strong>Controlador:</strong> <?= h((string)($config['controlador_nome'] ?? 'Controlador da operação')) ?></p>
        <p class="mb-1"><strong>E-mail do controlador:</strong> <?= h((string)($config['controlador_email'] ?? '-')) ?></p>
        <p class="mb-1"><strong>Encarregado (DPO):</strong> <?= h((string)($config['encarregado_nome'] ?? '-')) ?></p>
        <p class="mb-1"><strong>E-mail do encarregado:</strong> <?= h((string)($config['encarregado_email'] ?? '-')) ?></p>
        <p class="mb-0"><strong>Canal do titular:</strong> <?= h($canalTitular) ?></p>
    </section>

    <section class="saas-table-card">
        <h5 class="mb-3">Quais dados são tratados</h5>
        <ul class="mb-0">
            <li>Dados de hóspedes necessários à operação de A&B, como UH, PAX, data, horário, restaurante, operação e reservas temáticas.</li>
            <li>Dados de usuários internos, como nome, e-mail, perfil, restaurante vinculado, turno, registros executados e trilhas de auditoria.</li>
            <li>Dados de vouchers, refeições de colaboradores e registros administrativos vinculados à operação.</li>
            <li>Logs técnicos e de segurança, incluindo data/hora, usuário operador, ação realizada e contexto necessário para auditoria.</li>
        </ul>
    </section>

    <section class="saas-table-card">
        <h5 class="mb-3">Minimização na rotina</h5>
        <p class="mb-2">
            Os operadores devem registrar apenas os dados necessários para executar, conferir e auditar a operação.
        </p>
        <ul class="mb-0">
            <li>Evite inserir documentos pessoais, telefones, informações médicas ou dados financeiros em observações livres.</li>
            <li>Anexos de vouchers devem ser usados somente quando necessários para comprovação operacional.</li>
            <li>Relatórios e exportações devem ser compartilhados apenas com lideranças e áreas autorizadas.</li>
        </ul>
    </section>

    <section class="saas-table-card">
        <h5 class="mb-3">Finalidades e bases legais</h5>
        <ul class="mb-0">
            <li>Execução de procedimentos operacionais de hospedagem e alimentação.</li>
            <li>Cumprimento de obrigações legais e regulatórias aplicáveis.</li>
            <li>Legítimo interesse para segurança, auditoria e prevenção a fraudes.</li>
            <li>Exercício regular de direitos em processos administrativos e judiciais.</li>
            <li>Gestão de equipe, qualidade operacional e prestação de contas às lideranças autorizadas.</li>
        </ul>
    </section>

    <section class="saas-table-card">
        <h5 class="mb-3">Uso interno e acesso às informações</h5>
        <p class="mb-2">
            O FBControl é um sistema interno. O acesso é restrito a usuários autorizados, conforme perfil operacional: hostess, supervisão, gerência e administração.
        </p>
        <p class="mb-0">
            As informações não são destinadas a marketing. Elas são usadas para operação, conferência, auditoria, relatórios gerenciais e resposta a solicitações formais.
        </p>
    </section>

    <section class="saas-table-card">
        <h5 class="mb-3">Compartilhamento, armazenamento e retenção</h5>
        <ul class="mb-0">
            <li>Os dados podem ser armazenados em banco de dados, backups, logs e serviços técnicos necessários à hospedagem e envio de relatórios.</li>
            <li>O compartilhamento deve ocorrer apenas com áreas internas autorizadas ou fornecedores de infraestrutura envolvidos na operação do sistema.</li>
            <li>Os prazos de retenção devem seguir a finalidade operacional, obrigações legais, auditoria e políticas configuradas no módulo LGPD.</li>
            <li>Quando aplicável, dados vencidos podem ser eliminados por rotina controlada e registrada em trilha de auditoria.</li>
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

    <section class="saas-table-card">
        <h5 class="mb-3">Complemento aos termos de check-in</h5>
        <p class="mb-0">
            Este aviso complementa os termos e políticas apresentados no processo de hospedagem. Quando houver divergência operacional, a liderança responsável deve validar o fluxo com o controlador ou encarregado definido acima.
        </p>
    </section>
</div>
