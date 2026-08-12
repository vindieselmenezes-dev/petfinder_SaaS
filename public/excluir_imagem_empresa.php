<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../app/Controllers/EmpresaController.php";

$controller = new EmpresaController();
$usuarioId  = (int) $_SESSION["usuario_id"];

$empresaId = (int) ($_GET["empresa_id"] ?? 0);
$imagemId  = (int) ($_GET["imagem_id"] ?? 0);

/*
|--------------------------------------------------------------------------
| Confirma que a empresa pertence ao usuário antes de excluir a imagem
|--------------------------------------------------------------------------
*/

$empresa = $controller->buscarPorId($empresaId);

if ($empresa !== null && (int) $empresa["usuario_id"] === $usuarioId) {
    $controller->excluirImagemGaleria($imagemId, $empresaId);
}

header("Location: editar_empresa.php?id=" . $empresaId);
exit;
