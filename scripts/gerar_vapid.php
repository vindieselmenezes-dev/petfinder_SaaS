<?php

declare(strict_types=1);

/**
 * Gera o par de chaves VAPID usado nas notificações push (celular/navegador).
 *
 * Como usar (no terminal, dentro da pasta do projeto):
 *     php scripts/gerar_vapid.php
 *
 * Copie as 3 linhas que aparecerem para o arquivo .env do ambiente onde o
 * site roda. A chave PRIVADA é segredo: não envie para o GitHub nem cole em
 * conversas. A chave pública pode ser exposta (o site já a entrega ao navegador).
 *
 * Gere UM par por ambiente e guarde-o: se a chave mudar depois, as pessoas
 * que já ativaram as notificações precisam ativar de novo.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Rode este script pelo terminal.');
}

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "vendor/autoload.php não encontrado. Rode 'composer install' primeiro.\n");
    exit(1);
}
require_once $autoload;

// Windows/XAMPP: o PHP não acha o openssl.cnf sozinho e a criação da chave
// falha com "Unable to create the key". Aponta para o arquivo que o XAMPP traz.
if (PHP_OS_FAMILY === 'Windows' && !getenv('OPENSSL_CONF')) {
    $candidatos = [
        dirname(PHP_BINARY) . '\\extras\\ssl\\openssl.cnf',
        dirname(PHP_BINARY) . '\\extras\\openssl\\openssl.cnf',
        'C:\\xampp\\php\\extras\\ssl\\openssl.cnf',
        'C:\\xampp\\apache\\conf\\openssl.cnf',
    ];
    foreach ($candidatos as $candidato) {
        if (is_file($candidato)) {
            putenv('OPENSSL_CONF=' . $candidato);
            break;
        }
    }
}

if (!class_exists('\\Minishlink\\WebPush\\VAPID')) {
    fwrite(STDERR, "Biblioteca minishlink/web-push não instalada. Rode 'composer install'.\n");
    exit(1);
}

try {
    $chaves = \Minishlink\WebPush\VAPID::createVapidKeys();
} catch (Throwable $erro) {
    fwrite(STDERR, 'Não foi possível gerar as chaves: ' . $erro->getMessage() . "\n");
    fwrite(STDERR, "Confira se as extensões openssl e gmp (ou bcmath) do PHP estão ativas.\n");
    if (PHP_OS_FAMILY === 'Windows') {
        fwrite(STDERR, "No Windows, aponte o OpenSSL para o arquivo de configuração e rode de novo:\n");
        fwrite(STDERR, "  \$env:OPENSSL_CONF=\"C:\\xampp\\php\\extras\\ssl\\openssl.cnf\"\n");
    }
    exit(1);
}

$linhas = 'PUSH_VAPID_PUBLIC_KEY=' . $chaves['publicKey'] . "\n"
    . 'PUSH_VAPID_PRIVATE_KEY=' . $chaves['privateKey'] . "\n"
    . "PUSH_VAPID_SUBJECT=mailto:contato@seudominio.com.br\n";

// Com --arquivo, grava em storage/vapid_novas.txt (copie de lá, inteiro, e APAGUE o
// arquivo depois). Evita chaves cortadas ao copiar linhas longas do terminal.
if (in_array('--arquivo', $argv, true)) {
    $destino = __DIR__ . '/../storage/vapid_novas.txt';
    if (@file_put_contents($destino, $linhas) === false) {
        fwrite(STDERR, "Não foi possível gravar em storage/vapid_novas.txt.\n");
        exit(1);
    }
    echo "\nChaves salvas em storage/vapid_novas.txt. Abra o arquivo no VS Code, copie as linhas\n";
    echo "INTEIRAS para o .env e depois APAGUE o arquivo (ele contém a chave privada).\n";
    exit(0);
}

echo "\nCole estas linhas no seu .env (troque o e-mail do SUBJECT pelo seu):\n\n";
echo $linhas . "\n";
echo "ATENÇÃO: a chave privada é segredo. Não suba o .env para o GitHub.\n";
