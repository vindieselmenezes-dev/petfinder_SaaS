<?php

declare(strict_types=1);

session_start();

require_once '../app/Models/Usuario.php';
require_once '../app/Models/ResetSenha.php';
require_once '../app/Helpers/Csrf.php';

$usuarioModel = new Usuario();
$resetModel   = new ResetSenha();

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$mensagem     = '';
$tipoMensagem = '';
$tokenValido  = false;
$usuarioId    = null;

if ($token === '') {
    $mensagem     = 'Link inválido.';
    $tipoMensagem = 'erro';
} else {
    $usuarioId = $resetModel->validarToken($token);
    $tokenValido = $usuarioId !== null;

    if (!$tokenValido) {
        $mensagem     = 'Esse link é inválido ou já expirou. Solicite um novo.';
        $tipoMensagem = 'erro';
    }
}

if ($tokenValido && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nova_senha'])) {

    if (!Csrf::validar($_POST['csrf_token'] ?? null)) {

        $mensagem     = 'Sessão expirada. Atualize a página e tente novamente.';
        $tipoMensagem = 'erro';

    } else {

        $novaSenha = $_POST['nova_senha'] ?? '';
        $confirmacao = $_POST['confirmacao'] ?? '';

        if (strlen($novaSenha) < 6) {

            $mensagem     = 'A senha precisa ter pelo menos 6 caracteres.';
            $tipoMensagem = 'erro';

        } elseif ($novaSenha !== $confirmacao) {

            $mensagem     = 'As senhas não coincidem.';
            $tipoMensagem = 'erro';

        } else {

            $usuarioModel->atualizarSenha($usuarioId, password_hash($novaSenha, PASSWORD_DEFAULT));
            $resetModel->marcarComoUsado($token);

            header('Location: login.php?senha_redefinida=1');
            exit;
        }
    }
}

$tituloPagina = "Redefinir senha";

require_once '../app/Includes/header.php';
?>

<main class="conteudo" style="margin-left:0 !important;">
<div class="container" style="max-width:450px; margin:100px auto 0 auto;">

    <?php if ($mensagem): ?>
        <div class="mensagem <?= $tipoMensagem; ?>"><?= htmlspecialchars($mensagem); ?></div>
    <?php endif; ?>

    <?php if ($tokenValido): ?>
        <div class="formulario-cadastro">
            <h1 style="text-align:center;">🔑 Criar Nova Senha</h1>

            <form method="POST">
                <?= Csrf::campoHtml(); ?>
                <input type="hidden" name="token" value="<?= htmlspecialchars($token); ?>">

                <div class="grupo-form">
                    <label for="nova_senha">Nova senha (mín. 6 caracteres)</label>
                    <input type="password" id="nova_senha" name="nova_senha" class="form-control" autocomplete="new-password" minlength="6" required>
                </div>

                <div class="grupo-form">
                    <label for="confirmacao">Confirme a nova senha</label>
                    <input type="password" id="confirmacao" name="confirmacao" class="form-control" autocomplete="new-password" minlength="6" required>
                </div>

                <button type="submit" class="btn-acao" style="background:#3498db; color:white; width:100%; padding:12px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">Redefinir Senha</button>
            </form>
        </div>
    <?php else: ?>
        <p style="text-align:center;"><a href="esqueci_senha.php" style="color:#3498db;">Solicitar um novo link</a></p>
    <?php endif; ?>

</div>
</main>

<?php require_once '../app/Includes/footer.php'; ?>
