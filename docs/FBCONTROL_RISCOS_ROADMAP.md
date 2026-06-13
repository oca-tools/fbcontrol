# FBControl - Riscos, oportunidades e roadmap

## Resumo executivo

O FBControl esta funcional, consistente com o banco real e bem aderente a operacao de A&B. A analise nao encontrou um problema estrutural que impeca o uso local ou indique quebra imediata.

O principal risco agora e manutencao: regras importantes ainda estao concentradas em arquivos grandes e algumas features aparecem parcialmente preparadas no codigo. Parte do ruido operacional ja foi reduzida com README atualizado, docs organizados, backups movidos e bootstraps compartilhados.

Prioridade recomendada:

1. Concluir saneamento de auditoria e aplicar a migration de seguranca.
2. Evoluir o smoke check para testes pequenos de regras sensiveis.
3. Modularizar os fluxos criticos com cobertura minima.
4. So depois evoluir produto com features novas.

## Classificacao de risco

| Nivel | Significado |
| --- | --- |
| P0 | Pode quebrar operacao, seguranca ou dados rapidamente |
| P1 | Alto impacto, mas com contorno operacional |
| P2 | Divida tecnica importante |
| P3 | Melhoria, acabamento ou oportunidade |

## P0 - Riscos criticos

### Segredos em payloads de auditoria

Status no codigo: **CORRIGIDO**.

O cadastro de usuario enviava o campo `senha` recebido pelo controller para `Model::audit`.
Snapshots de usuario tambem podiam duplicar hashes de senha dentro de `dados_antes` e
`dados_depois`.

Controles implementados:

- Redacao recursiva de senhas, tokens e outros segredos no nucleo `Model::audit`.
- `SecurityLogModel` usa a mesma redacao.
- O cadastro registra apenas `senha_definida = true`.
- `tools/check_audit_sanitizer.php` impede regressao basica.
- `tools/sanitize_audit_sensitive_data.php` limpa payloads historicos em dry-run ou `--apply`.

Acao obrigatoria no deploy:

1. Fazer backup do banco.
2. Aplicar `sql/migration_v3_1_audit_security.sql`.
3. Executar `php tools/sanitize_audit_sensitive_data.php`.
4. Revisar a contagem e executar novamente com `--apply`.

## P1 - Riscos altos

### README e instalacao

Status: **RESOLVIDO**.

O `README.md` aponta para `sql/schema_v3_0.sql` em instalacoes novas e lista as migrations de
upgrade em ordem. O fluxo de upgrade tambem documenta o saneamento da auditoria.

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

Status: **RISCO ACEITO TEMPORARIAMENTE**.

O login desambigua contas por senha e associa operacoes ao `usuario_id`, mas isso identifica a
credencial aceita, nao garante qual pessoa utilizou o equipamento. A politica e os controles
obrigatorios estao em `docs/FBCONTROL_POLITICA_EMAIL_COMPARTILHADO.md`.

Evolucao recomendada:

- Criar identificador individual de login (usuario, matricula ou codigo).
- Manter o e-mail compartilhado apenas como contato operacional.

### Arquivos `.bak` dentro da release

Status: **RESOLVIDO NO FLUXO OFICIAL**.

`tools/build_release.php` empacota apenas arquivos rastreados e exclui `.bak*`.
`tools/check_release_hygiene.php --strict` bloqueia artefatos rastreados.

Limitacao: essa garantia depende do builder oficial. Compactar manualmente a pasta inteira pode
incluir arquivos nao rastreados, portanto deploys devem usar exclusivamente o builder ou
`git archive`.

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

### Consolidar schema de instalacao

Status: concluido na release 3.0 com `sql/schema_v3_0.sql`.

Uso recomendado:

- `sql/schema_v3_0.sql` para ambientes novos.
- Migrations versionadas apenas para upgrades incrementais de bancos existentes.

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

- Aplicar a migration de auditoria e sanear payloads historicos.
- Manter a politica de e-mail compartilhado como risco aceito e revisado.
- Atualizar README de instalacao.
- Manter `schema_v3_0.sql` como schema consolidado de instalacao nova.
- Remover/mover `.bak*` da release operacional.
- Documentar symlinks locais e uso direto da release no Windows.
- Criar migration ou decisao formal sobre `auto_cancel_no_show_min`.

Resultado esperado:

- Novo ambiente sobe com menos tentativa e erro.
- Codigo fica menos poluido.
- Schema minimo fica claro.

### Fase 1 - Testes de regras criticas

Objetivo: proteger fluxos que geram dinheiro/operacao.

Status: **COBERTURA INICIAL IMPLEMENTADA**.

`tools/test_critical_rules.php` usa tabelas temporarias e valida sem alterar dados reais:

- duplicidade exata dentro da janela de 10 minutos;
- multiplo acesso apenas no segundo registro, com PAX diferente e intervalo maior que 15 minutos;
- exclusao das UHs tecnicas 998/999;
- capacidade tematica com PAX real e reservas canceladas;
- permissao de edicao por autoria e hierarquia;
- encerramento por inatividade e protecao do modo demonstracao em turnos regulares e especiais.

Tarefas:

- Ampliar gradualmente os cenarios conforme bugs reais forem corrigidos.
- Adicionar cobertura de bloqueios semanais e por data.
- Testar permissoes de vinculo entre hostess, restaurante e operacao.

Resultado esperado:

- Mudancas deixam de depender so de teste manual.

### Fase 2 - Refatoracao sem mudar comportamento

Objetivo: quebrar arquivos gigantes em partes menores com cobertura minima.

Status: **PRIMEIRO CICLO IMPLEMENTADO**.

Entregas:

- `ReservaTematicaPolicy` concentra autoria, UHs tecnicas e idades CHD.
- `ShiftAutoCloseService` concentra encerramento automatico regular e especial.
- `TematicAccessService` unifica classificacao de restaurantes, operacoes e acesso da hostess.
- Scripts globais foram isolados em `footer_scripts.php`.
- Estilos globais foram isolados em `style_global.php`.

Tarefas:

- Continuar subdividindo scripts e estilos por responsabilidade.
- Extrair proximas regras de reservas tematicas conforme a cobertura crescer.
- Reduzir controllers grandes sem misturar mudanca visual ou funcional.

Resultado esperado:

- Menos risco em alteracoes futuras.
- Mais clareza para novas features.

### Fase 3 - Performance e escala

Objetivo: manter consultas e exportacoes previsiveis com crescimento do historico.

Status: **PRIMEIRO CICLO IMPLEMENTADO**.

Entregas:

- exportacoes lineares usam cursor por chave estavel, sem custo acumulado de `OFFSET`;
- indices compostos cobrem filtros combinados de acessos e buscas tematicas;
- `tools/check_query_performance.php` valida indices, planos `EXPLAIN` e total exportado;
- schema consolidado e migration incremental permanecem alinhados.

Tarefas:

- acompanhar planos no VPS conforme o volume real crescer;
- avaliar paginação por cursor na interface quando páginas muito profundas se tornarem comuns;
- evoluir agregados anuais para tabelas de resumo somente quando medições justificarem.

Resultado esperado:

- exportacoes extensas sem crescimento quadratico de leitura;
- regressões de indice detectadas antes do deploy.

### Fase 3.1 - Endurecimento de seguranca

Objetivo: reduzir a superficie de ataque antes de novas evolucoes funcionais.

Status: **PRIMEIRO CICLO IMPLEMENTADO**.

Entregas:

- revalidacao periodica do usuario autenticado para encerrar sessoes de contas desativadas;
- codificacao segura de dados PHP inseridos em JavaScript;
- anexos de vouchers servidos somente por rota autenticada;
- bloqueio HTTP direto ao diretorio de vouchers;
- validacao restrita de caminhos e extensoes de fotos e vouchers;
- limites de diretorio com separador completo, evitando confusao por prefixos semelhantes;
- protecao contra clickjacking com `DENY` e `frame-ancestors 'none'`;
- scanner SAST ampliado para JSON bruto em scripts e links diretos de vouchers;
- testes automatizados dos controles de seguranca adicionados ao runner principal.

Revisao executada:

- nenhum uso de `eval`, desserializacao insegura ou execucao de comandos foi encontrado no codigo web;
- consultas com trechos dinamicos usam valores internos ou listas permitidas;
- CSRF global, consultas preparadas e validacao de MIME existentes foram mantidos.

Tarefas:

- validar no VPS se o Apache respeita o bloqueio direto em `/uploads/vouchers/`;
- acompanhar logs de sessao encerrada por usuario desativado;
- repetir a auditoria sempre que novas rotas de upload ou download forem adicionadas.

### Fase 3.2 - LGPD e minimizacao operacional

Objetivo: reduzir duplicacao de dados pessoais em logs internos e orientar operadores.

Status: **PRIMEIRO CICLO IMPLEMENTADO**.

Entregas:

- eventos internos de LGPD passam a redigir documentos, e-mails, nomes de titulares e textos livres;
- formulários rápidos de status não carregam mais documento/e-mail/detalhes em campos ocultos;
- campos livres do módulo LGPD foram limitados e higienizados;
- exportação tabular de vouchers não expõe mais o caminho técnico do anexo;
- aviso de privacidade ganhou seção objetiva de minimização para a operação;
- `tools/sanitize_lgpd_event_details.php` permite medir e sanear eventos históricos.

Tarefas:

- executar o saneamento de eventos LGPD no VPS em modo dry-run e depois com `--apply`;
- revisar contatos de controlador/encarregado antes do uso formal;
- alinhar o aviso com a redação jurídica dos termos de check-in.

### Fase 4 - Produto e inteligencia operacional

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
