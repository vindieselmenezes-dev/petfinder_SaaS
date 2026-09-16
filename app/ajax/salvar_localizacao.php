<?php

declare(strict_types=1);


require_once __DIR__ . '/../bootstrap.php';
header('Content-Type: application/json');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(["sucesso" => false, "erro" => "Não autenticado"]);
    exit;
}

$dados = json_decode(file_get_contents('php://input'), true);

$latitude  = isset($dados['latitude']) ? (float) $dados['latitude'] : null;
$longitude = isset($dados['longitude']) ? (float) $dados['longitude'] : null;

if ($latitude === null || $longitude === null || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
    http_response_code(400);
    echo json_encode(["sucesso" => false, "erro" => "Coordenadas inválidas"]);
    exit;
}

$usuarioModel = new Usuario();
$ok = $usuarioModel->salvarLocalizacao(Auth::id(), $latitude, $longitude);

echo json_encode(["sucesso" => $ok]);
