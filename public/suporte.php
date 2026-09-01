<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../app/Controllers/SuporteController.php";

$controller = new SuporteController();
$usuarioId  = (int) $_SESSION["usuario_id"];

$chamados = $controller->listarPorUsuario($usuarioId);

$tituloPagina = "Fale com o Suporte";

require_once "../app/Includes/header.php";
require_once "../app/Includes/menu.php";
?>

<main class="conteudo">
<div class="container">

    <h1>💬 Fale com o Suporte</h1>
    <p style="color:#777; margin-bottom:20px;">Abra um chamado pra tirar dúvidas, reportar problemas ou fazer uma reclamação. Nossa equipe responde por aqui mesmo.</p>

    <?php if (isset($_SESSION['sucesso_chamado'])): ?>
        <div class="mensagem sucesso"><?= htmlspecialchars($_SESSION['sucesso_chamado']); unset($_SESSION['sucesso_chamado']); ?></div>
    <?php endif; ?>

    <a href="novo_chamado.php" class="btn-acao" style="background:#3498db; color:white; display:inline-block; margin-bottom:20px;">➕ Abrir novo chamado</a>

    <?php if (count($chamados) > 0): ?>
        <table class="tabela-pets">
            <thead>
                <tr>
                    <th>Assunto</th>
                    <th>Prioridade</th>
                    <th>Status</th>
                    <th>Aberto em</th>
                    <th style="text-align:center;">Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($chamados as $chamado):
                    $corStatus = [
                        'Aberto' => '#3498db',
                        'Em Atendimento' => '#f39c12',
                        'Resolvido' => '#2ecc71',
                        'Fechado' => '#95a5a6',
                    ][$chamado['status']] ?? '#95a5a6';
                ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($chamado['assunto']); ?></strong></td>
                        <td><?= htmlspecialchars($chamado['prioridade']); ?></td>
                        <td><span class="badge-status" style="background:<?= $corStatus; ?>;"><?= htmlspecialchars($chamado['status']); ?></span></td>
                        <td><?= date('d/m/Y H:i', strtotime($chamado['criado_em'])); ?></td>
                        <td style="text-align:center;">
                            <a href="chamado.php?id=<?= (int) $chamado['id']; ?>" class="btn-acao" style="background:#3498db; color:white;">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color:#95a5a6; font-style:italic;">Você ainda não abriu nenhum chamado.</p>
    <?php endif; ?>

</div>
</main>

<?php require_once "../app/Includes/footer.php"; ?>
