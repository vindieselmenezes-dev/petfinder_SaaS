<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../Models/MetricaEmpresa.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false]);
    exit;
}

$dados = json_decode(file_get_contents('php://input'), true);
$empresaId = (int) ($dados['empresa_id'] ?? 0);
$tipo = (string) ($dados['tipo'] ?? '');
$pagina = substr((string) ($dados['pagina'] ?? 'publica'), 0, 120);
$referenciaId = isset($dados['referencia_id']) ? (int) $dados['referencia_id'] : null;

if ($empresaId <= 0 || !in_array($tipo, ['clique', 'conversao'], true)) {
    http_response_code(422);
    echo json_encode(['sucesso' => false]);
    exit;
}

$model = new MetricaEmpresa();
$sucesso = $model->registrar($empresaId, $tipo, $pagina, $referenciaId, $_SESSION['usuario_id'] ?? null);
echo json_encode(['sucesso' => $sucesso]);
