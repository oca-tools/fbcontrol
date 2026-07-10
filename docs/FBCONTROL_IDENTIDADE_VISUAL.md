# FBControl — Identidade visual "Do mar à mesa"

Referência da identidade e da arquitetura visual criadas no remake de 2026 (etapas 1–9, CHANGELOG 3.1–3.9).

## Conceito

Três fontes, uma história: as **Galés de Maragogi** (turquesa = identidade e navegação), a **Oca** (terracota = cor de ação e apetite) e o **prato** (F&B). O símbolo é um prato visto de cima com a cúpula da oca sobre as ondas.

## Paleta

| Nome | Hex | Uso |
| --- | --- | --- |
| Turquesa Galés | `#129490` | identidade, navegação, seleção, theme-color |
| Maré Funda | `#0A5C59` | hover/ênfase do turquesa |
| Terracota Oca | `#C25B3F` | ação primária (todo CTA) |
| Areia Doce | `#F6F1E7` | fundo (tema Dia) |
| Tinta do Recife | `#1B3A4B` | texto |

Status operacionais (nomes internos da casa): ok = **Coqueiro** (verde), duplicado = **Pitanga** (vermelho), fora de horário = **Manga** (âmbar), múltiplo = **Jambo** (roxo), day use = **Água** (azul), não informado = **Concha** (cinza).

Temas: `light` = Dia (areia) · `dark` = **Modo Jantar** (mar à noite, para os turnos noturnos) · `sand` = Entardecer · `ocean` = Galés (ação em turquesa).

## Tipografia

**Inter** (estilo Roboto), com fallback `Roboto, 'Segoe UI', system-ui`. Uma família só; hierarquia por peso (600/500/400). Números tabulares (`fb-num`) para UH, PAX e horários.

## Arquitetura CSS

Ordem de carregamento no `<head>` (a ordem é contrato — não trocar):

1. `assets/css/legacy.css` — CSS legado congelado, gerado de `app/views/partials/style_global.php` por `tools/build_legacy_css.php`. Estiliza as telas ainda não migradas; morre junto com elas.
2. `design-system.css`, `layout.css`, `app-modern.css` — camadas legadas anteriores.
3. `components.css` — biblioteca do remake, prefixo `.fb-*` (botões, métricas, chips, badges, formulários, stepper, tabela-cartão, navegação inferior, bottom sheet, estados vazios). Mobile-first; desktop é enhancement ≥992px.
4. `tokens.css` — **por último, de propósito**: define os tokens canônicos `--fb-*` por tema e re-aponta as variáveis legadas `--ab-*`/`--ux-*` (ponte). Também re-estiliza `.btn-primary`/`.btn-success`/outlines e badges contextuais do Bootstrap nas telas legadas.

Regra de ouro: **telas novas usam só `.fb-*` e `--fb-*`**. Para trocar a identidade inteira, basta editar `tokens.css` e os 3 SVGs de `public/assets/` (logo claro/escuro e favicon) + ícones PWA.

## Regras de uso

- Proporção 60·30·10: areia domina, tinta/turquesa estruturam, terracota só na ação primária (um CTA por tela).
- Alvos de toque ≥44px; ação principal na zona do polegar; teclado numérico (`inputmode="numeric"`) em campos de UH.
- Navegação inferior no mobile (`partials/bottom_nav.php`): 3 destinos por perfil + "Mais".
- Voz de salão nos microtextos (saudação por turno, estados vazios como "Salão tranquilo por enquanto").

## Assets

- `public/assets/logo-fbcontrol.svg` / `logo-fbcontrol-dark.svg` (troca automática via `js-theme-logo`)
- `public/assets/favicon-fb-white.svg`
- `public/assets/icon-192.png`, `icon-512.png`, `apple-touch-icon.png` (PWA; full-bleed turquesa, seguro para máscara)
- `public/manifest.webmanifest` + `public/sw.js` (instalação como app; sem cache de dados de propósito)

Nota de produção: sirva `.webmanifest` com MIME `application/manifest+json`.
