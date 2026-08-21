<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../app/Controllers/ConversaController.php';
require_once '../app/Helpers/Csrf.php';

$controller = new ConversaController();
$usuarioId = (int) $_SESSION['usuario_id'];

$conversaId = (int) ($_GET['id'] ?? 0);
$conversaInfo = $controller->buscarPorId($conversaId, $usuarioId);

if (!$conversaInfo) {
    die("<h1 style='text-align:center; margin-top:50px; color:red;'>Conversa não encontrada ou você não tem permissão pra ver ela.</h1>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensagem'])) {
    if (Csrf::validar($_POST['csrf_token'] ?? null)) {
        $controller->enviarMensagem($conversaId, $usuarioId, $_POST['mensagem']);
    }
    header('Location: conversa.php?id=' . $conversaId);
    exit;
}

$controller->marcarComoLidas($conversaId, $usuarioId);
$mensagens = $controller->listarMensagens($conversaId);

$outroNome = (int) $conversaInfo['usuario_origem'] === $usuarioId
    ? $conversaInfo['destino_nome']
    : $conversaInfo['origem_nome'];

$tituloPagina = "Conversa com " . $outroNome;

require_once '../app/Includes/header.php';
require_once '../app/Includes/menu.php';
?>

<main class="conteudo">
<div class="container" style="max-width:650px; margin:0 auto;">

    <p><a href="conversas.php" style="color:#7f8c8d; text-decoration:none;">⬅ Voltar pras Mensagens</a></p>

    <div class="formulario-cadastro">
        <h2 style="margin-top:0;">💬 <?= htmlspecialchars($outroNome); ?></h2>
        <p style="color:#7f8c8d; font-size:13px; margin-top:-10px;">Assunto: <?= htmlspecialchars($conversaInfo['assunto'] ?? ''); ?></p>

        <div style="max-height:400px; overflow-y:auto; margin:15px 0; padding-right:5px;">
            <?php foreach ($mensagens as $m):
                $souEu = (int) $m['remetente_id'] === $usuarioId;
            ?>
                <div style="display:flex; justify-content:<?= $souEu ? 'flex-end' : 'flex-start'; ?>; margin-bottom:10px;">
                    <div style="max-width:75%; background:<?= $souEu ? '#3498db' : '#f1f3f5'; ?>; color:<?= $souEu ? 'white' : '#333'; ?>; padding:10px 14px; border-radius:14px; <?= $souEu ? 'border-bottom-right-radius:4px;' : 'border-bottom-left-radius:4px;'; ?>">
                        <p style="margin:0; white-space:pre-line;"><?= htmlspecialchars($m['mensagem']); ?></p>
                        <span style="font-size:11px; opacity:.7; display:block; margin-top:4px;"><?= date('d/m H:i', strtotime($m['enviado_em'])); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="POST" style="display:flex; gap:8px;">
            <?= Csrf::campoHtml(); ?>
            <input type="text" name="mensagem" class="form-control" autocomplete="off" required placeholder="Escreva uma mensagem..." style="flex:1;">
            <button type="submit" class="btn-acao" style="background:#3498db; color:white; border:none; padding:0 20px; border-radius:6px; font-weight:bold; cursor:pointer;">Enviar</button>
        </form>
    </div>

</div>
</main>

<?php require_once '../app/Includes/footer.php'; ?>
