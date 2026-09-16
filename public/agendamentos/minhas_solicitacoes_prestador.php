<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';



if (!isset($_SESSION["usuario_id"])) {
    header('Location: ' . Url::pagina('login.php'));
    exit;
}

require_once "../../app/Controllers/PrestadorController.php";

$controller = new PrestadorController();
$usuarioId = (int) $_SESSION["usuario_id"];

$solicitacoes = $controller->listarSolicitacoesFeitas($usuarioId);

$tituloPagina = "Minhas Solicitações de Passeio/Pet Sitter";

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

<h1>🐕 Minhas Solicitações</h1>
<p>Pedidos que você enviou para passeadores e pet sitters.</p>

<?php if (empty($solicitacoes)): ?>

    <div class="mensagem">
        Você ainda não solicitou nenhum serviço.
        <a href="prestadores.php">Encontrar um profissional</a>
    </div>

<?php else: ?>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Profissional</th>
                    <th>Tipo</th>
                    <th>Data desejada</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($solicitacoes as $s): ?>
                    <tr>
                        <td><a href="prestador.php?id=<?= (int) $s['prestador_id'] ?>"><?= htmlspecialchars($s['prestador_nome']) ?></a></td>
                        <td><?= match ($s['tipo']) { 'pet_sitter' => '🏠 Pet Sitter', 'taxista_pet' => '🚕 Táxi Pet', 'adestrador' => '🎓 Adestrador', default => '🐕 Passeador' } ?></td>
                        <td><?= $s['data_desejada'] ? htmlspecialchars($s['data_desejada']) : '-' ?></td>
                        <td><span class="badge bg-<?= $statusCores[$s['status']] ?? 'secondary' ?>"><?= htmlspecialchars($s['status']) ?></span></td>
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
