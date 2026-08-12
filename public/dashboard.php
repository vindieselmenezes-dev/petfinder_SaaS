<?php declare(strict_types=1); 
session_start(); 

if (!isset($_SESSION["usuario_id"])) { 
    header("Location: login.php"); 
    exit; 
} 

require_once "../app/Controllers/PetController.php"; 
require_once "../app/Models/Usuario.php"; 
require_once __DIR__ . '/../config/database.php'; 

$petController = new PetController(); 
$usuario = new Usuario(); 
$tipoUsuarioLogado = $_SESSION['perfil_tipo'] ?? 'tutor'; 

// Carrega as estatísticas reais do banco uma única vez (Sem loops) 
$totalPets        = count($petController->listarPorUsuario((int) $_SESSION["usuario_id"])); 
$totalUsuarios    = $usuario->contarUsuarios(); 
$totalPerdidos    = $petController->contarPorStatus("Perdido"); 
$totalEncontrados = $petController->contarPorStatus("Encontrado"); 
$totalAdocao      = $petController->contarPorStatus("Para Adoção"); 
$totalComTutor    = $petController->contarPorStatus("Com Tutor"); 
$totalAdotados    = $petController->contarPorStatus("Adotado"); 

require_once "../app/Includes/header.php"; 
require_once "../app/Includes/menu.php"; 
?> 

<!-- Ajuste do elemento Pai: Margem de 100px apenas para alinhar abaixo do menu superior -->
<main class="container" style="margin-top: 100px !important; margin-left: 280px !important; padding: 20px !important; display: block !important;"> 
    
    <!-- CORREÇÃO CIRÚRGICA: Força a caixa branca a abandonar o posicionamento absoluto e descer 40px extras via padding-top -->
    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); width: 100%; max-width: 1200px; margin: 40px auto 0 auto !important; position: relative !important; top: 0 !important; float: none !important; clear: both !important; display: block !important;"> 
        
        <h1 style="color: #2c3e50; margin-bottom: 5px; font-family: sans-serif; font-weight: bold; display: block !important;">📊 Dashboard - Administrador Global</h1> 
        <p style="color: #7f8c8d; margin-bottom: 30px; font-family: sans-serif; display: block !important;">Painel de controle macro do ecossistema PetFinder Brasil.</p> 

        <div class="cards"> 
            <a href="admin_usuarios.php?status=Todos" class="card"> 
                <div class="icone">🐶</div> 
                <h3>Pets cadastrados</h3> 
                <div class="numero"><?= $totalPets; ?></div> 
            </a> 
            <a href="admin_usuarios.php" class="card"> 
                <div class="icone">👤</div> 
                <h3>Usuários</h3> 
                <div class="numero"><?= $totalUsuarios; ?></div> 
            </a> 
            <a href="admin_usuarios.php?status=Perdido" class="card"> 
                <div class="icone">🔍</div> 
                <h3>Pets Perdidos</h3> 
                <div class="numero"><?= $totalPerdidos; ?></div> 
            </a> 
            <a href="admin_usuarios.php?status=Encontrado" class="card"> 
                <div class="icone">❤️</div> 
                <h3>Pets Encontrados</h3> 
                <div class="numero"><?= $totalEncontrados; ?></div> 
            </a> 
            <a href="admin_usuarios.php?status=Para Adoção" class="card"> 
                <div class="icone">🏠</div> 
                <h3>Para Adoção</h3> 
                <div class="numero"><?= $totalAdocao; ?></div> 
            </a> 
            <a href="admin_usuarios.php?status=Com Tutor" class="card"> 
                <div class="icone">🏡</div> 
                <h3>Com Tutor</h3> 
                <div class="numero"><?= $totalComTutor; ?></div> 
            </a> 
            <a href="admin_usuarios.php?status=Adotado" class="card"> 
                <div class="icone">🎉</div> 
                <h3>Adotados</h3> 
                <div class="numero"><?= $totalAdotados; ?></div> 
            </a> 
        </div> 
    </div> 
</main> 

<!-- BLOQUEIO ABSOLUTO DE SCRIPTS DE ATUALIZAÇÃO AUTOMÁTICA NA MEMÓRIA --> 
<script> 
(function() { 
    var highestTimeoutId = setTimeout(";"); 
    for (var i = 0 ; i < highestTimeoutId ; i++) { 
        clearTimeout(i); 
        clearInterval(i); 
    } 
})(); 
</script> 

<?php require_once "../app/Includes/footer.php"; ?>
