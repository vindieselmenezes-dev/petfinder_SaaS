<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';



if (!isset($_SESSION["usuario_id"])) {
    header('Location: ' . Url::pagina('login.php'));
    exit;
}

require_once "../../app/Controllers/EmpresaController.php";
require_once "../../app/Controllers/ProdutoController.php";
require_once "../../app/Helpers/EmpresaAcesso.php";
require_once "../../app/Models/Usuario.php";

$empresaController = new EmpresaController();
$produtoController  = new ProdutoController();
$pdo = Database::conectar();

$usuarioId = (int) $_SESSION["usuario_id"];
$produtoId = (int) ($_GET["id"] ?? 0);
$empresaId = (int) ($_GET["empresa_id"] ?? 0);

/*
|--------------------------------------------------------------------------
| Confirma que o usuário tem acesso à empresa (dono ou equipe) antes de
| excluir o produto
|--------------------------------------------------------------------------
*/

$empresa = $empresaController->buscarPorId($empresaId);

if ($empresa !== null && EmpresaAcesso::temAcesso($pdo, $empresaId, $usuarioId)) {

    if ($produtoController->excluir($produtoId, $empresaId)) {
        Flash::sucesso("Produto excluído com sucesso!");
    } else {
        Flash::erro("Não foi possível excluir o produto.");
    }

}

header("Location: meus_produtos.php?empresa_id=" . $empresaId);
exit;
