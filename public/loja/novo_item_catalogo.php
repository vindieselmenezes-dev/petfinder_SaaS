<?php
// Este arquivo foi substituído pelo fluxo nativo de produtos
// (cadastrar_produto.php / meus_produtos.php), que é mais completo
// (estoque, imagens, SKU, código de barras). Mantido só como
// redirecionamento pra não quebrar links salvos antigos.
require_once __DIR__ . '/../../app/bootstrap.php';

$empresaId = (int)($_GET['empresa_id'] ?? 0);
header('Location: ' . Url::pagina('meus_produtos.php') . '?empresa_id=' . $empresaId);
exit;
