<?php

declare(strict_types=1);

require_once "../app/Controllers/ProdutoController.php";

$controller = new ProdutoController();

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

$produto = $controller->buscarPorId($id);
$imagens = $produto ? $controller->buscarImagens($id) : [];
$estoque = $produto ? $controller->buscarEstoque($id) : null;

$temPromocao = $produto && !empty($produto["preco_promocional"]);
$precoFinal  = $produto ? ($temPromocao ? $produto["preco_promocional"] : $produto["preco_venda"]) : 0;

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $produto ? htmlspecialchars($produto["nome"]) . " - " : "" ?>PetFinder Brasil</title>

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
                <i class="bi bi-arrow-left"></i>
                Voltar para Produtos
            </a>

        </div>

    </header>

    <main class="container mb-5">

    <?php if (!$produto): ?>

        <div class="alert alert-warning text-center py-5">
            <h2>Produto não encontrado.</h2>
            <p class="mb-0">O link pode estar incorreto ou o produto não está mais disponível.</p>
        </div>

    <?php else: ?>

        <div class="row g-4">

            <!-- IMAGENS -->

            <div class="col-lg-5">

                <?php
                    $imagemPrincipal = !empty($imagens)
                        ? "../uploads/produtos/" . $imagens[0]["imagem"]
                        : "../assets/img/pets/sem-foto.png";
                ?>

                <img id="imagemPrincipal" src="<?= htmlspecialchars($imagemPrincipal) ?>"
                     class="img-fluid rounded-4 shadow-sm w-100 mb-2"
                     style="object-fit:cover; max-height:420px;"
                     alt="<?= htmlspecialchars($produto["nome"]) ?>">

                <?php if (count($imagens) > 1): ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($imagens as $imagem): ?>
                            <img
                                src="../uploads/produtos/<?= htmlspecialchars($imagem["imagem"]) ?>"
                                width="70" height="70"
                                class="rounded-3 miniatura-produto"
                                style="object-fit:cover; cursor:pointer; border:2px solid transparent;"
                                onclick="document.getElementById('imagemPrincipal').src=this.src;"
                                alt="Miniatura">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>

            <!-- INFORMAÇÕES -->

            <div class="col-lg-7">

                <?php if (!empty($produto['subcategoria_nome'])): ?>
                    <span class="badge bg-secondary mb-2"><?= htmlspecialchars($produto['subcategoria_nome']) ?></span>
                <?php endif; ?>

                <?php if (!empty($produto['destaque'])): ?>
                    <span class="badge bg-warning text-dark mb-2">⭐ Destaque</span>
                <?php endif; ?>

                <h1 class="fw-bold"><?= htmlspecialchars($produto["nome"]) ?></h1>

                <?php if (!empty($produto['marca_nome'])): ?>
                    <p class="text-muted mb-1">Marca: <strong><?= htmlspecialchars($produto['marca_nome']) ?></strong></p>
                <?php endif; ?>

                <?php if (!empty($produto['sku'])): ?>
                    <p class="text-muted small">SKU: <?= htmlspecialchars($produto['sku']) ?></p>
                <?php endif; ?>

                <div class="my-3">

                    <?php if ($temPromocao): ?>
                        <span class="text-decoration-line-through text-muted fs-5">
                            R$ <?= number_format((float) $produto['preco_venda'], 2, ',', '.') ?>
                        </span>
                        <span class="badge bg-danger ms-2">Promoção</span>
                        <br>
                    <?php endif; ?>

                    <span class="display-6 fw-bold text-success">
                        R$ <?= number_format((float) $precoFinal, 2, ',', '.') ?>
                    </span>

                </div>

                <?php
                    $quantidadeEstoque = $estoque ? (int) $estoque['quantidade'] : 0;
                ?>

                <?php if ($quantidadeEstoque > 0): ?>
                    <p class="text-success"><i class="bi bi-check-circle-fill"></i> Em estoque (<?= $quantidadeEstoque ?> disponíveis)</p>
                <?php else: ?>
                    <p class="text-danger"><i class="bi bi-x-circle-fill"></i> Fora de estoque no momento</p>
                <?php endif; ?>

                <?php if (!empty($produto['descricao'])): ?>
                    <h5 class="mt-4">Descrição</h5>
                    <p><?= nl2br(htmlspecialchars($produto['descricao'])) ?></p>
                <?php endif; ?>

                <?php if (!empty($produto['peso']) || !empty($produto['altura']) || !empty($produto['largura']) || !empty($produto['comprimento'])): ?>
                    <h5 class="mt-4">Dimensões</h5>
                    <ul class="list-unstyled text-muted">
                        <?php if (!empty($produto['peso'])): ?><li>Peso: <?= htmlspecialchars((string) $produto['peso']) ?> kg</li><?php endif; ?>
                        <?php if (!empty($produto['altura'])): ?><li>Altura: <?= htmlspecialchars((string) $produto['altura']) ?> cm</li><?php endif; ?>
                        <?php if (!empty($produto['largura'])): ?><li>Largura: <?= htmlspecialchars((string) $produto['largura']) ?> cm</li><?php endif; ?>
                        <?php if (!empty($produto['comprimento'])): ?><li>Comprimento: <?= htmlspecialchars((string) $produto['comprimento']) ?> cm</li><?php endif; ?>
                    </ul>
                <?php endif; ?>

                <hr class="my-4">

                <h5>Vendido por</h5>

                <p class="mb-2">
                    <i class="bi bi-shop"></i>
                    <strong><?= htmlspecialchars($produto["empresa_nome"]) ?></strong>
                    <?php if (!empty($produto['empresa_cidade'])): ?>
                        <br><i class="bi bi-geo-alt-fill"></i>
                        <?= htmlspecialchars($produto['empresa_cidade']) ?><?= !empty($produto['empresa_estado']) ? ' / ' . htmlspecialchars($produto['empresa_estado']) : '' ?>
                    <?php endif; ?>
                </p>

                <?php if (!empty($produto["empresa_whatsapp"])): ?>

                    <a href="https://wa.me/55<?= preg_replace('/\D/', '', $produto["empresa_whatsapp"]) ?>?text=<?= urlencode('Olá! Tenho interesse no produto: ' . $produto['nome']) ?>"
                       target="_blank" rel="noopener"
                       class="btn btn-success btn-lg">
                        <i class="bi bi-whatsapp"></i>
                        Falar com o vendedor
                    </a>

                    <a href="em_breve.php?recurso=Carrinho" class="btn btn-outline-primary btn-lg ms-2">
                        <i class="bi bi-cart-plus"></i>
                        Adicionar ao Carrinho
                    </a>

                    <a href="favoritar_produto.php?produto_id=<?= (int)$produto['id'] ?>&acao=adicionar" class="btn btn-light btn-lg ms-2">
                        <i class="bi bi-heart"></i>
                        Favoritar
                    </a>

                <?php else: ?>

                    <div class="alert alert-secondary mb-0">
                        Este vendedor ainda não cadastrou um WhatsApp de contato.
                    </div>

                <?php endif; ?>

            </div>

        </div>

    <?php endif; ?>

    </main>

    <footer class="border-top py-4 text-center text-muted">
        © <?= date("Y") ?> PetFinder Brasil
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
