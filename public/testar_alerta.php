<?php declare(strict_types=1); 
session_start(); 

if (!isset($_SESSION["usuario_id"])) { 
    header("Location: login.php"); 
    exit; 
} 

require_once "../app/Models/Usuario.php"; 
require_once "../app/Helpers/Csrf.php"; 
$pdo = Database::conectar(); 

// Puxa um pet qualquer do banco para podermos testar o envio
$stmtPet = $pdo->prepare("SELECT id, nome FROM pets LIMIT 1");
$stmtPet->execute();
$petParaTeste = $stmtPet->fetch(PDO::FETCH_ASSOC);

require_once "../app/Includes/header.php"; 
require_once "../app/Includes/menu.php"; 
?> 

<main class="container" style="margin-top: 110px !important; margin-left: 240px !important; margin-bottom: 50px; padding: 20px;"> 
    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); width: 100%; max-width: 600px; margin: 0 auto;"> 
        
        <h1 style="color: #e74c3c; margin-bottom: 5px;">🚨 Teste de Disparo: Pet Perdido</h1> 
        <p style="color: #7f8c8d; margin-bottom: 30px;">Simulador do Tópico 1 para acionar a Fórmula de Haversine e notificar os vizinhos.</p> 

        <?php if ($petParaTeste): ?>
            <form action="processa_alerta.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;"> 
                <?= Csrf::campoHtml() ?>
                <!-- Envia o ID do pet encontrado no banco -->
                <input type="hidden" name="pet_id" value="<?= $petParaTeste['id']; ?>"> 

                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-weight: bold; color: #4a5568;">Animal Selecionado para o Teste</label>
                    <input type="text" value="<?= htmlspecialchars($petParaTeste['nome']); ?> (ID: <?= $petParaTeste['id']; ?>)" disabled style="padding: 10px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-weight: bold; color: #4a5568;">Último lugar visto (Localização do Sumiço)</label>
                    <input type="text" name="last_seen_location" required value="Próximo à Praça Central, Centro" style="padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-weight: bold; color: #4a5568;">Características marcantes</label>
                    <textarea name="description" rows="3" style="padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">Usava coleira azul, é muito dócil.</textarea>
                </div>

                <button type="submit" style="background: #e74c3c; color: white; border: 0; padding: 12px; border-radius: 6px; font-weight: bold; font-size: 16px; cursor: pointer; margin-top: 15px;">🚀 Disparar Alerta com Haversine</button> 
            </form>
        <?php else: ?>
            <p style="color: #e74c3c; font-weight: bold; text-align: center;">⚠️ Erro: Você precisa ter pelo menos 1 pet cadastrado na tabela `pets` para conseguir testar este fluxo!</p>
        <?php endif; ?>

    </div> 
</main> 

<?php require_once '../app/Includes/footer.php'; ?>
