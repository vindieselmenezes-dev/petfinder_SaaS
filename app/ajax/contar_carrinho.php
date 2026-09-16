<?php

declare(strict_types=1);


require_once __DIR__ . '/../bootstrap.php';
header('Content-Type: application/json');

$carrinho = $_SESSION['carrinho'] ?? [];
$total = array_sum($carrinho);

echo json_encode([
    "logado" => Auth::check(),
    "total" => $total,
]);
