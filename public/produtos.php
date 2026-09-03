<?php

declare(strict_types=1);

session_start();

require_once "../app/Controllers/ProdutoController.php";
require_once "../app/Models/FavoritoProduto.php";

$controller = new ProdutoController();
$favoritoModel = new FavoritoProduto();

$subcategorias = $controller->listarSubcategorias();
$marcas = $controller->listarMarcas();

$busca = trim($_GET["busca"] ?? "");
$subcategoriaId = (int) ($_GET["subcategoria_id"] ?? 0);
$marcaId = (int) ($_GET["marca_id"] ?? 0);
$precoMin = (float) ($_GET["preco_min"] ?? 0);
$precoMax = (float) ($_GET["preco_max"] ?? 0);
$ordem = trim($_GET["ordem"] ?? "recente");
$cidade = trim($_GET["cidade"] ?? "");

$produtos = $controller->listarAtivos($busca, $subcategoriaId, $marcaId, $precoMin, $precoMax, $ordem, $cidade);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<script src="../assets/js/click-sounds.js"></script>



<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Produtos - PetFinder Brasil</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

    <header class="border-bottom py-3 mb-4">

        <div class="container d-flex align-items-center justify-content-between flex-wrap gap-3">

            <a href="../index.html" class="d-flex align-items-center text-decoration-none">
                <img src="../assets/img/logo.png" alt="PetFinder Brasil" height="40" class="me-2">
                <div>
                    <div class="fw-bold text-dark">PetFinder Brasil</div>
                    <small class="text-muted">Tudo para seu pet em um só lugar</small>
                </div>
            </a>

            <a href="cadastro_empresa.php" class="btn btn-success">
                <i class="bi bi-shop"></i>
                Quero Vender Aqui
            </a>

        </div>

    </header>

    <main class="container mb-5">

        <h1 class="fw-bold mb-1">🛍️ Produtos</h1>
        <p class="text-muted">Ração, brinquedos, acessórios e tudo mais para o seu pet.</p>

        <!-- FILTROS -->

        <form method="GET" class="row g-2 mb-4 align-items-end bg-light p-3 rounded-3">

            <div class="col-md-3">
                <label class="form-label small">Buscar</label>
                <input type="text" name="busca" class="form-control" placeholder="Nome do produto"
                    value="<?= htmlspecialchars($busca) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label small">Subcategoria</label>
                <select name="subcategoria_id" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach ($subcategorias as $sub): ?>
                        <option value="<?= $sub["id"] ?>" <?= $subcategoriaId === (int) $sub["id"] ? "selected" : "" ?>>
                            <?= htmlspecialchars($sub["nome"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small">Marca</label>
                <select name="marca_id" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach ($marcas as $marca): ?>
                        <option value="<?= $marca["id"] ?>" <?= $marcaId === (int) $marca["id"] ? "selected" : "" ?>>
                            <?= htmlspecialchars($marca["nome"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-1">
                <label class="form-label small">Min. R$</label>
                <input type="number" step="0.01" name="preco_min" class="form-control"
                    value="<?= $precoMin > 0 ? htmlspecialchars((string) $precoMin) : '' ?>">
            </div>

            <div class="col-md-1">
                <label class="form-label small">Máx. R$</label>
                <input type="number" step="0.01" name="preco_max" class="form-control"
                    value="<?= $precoMax > 0 ? htmlspecialchars((string) $precoMax) : '' ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label small">Cidade (mais próximos)</label>
                <input type="text" name="cidade" class="form-control" placeholder="Ex: Ouro Branco"
                    value="<?= htmlspecialchars($cidade) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label small">Ordenar</label>
                <select name="ordem" class="form-select">
                    <option value="recente" <?= $ordem === "recente" ? "selected" : "" ?>>Mais recentes</option>
                    <option value="menor_preco" <?= $ordem === "menor_preco" ? "selected" : "" ?>>Menor preço</option>
                    <option value="maior_preco" <?= $ordem === "maior_preco" ? "selected" : "" ?>>Maior preço</option>
                    <option value="nome" <?= $ordem === "nome" ? "selected" : "" ?>>Nome (A-Z)</option>
                </select>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel"></i>
                    Filtrar
                </button>
                <a href="produtos.php" class="btn btn-outline-secondary">Limpar filtros</a>
            </div>

        </form>

        <!-- LISTA -->

        <div class="row g-4">

            <?php if (count($produtos) === 0): ?>

                <div class="col-12 text-center text-muted py-5">
                    Nenhum produto encontrado com esses filtros.
                </div>

            <?php else: ?>

                <?php foreach ($produtos as $produto): ?>

                    <?php
                    $imagem = !empty($produto["imagem_principal"])
                        ? "../uploads/produtos/" . $produto["imagem_principal"]
                        : "../assets/img/pets/sem-foto.png";

                    $temPromocao = !empty($produto["preco_promocional"]);
                    $precoFinal = $temPromocao ? $produto["preco_promocional"] : $produto["preco_venda"];
                    ?>

                    <div class="col-lg-3 col-md-4 col-6">

                        <div class="card empresa-card h-100 shadow-sm">

                            <a href="produto.php?id=<?= (int) $produto['id'] ?>">
                                <img src="<?= htmlspecialchars($imagem) ?>" class="card-img-top"
                                    style="height:180px; object-fit:cover;" alt="<?= htmlspecialchars($produto['nome']) ?>">
                            </a>

                            <div class="card-body">

                                <?php if (!empty($produto['destaque'])): ?>
                                    <span class="badge bg-warning text-dark mb-1">⭐ Destaque</span>
                                <?php endif; ?>

                                <?php if (!empty($produto['subcategoria_nome'])): ?>
                                    <div class="small text-muted"><?= htmlspecialchars($produto['subcategoria_nome']) ?></div>
                                <?php endif; ?>

                                <h6 class="mb-1">
                                    <a href="produto.php?id=<?= (int) $produto['id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($produto['nome']) ?>
                                    </a>
                                </h6>

                                <?php if (!empty($produto['marca_nome'])): ?>
                                    <div class="small text-muted mb-2"><?= htmlspecialchars($produto['marca_nome']) ?></div>
                                <?php endif; ?>

                                <div>
                                    <?php if ($temPromocao): ?>
                                        <span class="text-decoration-line-through text-muted small">
                                            R$ <?= number_format((float) $produto['preco_venda'], 2, ',', '.') ?>
                                        </span><br>
                                    <?php endif; ?>
                                    <strong class="fs-5 text-success">
                                        R$ <?= number_format((float) $precoFinal, 2, ',', '.') ?>
                                    </strong>
                                </div>

                                <div class="small text-muted mt-1">
                                    <i class="bi bi-shop"></i>
                                    <?= htmlspecialchars($produto['empresa_nome']) ?>
                                </div>

                                <?php if ((int) $produto['estoque_quantidade'] <= 0): ?>
                                    <div class="small text-danger mt-1">Fora de estoque</div>
                                <?php endif; ?>

                            </div>

                            <div class="card-footer bg-white d-flex gap-2">
                                <a href="produto.php?id=<?= (int) $produto['id'] ?>"
                                    class="btn btn-outline-primary flex-grow-1">
                                    <i class="bi bi-eye"></i>
                                    Ver Produto
                                </a>
                                <?php if (isset($_SESSION['usuario_id'])): ?>
                                    <?php $jaFavoritado = $favoritoModel->existe((int) $_SESSION['usuario_id'], (int) $produto['id']); ?>
                                    <a href="favoritar_produto.php?produto_id=<?= (int) $produto['id'] ?>&acao=<?= $jaFavoritado ? 'remover' : 'adicionar' ?>"
                                        class="btn <?= $jaFavoritado ? 'btn-danger' : 'btn-outline-danger' ?>"
                                        title="<?= $jaFavoritado ? 'Remover dos favoritos' : 'Favoritar' ?>">
                                        <i class="bi bi-heart<?= $jaFavoritado ? '-fill' : '' ?>"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-outline-danger" title="Entre para favoritar">
                                        <i class="bi bi-heart"></i>
                                    </a>
                                <?php endif; ?>
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