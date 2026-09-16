<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

if (!isset($_SESSION["usuario_id"])) {
    header('Location: ' . Url::pagina('login.php'));
    exit;
}

require_once "../../app/Controllers/EmpresaController.php";
require_once "../../app/Helpers/EmpresaAcesso.php";
require_once "../../app/Models/Pedido.php";

$empresaController = new EmpresaController();
$pedidoModel = new Pedido();
$pdo = Database::conectar();

$usuarioId = (int) $_SESSION["usuario_id"];
$empresaId = (int) ($_GET["empresa_id"] ?? 0);

$empresa = $empresaController->buscarPorId($empresaId);

if ($empresa === null || !EmpresaAcesso::temAcesso($pdo, $empresaId, $usuarioId)) {
    Flash::erro("Empresa não encontrada.");
    header('Location: ' . Url::pagina('minhas_empresas.php'));
    exit;
}

$vendas = $pedidoModel->listarVendasPorEmpresa($empresaId);

$totalVendido = 0.0;
$totalComissao = 0.0;
$totalRepasse = 0.0;
foreach ($vendas as $venda) {
    $totalVendido += (float) $venda['subtotal'];
    $totalComissao += (float) $venda['valor_comissao'];
    $totalRepasse += (float) $venda['valor_repasse'];
}

$corStatus = [
    'Aguardando Pagamento' => 'warning text-dark',
    'Pago' => 'success',
    'Separação' => 'info text-dark',
    'Enviado' => 'primary',
    'Entregue' => 'success',
    'Cancelado' => 'danger',
];

require_once "../../app/Includes/header.php";
require_once "../../app/Includes/menu.php";
?>

<main class="conteudo">

    <h1>💰 Vendas - <?= htmlspecialchars($empresa["nome_fantasia"]) ?></h1>

    <p><a href="<?= Url::pagina('meus_produtos.php') ?>?empresa_id=<?= $empresaId ?>">← Voltar para Produtos</a></p>

    <p class="text-muted" style="max-width: 640px;">
        Valores <strong>simulados</strong> — o PetFinder ainda não tem integração com
        um gateway de pagamento real, então nenhum valor é efetivamente transferido.
        Esta tela mostra quanto seria retido pela plataforma e quanto vocês receberiam
        em cada venda, com a taxa de comissão atual do marketplace.
    </p>

    <div class="row mb-4" style="display:flex; gap:16px; flex-wrap:wrap;">
        <div class="card" style="padding:16px; min-width:200px;">
            <div class="text-muted" style="font-size:.85rem;">Total vendido</div>
            <div style="font-size:1.4rem; font-weight:700;">R$ <?= number_format($totalVendido, 2, ',', '.') ?></div>
        </div>
        <div class="card" style="padding:16px; min-width:200px;">
            <div class="text-muted" style="font-size:.85rem;">Comissão da plataforma</div>
            <div style="font-size:1.4rem; font-weight:700; color:#e67e22;">R$ <?= number_format($totalComissao, 2, ',', '.') ?></div>
        </div>
        <div class="card" style="padding:16px; min-width:200px;">
            <div class="text-muted" style="font-size:.85rem;">Você receberia</div>
            <div style="font-size:1.4rem; font-weight:700; color:#27ae60;">R$ <?= number_format($totalRepasse, 2, ',', '.') ?></div>
        </div>
    </div>

    <table class="tabela-pets">
        <thead>
            <tr>
                <th>Pedido</th>
                <th>Data</th>
                <th>Produto</th>
                <th>Qtd.</th>
                <th>Subtotal</th>
                <th>Comissão</th>
                <th>Você recebe</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>

            <?php if (count($vendas) > 0): ?>

                <?php foreach ($vendas as $venda): ?>
                    <tr>
                        <td><?= htmlspecialchars($venda['numero_pedido'] ?? ('#' . $venda['pedido_id'])) ?></td>
                        <td><?= date('d/m/Y', strtotime($venda['criado_em'])) ?></td>
                        <td><?= htmlspecialchars($venda['produto_nome']) ?></td>
                        <td><?= (int) $venda['quantidade'] ?></td>
                        <td>R$ <?= number_format((float) $venda['subtotal'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format((float) $venda['valor_comissao'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format((float) $venda['valor_repasse'], 2, ',', '.') ?></td>
                        <td><span class="badge bg-<?= $corStatus[$venda['status']] ?? 'secondary' ?>"><?= htmlspecialchars($venda['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="8">Nenhuma venda registrada ainda.</td>
                </tr>

            <?php endif; ?>

        </tbody>
    </table>

</main>

<?php
require_once "../../app/Includes/footer.php";
