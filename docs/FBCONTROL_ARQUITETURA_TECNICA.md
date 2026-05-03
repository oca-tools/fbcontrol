# FBControl - Arquitetura tecnica

## Visao geral

O FBControl e uma aplicacao PHP 8+ em MVC simples, sem framework externo de backend. O servidor web deve apontar para a pasta `public`, e toda navegacao passa pelo arquivo `public/index.php`.

Stack observada:

| Camada | Tecnologia |
| --- | --- |
| Backend | PHP 8+, MVC simples |
| Banco | MySQL/MariaDB via PDO |
| Frontend | PHP views, Bootstrap 5, Bootstrap Icons, CSS proprio |
| Sessao | PHP session |
| Rotas | Query string `?r=controller/action` |
| Deploy | Estrutura por releases + `current` + `shared` |

No ambiente local Windows, a release ativa esta em:

`apps/fbcontrol/releases/20260410_144241`

## Estrutura de pastas

| Pasta | Papel |
| --- | --- |
| `public/` | Document root, assets publicos e entrada da aplicacao |
| `app/controllers/` | Controllers chamados pelo roteador |
| `app/models/` | Models com SQL/PDO e regras de consulta |
| `app/views/` | Views PHP renderizadas pelo layout |
| `app/views/partials/` | Header/footer compartilhados |
| `app/core/` | `Database`, `Model`, `Controller`, `Auth` |
| `app/helpers/` | Funcoes globais de CSRF, escaping, normalizacao e badges |
| `app/cron/` | Jobs executaveis via CLI |
| `config/` | Configuracao base e override local |
| `sql/` | Schemas e migrations versionadas |
| `assets/` | Assets legados/compartilhados |
| `central/` | Copia da landing central dentro da release |

## Bootstrap da request

Arquivos:

- `public/index.php`
- `app/bootstrap_web.php`

Fluxo de inicializacao:

1. `public/index.php` define exibicao de erros conforme `APP_ENV`.
2. Configura sessao, cookie seguro, timeout e fallback de `session.save_path`.
3. Inicia sessao.
4. Envia headers de seguranca.
5. Monta Content Security Policy.
6. Carrega `app/bootstrap_web.php`.
7. O bootstrap web carrega polyfills, config, timezone, helpers, core, auth, controllers e models.
8. Se usuario esta logado, aplica timeout e guards opcionais.
9. Resolve rota `?r=controller/action`.
10. Aplica CSRF global para POST.
11. Instancia controller e chama action publica.

Headers de seguranca observados:

- `X-Frame-Options: SAMEORIGIN`
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy`
- `Cross-Origin-Opener-Policy`
- `Cross-Origin-Resource-Policy`
- `Strict-Transport-Security` quando HTTPS
- `Content-Security-Policy`

Leitura tecnica: o bootstrap agora separa inicializacao compartilhada de seguranca/roteamento. Isso reduz duplicacao sem mudar o comportamento publico das requests.

## Roteamento

Padrao:

`/?r=nomeController/metodo`

Exemplos:

| URL | Controller/metodo |
| --- | --- |
| `/?r=auth/login` | `AuthController::login` |
| `/?r=access/index` | `AccessController::index` |
| `/?r=reservasTematicas/reservas` | `ReservasTematicasController::reservas` |
| `/?r=dashboard/index` | `DashboardController::index` |

Regra de conversao:

- Primeiro segmento vira controller.
- Primeira letra e convertida para maiuscula.
- Sufixo `Controller` e adicionado.
- Segundo segmento vira action.

Protecoes:

- Rota aceita apenas letras, numeros, `_`, `/` e `-`.
- Action precisa ter nome valido.
- Action precisa ser publica.
- Action precisa estar declarada no proprio controller, nao herdada.
- Rota inexistente cai em `ErrorsController::notFound`.

Rotas vazias:

- Visitante vai para `auth/login`.
- Gerente logado vai para `dashboard/index`.
- Demais logados vao para `access/index`.

## Controllers mapeados

| Controller | Actions publicas |
| --- | --- |
| `AccessController` | `index`, `start`, `register`, `register_tematica`, `correct_last`, `register_colaborador`, `register_voucher` |
| `AuthController` | `login`, `logout` |
| `DashboardController` | `index`, `restaurant` |
| `RelatoriosController` | `index`, `export`, `export_mapa`, `export_bi`, `export_colaboradores`, `export_vouchers` |
| `RelatoriosTematicosController` | `index`, `export` |
| `ReservasTematicasController` | `reservas`, `operacao`, `admin`, `print` |
| `KpisController` | `index`, `saveOcupacao`, `exportTrend` |
| `TurnosController` | `start`, `end`, `especial_end`, `cancel` |
| `UsuariosController` | `index`, `create`, `edit`, `delete` |
| `LgpdController` | `index`, `saveConfig`, `addRequest`, `updateRequest`, `addIncident`, `updateIncident`, `saveRetention`, `runRetentionNow` |
| `EmailRelatoriosController` | `index`, `saveConfig`, `addRecipient`, `updateRecipientAttachment`, `removeRecipient`, `sendNow` |
| `HostessController` | `turnos`, `foto` |
| `RestaurantesController` | `index`, `create`, `edit` |
| `OperacoesController` | `index`, `create`, `edit` |
| `PortasController` | `index`, `create`, `edit` |
| `HorariosController` | `index`, `create`, `edit` |
| `VouchersController` | `index` |
| `EspeciaisController` | `index`, `start`, `register` |
| `ControlController` | `index` |
| `OnboardingController` | `hostessSeen`, `hostessComplete` |
| `PrivacidadeController` | `index` |
| `ApiController` | `ping` |

## Configuracao

Arquivo base: `config/config.php`

Fontes de configuracao:

1. Variaveis de ambiente.
2. Defaults do proprio `config.php`.
3. Override opcional em `config/config.local.php`.

Variaveis importantes:

| Variavel | Papel |
| --- | --- |
| `APP_ENV` | Controla exibicao de erros |
| `APP_NAME` | Nome da aplicacao |
| `APP_VERSION` | Versao exibida/usada |
| `APP_BASE_URL` | Base URL |
| `APP_TIMEZONE` | Timezone |
| `APP_SESSION_TIMEOUT_MIN` | Timeout de sessao |
| `APP_SESSION_NAME` | Nome do cookie de sessao |
| `APP_ENABLE_STRICT_SESSION_GUARDS` | Liga guards extras |
| `APP_ENFORCE_SESSION_BINDING` | Vincula sessao ao contexto do cliente |
| `APP_ENFORCE_SINGLE_SESSION` | Enforca sessao unica por usuario |
| `DB_HOST` | Host do banco |
| `DB_NAME` | Nome do banco |
| `DB_USER` | Usuario do banco |
| `DB_PASS` | Senha do banco |
| `DB_CHARSET` | Charset |

Ponto local importante:

- `apps/fbcontrol/current` e um symlink quebrado no Windows apontando para caminho Linux.
- `releases/20260410_144241/config/config.local.php` tambem aparece como link quebrado.
- Por isso, no XAMPP local a aplicacao depende das variaveis definidas no VirtualHost.

## Banco de dados

Arquivo: `app/core/Database.php`

Caracteristicas:

- Usa PDO.
- Singleton por request.
- `PDO::ERRMODE_EXCEPTION`.
- Fetch default como array associativo.
- `PDO::ATTR_EMULATE_PREPARES = false`.
- Charset vem da config, por padrao `utf8mb4`.

Base local usada no estudo:

`controle_ab_vps`

## Base model e auditoria

Arquivo: `app/core/Model.php`

Todo model herda `$this->db` de `Database::getInstance()`.

Existe metodo protegido `audit()` que grava em `auditoria`:

- tabela
- registro_id
- acao
- usuario_id
- dados_antes
- dados_depois
- criado_em

Leitura tecnica: auditoria esta acoplada ao model base, o que e pratico, mas tambem mistura persistencia com governanca.

## Autenticacao e sessao

Arquivo: `app/core/Auth.php`

Recursos:

- `Auth::check()`
- `Auth::user()`
- `Auth::login()`
- `Auth::logout()`
- `Auth::requireRole()`
- timeout por inatividade
- protecao contra session fixation com `session_regenerate_id(true)`
- CSRF token regenerado no login
- binding opcional por IP/User-Agent
- sessao unica opcional via tabela `sessoes_ativas`

Padrao operacional:

- Binding estrito desabilitado por padrao.
- Sessao unica desabilitada por padrao.

Leitura tecnica: isso combina com operacao real de hotel, onde hostess pode trocar de dispositivo/rede e falsos logouts seriam ruins.

## CSRF

Implementacao:

- Token criado por `csrf_token()`.
- Validacao por `csrf_validate()`.
- POST global validado em `public/index.php`.
- Controllers tambem fazem validacoes pontuais em alguns fluxos.
- Falhas de CSRF sao registradas em auditoria de seguranca.

Ponto de atencao:

- Ha validacao global e tambem validacoes locais em controllers. Isso e seguro, mas pode gerar duplicidade conceitual.

## Helpers globais

Arquivo: `app/helpers/functions.php`

Funcoes importantes:

- `normalize_mojibake`
- `normalize_output_mojibake`
- `csrf_token`
- `csrf_validate`
- `set_flash`
- `get_flash`
- `h`
- `sanitize_date_param`
- `sanitize_int_param`
- `sanitize_enum_param`
- `sanitize_uh_param`
- `json_response`
- badges de restaurante, operacao e UH

Ponto relevante:

- A saida HTML passa por `ob_start('normalize_output_mojibake')`.
- Isso corrige sintomas de encoding na renderizacao.

Leitura tecnica: o sistema criou um "airbag" contra mojibake. Ajuda em producao, mas o ideal de medio prazo e corrigir origem dos textos/dados.

## Views e layout

View base:

- `Controller::view()` sempre carrega `partials/header.php`, view especifica e `partials/footer.php`.

Ponto critico:

- `app/views/partials/header.php` foi reduzido para 35 linhas apos as extracoes de layout.
- O CSS inline global ainda tem cerca de 97 KB e foi isolado em `app/views/partials/head_inline_styles.php`.
- O layout ainda depende de scripts/comportamentos globais, principalmente no `footer.php` e no CSS inline.
- Os botoes de troca de tema foram extraidos para `app/views/partials/theme_switch_buttons.php`.
- A topbar desktop foi extraida para `app/views/partials/topbar.php`.
- A navegacao mobile/offcanvas foi extraida para `app/views/partials/mobile_nav.php`.
- O contexto compartilhado do layout foi extraido para `app/views/partials/layout_context.php`.
- A sidebar/menu desktop foi extraida para `app/views/partials/sidebar.php`.
- Os metadados/assets iniciais do head foram extraidos para `app/views/partials/head_meta.php`.
- Os CSS externos do head foram extraidos para `app/views/partials/head_stylesheets.php`.
- O CSS inline do head foi extraido para `app/views/partials/head_inline_styles.php`.
- O CSS de contraste de botoes foi extraido para `app/views/partials/style_button_contrast.php`.
- O CSS dos formularios de logout foi extraido para `app/views/partials/style_logout_forms.php`.
- O hotfix responsivo de `.app-content` foi extraido para `app/views/partials/style_app_content_hotfix.php`.
- O hardening visual global foi extraido para `app/views/partials/style_visual_hardening.php`.

Arquivos CSS externos:

- `public/assets/css/design-system.css`
- `public/assets/css/layout.css`
- `public/assets/css/app-modern.css`

Leitura tecnica: o layout virou um "mini frontend framework" dentro de um unico PHP. Funciona, mas qualquer ajuste visual global precisa ser feito com muito cuidado.

## Cron jobs

Pasta: `app/cron`

Bootstraps compartilhados:

- `app/bootstrap_web.php`
- `app/bootstrap_cli.php`

| Arquivo | Papel |
| --- | --- |
| `auto_close_shifts.php` | Fecha turnos ativos expirados |
| `lgpd_retention.php` | Executa retencao LGPD |
| `reservas_tematicas_auto_no_show.php` | Marca reservas expiradas como no-show |
| `send_daily_report_email.php` | Envia relatorio diario quando devido |

Caracteristicas:

- Cada cron usa `app/bootstrap_cli.php`.
- O bootstrap CLI carrega config, timezone, charset, helpers, core e autoload de models.
- Saida e texto simples para log.

Status apos saneamento local:

- A repeticao de bootstrap dos crons foi removida.
- O bootstrap foi validado isoladamente com `BOOTSTRAP_OK`.
- O bootstrap web foi validado isoladamente com `WEB_BOOTSTRAP_OK` e HTTP 200 em `/auth/login`.
- Lint PHP passou nos 109 arquivos ativos.
- Smoke check local disponivel em `tools/smoke_fbcontrol.php`.

## SQL e migrations

Pasta: `sql`

Arquivos observados:

- `schema_v2_0_final.sql`
- `schema_v2_1_final.sql`
- `migration_v2_1_lgpd.sql`
- `migration_v2_1_security_hardening.sql`
- `migration_v2_1_users_email_non_unique.sql`
- `migration_v2_2_reservas_tematicas_lote_chd.sql`
- `migration_v2_3_grupo_nome.sql`
- `migration_v2_3_titular_nome.sql`

Ponto observado:

- O codigo possui fallback para colunas/tabelas opcionais, como `pax_real`, `grupo_id`, `grupo_nome`, `titular_nome`, tabelas de CHD/grupos e `auto_cancel_no_show_min`.
- O dump local recebeu `auto_cancel_no_show_min` via migration v2.4.
- A migration `sql/migration_v2_4_auto_no_show_min.sql` foi criada para padronizar a coluna.
- O model ainda trata ausencia da coluna e assume `0`, mantendo compatibilidade com bancos antigos.

Leitura tecnica: ha compatibilidade com bancos em versoes diferentes. Isso e positivo para deploy gradual, mas pede um mapa claro de schema alvo.

## Tamanho e concentracao dos arquivos

Maiores arquivos ativos:

| Arquivo | Tamanho aproximado |
| --- | ---: |
| `app/views/partials/head_inline_styles.php` | 105 KB |
| `app/controllers/ReservasTematicasController.php` | 61 KB |
| `app/models/ReservaTematicaModel.php` | 57 KB |
| `app/views/access/index.php` | 51 KB |
| `app/controllers/AccessController.php` | 47 KB |
| `app/models/AccessModel.php` | 42 KB |
| `app/views/reservas_tematicas/reservas.php` | 39 KB |
| `app/views/reports/index.php` | 38 KB |
| `app/views/reservas_tematicas/operacao.php` | 38 KB |
| `app/views/partials/footer.php` | 36 KB |

Leitura tecnica: os pontos de maior risco de manutencao sao arquivos grandes, com muitas responsabilidades e regras misturadas.

## Backups dentro da release

Foram encontrados 41 arquivos `*.bak*`, somando cerca de 1,8 MB, e eles foram movidos para `apps/fbcontrol/_archive_bak_20260427`.

Exemplos:

- backups de controllers grandes
- backups de models grandes
- backups de views grandes
- varios backups do `header.php`

Ponto curioso:

- `.gitignore` ignora `*.bak`, mas a release continha arquivos `.bak.2026...`.
- Isso indica que os backups provavelmente foram gerados no servidor ou empacotados fora de um fluxo Git limpo.

Risco:

- O risco operacional imediato foi reduzido na copia local.
- Ainda vale criar verificacao de deploy para impedir novos `.bak*`.
- Mais confusao ao buscar codigo.
- Possibilidade de editar arquivo errado.

## Resultado de verificacao local

Comando equivalente executado:

`php -l` em todos os PHP ativos, excluindo symlinks quebrados e backups.

Resultado:

- 94 arquivos PHP ativos verificados.
- Nenhum erro de sintaxe encontrado.
- O unico problema inicial foi o symlink quebrado `config/config.local.php`, que nao e erro de sintaxe do codigo.

## Pontos fortes tecnicos

- Bootstraps web e CLI simples e compreensiveis.
- PDO configurado de forma segura.
- CSRF global em POST.
- Headers de seguranca bem completos.
- Redirect protegido contra URL externa.
- Sessao com timeout e mitigacao de fixation.
- Guards de sessao opcionais pensados para ambiente real.
- Auditoria presente em acoes sensiveis.
- Compatibilidade com schema em evolucao.
- Lint PHP limpo nos arquivos ativos.

## Riscos e dividas tecnicas

- Controllers e models grandes concentram regra demais.
- `head_inline_styles.php` concentra CSS global e ainda merece divisao futura.
- Backups `.bak*` ja foram movidos para fora da release local.
- Symlinks de Linux quebrados no Windows local.
- Regras de negocio aparecem em controllers, models e views.
- Normalizacao de mojibake mascara problema de origem.
- Alguns recursos dependem de nomes textuais de restaurante/operacao.
- Web e crons ja usam bootstraps compartilhados no ambiente local.
- Nao ha camada formal de rotas, middlewares ou services.
- Existe smoke check CLI inicial, mas ainda nao ha suite de testes de regra.
- README cita scripts/schemas antigos que nao aparecem na release atual.

## Caminho recomendado de evolucao

Ordem segura para melhorar sem quebrar operacao:

1. Criar um documento de schema alvo e comparar com dump real.
2. Separar CSS inline global e scripts globais em arquivos/partials menores.
3. Extrair regras de `AccessController` e `ReservasTematicasController` para services.
4. Evoluir o smoke check para testes pequenos de regras criticas: duplicidade, capacidade, no-show, turno e permissao.
5. Padronizar migrations para qualquer coluna usada por fallback.
6. Corrigir encoding na origem e reduzir dependencia de `normalize_output_mojibake`.

## Leitura estrategica

Tecnicamente, o FBControl e uma aplicacao artesanal, mas nao improvisada. Ela tem varias decisoes maduras para producao: seguranca, auditoria, permissao, crons e compatibilidade de schema.

O maior desafio agora nao e "fazer funcionar"; isso ele ja faz. O desafio e transformar o sistema em uma base mais modular, onde novas funcionalidades possam entrar sem aumentar o risco dos fluxos criticos de hostess, reservas tematicas e relatorios.
