## Sobre este pacote

Para manter o download leve, este zip **não inclui** `vendor/`,
`node_modules/` nem `assets/img/` (imagens). Ao aplicar estas mudanças no
seu projeto original:

1. Copie por cima as pastas `app/`, `config/`, `public/`, `.env.example`,
   `.gitignore` e `docs/` deste pacote para o seu projeto.
2. Rode `composer install` se necessário (o `vendor/` já existente no seu
   projeto continua funcionando normalmente).
3. Suas imagens em `assets/img/` e o `node_modules/` do seu projeto
   original não são afetados por esta fase — não precisam ser tocados.

## Fase 1 — Fundação do sistema

Esta é a primeira etapa da reestruturação do PetFinder Brasil, focada na
**base do sistema**: inicialização única, autenticação centralizada,
configuração e sessão. As próximas fases (visual/CSS e organização das
páginas em módulos) ficam para as próximas entregas.

## O que mudou

### 1. Um único ponto de entrada: `app/bootstrap.php`

Antes, cada uma das ~110 páginas em `public/` e `app/ajax/` repetia à mão:
`session_start()`, vários `require_once` de Models/Controllers/Helpers, e
checagens de sessão com nomes de chave diferentes de página pra página.

Agora, toda página só precisa de uma linha no topo:

```php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
```

O bootstrap cuida de: erros/log, fuso horário, Composer, variáveis de
ambiente (`.env`), sessão (com cookies `httponly`/`SameSite`), conexão com
banco, e carrega automaticamente todos os Models, Controllers e Helpers.

### 2. `Auth` — quem está logado (`app/Core/Auth.php`)

Substitui checagens como `$_SESSION['perfil_tipo'] ?? $_SESSION['user_role'] ?? 'tutor'`
espalhadas pelo código. Uso:

```php
Auth::check()            // está logado?
Auth::id()                // ID do usuário (ou null)
Auth::nome() / Auth::email() / Auth::tipo()
Auth::ehEmpresa() / Auth::ehAdministrador()
Auth::tipoEhUmDe('empresa', 'administrador')
Auth::login($usuario)     // grava a sessão (regenera o ID, evita session fixation)
Auth::logout()
```

### 3. `Middleware` — guardas de acesso (`app/Core/Middleware.php`)

Substitui o bloco repetido em dezenas de páginas:

```php
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
```

Agora:

```php
Middleware::exigirLogin();                       // exige estar logado
Middleware::exigirTipo(Auth::TIPO_EMPRESA);      // exige logado + tipo certo (403 se não bater)
Middleware::exigirCsrfValido();                  // valida token CSRF de um POST
```

### 4. `Flash` — mensagens de sucesso/erro (`app/Core/Flash.php`)

O padrão antigo criava uma chave de sessão por funcionalidade
(`sucesso_pet`, `erro_produto`, `sucesso_chamado`...) e várias dessas
mensagens eram gravadas mas **nunca chegavam a ser exibidas** (ex: em
`cadastrar_pet.php`, `editar_pet.php`). Agora:

```php
Flash::sucesso('Pet cadastrado com sucesso!');
Flash::erro('Não foi possível salvar.');
```

E `app/Includes/header.php` já chama `Flash::render()` automaticamente —
qualquer página que inclui o header padrão passa a exibir a mensagem sem
nenhum código extra. Chaves antigas (`sucesso_*`/`erro_*`) continuam
funcionando: `Flash` sabe ler e exibir as legadas também, então nada quebra
enquanto o restante das páginas não for migrado.

### 5. Credenciais fora do código (`.env`)

`config/database.php` agora lê `DB_HOST`, `DB_DATABASE`, `DB_USER`,
`DB_PASSWORD` de variáveis de ambiente, com os mesmos valores de antes
como padrão (então **nada quebra** em quem já roda o projeto localmente).
Copie `.env.example` para `.env` e ajuste para seu ambiente:

```bash
cp .env.example .env
```

## Páginas já migradas (servem de modelo)

- `public/login.php`, `public/logout.php`, `public/dashboard.php`
- Módulo Pet completo: `cadastrar_pet.php`, `editar_pet.php`,
  `excluir_pet.php`, `marcar_pet_recuperado.php`, `meus_pets.php`, `pet.php`
- Todos os 18 arquivos de `app/ajax/`
- `app/Includes/header.php`, `menu.php`, `footer.php`

## Como migrar as páginas restantes

Para cada página em `public/*.php` que ainda usa o padrão antigo:

1. Troque o bloco de topo (`session_start()` + `require_once` + checagem de
   `$_SESSION['usuario_id']`) por:
   ```php
   declare(strict_types=1);

   require_once __DIR__ . '/../app/bootstrap.php';

   Middleware::exigirLogin(); // remova esta linha se a página for pública
   ```
2. Troque `$_SESSION['usuario_id']` por `Auth::id()`, `$_SESSION['usuario_nome']`
   por `Auth::nome()`, e assim por diante.
3. Troque `$_SESSION['sucesso_x'] = '...'` por `Flash::sucesso('...')` (e o
   equivalente para erro).
4. Remova os `require_once` de Models/Controllers/Helpers que a página
   fazia manualmente — o bootstrap já carrega todos.

Não é preciso migrar tudo de uma vez: como o `Auth`/`Flash` são
compatíveis com o formato de sessão antigo, páginas migradas e não
migradas convivem sem problema.

## Testando localmente

1. `cp .env.example .env` e ajuste as credenciais do seu MySQL local.
2. Confirme que `storage/logs/` existe e tem permissão de escrita (é onde
   os erros de PHP passam a ser registrados, em vez de aparecer na tela).
3. Suba o projeto normalmente (ex: `php -S localhost:8000 -t public`) e
   teste login, cadastro de pet e exclusão de pet — são as páginas já
   migradas nesta fase.

## Próximas fases (ainda não feitas)

- **Visual/CSS**: consolidar os dois arquivos CSS (~3000 linhas) em um
  design system único, com variáveis de cor/espaçamento e sem regras
  duplicadas.
- **Organização de `public/`**: agrupar as ~100 páginas por módulo
  (pets, empresas, agendamentos/serviços, loja, suporte...) em vez de
  todas soltas na mesma pasta.
- Migrar o restante das páginas de `public/` para o padrão desta fase.
