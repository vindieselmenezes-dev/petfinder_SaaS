<?php

declare(strict_types=1);

require_once "../app/Controllers/EmpresaController.php";

$controller = new EmpresaController();

$q = trim($_GET['q'] ?? '');
$cidade = trim($_GET['cidade'] ?? '');
$categoriaId = isset($_GET['categoria_id']) ? (int) $_GET['categoria_id'] : 0;

$empresas = $controller->listarAtivas($categoriaId, $cidade, $q);

?>

<!DOCTYPE html>
<html lang="pt-BR">

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

        <div class="row g-4">

            <?php if (count($empresas) === 0): ?>
                <div class="col-12 text-center text-muted py-5">
                    Nenhuma empresa encontrada.
                </div>
            <?php else: ?>
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
            <?php endif; ?>

        </div>

    </main>

    <footer class="border-top py-4 text-center text-muted">
        © <?= date("Y") ?> PetFinder Brasil
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>