<?php declare(strict_types=1); 
session_start(); 

if (!isset($_SESSION['usuario_id']) || ($_SESSION['perfil_tipo'] ?? '') !== 'administrador') { 
    header('Location: login.php'); 
    exit; 
} 

require_once '../app/Models/Usuario.php'; 
$pdo = Database::conectar(); 

$usuarioModel = new Usuario(); 
$usuarios = $usuarioModel->listarTodos(); 
$mensagem = ''; 
$tipoMensagem = ''; 

if (isset($_GET['delete_id'])) { 
    $deleteId = (int) $_GET['delete_id']; 
    if ($deleteId > 0 && $deleteId !== (int)$_SESSION['usuario_id']) { 
        if ($usuarioModel->deletar($deleteId)) { 
            session_write_close();
            header('Location: admin_usuarios.php');
            exit;
        } 
    } 
} 

// Captura se o administrador clicou para ver os pets de algum usuário específico
$verPetsId = isset($_GET['ver_pets_de']) ? (int)$_GET['ver_pets_de'] : 0;
$petsDoUsuario = [];
if ($verPetsId > 0) {
    $stmtPets = $pdo->prepare("SELECT p.*, e.nome as especie_nome, r.nome as raca_nome FROM pets p LEFT JOIN especies e ON p.especie_id = e.id LEFT JOIN racas r ON p.raca_id = r.id WHERE p.usuario_id = ?");
    $stmtPets->execute([$verPetsId]);
    $petsDoUsuario = $stmtPets->fetchAll(PDO::FETCH_ASSOC);
}

require_once '../app/Includes/header.php'; 
require_once '../app/Includes/menu.php'; 
?> 

   <main class="container" style="margin-top: 110px !important; margin-left: 280px !important; margin-bottom: 50px; padding: 20px;"></main>

    
    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); width: 100%; max-width: 1200px; margin: 0 auto;">
        
        <h1 style="color: #2c3e50; margin-bottom: 5px;">👥 Gestão Unificada de Usuários & Pets</h1> 
        <p style="color: #7f8c8d; margin-bottom: 30px;">Gerencie contas e visualize animais vinculados diretamente ao perfil de cada tutor.</p> 

        <!-- SE O ADMIN CLICOU EM VER PETS, MOSTRA A CAIXA FLUTUANTE MODERNA NA MESMA TELA -->
        <?php if ($verPetsId > 0): ?>
            <div style="background: #f8fafc; border: 2px solid #cbd5e1; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin: 0; color: #1e293b;">🐾 Animais Vinculados ao Tutor</h3>
                    <a href="admin_usuarios.php" style="background: #64748b; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 13px;">✕ Fechar Painel</a>
                </div>
                
                <div style="display: flex; flex-wrap: wrap; gap: 15px;">
                    <?php if (count($petsDoUsuario) > 0): ?>
                        <?php foreach ($petsDoUsuario as $p): ?>
                            <div style="background: white; border: 1px solid #e2e8f0; padding: 15px; border-radius: 6px; display: flex; align-items: center; gap: 15px; width: calc(50% - 8px); box-sizing: border-box;">
                                
                                <!-- BLOCO BLINDADO CONTRA LOOP DE IMAGEM INEXISTENTE -->
                                <?php if (!empty($p['foto']) && file_exists(__DIR__ . '/../uploads/pets/' . $p['foto'])): ?>
                                    <img src="../uploads/pets/<?= htmlspecialchars($p['foto']); ?>" width="55" height="55" style="border-radius: 50%; object-fit: cover; border: 2px solid #cbd5e1;">
                                <?php else: ?>
                                    <div style="width: 55px; height: 55px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 24px; border: 2px solid #cbd5e1; flex-shrink: 0;">🐶</div>
                                <?php endif; ?>

                                <div> 
                                    <h4 style="margin: 0 0 5px 0; color: #334155;"><?= htmlspecialchars($p['nome']); ?></h4> 
                                    <span style="font-size: 13px; color: #64748b;"><?= htmlspecialchars($p['especie_nome'] ?? 'Canina/Felina'); ?> • <?= htmlspecialchars($p['raca_nome'] ?? 'Vira-lata'); ?></span> 
                                    <br><span style="background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; display: inline-block; margin-top: 5px;"><?= htmlspecialchars($p['status']); ?></span> 
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #94a3b8; font-style: italic; margin: 10px 0;">Este usuário não possui nenhum animal cadastrado no perfil ainda.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- TABELA DE USUÁRIOS -->
        <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden;"> 
            <thead> 
                <tr style="background: #34495e; color: white; text-align: left;"> 
                    <th style="padding: 15px;">Nome</th> 
                    <th style="padding: 15px;">Email</th> 
                    <th style="padding: 15px;">Perfil</th> 
                    <th style="padding: 15px; text-align: center;">Animais</th> 
                    <th style="padding: 15px; text-align: center;">Ações</th> 
                </tr> 
            </thead> 
            <tbody> 
                <?php if (count($usuarios) > 0): ?> 
                    <?php foreach ($usuarios as $usuario): 
                        // Conta quantos pets esse usuário específico tem de forma estática
                        $stmtCt = $pdo->prepare("SELECT COUNT(*) FROM pets WHERE usuario_id = ?");
                        $stmtCt->execute([$usuario['id']]);
                        $qtdPets = $stmtCt->fetchColumn();
                    ?> 
                        <tr style="border-bottom: 1px solid #eaeaea;"> 
                            <td style="padding: 15px; font-weight: bold; color: #333;"><?= htmlspecialchars(($usuario['nome'] ?? '') . ' ' . ($usuario['sobrenome'] ?? '')); ?></td> 
                            <td style="padding: 15px; color: #555;"><?= htmlspecialchars($usuario['email'] ?? ''); ?></td> 
                            <td style="padding: 15px;"><span style="background: #e1f5fe; color: #0288d1; padding: 3px 8px; border-radius: 4px; font-size: 13px; font-weight: bold;"><?= htmlspecialchars($usuario['perfil'] ?? 'cliente'); ?></span></td> 
                            
                            <!-- BOTÃO INTELIGENTE DE VISUALIZAÇÃO DE PETS POR USUÁRIO -->
                            <td style="padding: 15px; text-align: center;">
                                <a href="admin_usuarios.php?ver_pets_de=<?= $usuario['id']; ?>" style="background: #2563eb; color: white; text-decoration: none; padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; display: inline-block;">🔍 Ver Pets (<?= $qtdPets; ?>)</a>
                            </td>

                            <td style="padding: 15px; text-align: center;"> 
                                <?php if ($usuario['id'] !== $_SESSION['usuario_id']): ?> 
                                    <a href="admin_usuarios.php?delete_id=<?= $usuario['id']; ?>" onclick="return confirm('Deseja remover este usuário?');" style="color: #e74c3c; text-decoration: none; font-weight: bold; font-size: 14px;">🗑 Excluir</a> 
                                <?php else: ?> 
                                    <span style="background: #2ecc71; color: white; padding: 3px 8px; border-radius: 4px; font-size: 13px; font-weight: bold;">Você (Ativo)</span> 
                                <?php endif; ?> 
                            </td> 
                        </tr> 
                    <?php endforeach; ?> 
                <?php else: ?> 
                    <tr> 
                        <td colspan="5" style="padding: 20px; text-align: center; color: #95a5a6; font-style: italic;">Nenhum usuário encontrado.</td> 
                    </tr> 
                <?php endif; ?> 
            </tbody> 
        </table> 
    </div>
</main> 

<?php require_once '../app/Includes/footer.php'; ?>
