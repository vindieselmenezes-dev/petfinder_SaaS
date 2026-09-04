<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Controllers/ProdutoController.php';
require_once __DIR__ . '/../app/Helpers/Csrf.php';
require_once __DIR__ . '/../app/Helpers/EmpresaAcesso.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['usuario_id']) || !Csrf::validar($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Ação não autorizada.');
}

$empresaId = (int) ($_POST['empresa_id'] ?? 0);
$produtoId = (int) ($_POST['produto_id'] ?? 0);
$pdo = Database::conectar();
if (!EmpresaAcesso::temAcesso($pdo, $empresaId, (int) $_SESSION['usuario_id'])) {
    http_response_code(403);
    exit('Ação não autorizada.');
}

$ok = (new ProdutoController())->definirDestaque($produtoId, $empresaId, (bool) ($_POST['destaque'] ?? false));
$_SESSION[$ok ? 'sucesso_produto' : 'erro_produto'] = $ok ? 'Destaque removido.' : 'Não foi possível alterar o destaque.';
header('Location: meus_produtos.php?empresa_id=' . $empresaId);
exit;
