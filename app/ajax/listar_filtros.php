<?php

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../Controllers/PetController.php';

$controller = new PetController();

$especies = $controller->listarEspecies();
$racasPorEspecie = [];

foreach ($especies as $especie) {
    $racasPorEspecie[(int) $especie['id']] = $controller->listarRacas((int) $especie['id']);
}

echo json_encode([
    'especies' => $especies,
    'racas' => $racasPorEspecie,
]);
