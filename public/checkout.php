<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php?voltar=checkout.php');
    exit;
}

require_once "../app/Controllers/ProdutoController.php";
require_once "../app/Models/Endereco.php";
require_once "../app/Helpers/Csrf.php";
require_once "../config/database.php";

$controller = new ProdutoController();
$enderecoModel = new Endereco();

$carrinho = $_SESSION['carrinho'] ?? [];
$itens = [];
$subtotal = 0.0;

foreach ($carrinho as $produtoId => $qtd) {
    $produto = $controller->buscarPorId((int) $produtoId);
    if (!$produto) {
        continue;
    }
    $preco = !empty($produto['preco_promocional']) ? (float) $produto['preco_promocional'] : (float) $produto['preco_venda'];
    $total = $preco * $qtd;
    $itens[] = ['produto' => $produto, 'quantidade' => $qtd, 'total' => $total, 'preco' => $preco];
    $subtotal += $total;
}

$endereco = $enderecoModel->buscarPorUsuario((int) $_SESSION['usuario_id']);

require_once "../app/Models/Pedido.php";
$previsaoEntrega = (new Pedido())->calcularPrevisaoEntrega();

$pdo = Database::conectar();
$formasPagamento = $pdo->query("SELECT * FROM formas_pagamento WHERE ativo = 1 ORDER BY id")->fetchAll();

$erro = $_SESSION['checkout_erro'] ?? null;
unset($_SESSION['checkout_erro']);

?>
<!DOCTYPE html>
<html lang="pt-BR">

    <script src="../assets/js/click-sounds.js"></script>


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - PetFinder Brasil</title>
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
            <a href="carrinho.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i>
                Voltar para o carrinho
            </a>
        </div>
    </header>

    <main class="container mb-5">

        <h1 class="mb-4"><i class="bi bi-bag-check"></i> Finalizar Compra</h1>

        <?php if (empty($itens)): ?>

            <div class="alert alert-warning">
                Seu carrinho está vazio.
                <a href="produtos.php" class="alert-link">Ver produtos</a>
            </div>

        <?php elseif (!$endereco): ?>

            <div class="alert alert-warning">
                Você precisa cadastrar um endereço de entrega antes de continuar.
                <a href="endereco.php?voltar=checkout.php" class="alert-link">Cadastrar endereço</a>
            </div>

        <?php else: ?>

            <?php if ($erro): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <div class="row g-4">

                <div class="col-lg-7">

                    <div class="card mb-4">
                        <div class="card-header fw-bold">
                            <i class="bi bi-geo-alt"></i>
                            Endereço de entrega
                        </div>
                        <div class="card-body">
                            <p class="mb-1">
                                <?= htmlspecialchars($endereco['logradouro'] ?? '') ?>,
                                <?= htmlspecialchars($endereco['numero'] ?? '') ?>
                                <?php if (!empty($endereco['complemento'])): ?> - <?= htmlspecialchars($endereco['complemento']) ?><?php endif; ?>
                            </p>
                            <p class="mb-1 text-muted">
                                <?= htmlspecialchars($endereco['bairro'] ?? '') ?> -
                                <?= htmlspecialchars($endereco['cidade'] ?? '') ?>/<?= htmlspecialchars($endereco['estado'] ?? '') ?>
                                <?php if (!empty($endereco['cep'])): ?> - CEP <?= htmlspecialchars($endereco['cep']) ?><?php endif; ?>
                            </p>
                            <?php if (!empty($endereco['referencia'])): ?>
                                <p class="mb-1 text-muted small">
                                    <i class="bi bi-signpost"></i>
                                    Referência: <?= htmlspecialchars($endereco['referencia']) ?>
                                </p>
                            <?php endif; ?>
                            <p class="mb-1">
                                <i class="bi bi-truck text-success"></i>
                                Previsão de entrega: <strong><?= date('d/m/Y', strtotime($previsaoEntrega)) ?></strong>
                            </p>
                            <a href="endereco.php?voltar=checkout.php" class="small">Alterar endereço</a>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header fw-bold">
                            <i class="bi bi-bag"></i>
                            Itens do pedido
                        </div>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($itens as $item): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($item['produto']['nome']) ?></strong>
                                        <div class="text-muted small">
                                            Vendido por <?= htmlspecialchars($item['produto']['empresa_nome'] ?? '') ?>
                                            &middot; <?= (int) $item['quantidade'] ?>x R$ <?= number_format($item['preco'], 2, ',', '.') ?>
                                        </div>
                                    </div>
                                    <span>R$ <?= number_format($item['total'], 2, ',', '.') ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                </div>

                <div class="col-lg-5">

                    <form action="processa_checkout.php" method="POST">

                        <?= Csrf::campoHtml() ?>

                        <div class="card mb-4">
                            <div class="card-header fw-bold">
                                <i class="bi bi-credit-card"></i>
                                Forma de pagamento
                            </div>
                            <div class="card-body">
                                <?php foreach ($formasPagamento as $i => $forma): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="forma_pagamento_id"
                                               id="forma<?= (int) $forma['id'] ?>"
                                               value="<?= (int) $forma['id'] ?>"
                                               <?= $i === 0 ? 'checked' : '' ?> required>
                                        <label class="form-check-label" for="forma<?= (int) $forma['id'] ?>">
                                            <?= htmlspecialchars($forma['nome']) ?>
                                            <?php if (!empty($forma['descricao'])): ?>
                                                <small class="text-muted d-block"><?= htmlspecialchars($forma['descricao']) ?></small>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-body">
                                <label for="cupom" class="form-label fw-bold">Cupom de desconto</label>
                                <input type="text" class="form-control" id="cupom" name="cupom" placeholder="Opcional">
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <span>Subtotal</span>
                                    <span>R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
                                </div>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span>Frete</span>
                                    <span>Grátis</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between fs-5 fw-bold">
                                    <span>Total</span>
                                    <span>R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    O desconto do cupom, se válido, é aplicado ao confirmar.
                                </small>
                            </div>
                        </div>

                        <div class="alert alert-info small">
                            <i class="bi bi-info-circle"></i>
                            Ambiente de demonstração: o pagamento é confirmado automaticamente,
                            sem integração com um gateway de pagamento real.
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-lock-fill"></i>
                            Confirmar Compra
                        </button>

                    </form>

                </div>

            </div>

        <?php endif; ?>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
