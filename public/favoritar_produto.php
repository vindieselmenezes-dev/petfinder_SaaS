<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../app/Models/FavoritoProduto.php';

$usuarioId = (int) $_SESSION['usuario_id'];
$produtoId = (int) ($_GET['produto_id'] ?? 0);
$acao = $_GET['acao'] ?? 'adicionar';

if ($produtoId > 0) {
    $fav = new FavoritoProduto();

    if ($acao === 'remover') {
        $fav->remover($usuarioId, $produtoId);
    } else {
        $fav->adicionar($usuarioId, $produtoId);
    }
}

$voltar = $_SERVER['HTTP_REFERER'] ?? 'produtos.php';
header('Location: ' . $voltar);
exit;
