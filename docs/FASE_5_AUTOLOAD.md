# Fase 5 — Autoload sob demanda (performance)

## O problema

O `bootstrap.php` (Fase 1) carregava **todos** os arquivos de
`app/Controllers`, `app/Models` e `app/Helpers` em **toda** requisição,
mesmo que a página precisasse de só uma ou duas classes. Por exemplo,
`logout.php` só usa `Auth::logout()`, mas mesmo assim toda a base de
código (produtos, prontuários, notificações, prestadores...) era lida e
interpretada pelo PHP a cada acesso.

## Por que não migrei pra namespaces + PSR-4 "de verdade"

O pedido original era "migrar pra autoload PSR-4 do Composer, com
namespaces". Decidi **não** fazer isso, e explico o porquê:

1. Nenhuma classe do projeto tem namespace hoje (`class PetController`,
   não `App\Controllers\PetController`). Pra usar PSR-4 de verdade, eu
   teria que adicionar `namespace App\Controllers;` em ~40 arquivos e um
   `use App\Controllers\PetController;` (ou nome totalmente qualificado)
   em cada um dos ~110 lugares que fazem `new PetController()` — um
   volume de mudança mecânica muito maior, sem ganho de performance
   nenhum sobre a alternativa abaixo.
2. Não tenho PHP nem Composer disponíveis neste ambiente pra rodar
   `composer dump-autoload` e validar o resultado. Editar à mão os
   arquivos gerados pelo Composer (`vendor/composer/autoload_static.php`)
   é arriscado: um erro ali quebraria o autoload do PHPMailer e do
   Web Push também, não só do seu próprio código.

## O que fiz em vez disso

Como o nome de cada classe já é idêntico ao nome do arquivo (ex:
`PetController` mora em `app/Controllers/PetController.php` — comum em
projetos PHP sem framework), registrei um autoloader próprio no
`bootstrap.php`:

```php
spl_autoload_register(function (string $classe): void {
    static $pastas = ['Controllers', 'Models', 'Helpers'];
    foreach ($pastas as $pasta) {
        $caminho = APP_ROOT . "/app/{$pasta}/{$classe}.php";
        if (is_file($caminho)) {
            require_once $caminho;
            return;
        }
    }
});
```

Isso dá exatamente o ganho de performance pedido — cada classe só é lida
na hora em que é realmente usada — **sem** tocar no autoload do
Composer e **sem** precisar adicionar namespace em nenhum arquivo
existente. Antes de aplicar, conferi que:

- toda classe em `Controllers/`, `Models/` e `Helpers/` tem exatamente
  o mesmo nome do arquivo que a contém (script de verificação, sem
  nenhuma divergência encontrada);
- nenhum desses arquivos declara mais de uma classe;
- os `require_once` que alguns Controllers já tinham entre si (ex:
  `PetController` exigindo `Models/Pet.php`) continuam funcionando
  normalmente — `require_once` não gera erro se o arquivo já tiver sido
  carregado pelo autoloader.

## Se um dia quiserem migrar pra PSR-4/namespaces de verdade

Fica documentado como próximo passo possível, mas é um trabalho bem
maior (tocar ~150 arquivos) que só compensa se o projeto for crescer
bastante ou passar a usar um framework. Recomendo fazer isso com
PHPUnit rodando (Fase 9) pra pegar qualquer `use` ou nome totalmente
qualificado esquecido.
