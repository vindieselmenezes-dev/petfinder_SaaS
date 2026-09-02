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
$qNormalizado = mb_strtolower($q);

try {
    $sql = "
        SELECT tipo, id, texto FROM (
            SELECT 'pet' AS tipo, p.id, p.nome AS texto
            FROM pets p
            WHERE LOWER(p.nome) LIKE LOWER(:termo1)
            UNION ALL
            SELECT 'especie', id, nome FROM especies
            WHERE LOWER(nome) LIKE LOWER(:termo2) AND ativo = 1
            UNION ALL
            SELECT 'raca', id, nome FROM racas
            WHERE LOWER(nome) LIKE LOWER(:termo3) AND ativo = 1
            UNION ALL
            SELECT 'empresa', e.id, e.nome_fantasia FROM empresas e
            WHERE e.ativo = 1 AND LOWER(e.nome_fantasia) LIKE LOWER(:termo4)
            UNION ALL
            SELECT 'produto', p.id, p.nome FROM produtos p
            WHERE p.ativo = 1 AND LOWER(p.nome) LIKE LOWER(:termo5)
            UNION ALL
                        SELECT CASE
                                WHEN c.id = 9 THEN 'topico_produto'
                                WHEN c.id = 8 THEN 'topico_adocao'
                                ELSE 'topico_servico'
                        END AS tipo, c.id, c.nome
                        FROM categorias c
                        WHERE c.ativo = 1
                            AND (
                                    LOWER(c.nome) LIKE LOWER(:termo6)
                                      OR (c.id = 9 AND :q_produto IN ('produto', 'produtos', 'marketplace'))
                                      OR (c.id = 8 AND :q_adocao IN ('adocao', 'adoção', 'pets'))
                            )
                        UNION ALL
                        SELECT 'subcategoria_produto', sc.id, sc.nome
                        FROM subcategorias sc
                        WHERE sc.ativo = 1 AND LOWER(sc.nome) LIKE LOWER(:termo7)
                        UNION ALL
                        SELECT 'marca_produto', m.id, m.nome
                        FROM marcas m
                        WHERE m.ativo = 1 AND LOWER(m.nome) LIKE LOWER(:termo8)
                        UNION ALL
                        SELECT 'cidade', 0, cidade FROM enderecos
                        WHERE cidade IS NOT NULL AND cidade <> ''
                            AND LOWER(cidade) LIKE LOWER(:termo9)
        ) t
        ORDER BY texto
        LIMIT 10
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':termo1' => $termo,
        ':termo2' => $termo,
        ':termo3' => $termo,
        ':termo4' => $termo,
        ':termo5' => $termo,
        ':termo6' => $termo,
        ':termo7' => $termo,
        ':termo8' => $termo,
        ':termo9' => $termo,
        ':q_produto' => $qNormalizado,
        ':q_adocao' => $qNormalizado
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rows);
} catch (PDOException $e) {
    http_response_code(500);
    // Em ambiente de desenvolvimento, retorne a mensagem de erro para facilitar debug
    echo json_encode(['error' => $e->getMessage()]);
}
