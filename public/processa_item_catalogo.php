<?php
// Este arquivo foi substituído pelo fluxo nativo de produtos
// (ProdutoController::cadastrar, chamado por cadastrar_produto.php).
// Mantido só como redirecionamento pra não quebrar links salvos antigos.
session_start();
$empresaId = (int)($_POST['empresa_id'] ?? $_GET['empresa_id'] ?? 0);
header("Location: meus_produtos.php?empresa_id=" . $empresaId);
exit;
