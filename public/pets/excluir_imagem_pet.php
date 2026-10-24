<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';



if (!isset($_SESSION["usuario_id"])) {
    header('Location: ' . Url::pagina('login.php'));
    exit;
}

// Ação que altera dados: só aceita POST com token CSRF (nunca por link GET).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Url::redirecionar(Url::pagina('meus_pets.php'));
    exit;
}

Middleware::exigirCsrfValido();

require_once "../../app/Controllers/PetController.php";

$petController = new PetController();
$imagemController = new PetImagemController();
$usuarioId = (int) $_SESSION["usuario_id"];

$petId    = (int) ($_POST["pet_id"] ?? 0);
$imagemId = (int) ($_POST["imagem_id"] ?? 0);

/*
|--------------------------------------------------------------------------
| Confirma que o pet pertence ao usuário logado antes de excluir
|--------------------------------------------------------------------------
*/

$pet = $petController->buscarPorId($petId);

if ($pet !== null && (int) $pet["usuario_id"] === $usuarioId) {
    $imagemController->excluirImagem($imagemId, $petId);
}

header("Location: editar_pet.php?id=" . $petId);
exit;
