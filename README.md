# FBControl v3.0

Plataforma operacional A&B para hotéis e resorts, com foco em registro rápido de acesso, turnos, auditoria, relatórios e reservas temáticas.

## Stack
- PHP 8+ (MVC simples)
- MySQL 8+
- Bootstrap 5

## Módulos principais
- Login e perfis (`hostess`, `supervisor`, `gerente`, `admin`)
- Registro operacional por turno (UH, PAX, restaurante, porta, operação)
- Regras de duplicidade, fora de horário e múltiplo acesso
- Dashboard geral + centro de controle
- Relatórios operacionais e temáticos
- Vouchers + refeições de colaborador
- Reservas temáticas (reserva, operação e administração)
- KPIs estratégicos
- Onboarding/tutorial de hostess
- Envio de e-mail diário

## Instalação rápida
1. Crie o banco MySQL/MariaDB com charset `utf8mb4`.
2. Execute `sql/schema_v2_1_final.sql`.
3. Execute `sql/migration_v2_1_security_hardening.sql`.
4. Execute `sql/migration_v2_1_users_email_non_unique.sql`.
5. Execute `sql/migration_v2_2_reservas_tematicas_lote_chd.sql`.
6. Execute `sql/migration_v2_3_titular_nome.sql`.
7. Execute `sql/migration_v2_3_grupo_nome.sql`.
8. Execute `sql/migration_v2_4_auto_no_show_min.sql` se quiser configurar tolerância de no-show automático.
9. Execute `sql/migration_v2_5_tematic_capacity_by_date.sql` para habilitar capacidade temática por data.
10. Execute `sql/migration_v2_6_reservas_tematicas_bloqueios_datas.sql`.
11. Execute `sql/migration_v2_7_reservas_tematicas_bloqueios_semanais.sql`.
12. Execute `sql/migration_v2_8_turnos_modo_demo.sql`.
13. Execute `sql/migration_v2_9_performance_indexes.sql`.
14. Ajuste variáveis de ambiente ou `config/config.local.php`.
15. Configure o servidor web apontando para `public`.
16. Acesse: `/?r=auth/login`.

Exemplo local com MySQL CLI:

```bash
mysql -u usuario -p nome_do_banco < sql/schema_v2_1_final.sql
mysql -u usuario -p nome_do_banco < sql/migration_v2_1_security_hardening.sql
mysql -u usuario -p nome_do_banco < sql/migration_v2_1_users_email_non_unique.sql
mysql -u usuario -p nome_do_banco < sql/migration_v2_2_reservas_tematicas_lote_chd.sql
mysql -u usuario -p nome_do_banco < sql/migration_v2_3_titular_nome.sql
mysql -u usuario -p nome_do_banco < sql/migration_v2_3_grupo_nome.sql
mysql -u usuario -p nome_do_banco < sql/migration_v2_4_auto_no_show_min.sql
mysql -u usuario -p nome_do_banco < sql/migration_v2_5_tematic_capacity_by_date.sql
mysql -u usuario -p nome_do_banco < sql/migration_v2_6_reservas_tematicas_bloqueios_datas.sql
mysql -u usuario -p nome_do_banco < sql/migration_v2_7_reservas_tematicas_bloqueios_semanais.sql
mysql -u usuario -p nome_do_banco < sql/migration_v2_8_turnos_modo_demo.sql
mysql -u usuario -p nome_do_banco < sql/migration_v2_9_performance_indexes.sql
```

Observação: `migration_v2_1_lgpd.sql` só é necessária ao atualizar bancos antigos. As tabelas LGPD já estão em `schema_v2_1_final.sql`.

Alternativa para ambiente novo:

- `sql/schema_current.sql` contém um snapshot consolidado da estrutura local atual, sem dados.
- Esse snapshot já inclui a coluna `auto_cancel_no_show_min`.
- Se partir de `schema_v2_1_final.sql`, aplique as migrations listadas acima para chegar ao mesmo estado.

## Rotas úteis
- `/?r=access/index` (registro/turno)
- `/?r=dashboard/index`
- `/?r=control/index`
- `/?r=relatorios/index`
- `/?r=relatoriosTematicos/index`
- `/?r=kpis/index` (novo)
- `/?r=emailRelatorios/index`

## Configuração
- `APP_ENV`: use `production` no VPS e `local` em desenvolvimento.
- `APP_TIMEZONE`: padrão `America/Sao_Paulo`.
- `APP_SESSION_TIMEOUT_MIN`: timeout de sessão em minutos.
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`: conexão com banco.

O servidor web deve expor apenas a pasta `public`. Não aponte o document root para a raiz da release.

## Verificação local

Smoke check não destrutivo:

```bash
php tools/smoke_fbcontrol.php
```

Ele valida bootstrap web, conexão com banco, tabelas essenciais, coluna `auto_cancel_no_show_min` e renderização básica do layout logado.

Checagem de contexto do banco:

```bash
php tools/check_db_context.php
```

Use antes de revisar login, relatórios ou deploy. Ela confirma se o runtime está conectado em um banco coerente, com tabelas/migrations críticas e administrador ativo. No ambiente local com dados reais importados do VPS, o banco esperado é `controle_ab_vps`; o banco `controle_ab` pode existir como base antiga/de teste e não deve ser usado para validar credenciais ou dados operacionais.

Healthcheck operacional:

```bash
php tools/healthcheck_fbcontrol.php
```

Use depois de deploy e em revisões de VPS. Ele confere extensões PHP, limites de upload, diretórios de anexos, tabelas críticas, administrador ativo e sinais de crons atrasados.

No VPS, rode em modo estrito para falhar quando faltar extensão ou limite mínimo:

```bash
php tools/healthcheck_fbcontrol.php --strict
```

Validação completa multiplataforma:

```bash
php tools/run_checks.php
php tools/check_release_candidate.php
```

Higiene de release:

```bash
php tools/check_release_hygiene.php
php tools/build_release.php 3.0
```

O builder gera um pacote `.tar.gz` somente com arquivos rastreados e exclui `config.local.php`, uploads reais, backups e artefatos temporários. O `public/uploads/.htaccess` permanece para manter proteção no Apache.

## Documentação local de estudo

Os mapas funcionais, técnicos, de schema e roadmap ficam em:

`../../../../docs/DOCUMENTACAO_ESTUDO.md`

## Deploy VPS
- Script: `deploy/vps/install.sh`
- Guia: `docs/INSTALACAO_VPS.md`
