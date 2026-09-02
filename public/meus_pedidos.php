<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php?voltar=meus_pedidos.php');
    exit;
}

require_once "../app/Models/Pedido.php";

$pedidoModel = new Pedido();
$pedidos = $pedidoModel->listarPorUsuario((int) $_SESSION['usuario_id']);

$corStatus = [
    'Aguardando Pagamento' => 'warning text-dark',
    'Pago' => 'success',
    'Separação' => 'info text-dark',
    'Enviado' => 'primary',
    'Entregue' => 'success',
    'Cancelado' => 'danger',
];

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Pedidos - PetFinder Brasil</title>
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
            <a href="produtos.php" class="btn btn-outline-primary">
                <i class="bi bi-shop"></i>
                Continuar comprando
            </a>
        </div>
    </header>

    <main class="container mb-5">

        <h1 class="mb-4"><i class="bi bi-receipt"></i> Meus Pedidos</h1>

        <?php if (empty($pedidos)): ?>

            <div class="alert alert-info">
                Você ainda não fez nenhum pedido.
                <a href="produtos.php" class="alert-link">Ver produtos</a>
            </div>

        <?php else: ?>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Data</th>
                            <th>Itens</th>
                            <th>Total</th>
                            <th>Entrega prevista</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($pedido['numero_pedido'] ?? (string) $pedido['id']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($pedido['criado_em'])) ?></td>
                                <td><?= (int) $pedido['total_itens'] ?> item(ns)</td>
                                <td>R$ <?= number_format((float) $pedido['valor_total'], 2, ',', '.') ?></td>
                                <td>
                                    <?= !empty($pedido['previsao_entrega']) ? date('d/m/Y', strtotime($pedido['previsao_entrega'])) : '-' ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $corStatus[$pedido['status']] ?? 'secondary' ?>">
                                        <?= htmlspecialchars($pedido['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="pedido_confirmado.php?id=<?= (int) $pedido['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        Ver detalhes
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
