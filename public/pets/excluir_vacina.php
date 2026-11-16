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
$doseId = (int) ($_POST['dose_id'] ?? 0);
$pet = (new PetController())->buscarPorId($petId);

if (!$pet || (int) $pet['usuario_id'] !== Auth::id()) {
    Flash::erro('Pet não encontrado.');
    Url::redirecionar(Url::pagina('meus_pets.php'));
    exit;
}

if ((new PetVacinaController())->excluir($doseId, $petId)) {
    Flash::sucesso('Registro de vacina removido.');
} else {
    Flash::erro('Não foi possível remover o registro.');
}

Url::redirecionar(Url::pagina('vacinas_pet.php') . '?id=' . $petId);
exit;
