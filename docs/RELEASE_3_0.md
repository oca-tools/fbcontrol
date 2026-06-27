# FBControl 3.0 - Release Candidate

Este documento consolida o fechamento tecnico da versao 3.0.

## Status

Versao candidata: `3.0`

Objetivo da release:

- entregar a versao polida do FBControl;
- reduzir risco em relatorios, exports e consultas grandes;
- reforcar seguranca contra falhas comuns de aplicacao web;
- alinhar LGPD, operacao e deploy;
- deixar um pacote enxuto, sem configs locais, uploads reais ou artefatos de desenvolvimento.

## Banco e migrations

Em ambientes novos, use `sql/schema_v3_0.sql`.

`sql/schema_current.sql` permanece como snapshot tecnico da estrutura atual, mas o arquivo oficial para instalacao nova da release 3.0 e `sql/schema_v3_0.sql`.

Em bancos existentes, validar/aplicar as migrations versionadas:

1. `sql/migration_v2_1_security_hardening.sql`
2. `sql/migration_v2_1_users_email_non_unique.sql`
3. `sql/migration_v2_2_reservas_tematicas_lote_chd.sql`
4. `sql/migration_v2_3_titular_nome.sql`
5. `sql/migration_v2_3_grupo_nome.sql`
6. `sql/migration_v2_4_auto_no_show_min.sql`
7. `sql/migration_v2_5_tematic_capacity_by_date.sql`
8. `sql/migration_v2_6_reservas_tematicas_bloqueios_datas.sql`
9. `sql/migration_v2_7_reservas_tematicas_bloqueios_semanais.sql`
10. `sql/migration_v2_8_turnos_modo_demo.sql`
11. `sql/migration_v2_9_performance_indexes.sql`
12. `sql/migration_v3_0_query_performance.sql`
13. `sql/migration_v3_1_audit_security.sql`
14. `sql/migration_v3_2_chd_age_labels.sql`
15. `sql/migration_v3_3_thematic_availability_overrides.sql`

## Validacao local

Antes de empacotar:

```bash
php tools/run_checks.php
php tools/check_release_candidate.php
```

Para auditar o pacote sem gerar arquivo:

```bash
php tools/build_release.php 3.0 ignored.tar.gz --dry-run
```

Para gerar o pacote:

```bash
php tools/build_release.php 3.0
```

## Validacao no VPS

Depois do deploy:

```bash
cd /var/www/apps/fbcontrol/current
php tools/healthcheck_fbcontrol.php --strict
php tools/check_db_context.php
php deploy/security/sast_scan.php
php tools/sanitize_lgpd_event_details.php
```

Confirmar tambem:

- `APP_ENV=production` no Apache/FPM e nos jobs;
- `upload_max_filesize` em pelo menos `10M`;
- `post_max_size` maior ou igual ao limite de upload;
- extensoes PHP `fileinfo`, `zip` e `imagick` disponiveis;
- `public/uploads` persistente e com permissao de escrita para o servidor web.

## Crons obrigatorios

```bash
* * * * * /usr/bin/php /var/www/apps/fbcontrol/current/app/cron/auto_close_shifts.php >> /var/log/fbcontrol_shifts.log 2>&1
* * * * * /usr/bin/php /var/www/apps/fbcontrol/current/app/cron/reservas_tematicas_auto_no_show.php >> /var/log/fbcontrol_tematicos_no_show.log 2>&1
*/5 * * * * /usr/bin/php /var/www/apps/fbcontrol/current/app/cron/send_daily_report_email.php >> /var/log/fbcontrol_daily_email.log 2>&1
0 3 * * * /usr/bin/php /var/www/apps/fbcontrol/current/app/cron/lgpd_retention.php >> /var/log/fbcontrol_lgpd_retention.log 2>&1
30 3 * * * /var/www/apps/fbcontrol/current/deploy/vps/backup_restore_check.sh >> /var/log/fbcontrol_backup.log 2>&1
```

## Deploy seguro

1. Criar backup de banco e arquivos do live.
2. Subir pacote da release.
3. Preservar `config/config.php` live, `public/uploads` e logs.
4. Aplicar migrations pendentes.
5. Rodar `php tools/healthcheck_fbcontrol.php --strict`.
6. Rodar `php tools/sanitize_lgpd_event_details.php` e aplicar com `--apply` se houver eventos antigos a minimizar.
7. Rodar smoke manual no navegador:
   - login admin;
   - dashboard;
   - registro;
   - reservas tematicas;
   - relatorios;
   - usuarios;
   - privacidade.
8. Confirmar crons em ate 5 minutos.

## Rollback

Manter o backup do release anterior ate a versao 3.0 passar pelo primeiro ciclo operacional completo.

Rollback recomendado:

1. Apontar `current` para a release anterior.
2. Restaurar `config/config.php` se necessario.
3. Preservar `public/uploads`.
4. Reiniciar Apache/PHP-FPM.
5. Rodar `php tools/healthcheck_fbcontrol.php --strict`.

Banco:

- evitar rollback destrutivo de banco se a falha for apenas visual ou de codigo;
- restaurar dump apenas se migration causar incompatibilidade real e apos decisao explicita.

## Pendencias conhecidas

- Remover do versionamento o arquivo runtime `public/uploads/profiles/user_2.png` em um commit proprio, mantendo o arquivo local se ainda for util.
- Sanear e-mails ativos duplicados de hostess para reduzir risco operacional no login.
- Conferir no VPS se o PHP CLI e o PHP usado pelo Apache/FPM possuem as mesmas extensoes.
