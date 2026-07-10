<?php
$config = $this->data['config'] ?? [];
$flash = $this->data['flash'] ?? null;
$canalEmail = trim((string)($config['canal_titular_email'] ?? ''));
$controladorEmail = trim((string)($config['controlador_email'] ?? ''));
$canalTitular = $canalEmail !== '' ? $canalEmail : ($controladorEmail !== '' ? $controladorEmail : 'Canal a ser informado pela liderança ou recepção.');
?>

<div class="fb-privacy-page">
    <header class="fb-page-head fb-privacy-head">
        <div class="fb-page-head__main">
            <span class="fb-eyebrow"><i class="bi bi-shield-check"></i> Transparência e proteção de dados</span>
            <h1>Aviso de privacidade</h1>
            <p>Como o FBControl trata dados pessoais na rotina operacional de Alimentos &amp; Bebidas.</p>
        </div>
        <a href="/?r=auth/login" class="btn btn-outline-primary"><i class="bi bi-arrow-left"></i> Voltar ao acesso</a>
    </header>

    <section class="fb-privacy-summary" aria-label="Resumo da política">
        <article><i class="bi bi-bullseye"></i><span><strong>Finalidade definida</strong>Uso estritamente operacional, gerencial e de auditoria.</span></article>
        <article><i class="bi bi-person-check"></i><span><strong>Acesso controlado</strong>Informações disponíveis conforme perfil autorizado.</span></article>
        <article><i class="bi bi-database-lock"></i><span><strong>Retenção responsável</strong>Dados mantidos pelo tempo necessário e legal.</span></article>
    </section>

    <div class="fb-privacy-layout">
        <aside class="fb-privacy-contact">
            <span class="fb-eyebrow">Responsáveis e contato</span>
            <h2>Canal do titular</h2>
            <dl>
                <div><dt>Controlador</dt><dd><?= h((string)($config['controlador_nome'] ?? 'Controlador da operação')) ?></dd></div>
                <div><dt>E-mail</dt><dd><?= h((string)($config['controlador_email'] ?? '-')) ?></dd></div>
                <div><dt>Encarregado (DPO)</dt><dd><?= h((string)($config['encarregado_nome'] ?? '-')) ?></dd></div>
                <div><dt>Contato do encarregado</dt><dd><?= h((string)($config['encarregado_email'] ?? '-')) ?></dd></div>
                <div><dt>Solicitações</dt><dd><?= h($canalTitular) ?></dd></div>
            </dl>
            <div class="fb-privacy-deadline">
                <i class="bi bi-clock-history"></i>
                <span>Prazo operacional de resposta<strong><?= (int)($config['prazo_titular_dias'] ?? 15) ?> dias</strong></span>
            </div>
        </aside>

        <main class="fb-privacy-content">
            <section class="fb-privacy-section">
                <span class="fb-privacy-section__number">01</span>
                <div><h2>Quais dados são tratados</h2><ul>
                    <li>Dados necessários à operação de A&amp;B, como UH, PAX, data, horário, restaurante e reservas temáticas.</li>
                    <li>Dados dos usuários internos, incluindo nome, e-mail, perfil, vínculos e ações executadas.</li>
                    <li>Vouchers, registros administrativos e logs técnicos necessários à conferência e auditoria.</li>
                </ul></div>
            </section>

            <section class="fb-privacy-section">
                <span class="fb-privacy-section__number">02</span>
                <div><h2>Por que os dados são usados</h2><ul>
                    <li>Executar e conferir procedimentos operacionais de hospedagem e alimentação.</li>
                    <li>Cumprir obrigações legais e apoiar o exercício regular de direitos.</li>
                    <li>Proteger a operação por meio de segurança, auditoria e prevenção a fraudes.</li>
                    <li>Produzir indicadores gerenciais e prestar contas às lideranças autorizadas.</li>
                </ul></div>
            </section>

            <section class="fb-privacy-section fb-privacy-section--attention">
                <span class="fb-privacy-section__number">03</span>
                <div><h2>Minimização na rotina</h2><p>Registre somente o necessário para executar, conferir e auditar a operação.</p><ul>
                    <li>Não inclua documentos pessoais, telefones, dados médicos ou financeiros em observações livres.</li>
                    <li>Use anexos de vouchers somente quando forem necessários à comprovação operacional.</li>
                    <li>Compartilhe relatórios e exportações apenas com pessoas e áreas autorizadas.</li>
                </ul></div>
            </section>

            <section class="fb-privacy-section">
                <span class="fb-privacy-section__number">04</span>
                <div><h2>Acesso e compartilhamento</h2><p>O FBControl é um sistema interno. O acesso segue o perfil de cada usuário: hostess, supervisão, gerência ou administração. Os dados não são destinados a marketing e só podem ser compartilhados com áreas autorizadas ou fornecedores técnicos indispensáveis à operação.</p></div>
            </section>

            <section class="fb-privacy-section">
                <span class="fb-privacy-section__number">05</span>
                <div><h2>Armazenamento e retenção</h2><p>Os dados podem permanecer em bancos, backups e logs pelo período necessário às finalidades operacionais, obrigações legais e auditoria. Registros vencidos podem ser eliminados por rotina controlada e registrada.</p></div>
            </section>

            <section class="fb-privacy-section">
                <span class="fb-privacy-section__number">06</span>
                <div><h2>Direitos do titular</h2><p>O titular pode solicitar acesso, correção, anonimização, eliminação, portabilidade, oposição e informações sobre o uso compartilhado de seus dados pelo canal indicado nesta página.</p></div>
            </section>

            <section class="fb-privacy-section">
                <span class="fb-privacy-section__number">07</span>
                <div><h2>Incidentes de segurança</h2><p>Em caso de risco ou dano relevante, será executado um plano de resposta, mitigação e comunicação. A janela de referência para avaliação é de <strong><?= (int)($config['prazo_incidente_dias_uteis'] ?? 3) ?> dias úteis</strong>.</p></div>
            </section>

            <section class="fb-privacy-note">
                <i class="bi bi-info-circle"></i>
                <p>Este aviso complementa os termos apresentados no processo de hospedagem. Dúvidas devem ser direcionadas ao controlador ou encarregado informado nesta página.</p>
            </section>
        </main>
    </div>
</div>
