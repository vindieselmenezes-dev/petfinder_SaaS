<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

Middleware::exigirLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Url::redirecionar(Url::pagina('meus_pets.php'));
    exit;
}

Middleware::exigirCsrfValido();

$petId = (int) ($_POST['pet_id'] ?? 0);
$pet = (new PetController())->buscarPorId($petId);

// Só o tutor do pet registra vacinas.
if (!$pet || (int) $pet['usuario_id'] !== Auth::id()) {
    Flash::erro('Pet não encontrado.');
    Url::redirecionar(Url::pagina('meus_pets.php'));
    exit;
}

$erro = (new PetVacinaController())->registrar($petId, $_POST, Auth::id());

if ($erro === null) {
    Flash::sucesso('Vacina registrada com sucesso!');
} else {
    Flash::erro($erro);
}

Url::redirecionar(Url::pagina('vacinas_pet.php') . '?id=' . $petId);
exit;
