<?php

declare(strict_types=1);

require_once '../app/Controllers/ProdutoController.php';

session_start();

$controller = new ProdutoController();

$carrinho = $_SESSION['carrinho'] ?? [];
$itens = [];
$subtotal = 0.0;

foreach ($carrinho as $produtoId => $qtd) {
    $produto = $controller->buscarPorId((int) $produtoId);
    if (!$produto) continue;
    $preco = !empty($produto['preco_promocional']) ? (float)$produto['preco_promocional'] : (float)$produto['preco_venda'];
    $total = $preco * $qtd;
    $itens[] = ['produto' => $produto, 'quantidade' => $qtd, 'total' => $total];
    $subtotal += $total;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Carrinho - PetFinder Brasil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <main class="container my-5">
        <h1 class="mb-4">Carrinho</h1>

        <?php if (empty($itens)): ?>
            <div class="alert alert-info">Seu carrinho está vazio.</div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Preço</th>
                        <th>Quantidade</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['produto']['nome']) ?></td>
                            <td>R$ <?= number_format((float)$item['produto']['preco_venda'],2,',','.') ?></td>
                            <td><?= (int)$item['quantidade'] ?></td>
                            <td>R$ <?= number_format((float)$item['total'],2,',','.') ?></td>
                            <td>
                                <a href="remover_carrinho.php?produto_id=<?= (int)$item['produto']['id'] ?>&apagar=1" class="btn btn-sm btn-danger">Remover</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="d-flex justify-content-end">
                <div>
                    <p class="mb-1">Subtotal: <strong>R$ <?= number_format($subtotal,2,',','.') ?></strong></p>
                    <a href="#" class="btn btn-primary">Finalizar Compra</a>
                </div>
            </div>
        <?php endif; ?>

    </main>
</body>
</html>
