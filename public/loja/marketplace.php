<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/Controllers/ProdutoController.php';

$controller = new ProdutoController();
$subcategorias = $controller->listarSubcategorias();
$destaques = $controller->listarDestaques(8);
$ofertas = $controller->listarOfertas(8);
$totalProdutos = $controller->contarProdutos();

$iconesCategoria = [
    'acessórios' => 'bi-bag-heart',
    'aquários e terrários' => 'bi-water',
    'brinquedos' => 'bi-balloon',
    'camas e casinhas' => 'bi-house-heart',
    'higiene e beleza' => 'bi-droplet-half',
    'medicamentos' => 'bi-capsule',
    'ração' => 'bi-basket2',
    'roupas' => 'bi-tag',
];

function renderizarCardMarketplace(array $produto): void
{
    $imagem = Foto::url($produto['imagem_principal'] ?? null, 'produtos');
    $precoVenda = (float) ($produto['preco_venda'] ?? 0);
    $precoPromocional = (float) ($produto['preco_promocional'] ?? 0);
    $temPromocao = $precoPromocional > 0 && $precoPromocional < $precoVenda;
    $precoFinal = $temPromocao ? $precoPromocional : $precoVenda;
    ?>
    <div class="col-12 col-sm-6 col-lg-3">
        <article class="produto-marketplace h-100">
            <a class="produto-marketplace__imagem" href="produto.php?id=<?= (int) $produto['id'] ?>">
                <?php if ($temPromocao): ?>
                    <span class="produto-marketplace__selo">Oferta</span>
                <?php endif; ?>
                <img src="<?= htmlspecialchars($imagem) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>" loading="lazy">
            </a>
            <div class="produto-marketplace__conteudo">
                <p class="produto-marketplace__categoria">
                    <?= htmlspecialchars($produto['subcategoria_nome'] ?? 'Produtos para pets') ?></p>
                <h3><a href="produto.php?id=<?= (int) $produto['id'] ?>"><?= htmlspecialchars($produto['nome']) ?></a></h3>
                <?php if ($temPromocao): ?>
                    <del>R$ <?= number_format($precoVenda, 2, ',', '.') ?></del>
                <?php endif; ?>
                <p class="produto-marketplace__preco">R$ <?= number_format($precoFinal, 2, ',', '.') ?></p>
                <p class="produto-marketplace__loja">
                    <i class="bi bi-shop" aria-hidden="true"></i>
                    <?= htmlspecialchars($produto['empresa_nome'] ?? 'Loja parceira') ?>
                </p>
                <a class="btn btn-outline-primary w-100" href="produto.php?id=<?= (int) $produto['id'] ?>">Ver produto</a>
            </div>
        </article>
    </div>
    <?php
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace Pet - EcoSistemPet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .marketplace-topo {
            background: #eef6f4;
            border-bottom: 1px solid #dce8e4;
        }

        .marketplace-capa {
            padding: 2.5rem 0 2rem;
        }

        .marketplace-capa h1 {
            max-width: 680px;
            color: #173f35;
            font-size: clamp(1.8rem, 4vw, 2.6rem);
            font-weight: 750;
        }

        .marketplace-busca {
            max-width: 720px;
        }

        .marketplace-busca .form-control,
        .marketplace-busca .btn {
            min-height: 48px;
        }

        .marketplace-atalhos {
            display: flex;
            gap: 1.25rem;
            overflow-x: auto;
            padding: 0.85rem 0;
            white-space: nowrap;
        }

        .marketplace-atalhos a {
            color: #3d514b;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
        }

        .marketplace-atalhos a:hover {
            color: #16724f;
        }

        .marketplace-secao {
            padding: 2rem 0;
        }

        .marketplace-secao+.marketplace-secao {
            border-top: 1px solid #e8ecea;
        }

        .marketplace-categorias {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(132px, 1fr));
            gap: 0.75rem;
        }

        .marketplace-categoria {
            min-height: 104px;
            padding: 0.9rem;
            border: 1px solid #e1e8e5;
            border-radius: 8px;
            background: #fff;
            color: #2d4840;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: border-color 0.15s ease, background-color 0.15s ease;
        }

        .marketplace-categoria:hover {
            border-color: #72ae94;
            background: #f5faf7;
            color: #176b4a;
        }

        .marketplace-categoria .bi {
            color: #328064;
            font-size: 1.35rem;
        }

        .produto-marketplace {
            overflow: hidden;
            border: 1px solid #e4e9e7;
            border-radius: 8px;
            background: #fff;
            display: flex;
            flex-direction: column;
        }

        .produto-marketplace__imagem {
            position: relative;
            display: grid;
            place-items: center;
            aspect-ratio: 1 / 1;
            background: #f7f9f8;
            overflow: hidden;
        }

        .produto-marketplace__imagem img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .produto-marketplace__selo {
            position: absolute;
            top: 0.65rem;
            left: 0.65rem;
            z-index: 1;
            padding: 0.25rem 0.45rem;
            border-radius: 4px;
            background: #c84236;
            color: #fff;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .produto-marketplace__conteudo {
            display: flex;
            flex: 1;
            flex-direction: column;
            padding: 0.9rem;
        }

        .produto-marketplace__categoria,
        .produto-marketplace__loja {
            color: #66756f;
            font-size: 0.78rem;
            margin-bottom: 0.35rem;
        }

        .produto-marketplace h3 {
            min-height: 2.7em;
            margin: 0 0 0.5rem;
            font-size: 0.98rem;
            font-weight: 600;
        }

        .produto-marketplace h3 a {
            color: #263b35;
            text-decoration: none;
        }

        .produto-marketplace__conteudo del {
            color: #78837e;
            font-size: 0.8rem;
        }

        .produto-marketplace__preco {
            margin: 0.15rem 0 0.35rem;
            color: #18764f;
            font-size: 1.25rem;
            font-weight: 750;
        }

        .produto-marketplace__loja {
            margin-top: auto;
            margin-bottom: 0.75rem;
        }

        .marketplace-ofertas {
            background: #fbf7ed;
        }

        @media(max-width: 575.98px) {
            .marketplace-capa {
                padding: 1.75rem 0 1.5rem;
            }

            .marketplace-secao {
                padding: 1.5rem 0;
            }
        }
    </style>
</head>

<body>
    <header class="marketplace-topo">
        <div class="container">
            <nav class="d-flex align-items-center justify-content-between gap-3 py-3"
                aria-label="Navegação do marketplace">
                <a href="../../index.html" class="d-flex align-items-center text-decoration-none">
                    <img src="../../assets/img/logo.png" alt="EcoSistemPet" height="42" class="me-2">
                    <span class="fw-bold text-dark">Marketplace</span>
                </a>
                <div class="d-flex align-items-center gap-2">
                    <a href="produtos.php" class="btn btn-outline-success btn-sm">Catálogo</a>
                    <a href="carrinho.php" class="btn btn-success btn-sm"><i class="bi bi-cart3" aria-hidden="true"></i>
                        Carrinho</a>
                </div>
            </nav>
            <div class="marketplace-capa">
                <p class="text-uppercase small fw-bold text-success mb-2">Marketplace EcoSistemPet</p>
                <h1>Encontre tudo para cuidar bem do seu pet</h1>
                <p class="text-muted mb-3"><?= number_format($totalProdutos, 0, ',', '.') ?> produtos de lojas
                    parceiras.</p>
                <form class="input-group marketplace-busca" action="produtos.php" method="GET" role="search">
                    <input class="form-control" name="busca" type="search"
                        placeholder="Buscar produtos, marcas ou lojas" aria-label="Buscar produtos">
                    <button class="btn btn-success" type="submit"><i class="bi bi-search" aria-hidden="true"></i>
                        Buscar</button>
                </form>
            </div>
        </div>
    </header>

    <main class="container">
        <nav class="marketplace-atalhos border-bottom" aria-label="Seções do marketplace">
            <a href="#categorias">Categorias</a>
            <a href="#destaques">Produtos em destaque</a>
            <a href="#ofertas">Ofertas</a>
            <a href="produtos.php">Ver catálogo completo</a>
        </nav>

        <section class="marketplace-secao" id="categorias">
            <div class="d-flex align-items-end justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h4 fw-bold mb-1">Compre por categoria</h2>
                    <p class="text-muted mb-0">Explore as categorias disponíveis no catálogo.</p>
                </div>
                <a href="produtos.php" class="small text-success text-decoration-none">Ver tudo</a>
            </div>
            <div class="marketplace-categorias">
                <?php foreach ($subcategorias as $subcategoria): ?>
                    <?php
                    $nomeCategoria = (string) $subcategoria['nome'];
                    $iconeCategoria = $iconesCategoria[mb_strtolower($nomeCategoria)] ?? 'bi-grid';
                    ?>
                    <a class="marketplace-categoria" href="produtos.php?subcategoria_id=<?= (int) $subcategoria['id'] ?>">
                        <i class="bi <?= htmlspecialchars($iconeCategoria) ?>" aria-hidden="true"></i>
                        <span><?= htmlspecialchars($nomeCategoria) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="marketplace-secao" id="destaques">
            <div class="d-flex align-items-end justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h4 fw-bold mb-1">Produtos em destaque</h2>
                    <p class="text-muted mb-0">Seleção das lojas parceiras.</p>
                </div>
                <a href="produtos.php" class="small text-success text-decoration-none">Ver catálogo</a>
            </div>
            <div class="row g-3">
                <?php if (!$destaques): ?>
                    <p class="col-12 text-muted mb-0">Novos produtos em destaque aparecerão aqui.</p>
                <?php else: ?>
                    <?php foreach ($destaques as $produto): ?>
                        <?php renderizarCardMarketplace($produto); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="marketplace-secao marketplace-ofertas" id="ofertas">
            <div class="d-flex align-items-end justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h4 fw-bold mb-1">Ofertas</h2>
                    <p class="text-muted mb-0">Preços promocionais cadastrados pelas lojas.</p>
                </div>
                <a href="ofertas.php" class="small text-danger text-decoration-none">Todas as ofertas</a>
            </div>
            <div class="row g-3">
                <?php if (!$ofertas): ?>
                    <p class="col-12 text-muted mb-0">Nenhuma oferta disponível no momento.</p>
                <?php else: ?>
                    <?php foreach ($ofertas as $produto): ?>
                        <?php renderizarCardMarketplace($produto); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="border-top py-4 text-center text-muted">
        <a href="../../index.html" class="text-decoration-none">EcoSistemPet</a> · Marketplace de produtos para pets
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>