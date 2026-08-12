<?php declare(strict_types=1); 
session_start(); 

if (!isset($_SESSION["usuario_id"])) { 
    header("Location: login.php"); 
    exit; 
} 

require_once "../app/Controllers/PetController.php"; 
require_once "../app/Models/Favorito.php"; 

$controller = new PetController(); 
$favoritoModel = new Favorito(); 
$pets = $controller->listarPorStatus("Perdido"); 

require_once "../app/Includes/header.php"; 
require_once "../app/Includes/menu.php"; 
?> 

<!-- AJUSTE ESTRUTURAL MACRO -->
<main class="container" style="margin-top: 100px !important; margin-left: 280px !important; padding: 20px !important; display: block !important;"> 
    
    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); width: 100%; max-width: 1200px; margin: 40px auto 0 auto !important; position: relative !important; display: block !important;"> 
        
        <h1 style="color: #2c3e50; margin-bottom: 5px; font-family: sans-serif; font-weight: bold;">🔍 Pets Perdidos</h1> 
        <p style="color: #7f8c8d; margin-bottom: 30px; font-family: sans-serif;">Mural comunitário de animais reportados como desaparecidos. Reconheceu algum? Entre em contato imediato.</p> 

        <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.02); font-family: sans-serif;"> 
            <thead> 
                <tr style="background: #34495e; color: white; text-align: left;"> 
                    <th style="padding: 15px; text-align: center; width: 100px;">Foto</th> 
                    <th style="padding: 15px;">Nome</th> 
                    <th style="padding: 15px;">Espécie / Raça</th> 
                    <th style="padding: 15px;">Cidade</th> 
                    <th style="padding: 15px; text-align: center;">Ações</th> 
                </tr> 
            </thead> 
            <tbody> 
                <?php if (count($pets) > 0): ?> 
                    <?php foreach ($pets as $pet): ?> 
                        <tr style="border-bottom: 1px solid #eaeaea;"> 
                            <td style="padding: 15px; text-align: center;"> 
                                <?php 
                                // Se a imagem existir, mostra a foto; senão, mostra o emoji 🐾
                                if (!empty($pet['foto']) && file_exists("../uploads/pets/" . $pet['foto'])) {
                                    $caminhoFoto = "../uploads/pets/" . $pet['foto'];
                                    echo '<img src="'.htmlspecialchars($caminhoFoto).'" width="55" height="55" style="object-fit: cover; border-radius: 50%; border: 2px solid #ddd;" alt="Pet">';
                                } else {
                                    echo '<div style="width: 55px; height: 55px; background: #e2e8f0; border-radius: 50%; border: 2px solid #cbd5e1; display: inline-flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 20px; font-weight: bold;">🐾</div>';
                                }
                                ?> 
                            </td> 
                            <td style="padding: 15px; font-weight: bold; color: #333;"> 
                                <?= htmlspecialchars($pet["nome"] ?? 'Sem Nome'); ?> 
                            </td> 
                            <td style="padding: 15px; color: #555;"> 
                                <?= htmlspecialchars($pet["especie_nome"] ?? 'Não informada'); ?> 
                                <br><small style="color: #999;"><?= htmlspecialchars($pet["raca_nome"] ?? 'Mestiço / Vira-lata'); ?></small> 
                            </td> 
                            <td style="padding: 15px; color: #777;"> 
                                📍 <?= htmlspecialchars($pet["cidade"] ?? 'Não informada'); ?> 
                            </td> 
                            <td style="padding: 15px; text-align: center; white-space: nowrap;"> 
                                <?php if ($favoritoModel->existe((int) $_SESSION['usuario_id'], (int) $pet['id'])): ?> 
                                    <a href="favoritar.php?pet_id=<?= (int) $pet['id']; ?>&acao=remover" style="display: inline-block; background: #e74c3c; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 13px; font-family: sans-serif;">⭐ Remover</a> 
                                <?php else: ?> 
                                    <a href="favoritar.php?pet_id=<?= (int) $pet['id']; ?>&acao=adicionar" style="display: inline-block; background: #f39c12; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 13px; font-family: sans-serif;">☆ Favoritar</a> 
                                <?php endif; ?> 
                            </td> 
                        </tr> 
                    <?php endforeach; ?> 
                <?php else: ?> 
                    <tr> 
                        <td colspan="5" style="padding: 30px; text-align: center; color: #95a5a6; font-style: italic;">Nenhum pet perdido reportado no momento. 🎉</td> 
                    </tr> 
                <?php endif; ?> 
            </tbody> 
        </table> 
    </div> 
</main> 

<?php require_once "../app/Includes/footer.php"; ?>
