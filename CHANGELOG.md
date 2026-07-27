# Changelog

## 4.4 - retomada operacional e rastreabilidade

- A rota raiz volta ao login; a pagina publica de agradecimento e seus componentes foram removidos da release.
- Auditoria agora traduz eventos tecnicos para portugues e separa visualmente resultado da acao e responsavel.
- Tentativas de reserva recusadas, aceitas ou recebidas permanecem rastreaveis; a nova rotina de reset preserva a estrutura do resort e o administrador informado, removendo apenas dados operacionais apos backup.
- Novo `schema_v4_0.sql` consolidado e scripts de backup/reset/deploy controlado para a retomada da operacao.

## 4.3 - navegação única (plano 2.0, pilar 1)

- Fim da redundância de menus no mobile: a barra superior deixou de duplicar ações (guia, modo demo, sair, botão "Menu") e passou a ser só contexto de marca (logo + perfil, com link para a home).
- Gaveta única: guia e modo demo migraram para dentro do offcanvas `#mobileMenu`, que já tinha tema, navegação completa e sair. No mobile o único caminho para os secundários é o botão "Mais" da barra inferior; no desktop, o botão "Menu" da topnav abre a mesma gaveta.
- Resultado: um sistema de navegação só (barra inferior + gaveta), em vez de três superfícies. Fecha os três pilares do plano 2.0 (identidade por restaurante, vida nas telas, navegação única).

## 4.2 - vida nas telas com a identidade (plano 2.0, pilar 3)

- Reservar temático (passo 1): os cartões de disponibilidade agora têm a cara de cada restaurante — header colorido com ícone, e as barras de lotação na cor da casa (verde Giardino, roxo IX'u, etc.). Fim da tela "crua".
- Selos de restaurante aplicados nos hubs: Análise (ícone + barra colorida em "PAX por restaurante"; selo na tabela de últimos acessos), Operação (últimos registros), e no check-in temático (faixa de cor lateral + ícone por cartão) — reconhecimento instantâneo da casa.
- Tudo via `restaurante_selo()`/`restaurante_identidade()` (aceitam só o nome), sem mudança de backend; mobile-first, sem overflow.

## 4.1 - identidade por restaurante (plano 2.0, pilar 2)

- Migration `v3_5_identidade_restaurantes`: colunas `cor_hex` e `icone` (Bootstrap Icons) em `restaurantes`, com seed por nome (Corais=azul/bi-water, La Brasa=vermelho/bi-fire, Giardino=verde/bi-flower1, IX'u=roxo/bi-star, áreas=âmbar/bi-gem). Nascem NULL: enquanto vazias, a identidade é derivada do nome.
- `PerfilRestauranteService::identidade()`: devolve cor, ícone e tons derivados (fundo claro + texto escurecido, calculados por mistura RGB), com fallback por nome. Helpers globais `restaurante_identidade()` (cacheado) e `restaurante_selo($rest, 'chip'|'icon'|'dot')` renderizam o selo auto-contido (aceitam a linha do restaurante ou só o nome).
- Componente `.fb-rest-selo`/`.fb-rest-icon`/`.fb-rest-dot` no `components.css`. Selo aplicado à lista de restaurantes ativos do hub Operação (com faixa de cor lateral) e documentado no styleguide.

## 4.0 - identidade Oca Hotels (revisão de marca)

- A identidade "Do mar à mesa" (turquesa/terracota inventados) foi substituída pela marca oficial da rede Oca Hotels, extraída do CSS do site: laranja corporativo `#E67E3C` (marca/logo/destaques), ciano `#15B1C9` (ação/CTA/foco), texto `#252525`, superfícies branco/`#F4F4F3`, fonte **Montserrat**. Convenção: laranja = marca, ciano = ação.
- `tokens.css` reescrito por completo: tokens `--fb-*` na paleta Oca (tema claro Dia + escuro Noite), ponte para as variáveis legadas `ab-`/`ux-`/`ui-`, e o passe plano agora definitivo (gradientes/sombras zerados, raios contidos em 12px).
- Corrigido bug de CSS que anulava os tokens: um comentário continha a sequência de fechamento de comentário, encerrando o cabeçalho antes da hora e descartando o bloco `:root` inteiro (tokens não aplicavam).
- Fonte trocada de Inter para Montserrat; `theme-color` agora laranja Oca.
- Novo shell (caminho A): a sidebar legada deixou de ser renderizada; no desktop entra `partials/topnav.php` — barra superior com marca + abas primárias por perfil (gestão: Operação/Análise/Relatórios; hostess: Registro/Vouchers/Temáticos) + cluster do usuário; os destinos secundários seguem no offcanvas `#mobileMenu`, aberto pelo botão "Menu". Conteúdo centralizado full-width (max 1180px). Mobile mantém a barra superior + navegação inferior. Aba ativa do topo em laranja (marca); abas internas de filtro em ciano (ação); cabeçalhos de hub com título flutuando (sem cartão-herói).
- Smoke test `smoke_fbcontrol.php` atualizado para o shell novo (topnav no lugar de sidebar/topbar).
- Logo, favicon e ícones PWA refeitos na marca Oca: selo laranja `#E67E3C` com talheres (garfo + faca) brancos, wordmark FB laranja + Control em tinta/claro; ícones PNG 192/512/180 gerados full-bleed (maskable). `theme-color` e `background_color` do manifest alinhados (laranja + `#F4F4F3`).
- Filtros em chips + folha inferior (`fb-filters.js`, enhancement progressivo): cada `form[data-fb-filters]` (hubs Análise/Relatórios/Operação temática) vira, no mobile, uma barra de chips com o resumo dos filtros ativos + botão "Filtros · N" que abre o formulário como folha inferior (backdrop, alça, fecha por Aplicar/Escape/toque fora); no desktop o formulário segue inline. Sem JS, o formulário aparece normalmente; nenhuma mudança de backend.
- Revisão de marca Oca concluída: identidade, shell de abas, logo/ícones e filtros em chips entregues e verificados.
- Validação no shell novo: as 4 telas temáticas (check-in, reserva em 2 passos, calendário) e as 14 telas legadas (conferência, ambientes completos, CRUDs, auditoria, LGPD, e-mail, registro, vouchers, turnos) renderizam sem erro PHP, com o topnav e sem overflow horizontal (inclusive as pesadas: usuários, conferência). As telas legadas já vestem a marca Oca pela ponte de tokens; a migração do interior delas para os componentes `.fb-*` fica como polimento incremental por demanda.

## 3.9.1 - passe plano (correção pós-remake)

- O resultado visual divergia dos mockups aprovados: a ponte de tokens recoloria o app, mas três camadas legadas de "revamp" com `!important` ("iOS-like refresh", "UI Revamp v3 Premium SaaS", gradientes de body) mantinham o look inflado — sombras grandes, cantos de 20–24px, badges em gradiente, controles gigantes.
- Novo "passe plano" ao final de `tokens.css`: re-aponta também as variáveis `--ui-*` do revamp legado, mata gradientes de fundo, zera sombras, contém raios em 12–16px, densifica controles (`fb-input` 42px, chips 32px), pesos tipográficos 600 e badges chapados na paleta de status.
- Cabeçalhos dos hubs (Operação, Análise, Relatórios, Temáticos) viraram títulos flutuando no fundo, como nos mockups — sem cartão-herói.
- Diferença estrutural restante e assumida: no desktop o shell mantém a sidebar (os mockups usavam barra superior com abas); troca do shell fica como evolução opcional.

## 3.9 - faxina final (remake visual, etapa 9 — encerramento do remake)

- Removidas as 4 views órfãs deixadas pela consolidação (`dashboard/general.php`, `kpis/index.php`, `relatorios_tematicos/index.php`, `control/index.php`); as rotas correspondentes seguem vivas como redirects.
- Filtro de relatórios temáticos unificado em `TematicAccessService::lerFiltrosRelatorio` (antes duplicado entre o hub Análise e a exportação).
- CSS legado deixou de ser inline: `style_global.php` (~116KB embutidos em toda requisição) agora gera o arquivo estático cacheável `assets/css/legacy.css` via `tools/build_legacy_css.php`; os blocos de paleta por tema e cores de link sombreados pelo `tokens.css` foram removidos do legado (zero mudança visual, validada por cores computadas ao vivo).
- Check de segurança `kpi_view_uses_safe_json` re-apontado para `analise/_kpis.php`.
- README atualizado para a arquitetura de hubs; nova referência da identidade em `docs/FBCONTROL_IDENTIDADE_VISUAL.md`.
- Suíte de validação: 17 telas (hubs, temáticos novo/legado, hostess, CRUDs, styleguide, auditoria) renderizando sem warnings.

## 3.8 - PWA e acabamento (remake visual, etapa 8)

- FBControl instalável como app: `manifest.webmanifest` (nome, tela cheia standalone, cores da identidade) + ícones PNG 192/512 gerados do símbolo (fundo turquesa full-bleed, compatível com máscara) + `apple-touch-icon`.
- Service worker mínimo (`sw.js`) apenas para habilitar a instalação — sem cache de dados operacionais, de propósito; fila offline fica para fase futura.
- `theme-color` turquesa Galés na barra do navegador; metas Apple para tela cheia no iOS.
- Microtexto com voz de salão no login: saudação por horário ("Bom dia/Boa tarde/Boa noite, equipe A&B").

## 3.7.2 - Config. Temáticas como calendário (remake visual, etapa 7, aba Configurar)

- `reservasTematicas/admin` virou o calendário por restaurante: cada dia mostra seu estado (aberto, capacidade especial, bloqueado, aberto por exceção, fechamento semanal) e o painel do dia concentra capacidade por turno, bloqueio/reabertura com motivo obrigatório e fechamento de 7 dias — postando nas mesmas actions de antes (`config_capacidade_data`, `bloqueio_data`, `bloqueio_semana`, `bloqueio_semanal`).
- Fechamento semanal como fileira de dias tocáveis (seg–dom) por restaurante.
- Capacidade padrão, gestão de turnos e janelas de reserva da hostess seguem no ambiente completo, preservado em `reservasTematicas/adminCompleta`.
- Com isso a etapa 7 fecha: as três áreas do módulo temático (Reservar, Operar, Configurar) remontadas mobile-first com os ambientes legados preservados em rotas próprias.

## 3.7.1 - Nova reserva em 2 passos (remake visual, etapa 7, aba Reservar)

- `reservasTematicas/reservas` virou o fluxo de criação em 2 passos: no passo 1 a disponibilidade é a interface (turnos por restaurante com lugares livres, barra de ocupação e estados fechado/esgotado); no passo 2, o formulário com UH em destaque, stepper de PAX, marcadores rápidos, grupo/lote com linhas dinâmicas e toggle de pré-reserva (papéis privilegiados).
- Mesmos comandos do backend (`create`, `create_batch`, `create_pre_reservation` no `CriarReservaService`), mesmas validações de capacidade/duplicidade/excedente e mesma trilha de tentativas.
- O passo 2 lista o que já está reservado no turno; turno esgotado avisa que a reserva exigirá excedente autorizado; hostess fora da janela vê o aviso e os envios ficam desabilitados.
- O ambiente legado completo (mapa de reservas, cadastro assistido e edição supervisionada) foi preservado em `reservasTematicas/reservasCompleta`; a edição (`&edit=`) redireciona automaticamente para lá.

## 3.7 - Operação Temática como check-in (remake visual, etapa 7, aba Operar)

- `reservasTematicas/operacao` virou a tela de check-in mobile-first: fila de cartões por reserva com confirmação de PAX inline (`quick_status`/`finalizar`), ações "Não compareceu"/"Cancelar" com confirmação, chips de status com contagem funcionando como filtro, barra de lotação do turno (PAX ativos × capacidade configurada) e botão "Fechar turno" para a gestão.
- Pré-reservas aparecem como "UH pendente" com orientação de vincular a UH pela conferência; cartões finalizados mostram o PAX real e ficam esmaecidos.
- O ambiente completo de conferência e impressão foi preservado integralmente na nova rota `reservasTematicas/conferencia` (mesma action de POST parametrizada no controller — `OperarReservaService` e validações intactos), acessível pelo botão no topo do check-in.
- Controller passa a informar `capacidade_turno` (via `ReservaTematicaConfigModel::turnosConfigForDate`) quando restaurante e turno estão selecionados.

## 3.6 - hub Operação (remake visual, etapa 6)

- Nova rota `operacao/index`: monitor do dia em tempo real (mesmo motor do Centro de Controle, `ControlDashboardService`), com métricas de PAX/registros/turnos/alertas, restaurantes ativos com drill-down para o dashboard do restaurante, turnos abertos e últimos registros paginados. Auto-refresh a cada 60s na primeira página, apenas com a aba visível.
- `control/index` virou redirect para o hub, preservando a paginação; o encerramento automático de turnos vencidos continua acontecendo ao abrir o monitor.
- Menus: "Centro de Controle" renomeado para "Operação"; "Dashboard do Restaurante" saiu do menu (agora é drill-down dos hubs). Navegação inferior da gestão passou a ser Operação · Análise · Relatórios · Mais.
- Consolidação 8→3 concluída na navegação: a seção Gestão e BI tem agora Operação, Análise e Relatórios (+ Auditoria/LGPD como governança).

## 3.5.1 - hub Análise, parte 2 (remake visual, etapa 5)

- Abas Temáticos e KPIs internalizadas no hub `analise/index`, cada uma com seu filtro próprio (Temáticos: restaurante temático, turno, status da reserva, grupo e busca; KPIs: recorte operacional com registro de ocupação diária).
- Aba Temáticos: métricas de reservas/PAX/comparecimento/no-show, distribuição por restaurante e por turno, resumo diário e lista paginada de reservas, com exportação CSV/XLSX pelo endpoint legado (inalterado).
- Aba KPIs: índice de qualidade, mix por operação/restaurante, ranking de operadores, insights gerenciais e o painel Ocupação × PAX buffet com o formulário de lançamento diário (mesma action `kpis/saveOcupacao`).
- `kpis/index` e `relatoriosTematicos/index` viraram redirects para as abas correspondentes, preservando filtros; itens "KPIs Estratégicos" e "Relatórios Temáticos" saíram dos menus. Views `kpis/index.php` e `relatorios_tematicos/index.php` ficaram órfãs (remoção na faxina final).

## 3.5 - hub Análise, parte 1 (remake visual, etapa 5)

- Nova rota `analise/index`: hub de análise com filtro global e abas. A aba "Visão geral" absorve o Dashboard Geral com o mesmo contrato de dados (`DashboardOperacionalService::montarDashboardGeral`): métricas de PAX/acessos/alertas/exceções, fluxo por horário em gráfico de barras, PAX por operação e por restaurante (com drill-down para o dashboard do restaurante) e últimos acessos.
- `dashboard/index` virou redirect permanente para o hub, preservando o filtro da URL; a home da gerência passa a cair no hub. `dashboard/restaurant` (drill-down) permanece.
- Abas "Temáticos" e "KPIs" apontam para as telas legadas até a parte 2 internalizá-las; menus (sidebar, mobile e navegação inferior) renomeados de "Dashboard Geral" para "Análise".
- A view `dashboard/general.php` ficou sem referências e será removida na faxina final, após o período de convivência.

## 3.4 - hub Relatórios (remake visual, etapa 4)

- A rota `relatorios/index` virou o hub de relatórios: filtro único do recorte + 6 cartões de exportação (consolidado, base BI, mapa diário por UH, refeições de colaborador, vouchers com PDFs, reservas temáticas) apontando para os mesmos endpoints de exportação de antes — conteúdo dos arquivos inalterado.
- A consulta em tela (jornada de UH, mapa diário, BI paginado, colaboradores e vouchers) foi preservada integralmente na nova rota `relatorios/consulta`, acessível pelo botão "Consultar em tela" do hub.
- Seção "Envios automáticos" no hub (admin): status, horário e destinatários do E-mail Diário, com atalho para a gestão completa. O item "E-mail Diário" saiu do menu lateral — a rota `emailRelatorios/index` continua ativa como tela de gestão.
- Cartões exibem a contagem de registros do recorte antes de exportar.

## 3.3 - telas de operação mobile-first (remake visual, etapa 3)

- Nova navegação inferior fixa no mobile (`partials/bottom_nav.php`): 3 destinos por perfil + "Mais" (abre o menu completo). Hostess: Registro, Vouchers/Meus turnos, Temáticos; gestão: Registro, Dashboard, Relatórios.
- Hierarquia única de ação: todos os CTAs principais (`Registrar`, `Iniciar turno`, `Registrar voucher`) migrados para `.fb-btn--primary` (terracota, 52px); ponte global re-estiliza `.btn-primary`/`.btn-success`/outlines e badges contextuais do Bootstrap nas telas ainda não migradas.
- Registro: campo de UH com destaque grande (`fb-input--big`) mantendo teclado numérico; badges de status dos últimos acessos alinhados à semântica da identidade (duplicado=vermelho Pitanga, fora de horário=âmbar Manga, múltiplo=roxo Jambo).
- Meus turnos: badges de status migrados para os componentes novos.

## 3.2 - fundação visual (remake visual, etapa 2)

- Nova identidade "Do mar à mesa": logotipo do prato com oca e ondas (claro/escuro) e favicon, nos mesmos arquivos de `public/assets/`.
- Novo `tokens.css`: tokens canônicos `--fb-*` da identidade (paleta Maragogi) por tema — Dia (areia), Modo Jantar (escuro), Entardecer (sand) e Galés (ocean) — mais a ponte que re-aponta as variáveis legadas `--ab-*`/`--ux-*`; o app inteiro veste a identidade sem alteração de views.
- Novo `components.css`: biblioteca mobile-first do remake (botões, métricas, chips, badges de status, formulários, stepper, listas, tabela-cartão, navegação inferior, bottom sheet, estado vazio, toast).
- Tipografia trocada de Manrope/Space Grotesk para Inter (estilo Roboto), com fallback para a fonte do sistema.
- Nova rota `styleguide/index` (apenas admin): galeria de validação dos tokens e componentes.

## 3.1 - limpeza estrutural (remake visual, etapa 1)

- Removida a view morta `app/views/dashboard/index.php` (nunca renderizada; a rota `dashboard/index` usa `dashboard/general.php`).
- Filtros operacionais unificados em `FiltroOperacionalService`; a lista `STATUS_FILTERS` deixa de ser triplicada entre dashboard, KPIs e relatórios.
- Novo `PerfilRestauranteService` + migration `v3_4_perfil_operacional_restaurantes`: modo temático e recorte de operações viram flags no cadastro (`restaurantes.modo_tematico`, `permite_filtro_operacao`, `filtro_operacao_grupo`, `operacoes.tematica`), com fallback automático para as regras históricas por nome enquanto as flags estiverem NULL.
- Identificações por nome no `TurnosController` e no `layout_context` passam a usar `TematicAccessService`.

## 3.0 - pré-reservas e edição supervisionada de UH

- Adicionada pré-reserva sem UH visível para supervisão, gerência e administração.
- Pré-reservas usam identificação técnica interna, aparecem como `UH pendente` e ocupam a capacidade do turno.
- Ao vincular uma UH real, a pré-reserva passa automaticamente para `Reservada`.
- Gerência, supervisão e administração podem corrigir a UH pelo módulo de reservas ou pelo popup da operação temática.
- Alterações de UH validam limite de PAX e duplicidade e ficam registradas na auditoria.
- Hostess permanece impedida de criar pré-reserva, mas pode corrigir a UH das reservas de sua própria autoria, sempre com auditoria.

## 3.0 - correção da faixa histórica de UHs 300

- Restaurado o intervalo operacional `300–319`, comprovado pelo histórico de acessos e reservas.
- Mantidas bloqueadas as lacunas `320–399`, evitando liberar UHs sem confirmação operacional.
- Adicionados testes de cadastro completos para as UHs `306` e `308`.

## 3.0 - correção do cadastro de reservas temáticas

- Mensagens de UH inválida agora exibem o número informado e, em grupos, a linha correspondente.
- Validações de PAX, CHD, limite e duplicidade identificam a UH envolvida.
- Criação e edição de reservas individuais passaram a ser atômicas, evitando registros parciais.
- Falhas inesperadas de persistência geram diagnóstico seguro no log e retorno operacional contextualizado.
- Adicionado teste transacional do fluxo completo de cadastro individual e em grupo.

## FBControl 3.0

Release de polimento, escala e governanca.

### Principais entregas

- Redesign visual e responsivo dos modulos administrativos e tematicos.
- Logo e identidade visual renovadas para FBControl.
- Modulo de reservas tematicas redesenhado para cadastro, operacao, administracao e relatorios.
- Bloqueios de restaurante tematico por data e por dia da semana.
- Fechamento tematico por periodo de sete dias com abertura pontual de excecao.
- Disponibilidade tematica atualizada automaticamente ao trocar a data, sem reutilizar cache antigo.
- Redirecionamento pos-login alinhado ao perfil para evitar chegada indevida em pagina de acesso negado.
- Validacao de UH alinhada aos blocos oficiais, com autorreparo de unidades validas ausentes como a UH 3200.
- Modo demonstracao para treinamento, restrito a administradores.
- Permissoes ajustadas para gerente A&B em modulos operacionais e administrativos relevantes.
- Usuarios com abas de ativos/desativados e melhor experiencia mobile.
- Relatorios e BI com exportacoes mais eficientes e paginacao server-side onde necessario.
- Exportacoes Excel profissionalizadas com workbook real, cabecalho visual, metadados e fallback compativel.
- Impressao operacional tematica redesenhada para leitura rapida por turno, PAX e observacoes.
- Filtros de dashboard/BI com preservacao de posicao de tela em fluxos extensos.
- Regras de fechamento automatico de turnos regulares e especiais.
- No-show automatico para reservas tematicas via cron.
- Upload de voucher reforcado, com limite operacional maior e compactacao quando possivel.
- LGPD revisada: aviso publico mais claro, retencao automatica restrita a tabelas permitidas e documentacao operacional alinhada.

### Performance

- Consultas criticas passaram a evitar `DATE(coluna)` em filtros indexaveis.
- Adicionados indices de apoio em auditoria, vouchers, turnos, acessos especiais, logs tematicos e refeicoes de colaboradores.
- Exports grandes passaram a usar streaming de CSV em rotas criticas.
- Exportacoes de acessos, vouchers, refeicoes de colaboradores e reservas tematicas passaram de `OFFSET` para cursor.
- Adicionados indices compostos para filtros de acessos e consultas de reservas tematicas.
- Incluida checagem CLI de indices, planos `EXPLAIN` e integridade das exportacoes em lotes.

### Seguranca

- Redirecionamentos locais sanitizados.
- Nomes de arquivos de download sanitizados.
- Upload de foto de perfil e voucher com validacoes reforcadas.
- Uso de `HTTP_HOST` removido de fallback sensivel de e-mail.
- Scanner SAST local ampliado para casos de host header e request URI.
- Auditoria passou a redigir senhas, tokens e outros segredos de forma recursiva.
- Adicionada ferramenta de saneamento de payloads historicos da auditoria.
- Eventos de seguranca anteriores ao login podem registrar `usuario_id` nulo.
- Sessoes autenticadas passam a revalidar periodicamente se o usuario continua ativo e com o mesmo perfil.
- Dados PHP incorporados em JavaScript usam codificacao segura para impedir encerramento de tag e XSS armazenado.
- Anexos de vouchers deixaram de ser servidos por URL publica direta e passam por rota autenticada.
- Caminhos de fotos e vouchers sao aceitos apenas dentro das categorias e extensoes de upload permitidas.
- Protecao contra clickjacking foi endurecida para impedir enquadramento da aplicacao.
- Testes automatizados cobrem JSON em contexto de script, URLs de upload, redirecionamentos e sessao desativada.
- Eventos internos de LGPD passaram a minimizar documentos, e-mails e textos livres.
- Formularios rapidos do modulo LGPD deixam de embutir dados pessoais em campos ocultos.
- Exportacao tabular de vouchers passa a indicar apenas se existe anexo, sem expor o caminho tecnico do arquivo.
- Adicionada ferramenta para sanear eventos LGPD historicos.
- Adicionados testes isolados para duplicidade, multiplo acesso, capacidade, permissoes e encerramento automatico.
- Regras tematicas e encerramento automatico passaram a usar services compartilhados.
- Scripts e estilos globais foram separados dos wrappers de layout.

### Operacao

- Healthcheck operacional CLI.
- Checagem de contexto de banco para evitar confusao entre bases locais.
- Runner unico multiplataforma para lint, smoke, contexto, higiene, healthcheck e SAST.
- Builder de pacote limpo, excluindo config local, uploads reais, backups e artefatos temporarios.

### Observacoes

- O banco local correto para validacao com dados importados e `controle_ab_vps`.
- O banco `controle_ab` pode existir como base antiga/de teste e nao deve ser usado para validar credenciais ou dados operacionais.
- Ha um alerta conhecido de e-mail ativo repetido para `hostessoca@gmail.com`; isso deve ser saneado na governanca de usuarios.
