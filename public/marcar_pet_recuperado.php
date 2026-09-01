<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../app/Controllers/PetController.php";
require_once "../app/Models/Usuario.php";

$controller = new PetController();
$pdo        = Database::conectar();
$usuarioId  = (int) $_SESSION["usuario_id"];

$petId = (int) ($_GET["id"] ?? 0);

if ($petId <= 0) {
    header("Location: meus_pets.php");
    exit;
}

$pet = $controller->buscarPorId($petId);

if (!$pet || (int) $pet['usuario_id'] !== $usuarioId) {
    $_SESSION["erro_pet"] = "Pet não encontrado ou você não tem permissão.";
    header("Location: meus_pets.php");
    exit;
}

// Volta o status do pet pro normal e encerra qualquer alerta ativo dele
$controller->atualizarStatus($petId, 'Com Tutor', $usuarioId, 'Marcado como recuperado pelo tutor');

$stmt = $pdo->prepare("UPDATE pet_alertas_perdidos SET status = 'Encontrado' WHERE pet_id = ? AND status = 'Ativo'");
$stmt->execute([$petId]);

$_SESSION["sucesso_pet"] = "Que ótima notícia! " . htmlspecialchars($pet['nome']) . " foi marcado como recuperado.";

header("Location: meus_pets.php");
exit;
