<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

$carrinho = $_SESSION['carrinho'] ?? [];
$total = array_sum($carrinho);

echo json_encode([
    "logado" => isset($_SESSION["usuario_id"]),
    "total" => $total,
]);
