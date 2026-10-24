<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

Middleware::exigirLogin();

// Ação que altera dados: só aceita POST com token CSRF (nunca por link GET).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Url::redirecionar(Url::pagina('meus_pets.php'));
    exit;
}

Middleware::exigirCsrfValido();

$controller = new PetController();
$usuarioId  = Auth::id();

$petId = (int) ($_POST["id"] ?? 0);

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
