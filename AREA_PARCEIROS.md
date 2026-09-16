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
