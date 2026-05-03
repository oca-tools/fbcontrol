# FBControl - Riscos, oportunidades e roadmap

## Resumo executivo

O FBControl esta funcional, consistente com o banco real e bem aderente a operacao de A&B. A analise nao encontrou um problema estrutural que impeca o uso local ou indique quebra imediata.

O principal risco agora e manutencao: regras importantes ainda estao concentradas em arquivos grandes e algumas features aparecem parcialmente preparadas no codigo. Parte do ruido operacional ja foi reduzida com README atualizado, docs organizados, backups movidos e bootstraps compartilhados.

Prioridade recomendada:

1. Continuar modularizando aos poucos os fluxos criticos.
2. Evoluir o smoke check inicial para testes pequenos de regras sensiveis.
3. Preparar deploy/schema do v2.4 no VPS quando aprovado.
4. So depois evoluir produto com features novas.

## Classificacao de risco

| Nivel | Significado |
| --- | --- |
| P0 | Pode quebrar operacao, seguranca ou dados rapidamente |
| P1 | Alto impacto, mas com contorno operacional |
| P2 | Divida tecnica importante |
| P3 | Melhoria, acabamento ou oportunidade |

## P0 - Riscos criticos

### Nenhum P0 confirmado

Nao encontrei, nesta rodada, um risco critico imediato como:

- Erro de sintaxe em PHP ativo.
- Divergencia estrutural entre banco real e migrations aplicadas.
- Upload sem validacao basica.
- Ausencia geral de CSRF.
- Senhas sem hash.
- Roteamento permitindo action arbitraria herdada.

Validacoes realizadas:

- 94 arquivos PHP ativos passaram em `php -l`.
- Banco real bateu com `schema_v2_1_final.sql` + migrations.
- Upload de foto e voucher valida tamanho, extensao e MIME.
- Senhas usam `password_hash` e `password_verify`.
- POST tem CSRF global no bootstrap.

## P1 - Riscos altos

### README e instalacao desatualizados

O `README.md` menciona arquivos antigos como `schema_v1_1_final.sql` e migrations v2.0 que nao aparecem na release atual.

Impacto:

- Novo deploy pode ser feito na ordem errada.
- Ambiente local de outro desenvolvedor pode nascer incompleto.
- Risco de usar `schema_v2_1_final.sql` sozinho e perder tabelas/colunas das migrations v2.2/v2.3.

Acao recomendada:

- Atualizar README com a ordem real:
  `schema_v2_1_final.sql`, security hardening, users email non unique, reservas lote/CHD, titular nome, grupo nome.

### `auto_cancel_no_show_min` preparado no codigo, ausente no schema original

O codigo suporta `reservas_tematicas_config.auto_cancel_no_show_min`, mas a coluna nao existia no dump original nem nas migrations ate v2.3.

Impacto:

- A tela/admin pode sugerir configuracao de tolerancia que nao persiste de fato.
- Auto no-show configuravel fica sempre em `0`.
- A equipe pode acreditar que uma regra automatica esta ativa quando nao esta.

Status apos saneamento local:

- Foi criada `migration_v2_4_auto_no_show_min.sql`.
- A migration foi aplicada no banco local.
- `schema_current.sql` foi regenerado com a coluna.

Acao pendente:

- Aplicar a migration no VPS quando for decidido ativar a tolerancia configuravel em producao.

### E-mail compartilhado por varias hostess

No banco local, 10 hostess ativas usam `hostessoca@gmail.com`.

Impacto:

- Auditoria de login por e-mail perde valor.
- Recuperacao/gestao de identidade fica confusa.
- `UserModel::authenticateByEmailAndPassword` trata multiplos usuarios com mesmo e-mail, o que exige cuidado operacional.

Observacao:

- O sistema parece ter sido ajustado para permitir e-mail nao unico.
- Isso pode ser uma decisao operacional, nao necessariamente bug.

Acao recomendada:

- Definir politica: e-mail compartilhado e permitido oficialmente ou e um workaround?
- Se for permitido, documentar.
- Se nao for, criar login por usuario/codigo ou exigir e-mail unico por pessoa.

### Arquivos `.bak` dentro da release

Foram encontrados 41 arquivos `.bak*`, somando cerca de 1,8 MB. Eles foram movidos para `apps/fbcontrol/_archive_bak_20260427` no ambiente local.

Impacto:

- Busca no codigo fica poluida.
- Risco de editar arquivo errado.
- Deploy carrega historico operacional desnecessario.
- Pode vazar logica antiga ou informacao sensivel no pacote.

Status apos saneamento local:

- Backups movidos para fora da release operacional.
- Inventario mantido em `docs/FBCONTROL_BACKUPS_BAK_INVENTARIO.md`.

Acao pendente:

- Adicionar uma verificacao de deploy para bloquear `.bak*`.

## P2 - Dividas tecnicas importantes

### Arquivos grandes demais

Maiores pontos:

| Arquivo | Linhas |
| --- | ---: |
| `app/views/partials/head_inline_styles.php` | 2781 |
| `app/models/ReservaTematicaModel.php` | 1316 |
| `app/controllers/ReservasTematicasController.php` | 1180 |
| `app/models/AccessModel.php` | 996 |
| `app/views/access/index.php` | 968 |
| `app/controllers/AccessController.php` | 964 |
| `app/views/partials/footer.php` | 835 |

Impacto:

- Mudancas pequenas ficam arriscadas.
- Dificulta onboarding.
- Dificulta teste automatizado.
- Aumenta chance de regressao nos fluxos de hostess e reservas.

Acao recomendada:

- Comecar por extracoes sem mudar comportamento.
- Separar layout: head, sidebar, topbar, menus, scripts e temas.
- Depois extrair services dos controllers criticos.

### Regras de negocio espalhadas

Hoje regras aparecem em:

- Controllers.
- Models.
- Views.
- Helpers globais.

Exemplos:

- Duplicidade de UH.
- Capacidade de reserva.
- Permissao de hostess por restaurante.
- Horario/turno.
- Status tematico.
- Auto no-show.

Impacto:

- Mudar uma regra exige caçar varias partes.
- Testar comportamento fica dificil.
- Risco de regras divergentes entre fluxo classico e fluxo tematico.

Acao recomendada:

- Criar services pequenos:
  `AccessRegistrationService`, `ShiftService`, `ReservaTematicaService`, `PermissionService`.

### Fallbacks de schema ainda ativos

O codigo ainda checa dinamicamente colunas/tabelas que ja existem no banco real.

Impacto:

- Mais complexidade.
- Queries mais dificeis de ler.
- Dificulta saber qual schema minimo e suportado.

Acao recomendada:

- Definir oficialmente o schema minimo.
- Depois remover fallbacks legados em uma versao controlada.

### Normalizacao de mojibake na saida

O sistema usa `normalize_output_mojibake` no buffer HTML.

Impacto:

- Ajuda a operacao, mas mascara origem do problema.
- Pode esconder dados/textos gravados com encoding incorreto.

Acao recomendada:

- Mapear onde o mojibake nasce.
- Corrigir arquivos/dados na origem.
- Manter o fallback temporariamente ate estabilizar.

### Bootstrap web/CLI duplicado

Os crons carregavam config, core e autoload manualmente, enquanto a entrada web concentrava inicializacao propria em `public/index.php`.

Impacto:

- Mudanca de bootstrap precisaria ser repetida.
- Risco de divergencia entre web e CLI.

Acao recomendada:

Status apos saneamento local:

- Criado `app/bootstrap_cli.php`.
- Atualizados os crons para usar o bootstrap compartilhado.
- Criado `app/bootstrap_web.php`.
- Atualizado `public/index.php` para carregar a inicializacao compartilhada.
- Validado com `BOOTSTRAP_OK`, `WEB_BOOTSTRAP_OK`, HTTP 200 em `/auth/login` e lint em 109 arquivos PHP ativos.

## P3 - Melhorias e oportunidades

### Consolidar `schema_current.sql`

Hoje o schema real depende de schema final + varias migrations.

Oportunidade:

- Criar um snapshot consolidado atual.
- Deixar migrations apenas para upgrades incrementais.

### Criar testes pequenos de regra

Primeiros testes recomendados:

- UH duplicada.
- UH tecnica `998`/`999`.
- Capacidade por turno.
- Reserva em lote.
- No-show parcial.
- Encerramento de turno.
- Permissao por restaurante/operacao.

Status apos saneamento local:

- Criado `tools/smoke_fbcontrol.php`.
- O smoke valida bootstrap web, banco, tabelas essenciais, coluna `auto_cancel_no_show_min` e render basico do layout logado.
- Ele ainda nao substitui uma suite de testes de regra.

### Melhorar indicadores operacionais

Dados observados:

- 258 acessos em UHs tecnicas `998`/`999`.
- 824 PAX em UHs tecnicas.
- 1 turno aberto no dump local.

Oportunidades:

- Dashboard de qualidade de dados.
- Alerta de turno aberto.
- Indicador de UH nao informada por dia/hostess.
- Ranking de no-show e divergencia por restaurante/turno.

### Evoluir reservas tematicas

O modulo ja tem valor alto. Proximas evolucoes naturais:

- Confirmacao ativa antes do jantar.
- Lista de espera.
- Previsao de no-show por horario/restaurante.
- Avisos de capacidade em tempo real.
- Painel de cozinha por turno.

### Integração futura com PMS/ocupacao

O KPI de ocupacao hoje e manual.

Oportunidade:

- Importar ocupacao diaria automaticamente.
- Comparar PAX previsto, PAX reservado e PAX realizado.

## Roadmap recomendado

### Fase 0 - Higiene e seguranca de manutencao

Objetivo: reduzir risco antes de mexer em feature.

Tarefas:

- Atualizar README de instalacao.
- Criar `schema_current.sql`.
- Remover/mover `.bak*` da release operacional.
- Documentar symlinks locais e uso direto da release no Windows.
- Criar migration ou decisao formal sobre `auto_cancel_no_show_min`.

Resultado esperado:

- Novo ambiente sobe com menos tentativa e erro.
- Codigo fica menos poluido.
- Schema minimo fica claro.

### Fase 1 - Refatoracao sem mudar comportamento

Objetivo: quebrar arquivos gigantes em partes menores.

Tarefas:

- Continuar separando `header.php` em partials.
- Separar scripts globais do `footer.php`.
- Manter bootstraps web/CLI compartilhados pequenos e sem regra de negocio.
- Extrair helpers de permissao.
- Criar camada inicial de service para reservas tematicas.

Resultado esperado:

- Menos risco em alteracoes futuras.
- Mais clareza para novas features.

### Fase 2 - Testes de regras criticas

Objetivo: proteger fluxos que geram dinheiro/operacao.

Tarefas:

- Evoluir `tools/smoke_fbcontrol.php` ou criar base simples de testes PHP.
- Testar regras de duplicidade, PAX, capacidade e turno.
- Criar fixtures pequenas de banco ou mocks simples.
- Testar permissao hostess/restaurante/operacao.

Resultado esperado:

- Mudancas deixam de depender so de teste manual.

### Fase 3 - Produto e inteligencia operacional

Objetivo: transformar dados em acao.

Tarefas:

- Painel de qualidade de dados.
- Alertas de turno aberto.
- No-show tematico ativo/configuravel.
- Relatorio de previsao versus realizado.
- Lista de espera ou controle de overbooking.

Resultado esperado:

- Plataforma passa de registro operacional para apoio de decisao.

## Primeiras tarefas sugeridas

Com os primeiros saneamentos concluidos, eu seguiria nesta ordem:

1. Separar CSS inline global e scripts globais em partes menores.
2. Evoluir o smoke check para testes pequenos de regras criticas.
3. Extrair regras de reservas tematicas para services.
4. Aplicar `migration_v2_4_auto_no_show_min.sql` no VPS quando a feature for habilitada em producao.
5. Separar scripts globais do `footer.php`.

## Decisao recomendada

Minha recomendacao: nao comecar por feature nova ainda.

O sistema ja tem valor de produto e dados reais. O melhor movimento agora e uma etapa curta de saneamento tecnico, porque ela diminui o risco de qualquer melhoria posterior. Depois disso, o modulo de Reservas Tematicas e o melhor candidato para evolucao de produto, pois tem alto impacto operacional e muita informacao ja estruturada.
