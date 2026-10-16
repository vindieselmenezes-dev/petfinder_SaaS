<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/Models/ConfirmacaoEmail.php';
require_once __DIR__ . '/../../app/Models/RateLimiter.php';
require_once __DIR__ . '/../../app/Helpers/Csrf.php';

/**
 * Destino do link do e-mail de confirmação.
 *
 * O link (GET) só MOSTRA um botão; quem confirma é o clique, que envia um
 * POST. Assim, antivírus e visualizadores de e-mail que abrem links
 * automaticamente não gastam o token antes da pessoa.
 */

$confirmacaoModel = new ConfirmacaoEmail();

$mensagem = '';
$tipoMensagem = '';
$confirmado = false;

$token = (string) ($_POST['token'] ?? $_GET['token'] ?? '');
$tokenValido = $token !== '' && $confirmacaoModel->validarToken($token) !== null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $limite = new RateLimiter();

    if (!Csrf::validar($_POST['csrf_token'] ?? null)) {

        $mensagem = 'Sessão expirada. Atualize a página e tente novamente.';
        $tipoMensagem = 'erro';

    } elseif (!$limite->permitido('confirmar_email', 10, 15, 30)) {

        $mensagem = 'Muitas tentativas. Tente novamente em '
            . max(1, $limite->minutosRestantes('confirmar_email')) . ' minuto(s).';
        $tipoMensagem = 'erro';

    } elseif ($tokenValido && $confirmacaoModel->confirmar($token)) {

        $confirmado = true;
        $tokenValido = false;
        $mensagem = 'E-mail confirmado! Agora você já pode entrar.';
        $tipoMensagem = 'sucesso';

    } else {

        $tokenValido = false;
        $mensagem = 'Este link é inválido ou expirou.';
        $tipoMensagem = 'erro';
    }
}

$tituloPagina = 'Confirmar e-mail';

require_once __DIR__ . '/../../app/Includes/header.php';
?>

<main class="conteudo" style="margin-left:0 !important;">
    <div class="container" style="max-width:450px; margin:100px auto 0 auto;">

        <?php if ($mensagem): ?>
            <div class="mensagem <?= $tipoMensagem; ?>"><?= htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <div class="formulario-cadastro">
            <h1 style="text-align:center;">✉️ Confirmar e-mail</h1>

            <?php if ($confirmado): ?>

                <p style="color:#7f8c8d; text-align:center; font-size:14px; margin:20px 0;">
                    Sua conta está ativa.
                </p>
                <a href="<?= Url::pagina('login.php') ?>"
                    style="display:block; text-align:center; background:#3498db; color:white; padding:12px; border-radius:6px; font-weight:bold; text-decoration:none;">Ir para o login</a>

            <?php elseif ($tokenValido): ?>

                <p style="color:#7f8c8d; text-align:center; font-size:14px; margin-bottom:20px;">
                    Clique no botão para confirmar seu e-mail e ativar a conta.
                </p>
                <form method="POST">
                    <?= Csrf::campoHtml(); ?>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token); ?>">
                    <button type="submit"
                        style="background:#27ae60; color:white; width:100%; padding:12px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">Confirmar meu e-mail</button>
                </form>

            <?php else: ?>

                <p style="color:#7f8c8d; text-align:center; font-size:14px; margin:20px 0;">
                    Este link não vale mais. Peça um novo para receber outro e-mail de confirmação.
                </p>
                <a href="<?= Url::pagina('reenviar_confirmacao.php') ?>"
                    style="display:block; text-align:center; background:#3498db; color:white; padding:12px; border-radius:6px; font-weight:bold; text-decoration:none;">Enviar novo link</a>

            <?php endif; ?>

            <p style="text-align:center; margin-top:15px; font-size:14px;">
                <a href="<?= Url::pagina('login.php') ?>" style="color:#7f8c8d; text-decoration:none;">⬅ Voltar pro login</a>
            </p>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/../../app/Includes/footer.php'; ?>
