<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../app/Helpers/Csrf.php';

function voltarSeguro(?string $url): string
{
    // só aceita caminho relativo dentro do próprio site — nunca uma URL
    // completa, pra não virar um open-redirect.
    if (!$url || preg_match('#^(https?:)?//#i', $url) || str_starts_with($url, '\\')) {
        return 'produtos.php';
    }
    return $url;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validar($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    die('Requisição inválida. Volte para a página do produto e tente novamente.');
}

$produtoId = (int) ($_POST['produto_id'] ?? 0);
$quantidade = max(1, (int) ($_POST['quantidade'] ?? 1));

if ($produtoId <= 0) {
    $voltar = voltarSeguro($_POST['voltar'] ?? null);
    header('Location: ' . $voltar);
    exit;
}

if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

if (($_POST['modo'] ?? '') === 'agora') {
    $_SESSION['carrinho'] = [$produtoId => $quantidade];
} elseif (isset($_SESSION['carrinho'][$produtoId])) {
    $_SESSION['carrinho'][$produtoId] += $quantidade;
} else {
    $_SESSION['carrinho'][$produtoId] = $quantidade;
}

$_SESSION['carrinho_flash'] = 'Produto adicionado ao carrinho!';

$voltar = voltarSeguro($_POST['voltar'] ?? null);
header('Location: ' . $voltar);
exit;
