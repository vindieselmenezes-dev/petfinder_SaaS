<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';



if (!isset($_SESSION["usuario_id"])) {
    header('Location: ' . Url::pagina('login.php'));
    exit;
}

require_once "../../app/Controllers/PrestadorController.php";
require_once "../../app/Helpers/Csrf.php";

$controller = new PrestadorController();
$usuarioId = (int) $_SESSION["usuario_id"];

$prestadorId = (int) ($_GET["prestador_id"] ?? 0);
$prestador = $controller->buscarPorId($prestadorId);

if (!$prestador || (int) $prestador["usuario_id"] !== $usuarioId) {
    header('Location: ' . Url::pagina('dashboard.php'));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && Csrf::validar($_POST["csrf_token"] ?? null)) {
    $solicitacaoId = (int) ($_POST["solicitacao_id"] ?? 0);
    $status = $_POST["status"] ?? "";

    if (in_array($status, ['aceita', 'recusada', 'concluida', 'cancelada'], true)) {
        $controller->atualizarStatusSolicitacao($solicitacaoId, $prestadorId, $status);
    }

    header("Location: solicitacoes_prestador.php?prestador_id=" . $prestadorId);
    exit;
}

$solicitacoes = $controller->listarSolicitacoesRecebidas($prestadorId);

$tituloPagina = "Solicitações Recebidas";

$statusCores = [
    'pendente' => 'warning',
    'aceita' => 'success',
    'recusada' => 'danger',
    'concluida' => 'primary',
    'cancelada' => 'secondary',
];

?>

<?php require_once "../../app/Includes/header.php"; ?>

<?php require_once "../../app/Includes/menu.php"; ?>

<main class="conteudo">

<div class="container">

<h1>📋 Solicitações Recebidas</h1>
<p>Pedidos de tutores para o seu perfil de <?= match ($prestador['tipo']) { 'pet_sitter' => 'Pet Sitter', 'taxista_pet' => 'Táxi Pet', 'adestrador' => 'Adestrador', default => 'Passeador' } ?>.</p>

<?php if ($controller->trialVencido($prestadorId)): ?>
    <div class="mensagem erro">
        ⏰ Seu período de teste venceu. Seu perfil parou de aparecer nas buscas e não recebe novas solicitações até você assinar um plano pago.
        <br>
        <a href="<?= Url::pagina('planos.php') ?>?prestador_id=<?= $prestadorId ?>" class="btn btn-success btn-sm mt-2">📈 Ver planos</a>
    </div>
<?php endif; ?>

<?php if (empty($solicitacoes)): ?>

    <div class="mensagem">Você ainda não recebeu nenhuma solicitação.</div>

<?php else: ?>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Tutor</th>
                    <th>Pet</th>
                    <th>Data desejada</th>
                    <th>Período</th>
                    <th>Mensagem</th>
                    <th>Status</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($solicitacoes as $s): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($s['tutor_nome']) ?>
                            <?php if (!empty($s['tutor_telefone'])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($s['tutor_telefone']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($s['pet_nome'] ?? '-') ?></td>
                        <td><?= $s['data_desejada'] ? htmlspecialchars($s['data_desejada']) : '-' ?></td>
                        <td><?= htmlspecialchars($s['periodo'] ?? '-') ?></td>
                        <td class="small"><?= htmlspecialchars(mb_strimwidth($s['mensagem'] ?? '', 0, 60, '...')) ?></td>
                        <td><span class="badge bg-<?= $statusCores[$s['status']] ?? 'secondary' ?>"><?= htmlspecialchars($s['status']) ?></span></td>
                        <td>
                            <?php if ($s['status'] === 'pendente'): ?>
                                <form method="POST" class="d-inline">
                                    <?= Csrf::campoHtml() ?>
                                    <input type="hidden" name="solicitacao_id" value="<?= (int) $s['id'] ?>">
                                    <input type="hidden" name="status" value="aceita">
                                    <button type="submit" class="btn btn-sm btn-success">Aceitar</button>
                                </form>
                                <form method="POST" class="d-inline">
                                    <?= Csrf::campoHtml() ?>
                                    <input type="hidden" name="solicitacao_id" value="<?= (int) $s['id'] ?>">
                                    <input type="hidden" name="status" value="recusada">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Recusar</button>
                                </form>
                            <?php elseif ($s['status'] === 'aceita'): ?>
                                <form method="POST" class="d-inline">
                                    <?= Csrf::campoHtml() ?>
                                    <input type="hidden" name="solicitacao_id" value="<?= (int) $s['id'] ?>">
                                    <input type="hidden" name="status" value="concluida">
                                    <button type="submit" class="btn btn-sm btn-primary">Marcar concluída</button>
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
