<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>PetFinder Brasil</title>

    <link rel="stylesheet" href="../assets/css/dashboard.css">

</head>

<body>

<div class="wrapper">

<header class="topo">

    <div class="logo-area">

        <h2><a href="../index.html">🐾 PetFinder Brasil</a></h2>

        <span>Informação, cuidado e carinho para seu pet.</span>

    </div>

    <div class="usuario-area">

        Bem-vindo,
        <strong><?= htmlspecialchars($_SESSION['usuario_nome']); ?></strong>

    </div>

</header>