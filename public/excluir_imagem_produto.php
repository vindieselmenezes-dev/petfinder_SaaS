<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../app/Controllers/ProdutoController.php";

$produtoController = new ProdutoController();
$usuarioId  = (int) $_SESSION["usuario_id"];

$produtoId = (int) ($_GET["produto_id"] ?? 0);
$imagemId  = (int) ($_GET["imagem_id"] ?? 0);

/*
|--------------------------------------------------------------------------
| Confirma que o produto pertence a uma empresa do usuário logado
|--------------------------------------------------------------------------
*/

$produto = $produtoController->buscarPorId($produtoId);

if ($produto !== null && (int) $produto["empresa_usuario_id"] === $usuarioId) {
    $produtoController->excluirImagem($imagemId, $produtoId);
}

header("Location: editar_produto.php?id=" . $produtoId);
exit;
