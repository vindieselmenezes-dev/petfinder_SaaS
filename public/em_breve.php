<?php

declare(strict_types=1);

$recurso = trim($_GET["recurso"] ?? "Esta funcionalidade");

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Em breve - PetFinder Brasil</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

    <header class="border-bottom py-3 mb-4">
        <div class="container d-flex align-items-center">
            <a href="../index.html" class="d-flex align-items-center text-decoration-none">
                <img src="../assets/img/logo.png" alt="PetFinder Brasil" height="40" class="me-2">
                <div>
                    <div class="fw-bold text-dark">PetFinder Brasil</div>
                    <small class="text-muted">Tudo para seu pet em um só lugar</small>
                </div>
            </a>
        </div>
    </header>

    <main class="container text-center py-5">

        <i class="bi bi-cone-striped display-1 text-warning"></i>

        <h1 class="fw-bold mt-3"><?= htmlspecialchars($recurso) ?> chegando em breve!</h1>

        <p class="text-muted fs-5">
            Estamos trabalhando pra trazer essa funcionalidade pra você.
        </p>

        <a href="../index.html" class="btn btn-primary mt-3">
            <i class="bi bi-arrow-left"></i>
            Voltar para a página inicial
        </a>

    </main>

    <footer class="border-top py-4 text-center text-muted mt-5">
        © <?= date("Y") ?> PetFinder Brasil
    </footer>

</body>

</html>
