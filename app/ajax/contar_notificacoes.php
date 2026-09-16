<?php

declare(strict_types=1);


require_once __DIR__ . '/../bootstrap.php';
header('Content-Type: application/json');

if (!Auth::check()) {
    echo json_encode(["logado" => false, "total" => 0]);
    exit;
}

$controller = new NotificacaoController();

echo json_encode([
    "logado" => true,
    "total"  => $controller->contarNaoLidas(Auth::id())
]);
