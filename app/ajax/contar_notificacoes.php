<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION["usuario_id"])) {
    echo json_encode(["logado" => false, "total" => 0]);
    exit;
}

require_once __DIR__ . '/../Controllers/NotificacaoController.php';

$controller = new NotificacaoController();

echo json_encode([
    "logado" => true,
    "total"  => $controller->contarNaoLidas((int) $_SESSION["usuario_id"])
]);
