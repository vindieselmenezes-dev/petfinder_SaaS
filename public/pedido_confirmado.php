<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once "../app/Models/Pedido.php";

$pedidoModel = new Pedido();
$pedidoId = (int) ($_GET['id'] ?? 0);
$pedido = $pedidoModel->buscarPorId($pedidoId, (int) $_SESSION['usuario_id']);

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Confirmado - PetFinder Brasil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <header class="border-bottom py-3 mb-4">
        <div class="container d-flex align-items-center justify-content-between">
            <a href="../index.html" class="d-flex align-items-center text-decoration-none">
                <img src="../assets/img/logo.png" alt="PetFinder Brasil" height="40" class="me-2">
                <div>
                    <div class="fw-bold text-dark">PetFinder Brasil</div>
                    <small class="text-muted">Tudo para seu pet em um só lugar</small>
                </div>
            </a>
            <a href="produtos.php" class="btn btn-outline-primary">
                <i class="bi bi-shop"></i>
                Continuar comprando
            </a>
        </div>
    </header>

    <main class="container mb-5">

        <?php if (!$pedido): ?>

            <div class="alert alert-warning text-center py-5">
                <h2>Pedido não encontrado.</h2>
                <p class="mb-0">Verifique se o link está correto ou consulte seus pedidos.</p>
                <a href="meus_pedidos.php" class="btn btn-primary mt-3">Ver meus pedidos</a>
            </div>

        <?php else: ?>

            <div class="text-center mb-4">
                <i class="bi bi-check-circle-fill text-success" style="font-size:4rem;"></i>
                <h1 class="fw-bold mt-3">Pedido confirmado!</h1>
                <p class="text-muted">
                    Número do pedido: <strong>#<?= htmlspecialchars($pedido['numero_pedido']) ?></strong>
                </p>
                <span class="badge bg-success fs-6">
                    <i class="bi bi-check2"></i>
                    <?= htmlspecialchars($pedido['status']) ?>
                </span>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-7">

                    <div class="card mb-4">
                        <div class="card-header fw-bold">
                            <i class="bi bi-geo-alt"></i>
                            Entrega
                        </div>
                        <div class="card-body">
                            <?php if (!empty($pedido['logradouro'])): ?>
                                <p class="mb-1">
                                    <?= htmlspecialchars($pedido['logradouro']) ?>, <?= htmlspecialchars($pedido['numero'] ?? '') ?>
                                    <?php if (!empty($pedido['complemento'])): ?> - <?= htmlspecialchars($pedido['complemento']) ?><?php endif; ?>
                                </p>
                                <p class="mb-1 text-muted">
                                    <?= htmlspecialchars($pedido['bairro'] ?? '') ?> -
                                    <?= htmlspecialchars($pedido['cidade'] ?? '') ?>/<?= htmlspecialchars($pedido['estado'] ?? '') ?>
                                    <?php if (!empty($pedido['cep'])): ?> - CEP <?= htmlspecialchars($pedido['cep']) ?><?php endif; ?>
                                </p>
                                <?php if (!empty($pedido['referencia'])): ?>
                                    <p class="mb-1 text-muted small">
                                        <i class="bi bi-signpost"></i>
                                        Referência: <?= htmlspecialchars($pedido['referencia']) ?>
                                    </p>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if (!empty($pedido['previsao_entrega'])): ?>
                                <p class="mb-0 mt-2">
                                    <i class="bi bi-truck text-success"></i>
                                    Previsão de entrega: <strong><?= date('d/m/Y', strtotime($pedido['previsao_entrega'])) ?></strong>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header fw-bold">Itens</div>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($pedido['itens'] as $item): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($item['produto_nome']) ?></strong>
                                        <div class="text-muted small">
                                            Vendido por <?= htmlspecialchars($item['empresa_nome'] ?? '') ?>
                                            &middot; <?= (int) $item['quantidade'] ?>x R$ <?= number_format((float) $item['preco_unitario'], 2, ',', '.') ?>
                                        </div>
                                    </div>
                                    <span>R$ <?= number_format((float) $item['subtotal'], 2, ',', '.') ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <span>Subtotal</span>
                                <span>R$ <?= number_format((float) $pedido['valor_produtos'], 2, ',', '.') ?></span>
                            </div>
                            <?php if ((float) $pedido['valor_desconto'] > 0): ?>
                                <div class="d-flex justify-content-between text-success">
                                    <span>Desconto</span>
                                    <span>- R$ <?= number_format((float) $pedido['valor_desconto'], 2, ',', '.') ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between text-muted small">
                                <span>Frete</span>
                                <span>Grátis</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fs-5 fw-bold">
                                <span>Total pago</span>
                                <span>R$ <?= number_format((float) $pedido['valor_total'], 2, ',', '.') ?></span>
                            </div>
                            <p class="text-muted small mb-0 mt-2">
                                Forma de pagamento: <?= htmlspecialchars($pedido['forma_pagamento_nome'] ?? '') ?>
                            </p>
                        </div>
                    </div>

                    <div class="text-center">
                        <a href="meus_pedidos.php" class="btn btn-outline-primary">
                            <i class="bi bi-receipt"></i>
                            Ver meus pedidos
                        </a>
                    </div>

                </div>
            </div>

        <?php endif; ?>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
