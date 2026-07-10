# FBControl — Conceito visual 2.0 ("Operação viva")

Plano executável para levar a interface de "marca nova por fora, esqueleto antigo por dentro"
a uma experiência de app moderno, mantendo a usabilidade operacional. Documento de referência
para orientar a execução tela a tela.

> Referências visuais ilustradas na subpasta [`referencias/`](./referencias) — abra os `.html` no navegador.
> Os mockups usam Tabler Icons por praticidade; em produção o app usa Bootstrap Icons (`bi-*`) equivalentes.

---

## A) Diagnóstico — o que ainda cheira a legado

A causa raiz é conhecida: no remake, o CSS legado (~5.600 linhas) foi **ponteado** por tokens em vez
de substituído, e o "passe plano" **zerou todas as sombras** para matar o visual inflado antigo — o que
corrigiu demais e deixou tudo "plano e sem vida".

| Sintoma | Causa real (no código) |
|---|---|
| "Plano e sem vida" | Passe plano com `box-shadow: none !important`. App moderno usa elevação sutil, não zero. |
| Componentes genéricos | Metade das telas usa `.card`/`.saas-*`/Bootstrap; só os hubs usam os `.fb-*`. Dois vocabulários. |
| Tabelas com cara de planilha | Tabelas legadas (conferência/CRUDs) são `<table>` Bootstrap cru, sem densidade nem hierarquia. |
| Modais datados | Modais de detalhe são Bootstrap padrão, sem cabeçalho rico. |
| Sem movimento | Não existe sistema de transição/motion. Tudo "seco". |
| Números como texto | KPIs e contagens são texto; falta dataviz (barras, anéis, deltas). |
| Hierarquia fraca | Muito texto solto de peso/cor iguais; pouca distinção primário vs. secundário. |

Resumo: o remake trocou cores/fonte de tudo, mas só reconstruiu a estrutura de ~metade. A outra metade
está pintada, não redesenhada — e falta a camada de "acabamento de app" (elevação, motion, densidade, dataviz).

---

## B) Direção criativa — "Operação viva"

Um conceito guia tudo: **calma na superfície, vida no detalhe.** Cinco princípios inegociáveis:

1. **Elevação com propósito** — 3 níveis de sombra sutis (repouso / hover / flutuante). Profundidade funcional, não decorativa.
2. **Cor com parcimônia** — fundo neutro Oca (`#F4F4F3`), superfícies brancas, tinta escura. Laranja = marca, ciano = ação, cores por restaurante = categoria, status = semântico. Cor sempre **codifica**, nunca decora.
3. **Densidade de app** — alvos de 44px no toque, espaçamento por escala (4/8/12/16/24). Nada de padding aleatório.
4. **Movimento discreto** — 120–200ms em hover/press/entrada de folha/toast. Sempre sob `prefers-reduced-motion: no-preference`.
5. **Dado é visual** — todo número importante ganha acompanhante visual (barra, anel, delta com seta/cor, mini-tendência).

### Tokens da direção (paleta e escalas)

```
Marca (laranja Oca)   #E67E3C   /  hover #C9641F  /  bg #FBEFE6
Ação (ciano Oca)      #15B1C9   /  hover #0E93A8  /  link #0E7C8E
Fundo                 #F4F4F3   ·  superfície #FFFFFF  ·  tinta #252525  ·  muted #7A7A79
Borda hairline        #E2E2E2
Status  ok #2E9E6B/#E6F5EE · warn #C77A12/#FBF0DC · danger #C0433B/#FBE9E7 · info #2E7C9E/#E4F1F7 · multi #6C5CB0/#ECE9F7
Restaurante  Corais #2E7C9E · La Brasa #C0433B · Giardino #4E8B3B · IX'u #6C5CB0 · áreas #B07D2A

Elevação   1: 0 1px 2px /.06   ·   2: 0 4px 14px /.12 (+translateY -2px)   ·   3: 0 12px 32px /.18
Raio       controles 9–10px · cards 12px · pílulas 999px
Espaço     4 · 8 · 12 · 16 · 24 · 32
Motion     dur 120–200ms · ease cubic-bezier(.2,.7,.3,1)
Fonte      Montserrat (400/500/600/700) — pesos 600 para títulos/ênfase
```

---

## C) Plano de melhoria por área

1. **Sidebar / menu / navegação** — rail compacto de ícones no desktop (64px, expande no hover); barra
   inferior nos tablets (paisagem e retrato); FAB de ação primária contextual no mobile/tablet; estado
   ativo em laranja com indicador; transição de 150ms na expansão. Ref.: `01-navegacao-responsiva.html`.
2. **Header / topbar** — sem cartão-herói. Padrão: `label` (uppercase 11px) + `título` (600) + subtítulo
   + ação primária à direita. Em telas com dados, linha de resumo (3–4 métricas) sob o título. Breadcrumb
   só em detalhe (Reserva → Turno → UH). Ref.: `05-header-resumo-listagem.html`.
3. **Cards** — elevação: repouso `0 1px 2px`, hover `0 4px 14px` + `translateY(-2px)` em 150ms. Estrutura
   fixa: cabeçalho (título + ação/badge), corpo, rodapé opcional. Faixa de cor lateral quando pertence a
   uma entidade. Raio 12px, borda hairline. Ref.: `03-elevacao-cards.html`.
4. **Badges** — três famílias: **status** (semântico, tint+texto), **contagem** (pílula numérica neutra),
   **identidade** (restaurante, com ícone). Nunca gradiente. 12px, peso 600. Ref.: `04-componentes-base.html`.
5. **Botões** — hierarquia rígida: 1 primário por tela (ciano cheio), secundário (contorno), fantasma
   (texto), perigo (vermelho tint). Estados hover/active(`scale .98`)/disabled(opacity .5)/loading(spinner).
   Ícone à esquerda, 44px de alvo. Ref.: `04-componentes-base.html`.
6. **Tabelas / listagens** — aposentar a `<table>` planilha. Padrão "linha-registro": densa, informação-chave
   em 600, resto secundário; hover sutil; no mobile já vira cartão (estender o `.fb-table` às telas legadas).
   Ref.: `05-header-resumo-listagem.html`.
7. **Reservas temáticas** — PAX/CHD viram badges com ícone (adultos/CHD/idade); ocupação e excedente viram
   alerta visual; status e observações com hierarquia; "Ver detalhes" abre painel estruturado (bottom sheet
   no mobile). Ref.: `02-reserva-detalhe.html`.
8. **Modais e detalhes** — abandonar o modal Bootstrap cru. Mobile: bottom sheet (`.fb-sheet`); desktop:
   painel lateral ou modal com cabeçalho rico (identidade + status + ações no topo, corpo em seções).
   "Ver detalhes" nunca abre alerta de texto. Ref.: `02-reserva-detalhe.html`.
9. **Formulários** — labels 12px acima do campo, campos 44px, foco com anel ciano, agrupamento por seção,
   ação fixa no rodapé (sticky no mobile). Steppers para PAX/CHD, toggles para binários, chips para múltipla
   escolha curta. Ref.: `04-componentes-base.html`.
10. **Alertas e feedbacks** — **inline** (contextual, tint + ícone), **toast** (ação concluída, 3s),
    **banner** (estado persistente). Todo POST bem-sucedido → toast; todo erro → inline no ponto do problema.
    Ref.: `06-feedbacks-estados.html`.
11. **Mobile / tablet** — mobile-first com breakpoints reais (não só encolher desktop). Alvos 44px, FAB,
    bottom sheet, arrastar folha para fechar. Testar em 360/390/768/1024. Ref.: `01-navegacao-responsiva.html`.
12. **Estados vazios / loading / erro** — vazio = convite (ícone + título + 1 CTA); loading = skeleton com
    a forma do conteúdo; erro = o que houve + como resolver, sem stack trace. Ref.: `06-feedbacks-estados.html`.

---

## D) Recomendações de implementação (seguro, por etapas)

1. **Fechar o design system nos tokens** — adicionar ao `tokens.css`: escala de elevação (`--fb-shadow-1/2/3`),
   de espaço (`--fb-space-*`) e de motion (`--fb-ease`, `--fb-dur`). **Reverter o `box-shadow: none !important`
   global para elevação sutil nos `.fb-card`.** (Maior alavanca isolada — 1 mudança, ~80% da sensação "morta".)
2. **Um vocabulário só** — migrar as telas legadas (conferência, ambientes completos, CRUDs) dos `.card`/`.saas-*`
   para os `.fb-*`, tela a tela, validando cada uma. É o "outro 50%".
3. **Componentizar o que falta** — `fb-modal`/`fb-sheet` unificado, `fb-table` estendido, `fb-skeleton`,
   `fb-toast` (base já existe), `fb-fab`, `fb-summary-bar`. Zero estilo inline repetido.
4. **Motion como camada** — um `motion.css` com transições de hover/press/entrada, dentro de
   `@media (prefers-reduced-motion: no-preference)`.
5. **Refatorar por etapas e validar** — cada tela crítica (registro, check-in, análise, reserva) validada em
   360/768/1024/1280 antes de seguir. Sem big-bang.
6. **Matar o CSS legado gradualmente** — a cada tela migrada para `.fb-*`, remover o trecho correspondente do
   `legacy.css`. Meta real: `legacy.css` chegar a zero.

---

## E) Checklist de aceite — "virou app?"

- [ ] Nenhuma tela usa `.card`/`.saas-*`/`<table>` Bootstrap cru — só `.fb-*`
- [ ] Cards têm elevação em repouso e reagem ao hover/press
- [ ] Existe 1 ação primária clara por tela (e um FAB contextual no mobile/tablet)
- [ ] Todo número importante tem acompanhante visual (barra/anel/delta)
- [ ] Nenhuma tabela parece planilha; no mobile viram cartões
- [ ] "Ver detalhes" abre painel/bottom sheet estruturado, nunca alerta de texto
- [ ] Todo POST dá feedback (toast de sucesso / inline de erro)
- [ ] Loadings são skeletons; vazios são convites com CTA
- [ ] Navegação coerente e testada em 360/768/1024/1280 (rail no desktop, bottom nav nos tablets)
- [ ] Reservas temáticas: PAX/CHD/status/excedente/obs são visuais, não texto solto
- [ ] Transições de 120–200ms; respeitam `prefers-reduced-motion`
- [ ] `legacy.css` encolhendo a cada etapa (meta: zero)

---

## Referências visuais

| Arquivo | Cobre |
|---|---|
| `referencias/01-navegacao-responsiva.html` | Navegação desktop → tablet → mobile, FAB |
| `referencias/02-reserva-detalhe.html` | Reservas temáticas: cartão + painel "ver detalhes" |
| `referencias/03-elevacao-cards.html` | Sistema de elevação (3 níveis) + anatomia de card |
| `referencias/04-componentes-base.html` | Botões, badges (3 famílias), campos/stepper |
| `referencias/05-header-resumo-listagem.html` | Header de produto, resumo, listagem moderna |
| `referencias/06-feedbacks-estados.html` | Alertas, toast, vazio, skeleton, erro |
