<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../app/Models/Pedido.php';
require_once __DIR__ . '/../app/Helpers/Csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['usuario_id']) || !in_array($_SESSION['perfil_tipo'] ?? '', ['administrador', 'admin'], true) || !Csrf::validar($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Ação não autorizada.');
}

$ok = (new Pedido())->atualizarStatus((int) ($_POST['pedido_id'] ?? 0), (string) ($_POST['status'] ?? ''), trim((string) ($_POST['observacao'] ?? '')));
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'meus_pedidos.php') . ($ok ? '' : (str_contains($_SERVER['HTTP_REFERER'] ?? '', '?') ? '&' : '?') . 'erro_status=1'));
exit;
