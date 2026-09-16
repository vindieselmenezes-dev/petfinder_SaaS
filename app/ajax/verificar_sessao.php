<?php

declare(strict_types=1);


require_once __DIR__ . '/../bootstrap.php';
header('Content-Type: application/json');

echo json_encode([
    "logado" => Auth::check(),
    "nome"   => Auth::check() ? Auth::nome() : null
]);
