<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION["usuario_id"])) {
    http_response_code(401);
    echo json_encode(["sucesso" => false]);
    exit;
}

require_once __DIR__ . '/../Controllers/NotificacaoController.php';

$controller = new NotificacaoController();
$usuarioId  = (int) $_SESSION["usuario_id"];

$sucesso = $controller->marcarTodasComoLidas($usuarioId);

echo json_encode(["sucesso" => $sucesso]);
