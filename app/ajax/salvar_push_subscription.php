<?php

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Helpers/Csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false]);
    exit;
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!Csrf::validar($token)) {
    http_response_code(419);
    echo json_encode(['sucesso' => false, 'erro' => 'CSRF']);
    exit;
}

$dados = json_decode(file_get_contents('php://input'), true);
if (!is_array($dados) || empty($dados['endpoint']) || empty($dados['keys']['p256dh']) || empty($dados['keys']['auth'])) {
    http_response_code(422);
    echo json_encode(['sucesso' => false, 'erro' => 'Assinatura inválida']);
    exit;
}

$pdo = Database::conectar();
$stmt = $pdo->prepare(
    'INSERT INTO push_subscriptions (usuario_id, endpoint, p256dh, auth)
     VALUES (:usuario_id, :endpoint, :p256dh, :auth)
     ON DUPLICATE KEY UPDATE usuario_id = VALUES(usuario_id), p256dh = VALUES(p256dh), auth = VALUES(auth), ativo = 1'
);
$stmt->execute([
    ':usuario_id' => (int) $_SESSION['usuario_id'],
    ':endpoint' => (string) $dados['endpoint'],
    ':p256dh' => (string) $dados['keys']['p256dh'],
    ':auth' => (string) $dados['keys']['auth'],
]);
echo json_encode(['sucesso' => true]);
