<?php

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

$termo = trim($_GET['termo'] ?? '');

/*
|--------------------------------------------------------------------------
| Exige pelo menos 2 caracteres, pra não sobrecarregar o banco a cada tecla
|--------------------------------------------------------------------------
*/

if (mb_strlen($termo) < 2) {
    echo json_encode([]);
    exit;
}

$pdo = Database::conectar();
$curinga = '%' . $termo . '%';

$sugestoes = [];

/*
|--------------------------------------------------------------------------
| 1. Nomes de pets disponíveis para adoção
|--------------------------------------------------------------------------
*/

$sqlPets = "
    SELECT DISTINCT nome
    FROM pets
    WHERE nome LIKE :termo
      AND status = 'Para Adoção'
    ORDER BY nome
    LIMIT 5
";

$stmt = $pdo->prepare($sqlPets);
$stmt->execute([':termo' => $curinga]);

foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $nome) {
    $sugestoes[] = ['tipo' => 'pet', 'texto' => $nome];
}

/*
|--------------------------------------------------------------------------
| 2. Espécies
|--------------------------------------------------------------------------
*/

$sqlEspecies = "
    SELECT DISTINCT nome
    FROM especies
    WHERE nome LIKE :termo
      AND ativo = 1
    ORDER BY nome
    LIMIT 5
";

$stmt = $pdo->prepare($sqlEspecies);
$stmt->execute([':termo' => $curinga]);

foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $nome) {
    $sugestoes[] = ['tipo' => 'especie', 'texto' => $nome];
}

/*
|--------------------------------------------------------------------------
| 3. Raças
|--------------------------------------------------------------------------
*/

$sqlRacas = "
    SELECT DISTINCT nome
    FROM racas
    WHERE nome LIKE :termo
      AND ativo = 1
    ORDER BY nome
    LIMIT 5
";

$stmt = $pdo->prepare($sqlRacas);
$stmt->execute([':termo' => $curinga]);

foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $nome) {
    $sugestoes[] = ['tipo' => 'raca', 'texto' => $nome];
}

/*
|--------------------------------------------------------------------------
| 4. Empresas cadastradas
|--------------------------------------------------------------------------
*/

$sqlEmpresas = "
    SELECT DISTINCT nome_fantasia
    FROM empresas
    WHERE nome_fantasia LIKE :termo
      AND ativo = 1
    ORDER BY nome_fantasia
    LIMIT 5
";

$stmt = $pdo->prepare($sqlEmpresas);
$stmt->execute([':termo' => $curinga]);

foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $nome) {
    $sugestoes[] = ['tipo' => 'empresa', 'texto' => $nome];
}

/*
|--------------------------------------------------------------------------
| 5. Cidades (só de quem tem endereço cadastrado)
|--------------------------------------------------------------------------
*/

$sqlCidades = "
    SELECT DISTINCT cidade
    FROM enderecos
    WHERE cidade LIKE :termo
      AND cidade IS NOT NULL
      AND cidade != ''
    ORDER BY cidade
    LIMIT 5
";

$stmt = $pdo->prepare($sqlCidades);
$stmt->execute([':termo' => $curinga]);

foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $nome) {
    $sugestoes[] = ['tipo' => 'cidade', 'texto' => $nome];
}

/*
|--------------------------------------------------------------------------
| Remove duplicatas (ex: "Labrador" como raça, mas também batendo em
| outro campo) e limita o total geral a 8 sugestões
|--------------------------------------------------------------------------
*/

$vistos = [];
$resultadoFinal = [];

foreach ($sugestoes as $sugestao) {

    $chave = mb_strtolower($sugestao['tipo'] . '|' . $sugestao['texto']);

    if (isset($vistos[$chave])) {
        continue;
    }

    $vistos[$chave] = true;
    $resultadoFinal[] = $sugestao;

    if (count($resultadoFinal) >= 8) {
        break;
    }

}

echo json_encode($resultadoFinal);
