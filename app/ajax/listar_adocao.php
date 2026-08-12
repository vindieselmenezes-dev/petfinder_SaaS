<?php

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../Controllers/PetController.php';

$controller = new PetController();

$busca      = trim($_GET['busca'] ?? '');
$cidade     = trim($_GET['cidade'] ?? '');
$especieId  = isset($_GET['especie_id']) ? (int) $_GET['especie_id'] : 0;
$racaId     = isset($_GET['raca_id']) ? (int) $_GET['raca_id'] : 0;
$sexo       = trim($_GET['sexo'] ?? '');
$cor        = trim($_GET['cor'] ?? '');
$castrado   = isset($_GET['castrado']) ? (int) $_GET['castrado'] : -1;
$idadeMin   = isset($_GET['idade_min']) ? (int) $_GET['idade_min'] : 0;
$idadeMax   = isset($_GET['idade_max']) ? (int) $_GET['idade_max'] : 0;
$pesoMin    = isset($_GET['peso_min']) ? (float) $_GET['peso_min'] : 0.0;
$pesoMax    = isset($_GET['peso_max']) ? (float) $_GET['peso_max'] : 0.0;
$alturaMin  = isset($_GET['altura_min']) ? (float) $_GET['altura_min'] : 0.0;
$alturaMax  = isset($_GET['altura_max']) ? (float) $_GET['altura_max'] : 0.0;
$status     = trim($_GET['status'] ?? 'Para Adoção');
$ordem      = trim($_GET['ordem'] ?? 'criado_em');
$direcao    = trim($_GET['direcao'] ?? 'DESC');

/*
|--------------------------------------------------------------------------
| Ignora o valor "placeholder" do select de cidade
|--------------------------------------------------------------------------
*/

if ($cidade === 'Selecione sua cidade') {
    $cidade = '';
}

$pets = $controller->buscarAdocaoPublico(
    $busca,
    $cidade,
    $especieId,
    $racaId,
    $sexo,
    $cor,
    $castrado,
    $idadeMin,
    $idadeMax,
    $pesoMin,
    $pesoMax,
    $alturaMin,
    $alturaMax,
    $status,
    $ordem,
    $direcao
);

echo json_encode($pets);
