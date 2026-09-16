# Área de Parceiros (ONGs, empresas apoiadoras e campanhas)

## O que mudou

A seção **"Empresas Parceiras"** da `index.html` era uma fileira estática de quatro
imagens (`assets/img/parceiros/parceiro0X.jpg`), sem cadastro nenhum por trás.

Agora ela é um módulo completo, voltado para **ONGs e empresas que ajudam na
divulgação e no crescimento do site**, com destaque para **campanhas, eventos e
doações**.

## Banco de dados

`database/migration_024_parceiros_campanhas.sql` (aplicar depois da 023):

| Tabela | Para que serve |
| --- | --- |
| `parceiros` | ONGs, protetores e empresas apoiadoras. Tem `status` (pendente/aprovado/recusado/inativo) e `destaque` (aparece na home). |
| `parceiro_campanhas` | Publicações do parceiro: `campanha` (meta em R$), `evento` (data + local) ou `doacao` (itens). |
| `campanha_apoios` | Quem se ofereceu para doar, ser voluntário ou divulgar. |

O parceiro pode ser vinculado a uma empresa já existente do SaaS (`empresa_id`),
mas não precisa ser — uma ONG entra sem ter empresa cadastrada.

## Arquivos novos

**Modelos e regras**

- `app/Models/Parceiro.php`
- `app/Models/Campanha.php`
- `app/Controllers/ParceiroController.php` — validações e upload de imagens
- `app/ajax/listar_parceiros_destaque.php` — alimenta a home

**Páginas** (`public/parceiros/`)

| Arquivo | Quem acessa |
| --- | --- |
| `parceiros.php` | Público. Hub com campanhas em destaque + lista de parceiros. |
| `parceiro.php` | Público. Perfil da ONG/empresa, contatos e formas de doar. |
| `campanha.php` | Público. Detalhe da ação + formulário "quero ajudar". |
| `cadastrar_parceiro.php` | Logado. Candidatura e edição da parceria. |
| `painel_parceiro.php` | Logado. Painel do parceiro com suas publicações. |
| `campanha_form.php` | Parceiro. Cria/edita campanha, evento ou doação. |
| `campanha_acoes.php` | Parceiro. POST: excluir publicação, atualizar arrecadado. |
| `apoios_campanha.php` | Parceiro. Lista quem se ofereceu para ajudar. |
| `admin_parceiros.php` | Administrador. Aprova, recusa, inativa e destaca. |

Todas foram registradas no mapa `Url::MODULOS` (módulo `parceiros`), então os
links continuam funcionando se o projeto estiver em subpasta.

## Fluxo

1. A ONG/empresa se cadastra em **Quero ser parceiro** → entra como `pendente`.
2. O administrador aprova em **Moderação de Parceiros** (menu do admin).
3. O parceiro publica campanhas, eventos e pedidos de doação pelo painel.
4. Visitantes veem tudo em `/public/parceiros/parceiros.php` e na home, e podem
   registrar apoio (doação, voluntariado ou divulgação).
5. O parceiro vê os apoios recebidos e atualiza o total arrecadado.

## Importante sobre dinheiro

O PetFinder **não recebe nem intermedia** doações: a página só exibe a chave PIX
ou o link do parceiro, e o valor arrecadado é informado manualmente por ele.
Isso evita qualquer obrigação de gateway de pagamento, split e repasse — se um
dia isso for desejado, o ponto de entrada é `campanha_apoios`, que já guarda a
intenção de doação de cada pessoa.

## Uploads

Duas pastas novas, criadas automaticamente no primeiro upload:

- `uploads/parceiros/` — logos
- `uploads/campanhas/` — imagens das ações

Aceita JPG, PNG e WEBP até 5 MB, validados por `getimagesize()` e redimensionados
pelo `ImagemUpload` (mesmo padrão das empresas e produtos).

## Notificações

- Quando alguém registra apoio em uma campanha, o responsável pelo parceiro
  recebe uma notificação (sino + push + e-mail, conforme o `.env`) com link
  direto para `apoios_campanha.php`.
- Quando o administrador aprova, recusa ou inativa uma parceria, o responsável
  também é notificado, com a observação escrita na moderação.

Ambas usam o `Notificacao` que já existia — nenhum canal novo foi criado.

## Dados de exemplo

`database/seed_parceiros_exemplo.sql` (opcional, só para desenvolvimento e
apresentação) cria 4 parceiros aprovados e 4 publicações — uma campanha com meta
parcialmente atingida, dois eventos e um pedido de doação de itens. Precisa de
pelo menos um usuário cadastrado; o primeiro vira o responsável.

## Cards no dashboard

`public/dashboard.php` ganhou atalhos para a área:

- **Administrador:** "Parcerias a aprovar" (contador da fila de moderação, leva
  direto para `admin_parceiros.php?status=pendente`) e "Campanhas ativas".
- **Tutor/cliente:** "Campanhas e Doações" (número de ações em andamento) e um
  card que muda conforme a situação — "Painel do Parceiro" para quem já tem
  parceria cadastrada, ou "Seja Parceiro" para quem ainda não tem.

A leitura desses números está dentro de um `try/catch`: se a `migration_024`
ainda não tiver sido aplicada, o dashboard continua carregando normalmente com
os contadores zerados, e o erro vai só para o log.

## Paginação no hub (`parceiros.php`)

O hub tem duas listagens compridas na mesma tela — campanhas/eventos/doações e
parceiros — e cada uma pagina de forma independente, reaproveitando o
`app/Helpers/Paginador.php` que já existia para as listas de pets:

- Campanhas: 6 por página, parâmetro `?pagina=`.
- Parceiros: 9 por página, parâmetro `?pagina_parceiros=`.

O `Paginador` ganhou dois complementos, sem quebrar quem já o usa (que
continua chamando `Paginador::renderizar($pagina, $totalPaginas)` normalmente):

- um 4º argumento opcional `$parametro` em `renderizar()`, pra permitir duas
  paginações independentes na mesma URL sem uma sobrescrever a outra;
- `Paginador::offset($pagina, $porPagina)`, pra não espalhar a conta de
  `LIMIT`/`OFFSET` em cada página que usa paginação.

Os models ganharam os métodos de contagem que faltavam para isso funcionar:

- `Campanha::contarAtivas($tipo)` e `Campanha::listarAtivas(..., $offset)`.
- `Parceiro::contarAprovados($tipo, $cidade, $busca)` e
  `Parceiro::listarAprovados(..., $limite, $offset)`.

Trocar de página nunca derruba os filtros que já estavam ativos (tipo de
campanha, tipo de parceiro, cidade, busca) — e trocar um filtro sempre volta
para a página 1 daquela lista, porque os links/forms de filtro não carregam
os parâmetros de página.

## Compartilhamento em redes sociais

Os cards de campanha/evento/doação ganharam um botão de compartilhar
(WhatsApp, Facebook, X e Telegram) mais "copiar link", em três lugares:

- **Home (`index.html`)**: dropdown compacto no card, montado em JS puro
  (mesma lógica do PHP, reescrita em `cardCampanha()` / `dropdownCompartilhar()`),
  já que essa seção é renderizada no navegador a partir do JSON do AJAX.
- **Hub (`public/parceiros/parceiros.php`)**: mesmo dropdown compacto, mas
  gerado no servidor por `Compartilhamento::renderizarDropdown()`.
- **Detalhe da campanha (`public/parceiros/campanha.php`)**: bloco completo
  com os botões grandes + campo de link, via `Compartilhamento::renderizarBloco()`.

Não usa API nem chave de nenhuma rede — são só os links públicos de "intent"
de cada uma (`wa.me`, `facebook.com/sharer`, `twitter.com/intent/tweet`,
`t.me/share`), então funciona sem nenhuma configuração extra.

### Arquivos novos

- `app/Helpers/Compartilhamento.php` — monta os links e o HTML dos dois
  formatos (bloco completo e dropdown compacto).
- `assets/js/compartilhamento.js` — clique delegado no `document` para o
  botão "copiar link" (via `data-copiar-alvo="idDoInput"` ou
  `data-copiar-texto="url""`), com fallback para navegadores sem Clipboard
  API. Incluído em `campanha.php`, `parceiros.php` e na home.

### `Url::absoluta()`

Os links de `Url::pagina()` são relativos (ex: `/public/parceiros/campanha.php?id=5`).
WhatsApp/Facebook/X precisam de uma URL completa pra funcionar de verdade, então
`app/Core/Url.php` ganhou `Url::absoluta($caminhoRelativo)`, que prefixa o
esquema (`http`/`https`) e o domínio a partir de `$_SERVER['HTTP_HOST']`. Se
não houver host disponível (ex: rodando via CLI), devolve o caminho relativo
mesmo, sem quebrar.

## Checagem do rodapé, redes sociais e newsletter (fora do módulo de parceiros)

Aproveitando que a home (`index.html`) já estava sendo mexida, revisei três
pontos que não tinham relação direta com parceiros, mas estavam quebrados ou
incompletos:

### Newsletter ("Receba nossas novidades")

O formulário existia só visualmente: sem `action`, sem JS, e o botão
"Inscrever-se" fazia um GET recarregando a própria página sem salvar nada —
apesar de já existir uma tabela `newsletter` pronta no banco, sem nenhum
código por trás dela.

Agora:

- `app/Models/Newsletter.php` — grava a inscrição, reativa quem já tinha
  cancelado (e-mail é `UNIQUE` na tabela) e valida o formato do e-mail.
- `app/ajax/newsletter_inscrever.php` — endpoint público (sem exigir login,
  sem CSRF de propósito — é só um cadastro de e-mail, o pior caso de abuso é
  inscrever a própria vítima). Tem honeypot (campo escondido `site`) contra
  bots simples de spam.
- O formulário na home agora envia via `fetch`, mostra mensagem de sucesso/erro
  embaixo do botão e não recarrega a página.

### Redes sociais no rodapé

Os ícones (Facebook, Instagram, YouTube, LinkedIn) apontavam para `href="#"` e
não tinham `aria-label` — ou seja, além de não levar a lugar nenhum, um leitor
de tela não conseguia dizer qual rede cada ícone representava.

Adicionei `aria-label` e `title` em cada um. Os links continuam como
placeholder (`#`) porque **não tenho as URLs reais** dos perfis do PetFinder
Brasil — troque pelos links de verdade assim que existirem (tem um comentário
`TODO` no HTML, logo acima dos ícones, lembrando de adicionar também
`target="_blank" rel="noopener"` quando isso acontecer).

Os links "Sobre" e "Contato" (mesma seção do rodapé) também são placeholder
(`#`) — não mexi neles porque não há, ainda, uma página de destino para
apontar.

## Páginas institucionais do rodapé (Sobre, Contato, Ajuda, Privacidade, Termos)

Os cinco links do rodapé da home que ainda eram placeholder (`href="#"`)
agora apontam para páginas de verdade, todas em `public/` (mesmo nível de
`blog.php`, sem precisar de login):

| Link do rodapé | Arquivo | O que tem |
| --- | --- | --- |
| Sobre | `public/sobre.php` | Missão do projeto + números reais da plataforma (pets cadastrados, adoções, empresas, ONGs). |
| Contato | `public/contato.php` | Formulário público de contato (funciona logado ou não). |
| Ajuda | `public/ajuda.php` | Perguntas frequentes (accordion) + atalho para abrir um chamado de suporte. |
| Privacidade | `public/privacidade.php` | Política de privacidade, no espírito da LGPD. |
| Termos | `public/termos.php` | Termos de uso da plataforma. |

### Formulário de contato

Antes não existia nada — os dados iam para lugar nenhum. Agora:

- `app/Models/MensagemContato.php` + `database/migration_025_mensagens_contato.sql`
  guardam cada mensagem enviada.
- Ao enviar, todos os administradores recebem uma notificação (sino), e a
  mensagem também vai para o e-mail configurado em `EMAIL_CONTATO` (no
  `.env.example`) — o `Mailer` sempre grava em `logs/emails.log`, mesmo sem
  SMTP configurado, então nada se perde mesmo num ambiente que ainda não
  tem envio de e-mail configurado de verdade.
- `public/admin_contatos.php` (menu do admin → "📬 Mensagens de Contato")
  lista as mensagens e permite marcar o andamento (novo / em andamento /
  respondido).
- Tem honeypot contra bots, seguindo o mesmo padrão do formulário de
  newsletter.

### Sobre `privacidade.php` e `termos.php`

**Importante:** o texto é um modelo, no espírito da LGPD e das práticas
usuais de termos de uso — não é aconselhamento jurídico. Os trechos entre
colchetes (`[razão social a definir]`, `[CNPJ a definir]`, `[endereço a
definir]`, `[cidade/UF a definir]`) precisam ser preenchidos com os dados
jurídicos reais de quem for operar o PetFinder Brasil de verdade, e o ideal
é revisar com um advogado antes de publicar.

### Links "Sobre"/"Contato" dentro de Ajuda

`ajuda.php` já linka para `contato.php` e para abrir um chamado de suporte
(`novo_chamado.php`, que exige login — por isso o botão muda para "Entrar
para abrir um chamado" quando a pessoa não está logada).
