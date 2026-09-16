<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';



if (!isset($_SESSION["usuario_id"])) {
    header('Location: ' . Url::pagina('login.php'));
    exit;
}

require_once "../../app/Controllers/EmpresaController.php";

$controller = new EmpresaController();
$usuarioId  = (int) $_SESSION["usuario_id"];

$empresaId = (int) ($_GET["id"] ?? 0);

if ($empresaId <= 0) {
    header('Location: ' . Url::pagina('minhas_empresas.php'));
    exit;
}

if ($controller->excluir($empresaId, $usuarioId)) {
    Flash::sucesso("Empresa excluída com sucesso!");
} else {
    Flash::erro("Não foi possível excluir a empresa.");
}

header('Location: ' . Url::pagina('minhas_empresas.php'));
exit;
