<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';



if (!isset($_SESSION["usuario_id"])) {
    header('Location: ' . Url::pagina('login.php'));
    exit;
}

// Ação que altera dados: só aceita POST com token CSRF (nunca por link GET).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Url::redirecionar(Url::pagina('minhas_empresas.php'));
    exit;
}

Middleware::exigirCsrfValido();

require_once "../../app/Controllers/EmpresaController.php";

$controller = new EmpresaController();
$galeriaController = new EmpresaGaleriaController();
$usuarioId  = (int) $_SESSION["usuario_id"];

$empresaId = (int) ($_POST["empresa_id"] ?? 0);
$imagemId  = (int) ($_POST["imagem_id"] ?? 0);

/*
|--------------------------------------------------------------------------
| Confirma que a empresa pertence ao usuário antes de excluir a imagem
|--------------------------------------------------------------------------
*/

$empresa = $controller->buscarPorId($empresaId);

if ($empresa !== null && (int) $empresa["usuario_id"] === $usuarioId) {
    $galeriaController->excluirImagemGaleria($imagemId, $empresaId);
}

header("Location: editar_empresa.php?id=" . $empresaId);
exit;
