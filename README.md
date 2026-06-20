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

## Estrutura de pastas

- `public/`: ponto de entrada HTTP (`index.php`) e assets expostos pelo servidor web. O document root deve apontar para esta pasta.
- `app/bootstrap_web.php`: inicialização da aplicação web, sessão, helpers, core e autoload.
- `app/bootstrap_cli.php`: inicialização para crons e ferramentas CLI.
- `app/core/`: infraestrutura compartilhada (`Controller`, `Model`, `Database`, `Auth`).
- `app/controllers/`: orquestração das rotas, validação de entrada e chamadas aos models/services.
- `app/models/`: acesso a dados e regras persistidas por entidade.
- `app/services/`: serviços de domínio reutilizáveis, como exportações, reservas temáticas e fechamento automático.
- `app/support/AppConstants.php`: constantes compartilhadas de rotas, perfis, mensagens, limites técnicos e formatos de exportação.
- `app/helpers/`: funções utilitárias globais para segurança, formatação, uploads, datas e respostas JSON.
- `app/views/`: templates PHP renderizados pelos controllers.
- `config/`: configuração base e exemplo de configuração local.
- `sql/`: schema completo e migrations incrementais.
- `tools/`: checks, smoke tests, empacotamento e rotinas de manutenção.
- `docs/`: documentação técnica e operacional complementar.

## Guia para manutenção

1. Comece pelo controller da rota em `app/controllers/` para entender o fluxo HTTP.
2. Siga para o model em `app/models/` quando a mudança envolver consulta, escrita ou auditoria de banco.
3. Use `app/services/` para regras reutilizáveis ou operações que não pertencem a uma única tela.
4. Adicione novas constantes compartilhadas em `app/support/AppConstants.php` antes de repetir strings, limites, nomes de perfis, rotas ou formatos em vários arquivos.
5. Mantenha helpers em `app/helpers/functions.php` apenas para utilitários genéricos e sem estado.
6. Ao criar ou alterar funções/classes, inclua tipos de parâmetros e retorno, além de docstrings curtas com entrada e resultado esperado.
7. Depois de alterações estruturais, rode `php tools/run_checks.php` ou, no mínimo, lint nos arquivos PHP modificados.

## Instalação rápida
1. Crie o banco MySQL/MariaDB com charset `utf8mb4`.
2. Execute `sql/schema_v3_0.sql`.
3. Ajuste variáveis de ambiente ou `config/config.local.php`.
4. Configure o servidor web apontando para `public`.
5. Acesse: `/?r=auth/login`.

Exemplo local com MySQL CLI:

```bash
mysql -u usuario -p nome_do_banco < sql/schema_v3_0.sql
```

## Upgrade de bancos antigos

Para atualizar bancos já existentes, não recrie o schema. Aplique as migrations em ordem, validando backup antes:

```bash
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
mysql -u usuario -p nome_do_banco < sql/migration_v3_0_query_performance.sql
mysql -u usuario -p nome_do_banco < sql/migration_v3_1_audit_security.sql
```

Observação: `migration_v2_1_lgpd.sql` só é necessária ao atualizar bancos muito antigos. As tabelas LGPD já estão em `schema_v3_0.sql`.

Como alternativa idempotente ao comando MySQL, a migration pode usar a configuracao do aplicativo:

```bash
php tools/apply_query_performance_indexes.php
php tools/apply_audit_security_migration.php
```

Depois de aplicar `migration_v3_1_audit_security.sql`, revise e saneie payloads historicos:

```bash
php tools/sanitize_audit_sensitive_data.php
php tools/sanitize_audit_sensitive_data.php --apply
```

O primeiro comando e somente leitura e informa quantas linhas precisam de redacao. Execute
`--apply` apenas depois de confirmar que existe backup recente do banco.

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

Checagem de consultas e exportacoes:

```bash
php tools/check_query_performance.php
```

Ela confirma os indices compostos, registra os planos `EXPLAIN` representativos e compara
a contagem esperada com a quantidade realmente percorrida pelas exportacoes por cursor.

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

Teste especifico da redacao de auditoria:

```bash
php tools/check_audit_sanitizer.php
```

Testes isolados das regras operacionais criticas:

```bash
php tools/test_critical_rules.php
```

Esse teste usa apenas tabelas temporarias na conexao local. Nenhuma linha das tabelas
operacionais e inserida, alterada ou removida.

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
