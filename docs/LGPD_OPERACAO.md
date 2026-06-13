# LGPD - Operacao Continua (FBControl)

Este guia define rotina operacional minima para conformidade diaria.

## 1) Responsaveis

- Controlador: Grand Oca Maragogi Resort.
- Operador técnico: responsável contratado pela hospedagem, manutenção e suporte do software, conforme contrato vigente.
- Encarregado (DPO): preencher no modulo `/?r=lgpd/index`.

## 2) Base legal e finalidade

- Finalidade principal: controle operacional de acesso A&B, auditoria e relatorios de operacao.
- Dados tratados: identificacao de UH, registros de turno, auditoria de uso, reservas tematicas e incidentes.
- Minimizar dados pessoais: nao coletar campos sem necessidade operacional.
- Campos livres devem evitar CPF, documentos, telefones, dados de saúde, dados financeiros e qualquer informação que não seja necessária à operação.
- Vouchers e anexos devem circular apenas por relatórios e usuários autorizados.

## 3) Fluxo de solicitacao de titular

1. Registrar solicitacao em `/?r=lgpd/index` (tipo, titular, canal, prazo).
2. Classificar risco e escopo do pedido.
3. Executar atendimento (acesso, correcao, anonimização, eliminacao, etc).
4. Registrar evidencia no proprio ticket LGPD.
5. Encerrar ticket com data e responsavel.

SLA sugerido:
- prazo titular: 15 dias corridos (ajustavel no modulo).

## 4) Fluxo de incidente

1. Abrir incidente em `/?r=lgpd/index`.
2. Classificar severidade (baixa, media, alta, critica).
3. Definir acao imediata de contencao.
4. Documentar causa raiz e plano de correcao.
5. Quando aplicavel, avaliar notificacao a ANPD e titulares.
6. Encerrar com evidencias tecnicas e decisao formal.

SLA sugerido:
- triagem inicial: ate 1 dia util.
- resposta formal: ate 3 dias uteis (parametro default do sistema).

## 5) Retencao e descarte

- Politicas em `lgpd_retencao_politicas`.
- A rotina atual elimina registros vencidos somente em tabelas operacionais permitidas pelo sistema: auditoria, historico de e-mails diarios, eventos LGPD e sessoes ativas.
- Anonimizacao de bases operacionais deve ser tratada como solicitacao registrada e analisada caso a caso; nao e executada automaticamente nesta versao.
- Eventos internos de LGPD devem guardar metadados e status, nao documentos, e-mails ou textos livres completos.
- Job diario: `app/cron/lgpd_retention.php`.
- Toda limpeza deve gerar trilha de evento em `lgpd_eventos`.

Saneamento de eventos historicos:

```bash
php tools/sanitize_lgpd_event_details.php
php tools/sanitize_lgpd_event_details.php --apply
```

Cron recomendado:

```bash
0 3 * * * /usr/bin/php /var/www/apps/fbcontrol/current/app/cron/lgpd_retention.php >> /var/log/fbcontrol_lgpd_retention.log 2>&1
```

## 6) Evidencias minimas para auditoria

- Tickets de solicitacao e incidente com status e responsavel.
- Log de execucao do cron de retencao.
- Relatorio mensal de backup + restore drill.
- Revisao trimestral de perfis de acesso (admin/supervisor/gerente/hostess).

## 7) Checklist mensal

- [ ] Revisar usuarios ativos e perfis.
- [ ] Revisar politicas de retencao.
- [ ] Testar restauracao de backup.
- [ ] Revisar logs de autenticacao suspeita.
- [ ] Atualizar contatos de controlador e DPO no modulo LGPD.
- [ ] Revisar se eventos LGPD historicos ja foram saneados.

## 8) Observacao juridica

Este documento e tecnico-operacional e nao substitui consultoria juridica.
Sempre validar clausulas contratuais, base legal e comunicacoes formais com apoio juridico.
