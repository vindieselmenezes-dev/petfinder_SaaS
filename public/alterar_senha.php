<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../app/Models/Usuario.php';
require_once '../app/Helpers/ValidacaoSenha.php';
require_once '../app/Helpers/Csrf.php';

$usuarioModel = new Usuario();
$usuarioId    = (int) $_SESSION['usuario_id'];

$mensagem     = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $senhaAtual      = $_POST['senha_atual'] ?? '';
    $senhaNova       = $_POST['senha_nova'] ?? '';
    $confirmarSenha  = $_POST['confirmar_senha'] ?? '';

    $usuario = $usuarioModel->buscarPorId($usuarioId);

    $erroSenha = ValidacaoSenha::validar($senhaNova);

    if (!Csrf::validar($_POST['csrf_token'] ?? null)) {

        $mensagem     = 'Sessão expirada. Atualize a página e tente novamente.';
        $tipoMensagem = 'erro';

    } elseif (!$usuario) {

        $mensagem     = 'Usuário não encontrado.';
        $tipoMensagem = 'erro';

    } elseif (!password_verify($senhaAtual, $usuario['senha'])) {

        $mensagem     = 'A senha atual está incorreta.';
        $tipoMensagem = 'erro';

    } elseif ($erroSenha !== null) {

        $mensagem     = $erroSenha;
        $tipoMensagem = 'erro';

    } elseif ($senhaNova !== $confirmarSenha) {

        $mensagem     = 'A confirmação não bate com a nova senha.';
        $tipoMensagem = 'erro';

    } elseif ($senhaAtual === $senhaNova) {

        $mensagem     = 'A nova senha deve ser diferente da senha atual.';
        $tipoMensagem = 'erro';

    } else {

        $novoHash = password_hash($senhaNova, PASSWORD_DEFAULT);

        if ($usuarioModel->atualizarSenha($usuarioId, $novoHash)) {
            $mensagem     = 'Senha alterada com sucesso!';
            $tipoMensagem = 'sucesso';
        } else {
            $mensagem     = 'Não foi possível alterar a senha. Tente novamente.';
            $tipoMensagem = 'erro';
        }

    }

}

require_once '../app/Includes/header.php';
require_once '../app/Includes/menu.php';
?>

<main class="conteudo">

    <h1>🔒 Alterar Senha</h1>

    <p>Defina uma nova senha para sua conta.</p>

    <?php if (!empty($mensagem)): ?>

        <div class="mensagem <?= $tipoMensagem; ?>">
            <?= htmlspecialchars($mensagem); ?>
        </div>

    <?php endif; ?>

    <form method="POST" action="" class="formulario-endereco">

        <?= Csrf::campoHtml() ?>

        <div class="grupo-form">
            <label for="senha_atual">Senha atual *</label>
            <input
                type="password"
                id="senha_atual"
                name="senha_atual"
                required>
        </div>

        <div class="grupo-form">
            <label for="senha_nova">Nova senha *</label>
            <input
                type="password"
                id="senha_nova"
                name="senha_nova"
                minlength="8"
                required>
            <small style="display:block; color:#6c757d; margin-top:4px;">
                Mínimo 8 caracteres, com 1 letra maiúscula e 1 número.
            </small>
        </div>

        <div class="grupo-form">
            <label for="confirmar_senha">Confirmar nova senha *</label>
            <input
                type="password"
                id="confirmar_senha"
                name="confirmar_senha"
                minlength="8"
                required>
        </div>

        <div class="grupo-form">
            <button type="submit" class="btn">
                Alterar Senha
            </button>
        </div>

    </form>

</main>

<?php require_once '../app/Includes/footer.php'; ?>
