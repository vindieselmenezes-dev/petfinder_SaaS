<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../app/Controllers/EmpresaController.php';
require_once __DIR__ . '/../app/Helpers/Csrf.php';

$empresaId = (int) ($_POST['empresa_id'] ?? 0);
$nota = (int) ($_POST['nota'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario_id']) || !Csrf::validar($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Requisição inválida.');
}

$sucesso = (new EmpresaController())->avaliar($empresaId, (int) $_SESSION['usuario_id'], $nota);
$_SESSION['avaliacao_mensagem'] = $sucesso
    ? 'Avaliação registrada com sucesso.'
    : 'Você já avaliou esta empresa ou a nota é inválida.';

header('Location: empresa.php?id=' . $empresaId);
exit;