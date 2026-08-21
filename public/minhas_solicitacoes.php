<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../app/Controllers/SolicitacaoAdocaoController.php';
require_once '../app/Helpers/Csrf.php';

$controller = new SolicitacaoAdocaoController();
$usuarioId = (int) $_SESSION['usuario_id'];

$mensagem = '';
$tipoMensagem = '';

if (isset($_GET['enviado'])) {
    $mensagem = 'Solicitação enviada! O dono do pet vai receber sua mensagem e pode te responder.';
    $tipoMensagem = 'sucesso';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_id'])) {
    if (Csrf::validar($_POST['csrf_token'] ?? null)) {
        $resultado = $controller->cancelar((int) $_POST['cancelar_id'], $usuarioId);
        $mensagem = $resultado['sucesso'] ? 'Solicitação cancelada.' : $resultado['erro'];
        $tipoMensagem = $resultado['sucesso'] ? 'sucesso' : 'erro';
    }
}

$solicitacoes = $controller->listarEnviadas($usuarioId);

$tituloPagina = "Minhas Solicitações de Adoção";

require_once '../app/Includes/header.php';
require_once '../app/Includes/menu.php';

$corStatus = [
    'Pendente' => '#f39c12',
    'Aprovada' => '#2ecc71',
    'Rejeitada' => '#e74c3c',
    'Cancelada' => '#95a5a6',
];
?>

<main class="conteudo">
<div class="container">

    <h1>🏠 Minhas Solicitações de Adoção</h1>

    <?php if ($mensagem): ?>
        <div class="mensagem <?= $tipoMensagem; ?>"><?= htmlspecialchars($mensagem); ?></div>
    <?php endif; ?>

    <?php if (count($solicitacoes) > 0): ?>
        <?php foreach ($solicitacoes as $s): ?>
            <div class="org-item" style="display:flex; justify-content:space-between; align-items:center;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <?php if (!empty($s['pet_foto']) && file_exists(__DIR__ . '/../uploads/pets/' . $s['pet_foto'])): ?>
                        <img src="../uploads/pets/<?= htmlspecialchars($s['pet_foto']); ?>" width="50" height="50" style="border-radius:50%; object-fit:cover;">
                    <?php else: ?>
                        <div style="width:50px; height:50px; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center;">🐶</div>
                    <?php endif; ?>
                    <div>
                        <strong><?= htmlspecialchars($s['pet_nome']); ?></strong>
                        <span class="badge-status" style="background:<?= $corStatus[$s['status']] ?? '#95a5a6'; ?>; margin-left:6px;"><?= htmlspecialchars($s['status']); ?></span>
                        <span style="font-size:12px; color:#7f8c8d; display:block;">Enviado em <?= date('d/m/Y', strtotime($s['criado_em'])); ?></span>
                    </div>
                </div>
                <div style="display:flex; gap:6px;">
                    <?php if (!empty($s['conversa_id'])): ?>
                        <a href="conversa.php?id=<?= (int) $s['conversa_id']; ?>" class="btn-acao" style="background:#3498db; color:white;">💬 Conversa</a>
                    <?php endif; ?>
                    <?php if ($s['status'] === 'Pendente'): ?>
                        <form method="POST" onsubmit="return confirm('Cancelar essa solicitação?');" style="display:inline;">
                            <?= Csrf::campoHtml(); ?>
                            <input type="hidden" name="cancelar_id" value="<?= (int) $s['id']; ?>">
                            <button type="submit" class="btn-acao" style="background:#e74c3c; color:white; border:none; cursor:pointer;">Cancelar</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color:#95a5a6; font-style:italic;">Você ainda não solicitou nenhuma adoção. <a href="pets_adocao.php">Ver pets disponíveis</a></p>
    <?php endif; ?>

</div>
</main>

<?php require_once '../app/Includes/footer.php'; ?>
