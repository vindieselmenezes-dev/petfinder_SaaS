<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';



if (!isset($_SESSION["usuario_id"])) {
    header('Location: ' . Url::pagina('login.php'));
    exit;
}

// Ação que altera dados: só aceita POST com token CSRF (nunca por link GET).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Url::redirecionar(Url::pagina('meus_produtos.php'));
    exit;
}

Middleware::exigirCsrfValido();

require_once "../../app/Controllers/ProdutoController.php";

$produtoController = new ProdutoController();
$imagemController = new ProdutoImagemController();
$usuarioId  = (int) $_SESSION["usuario_id"];

$produtoId = (int) ($_POST["produto_id"] ?? 0);
$imagemId  = (int) ($_POST["imagem_id"] ?? 0);

/*
|--------------------------------------------------------------------------
| Confirma que o produto pertence a uma empresa do usuário logado
|--------------------------------------------------------------------------
*/

$produto = $produtoController->buscarPorId($produtoId);

if ($produto !== null && (int) $produto["empresa_usuario_id"] === $usuarioId) {
    $imagemController->excluirImagem($imagemId, $produtoId);
}

header("Location: editar_produto.php?id=" . $produtoId);
exit;
