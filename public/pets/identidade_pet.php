<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';


/**
 * Carteirinha / Identidade digital do pet -- página pública, pensada pra
 * ser acessada via QR code numa plaquinha física ou compartilhada em caso
 * de pet perdido. Ideia trazida do "identidadepet.html" do
 * projetointegrador, aproveitando os dados que o SaaS já guarda
 * (microchip, espécie, raça, status, tutor).
 */


require_once "../../app/Controllers/PetController.php";
require_once "../../app/Helpers/Seo.php";

$controller = new PetController();

$token = trim($_GET["token"] ?? "");
$pet = $token !== "" ? $controller->buscarPorToken($token) : null;

$seoTitulo = $pet ? "Identidade de " . $pet["nome"] . " - PetFinder Brasil" : "Identidade Pet - PetFinder Brasil";

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($seoTitulo) ?></title>
    <?= Seo::tags($seoTitulo, "Cartão de identidade digital do pet no PetFinder Brasil.", Url::pagina('identidade_pet.php') . '?token=' . urlencode($token), Foto::url(null, 'pets'), "website") ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">

    <style>
        .carteirinha {
            max-width: 420px;
            margin: 40px auto;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0,0,0,.15);
            border: 1px solid #e2e8f0;
        }
        .carteirinha-topo {
            background: linear-gradient(120deg, #015C1E, #0a9c3f);
            color: #fff;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .carteirinha-foto {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            margin: -50px auto 10px;
            display: block;
            background: #fff;
        }
    </style>

</head>

<body class="bg-light" style="padding-top:38px;">
    <div style="position:fixed;top:0;left:0;right:0;z-index:2000;background:#f8f9fa;border-bottom:1px solid #dee2e6;padding:8px 20px;height:38px;box-sizing:border-box;"><button type="button" onclick="if(window.history.length>1){history.back();}else{window.location.href='../../index.html';}" style="background:none;border:none;color:#1B365D;cursor:pointer;font-size:14px;padding:0;" aria-label="Voltar para a página anterior">← Voltar</button></div>

    <header class="border-bottom py-3 mb-2 bg-white">
        <div class="container d-flex align-items-center">
            <a href="../../index.html" class="d-flex align-items-center text-decoration-none">
                <img src="../../assets/img/logo.png" alt="PetFinder Brasil" height="36" class="me-2">
                <span class="fw-bold text-dark">PetFinder Brasil</span>
            </a>
        </div>
    </header>

    <main class="container">

        <?php if (!$pet): ?>

            <div class="alert alert-warning carteirinha" style="max-width:500px;">
                Identidade não encontrada. Confira se o link/QR code está correto.
            </div>

        <?php else: ?>

            <div class="carteirinha bg-white text-center pb-4">

                <div class="carteirinha-topo">
                    <strong>🪪 Identidade Animal</strong>
                    <span class="badge bg-light text-dark"><?= htmlspecialchars($pet["status"]) ?></span>
                </div>

                <?php
                $foto = Foto::url($pet["foto"] !== "sem-foto.png" ? ($pet["foto"] ?? null) : null, 'pets');
                ?>

                <img src="<?= htmlspecialchars($foto) ?>" class="carteirinha-foto" alt="Foto de <?= htmlspecialchars($pet['nome']) ?>">

                <h2 class="mb-0"><?= htmlspecialchars($pet["nome"]) ?></h2>
                <p class="text-muted mb-3"><?= htmlspecialchars($pet["especie"]) ?> · <?= htmlspecialchars($pet["raca"]) ?> · <?= htmlspecialchars($pet["sexo"]) ?></p>

                <div class="text-start px-4">

                    <?php if (!empty($pet["cor"])): ?>
                        <p class="mb-1"><i class="bi bi-palette"></i> Cor: <?= htmlspecialchars($pet["cor"]) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($pet["microchip"])): ?>
                        <p class="mb-1"><i class="bi bi-cpu"></i> Microchip: <?= htmlspecialchars($pet["microchip"]) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($pet["castrado"])): ?>
                        <p class="mb-1"><i class="bi bi-check-circle"></i> Castrado(a)</p>
                    <?php endif; ?>

                    <hr>

                    <p class="mb-1"><i class="bi bi-person-fill"></i> Tutor: <?= htmlspecialchars($pet["tutor_nome"]) ?></p>

                    <?php if (!empty($pet["tutor_telefone"])): ?>
                        <p class="mb-1"><i class="bi bi-telephone-fill"></i> <?= htmlspecialchars($pet["tutor_telefone"]) ?></p>
                    <?php endif; ?>

                </div>

                <?php if ($pet["status"] === 'Perdido'): ?>
                    <div class="alert alert-danger mx-4 mt-3">
                        🚨 Este pet está desaparecido! Se você o encontrou, entre em contato com o tutor.
                    </div>
                <?php endif; ?>

                <div class="px-4 mt-3">
                    <a href="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode('https://petfinderbrasil.com.br/public/identidade_pet.php?token=' . $pet['token_identidade']) ?>"
                        target="_blank" rel="noopener" class="text-decoration-none small text-muted">
                        <i class="bi bi-qr-code"></i> Ver / imprimir QR desta identidade
                    </a>
                </div>

            </div>

        <?php endif; ?>

    </main>

    <footer class="border-top py-4 text-center text-muted mt-4">
        © <?= date("Y") ?> PetFinder Brasil
    </footer>

</body>

</html>
