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

$tituloPagina = "Gestão de Usuários";

require_once '../app/Includes/header.php'; 
require_once '../app/Includes/menu.php'; 
?> 

<main class="conteudo">
<div class="container">

    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); width: 100%; max-width: 1200px; margin: 0 auto;"> 
        
        <h1 style="color: #2c3e50; margin-bottom: 5px;">👥 Gestão de Usuários</h1> 
        <p style="color: #7f8c8d; margin-bottom: 30px;">Veja o perfil completo de cada tutor: dados da conta, pets, empresas e permissões.</p> 

        <table class="tabela-pets"> 
            <thead> 
                <tr> 
                    <th>Nome</th> 
                    <th>Email</th> 
                    <th>Perfil</th> 
                    <th style="text-align: center;">Ações</th> 
                </tr> 
            </thead> 
            <tbody> 
                <?php if (count($usuarios) > 0): ?> 
                    <?php foreach ($usuarios as $usuario): ?> 
                        <tr> 
                            <td style="font-weight: bold;"><?= htmlspecialchars(($usuario['nome'] ?? '') . ' ' . ($usuario['sobrenome'] ?? '')); ?></td> 
                            <td><?= htmlspecialchars($usuario['email'] ?? ''); ?></td> 
                            <td><span style="background: #e1f5fe; color: #0288d1; padding: 3px 8px; border-radius: 4px; font-size: 13px; font-weight: bold;"><?= htmlspecialchars($usuario['perfil'] ?? 'cliente'); ?></span></td> 

                            <td style="text-align: center; white-space:nowrap;"> 
                                <a href="admin_usuario_detalhe.php?id=<?= $usuario['id']; ?>" class="btn-acao" style="background:#2563eb; color:white;">👁️ Ver Perfil</a>
                                <?php if ($usuario['id'] !== $_SESSION['usuario_id']): ?> 
                                    <a href="admin_usuarios.php?delete_id=<?= $usuario['id']; ?>" onclick="return confirm('Deseja remover este usuário?');" class="btn-acao" style="background:#e74c3c; color:white;">🗑 Excluir</a> 
                                <?php else: ?> 
                                    <span style="background: #2ecc71; color: white; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: bold;">Você (Ativo)</span> 
                                <?php endif; ?> 
                            </td> 
                        </tr> 
                    <?php endforeach; ?> 
                <?php else: ?> 
                    <tr> 
                        <td colspan="4" style="padding: 20px; text-align: center; color: #95a5a6; font-style: italic;">Nenhum usuário encontrado.</td> 
                    </tr> 
                <?php endif; ?> 
            </tbody> 
        </table> 
    </div>
</div>
</main> 

<?php require_once '../app/Includes/footer.php'; ?>
