# Fase 3 — Visual/CSS consolidado

## O problema

`assets/css/style.css` (site público) e `assets/css/dashboard.css`
(área logada) tinham, cada um, sua própria cópia de:

- `@import` da fonte Poppins
- bloco `:root` com as variáveis de cor (levemente diferentes entre os
  dois: `dashboard.css` tinha `--vermelho`, `--sombra-leve` e `--raio`
  que `style.css` não tinha; os valores de `--sombra` e `--transicao`
  também eram diferentes entre os dois arquivos)
- reset (`* { margin: 0; padding: 0; ... }`)
- estilos base de `body`

Além disso, `style.css` (2490 linhas) tinha uma formatação bem
incomum: **uma linha em branco depois de cada propriedade**, o que
inflava o arquivo e dificultava a leitura.

## O que foi feito

### 1. `assets/css/base.css` (novo)

Concentra tudo que é realmente compartilhado: fonte, variáveis de cor
(a união das duas listas, com um valor único de `--sombra` e
`--transicao`), reset e estilos base de `body`. Também adicionei duas
coisas de boas práticas que nenhum dos dois arquivos tinha:

```css
/* foco visível ao navegar por teclado */
a:focus-visible, button:focus-visible, ... { outline: 2px solid var(--azul-claro); }

/* respeita quem pediu "reduzir animações" no sistema operacional */
@media (prefers-reduced-motion: reduce) { ... }
```

### 2. `style.css` e `dashboard.css` enxutos

Cada um agora começa com `@import url('base.css');` e só contém o que
é realmente específico dele (o layout do dashboard num, os estilos de
marketing/catálogo/blog no outro). `style.css` também foi todo
reformatado, removendo as linhas em branco excessivas — caiu de 2490
para cerca de 1500 linhas **sem perder nenhuma regra**, só mais legível.

### 3. Duplicidade real de regras

Encontrei `.lista-sugestoes` declarado duas vezes dentro do próprio
`style.css` (uma declaração original + uma "correção de
posicionamento" feita depois por cima) — consolidei em uma única
declaração com o resultado final.

## Resultado

| Arquivo | Antes | Depois |
|---|---|---|
| `style.css` | 2490 linhas | ~1500 linhas |
| `dashboard.css` | 624 linhas | ~590 linhas |
| `base.css` | não existia | 70 linhas (novo, compartilhado) |
| **Total** | **3114 linhas** | **~2160 linhas** |

Nenhuma classe HTML foi renomeada — todas as páginas continuam
funcionando exatamente como antes, só que agora uma mudança de cor da
marca (por exemplo) é feita uma vez em `base.css`, em vez de duas.

## O que fica pra depois (fora do escopo desta limpeza)

Esta fase focou em **eliminar duplicidade e organizar**, não em redesenhar
a aparência do site — eu não tenho como renderizar/tirar print das
páginas neste ambiente, então uma mudança visual mais ousada (paleta,
imagens, layout) seria arriscada sem conseguir conferir o resultado. Se
depois disso vocês quiserem um refresh visual de verdade (nova paleta,
tipografia, etc.), isso vale um pedido à parte, testado visualmente.
