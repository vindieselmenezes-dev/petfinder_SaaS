<?php

declare(strict_types=1);

require_once "../app/Controllers/ProdutoController.php";

$controller = new ProdutoController();

$ofertas = $controller->listarOfertas();

?>

<!DOCTYPE html>
<html lang="pt-BR">
<script src="../assets/js/click-sounds.js"></script>



<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ofertas - PetFinder Brasil</title>

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
                Ver todos os produtos
            </a>

        </div>

    </header>

    <main class="container mb-5">

        <div class="p-4 mb-4 rounded-4 text-white" style="background: linear-gradient(135deg, #dc3545, #fd7e14);">
            <h1 class="fw-bold mb-1">🔥 Ofertas Imperdíveis</h1>
            <p class="mb-0">Produtos com desconto de verdade, direto das lojas parceiras.</p>
        </div>

        <div class="row g-4">

            <?php if (count($ofertas) === 0): ?>

                <div class="col-12 text-center text-muted py-5">
                    Nenhuma oferta disponível no momento. Volte mais tarde!
                </div>

            <?php else: ?>

                <?php foreach ($ofertas as $produto): ?>

                    <?php
                    $imagem = !empty($produto["imagem_principal"])
                        ? "../uploads/produtos/" . $produto["imagem_principal"]
                        : "../assets/img/pets/sem-foto.png";
                    ?>

                    <div class="col-lg-3 col-md-4 col-6">

                        <div class="card empresa-card h-100 shadow-sm position-relative">

                            <span class="badge bg-danger position-absolute m-2" style="z-index:1;">
                                -<?= (int) $produto['percentual_desconto'] ?>%
                            </span>

                            <a href="produto.php?id=<?= (int) $produto['id'] ?>">
                                <img src="<?= htmlspecialchars($imagem) ?>" class="card-img-top"
                                    style="height:180px; object-fit:cover;" alt="<?= htmlspecialchars($produto['nome']) ?>">
                            </a>

                            <div class="card-body">

                                <?php if (!empty($produto['subcategoria_nome'])): ?>
                                    <div class="small text-muted"><?= htmlspecialchars($produto['subcategoria_nome']) ?></div>
                                <?php endif; ?>

                                <h6 class="mb-2">
                                    <a href="produto.php?id=<?= (int) $produto['id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($produto['nome']) ?>
                                    </a>
                                </h6>

                                <div>
                                    <span class="text-decoration-line-through text-muted small">
                                        R$ <?= number_format((float) $produto['preco_venda'], 2, ',', '.') ?>
                                    </span><br>
                                    <strong class="fs-5 text-danger">
                                        R$ <?= number_format((float) $produto['preco_promocional'], 2, ',', '.') ?>
                                    </strong>
                                </div>

                                <div class="small text-muted mt-1">
                                    <i class="bi bi-shop"></i>
                                    <?= htmlspecialchars($produto['empresa_nome']) ?>
                                </div>

                            </div>

                            <div class="card-footer bg-white">
                                <a href="produto.php?id=<?= (int) $produto['id'] ?>" class="btn btn-outline-danger w-100">
                                    <i class="bi bi-eye"></i>
                                    Ver Oferta
                                </a>
                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

    </main>

    <footer class="border-top py-4 text-center text-muted">
        © <?= date("Y") ?> PetFinder Brasil
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>