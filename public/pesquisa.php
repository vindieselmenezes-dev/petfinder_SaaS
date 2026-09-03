<?php

declare(strict_types=1);

require_once "../app/Controllers/EmpresaController.php";
require_once "../app/Controllers/ProdutoController.php";
require_once "../app/Controllers/PetController.php";

$empresaController = new EmpresaController();
$produtoController = new ProdutoController();
$petController = new PetController();

$q = trim($_GET['q'] ?? '');
$cidade = trim($_GET['cidade'] ?? '');
$categoriaId = isset($_GET['categoria_id']) ? (int) $_GET['categoria_id'] : 0;

$categorias = $empresaController->listarCategorias();
$topicos = array_values(array_filter($categorias, static function (array $categoria) use ($q): bool {
    return $q !== '' && stripos($categoria['nome'], $q) !== false;
}));

$empresas = $empresaController->listarAtivas($categoriaId, $cidade, $q);
$produtos = $produtoController->listarAtivos($q, 0, 0, 0.0, 0.0, 'recente', $cidade);
$pets = $petController->buscarAdocaoPublico(busca: $q, cidade: $cidade, status: 'Todos');
$temResultados = $topicos || $empresas || $produtos || $pets;

?>

<!DOCTYPE html>
<html lang="pt-BR">
<script src="../assets/js/click-sounds.js"></script>



<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesquisa - PetFinder Brasil</title>

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

            <a href="empresas.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i>
                Voltar
            </a>
        </div>
    </header>

    <main class="container mb-5">

        <h1 class="fw-bold mb-1">Pesquisa</h1>
        <p class="text-muted">Resultados para "<?= htmlspecialchars($q) ?>"</p>

        <?php if (!$temResultados): ?>
            <div class="alert alert-info">Nenhum resultado encontrado para esta pesquisa.</div>
        <?php endif; ?>

        <?php if ($topicos): ?>
            <h2 class="h4 mt-4 mb-3">Tópicos</h2>
            <div class="row g-3 mb-4">
                <?php foreach ($topicos as $topico): ?>
                    <?php
                    $destinoTopico = match ((int) $topico['id']) {
                        8 => 'buscar_pets.php',
                        9 => 'produtos.php',
                        default => 'empresas.php?categoria_id=' . (int) $topico['id']
                    };
                    ?>
                    <div class="col-md-4">
                        <a href="<?= htmlspecialchars($destinoTopico) ?>" class="card h-100 text-decoration-none shadow-sm">
                            <div class="card-body">
                                <i class="bi <?= htmlspecialchars($topico['icone'] ?? 'bi-grid') ?> fs-3 text-primary"></i>
                                <h3 class="h5 mt-2 text-dark"><?= htmlspecialchars($topico['nome']) ?></h3>
                                <p class="text-muted small mb-0"><?= htmlspecialchars($topico['descricao'] ?? '') ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($empresas): ?>
            <h2 class="h4 mt-4 mb-3">Empresas e serviços</h2>
            <div class="row g-4 mb-4">
                <?php foreach ($empresas as $empresa): ?>
                    <?php
                    $capa = !empty($empresa["capa"]) ? "../uploads/empresas/" . $empresa["capa"] : "../assets/img/pets/sem-foto.png";
                    ?>

                    <div class="col-lg-4 col-md-6">
                        <div class="card empresa-card h-100 shadow-sm">
                            <a href="empresa.php?id=<?= (int) $empresa['id'] ?>">
                                <img src="<?= htmlspecialchars($capa) ?>" class="card-img-top"
                                    alt="<?= htmlspecialchars($empresa['nome_fantasia']) ?>">
                            </a>
                            <div class="card-body">
                                <span class="badge bg-primary mb-2">
                                    <i class="bi <?= htmlspecialchars($empresa['categoria_icone'] ?? 'bi-shop') ?>"></i>
                                    <?= htmlspecialchars($empresa['categoria_nome']) ?>
                                </span>
                                <h4>
                                    <a href="empresa.php?id=<?= (int) $empresa['id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($empresa['nome_fantasia']) ?>
                                    </a>
                                </h4>
                                <p class="text-muted small">
                                    <?= htmlspecialchars(mb_strimwidth($empresa['descricao'] ?? '', 0, 100, '...')) ?>
                                </p>
                                <div class="mb-2">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <?= htmlspecialchars(($empresa['cidade'] ?: 'Cidade não informada') . ($empresa['estado'] ? ' / ' . $empresa['estado'] : '')) ?>
                                </div>
                                <div>
                                    <?php if ((float) $empresa['avaliacao'] > 0): ?>
                                        ⭐ <?= number_format((float) $empresa['avaliacao'], 1) ?>
                                        <small class="text-muted">(<?= (int) $empresa['total_avaliacoes'] ?> avaliações)</small>
                                    <?php else: ?>
                                        <small class="text-muted">Sem avaliações ainda</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <a href="empresa.php?id=<?= (int) $empresa['id'] ?>" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-eye"></i>
                                    Ver Perfil
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($produtos): ?>
            <h2 class="h4 mt-4 mb-3">Produtos</h2>
            <div class="row g-4 mb-4">
                <?php foreach ($produtos as $produto): ?>
                    <div class="col-lg-3 col-md-6">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <span class="badge bg-success mb-2">Produto</span>
                                <h3 class="h5">
                                    <a href="produto.php?id=<?= (int) $produto['id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($produto['nome']) ?>
                                    </a>
                                </h3>
                                <p class="small text-muted mb-1"><?= htmlspecialchars($produto['empresa_nome'] ?? '') ?></p>
                                <strong>R$
                                    <?= number_format((float) ($produto['preco_promocional'] ?: $produto['preco_venda']), 2, ',', '.') ?></strong>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($pets): ?>
            <h2 class="h4 mt-4 mb-3">Pets</h2>
            <div class="row g-4">
                <?php foreach ($pets as $pet): ?>
                    <div class="col-lg-3 col-md-6">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <span class="badge bg-warning text-dark mb-2">Pet</span>
                                <h3 class="h5">
                                    <a href="pet.php?id=<?= (int) $pet['id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($pet['nome']) ?>
                                    </a>
                                </h3>
                                <p class="small text-muted mb-0">
                                    <?= htmlspecialchars(($pet['especie'] ?? '') . ' - ' . ($pet['raca'] ?? '')) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <footer class="border-top py-4 text-center text-muted">
        © <?= date("Y") ?> PetFinder Brasil
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>