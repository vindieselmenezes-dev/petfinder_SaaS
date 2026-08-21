<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../app/Models/Usuario.php';
require_once '../app/Helpers/Csrf.php';

$usuarioModel = new Usuario();
$usuarioId    = (int) $_SESSION['usuario_id'];

$mensagem     = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!Csrf::validar($_POST['csrf_token'] ?? null)) {

        $mensagem     = 'Sessão expirada. Atualize a página e tente novamente.';
        $tipoMensagem = 'erro';

    } else {

        $nome      = trim($_POST['nome'] ?? '');
        $sobrenome = trim($_POST['sobrenome'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $telefone  = trim($_POST['telefone'] ?? '') ?: null;

        if ($nome === '' || $email === '') {

            $mensagem     = 'Nome e e-mail são obrigatórios.';
            $tipoMensagem = 'erro';

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $mensagem     = 'Digite um e-mail válido.';
            $tipoMensagem = 'erro';

        } elseif ($usuarioModel->emailExiste($email, $usuarioId)) {

            $mensagem     = 'Esse e-mail já está sendo usado por outra conta.';
            $tipoMensagem = 'erro';

        } else {

            $usuarioModel->atualizarDados($usuarioId, $nome, $sobrenome, $email, $telefone);

            // Se o e-mail mudou, a sessão precisa refletir isso (login usa email)
            $_SESSION['usuario_nome'] = $nome;

            $mensagem     = 'Dados atualizados com sucesso!';
            $tipoMensagem = 'sucesso';
        }
    }
}

$usuario = $usuarioModel->buscarPorId($usuarioId);

$tituloPagina = "Meu Perfil";

require_once '../app/Includes/header.php';
require_once '../app/Includes/menu.php';
?>

<main class="conteudo">
<div class="container" style="max-width:500px; margin:0 auto;">

    <?php if ($mensagem): ?>
        <div class="mensagem <?= $tipoMensagem; ?>"><?= htmlspecialchars($mensagem); ?></div>
    <?php endif; ?>

    <div class="formulario-cadastro">
        <h1 style="text-align:center;">👤 Meu Perfil</h1>
        <p style="color:#7f8c8d; text-align:center; font-size:14px; margin-bottom:20px;">Atualize seus dados pessoais.</p>

        <form method="POST">
            <?= Csrf::campoHtml(); ?>

            <div class="grupo-form">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" class="form-control" autocomplete="off" value="<?= htmlspecialchars($usuario['nome'] ?? ''); ?>" required>
            </div>

            <div class="grupo-form">
                <label for="sobrenome">Sobrenome</label>
                <input type="text" id="sobrenome" name="sobrenome" class="form-control" autocomplete="off" value="<?= htmlspecialchars($usuario['sobrenome'] ?? ''); ?>">
            </div>

            <div class="grupo-form">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" class="form-control" autocomplete="off" value="<?= htmlspecialchars($usuario['email'] ?? ''); ?>" required>
            </div>

            <div class="grupo-form">
                <label for="telefone">Telefone</label>
                <input type="text" id="telefone" name="telefone" class="form-control" autocomplete="off" value="<?= htmlspecialchars($usuario['telefone'] ?? ''); ?>" placeholder="(11) 99999-9999">
            </div>

            <button type="submit" class="btn-acao" style="background:#3498db; color:white; width:100%; padding:12px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">Salvar Alterações</button>
        </form>

        <div style="display:flex; justify-content:space-between; margin-top:15px; font-size:14px;">
            <a href="endereco.php" style="color:#7f8c8d; text-decoration:none;">📍 Meu Endereço</a>
            <a href="alterar_senha.php" style="color:#7f8c8d; text-decoration:none;">🔒 Alterar Senha</a>
        </div>
    </div>

</div>
</main>

<?php require_once '../app/Includes/footer.php'; ?>
