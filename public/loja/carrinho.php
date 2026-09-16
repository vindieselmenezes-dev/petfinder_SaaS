<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';


require_once '../../app/Controllers/ProdutoController.php';
require_once '../../app/Helpers/Csrf.php';


$controller = new ProdutoController();

$carrinho = $_SESSION['carrinho'] ?? [];
$itens = [];
$subtotal = 0.0;

foreach ($carrinho as $produtoId => $qtd) {
    $produto = $controller->buscarPorId((int) $produtoId);
    if (!$produto) continue;
    $preco = !empty($produto['preco_promocional']) ? (float)$produto['preco_promocional'] : (float)$produto['preco_venda'];
    $total = $preco * $qtd;
    $itens[] = ['produto' => $produto, 'quantidade' => $qtd, 'total' => $total, 'preco' => $preco];
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body style="padding-top:38px;">
    <div style="position:fixed;top:0;left:0;right:0;z-index:2000;background:#f8f9fa;border-bottom:1px solid #dee2e6;padding:8px 20px;height:38px;box-sizing:border-box;"><button type="button" onclick="if(window.history.length>1){history.back();}else{window.location.href='../../index.html';}" style="background:none;border:none;color:#1B365D;cursor:pointer;font-size:14px;padding:0;" aria-label="Voltar para a página anterior">← Voltar</button></div>

    <header class="border-bottom py-3 mb-4">
        <div class="container d-flex align-items-center justify-content-between">
            <a href="../../index.html" class="d-flex align-items-center text-decoration-none">
                <img src="../../assets/img/logo.png" alt="PetFinder Brasil" height="40" class="me-2">
                <div>
                    <div class="fw-bold text-dark">PetFinder Brasil</div>
                    <small class="text-muted">Tudo para seu pet em um só lugar</small>
                </div>
            </a>
            <a href="produtos.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i>
                Continuar comprando
            </a>
        </div>
    </header>

    <main class="container my-5">
        <h1 class="mb-4"><i class="bi bi-cart3"></i> Carrinho</h1>

        <?php if (empty($itens)): ?>
            <div class="alert alert-info">
                Seu carrinho está vazio.
                <a href="produtos.php" class="alert-link">Ver produtos</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Vendido por</th>
                            <th>Preço</th>
                            <th>Quantidade</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itens as $item): ?>
                            <tr>
                                <td>
                                    <a href="produto.php?id=<?= (int)$item['produto']['id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($item['produto']['nome']) ?>
                                    </a>
                                </td>
                                <td class="text-muted small"><?= htmlspecialchars($item['produto']['empresa_nome'] ?? '') ?></td>
                                <td>R$ <?= number_format($item['preco'], 2, ',', '.') ?></td>
                                <td style="min-width:140px;">
                                    <form action="atualizar_carrinho.php" method="POST" class="d-flex align-items-center gap-2">
                                        <?= Csrf::campoHtml() ?>
                                        <input type="hidden" name="produto_id" value="<?= (int)$item['produto']['id'] ?>">
                                        <input type="hidden" name="voltar" value="carrinho.php">
                                        <input
                                            type="number"
                                            name="quantidade"
                                            value="<?= (int)$item['quantidade'] ?>"
                                            min="1"
                                            max="999"
                                            class="form-control form-control-sm"
                                            style="width:70px;"
                                        >
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Atualizar quantidade">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                    </form>
                                </td>
                                <td>R$ <?= number_format((float)$item['total'], 2, ',', '.') ?></td>
                                <td>
                                    <form action="remover_carrinho.php" method="POST" class="d-inline">
                                        <?= Csrf::campoHtml() ?>
                                        <input type="hidden" name="produto_id" value="<?= (int)$item['produto']['id'] ?>">
                                        <input type="hidden" name="apagar" value="1">
                                        <input type="hidden" name="voltar" value="carrinho.php">
                                        <button type="submit" class="btn btn-sm btn-danger">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end">
                <div class="text-end">
                    <p class="mb-2 fs-5">Subtotal: <strong>R$ <?= number_format($subtotal, 2, ',', '.') ?></strong></p>
                    <a href="checkout.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-bag-check"></i>
                        Finalizar Compra
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
