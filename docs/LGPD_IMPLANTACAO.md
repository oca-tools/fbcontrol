# LGPD - Guia de Implantacao no FBControl

## 1) O que este pacote entrega
- Modulo `/?r=lgpd/index` para governanca, solicitacoes de titulares e incidentes.
- Aviso de privacidade publico em `/?r=privacidade/index`.
- Politicas de retencao com execucao manual e via cron.
- Trilha de eventos LGPD em `lgpd_eventos`.

## 2) SQL para aplicar
Execute a migracao:

```bash
mysql -u SEU_USUARIO -p -D controle_ab < sql/migration_v2_1_lgpd.sql
```

## 3) Perfis de acesso
- `admin`: acesso total ao modulo LGPD.
- `supervisor`: pode gerenciar solicitacoes/incidentes/configuracoes.
- `gerente`: acesso somente de consulta.

## 4) Rotina automatica (retencao)
Adicionar no cron do servidor:

```bash
0 3 * * * /usr/bin/php /var/www/apps/fbcontrol/current/app/cron/lgpd_retention.php >> /var/log/fbcontrol_lgpd_retention.log 2>&1
```

## 5) Checklist operacional
- Definir encarregado (DPO) e canal de atendimento.
- Definir SLA de solicitacoes de titulares.
- Definir playbook de incidente (quem aciona, quem aprova, quem comunica).
- Revisar politicas de retencao trimestralmente.
- Registrar evidencias de atendimento no modulo LGPD.

## 6) Recomendacoes adicionais
- Revisar contratos com operadores (VPS, e-mail, backups).
- Ativar MFA para contas de administrador.
- Restringir acesso ao banco por IP interno sempre que possivel.
- Manter backups cifrados e teste de restauracao mensal.
- Seguir o runbook operacional em `docs/LGPD_OPERACAO.md`.
