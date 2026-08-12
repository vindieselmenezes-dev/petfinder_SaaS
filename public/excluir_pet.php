<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../app/Controllers/PetController.php";

$controller = new PetController();
$usuarioId  = (int) $_SESSION["usuario_id"];

$petId = (int) ($_GET["id"] ?? 0);

if ($petId <= 0) {
    header("Location: meus_pets.php");
    exit;
}

if ($controller->excluir($petId, $usuarioId)) {
    $_SESSION["sucesso_pet"] = "Pet excluído com sucesso!";
} else {
    $_SESSION["erro_pet"] = "Não foi possível excluir o pet.";
}

header("Location: meus_pets.php");
exit;
