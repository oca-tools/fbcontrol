# FBControl - Reservas Tematicas

## Papel do modulo

Reservas Tematicas e o segundo grande nucleo operacional do FBControl. Enquanto o fluxo de acessos controla a entrada nos restaurantes por turno, este modulo organiza a jornada de reserva, conferencia, cozinha, excecoes e auditoria dos restaurantes tematicos.

Na pratica, ele resolve quatro dores:

- Evita reserva duplicada para a mesma UH, data, restaurante e turno.
- Controla capacidade por restaurante e por horario de atendimento.
- Da para cozinha e lideranca uma lista operacional limpa, filtravel e imprimivel.
- Registra o que mudou, quem mudou e quando mudou.

Restaurantes tematicos identificados no codigo e banco:

| Restaurante | Papel |
| --- | --- |
| Restaurante Giardino | Tematico |
| Restaurante IX'u | Tematico |
| Restaurante La Brasa | Tematico |

## Jornadas principais

### Jornada 1 - Criacao de reservas

Rota: `/?r=reservasTematicas/reservas`

Controller: `ReservasTematicasController::reservas`

View: `app/views/reservas_tematicas/reservas.php`

Uso esperado:

1. Hostess/admin/supervisor acessa a tela de reservas.
2. Seleciona data, restaurante e turno.
3. Consulta disponibilidade.
4. Informa UH, titular, PAX e CHD quando houver.
5. Pode adicionar tags rapidas de observacao.
6. Sistema valida UH, capacidade, duplicidade e limite de PAX.
7. Reserva nasce com status `Reservada`.

O mesmo ambiente permite criacao em lote. Esse fluxo e importante para grupos, pois varias UHs podem entrar numa mesma operacao, com responsavel comum e agrupamento em `reservas_tematicas_grupos`.

### Jornada 2 - Operacao e conferencia

Rota: `/?r=reservasTematicas/operacao`

Controller: `ReservasTematicasController::operacao`

View: `app/views/reservas_tematicas/operacao.php`

Uso esperado:

1. Operador filtra por data, restaurante, turno, UH, titular, texto livre ou status.
2. Sistema mostra resumo do turno e lista de reservas.
3. Equipe confirma entrada, marca no-show, cancela ou registra divergencia.
4. Quando finaliza, pode informar `pax_real`.
5. Alteracoes relevantes sao gravadas em log.
6. Turno pode ser fechado para impedir alteracoes por hostess.

Essa jornada funciona como ponte entre recepcao, hostess, restaurante e cozinha. E uma tela operacional, nao apenas administrativa.

### Jornada 3 - Impressao

Rota: `/?r=reservasTematicas/print`

Controller: `ReservasTematicasController::print`

View: `app/views/reservas_tematicas/print.php`

Uso esperado:

- Gerar lista detalhada ou resumida para conferencia/cozinha.
- Manter filtros de data, restaurante, turno, UH, titular, status e ordenacao.
- Apoiar operacao offline ou impressa durante o servico.

### Jornada 4 - Configuracao administrativa

Rota: `/?r=reservasTematicas/admin`

Controller: `ReservasTematicasController::admin`

View: `app/views/reservas_tematicas/admin.php`

Uso esperado:

- Configurar capacidade total por restaurante.
- Configurar capacidade por turno.
- Ativar/inativar turnos.
- Criar novos turnos.
- Configurar periodos em que hostess pode criar reservas.
- Definir tolerancia de auto no-show.

Somente `admin` acessa essa tela.

## Permissoes

Regras observadas no controller:

| Area | Quem acessa |
| --- | --- |
| Modulo geral | admin, supervisor, hostess com Corais ou tematico |
| Criacao de reservas | admin, supervisor, hostess com Corais |
| Operacao/conferencia | admin, supervisor, hostess com Corais ou tematico |
| Admin do modulo | admin |

Leitura de produto: existe separacao entre quem cria reservas e quem opera a chegada. Hostess vinculada ao Corais consegue reservar; hostess vinculada aos restaurantes tematicos consegue operar a conferencia.

## Regras de criacao

Validacoes principais:

- Restaurante precisa estar entre os tematicos.
- Turno e obrigatorio.
- UH e obrigatoria e precisa existir em `unidades_habitacionais`.
- Titular e obrigatorio.
- PAX precisa ser maior que zero.
- Quantidade de CHD nao pode exceder PAX.
- Idades de CHD devem ficar entre 0 e 17.
- PAX nao pode exceder limite da UH.
- Nao pode haver reserva duplicada para UH + data + turno + restaurante.
- UHs tecnicas `998` e `999` podem duplicar.
- Capacidade do turno precisa comportar a reserva, salvo excecao autorizada.

Excedente:

- Existe campo `excedente`.
- Existe motivo `excedente_motivo`.
- Existe autor `excedente_autor_id`.
- O sistema registra quando a excecao foi assumida.

Leitura de produto: excedente nao e tratado como erro escondido. Ele e uma excecao operacional assumida e rastreavel.

## Criacao em lote

O lote permite cadastrar varias UHs no mesmo atendimento.

Regras principais:

- Cada UH do lote precisa existir.
- Nao pode repetir UH no mesmo lote.
- Titular pode vir por UH ou por responsavel padrao do lote.
- PAX e CHD sao validados por UH.
- A capacidade e calculada pelo total do lote.
- Quando possivel, o sistema cria um grupo em `reservas_tematicas_grupos`.

Leitura de produto: essa e uma funcionalidade bem aderente a hotelaria, porque familias/grupos frequentemente ocupam mais de uma UH.

## Status

Status canonicos usados pelo modulo:

| Status | Sentido operacional |
| --- | --- |
| Reservada | Reserva ativa, aguardando atendimento |
| Finalizada | Entrada confirmada/concluida |
| Nao compareceu | No-show total ou automatico |
| Cancelada | Reserva cancelada |
| Divergencia | Houve diferenca operacional a tratar |
| Excedente | Reserva acima da capacidade normal |

O controller normaliza alguns valores antigos ou com variacao de acento. Isso indica que o modulo ja passou por ajustes de producao e precisou manter compatibilidade com dados anteriores.

## PAX real e no-show parcial

Na operacao, `pax_real` representa quantas pessoas realmente compareceram.

Regras:

- `pax_real` precisa ficar entre `0` e o PAX reservado.
- Se status for `Finalizada` e `pax_real` vier vazio, o sistema assume PAX reservado.
- Se status for `Nao compareceu` e `pax_real` vier vazio, o sistema assume `0`.
- Se `Finalizada` ou `Nao compareceu` tiver PAX real menor que reservado, o sistema acrescenta observacao de no-show parcial.

Leitura de produto: o sistema nao mede apenas reserva criada; ele tenta medir aderencia entre reservado e realizado.

## Auto no-show

Metodo: `ReservasTematicasController::runAutoNoShow`

Modelo: `ReservaTematicaModel::findAutoNoShowCandidates`

Comportamento:

- Busca reservas ainda `Reservada`.
- Usa data, restaurante e horario do turno.
- Aplica tolerancia configurada em minutos.
- Atualiza para `Nao compareceu`.
- Define `pax_real` como `0`.
- Grava log com acao `auto_no_show`.

Ponto de atencao: no dump local, a tabela `reservas_tematicas_config` tem `capacidade_total`, mas o controller tenta trabalhar tambem com `auto_cancel_no_show_min`. Vale conferir se o schema em producao tem coluna adicional que nao apareceu no dump, ou se o codigo tem fallback/migracao em outro ponto.

## Fechamento de turno

Tabela: `reservas_tematicas_fechamentos`

Regra:

- Um fechamento e unico por restaurante + data + turno.
- Se o turno estiver fechado, hostess nao altera.
- Admin/supervisor podem alterar turno fechado, mas precisam justificar.

Leitura de produto: isso cria um fechamento operacional parecido com caixa/turno, protegendo o historico depois que a operacao termina.

## Auditoria

Tabela: `reservas_tematicas_logs`

Campos principais:

- `reserva_id`
- `acao`
- `usuario_id`
- `dados_antes`
- `dados_depois`
- `justificativa`
- `criado_em`

Acoes encontradas no banco local:

| Acao | Quantidade |
| --- | ---: |
| create | 1560 |
| status | 87 |
| update | 9 |
| update_detail | 3 |

Leitura de produto: a maior parte do uso real e criacao de reservas. Alteracoes de status ainda parecem pouco usadas, ou o dataset foi capturado antes da operacao final do dia.

## Dados reais do dump local

Periodo observado:

| Data | Reservas | PAX |
| --- | ---: | ---: |
| 2026-04-10 | 108 | 283 |
| 2026-04-11 | 91 | 241 |
| 2026-04-12 | 114 | 306 |
| 2026-04-13 | 63 | 191 |
| 2026-04-14 | 109 | 295 |
| 2026-04-15 | 68 | 165 |
| 2026-04-16 | 103 | 288 |
| 2026-04-17 | 90 | 224 |
| 2026-04-18 | 97 | 280 |
| 2026-04-19 | 116 | 320 |
| 2026-04-20 | 58 | 171 |
| 2026-04-21 | 71 | 199 |
| 2026-04-22 | 71 | 194 |
| 2026-04-23 | 72 | 191 |
| 2026-04-24 | 120 | 310 |
| 2026-04-25 | 106 | 286 |
| 2026-04-26 | 103 | 274 |

Resumo geral:

- Reservas tematicas: 1560.
- Logs tematicos: 1659.
- Periodo de dados: 2026-04-10 a 2026-04-26.
- Total de PAX reservado no periodo: 4498.
- Media aproximada: 91,8 reservas/dia e 264,6 PAX/dia.

Usuarios que mais criaram reservas:

| Usuario | Perfil | Reservas | PAX |
| --- | --- | ---: | ---: |
| Isabelly | hostess | 631 | 1670 |
| Vitoria | supervisor | 403 | 1099 |
| Cleonice | hostess | 330 | 916 |
| Administrador | admin | 130 | 354 |
| Melissa | hostess | 66 | 179 |

Turnos ativos:

| Hora | Ativo |
| --- | --- |
| 19:00 | Sim |
| 19:30 | Sim |
| 20:00 | Sim |
| 20:30 | Sim |
| 21:00 | Sim |

Turno `18:30` existe, mas esta inativo no dump local.

Periodos ativos para criacao de reservas:

| Inicio | Fim |
| --- | --- |
| 08:30 | 12:10 |
| 13:00 | 19:00 |

## Tabelas principais

| Tabela | Papel |
| --- | --- |
| `reservas_tematicas` | Reserva individual, status, PAX, UH, restaurante, turno e observacoes |
| `reservas_tematicas_chd` | Idades de criancas vinculadas a reserva |
| `reservas_tematicas_grupos` | Agrupamento de varias UHs em um mesmo atendimento |
| `reservas_tematicas_config` | Configuracao por restaurante |
| `reservas_tematicas_config_turnos` | Capacidade por restaurante e turno |
| `reservas_tematicas_turnos` | Horarios de atendimento |
| `reservas_tematicas_periodos` | Janelas em que reservas podem ser criadas |
| `reservas_tematicas_fechamentos` | Bloqueio de alteracao por restaurante/data/turno |
| `reservas_tematicas_logs` | Auditoria de criacao, status e alteracoes |

## Pontos fortes

- Modelo bem aderente a operacao real de resort/hotelaria.
- Controle de capacidade por turno evita superlotacao sem visibilidade.
- Criacao em lote cobre grupos e familias.
- CHD com idade da mais contexto para cozinha/operacao.
- Excedente e rastreavel, nao apenas permitido silenciosamente.
- Logs guardam antes/depois em JSON.
- Fechamento protege dados apos o turno.
- Impressao atende operacao fisica, que provavelmente ainda e necessaria.

## Pontos de atencao

- O controller concentra muitas responsabilidades em um unico arquivo grande.
- Ha arquivos `.bak` versionados junto dos arquivos ativos.
- Algumas regras de negocio estao diretamente no controller, dificultando testes.
- O schema local pode estar divergente do codigo em relacao a `auto_cancel_no_show_min`.
- Ha sinais de normalizacao de mojibake/status, indicando historico de problemas de encoding.
- O fluxo de operacao parece menos usado que o fluxo de criacao, pelos dados de status/logs.

## Leitura estrategica

O FBControl nao e apenas um "controle de entrada". A proposta mais forte dele e virar uma plataforma operacional de A&B para hotelaria:

- Controle de acesso por restaurante e turno.
- Capacidade e previsao de demanda.
- Reservas tematicas com regras reais de resort.
- Auditoria de excecoes.
- Relatorios para gestao.
- Suporte ao trabalho de hostess, supervisao, cozinha e gerencia.

Dentro desse contexto, Reservas Tematicas e um modulo com muito valor de produto, porque conecta a promessa feita ao hospede com a execucao do restaurante no dia.

