<?php

declare(strict_types=1);

/**
 * ==========================================================
 * PETFINDER BRASIL — Descadastro da newsletter
 * ==========================================================
 * Link que deve ir no rodapé de todo e-mail de newsletter:
 *   Url::absoluta(Url::pagina('descadastrar_newsletter.php'))
 *       . '?email=' . urlencode($email)
 *       . '&token=' . (new Newsletter())->gerarTokenDescadastro($email)
 *
 * Com e-mail + token válidos na URL, o descadastro é feito na hora
 * (um clique só, sem exigir login nem confirmação — é o padrão exigido
 * por boa parte da legislação de e-mail marketing). Sem token (alguém
 * que chegou na página por fora, sem ter um e-mail em mãos), mostra um
 * formulário manual: pior cenário de abuso é descadastrar o e-mail de
 * outra pessoa da newsletter, o que não justifica exigir confirmação
 * por e-mail pra isso.
 */

require_once __DIR__ . '/../app/bootstrap.php';

$newsletterModel = new Newsletter();

$email = trim((string) ($_GET['email'] ?? $_POST['email'] ?? ''));
$token = trim((string) ($_GET['token'] ?? ''));

$mensagem = null;
$tipoMensagem = null;
$descadastradoViaLink = false;

// --- Descadastro direto por link (GET com email + token) -------------------
if ($email !== '' && $token !== '' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($newsletterModel->tokenDescadastroValido($email, $token)) {
        $newsletterModel->cancelar($email);
        $mensagem = 'Pronto! O e-mail ' . htmlspecialchars($email) . ' não vai mais receber nossa newsletter.';
        $tipoMensagem = 'success';
        $descadastradoViaLink = true;
    } else {
        $mensagem = 'Esse link de descadastro é inválido. Use o formulário abaixo para se descadastrar manualmente.';
        $tipoMensagem = 'danger';
    }
}

// --- Descadastro manual (formulário, sem token) -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Honeypot: campo escondido que só um robô preencheria.
    $armadilha = trim($_POST['site'] ?? '');

    if ($armadilha === '') {

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensagem = 'Informe um e-mail válido.';
            $tipoMensagem = 'danger';
        } else {
            $newsletterModel->cancelar($email);
            $mensagem = 'Pronto! O e-mail ' . htmlspecialchars($email) . ' não vai mais receber nossa newsletter.';
            $tipoMensagem = 'success';
        }
    } else {
        // Preencheu o honeypot: finge sucesso, não faz nada.
        $mensagem = 'Pronto! Você foi descadastrado da newsletter.';
        $tipoMensagem = 'success';
    }
}

$tituloPagina = 'Descadastrar da newsletter';

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($tituloPagina) ?> - PetFinder Brasil</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= Url::asset('css/style.css') ?>">

</head>

<body>

    <header class="border-bottom py-3">

        <div class="container d-flex align-items-center justify-content-between flex-wrap gap-3">

            <a href="<?= Url::raiz('index.html') ?>" class="d-flex align-items-center text-decoration-none">
                <img src="<?= Url::asset('img/logo.png') ?>" alt="PetFinder Brasil" height="40" class="me-2">
                <div>
                    <div class="fw-bold text-dark">PetFinder Brasil</div>
                    <small class="text-muted">Tudo para seu pet em um só lugar</small>
                </div>
            </a>

        </div>

    </header>

    <main class="container my-5">

        <div class="row justify-content-center">
            <div class="col-lg-6">

                <div class="text-center mb-4">
                    <h1 class="fw-bold h3">✉️ Descadastrar da newsletter</h1>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">

                        <?php if ($mensagem !== null): ?>
                            <div class="alert alert-<?= htmlspecialchars($tipoMensagem) ?>">
                                <?= $mensagem /* já veio com htmlspecialchars nas partes variáveis */ ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!$descadastradoViaLink): ?>

                            <p class="text-muted small">
                                Informe o e-mail que você quer remover da nossa lista de novidades.
                            </p>

                            <form method="POST" class="row g-3">

                                <!-- Honeypot: invisível para pessoas, tentador para robôs de spam -->
                                <div style="position:absolute; left:-9999px; width:1px; height:1px; overflow:hidden;"
                                    aria-hidden="true">
                                    <label for="site">Não preencha este campo</label>
                                    <input type="text" name="site" id="site" tabindex="-1" autocomplete="off">
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="email">E-mail</label>
                                    <input type="email" name="email" id="email" class="form-control" required
                                        value="<?= htmlspecialchars($email) ?>">
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-outline-danger">
                                        Descadastrar
                                    </button>
                                </div>

                            </form>

                        <?php endif; ?>

                    </div>
                </div>

                <p class="text-center text-muted small mt-3">
                    Mudou de ideia? Você pode se inscrever de novo a qualquer momento pelo rodapé do site.
                </p>

            </div>
        </div>

    </main>

    <footer class="bg-dark text-light py-4">
        <div class="container text-center">
            © <?= date('Y') ?> PetFinder Brasil ·
            <a href="<?= Url::raiz('index.html') ?>" class="text-light">Voltar para a home</a>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
