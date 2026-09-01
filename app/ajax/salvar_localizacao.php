<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION["usuario_id"])) {
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

require_once __DIR__ . '/../Models/Usuario.php';

$usuarioModel = new Usuario();
$ok = $usuarioModel->salvarLocalizacao((int) $_SESSION["usuario_id"], $latitude, $longitude);

echo json_encode(["sucesso" => $ok]);
