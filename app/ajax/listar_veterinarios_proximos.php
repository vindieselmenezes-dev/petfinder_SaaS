<?php

declare(strict_types=1);


require_once __DIR__ . '/../bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['localizacao' => false, 'veterinarios' => []]);
    exit;
}

$veterinarios = (new Veterinario())->listarProximos(Auth::id());

echo json_encode([
    'localizacao' => true,
    'veterinarios' => $veterinarios,
], JSON_UNESCAPED_UNICODE);