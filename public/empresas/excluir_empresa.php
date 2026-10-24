<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';



if (!isset($_SESSION["usuario_id"])) {
    Url::redirecionar(Url::pagina('login.php'));
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
$usuarioId  = (int) $_SESSION["usuario_id"];

$empresaId = (int) ($_POST["id"] ?? 0);

if ($empresaId <= 0) {
    Url::redirecionar(Url::pagina('minhas_empresas.php'));
    exit;
}

if ($controller->excluir($empresaId, $usuarioId)) {
    Flash::sucesso("Empresa excluída com sucesso!");
} else {
    Flash::erro("Não foi possível excluir a empresa.");
}

Url::redirecionar(Url::pagina('minhas_empresas.php'));
exit;
