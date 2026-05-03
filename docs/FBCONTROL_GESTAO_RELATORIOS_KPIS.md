# FBControl - Gestao, relatorios e KPIs

## Papel da camada gerencial

A camada gerencial do FBControl transforma registros operacionais em acompanhamento para supervisao, gerencia e administracao. Ela junta tres fontes principais:

- Acessos reais dos restaurantes, salvos em `acessos`.
- Reservas tematicas, principalmente finalizadas e no-shows.
- Ocupacao diaria informada manualmente, salva em `kpi_ocupacao_diaria`.

Leitura de produto: o FBControl tenta responder duas perguntas ao mesmo tempo:

- O que aconteceu na porta do restaurante?
- O que isso significa para gestao de A&B, equipe, ocupacao e qualidade operacional?

## Dashboards

Controller: `DashboardController`

Rotas principais:

| Rota | Finalidade |
| --- | --- |
| `/?r=dashboard/index` | Dashboard geral |
| `/?r=dashboard/restaurant&id=...` | Dashboard por restaurante |

Permissao:

- `admin`
- `supervisor`
- `gerente`

O dashboard geral usa filtros de data, periodo, restaurante, operacao e status. Quando faz sentido, ele soma contribuicao de reservas tematicas finalizadas ao total de PAX do painel. Isso evita que restaurante tematico fique invisivel quando a operacao nao passa pelo fluxo classico de acesso.

Recursos observados:

- Total de PAX.
- Totais por restaurante.
- Totais por operacao.
- Fluxo por horario.
- Ultimos registros.
- Merge entre acessos classicos e reservas tematicas finalizadas.
- Tratamento especial para restaurantes tematicos.

Leitura de produto: o dashboard busca uma visao consolidada da operacao, mesmo que os dados venham de fluxos diferentes.

## Relatorios operacionais

Controller: `RelatoriosController`

Rota principal:

- `/?r=relatorios/index`

Permissao:

- `admin`
- `supervisor`
- `gerente`

Fontes usadas:

- `AccessModel`
- `CollaboratorMealModel`
- `VoucherModel`
- `ReservaTematicaModel`

Filtros principais:

- Data unica.
- Periodo.
- Restaurante.
- Operacao.
- Status operacional.
- UH.

Status filtraveis no relatorio de acessos:

| Status | Sentido |
| --- | --- |
| `ok` | Registro normal |
| `duplicado` | Alerta de duplicidade |
| `fora_horario` | Registro fora do horario esperado |
| `multiplo` | Contexto de multiplos registros |
| `nao_informado` | UH tecnica/nao informada |
| `day_use` | Uso associado a UH tecnica |

Exportacoes:

| Rota | Arquivo |
| --- | --- |
| `/?r=relatorios/export` | `relatorio_acessos.csv` ou `.xls` |
| `/?r=relatorios/export_mapa` | `mapa_diario_uh.csv` ou `.xls` |
| `/?r=relatorios/export_bi` | `base_bi.csv` ou `.xls` |
| `/?r=relatorios/export_colaboradores` | `colaboradores_refeicoes.csv` ou `.xls` |
| `/?r=relatorios/export_vouchers` | `vouchers_registrados.csv` ou `.xls` |

Todas as exportacoes importantes registram auditoria via `SecurityLogModel`.

## Relatorios tematicos

Controller: `RelatoriosTematicosController`

Rota:

- `/?r=relatoriosTematicos/index`

Permissao:

- `admin`
- `supervisor`
- `gerente`

Indicadores:

- Reservas.
- PAX reservado.
- PAX comparecido.
- Taxa de comparecimento.
- Totais por restaurante.
- Totais por turno.
- Totais por dia.
- Lista detalhada filtravel.

Exportacao:

- `/?r=relatoriosTematicos/export`
- Saida `relatorio_tematicos.csv` ou `.xls`.

Campos exportados incluem data, turno, restaurante, lote, grupo, responsavel, UH, titular, PAX adulto, PAX CHD, PAX reservado, PAX real, status, excedente, observacoes, tags, usuario e criacao.

Leitura de produto: aqui esta a ponte entre reserva feita e resultado real da noite.

## KPIs

Controller: `KpisController`

Rota principal:

- `/?r=kpis/index`

Permissao:

- Visualizacao: `admin`, `supervisor`, `gerente`.
- Edicao de ocupacao: `admin`, `supervisor`.

Indicadores calculados:

- Resumo geral de acessos.
- Tendencia diaria.
- Ranking de operadores.
- Mix por operacao.
- Mix por restaurante.
- Serie horaria.
- Estatisticas tematicas.
- Taxa de no-show tematico.
- Taxa de comparecimento tematico.
- Ocupacao diaria.
- Relacao entre PAX buffet e ocupacao.

Insights automaticos:

- Alerta se UH nao informada passar de 5%.
- Alerta se taxa de alertas operacionais passar de 12%.
- Alerta se no-show tematico passar de 10%.
- Destaque positivo se comparecimento tematico passar de 90%.
- Alerta se buffet superar ocupacao em mais de 115%.

Leitura de produto: a tela de KPI nao e apenas grafico. Ela tenta sugerir interpretacoes operacionais para lideranca.

## Ocupacao diaria

Tabela: `kpi_ocupacao_diaria`

Campos principais:

- `data_ref`
- `ocupacao_uh`
- `ocupacao_pax`
- `observacao`
- `atualizado_por`
- `atualizado_em`

Dados locais observados:

| Data | UH ocupadas | PAX ocupacao |
| --- | ---: | ---: |
| 2026-04-10 | 257 | 701 |
| 2026-04-11 | 270 | 740 |
| 2026-04-12 | 291 | 790 |
| 2026-04-13 | 277 | 760 |
| 2026-04-14 | 316 | 859 |
| 2026-04-15 | 318 | 864 |
| 2026-04-16 | 308 | 837 |
| 2026-04-17 | 336 | 933 |
| 2026-04-18 | 279 | 784 |
| 2026-04-19 | 270 | 772 |
| 2026-04-20 | 269 | 769 |
| 2026-04-21 | 279 | 776 |

Resumo:

- 12 dias de ocupacao preenchida.
- Soma de ocupacao UH: 3470.
- Soma de ocupacao PAX: 9585.

## Dados reais de acessos

Periodo observado em `acessos`: 2026-04-10 a 2026-04-27.

Resumo por restaurante/operacao:

| Restaurante | Operacao | Registros | PAX | UHs unicas |
| --- | --- | ---: | ---: | ---: |
| Restaurante Corais | Jantar | 3271 | 8584 | 426 |
| Restaurante Corais | Almoco | 370 | 925 | 217 |
| Restaurante Corais | Cafe da Manha | 285 | 657 | 221 |
| Privileged | Privileged | 24 | 66 | 18 |

Leitura de produto: no dataset local, o grande volume do fluxo classico esta no Jantar do Corais. Os restaurantes tematicos aparecem mais fortemente pelo modulo de reservas.

## Auditoria e seguranca operacional

Tabela: `auditoria`

Eventos mais frequentes observados:

| Tabela/area | Acao | Quantidade |
| --- | --- | ---: |
| seguranca | auth_login_success | 324 |
| seguranca | auth_logout | 161 |
| turnos | create | 67 |
| turnos | update | 51 |
| usuarios_restaurantes | create | 48 |
| acessos | update_pax_2min | 24 |
| turnos | auto_close_timeout | 15 |
| usuarios_restaurantes_operacoes | create | 14 |
| usuarios | create | 13 |
| usuarios | update | 10 |
| colaborador_refeicoes | create | 9 |
| seguranca | csrf_invalid | 3 |

Leitura de produto: o sistema ja registra eventos sensiveis de seguranca, mudancas em turno, permissoes e correcao de PAX.

## Usuarios e permissoes

Controller: `UsuariosController`

Permissao:

- Somente `admin`.

Perfis observados no banco local:

| Perfil | Ativo | Usuarios |
| --- | ---: | ---: |
| admin | 1 | 1 |
| gerente | 1 | 1 |
| supervisor | 1 | 1 |
| hostess | 1 | 10 |
| hostess | 0 | 1 |

O cadastro de usuario permite vincular:

- Restaurantes inteiros.
- Operacoes especificas por restaurante.

Leitura de produto: a permissao e granular o bastante para uma hostess operar so o que faz sentido para sua escala/restaurante.

## Relatorio diario por email

Controller: `EmailRelatoriosController`

Model: `DailyReportEmailModel`

Permissao:

- Somente `admin`.

Recursos:

- Configurar remetente.
- Configurar destinatarios.
- Definir se destinatario recebe anexo de vouchers.
- Enviar relatorio manualmente com `sendNow`.
- Salvar configuracao de relatorio diario.

Tabelas relacionadas:

- `relatorio_email_config`
- `relatorio_email_destinatarios`
- `relatorio_email_envios`

Leitura de produto: o sistema tem caminho para rotina executiva automatizada, onde a gestao recebe o consolidado sem precisar entrar no painel.

## LGPD e governanca

Controller: `LgpdController`

Permissao:

- Somente `admin`.

Recursos principais:

- Configuracao LGPD.
- Solicitacoes do titular.
- Registro e acompanhamento de incidentes.
- Politicas de retencao.
- Execucao manual de retencao.
- Modos de retencao: `anonimizar` ou `eliminar`.

Tipos de solicitacao aceitos:

- Acesso.
- Correcao.
- Anonimizacao.
- Eliminacao.
- Portabilidade.
- Oposicao.
- Revogacao.

Leitura de produto: apesar de ser um sistema operacional interno, ha uma preocupacao formal com privacidade e retencao de dados.

## Pontos fortes

- Dashboards integram fluxo classico e tematico.
- Exportacoes CSV/XLS cobrem BI, operacao, mapa diario, colaboradores e vouchers.
- KPI de ocupacao permite comparar consumo real com ocupacao do hotel.
- Auditoria registra exportacoes e acoes sensiveis.
- Usuarios possuem permissao por restaurante e operacao.
- Relatorio por email indica maturidade para rotina executiva.
- Modulo LGPD e incomum em sistemas pequenos e agrega valor institucional.

## Pontos de atencao

- Muitos calculos de produto estao distribuidos entre controllers e models.
- Alguns exports usam extensao `.xls`, mas geram conteudo tabular simples via `fputcsv`; funciona, mas nao e XLSX real.
- A ocupacao diaria e manual; se houver PMS/CM externo, integracao futura agregaria muito valor.
- Os paineis dependem de convencoes de nome como "Corais", "Giardino", "IX", "La Brasa" e "Tematico".
- Existem arquivos `.bak` versionados tambem nos controllers de relatorio/tematicos/LGPD.

## Leitura estrategica

A camada gerencial mostra que o FBControl quer ser uma plataforma de inteligencia operacional de A&B:

- Hostess registra a realidade na ponta.
- Reservas tematicas organizam capacidade e comparecimento.
- Dashboards consolidam leitura para lideranca.
- KPIs conectam consumo com ocupacao.
- Exportacoes alimentam BI e auditoria.
- LGPD e logs dao sustentacao institucional.

Se o produto evoluir, o caminho natural e transformar esses dados em previsao: demanda por restaurante, taxa de comparecimento por turno, risco de no-show, necessidade de equipe e alertas antes da operacao acontecer.

