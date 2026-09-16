<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL — Bootstrap da aplicação
 * ==========================================================
 * Ponto único de entrada para toda página em /public e /app/ajax.
 *
 * Antes, cada uma das ~110 páginas repetia manualmente:
 *   - session_start()
 *   - require_once de vários Models/Controllers/Helpers
 *   - checagens de sessão com nomes de chave inconsistentes
 *
 * Agora basta uma linha no topo de cada página:
 *   require_once __DIR__ . '/../app/bootstrap.php';
 */

define('APP_ROOT', dirname(__DIR__));

// --- Erros: registra em log, nunca mostra na tela em produção ---------------
$appDebug = getenv('APP_DEBUG');
error_reporting(E_ALL);
ini_set('display_errors', $appDebug === 'true' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', APP_ROOT . '/storage/logs/php_errors.log');

date_default_timezone_set('America/Sao_Paulo');

// --- Dependências (Composer) + variáveis de ambiente -------------------------
require_once APP_ROOT . '/vendor/autoload.php';
require_once APP_ROOT . '/app/Core/Env.php';
Env::carregar(APP_ROOT . '/.env');

// --- Cabeçalhos de segurança + HTTPS forçado (fora do ambiente local) -------
require_once APP_ROOT . '/app/Core/SegurancaHttp.php';
SegurancaHttp::aplicar();

// --- Sessão segura ------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// --- Banco de dados -------------------------------------------------------
require_once APP_ROOT . '/config/database.php';

// --- Núcleo (autenticação, guardas de acesso, mensagens flash) --------------
require_once APP_ROOT . '/app/Core/Auth.php';
require_once APP_ROOT . '/app/Core/Middleware.php';
require_once APP_ROOT . '/app/Core/Flash.php';
require_once APP_ROOT . '/app/Core/Url.php';

// --- Autoload sob demanda dos Helpers/Models/Controllers/Foto ---------------
// Antes, o bootstrap carregava TODOS os arquivos dessas pastas em toda
// requisição (via glob + require_once), mesmo quando a página só usava
// uma ou duas classes. Como o nome de cada classe é idêntico ao nome do
// arquivo (ex: PetController -> app/Controllers/PetController.php), dá
// pra carregar cada uma só na hora em que ela é realmente usada — sem
// precisar mexer no autoload gerado pelo Composer (arriscado sem poder
// rodar `composer dump-autoload` para validar).
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
