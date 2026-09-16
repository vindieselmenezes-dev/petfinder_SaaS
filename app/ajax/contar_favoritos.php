<?php

declare(strict_types=1);


require_once __DIR__ . '/../bootstrap.php';
header('Content-Type: application/json');

if (!Auth::check()) {
    echo json_encode(["logado" => false, "total" => 0]);
    exit;
}

$usuarioId = Auth::id();

$favoritoPet     = new Favorito();
$favoritoProduto = new FavoritoProduto();

$totalPets     = count($favoritoPet->listarPorUsuario($usuarioId));
$totalProdutos = count($favoritoProduto->listarPorUsuario($usuarioId));

echo json_encode([
    "logado"         => true,
    "total"          => $totalPets + $totalProdutos,
    "total_pets"     => $totalPets,
    "total_produtos" => $totalProdutos,
]);
