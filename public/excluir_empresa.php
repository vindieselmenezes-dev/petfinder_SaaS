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

$empresaId = (int) ($_GET["id"] ?? 0);

if ($empresaId <= 0) {
    header("Location: minhas_empresas.php");
    exit;
}

if ($controller->excluir($empresaId, $usuarioId)) {
    $_SESSION["sucesso_empresa"] = "Empresa excluída com sucesso!";
} else {
    $_SESSION["erro_empresa"] = "Não foi possível excluir a empresa.";
}

header("Location: minhas_empresas.php");
exit;
