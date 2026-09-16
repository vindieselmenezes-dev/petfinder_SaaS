<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

Middleware::exigirLogin();

$petController = new PetController();
$usuario = new Usuario();
$souAdmin = Auth::ehAdministrador();

// Área de parceiros: números para os cards do dashboard.
// O try/catch evita que o dashboard quebre em ambientes onde a
// migration_024 (tabelas de parceiros) ainda não foi aplicada.
$parceiroModel = new Parceiro();
$resumoParceiros = ['parceiros' => 0, 'ongs' => 0, 'campanhas' => 0, 'arrecadado' => 0.0];
$minhasParcerias = [];
$parceirosPendentes = 0;
$campanhasPendentes = 0;

try {
    $resumoParceiros = $parceiroModel->resumo();
    $minhasParcerias = $parceiroModel->listarPorUsuario((int) Auth::id());

    if ($souAdmin) {
        $parceirosPendentes = $parceiroModel->contarPorStatus('pendente');
        // Continua em 0 se a migration_026 (moderação por campanha)
        // ainda não tiver sido aplicada — contarPendentesModeracao()
        // já engole esse erro sozinho.
        $campanhasPendentes = (new Campanha())->contarPendentesModeracao();
    }
} catch (PDOException $e) {
    error_log('Área de parceiros indisponível no dashboard: ' . $e->getMessage());
}

require_once __DIR__ . '/../app/Includes/header.php';
require_once __DIR__ . '/../app/Includes/menu.php';
?>

<main class="container"
    style="margin-top: 100px !important; margin-left: 240px !important; padding: 20px !important; display: block !important;">

    <div
        style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); width: 100%; max-width: 1200px; margin: 40px auto 0 auto !important; position: relative !important; top: 0 !important; float: none !important; clear: both !important; display: block !important;">

        <?php if ($souAdmin): ?>
            <?php
            // ===================== VISÃO DE ADMINISTRADOR =====================
            $totalPets = $petController->contarPets();
            $totalUsuarios = $usuario->contarUsuarios();
            $totalPerdidos = $petController->contarPorStatus("Perdido");
            $totalEncontrados = $petController->contarPorStatus("Encontrado");
            $totalAdocao = $petController->contarPorStatus("Para Adoção");
            $totalComTutor = $petController->contarPorStatus("Com Tutor");
            $totalAdotados = $petController->contarPorStatus("Adotado");
            ?>

            <h1 style="color: #2c3e50; margin-bottom: 5px; font-family: sans-serif; font-weight: bold;">📊 Dashboard -
                Administrador Global</h1>
            <p style="color: #7f8c8d; margin-bottom: 30px; font-family: sans-serif;">Painel de controle macro do ecossistema
                PetFinder Brasil.</p>

            <div class="cards">
                <a href="<?= Url::pagina('admin_usuarios.php') ?>?status=Todos" class="card">
                    <div class="icone">🐶</div>
                    <h3>Pets cadastrados</h3>
                    <div class="numero"><?= $totalPets; ?></div>
                </a>
                <a href="<?= Url::pagina('admin_usuarios.php') ?>" class="card">
                    <div class="icone">👤</div>
                    <h3>Usuários</h3>
                    <div class="numero"><?= $totalUsuarios; ?></div>
                </a>
                <a href="<?= Url::pagina('admin_usuarios.php') ?>?status=Perdido" class="card">
                    <div class="icone">🔍</div>
                    <h3>Pets Perdidos</h3>
                    <div class="numero"><?= $totalPerdidos; ?></div>
                </a>
                <a href="<?= Url::pagina('admin_usuarios.php') ?>?status=Encontrado" class="card">
                    <div class="icone">❤️</div>
                    <h3>Pets Encontrados</h3>
                    <div class="numero"><?= $totalEncontrados; ?></div>
                </a>
                <a href="<?= Url::pagina('admin_usuarios.php') ?>?status=Para Adoção" class="card">
                    <div class="icone">🏠</div>
                    <h3>Para Adoção</h3>
                    <div class="numero"><?= $totalAdocao; ?></div>
                </a>
                <a href="<?= Url::pagina('admin_usuarios.php') ?>?status=Com Tutor" class="card">
                    <div class="icone">🏡</div>
                    <h3>Com Tutor</h3>
                    <div class="numero"><?= $totalComTutor; ?></div>
                </a>
                <a href="<?= Url::pagina('admin_usuarios.php') ?>?status=Adotado" class="card">
                    <div class="icone">🎉</div>
                    <h3>Adotados</h3>
                    <div class="numero"><?= $totalAdotados; ?></div>
                </a>
                <a href="<?= Url::pagina('admin_parceiros.php') ?>?status=pendente" class="card">
                    <div class="icone">🤝</div>
                    <h3>Parcerias a aprovar</h3>
                    <div class="numero"><?= $parceirosPendentes; ?></div>
                </a>
                <a href="<?= Url::pagina('admin_campanhas.php') ?>?status=pendente" class="card">
                    <div class="icone">🛡️</div>
                    <h3>Campanhas a aprovar</h3>
                    <div class="numero"><?= $campanhasPendentes; ?></div>
                </a>
                <a href="<?= Url::pagina('parceiros.php') ?>" class="card">
                    <div class="icone">📣</div>
                    <h3>Campanhas ativas</h3>
                    <div class="numero"><?= $resumoParceiros['campanhas']; ?></div>
                </a>
            </div>

        <?php else: ?>
            <?php
            // ===================== VISÃO DE TUTOR / CLIENTE =====================
            $meusPets = $petController->listarPorUsuario(Auth::id());
            $totalMeusPets = count($meusPets);
            $totalMeusPerdidos = 0;
            foreach ($meusPets as $p) {
                if (($p['status'] ?? '') === 'Perdido') {
                    $totalMeusPerdidos++;
                }
            }
            ?>

            <h1 style="color: #2c3e50; margin-bottom: 5px; font-family: sans-serif; font-weight: bold;">🐾 Olá,
                <?= htmlspecialchars(Auth::nome()); ?>!
            </h1>
            <p style="color: #7f8c8d; margin-bottom: 30px; font-family: sans-serif;">Aqui está um resumo da sua conta no
                PetFinder Brasil.</p>

            <div class="cards">
                <a href="<?= Url::pagina('meus_pets.php') ?>" class="card">
                    <div class="icone">🐶</div>
                    <h3>Meus Pets</h3>
                    <div class="numero"><?= $totalMeusPets; ?></div>
                </a>
                <a href="<?= Url::pagina('meus_pets.php') ?>?status=Perdido" class="card">
                    <div class="icone">🔍</div>
                    <h3>Meus Pets Perdidos</h3>
                    <div class="numero"><?= $totalMeusPerdidos; ?></div>
                </a>
                <a href="<?= Url::pagina('meus_favoritos.php') ?>" class="card">
                    <div class="icone">⭐</div>
                    <h3>Meus Favoritos</h3>
                    <div class="numero">Ver</div>
                </a>
                <a href="<?= Url::pagina('cadastrar_pet.php') ?>" class="card">
                    <div class="icone">➕</div>
                    <h3>Cadastrar Pet</h3>
                    <div class="numero">Novo</div>
                </a>
                <a href="<?= Url::pagina('pets_adocao.php') ?>" class="card">
                    <div class="icone">🏠</div>
                    <h3>Pets para Adoção</h3>
                    <div class="numero">Ver</div>
                </a>
                <a href="<?= Url::pagina('meu_perfil.php') ?>" class="card">
                    <div class="icone">👤</div>
                    <h3>Meu Perfil</h3>
                    <div class="numero">Editar</div>
                </a>
                <a href="<?= Url::pagina('parceiros.php') ?>" class="card">
                    <div class="icone">📣</div>
                    <h3>Campanhas e Doações</h3>
                    <div class="numero"><?= $resumoParceiros['campanhas']; ?></div>
                </a>

                <?php if (count($minhasParcerias) > 0): ?>
                    <a href="<?= Url::pagina('painel_parceiro.php') ?>" class="card">
                        <div class="icone">🤝</div>
                        <h3>Painel do Parceiro</h3>
                        <div class="numero"><?= count($minhasParcerias); ?></div>
                    </a>
                <?php else: ?>
                    <a href="<?= Url::pagina('cadastrar_parceiro.php') ?>" class="card">
                        <div class="icone">🤝</div>
                        <h3>Seja Parceiro</h3>
                        <div class="numero">ONG / Empresa</div>
                    </a>
                <?php endif; ?>
            </div>

        <?php endif; ?>

    </div>
</main>

<?php require_once "../app/Includes/footer.php"; ?>