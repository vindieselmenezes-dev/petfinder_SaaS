<?php

declare(strict_types=1);

session_start();

$produtoId = (int) ($_GET['produto_id'] ?? 0);
$removerTudo = isset($_GET['apagar']) && $_GET['apagar'] === '1';

if ($produtoId <= 0) {
    $voltar = $_SERVER['HTTP_REFERER'] ?? 'carrinho.php';
    header('Location: ' . $voltar);
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

$voltar = $_SERVER['HTTP_REFERER'] ?? 'carrinho.php';
header('Location: ' . $voltar);
exit;
