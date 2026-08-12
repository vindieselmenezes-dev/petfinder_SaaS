<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../app/Controllers/EmpresaController.php";
require_once "../app/Controllers/ProdutoController.php";

$empresaController = new EmpresaController();
$produtoController  = new ProdutoController();

$usuarioId = (int) $_SESSION["usuario_id"];
$produtoId = (int) ($_GET["id"] ?? 0);
$empresaId = (int) ($_GET["empresa_id"] ?? 0);

/*
|--------------------------------------------------------------------------
| Confirma que a empresa pertence ao usuário antes de excluir o produto
|--------------------------------------------------------------------------
*/

$empresa = $empresaController->buscarPorId($empresaId);

if ($empresa !== null && (int) $empresa["usuario_id"] === $usuarioId) {

    if ($produtoController->excluir($produtoId, $empresaId)) {
        $_SESSION["sucesso_produto"] = "Produto excluído com sucesso!";
    } else {
        $_SESSION["erro_produto"] = "Não foi possível excluir o produto.";
    }

}

header("Location: meus_produtos.php?empresa_id=" . $empresaId);
exit;
