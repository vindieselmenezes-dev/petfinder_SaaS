# Fase 6 — CSRF completo + cabeçalhos de segurança

## ⚠️ Bug crítico encontrado e corrigido no caminho

No meio desta fase, ao mexer no `2fa.php`, percebi que ele usava
`Url::pagina()` sem nunca carregar o `bootstrap.php`. Investigando mais
a fundo, descobri que isso não era um caso isolado: **96 das 108
páginas reais do site nunca tinham sido migradas para o `bootstrap.php`**
— elas continuavam com o padrão antigo (`session_start()` +
`require_once` manual) desde antes da Fase 1. Como o `header.php`
compartilhado (usado por praticamente toda página) já dependia de
`Auth::check()` e `Flash::render()` desde a Fase 1, e o `menu.php`/vários
links já dependiam de `Url::pagina()` desde a Fase 2, **a grande maioria
do site já estava quebrada com erro fatal antes mesmo desta fase** —
essa correção não é opcional, era a base pra qualquer outra funcionar.

Corrigido nas 96 páginas: adicionado o `require_once .../app/bootstrap.php`
no lugar certo (respeitando `declare(strict_types=1)` como primeira
instrução) e removido o `session_start()` manual redundante. Cometi um
erro de cálculo de profundidade na primeira tentativa (contei os `../`
errado) — também já corrigido e conferido em toda a árvore `public/`.

Essa é a explicação mais honesta de por que recomendo fortemente testar
tudo localmente antes de ir pra produção: eu não tenho como rodar PHP ou
abrir um navegador aqui, então essa classe de bug (dependência de classe
não carregada) só aparece rodando de verdade, não em uma leitura estática
do código. Fiz o que dava pra fazer sem rodar PHP: uma varredura
automática comparando "quem usa `Url::`/`Auth::`/etc" com "quem carrega
o bootstrap", em vez de confiar só em inspeção visual.

## CSRF completo

Havia 6 páginas que processavam POST sem validar o token CSRF:

| Página | O que faltava |
|---|---|
| `login.php` | token + validação |
| `cadastro.php` | token + validação (e também não usava bootstrap ainda — migrado) |
| `conta/2fa.php` | token + validação |
| `conta/onboarding.php` | token + validação |
| `agendamentos/novo_prontuario.php` | formulário de cadastro de CRMV tinha o form mas sem token nem validação (o form principal de prontuário já validava, só esse trecho menor tinha passado batido) |
| `loja/processa_item_catalogo.php` | não recebeu — é um redirecionamento legado sem gravação em banco (ver Fase 7, candidato a remoção) |

Todas as outras ~40 páginas que processam POST já validavam CSRF desde
antes — essa fase fechou os últimos buracos.

## Cabeçalhos de segurança (`app/Core/SegurancaHttp.php`)

Aplicados uma vez, no bootstrap, pra todo o site:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy` — bloqueia câmera/microfone, mantém geolocalização
  (usada de verdade nos recursos de pet perdido)
- `Strict-Transport-Security` — só quando a requisição já está em HTTPS
- `Content-Security-Policy` — restringe a origens conhecidas (`cdn.jsdelivr.net`,
  `fonts.googleapis.com`, `fonts.gstatic.com`, `api.qrserver.com`)

### Sobre o CSP não ser 100% estrito

O CSP inclui `'unsafe-inline'` pra script e style. Isso é uma escolha
deliberada e pragmática: o site tem dezenas de `<script>` e `style=""`
inline espalhados pelas páginas (não introduzidos por mim — já existiam
no código original). Removê-los exigiria mover cada um pra arquivo
externo ou gerar um "nonce" único por requisição e aplicá-lo em cada
tag — um projeto bem maior, arriscado de fazer sem poder testar
visualmente cada página depois. Documentado como próximo passo possível
de segurança, mas fora do escopo desta fase.

## HTTPS forçado

Só entra em ação quando `APP_ENV` (no `.env`) for diferente de `local`.
Em ambiente de desenvolvimento sem certificado, nada muda.

## Verificação

- Cabeçalhos e chaves balanceadas conferidos nos arquivos alterados.
- Varredura confirmando as 96 páginas com bootstrap correto (profundidade
  de `../` certa em cada uma).
- Varredura confirmando cobertura de `Csrf::validar()` em todas as
  páginas que processam POST (exceto o stub legado sem gravação).
