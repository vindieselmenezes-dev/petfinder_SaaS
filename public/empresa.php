<?php

declare(strict_types=1);

require_once "../app/Controllers/EmpresaController.php";

$controller = new EmpresaController();

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

$empresa = $controller->buscarPorId($id);

$horarios = $empresa ? $controller->buscarHorarios($id) : [];
$galeria  = $empresa ? $controller->buscarGaleria($id) : [];

$horariosPorDia = [];
foreach ($horarios as $horario) {
    $horariosPorDia[$horario['dia_semana']] = $horario;
}

$diasSemana = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $empresa ? htmlspecialchars($empresa["nome_fantasia"]) . " - " : "" ?>PetFinder Brasil</title>

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

            <a href="empresas.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i>
                Voltar para Empresas
            </a>

        </div>

    </header>

    <main class="mb-5">

    <?php if (!$empresa): ?>

        <div class="container">
            <div class="alert alert-warning text-center py-5">
                <h2>Empresa não encontrada.</h2>
                <p class="mb-0">O link pode estar incorreto ou a empresa já não está mais ativa.</p>
            </div>
        </div>

    <?php else: ?>

        <!-- CAPA -->

        <?php
            $capa = !empty($empresa["capa"])
                ? "../uploads/empresas/" . $empresa["capa"]
                : "../assets/img/pets/sem-foto.png";
        ?>

        <div style="height: 280px; overflow: hidden; background: #e9ecef;">
            <img src="<?= htmlspecialchars($capa) ?>" style="width:100%; height:100%; object-fit:cover;" alt="Capa">
        </div>

        <div class="container">

            <div class="row">

                <!-- LOGO + INFO PRINCIPAL -->

                <div class="col-12">

                    <div class="d-flex align-items-end gap-3" style="margin-top:-60px;">

                        <?php if (!empty($empresa["logo"])): ?>
                            <img
                                src="../uploads/empresas/<?= htmlspecialchars($empresa["logo"]) ?>"
                                width="120" height="120"
                                style="object-fit:cover; border-radius:16px; border:4px solid #fff; background:#fff;"
                                alt="Logo">
                        <?php else: ?>
                            <div style="width:120px; height:120px; border-radius:16px; border:4px solid #fff; background:#f1f3f5; display:flex; align-items:center; justify-content:center;">
                                <i class="bi bi-shop fs-1 text-muted"></i>
                            </div>
                        <?php endif; ?>

                        <div class="pb-2">
                            <h1 class="fw-bold mb-1"><?= htmlspecialchars($empresa["nome_fantasia"]) ?></h1>
                            <span class="badge bg-primary">
                                <i class="bi <?= htmlspecialchars($empresa['categoria_icone'] ?? 'bi-shop') ?>"></i>
                                <?= htmlspecialchars($empresa["categoria_nome"]) ?>
                            </span>
                            <?php if (!empty($empresa["verificada"])): ?>
                                <span class="badge bg-success">
                                    <i class="bi bi-patch-check-fill"></i> Verificada
                                </span>
                            <?php endif; ?>
                        </div>

                    </div>

                </div>

            </div>

            <div class="row g-4 mt-2">

                <!-- COLUNA PRINCIPAL -->

                <div class="col-lg-8">

                    <?php if (!empty($empresa["descricao"])): ?>
                        <h5>Sobre</h5>
                        <p><?= nl2br(htmlspecialchars($empresa["descricao"])) ?></p>
                        <hr>
                    <?php endif; ?>

                    <?php if ((float) $empresa["avaliacao"] > 0): ?>
                        <p>
                            ⭐ <strong><?= number_format((float) $empresa["avaliacao"], 1) ?></strong>
                            <span class="text-muted">(<?= (int) $empresa["total_avaliacoes"] ?> avaliações)</span>
                        </p>
                    <?php endif; ?>

                    <!-- GALERIA -->

                    <?php if (!empty($galeria)): ?>

                        <h5 class="mt-4">Galeria de Fotos</h5>

                        <div class="row g-2 mb-4">

                            <?php foreach ($galeria as $imagem): ?>
                                <div class="col-4 col-md-3">
                                    <img
                                        src="../uploads/empresas/<?= htmlspecialchars($imagem["imagem"]) ?>"
                                        class="img-fluid rounded-3"
                                        style="width:100%; height:130px; object-fit:cover;"
                                        alt="Foto da empresa">
                                </div>
                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                    <!-- HORÁRIO -->

                    <h5>Horário de Funcionamento</h5>

                    <table class="table table-sm w-auto">
                        <tbody>
                            <?php foreach ($diasSemana as $dia): ?>
                                <?php $h = $horariosPorDia[$dia] ?? null; ?>
                                <tr>
                                    <td class="fw-semibold pe-4"><?= $dia ?></td>
                                    <td>
                                        <?php if ($h && !empty($h['fechado'])): ?>
                                            <span class="text-danger">Fechado</span>
                                        <?php elseif ($h && !empty($h['abertura']) && !empty($h['fechamento'])): ?>
                                            <?= substr($h['abertura'], 0, 5) ?> às <?= substr($h['fechamento'], 0, 5) ?>
                                        <?php else: ?>
                                            <span class="text-muted">Não informado</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                </div>

                <!-- COLUNA LATERAL: CONTATO -->

                <div class="col-lg-4">

                    <div class="border rounded-3 p-4 sticky-top" style="top: 20px;">

                        <h5>Contato</h5>

                        <?php if (!empty($empresa["endereco"])): ?>
                            <p class="mb-2">
                                <i class="bi bi-geo-alt-fill"></i>
                                <?= htmlspecialchars($empresa["endereco"]) ?><?= !empty($empresa["numero"]) ? ", " . htmlspecialchars($empresa["numero"]) : "" ?>
                                <?php if (!empty($empresa["bairro"])): ?><br><?= htmlspecialchars($empresa["bairro"]) ?><?php endif; ?>
                                <?php if (!empty($empresa["cidade"])): ?><br><?= htmlspecialchars($empresa["cidade"]) ?> / <?= htmlspecialchars($empresa["estado"]) ?><?php endif; ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($empresa["telefone"])): ?>
                            <p class="mb-2">
                                <i class="bi bi-telephone-fill"></i>
                                <?= htmlspecialchars($empresa["telefone"]) ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($empresa["email"])): ?>
                            <p class="mb-2">
                                <i class="bi bi-envelope-fill"></i>
                                <?= htmlspecialchars($empresa["email"]) ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($empresa["site"])): ?>
                            <p class="mb-3">
                                <i class="bi bi-globe"></i>
                                <a href="<?= htmlspecialchars($empresa["site"]) ?>" target="_blank" rel="noopener">
                                    <?= htmlspecialchars($empresa["site"]) ?>
                                </a>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($empresa["whatsapp"])): ?>
                            <a href="https://wa.me/55<?= preg_replace('/\D/', '', $empresa["whatsapp"]) ?>"
                               target="_blank" rel="noopener"
                               class="btn btn-success w-100">
                                <i class="bi bi-whatsapp"></i>
                                Falar no WhatsApp
                            </a>
                        <?php else: ?>
                            <div class="alert alert-secondary mb-0">
                                WhatsApp não informado.
                            </div>
                        <?php endif; ?>

                    </div>

                </div>

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
