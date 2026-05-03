# FBControl - Fluxo da hostess e registro operacional

## Papel do fluxo

O fluxo da hostess e o coracao operacional do FBControl. Ele transforma o controle de entrada dos restaurantes em um processo rastreavel por usuario, turno, restaurante, operacao, UH e PAX.

Na pratica, a hostess usa o sistema para:

- Abrir um turno no restaurante/operacao correta.
- Registrar acessos de hospedes por UH e PAX.
- Tratar excecoes como UH nao informada, day use, duplicidade e fora de horario.
- Operar reservas tematicas quando o turno e tematico.
- Encerrar o turno com resumo.
- Consultar seus turnos e desempenho.

## Caminho principal

1. Hostess acessa `/?r=auth/login`.
2. Sistema valida e-mail/senha e aplica controle contra tentativas repetidas.
3. Apos login, se nao ha turno ativo, a tela de Registro entra em modo `Iniciar turno`.
4. Hostess seleciona restaurante, operacao e, quando exigido, porta.
5. Sistema valida permissoes da hostess para aquele restaurante e operacao.
6. Sistema verifica se o turno esta dentro do horario configurado.
7. Hostess confirma checklist e inicia turno.
8. Tela muda para modo `Registro`.
9. Hostess registra UH e PAX.
10. Sistema valida UH, limite de PAX, duplicidade e horario.
11. Hostess acompanha os ultimos acessos na lateral da tela.
12. Ao final, hostess encerra o turno e recebe resumo.

## Login

Controller: `AuthController`

Regras:

- Login usa e-mail e senha.
- Existe bloqueio por IP + e-mail apos falhas repetidas.
- Limite atual: 5 tentativas em janela de 15 minutos.
- Backoff pode chegar a 30 minutos.
- Sucesso e falha sao registrados em `SecurityLogModel`.
- Logout exige POST e tambem registra evento de seguranca.

Leitura de produto: o login ja foi pensado para operacao real, onde tablet/computador compartilhado pode sofrer tentativa repetida ou uso indevido.

## Inicio de turno

Controllers relacionados:

- `AccessController::index`
- `AccessController::start`
- `TurnosController::start`

Model principal:

- `ShiftModel`

Tela:

- `app/views/access/index.php`

Regras principais:

- Apenas `admin`, `supervisor` e `hostess` podem iniciar turno.
- Se o usuario ja tem turno ativo, ele vai direto para registro.
- Hostess so ve restaurantes vinculados a ela.
- Hostess pode ter operacoes especificas permitidas por restaurante.
- Porta so aparece quando o restaurante tem portas cadastradas.
- Se restaurante exige porta, o sistema bloqueia inicio sem porta.
- Se a operacao esta fora do horario, o sistema pede confirmacao extra.
- Antes de iniciar, ha popup de confirmacao com usuario, restaurante, operacao e porta.
- O sistema salva rascunho local da selecao de turno para evitar retrabalho.

Horarios configurados no banco local:

| Restaurante | Operacao | Inicio | Fim | Tolerancia |
| --- | --- | --- | --- | --- |
| Restaurante Corais | Cafe da Manha | 06:30 | 10:00 | 15 min |
| Restaurante Corais | Almoco | 12:00 | 15:00 | 15 min |
| Restaurante Corais | Jantar | 18:30 | 22:00 | 15 min |
| Restaurante Giardino | Tematico | 19:00 | 22:00 | 15 min |
| Restaurante IX'u | Tematico | 19:00 | 22:00 | 15 min |
| Restaurante La Brasa | Almoco | 12:00 | 15:00 | 15 min |
| Restaurante La Brasa | Tematico | 19:00 | 22:00 | 15 min |
| Privileged | Privileged | 10:00 | 17:00 | 0 min |

Observacao importante: apesar de existir `TurnosController::start`, a tela principal atual usa `AccessController::start` via formulario `/?r=access/start`. O `TurnosController` ainda existe e replica parte das regras. Isso merece consolidacao futura para reduzir duplicidade de logica.

## Registro de acesso

Controller:

- `AccessController::register`

Model:

- `AccessModel`
- `UnitModel`
- `RestaurantOperationModel`

Campos principais:

- UH.
- PAX.
- Restaurante do turno.
- Operacao do turno.
- Porta do turno, quando houver.
- Usuario.
- Turno.

Regras de validacao:

- Precisa haver turno ativo.
- Turno tematico nao usa o registro comum; redireciona para operacao tematica.
- UH e obrigatoria.
- UH precisa existir em `unidades_habitacionais`.
- Se a operacao exige PAX, PAX deve ser maior que zero.
- PAX nao pode exceder a capacidade configurada da UH.
- Soma diaria por UH e operacao tambem respeita limite de PAX da UH.
- Duplicidade imediata identica em ate 2 minutos exige confirmacao.
- Duplicidade operacional em ate 10 minutos e marcada como alerta.
- Fora de horario e marcado no registro.
- UHs tecnicas `998` e `999` nao entram em certas regras de duplicidade.

UHs tecnicas:

- `998`: Nao informado.
- `999`: Day use.

Uso observado no banco:

- UH `998`: 241 registros, 766 PAX.
- UH `999`: 17 registros, 58 PAX.

Leitura de produto: o sistema aceita excecao operacional sem quebrar o fluxo. Em vez de forcar a hostess a parar quando nao ha UH, ele registra uma UH tecnica e preserva rastreabilidade.

## Correção rapida

Controller:

- `AccessController::correct_last`

Regra:

- A hostess pode corrigir o PAX do ultimo lançamento dentro de uma janela curta de 2 minutos.
- A correção revalida limite da UH.
- A correção considera o total diario da UH na operacao.
- A alteracao gera auditoria com acao `update_pax_2min`.

Leitura de produto: essa regra resolve erro humano comum de digitação sem abrir espaço amplo para alteração retroativa.

## Encerramento de turno

Controller:

- `TurnosController::end`

Model:

- `ShiftModel`

Regras:

- Apenas usuario autenticado com perfil permitido encerra.
- Se o turno ainda nao chegou ao horario final, o sistema bloqueia encerramento.
- A mensagem informa quantos minutos faltam e a tolerancia.
- Ao encerrar, `fim_em` e gravado no turno.
- Sistema mostra resumo com total de acessos e total de PAX.
- Turno tematico inclui resumo de reservas tematicas.

Cancelamento:

- Turno comum pode ser cancelado apenas se nao houver acessos.
- Turno tematico pode ser cancelado apenas se nao houver alteracoes manuais em reservas desde o inicio.

Autoencerramento:

- `ShiftModel::autoCloseExpired` fecha turnos vencidos.
- So fecha turnos ativos que tenham pelo menos um acesso.
- Considera hora final da operacao, tolerancia e mais 10 minutos de graca.

## Meus turnos

Controller:

- `HostessController::turnos`

Tela:

- `app/views/hostess/turnos.php`

Funcoes:

- Lista ultimos turnos da hostess.
- Mostra quantidade de turnos completos.
- Calcula nivel da hostess:
  - Bronze: padrao.
  - Prata: 10+ turnos.
  - Ouro: 30+ turnos.
  - Platina: 60+ turnos.
- Permite upload de foto de perfil.

Leitura de produto: existe uma camada leve de gamificacao/reconhecimento. Isso pode ajudar na adesao da equipe se for bem trabalhado.

## Operacao tematica dentro do Registro

O sistema detecta turno tematico por restaurante/operacao.

Restaurantes considerados tematicos:

- Giardino.
- La Brasa, quando operacao tambem e tematica.
- IX'u / IXU.

Quando o turno e tematico:

- A tela de registro comum nao aparece.
- A hostess ve uma lista de reservas por data/restaurante/status.
- Acoes possiveis: confirmar ou cancelar/no-show.
- Confirmacao pode registrar PAX real menor que reservado.
- PAX real menor gera observacao de no-show parcial.
- Status finais bloqueiam novas alteracoes comuns.

Leitura de produto: o FBControl trata tematico como fluxo proprio, nao como simples acesso. Isso e correto porque existe reserva, capacidade, turno, no-show e conferencia.

## Dados reais do fluxo classico

No banco local importado:

- 3.950 registros de acesso.
- 67 turnos.
- 48 registros de vinculacao usuario-restaurante.
- 14 usuarios.
- 11 hostess.

Distribuicao de acesso classico:

| Restaurante | Operacao | Registros | PAX | Duplicados | Fora horario |
| --- | --- | ---: | ---: | ---: | ---: |
| Restaurante Corais | Jantar | 3.271 | 8.584 | 43 | 0 |
| Restaurante Corais | Almoco | 370 | 925 | 3 | 0 |
| Restaurante Corais | Cafe da Manha | 285 | 657 | 2 | 0 |
| Privileged | Privileged | 24 | 66 | 0 | 0 |

Leitura:

- O uso real do registro comum esta fortemente concentrado no Corais.
- O jantar e o maior volume operacional.
- Os tematicos aparecem principalmente no modulo de reservas, nao como registros de acesso comum.
- Nao ha registros fora de horario no dump, sinal de aderencia aos horarios ou de uso dentro da janela de tolerancia.

## Ranking operacional observado

| Usuario | Perfil | Turnos | Acessos | PAX |
| --- | --- | ---: | ---: | ---: |
| Cleonice | hostess | 13 | 1.019 | 2.533 |
| Isabelly | hostess | 12 | 824 | 2.348 |
| Melissa | hostess | 14 | 817 | 2.062 |
| Vitoria | supervisor | 11 | 611 | 1.641 |
| Thuany | hostess | 7 | 501 | 1.194 |

Leitura:

- O sistema tem operadoras claramente ativas.
- O fluxo nao e apenas teste: ja registra operacao real.
- O supervisor tambem opera registros, nao apenas acompanha.

## Pontos fortes

- Fluxo coerente com rotina de porta/restaurante.
- Regras reais de hotelaria: UH, PAX, restaurante, operacao, porta e turno.
- Tratamento de excecoes sem travar operacao.
- Duplicidade e fora de horario viram indicadores, nao apenas bloqueios.
- Janela curta de correcao reduz erro humano.
- Autoencerramento reduz turno aberto esquecido.
- Vinculo hostess-restaurante limita acesso operacional.
- Tematicos tem fluxo proprio, mais adequado ao dominio.

## Pontos de atencao

- Ha duplicidade de logica entre `AccessController::start` e `TurnosController::start`.
- Arquivos `.bak` dentro de controllers/models/views aumentam ruido e risco de manutencao.
- Algumas mensagens aparentam mojibake em pontos isolados, por exemplo `?ltimo lançamento`.
- A tela tem muito JavaScript inline, o que dificulta teste e evolucao incremental.
- O fluxo de tematico esta parcialmente acoplado ao fluxo de registro.
- O uso de UHs tecnicas e util, mas precisa estar documentado para gestao e auditoria.

## Recomendacoes proximas

1. Criar documento operacional para hostess com passo a passo curto.
2. Consolidar abertura de turno em um unico controller.
3. Criar usuarios locais de teste por perfil.
4. Testar em navegador o fluxo completo: login, iniciar turno, registrar UH, corrigir PAX, encerrar turno.
5. Mapear o modulo de reservas tematicas em documento separado.
6. Depois mapear dashboards/relatorios com base nos dados reais.
