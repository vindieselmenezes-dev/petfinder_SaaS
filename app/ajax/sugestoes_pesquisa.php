<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/database.php';

$pdo = Database::conectar();

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo json_encode([]);
    exit;
}

$termo = '%' . $q . '%';

try {
    $sql = "
        SELECT DISTINCT termo FROM (
            SELECT p.nome AS termo FROM pets p WHERE LOWER(p.nome) LIKE LOWER(:termo1)
            UNION
            SELECT nome AS termo FROM especies WHERE LOWER(nome) LIKE LOWER(:termo2)
            UNION
            SELECT nome AS termo FROM racas WHERE LOWER(nome) LIKE LOWER(:termo3)
            UNION
            SELECT e.nome_fantasia AS termo FROM empresas e WHERE e.ativo = 1 AND LOWER(e.nome_fantasia) LIKE LOWER(:termo4)
        ) t
        ORDER BY termo
        LIMIT 10
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':termo1' => $termo,
        ':termo2' => $termo,
        ':termo3' => $termo,
        ':termo4' => $termo
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode($rows);
} catch (PDOException $e) {
    http_response_code(500);
    // Em ambiente de desenvolvimento, retorne a mensagem de erro para facilitar debug
    echo json_encode(['error' => $e->getMessage()]);
}
