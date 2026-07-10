# FBControl — Conceito visual 3.0 ("Tinta & Brasa")

Direção vigente. Substitui o 2.0 ("Operação viva"), que tratou "moderno" como neutralidade —
e neutralidade em excesso virou "pálido". O 3.0 corrige o **princípio**, não só os valores.

> Referências visuais na subpasta [`referencias/`](./referencias) — abra os `.html` no navegador.
> Mockups usam Tabler Icons por praticidade; em produção o app usa Bootstrap Icons (`bi-*`).

---

## Princípio central

**Contraste e cor são informação.** Informação importante tem que ser **grande, escura ou colorida** —
o erro atual é que muita coisa importante está *apagada nas três dimensões ao mesmo tempo*: valor menor
que o rótulo, tudo em cinza médio (`#7A7A79`), caixa baixa, escondido, e CTA ciano sem contraste.

A regra de ouro que resolve o "pálido":

> Todo valor é maior e mais escuro que seu rótulo. Uppercase pequeno é só para rótulo, nunca para dado.
> Cor plena vai onde a operação **decide**; o resto é neutro de verdade (não cinza médio, mas quase-preto para texto e quase-branco para fundo).

---

## A) Diagnóstico — por que ainda parece pálido e antigo

| Sintoma | Causa |
|---|---|
| "Pálido" | Texto de leitura em `#7A7A79` (cinza médio); valores no mesmo peso/cor dos rótulos; CTA ciano `#15B1C9` com branco tem contraste ~2.5:1 (falha AA). |
| "Poluído" | Card dentro de card; cartões de "filtro ativo"; formulário de filtro aberto por padrão ocupando meia tela; molduras demais. |
| "Info importante escondida" | Hierarquia achatada — UH, PAX, status e horário competem no mesmo tamanho. Nada é herói. |
| "Ainda tem coisa antiga" | Telas legadas (conferência, ambientes completos, CRUDs) seguem em `.card`/`.saas-*`/Bootstrap — pintadas pela ponte, não redesenhadas. |

Autocrítica: os conceitos anteriores achataram, acinzentaram e encolheram em nome de "moderno".
Moderno de verdade é **hierarquia forte + contraste alto + cor com significado**, não ausência de tudo.

---

## B) Direção "Tinta & Brasa" — tokens

```
Tinta (texto/CTA)     ink #1C1C1B  ·  corpo #3B3B39  ·  metadado #6B6B68  (adeus #7A7A79 em texto de leitura)
Superfície            fundo #F4F4F3  ·  card #FFFFFF  ·  borda #E2E2E2
Marca (Oca)           laranja #E67E3C — EXCLUSIVO de marca/identidade
Ação                  primário = TINTA (#1F1F1E sobre branco) — contraste >12:1
                      ciano #15B1C9 rebaixado a interação leve: link, seleção, foco, aba interna
Restaurante           Corais #2E7C9E · La Brasa #C0433B · Giardino #4E8B3B · IX'u #6C5CB0 — usados SÓLIDOS (branco por cima), não em tint pálido
Status  bloqueia →    SÓLIDO + ícone + texto  (esgotado/no-show #C0433B · fechado #7A4A0B)
        informa   →   TINT + ícone + texto   (ok #E6F5EE/#1E6B3E · reservada #E4F1F7/#1D5A75 · alerta #FBF0DC/#7A4A0B)

Tipografia · exatamente 3 níveis (Montserrat):
  Display  42–48px / 800   — o número-herói, 1 por tela
  Corpo    15px / 600      — a informação (UH, titular, nome)
  Metadado 11–12px / 600 uppercase — só rótulos
Raio  controles 9–10px · cards 11–12px    Espaço 4/8/12/16/24    Elevação sutil 0 1px 3px
```

Regra AA: todo par cor-de-fundo/texto passa contraste; status nunca comunica por cor sozinha (sempre ícone+texto).

---

## C) Plano por área

1. **Tokens** — trocar `--fb-ink` para `#1C1C1B`, texto de leitura para `#3B3B39`, CTA primário para tinta,
   rebaixar ciano a link/foco/seleção, criar escala display 42–48px. Ref.: `02-sistema-tinta-brasa.html`.
2. **Todas as telas — anatomia em 3 zonas** — (1) cabeçalho sem moldura, (2) faixa de números com 1 herói +
   apoios sem caixa, (3) uma superfície de conteúdo. "1 moldura por grupo": sub-blocos separam por espaço e
   fio, nunca card-dentro-de-card. Ref.: `04-anatomia-tela-despoluida.html`.
3. **Filtros** — colapsar em 1 linha (chips do estado + "Filtros" abre a folha) também no desktop; matar o
   formulário aberto por padrão; matar os cartões "filtro ativo" (viram texto na própria linha).
4. **Números** — todo KPI importante é herói ou apoio, nunca tile cinza emoldurado; delta com sinal + cor de
   direção; onde couber, barra/medidor. Nenhum valor menor que o rótulo.
5. **Botões** — primário = tinta (1 por tela); secundário = contorno; leve = link ciano; destrutivo = vermelho.
   Ref.: `02-sistema-tinta-brasa.html`.
6. **Status / badges** — sólido+ícone+texto quando bloqueia; tint+ícone+texto quando informa. Ref.: `02`.
7. **Reservas temáticas** — cartão-**bilhete**: hora na cor da casa (canhoto esquerdo), UH em display, dados em
   badges com ícone (adultos/CHD com idade/marcadores), canhoto de status à direita. Fila **agrupada por turno**,
   com o cabeçalho do turno funcionando como **medidor de lotação** (PAX/capacidade + livres). Ref.: `03-reservas-bilhete.html`.
8. **Restaurantes** — headers **sólidos** (branco sobre a cor da casa), não os tints pálidos atuais; a cor da
   casa aparece em ≥1 elemento forte por tela do módulo.
9. **Formulários** — labels 12px, campos 44px, foco ciano, ação fixa no rodapé; steppers/toggles/chips.
10. **Feedbacks** — toast (sucesso), inline (erro no ponto), banner (estado). POST sempre dá retorno.
11. **Mobile/tablet** — mobile-first; bilhete e zonas funcionam em 360px; testar 360/768/1024/1280.
12. **Legado restante — reescrever de verdade** — conferência, ambientes completos e CRUDs saem de `.card`/`.saas-*`
    para `.fb-*` nesta passada. A ponte já provou que pintar não basta. Cada tela migrada apaga seu trecho do `legacy.css`.

---

## D) Implementação (segura, por etapas)

1. **Primeiro os tokens** (item C1) — 1 arquivo, efeito global imediato: ink mais escuro, texto de leitura mais
   escuro, CTA tinta, ciano rebaixado, escala display. É a maior alavanca contra o "pálido".
2. **Componentes** — `fb-hero` (número-herói), `fb-ticket` (cartão-bilhete), `fb-turno-header` (medidor),
   `fb-summary` (faixa de números sem caixa), revisão de `fb-badge` (sólido vs tint), `fb-filterbar` (1 linha).
3. **Aplicar por tela crítica** — registro, check-in, reserva, análise — validando em 360/768/1024/1280 antes de seguir.
4. **Migrar o legado tela a tela** — reescrever em `.fb-*` e apagar o trecho correspondente do `legacy.css` (meta: zero).
5. **Sem big-bang** — cada etapa é uma release validável, com rollback.

---

## E) Checklist de aceite 3.0 — "saiu do pálido?"

- [ ] Toda tela tem exatamente 1 número-herói (≥42px) e nenhum valor menor que seu rótulo
- [ ] Nenhum texto de leitura em `#7A7A79`; metadados só como rótulos uppercase
- [ ] CTA primário é tinta (contraste >12:1); nenhum botão ciano-com-branco sobrou
- [ ] Status críticos são sólidos com ícone+texto; nada comunica por cor sozinha
- [ ] Nenhum card dentro de card; nenhum formulário de filtro aberto por padrão
- [ ] Headers de restaurante em cor plena; a cor da casa aparece em ≥1 elemento forte por tela do módulo
- [ ] Reservas: bilhete + agrupamento por turno + medidor de lotação funcionando
- [ ] `legacy.css` menor a cada tela migrada (meta: zero)

---

## Referências visuais

| Arquivo | Cobre |
|---|---|
| `referencias/01-antes-depois-palido.html` | O mesmo fragmento: pálido atual × Tinta & Brasa |
| `referencias/02-sistema-tinta-brasa.html` | Escala tipográfica (3 níveis), botões, status sólido vs tint |
| `referencias/03-reservas-bilhete.html` | Reservas: fila por turno + cartão-bilhete |
| `referencias/04-anatomia-tela-despoluida.html` | 3 zonas + regras de subtração (despoluir) |
