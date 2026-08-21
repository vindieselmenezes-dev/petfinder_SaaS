<?php

declare(strict_types=1);

session_start();

require_once '../app/Models/Usuario.php';
require_once '../app/Models/ResetSenha.php';
require_once '../app/Helpers/Mailer.php';
require_once '../app/Helpers/Csrf.php';

$usuarioModel = new Usuario();
$resetModel   = new ResetSenha();

$mensagem     = '';
$tipoMensagem = '';
$linkDeTeste  = null; // só preenchido em modo local, ver nota abaixo

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!Csrf::validar($_POST['csrf_token'] ?? null)) {

        $mensagem     = 'Sessão expirada. Atualize a página e tente novamente.';
        $tipoMensagem = 'erro';

    } else {

        $email = trim($_POST['email'] ?? '');
        $usuario = $usuarioModel->buscarPorEmail($email);

        // Por segurança, a mensagem é sempre a mesma exista ou não o e-mail
        // (não dá pra alguém descobrir quais e-mails estão cadastrados)
        $mensagem = 'Se esse e-mail estiver cadastrado, enviamos um link de redefinição.';
        $tipoMensagem = 'sucesso';

        if ($usuario) {
            $token = $resetModel->gerarToken((int) $usuario['id']);
            $link  = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/redefinir_senha.php?token=' . $token;

            Mailer::enviar(
                $usuario['email'],
                'Redefinição de senha - PetFinder Brasil',
                "Olá, {$usuario['nome']}!<br><br>Clique no link abaixo pra criar uma nova senha (válido por 1 hora):<br>
                <a href=\"$link\">$link</a><br><br>Se você não pediu isso, pode ignorar este e-mail."
            );

            // MODO TESTE LOCAL: como o envio de e-mail ainda não está
            // configurado de verdade (ver app/Helpers/Mailer.php), mostramos
            // o link aqui na tela também, só pra dar pra testar o fluxo
            // sem precisar de servidor de e-mail. Remover isso quando o
            // envio real estiver configurado.
            $linkDeTeste = $link;
        }
    }
}

$tituloPagina = "Esqueci minha senha";

require_once '../app/Includes/header.php';
?>

<main class="conteudo" style="margin-left:0 !important;">
<div class="container" style="max-width:450px; margin:100px auto 0 auto;">

    <?php if ($mensagem): ?>
        <div class="mensagem <?= $tipoMensagem; ?>"><?= htmlspecialchars($mensagem); ?></div>
    <?php endif; ?>

    <?php if ($linkDeTeste): ?>
        <div class="mensagem" style="background:#fff8e1; color:#8a6d00; border-color:#ffe082;">
            🧪 <strong>Modo teste local</strong> — o envio de e-mail real ainda não foi configurado.
            Por enquanto, use este link diretamente:<br>
            <a href="<?= htmlspecialchars($linkDeTeste); ?>"><?= htmlspecialchars($linkDeTeste); ?></a>
        </div>
    <?php endif; ?>

    <div class="formulario-cadastro">
        <h1 style="text-align:center;">🔑 Esqueci Minha Senha</h1>
        <p style="color:#7f8c8d; text-align:center; font-size:14px; margin-bottom:20px;">Digite seu e-mail cadastrado pra receber o link de redefinição.</p>

        <form method="POST">
            <?= Csrf::campoHtml(); ?>
            <div class="grupo-form">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" class="form-control" autocomplete="off" required>
            </div>
            <button type="submit" class="btn-acao" style="background:#3498db; color:white; width:100%; padding:12px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">Enviar Link de Redefinição</button>
        </form>

        <p style="text-align:center; margin-top:15px; font-size:14px;">
            <a href="login.php" style="color:#7f8c8d; text-decoration:none;">⬅ Voltar pro login</a>
        </p>
    </div>

</div>
</main>

<?php require_once '../app/Includes/footer.php'; ?>
