<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

if (!Auth::check()) {
    echo json_encode(["logado" => false, "total" => 0]);
    exit;
}

$controller = new NotificacaoController();

// Id do alerta de pet (perdido/encontrado) mais recente ainda não lido.
// A página usa isso para tocar o latido quando surge um alerta novo.
$alertaPet = 0;
try {
    $stmt = Database::conectar()->prepare(
        "SELECT COALESCE(MAX(id), 0) FROM notificacoes
         WHERE usuario_id = ? AND lida = 0
           AND (link LIKE '%pets_encontrados%' OR link LIKE '%pets_perdidos%')"
    );
    $stmt->execute([Auth::id()]);
    $alertaPet = (int) $stmt->fetchColumn();
} catch (Throwable $erro) {
    error_log('[contar_notificacoes] ' . $erro->getMessage());
}

echo json_encode([
    "logado" => true,
    "total"  => $controller->contarNaoLidas(Auth::id()),
    "alerta_pet" => $alertaPet
]);
