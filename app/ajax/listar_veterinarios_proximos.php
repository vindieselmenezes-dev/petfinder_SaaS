<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['localizacao' => false, 'veterinarios' => []]);
    exit;
}

require_once __DIR__ . '/../Models/Veterinario.php';

$veterinarios = (new Veterinario())->listarProximos((int) $_SESSION['usuario_id']);

echo json_encode([
    'localizacao' => true,
    'veterinarios' => $veterinarios,
], JSON_UNESCAPED_UNICODE);