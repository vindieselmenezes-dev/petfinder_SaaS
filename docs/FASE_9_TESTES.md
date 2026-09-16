# Fase 9 — Testes automatizados (PHPUnit)

## O que foi criado

30 testes PHPUnit cobrindo as três classes puras do `app/Core/` — as
que não dependem de banco de dados, então rodam em menos de um segundo,
sem precisar de MySQL configurado:

- **`tests/phpunit/AuthTest.php`** (13 testes) — login preenche a sessão
  certo, os fallbacks de tipo de usuário (`perfil_tipo` → `tipo_usuario`
  → `cliente`), `ehAdministrador()`/`ehEmpresa()`/`tipoEhUmDe()`,
  `logout()` limpa tudo, valores padrão de nome/e-mail.
- **`tests/phpunit/UrlTest.php`** (9 testes) — resolução de módulo por
  página, respeito ao `APP_BASE_PATH` configurável, os quatro tipos de
  link (`pagina`, `asset`, `upload`, `ajax`, `raiz`).
- **`tests/phpunit/FlashTest.php`** (11 testes) — grava e consome uma
  única vez, tipos corretos (success/danger/warning), compatibilidade
  com as chaves antigas (`sucesso_x`/`erro_x` — a mesma rede de segurança
  usada nas Fases 1 e 8), e o mais importante: **`render()` escapa a
  mensagem** (teste específico de proteção contra XSS).

## Como rodar

```bash
# Instalar o PHPUnit (uma vez só), se ainda não tiver:
sudo apt install phpunit
# ou, se preferirem via Composer:
composer require --dev phpunit/phpunit ^9

# Rodar os testes:
phpunit --configuration phpunit.xml
```

Isso não interfere com a suíte de testes que já existia
(`tests/run_all.php`, com `TestKit.php`) — que testa os Models e
Controllers contra um banco de dados de verdade. Continuam
funcionando e sendo executados separadamente:

```bash
php tests/run_all.php
```

## Uma peculiaridade do PHP em linha de comando

`Auth::login()`/`Auth::logout()` chamam funções de sessão
(`session_regenerate_id`, `setcookie`, `session_destroy`) que, quando
rodadas via linha de comando (sem um servidor web de verdade), emitem
avisos inofensivos assim que qualquer texto já tiver sido impresso na
tela — isso **não acontece em produção**, é uma particularidade só do
PHP CLI. O `phpunit.xml` já está configurado pra não exibir esse ruído
(`error_reporting` ignora `E_WARNING`/`E_DEPRECATED` durante os testes).
Documentei isso nos comentários do `AuthTest.php` e do `phpunit.xml`
pra ninguém estranhar no futuro.

## Verificação final desta fase (e de todo o cronograma)

- **112 testes automatizados passando** no total: 82 da suíte antiga
  (Models/Controllers com banco de dados real) + 30 novos (classes
  puras do Core).
- `php -l` (checagem de sintaxe) em todos os arquivos `.php` do
  projeto — zero erros.
- Site testado ao vivo com PHP 8.3 + MariaDB + o banco de dados real
  do projeto, cobrindo páginas de todos os módulos.

Com isso, fecha o cronograma das 9 fases combinado no início: fundação,
organização em módulos, CSS, helper de foto, performance, CSRF e
segurança, limpeza, flash messages, e agora testes automatizados.
