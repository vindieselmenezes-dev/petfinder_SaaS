<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

require_once __DIR__ . '/../../app/Helpers/Csrf.php';

function voltarSeguro(?string $url): string
{
    // só aceita caminho relativo dentro do próprio site — nunca uma URL
    // completa, pra não virar um open-redirect.
    if (!$url || preg_match('#^(https?:)?//#i', $url) || str_starts_with($url, '\\')) {
        return 'carrinho.php';
    }
    return $url;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validar($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    die('Requisição inválida. Volte para o carrinho e tente novamente.');
}

$produtoId = (int) ($_POST['produto_id'] ?? 0);
$quantidade = (int) ($_POST['quantidade'] ?? 0);

if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

if ($produtoId > 0 && isset($_SESSION['carrinho'][$produtoId])) {
    if ($quantidade <= 0) {
        // Quantidade zerada ou inválida = mesma coisa que remover o item.
        unset($_SESSION['carrinho'][$produtoId]);
    } else {
        // Limite de segurança pra não deixar alguém forçar um número
        // absurdo direto no POST (o checkout também valida contra o
        // estoque real, isso aqui é só uma trava de bom senso na tela).
        $_SESSION['carrinho'][$produtoId] = min($quantidade, 999);
    }
}

header('Location: ' . voltarSeguro($_POST['voltar'] ?? null));
exit;
