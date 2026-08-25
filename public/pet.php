<?php

declare(strict_types=1);

session_start();

require_once "../app/Controllers/PetController.php";
require_once "../app/Models/Favorito.php";

$controller = new PetController();
$favoritoModel = new Favorito();

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

$pet = $controller->buscarPorId($id);
$imagensAdicionais = $pet ? $controller->buscarImagens($id) : [];

$statusCores = [
    "Para Adoção" => "success",
    "Perdido"     => "danger",
    "Encontrado"  => "info",
    "Adotado"     => "secondary",
    "Com Tutor"   => "primary"
];

$corStatus = $pet ? ($statusCores[$pet["status"]] ?? "secondary") : "secondary";

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $pet ? htmlspecialchars($pet["nome"]) . " - " : "" ?>PetFinder Brasil</title>

    <!-- BOOTSTRAP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- BOOTSTRAP ICONS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

    <!-- ========================================================= -->
    <!-- CABEÇALHO SIMPLES -->
    <!-- ========================================================= -->

    <header class="border-bottom py-3 mb-4">

        <div class="container d-flex align-items-center justify-content-between">

            <a href="../index.html" class="d-flex align-items-center text-decoration-none">

                <img src="../assets/img/logo.png" alt="PetFinder Brasil" height="40" class="me-2">

                <div>
                    <div class="fw-bold text-dark">PetFinder Brasil</div>
                    <small class="text-muted">Tudo para seu pet em um só lugar</small>
                </div>

            </a>

            <a href="../index.html#adocao" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i>
                Voltar para os pets
            </a>

        </div>

    </header>

    <main class="container mb-5">

    <?php if (!$pet): ?>

        <div class="alert alert-warning text-center py-5">
            <h2>Pet não encontrado.</h2>
            <p class="mb-0">O link pode estar incorreto ou o pet já não está mais disponível.</p>
        </div>

    <?php else: ?>

        <div class="row g-4">

            <!-- FOTO -->

            <div class="col-lg-5">

                <?php
                    $foto = (!empty($pet["foto"]) && $pet["foto"] !== "sem-foto.png")
                        ? "../uploads/pets/" . $pet["foto"]
                        : "../assets/img/pets/sem-foto.png";
                ?>

                <img src="<?= htmlspecialchars($foto) ?>"
                     class="img-fluid rounded-4 shadow-sm w-100"
                     style="object-fit: cover; max-height: 480px;"
                     alt="<?= htmlspecialchars($pet["nome"]) ?>">

                <?php if (!empty($imagensAdicionais)): ?>
                    <div class="mt-3 d-flex flex-wrap gap-2">
                        <?php foreach ($imagensAdicionais as $imagem): ?>
                            <img src="../uploads/pets/<?= htmlspecialchars($imagem['arquivo']) ?>"
                                 class="img-thumbnail"
                                 style="width: 120px; height: 90px; object-fit: cover;"
                                 alt="Imagem adicional">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>

            <!-- INFORMAÇÕES -->

            <div class="col-lg-7">

                <span class="badge bg-<?= $corStatus ?> mb-2">
                    <?= htmlspecialchars($pet["status"]) ?>
                </span>

                <h1 class="fw-bold"><?= htmlspecialchars($pet["nome"]) ?></h1>

                <p class="text-muted fs-5">
                    <?= htmlspecialchars($pet["especie"]) ?> • <?= htmlspecialchars($pet["raca"]) ?>
                </p>

                <div class="row g-3 my-3">

                    <div class="col-6 col-md-4">
                        <div class="border rounded-3 p-3 text-center h-100">
                            <div class="text-muted small">Sexo</div>
                            <div class="fw-semibold"><?= htmlspecialchars($pet["sexo"] ?: "Não informado") ?></div>
                        </div>
                    </div>

                    <div class="col-6 col-md-4">
                        <div class="border rounded-3 p-3 text-center h-100">
                            <div class="text-muted small">Cor</div>
                            <div class="fw-semibold"><?= htmlspecialchars($pet["cor"] ?: "Não informada") ?></div>
                        </div>
                    </div>

                    <div class="col-6 col-md-4">
                        <div class="border rounded-3 p-3 text-center h-100">
                            <div class="text-muted small">Peso</div>
                            <div class="fw-semibold">
                                <?= $pet["peso"] !== null ? htmlspecialchars((string) $pet["peso"]) . " kg" : "Não informado" ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-4">
                        <div class="border rounded-3 p-3 text-center h-100">
                            <div class="text-muted small">Altura</div>
                            <div class="fw-semibold">
                                <?= $pet["altura"] !== null ? htmlspecialchars((string) $pet["altura"]) . " cm" : "Não informado" ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-4">
                        <div class="border rounded-3 p-3 text-center h-100">
                            <div class="text-muted small">Castrado</div>
                            <div class="fw-semibold"><?= !empty($pet["castrado"]) ? "Sim" : "Não" ?></div>
                        </div>
                    </div>

                    <div class="col-6 col-md-4">
                        <div class="border rounded-3 p-3 text-center h-100">
                            <div class="text-muted small">Cidade</div>
                            <div class="fw-semibold">
                                <?= htmlspecialchars($pet["cidade"] ?? '') ?: "Não informada" ?>
                            </div>
                        </div>
                    </div>

                </div>

                <?php if (!empty($pet["observacoes"])): ?>

                    <h5 class="mt-4">Sobre <?= htmlspecialchars($pet["nome"]) ?></h5>
                    <p><?= nl2br(htmlspecialchars($pet["observacoes"])) ?></p>

                <?php endif; ?>

                <hr class="my-4">

                <?php if (isset($_SESSION["usuario_id"]) && $pet): ?>
                    <div class="d-flex gap-2 flex-wrap mb-4">
                        <?php if ($favoritoModel->existe((int) $_SESSION["usuario_id"], (int) $pet["id"])): ?>
                            <a href="favoritar.php?pet_id=<?= (int) $pet["id"]; ?>&acao=remover" class="btn btn-danger">
                                <i class="bi bi-star-fill"></i> Remover dos favoritos
                            </a>
                        <?php else: ?>
                            <a href="favoritar.php?pet_id=<?= (int) $pet["id"]; ?>&acao=adicionar" class="btn btn-warning text-dark">
                                <i class="bi bi-star"></i> Favoritar pet
                            </a>
                        <?php endif; ?>

                        <?php if (($pet["status"] ?? '') === 'Para Adoção' && (int) $pet["usuario_id"] !== (int) $_SESSION["usuario_id"]): ?>
                            <a href="solicitar_adocao.php?pet_id=<?= (int) $pet["id"]; ?>" class="btn btn-success">
                                <i class="bi bi-house-heart"></i> Quero Adotar
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($pet): ?>
                    <p class="mb-4">
                        <a href="historico_pet.php?id=<?= (int) $pet["id"]; ?>" class="text-decoration-none text-secondary">
                            <i class="bi bi-clock-history"></i> Ver histórico completo deste pet
                        </a>
                    </p>
                <?php endif; ?>

                <h5>Tutor responsável</h5>

                <p class="mb-3">
                    <?= htmlspecialchars($pet["tutor_nome"] ?? 'Não informado') ?>
                </p>

                <?php if (!empty($pet["tutor_telefone"])): ?>

                    <a href="https://wa.me/55<?= preg_replace('/\D/', '', $pet["tutor_telefone"]) ?>"
                       target="_blank" rel="noopener"
                       class="btn btn-success btn-lg">
                        <i class="bi bi-whatsapp"></i>
                        Falar com o tutor no WhatsApp
                    </a>

                <?php else: ?>

                    <div class="alert alert-secondary mb-0">
                        Este tutor ainda não cadastrou um telefone de contato.
                    </div>

                <?php endif; ?>

            </div>

        </div>

    <?php endif; ?>

    </main>

    <footer class="border-top py-4 text-center text-muted">
        © <?= date("Y") ?> PetFinder Brasil
    </footer>

    <!-- BOOTSTRAP JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
