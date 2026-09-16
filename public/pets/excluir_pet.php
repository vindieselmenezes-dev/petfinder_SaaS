<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

Middleware::exigirLogin();

$controller = new PetController();
$usuarioId  = Auth::id();

$petId = (int) ($_GET["id"] ?? 0);

if ($petId <= 0) {
    header('Location: ' . Url::pagina('meus_pets.php'));
    exit;
}

if ($controller->excluir($petId, $usuarioId)) {
    Flash::sucesso("Pet excluído com sucesso!");
} else {
    Flash::erro("Não foi possível excluir o pet.");
}

header('Location: ' . Url::pagina('meus_pets.php'));
exit;
