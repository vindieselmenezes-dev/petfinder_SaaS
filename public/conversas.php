<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../app/Controllers/ConversaController.php';

$controller = new ConversaController();
$usuarioId = (int) $_SESSION['usuario_id'];

$conversas = $controller->listarPorUsuario($usuarioId);

$tituloPagina = "Mensagens";

require_once '../app/Includes/header.php';
require_once '../app/Includes/menu.php';
?>

<main class="conteudo">
<div class="container">

    <h1>💬 Mensagens</h1>

    <?php if (count($conversas) > 0): ?>
        <?php foreach ($conversas as $c): ?>
            <a href="conversa.php?id=<?= (int) $c['id']; ?>" style="text-decoration:none; color:inherit;">
                <div class="notificacao-item <?= (int) ($c['nao_lidas'] ?? 0) > 0 ? '' : 'lida'; ?>" style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <strong><?= htmlspecialchars($c['outro_nome']); ?></strong>
                        <?php if ((int) ($c['nao_lidas'] ?? 0) > 0): ?>
                            <span class="badge-status" style="background:#e74c3c; margin-left:6px;"><?= (int) $c['nao_lidas']; ?> nova(s)</span>
                        <?php endif; ?>
                        <span style="font-size:13px; color:#7f8c8d; display:block;"><?= htmlspecialchars($c['assunto'] ?? ''); ?></span>
                        <?php if (!empty($c['ultima_mensagem'])): ?>
                            <span style="font-size:13px; color:#999; display:block; margin-top:4px;"><?= htmlspecialchars(mb_strimwidth($c['ultima_mensagem'], 0, 80, '...')); ?></span>
                        <?php endif; ?>
                    </div>
                    <span style="font-size:12px; color:#aaa; white-space:nowrap;">
                        <?= !empty($c['ultima_mensagem_em']) ? date('d/m H:i', strtotime($c['ultima_mensagem_em'])) : date('d/m', strtotime($c['criado_em'])); ?>
                    </span>
                </div>
            </a>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color:#95a5a6; font-style:italic;">Nenhuma conversa ainda.</p>
    <?php endif; ?>

</div>
</main>

<?php require_once '../app/Includes/footer.php'; ?>
