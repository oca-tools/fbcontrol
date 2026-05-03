# FBControl - Schema do banco e migrations

## Objetivo

Este documento compara o banco real importado do VPS com os SQLs da release local do FBControl. A ideia e saber qual e o schema alvo antes de alterar funcionalidades.

Banco real local:

`controle_ab_vps`

Schema de referencia usado:

1. `sql/schema_v2_1_final.sql`
2. `sql/migration_v2_1_security_hardening.sql`
3. `sql/migration_v2_1_users_email_non_unique.sql`
4. `sql/migration_v2_2_reservas_tematicas_lote_chd.sql`
5. `sql/migration_v2_3_titular_nome.sql`
6. `sql/migration_v2_3_grupo_nome.sql`

## Resultado da comparacao inicial

Foi criado um banco temporario de comparacao:

`codex_fbcontrol_schema_v21_20260427`

Processo:

1. Importei `schema_v2_1_final.sql`.
2. Apliquei as migrations listadas acima.
3. Comparei `information_schema` entre o banco temporario e `controle_ab_vps`.

Resultado:

- Tabelas: sem diferenca.
- Colunas: sem diferenca.
- Indices: sem diferenca.
- Foreign keys: sem diferenca.

Leitura inicial: o dump real do VPS estava alinhado com `schema_v2_1_final.sql` mais as migrations da release ate v2.3.

Atualizacao local:

- Foi criada `migration_v2_4_auto_no_show_min.sql`.
- A migration foi aplicada no banco local `controle_ab_vps`.
- `schema_current.sql` foi regenerado depois disso.
- O schema local atual agora inclui `reservas_tematicas_config.auto_cancel_no_show_min`.

## Numeros do schema real

| Item | Quantidade |
| --- | ---: |
| Tabelas | 36 |
| Indices | 127 |
| Foreign keys | 65 |

## Tabelas e volume local

| Tabela | Linhas aproximadas |
| --- | ---: |
| `acessos` | 3950 |
| `reservas_tematicas` | 1560 |
| `reservas_tematicas_logs` | 1528 |
| `auditoria` | 774 |
| `relatorio_email_envios` | 607 |
| `unidades_habitacionais` | 434 |
| `reservas_tematicas_grupos` | 317 |
| `reservas_tematicas_chd` | 221 |
| `turnos` | 67 |
| `usuarios_restaurantes` | 48 |
| `reservas_tematicas_config_turnos` | 18 |
| `usuarios` | 14 |
| `usuarios_restaurantes_operacoes` | 14 |
| `kpi_ocupacao_diaria` | 12 |
| `colaborador_refeicoes` | 9 |
| `restaurante_operacoes` | 8 |
| `usuarios_onboarding` | 8 |
| `operacoes` | 6 |
| `reservas_tematicas_turnos` | 6 |
| `restaurantes` | 6 |
| `restaurante_especiais` | 4 |
| `lgpd_retencao_politicas` | 3 |
| `reservas_tematicas_config` | 3 |
| `portas` | 2 |
| `relatorio_email_destinatarios` | 2 |
| `reservas_tematicas_periodos` | 2 |

Tabelas vazias no dump local:

- `acessos_especiais`
- `lgpd_config`
- `lgpd_eventos`
- `lgpd_incidentes`
- `lgpd_solicitacoes`
- `relatorio_email_config`
- `reservas_tematicas_fechamentos`
- `sessoes_ativas`
- `turnos_especiais`
- `vouchers`

## Agrupamento funcional das tabelas

### Operacao principal

| Tabela | Papel |
| --- | --- |
| `acessos` | Registros de entrada por UH/PAX |
| `turnos` | Turnos normais de usuarios |
| `restaurantes` | Restaurantes/areas |
| `operacoes` | Cafe, almoco, jantar, tematico, etc. |
| `restaurante_operacoes` | Horarios por restaurante/operacao |
| `portas` | Pontos de entrada quando aplicavel |
| `unidades_habitacionais` | Cadastro de UHs |

### Operacao especial

| Tabela | Papel |
| --- | --- |
| `acessos_especiais` | Registros especiais separados |
| `turnos_especiais` | Turnos especiais |
| `restaurante_especiais` | Configuracao de restaurantes especiais |

### Reservas tematicas

| Tabela | Papel |
| --- | --- |
| `reservas_tematicas` | Reserva individual |
| `reservas_tematicas_chd` | Idades CHD por reserva |
| `reservas_tematicas_grupos` | Lotes/grupos de reservas |
| `reservas_tematicas_config` | Capacidade total por restaurante |
| `reservas_tematicas_config_turnos` | Capacidade por restaurante/turno |
| `reservas_tematicas_fechamentos` | Fechamento por data/restaurante/turno |
| `reservas_tematicas_logs` | Historico antes/depois |
| `reservas_tematicas_periodos` | Janelas permitidas para criar reserva |
| `reservas_tematicas_turnos` | Horarios de reserva |

### Gestao e indicadores

| Tabela | Papel |
| --- | --- |
| `kpi_ocupacao_diaria` | Ocupacao manual diaria |
| `colaborador_refeicoes` | Refeicoes de colaboradores |
| `vouchers` | Vouchers/upselling |
| `relatorio_email_config` | Configuracao de envio diario |
| `relatorio_email_destinatarios` | Destinatarios do relatorio |
| `relatorio_email_envios` | Historico de envios |

### Usuarios e permissoes

| Tabela | Papel |
| --- | --- |
| `usuarios` | Usuarios e perfis |
| `usuarios_restaurantes` | Permissao por restaurante |
| `usuarios_restaurantes_operacoes` | Permissao por restaurante/operacao |
| `usuarios_onboarding` | Estado do tutorial da hostess |
| `sessoes_ativas` | Sessao unica opcional |

### Auditoria e LGPD

| Tabela | Papel |
| --- | --- |
| `auditoria` | Eventos operacionais e seguranca |
| `lgpd_config` | Configuracao LGPD |
| `lgpd_eventos` | Eventos LGPD |
| `lgpd_incidentes` | Incidentes |
| `lgpd_retencao_politicas` | Politicas de retencao |
| `lgpd_solicitacoes` | Solicitacoes de titulares |

## Migrations existentes

### `migration_v2_1_lgpd.sql`

Cria:

- `lgpd_config`
- `lgpd_solicitacoes`
- `lgpd_incidentes`
- `lgpd_retencao_politicas`
- `lgpd_eventos`

Observacao: essas tabelas ja aparecem incorporadas em `schema_v2_1_final.sql`.

### `migration_v2_1_security_hardening.sql`

Cria:

- `sessoes_ativas`

Tambem remove sessoes antigas.

Uso no codigo:

- `Auth::enforceSingleSession()`
- `Auth::upsertSessionRegistry()`

### `migration_v2_1_users_email_non_unique.sql`

Altera:

- Remove indice unico de `usuarios.email`, se existir.
- Cria indice comum `idx_usuarios_email`, se necessario.

Leitura de produto: permite manter usuarios historicos ou inativos com o mesmo e-mail, dependendo do fluxo operacional.

### `migration_v2_2_reservas_tematicas_lote_chd.sql`

Cria:

- `reservas_tematicas_grupos`
- `reservas_tematicas_chd`

Adiciona em `reservas_tematicas`:

- `grupo_id`
- `pax_adulto`
- `pax_chd`
- `qtd_chd`

Tambem cria indices e FK para grupos.

### `migration_v2_3_titular_nome.sql`

Adiciona:

- `reservas_tematicas.titular_nome`

Tambem tenta preencher o titular a partir do responsavel do grupo quando aplicavel.

### `migration_v2_3_grupo_nome.sql`

Adiciona:

- `reservas_tematicas.grupo_nome`

Tambem cria:

- `idx_reservas_tematicas_grupo_nome`

E tenta popular `grupo_nome` a partir de `reservas_tematicas_grupos.responsavel_nome`.

## Fallbacks de schema no codigo

O codigo ainda verifica dinamicamente se algumas colunas/tabelas existem. Esses fallbacks permitem rodar em bancos antigos.

### `ReservaTematicaModel`

Verifica:

- `reservas_tematicas.pax_real`
- `reservas_tematicas.titular_nome`
- `reservas_tematicas.grupo_id`
- `reservas_tematicas.pax_adulto`
- `reservas_tematicas.pax_chd`
- `reservas_tematicas.qtd_chd`
- `reservas_tematicas.grupo_nome`
- tabela `reservas_tematicas_grupos`
- tabela `reservas_tematicas_chd`

Status no banco real:

- Todas existem.

Leitura: esses fallbacks hoje sao compatibilidade historica, nao necessidade para o banco atual.

### `ReservaTematicaConfigModel`

Verifica:

- `reservas_tematicas_config.auto_cancel_no_show_min`

Status no banco real importado originalmente:

- Nao existe.

Status local apos saneamento:

- Existe, criada por `migration_v2_4_auto_no_show_min.sql`.

Comportamento atual:

- O model retorna `0 AS auto_cancel_no_show_min`.
- `updateConfig()` ignora a coluna quando ela nao existe.

Leitura: a feature de tolerancia configuravel para auto no-show esta preparada no codigo, mas nao esta entregue pelo schema atual. Na pratica, auto no-show configuravel fica desativado/zero enquanto essa coluna nao existir.

### `DailyReportEmailModel`

Verifica:

- `relatorio_email_destinatarios.receber_anexo_vouchers`

Status no banco real:

- Existe.

### `UserRestaurantOperationModel`

Verifica:

- tabela `usuarios_restaurantes_operacoes`

Status no banco real:

- Existe.

### `LgpdModel`

Verifica existencia de tabelas dinamicamente para aplicar retencao.

Status no banco real:

- Tabelas LGPD existem, mas algumas estao vazias no dump local.

## Ponto especifico: auto no-show configuravel

O controller e o model indicam intencao de configurar tolerancia por restaurante:

- Campo esperado: `reservas_tematicas_config.auto_cancel_no_show_min`
- Uso: definir minutos apos o horario do turno para marcar `Nao compareceu`.

Mas:

- A coluna nao existe no banco real.
- A coluna nao aparece nos SQLs/migrations da release.
- O model tem fallback para `0`.

Conclusao original:

- Nao e bug fatal.
- E uma feature incompleta ou planejada.
- Se quisermos ativar isso de verdade, precisa de migration explicita.

Migration criada:

```sql
ALTER TABLE reservas_tematicas_config
ADD COLUMN auto_cancel_no_show_min INT NOT NULL DEFAULT 0 AFTER ativo;
```

Ela foi escrita de forma idempotente usando `information_schema`, para poder ser executada mais de uma vez.

## Schema alvo recomendado

Para novos ambientes locais ou VPS, a ordem segura e:

1. Importar `schema_v2_1_final.sql`.
2. Aplicar `migration_v2_1_security_hardening.sql`.
3. Aplicar `migration_v2_1_users_email_non_unique.sql`.
4. Aplicar `migration_v2_2_reservas_tematicas_lote_chd.sql`.
5. Aplicar `migration_v2_3_titular_nome.sql`.
6. Aplicar `migration_v2_3_grupo_nome.sql`.
7. Aplicar `migration_v2_4_auto_no_show_min.sql`.

Observacao:

- `migration_v2_1_lgpd.sql` e redundante se `schema_v2_1_final.sql` for usado, pois LGPD ja esta no schema final.
- Se partir de schema mais antigo, aplicar `migration_v2_1_lgpd.sql` tambem.

## Pontos de atencao

- `README.md` menciona schemas/migrations antigas que nao existem nessa release, como `schema_v1_1_final.sql` e migrations v2.0.
- `schema_v2_1_final.sql` nao inclui as migrations v2.2/v2.3, entao ele sozinho nao representa o banco real atual.
- O dump real esta alinhado com schema + migrations, mas nao com o schema sozinho.
- Existem fallbacks no codigo que poderiam ser removidos no futuro se o projeto assumir oficialmente esse schema como minimo.
- A coluna `auto_cancel_no_show_min` era o unico fallback funcional que apontava para uma feature ausente no schema; agora existe migration local para ela.

## Recomendacao

Antes de qualquer evolucao maior:

1. Criar um `schema_current.sql` consolidado a partir do banco real atual.
2. Atualizar o README com a ordem correta de instalacao.
3. Aplicar `migration_v2_4_auto_no_show_min.sql` nos ambientes onde a tolerancia configuravel for desejada.
4. Definir o schema minimo suportado pelo codigo.
5. Depois disso, remover fallbacks antigos com mais seguranca.

Leitura estrategica: o banco esta saudavel e consistente. O risco nao e corrupcao estrutural; o risco e documentacao de instalacao desatualizada e codigo mantendo compatibilidade com versoes antigas sem uma linha oficial de corte.
