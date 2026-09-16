<?php

declare(strict_types=1);


require_once __DIR__ . '/../bootstrap.php';
header('Content-Type: application/json');

$pet = new Pet();

$especie = isset($_GET["especie"])
    ? (int) $_GET["especie"]
    : 0;

if ($especie <= 0) {

    echo json_encode([]);
    exit;

}

$racas = $pet->listarRacas($especie);

echo json_encode($racas);