<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= isset($tituloPagina) ? htmlspecialchars($tituloPagina) . ' - PetFinder Brasil' : 'PetFinder Brasil' ?>
    </title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="<?= Url::asset('css/dashboard.css') ?>">

    <!-- PWA -->
    <link rel="manifest" href="<?= Url::raiz('manifest.json') ?>">
    <meta name="theme-color" content="#015C1E">
    <link rel="apple-touch-icon" href="<?= Url::asset('img/icons/apple-touch-icon.png') ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="PetFinder">

</head>

<body>

    <script>
        if ("serviceWorker" in navigator) {
            window.addEventListener("load", () => {
                navigator.serviceWorker.register("<?= Url::raiz('sw.js') ?>", { scope: "<?= Url::base() ?: '/' ?>" }).catch(() => { });
            });
        }

        document.addEventListener("DOMContentLoaded", () => {
            const botaoVoltar = document.getElementById("botaoVoltar");
            if (botaoVoltar) {
                botaoVoltar.addEventListener("click", () => {
                    // Se a aba não tem histórico de navegação pra voltar
                    // (ex: link aberto direto, favorito, nova aba), manda
                    // pro Dashboard em vez de deixar o botão sem efeito.
                    if (window.history.length > 1) {
                        window.history.back();
                    } else {
                        window.location.href = "<?= Url::pagina('dashboard.php') ?>";
                    }
                });
            }
        });
    </script>

    <div class="wrapper">

        <header class="topo">

            <div style="display:flex; align-items:center; gap:10px;">
                <button type="button" class="menu-toggle" id="menuToggle" aria-label="Abrir menu" aria-expanded="false"
                    aria-controls="sidebarMenu">
                    ☰
                </button>

                <button type="button" class="btn-voltar" id="botaoVoltar" aria-label="Voltar para a página anterior">
                    ← <span>Voltar</span>
                </button>

                <div class="logo-area">

                    <h2><a href="<?= Url::raiz('index.html') ?>">🐾 PetFinder Brasil</a></h2>

                    <span>Informação, cuidado e carinho para seu pet.</span>

                </div>
            </div>

            <div class="usuario-area">

                <?php if (Auth::check()): ?>
                    Bem-vindo,
                    <strong><?= htmlspecialchars(Auth::nome()); ?></strong>
                <?php endif; ?>

            </div>

        </header>

        <?php Flash::render(); ?>

        <div class="sidebar-overlay" id="sidebarOverlay"></div>