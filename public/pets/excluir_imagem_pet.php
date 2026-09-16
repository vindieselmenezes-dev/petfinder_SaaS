<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';



if (!isset($_SESSION["usuario_id"])) {
    header('Location: ' . Url::pagina('login.php'));
    exit;
}

require_once "../../app/Controllers/PetController.php";

$petController = new PetController();
$usuarioId = (int) $_SESSION["usuario_id"];

$petId    = (int) ($_GET["pet_id"] ?? 0);
$imagemId = (int) ($_GET["imagem_id"] ?? 0);

/*
|--------------------------------------------------------------------------
| Confirma que o pet pertence ao usuário logado antes de excluir
|--------------------------------------------------------------------------
*/

$pet = $petController->buscarPorId($petId);

if ($pet !== null && (int) $pet["usuario_id"] === $usuarioId) {
    $petController->excluirImagem($imagemId, $petId);
}

header("Location: editar_pet.php?id=" . $petId);
exit;
