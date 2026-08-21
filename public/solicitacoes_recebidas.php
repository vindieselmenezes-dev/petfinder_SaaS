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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'], $_POST['solicitacao_id'])) {

    if (!Csrf::validar($_POST['csrf_token'] ?? null)) {

        $mensagem = 'Sessão expirada. Atualize a página e tente novamente.';
        $tipoMensagem = 'erro';

    } else {

        $solicitacaoId = (int) $_POST['solicitacao_id'];

        $resultado = $_POST['acao'] === 'aprovar'
            ? $controller->aprovar($solicitacaoId, $usuarioId)
            : $controller->rejeitar($solicitacaoId, $usuarioId);

        $mensagem = $resultado['sucesso']
            ? ($_POST['acao'] === 'aprovar' ? 'Solicitação aprovada! O pet foi transferido pro novo tutor. 🎉' : 'Solicitação rejeitada.')
            : $resultado['erro'];
        $tipoMensagem = $resultado['sucesso'] ? 'sucesso' : 'erro';
    }
}

$solicitacoes = $controller->listarRecebidas($usuarioId);

$tituloPagina = "Solicitações de Adoção Recebidas";

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

    <h1>📥 Solicitações de Adoção Recebidas</h1>

    <?php if ($mensagem): ?>
        <div class="mensagem <?= $tipoMensagem; ?>"><?= htmlspecialchars($mensagem); ?></div>
    <?php endif; ?>

    <?php if (count($solicitacoes) > 0): ?>
        <?php foreach ($solicitacoes as $s): ?>
            <div class="org-item">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <?php if (!empty($s['pet_foto']) && file_exists(__DIR__ . '/../uploads/pets/' . $s['pet_foto'])): ?>
                            <img src="../uploads/pets/<?= htmlspecialchars($s['pet_foto']); ?>" width="50" height="50" style="border-radius:50%; object-fit:cover;">
                        <?php else: ?>
                            <div style="width:50px; height:50px; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center;">🐶</div>
                        <?php endif; ?>
                        <div>
                            <strong><?= htmlspecialchars($s['pet_nome']); ?></strong>
                            <span class="badge-status" style="background:<?= $corStatus[$s['status']] ?? '#95a5a6'; ?>; margin-left:6px;"><?= htmlspecialchars($s['status']); ?></span>
                            <span style="font-size:13px; color:#7f8c8d; display:block;">Interessado(a): <strong><?= htmlspecialchars($s['solicitante_nome']); ?></strong></span>
                        </div>
                    </div>
                    <?php if (!empty($s['conversa_id'])): ?>
                        <a href="conversa.php?id=<?= (int) $s['conversa_id']; ?>" class="btn-acao" style="background:#3498db; color:white;">💬 Conversa</a>
                    <?php endif; ?>
                </div>

                <p style="background:#f8fafc; padding:10px 12px; border-radius:8px; margin:10px 0 0 0; font-size:14px; color:#444;">"<?= htmlspecialchars($s['mensagem']); ?>"</p>

                <?php if ($s['status'] === 'Pendente'): ?>
                    <div style="display:flex; gap:8px; margin-top:10px;">
                        <form method="POST" onsubmit="return confirm('Aprovar esta adoção? O pet será transferido pro novo tutor.');">
                            <?= Csrf::campoHtml(); ?>
                            <input type="hidden" name="solicitacao_id" value="<?= (int) $s['id']; ?>">
                            <input type="hidden" name="acao" value="aprovar">
                            <button type="submit" class="btn-acao" style="background:#2ecc71; color:white; border:none; cursor:pointer;">✅ Aprovar</button>
                        </form>
                        <form method="POST" onsubmit="return confirm('Rejeitar esta solicitação?');">
                            <?= Csrf::campoHtml(); ?>
                            <input type="hidden" name="solicitacao_id" value="<?= (int) $s['id']; ?>">
                            <input type="hidden" name="acao" value="rejeitar">
                            <button type="submit" class="btn-acao" style="background:#e74c3c; color:white; border:none; cursor:pointer;">❌ Rejeitar</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color:#95a5a6; font-style:italic;">Nenhuma solicitação de adoção recebida ainda.</p>
    <?php endif; ?>

</div>
</main>

<?php require_once '../app/Includes/footer.php'; ?>
