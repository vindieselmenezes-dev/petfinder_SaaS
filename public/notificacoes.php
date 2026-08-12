<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../app/Controllers/NotificacaoController.php";

$controller = new NotificacaoController();
$usuarioId  = (int) $_SESSION["usuario_id"];

$notificacoes = $controller->listarPorUsuario($usuarioId);

$iconePorTipo = [
    "Sistema"   => "bi-gear-fill text-secondary",
    "Pedido"    => "bi-box-seam-fill text-primary",
    "Pagamento" => "bi-credit-card-fill text-success",
    "Consulta"  => "bi-heart-pulse-fill text-danger",
    "Promoção"  => "bi-tag-fill text-warning"
];

require_once "../app/Includes/header.php";
require_once "../app/Includes/menu.php";
?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Notificações</title>

<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

</head>

<body>

<main class="conteudo">

<div class="container">

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">

    <h1>🔔 Notificações</h1>

    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnMarcarTodas">
        Marcar todas como lidas
    </button>

</div>

<div id="listaNotificacoes">

    <?php if (count($notificacoes) === 0): ?>

        <div class="text-center text-muted py-5">
            <i class="bi bi-bell-slash display-4"></i>
            <p class="mt-3">Você ainda não tem nenhuma notificação.</p>
        </div>

    <?php else: ?>

        <?php foreach ($notificacoes as $notificacao): ?>

            <?php $icone = $iconePorTipo[$notificacao["tipo"]] ?? "bi-bell-fill text-secondary"; ?>

            <div class="card mb-2 notificacao-item <?= empty($notificacao["lida"]) ? "border-primary" : "" ?>"
                 data-id="<?= (int) $notificacao["id"] ?>">

                <div class="card-body d-flex gap-3 align-items-start">

                    <i class="bi <?= $icone ?> fs-4"></i>

                    <div class="flex-grow-1">

                        <div class="d-flex justify-content-between align-items-start">
                            <strong><?= htmlspecialchars($notificacao["titulo"] ?? "Notificação") ?></strong>
                            <?php if (empty($notificacao["lida"])): ?>
                                <span class="badge bg-primary">Nova</span>
                            <?php endif; ?>
                        </div>

                        <p class="mb-1 text-muted"><?= nl2br(htmlspecialchars($notificacao["mensagem"] ?? "")) ?></p>

                        <small class="text-muted">
                            <?= date("d/m/Y H:i", strtotime($notificacao["criado_em"])) ?>
                            · <?= htmlspecialchars($notificacao["tipo"]) ?>
                        </small>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</div>

</div>

</main>

<?php require_once "../app/Includes/footer.php"; ?>

<script>

/*
|--------------------------------------------------------------------------
| Marca uma notificação como lida ao clicar nela
|--------------------------------------------------------------------------
*/

document.querySelectorAll(".notificacao-item").forEach(function (item) {

    item.addEventListener("click", function () {

        const id = this.dataset.id;

        if (!this.classList.contains("border-primary")) return;

        fetch("../app/ajax/marcar_notificacao_lida.php?id=" + id)
            .then(function () {
                item.classList.remove("border-primary");
                const badge = item.querySelector(".badge");
                if (badge) badge.remove();
            });

    });

});

/*
|--------------------------------------------------------------------------
| Marca todas como lidas
|--------------------------------------------------------------------------
*/

const btnMarcarTodas = document.getElementById("btnMarcarTodas");

if (btnMarcarTodas) {

    btnMarcarTodas.addEventListener("click", function () {

        fetch("../app/ajax/marcar_todas_notificacoes.php")
            .then(function () {
                document.querySelectorAll(".notificacao-item").forEach(function (item) {
                    item.classList.remove("border-primary");
                    const badge = item.querySelector(".badge");
                    if (badge) badge.remove();
                });
            });

    });

}

</script>

</body>

</html>
