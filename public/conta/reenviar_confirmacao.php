<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/Models/ConfirmacaoEmail.php';
require_once __DIR__ . '/../../app/Models/RateLimiter.php';
require_once __DIR__ . '/../../app/Helpers/Csrf.php';

$confirmacaoModel = new ConfirmacaoEmail();

$mensagem = '';
$tipoMensagem = '';
$linkDeTeste = null; // só em modo local (APP_ENV=local)

// Vem do cadastro (ou do login barrado): guarda na sessão o e-mail em questão.
$emailSessao = (string) ($_SESSION['confirmacao_email_pendente'] ?? '');

$podeEntrarJa = false; // exigência desligada: a conta já pode entrar sem confirmar

if (isset($_GET['cadastro']) && $emailSessao !== '') {
    if (ConfirmacaoEmail::exigido()) {
        $mensagem = 'Conta criada! Enviamos um link de confirmação para ' . $emailSessao
            . '. Confirme o e-mail para poder entrar.';
    } else {
        $podeEntrarJa = true;
        $mensagem = 'Conta criada com sucesso! Você já pode entrar. Também enviamos um link de confirmação para '
            . $emailSessao . '.';
    }
    $tipoMensagem = 'sucesso';

    // Link de teste do cadastro, guardado só para quem está em ambiente local.
    if (SegurancaHttp::ehAmbienteLocal() && !empty($_SESSION['confirmacao_link_local'])) {
        $linkDeTeste = (string) $_SESSION['confirmacao_link_local'];
    }
    unset($_SESSION['confirmacao_link_local']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!Csrf::validar($_POST['csrf_token'] ?? null)) {

        $mensagem = 'Sessão expirada. Atualize a página e tente novamente.';
        $tipoMensagem = 'erro';

    } else {

        $email = trim((string) ($_POST['email'] ?? ''));
        $emailSessao = $email;

        // Limite por IP: 5 pedidos a cada 15 minutos (bloqueia por 30).
        $limite = new RateLimiter();

        if (!$limite->permitido('reenviar_confirmacao_ip', 5, 15, 30)) {

            $mensagem = 'Muitos pedidos. Tente novamente em '
                . max(1, $limite->minutosRestantes('reenviar_confirmacao_ip')) . ' minuto(s).';
            $tipoMensagem = 'erro';

        } else {

            // Resposta sempre igual, exista a conta ou não, para não revelar
            // quais e-mails estão cadastrados.
            $mensagem = 'Se esse e-mail estiver cadastrado e ainda não confirmado, enviamos um novo link.';
            $tipoMensagem = 'sucesso';

            $usuario = $confirmacaoModel->buscarPorEmail($email);

            // Limite por e-mail: no máximo 3 envios por hora para a mesma conta.
            $chaveEmail = 'e:' . hash('sha256', mb_strtolower($email));

            if (
                $usuario
                && empty($usuario['email_verificado_em'])
                && $limite->permitido('reenviar_confirmacao_email', 3, 60, 60, $chaveEmail)
            ) {
                $link = $confirmacaoModel->enviarLink($usuario);

                // Só em ambiente local: fora dele o link chega apenas por e-mail.
                if (SegurancaHttp::ehAmbienteLocal()) {
                    $linkDeTeste = $link;
                }
            }
        }
    }
}

$tituloPagina = 'Reenviar confirmação de e-mail';

require_once __DIR__ . '/../../app/Includes/header.php';
?>

<main class="conteudo" style="margin-left:0 !important;">
    <div class="container" style="max-width:450px; margin:100px auto 0 auto;">

        <?php if ($mensagem): ?>
            <div class="mensagem <?= $tipoMensagem; ?>"><?= htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <?php if ($linkDeTeste): ?>
            <div class="mensagem" style="background:#fff8e1; color:#8a6d00; border-color:#ffe082;">
                🧪 <strong>Modo teste local</strong> — use este link diretamente:<br>
                <a href="<?= htmlspecialchars($linkDeTeste); ?>"><?= htmlspecialchars($linkDeTeste); ?></a>
            </div>
        <?php endif; ?>

        <?php if ($podeEntrarJa): ?>
            <a href="<?= Url::pagina('login.php') ?>"
                style="display:block; text-align:center; background:#27ae60; color:white; padding:12px; border-radius:6px; font-weight:bold; text-decoration:none; margin-bottom:15px;">Ir para o login</a>
        <?php endif; ?>

        <div class="formulario-cadastro">
            <h1 style="text-align:center;">✉️ Confirme seu e-mail</h1>
            <p style="color:#7f8c8d; text-align:center; font-size:14px; margin-bottom:20px;">
                Não recebeu o link? Confira a caixa de spam ou peça um novo abaixo.
            </p>

            <form method="POST">
                <?= Csrf::campoHtml(); ?>
                <div class="grupo-form">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" class="form-control" autocomplete="email"
                        value="<?= htmlspecialchars($emailSessao); ?>" required>
                </div>
                <button type="submit" class="btn-acao"
                    style="background:#3498db; color:white; width:100%; padding:12px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">Enviar novo link</button>
            </form>

            <p style="text-align:center; margin-top:15px; font-size:14px;">
                <a href="<?= Url::pagina('login.php') ?>" style="color:#7f8c8d; text-decoration:none;">⬅ Voltar pro login</a>
            </p>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/../../app/Includes/footer.php'; ?>
