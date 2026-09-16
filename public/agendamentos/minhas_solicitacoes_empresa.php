<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';



if (!isset($_SESSION["usuario_id"])) {
    header('Location: ' . Url::pagina('login.php'));
    exit;
}

require_once "../../app/Controllers/EmpresaSolicitacaoController.php";
require_once "../../app/Helpers/Csrf.php";

$controller = new EmpresaSolicitacaoController();
$usuarioId = (int) $_SESSION["usuario_id"];

$flashSolicitacao = Flash::consumir();
$sucesso = ($flashSolicitacao && $flashSolicitacao['tipo'] === 'success') ? $flashSolicitacao['mensagem'] : null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && Csrf::validar($_POST["csrf_token"] ?? null)) {
    $solicitacaoId = (int) ($_POST["solicitacao_id"] ?? 0);
    $controller->cancelarPeloTutor($solicitacaoId, $usuarioId);
    header('Location: ' . Url::pagina('minhas_solicitacoes_empresa.php'));
    exit;
}

$solicitacoes = $controller->listarPorTutor($usuarioId);

$tituloPagina = "Minhas Solicitações de Serviço";

$statusRotulos = [
    'pendente' => ['texto' => 'Pendente', 'cor' => 'warning'],
    'aceita' => ['texto' => 'Aceita', 'cor' => 'info'],
    'recusada' => ['texto' => 'Recusada', 'cor' => 'secondary'],
    'concluida' => ['texto' => 'Concluída', 'cor' => 'success'],
    'cancelada' => ['texto' => 'Cancelada', 'cor' => 'secondary'],
];

?>

<?php require_once "../../app/Includes/header.php"; ?>

<?php require_once "../../app/Includes/menu.php"; ?>

<main class="conteudo">

<div class="container">

<h1>🐾 Minhas Solicitações de Serviço</h1>
<p class="text-muted">Pedidos de Banho e Tosa, Hotel/Creche e Adestramento feitos a empresas cadastradas.</p>

<?php if ($sucesso): ?>
    <div class="mensagem sucesso"><?= htmlspecialchars($sucesso) ?></div>
<?php endif; ?>

<?php if (empty($solicitacoes)): ?>

    <div class="mensagem">
        Você ainda não fez nenhum pedido de serviço.
        <a href="<?= Url::pagina('empresas.php') ?>">Encontrar uma empresa</a>
    </div>

<?php else: ?>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Empresa</th>
                    <th>Serviço</th>
                    <th>Pet</th>
                    <th>Data solicitada</th>
                    <th>Busca em casa</th>
                    <th>Confirmação da empresa</th>
                    <th>Status</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($solicitacoes as $s): ?>
                    <?php $rotulo = $statusRotulos[$s['status']] ?? ['texto' => $s['status'], 'cor' => 'secondary']; ?>
                    <tr>
                        <td><?= htmlspecialchars($s['empresa_nome']) ?></td>
                        <td><?= htmlspecialchars($s['servico'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($s['pet_nome'] ?? '-') ?></td>
                        <td>
                            <?= htmlspecialchars($s['data_desejada'] ?? '-') ?>
                            <?= !empty($s['periodo']) ? '(' . htmlspecialchars($s['periodo']) . ')' : '' ?>
                        </td>
                        <td>
                            <?= !empty($s['busca_em_casa']) ? '🚐 Sim' : 'Não' ?>
                        </td>
                        <td class="small">
                            <?php if (!empty($s['data_hora_confirmada']) || !empty($s['profissional_responsavel']) || !empty($s['observacoes_empresa'])): ?>
                                <?php if (!empty($s['data_hora_confirmada'])): ?>
                                    📅 <?= date('d/m/Y \à\s H:i', strtotime($s['data_hora_confirmada'])) ?><br>
                                <?php endif; ?>
                                <?php if (!empty($s['profissional_responsavel'])): ?>
                                    👤 <?= htmlspecialchars($s['profissional_responsavel']) ?><br>
                                <?php endif; ?>
                                <?php if (!empty($s['observacoes_empresa'])): ?>
                                    💬 "<?= htmlspecialchars($s['observacoes_empresa']) ?>"
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">Aguardando confirmação</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-<?= $rotulo['cor'] ?>"><?= htmlspecialchars($rotulo['texto']) ?></span></td>
                        <td>
                            <?php if (in_array($s['status'], ['pendente', 'aceita'], true)): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Cancelar este pedido?');">
                                    <?= Csrf::campoHtml() ?>
                                    <input type="hidden" name="solicitacao_id" value="<?= (int) $s['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Cancelar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>

</div>

</main>

<?php require_once "../../app/Includes/footer.php"; ?>

</body>

</html>
