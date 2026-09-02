<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../app/Helpers/Csrf.php';

function voltarSeguro(?string $url): string {
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
$removerTudo = isset($_POST['apagar']) && $_POST['apagar'] === '1';

if ($produtoId <= 0) {
    header('Location: ' . voltarSeguro($_POST['voltar'] ?? null));
    exit;
}

if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

if ($removerTudo) {
    unset($_SESSION['carrinho'][$produtoId]);
} else {
    if (isset($_SESSION['carrinho'][$produtoId])) {
        $_SESSION['carrinho'][$produtoId]--;
        if ($_SESSION['carrinho'][$produtoId] <= 0) {
            unset($_SESSION['carrinho'][$produtoId]);
        }
    }
}

header('Location: ' . voltarSeguro($_POST['voltar'] ?? null));
exit;
