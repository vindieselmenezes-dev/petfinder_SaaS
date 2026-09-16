<?php

declare(strict_types=1);


require_once __DIR__ . '/../bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
try {
    echo json_encode((new ProdutoController())->listarDestaques(4), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['erro' => 'Não foi possível carregar os destaques.']);
}
