<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';



require_once "../../app/Controllers/ProdutoController.php";
require_once "../../app/Models/FavoritoProduto.php";

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
$categoriaId = (int) ($_GET["categoria_id"] ?? 0);
$empresa = trim((string) ($_GET["empresa"] ?? ""));
$precoFiltro = trim((string) ($_GET["preco_filtro"] ?? ""));
$apenasPromocao = !empty($_GET["promocao"]);
$avaliacaoMinima = (float) ($_GET["avaliacao"] ?? 0);
if (!in_array($avaliacaoMinima, [0.0, 3.5, 4.0, 4.5], true)) {
    $avaliacaoMinima = 0.0;
}
$subcategoriasSelecionadas = [];
if (!empty($_GET["subcategoria"])) {
    $subcategoriasSelecionadas = array_map('intval', (array) $_GET["subcategoria"]);
    $subcategoriasSelecionadas = array_values(array_filter($subcategoriasSelecionadas, static fn($valor) => $valor > 0));
}

if ($precoFiltro !== '') {
    if (str_ends_with($precoFiltro, '+')) {
        $precoMin = (float) substr($precoFiltro, 0, -1);
        $precoMax = 0.0;
    } else {
        $faixa = explode('-', $precoFiltro, 2);
        if (count($faixa) === 2) {
            $precoMin = (float) trim($faixa[0]);
            $precoMax = (float) trim($faixa[1]);
        }
    }
}

$categorias = $controller->listarCategorias();
$produtos = $controller->listarAtivos(
    $busca,
    $subcategoriaId,
    $marcaId,
    $precoMin,
    $precoMax,
    $ordem,
    $cidade,
    $categoriaId,
    $empresa,
    $apenasPromocao,
    $subcategoriasSelecionadas,
    $avaliacaoMinima
);

$categoriasProdutos = [
    'Alimentação' => ['Ração'],
    'Higiene e Saúde' => ['Higiene e Beleza', 'Medicamentos'],
    'Acessórios' => ['Acessórios', 'Roupas'],
    'Brinquedos' => ['Brinquedos'],
    'Camas e Conforto' => ['Camas e Casinhas'],
    'Aquarismo' => ['Aquários e Terrários'],
];

$subcategoriasPorNome = [];
foreach ($subcategorias as $subcategoria) {
    $nome = trim((string) ($subcategoria['nome'] ?? ''));
    if ($nome !== '') {
        $subcategoriasPorNome[strtolower($nome)] = (int) $subcategoria['id'];
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Produtos - EcoSistemPet</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">

    <style>
        .produto-filtro {
            background: #f6f7fb;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
        }

        .produto-filtro .row {
            --bs-gutter-x: 0.5rem;
            --bs-gutter-y: 0.5rem;
        }

        .produto-filtro .form-label {
            font-size: 0.78rem;
            margin-bottom: 0.35rem;
        }

        .produto-filtro .form-control,
        .produto-filtro .form-select {
            border-radius: 999px;
            border: 1px solid #dfe3ea;
            background: #fff;
            min-height: 40px;
            padding: 0.5rem 0.85rem;
            color: #1f2937;
            font-size: 0.9rem;
        }

        .produto-filtro .form-control:focus,
        .produto-filtro .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.15);
        }

        .accordion-filtro {
            width: 100%;
            justify-content: space-between;
            background: #fff;
            border: 1px solid #dce5e3;
            color: #23443d;
            border-radius: 9px;
            padding: 0.55rem 0.75rem;
            font-weight: 600;
            font-size: 0.88rem;
            transition: 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
        }

        .accordion-filtro:hover {
            background: #f2f8f5;
            border-color: #b8d2c7;
        }

        .accordion-filtro .bi {
            order: 1;
            transition: transform 0.2s ease;
            font-size: 0.85rem;
        }

        .accordion-filtro[aria-expanded="true"] .bi {
            transform: rotate(180deg);
        }

        .categoria-box {
            background: #fff;
            border: 1px solid #e3e9e6;
            border-left: 3px solid #4b9278;
            border-radius: 8px;
            padding: 0.65rem 0.7rem 0.35rem;
            height: 100%;
        }

        .categoria-box .titulo {
            color: #263b35;
            font-weight: 700;
            margin-bottom: 0.4rem;
            font-size: 0.82rem;
        }

        .categoria-box label {
            color: #4b5c56;
            font-size: 0.76rem;
            margin: 0 -0.3rem 0.15rem;
            padding: 0.25rem 0.3rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .categoria-box label:hover {
            background: #f1f7f4;
        }

        .categoria-box input[type="checkbox"] {
            accent-color: #328064;
            margin: 0;
        }
    </style>

</head>

<body style="padding-top:38px;">
    <div
        style="position:fixed;top:0;left:0;right:0;z-index:2000;background:#f8f9fa;border-bottom:1px solid #dee2e6;padding:8px 20px;height:38px;box-sizing:border-box;">
        <button type="button"
            onclick="if(window.history.length>1){history.back();}else{window.location.href='../../index.html';}"
            style="background:none;border:none;color:#1B365D;cursor:pointer;font-size:14px;padding:0;"
            aria-label="Voltar para a página anterior">← Voltar</button>
    </div>

    <header class="border-bottom py-3 mb-4">

        <div class="container d-flex align-items-center justify-content-between flex-wrap gap-3">

            <a href="../../index.html" class="d-flex align-items-center text-decoration-none">
                <img src="../../assets/img/logo.png" alt="EcoSistemPet" height="40" class="me-2">
                <div>
                    <div class="fw-bold text-dark">EcoSistemPet</div>
                    <small class="text-muted">Tudo para seu pet em um só lugar</small>
                </div>
            </a>

            <a href="<?= Url::pagina('cadastro_empresa.php') ?>" class="btn btn-success">
                <i class="bi bi-shop"></i>
                Quero Vender Aqui
            </a>

        </div>

    </header>

    <main class="container mb-5">

        <h1 class="fw-bold mb-1">🛍️ Produtos</h1>
        <p class="text-muted">Ração, brinquedos, acessórios e tudo mais para o seu pet.</p>

        <!-- FILTROS -->

        <form method="GET" class="mb-4 p-3 produto-filtro">

            <div class="row g-2 align-items-end">
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label small fw-semibold">🔎 Buscar produto</label>
                    <input type="text" name="busca" class="form-control rounded-pill"
                        placeholder="Nome, marca, categoria ou loja" value="<?= htmlspecialchars($busca) ?>">
                </div>

                <div class="col-xl-2 col-lg-3 col-md-6">
                    <label class="form-label small fw-semibold">💰 Faixa de preço</label>
                    <select name="preco_filtro" class="form-select rounded-pill">
                        <option value="">Qualquer valor</option>
                        <option value="0-50" <?= $precoFiltro === '0-50' ? 'selected' : '' ?>>Até R$ 50</option>
                        <option value="50.01-100" <?= $precoFiltro === '50.01-100' ? 'selected' : '' ?>>R$ 50,01 a R$ 100
                        </option>
                        <option value="100.01-200" <?= $precoFiltro === '100.01-200' ? 'selected' : '' ?>>R$ 100,01 a R$
                            200</option>
                        <option value="200.01-500" <?= $precoFiltro === '200.01-500' ? 'selected' : '' ?>>R$ 200,01 a R$
                            500</option>
                        <option value="500+" <?= $precoFiltro === '500+' ? 'selected' : '' ?>>Acima de R$ 500</option>
                    </select>
                </div>

                <div class="col-xl-2 col-lg-3 col-md-6">
                    <label class="form-label small fw-semibold">🏪 Empresa / Petshop</label>
                    <input type="text" name="empresa" class="form-control rounded-pill" placeholder="Nome da empresa"
                        value="<?= htmlspecialchars($empresa) ?>">
                </div>

                <div class="col-xl-2 col-lg-3 col-md-6">
                    <label class="form-label small fw-semibold">📍 Região (cidade ou UF)</label>
                    <input type="text" name="cidade" class="form-control rounded-pill"
                        placeholder="Ex: Belo Horizonte ou MG" value="<?= htmlspecialchars($cidade) ?>">
                </div>

                <div class="col-xl-1 col-lg-2 col-md-4">
                    <label class="form-label small fw-semibold">⭐ Avaliação da loja</label>
                    <select name="avaliacao" class="form-select rounded-pill">
                        <option value="" <?= $avaliacaoMinima === 0.0 ? 'selected' : '' ?>>Qualquer</option>
                        <option value="4.5" <?= $avaliacaoMinima === 4.5 ? 'selected' : '' ?>>4,5+</option>
                        <option value="4.0" <?= $avaliacaoMinima === 4.0 ? 'selected' : '' ?>>4,0+</option>
                        <option value="3.5" <?= $avaliacaoMinima === 3.5 ? 'selected' : '' ?>>3,5+</option>
                    </select>
                </div>

                <div class="col-xl-1 col-lg-2 col-md-4">
                    <label class="form-label small fw-semibold">🏷️ Promoções</label>
                    <select name="promocao" class="form-select rounded-pill">
                        <option value="">Todas</option>
                        <option value="1" <?= $apenasPromocao ? 'selected' : '' ?>>Com promoção</option>
                    </select>
                </div>

                <div class="col-xl-2 col-lg-3 col-md-6">
                    <label class="form-label small fw-semibold">Ordenar</label>
                    <select name="ordem" class="form-select rounded-pill">
                        <option value="recente" <?= $ordem === "recente" ? "selected" : "" ?>>Mais recentes</option>
                        <option value="menor_preco" <?= $ordem === "menor_preco" ? "selected" : "" ?>>Menor preço</option>
                        <option value="maior_preco" <?= $ordem === "maior_preco" ? "selected" : "" ?>>Maior preço</option>
                        <option value="nome" <?= $ordem === "nome" ? "selected" : "" ?>>Nome (A-Z)</option>
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <button type="button" class="btn accordion-filtro" data-bs-toggle="collapse"
                    data-bs-target="#categoriaSubcategoria" aria-expanded="false" aria-controls="categoriaSubcategoria">
                    <i class="bi bi-chevron-down"></i>
                    Categoria / Subcategoria
                </button>
            </div>

            <div class="collapse mt-3" id="categoriaSubcategoria">
                <div class="row g-2">
                    <?php foreach ($categoriasProdutos as $categoria => $subcategoriasAtuais): ?>
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="categoria-box">
                                <div class="titulo"><?= htmlspecialchars($categoria) ?></div>
                                <?php foreach ($subcategoriasAtuais as $subcategoriaNome): ?>
                                    <?php $subcategoriaId = $subcategoriasPorNome[strtolower(trim((string) $subcategoriaNome))] ?? 0; ?>
                                    <?php if ($subcategoriaId > 0): ?>
                                        <label class="d-flex align-items-center gap-2 small mb-2 text-muted">
                                            <input type="checkbox" name="subcategoria[]" value="<?= (int) $subcategoriaId ?>"
                                                <?= in_array($subcategoriaId, $subcategoriasSelecionadas, true) ? 'checked' : '' ?>>
                                            <span><?= htmlspecialchars($subcategoriaNome) ?></span>
                                        </label>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap mt-3">
                <button type="submit" class="btn btn-primary rounded-pill">
                    <i class="bi bi-funnel"></i>
                    Filtrar
                </button>
                <a href="produtos.php" class="btn btn-outline-secondary rounded-pill">Limpar filtros</a>
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
                    $imagem = Foto::url($produto["imagem_principal"] ?? null, 'produtos');

                    $temPromocao = !empty($produto["preco_promocional"]);
                    $precoFinal = $temPromocao ? $produto["preco_promocional"] : $produto["preco_venda"];
                    ?>

                    <div class="col-lg-3 col-md-4 col-6">

                        <div class="card empresa-card h-100 shadow-sm">

                            <a href="produto.php?id=<?= (int) $produto['id'] ?>">
                                <img src="<?= htmlspecialchars($imagem) ?>" class="card-img-top produto-card-img"
                                    style="height:180px;" alt="<?= htmlspecialchars($produto['nome']) ?>">
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
                                    <a href="<?= Url::pagina('login.php') ?>" class="btn btn-outline-danger"
                                        title="Entre para favoritar">
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
        © <?= date("Y") ?> EcoSistemPet
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>