# Fase 2 — Organização de `public/` em módulos

Agora as ~100 páginas soltas em `public/` estão agrupadas por
funcionalidade, dentro de subpastas:

```
public/
├── login.php, logout.php, cadastro.php, dashboard.php   (entrada/hub)
├── blog.php, pesquisa.php, em_breve.php                 (institucional)
├── pets/            (23 páginas: cadastro, busca, favoritos, adoção...)
├── empresas/        (15 páginas: cadastro de empresa, planos, faturamento...)
├── agendamentos/    (22 páginas: consultas, prestadores, hotelzinho, banho e tosa...)
├── loja/            (21 páginas: produtos, carrinho, checkout, pedidos...)
├── suporte/         (8 páginas: chamados, conversas...)
├── admin/           (2 páginas: gestão de usuários)
└── conta/           (9 páginas: perfil, senha, endereço, 2FA...)
```

## Como os links continuam funcionando

Isso era o maior risco da reorganização: mover 100 arquivos sem quebrar
links entre eles. Duas coisas resolvem isso:

### 1. `Url::pagina()` — um mapa único de módulos

Criei `app/Core/Url.php`, que sabe em qual módulo cada página vive.
Todo link entre páginas do sistema (no menu lateral, e nos links entre
módulos diferentes que eu encontrei durante a migração) passou a usar:

```php
Url::pagina('cadastrar_pet.php')  // -> /public/pets/cadastrar_pet.php
```

Se um arquivo mudar de módulo no futuro, **só se atualiza uma linha em
`Url.php`** — nenhuma página precisa ser tocada.

Também existem `Url::asset()`, `Url::ajax()` e `Url::raiz()` para
CSS/JS/imagens, endpoints de `app/ajax`, e arquivos da raiz do projeto
(`index.html`, `manifest.json`, `sw.js`).

Configurável via `.env` (`APP_BASE_PATH`) para o caso do projeto estar
hospedado numa subpasta.

### 2. Redirecionamentos de compatibilidade (rede de segurança)

Todo o caminho antigo (ex: `public/cadastrar_pet.php`) continua
existindo — agora como um redirecionamento 301 para o novo local
(`public/pets/cadastrar_pet.php`), preservando a query string. Ou seja,
links salvos, favoritos do navegador, ou qualquer referência que eu não
tenha pego na varredura continuam funcionando (com um redirecionamento
a mais).

## O que foi conferido

Depois de mover os arquivos, rodei uma varredura automática em **todo**
o `public/` procurando por `href`, `action` e `window.location`
apontando para arquivos de outro módulo (ou da raiz) de forma "hardcoded"
— esses casos foram corrigidos para usar `Url::pagina()`. Ao final, a
varredura não encontrou mais nenhuma referência inconsistente.

Também corrigi de bônus 4 links quebrados que já existiam **antes** desta
reorganização (apontavam para um `index.php` que nunca existiu em
`public/` — viram agora `Url::raiz('index.html')`, a home real do site).

## O que NÃO foi verificado (por não ter como rodar/testar PHP aqui)

Esta fase foi feita inteiramente por análise estática de texto — não há
um interpretador PHP disponível neste ambiente para efetivamente rodar o
site e clicar em cada link. Recomendo, antes de subir pra produção:

1. Rodar localmente (`php -S localhost:8000 -t public` a partir da pasta
   do projeto) e navegar pelas telas principais de cada módulo.
2. Prestar atenção especial em fluxos que atravessam módulos (ex: do
   carrinho pro checkout, de um pet pra uma solicitação de adoção).
3. Testar como usuário tutor, empresa e administrador (o menu muda pra
   cada um).

## Correção encontrada durante a auditoria

Ao revisar todos os links, encontrei um problema real que a própria
reorganização física causaria: páginas que **não** usam o `header.php`
compartilhado (produtos, empresas, prestadores, etc. — que têm seu
próprio `<head>` "site público") tinham links para `../assets/...`,
`../uploads/...` e `../index.html` com apenas um nível de `../`. Como
essas páginas se moveram uma pasta mais fundo, isso quebraria CSS,
imagens e uploads. Corrigido em todos os 38 arquivos afetados (mais 4
links que já apontavam pra um `index.php` inexistente antes mesmo desta
reorganização).

## Próxima fase

Com a fundação (Fase 1) e a organização em módulos (Fase 2) prontas, falta
a **Fase 3 — Visual/CSS**: consolidar os dois arquivos CSS em um design
system único, sem duplicidade de regras.
