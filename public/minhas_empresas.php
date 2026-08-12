<?php declare(strict_types=1); 
session_start(); 

if (!isset($_SESSION["usuario_id"])) { 
    header("Location: login.php"); 
    exit; 
} 

require_once "../app/Controllers/EmpresaController.php"; 
$controller = new EmpresaController(); 
$usuarioId = (int) $_SESSION["usuario_id"]; 
$empresas = $controller->listarPorUsuario($usuarioId); 

$mensagem = ""; 
$tipoMensagem = ""; 

if (!empty($_SESSION["sucesso_empresa"])) { 
    $mensagem = $_SESSION["sucesso_empresa"]; 
    $tipoMensagem = "sucesso"; 
    unset($_SESSION["sucesso_empresa"]); 
} elseif (!empty($_SESSION["erro_empresa"])) { 
    $mensagem = $_SESSION["erro_empresa"]; 
    $tipoMensagem = "erro"; 
    unset($_SESSION["erro_empresa"]); 
} 

require_once "../app/Includes/header.php"; 
require_once "../app/Includes/menu.php"; 
?> 

<!-- AJUSTE ESTRUTURAL MACRO: Afasta do menu lateral e desce do topo fixo -->
<main class="container" style="margin-top: 100px !important; margin-left: 280px !important; padding: 20px !important; display: block !important;"> 
    
    <!-- CONTÊINER BRANCO UNIFICADO: Mesmo design de alto nível das telas anteriores -->
    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); width: 100%; max-width: 1200px; margin: 40px auto 0 auto !important; position: relative !important; display: block !important;"> 
        
        <h1 style="color: #2c3e50; margin-bottom: 5px; font-family: sans-serif; font-weight: bold;">🏢 Minhas Empresas</h1> 
        <p style="color: #7f8c8d; margin-bottom: 25px; font-family: sans-serif;">Gerencie as organizações, clínicas e comércios cadastrados no seu perfil.</p> 

        <?php if (!empty($mensagem)): ?> 
            <div class="mensagem <?= $tipoMensagem; ?>" style="padding: 12px; margin-bottom: 20px; border-radius: 6px; background: <?= $tipoMensagem === 'sucesso' ? '#d4edda; color: #155724;' : '#f8d7da; color: #721c24;'; ?>; font-family: sans-serif; font-weight: bold;"> 
                <?= htmlspecialchars($mensagem); ?> 
            </div> 
        <?php endif; ?> 

        <!-- BOTÃO DE CADASTRO ESTILIZADO -->
        <div style="margin-bottom: 25px; text-align: left;"> 
            <a href="cadastrar_empresa.php" style="display: inline-block; background: #2ecc71; color: white; text-decoration: none; padding: 12px 20px; border-radius: 6px; font-weight: bold; font-size: 14px; font-family: sans-serif; box-shadow: 0 2px 4px rgba(46, 204, 113, 0.2);">➕ Cadastrar Nova Empresa</a> 
        </div> 

        <!-- TABELA CORPORATIVA PADRONIZADA -->
        <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.02); font-family: sans-serif;"> 
            <thead> 
                <tr style="background: #34495e; color: white; text-align: left;"> 
                    <th style="padding: 15px; text-align: center; width: 100px;">Logo</th> 
                    <th style="padding: 15px;">Nome</th> 
                    <th style="padding: 15px;">Categoria</th> 
                    <th style="padding: 15px;">Cidade</th> 
                    <th style="padding: 15px;">Status</th> 
                    <th style="padding: 15px; text-align: center;">Ações</th> 
                </tr> 
            </thead> 
            <tbody> 
                <?php if (!empty($empresas) && count($empresas) > 0): ?> 
                    <?php foreach ($empresas as $emp): ?> 
                        <tr style="border-bottom: 1px solid #eaeaea;"> 
                            <td style="padding: 15px; text-align: center;"> 
                                <?php $logoNome = !empty($emp['logo']) ? $emp['logo'] : 'sem-logo.png'; ?> 
                                <img src="../uploads/logos/<?= htmlspecialchars($logoNome); ?>" width="50" height="50" style="object-fit: cover; border-radius: 6px; border: 1px solid #eee;" alt="Logo" onerror="this.src='../assets/img/sem-foto.png';"> 
                            </td> 
                            <td style="padding: 15px; font-weight: bold; color: #333;"> 
                                <?= htmlspecialchars($emp["nome"] ?? 'Sem Nome'); ?> 
                            </td> 
                            <td style="padding: 15px; color: #555;"> 
                                <?= htmlspecialchars($emp["categoria"] ?? 'Não informada'); ?> 
                            </td> 
                            <td style="padding: 15px; color: #777;"> 
                                📍 <?= htmlspecialchars($emp["cidade"] ?? 'Não informada'); ?> 
                            </td> 
                            <td style="padding: 15px;"> 
                                <?php 
                                $status = $emp['status'] ?? 'Ativo'; 
                                $bg = '#e8f5e9'; 
                                $cl = '#2e7d32'; 
                                if ($status === 'Atrasado' || $status === 'Inativo') { 
                                    $bg = '#ffebee'; 
                                    $cl = '#c62828'; 
                                } 
                                ?> 
                                <span style="background: <?= $bg; ?>; color: <?= $cl; ?>; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold;"> 
                                    <?= htmlspecialchars($status); ?> 
                                </span> 
                            </td> 
                            <td style="padding: 15px; text-align: center; white-space: nowrap;"> 
                                <a href="painel_b2b.php?org_id=<?= (int)$emp['id']; ?>" style="color: #2ecc71; text-decoration: none; font-weight: bold; font-size: 14px; margin-right: 15px;">🚀 Acessar Painel</a> 
                                <a href="editar_empresa.php?id=<?= (int)$emp['id']; ?>" style="color: #3498db; text-decoration: none; font-weight: bold; font-size: 14px;">✏️ Editar</a> 
                            </td> 
                        </tr> 
                    <?php endforeach; ?> 
                <?php else: ?> 
                    <tr> 
                        <td colspan="6" style="padding: 30px; text-align: center; color: #95a5a6; font-style: italic;">Você ainda não possui nenhuma empresa cadastrada na plataforma.</td> 
                    </tr> 
                <?php endif; ?> 
            </tbody> 
        </table> 
    </div> 
</main> 

<?php require_once "../app/Includes/footer.php"; ?>
