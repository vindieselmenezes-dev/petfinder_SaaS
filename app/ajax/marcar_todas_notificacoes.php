<?php

declare(strict_types=1);


require_once __DIR__ . '/../bootstrap.php';
header('Content-Type: application/json');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(["sucesso" => false]);
    exit;
}

$controller = new NotificacaoController();
$usuarioId  = Auth::id();

$sucesso = $controller->marcarTodasComoLidas($usuarioId);

echo json_encode(["sucesso" => $sucesso]);
