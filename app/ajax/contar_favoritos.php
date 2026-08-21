<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION["usuario_id"])) {
    echo json_encode(["logado" => false, "total" => 0]);
    exit;
}

require_once __DIR__ . '/../Models/Favorito.php';
require_once __DIR__ . '/../Models/FavoritoProduto.php';

$usuarioId = (int) $_SESSION["usuario_id"];

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
