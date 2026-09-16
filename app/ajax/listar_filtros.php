<?php

declare(strict_types=1);


require_once __DIR__ . '/../bootstrap.php';
header('Content-Type: application/json');

$controller = new PetController();

$especies = $controller->listarEspecies();
$racasPorEspecie = [];

foreach ($especies as $especie) {
    $racasPorEspecie[(int) $especie['id']] = $controller->listarRacas((int) $especie['id']);
}

echo json_encode([
    'especies' => $especies,
    'racas' => $racasPorEspecie,
    'cidades' => $controller->listarCidadesComPets(),
]);
