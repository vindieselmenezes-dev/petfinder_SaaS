<?php

declare(strict_types=1);

session_start();

$produtoId = (int) ($_GET['produto_id'] ?? 0);
$quantidade = max(1, (int) ($_GET['quantidade'] ?? 1));

if ($produtoId <= 0) {
    $voltar = $_SERVER['HTTP_REFERER'] ?? 'produtos.php';
    header('Location: ' . $voltar);
    exit;
}

if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

if (isset($_SESSION['carrinho'][$produtoId])) {
    $_SESSION['carrinho'][$produtoId] += $quantidade;
} else {
    $_SESSION['carrinho'][$produtoId] = $quantidade;
}

$voltar = $_SERVER['HTTP_REFERER'] ?? 'produtos.php';
header('Location: ' . $voltar);
exit;
