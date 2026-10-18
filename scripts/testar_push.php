<?php

declare(strict_types=1);

/**
 * Dispara uma notificação de teste (sino + aviso push) para um usuário.
 *
 * Uso, no terminal, dentro da pasta do projeto:
 *     php scripts/testar_push.php ID_DO_USUARIO
 *
 * O ID está na coluna "id" da tabela usuarios (phpMyAdmin). A pessoa precisa
 * ter ativado as notificações no navegador/app (tabela push_subscriptions).
 * No Windows/XAMPP, defina antes:
 *     $env:OPENSSL_CONF="C:\xampp\php\extras\ssl\openssl.cnf"
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Rode este script pelo terminal.');
}

$usuarioId = (int) ($argv[1] ?? 0);
if ($usuarioId <= 0) {
    fwrite(STDERR, "Informe o ID do usuário. Exemplo: php scripts/testar_push.php 3\n");
    exit(1);
}

$raiz = dirname(__DIR__);
require_once $raiz . '/vendor/autoload.php';
require_once $raiz . '/app/Core/Env.php';
Env::carregar($raiz . '/.env');
require_once $raiz . '/config/database.php';
require_once $raiz . '/app/Models/Notificacao.php';

try {
    $pdo = Database::conectar();

    $assinaturas = $pdo->prepare('SELECT COUNT(*) FROM push_subscriptions WHERE usuario_id = :id');
    $assinaturas->execute([':id' => $usuarioId]);
    $total = (int) $assinaturas->fetchColumn();

    echo "Aparelhos com push ativado para o usuário {$usuarioId}: {$total}\n";
    if ($total === 0) {
        echo "Nenhum. Abra o site logado com essa conta, vá em Notificações e ative.\n";
    }

    $ok = (new Notificacao())->criar(
        $usuarioId,
        'Teste de notificação',
        'Se você está vendo isto no celular ou no computador, o push funciona.',
        'Sistema'
    );

    echo $ok ? "Notificação criada e push disparado.\n" : "Não foi possível criar a notificação.\n";
    echo "Se o aviso não chegar, procure \"[PushNotifier]\" no log de erros do PHP.\n";
} catch (Throwable $erro) {
    fwrite(STDERR, 'Erro: ' . $erro->getMessage() . "\n");
    exit(1);
}
