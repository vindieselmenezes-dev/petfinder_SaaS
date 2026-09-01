<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= isset($tituloPagina) ? htmlspecialchars($tituloPagina) . ' - PetFinder Brasil' : 'PetFinder Brasil' ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <!-- PWA -->
    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#015C1E">
    <link rel="apple-touch-icon" href="../assets/img/icons/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="PetFinder">

</head>

<body>

<script>
if ("serviceWorker" in navigator) {
    window.addEventListener("load", () => {
        navigator.serviceWorker.register("../sw.js", { scope: "../" }).catch(() => {});
    });
}
</script>

<div class="wrapper">

<header class="topo">

    <div style="display:flex; align-items:center; gap:10px;">
        <button type="button" class="menu-toggle" id="menuToggle" aria-label="Abrir menu" aria-expanded="false" aria-controls="sidebarMenu">
            ☰
        </button>

        <div class="logo-area">

            <h2><a href="../index.html">🐾 PetFinder Brasil</a></h2>

            <span>Informação, cuidado e carinho para seu pet.</span>

        </div>
    </div>

    <div class="usuario-area">

        <?php if (isset($_SESSION['usuario_nome'])): ?>
            Bem-vindo,
            <strong><?= htmlspecialchars($_SESSION['usuario_nome']); ?></strong>
        <?php endif; ?>

    </div>

</header>

<div class="sidebar-overlay" id="sidebarOverlay"></div>