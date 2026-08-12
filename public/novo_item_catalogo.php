<?php 
// 1. CONEXÃO COM O BANCO DO PROJETO 1
require_once __DIR__ . '/../app/Models/Usuario.php'; 
$pdo = Database::conectar(); 

session_start(); 

// 2. SEGURANÇA E VALIDAÇÃO
if (!isset($_SESSION['user_id']) || !isset($_GET['org_id'])) { 
    header("Location: login.php"); 
    exit(); 
} 

$orgId = (int)$_GET['org_id'];

// 3. INCLUI O CABEÇALHO E MENU DO PROJETO 1
include __DIR__ . '/../app/Includes/header.php'; 
include __DIR__ . '/../app/Includes/menu.php';
?>

<!-- 4. ADICIONA A MARGEM PARA EMPURRAR O CONTEÚDO PARA A DIREITA -->
<main class="container" style="margin-top: 30px; margin-bottom: 50px; margin-left: 280px; padding: 20px;">

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Item - Catálogo Comercial</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 450px; }
        h2 { color: #2c3e50; text-align: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #34495e; font-weight: bold; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #bdc3c7; border-radius: 6px; box-sizing: border-box; }
        .btn { width: 100%; padding: 12px; background: #3498db; color: white; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; font-weight: bold; }
        .btn:hover { background: #2980b9; }
        .back-link { text-align: center; margin-top: 15px; font-size: 14px; }
        .back-link a { color: #7f8c8d; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <h2>🛒 Adicionar ao Catálogo Vitrine</h2>
        <form action="processa_item_catalogo.php" method="POST">
            <input type="hidden" name="organization_id" value="<?php echo $orgId; ?>">
            
            <div class="form-group">
                <label for="name">Nome do Produto ou Serviço</label>
                <input type="text" id="name" name="name" required placeholder="Ex: Tosa Higiênica, Shmapoo Pet">
            </div>

            <div class="form-group">
                <label for="type">Tipo de Oferta</label>
                <select id="type" name="type" required>
                    <option value="Produto">Produto Físico</option>
                    <option value="Serviço">Serviço Agendável</option>
                </select>
            </div>

            <div class="form-group">
                <label for="price">Preço de Venda (R$)</label>
                <input type="number" id="price" name="price" step="0.01" required placeholder="0.00">
            </div>

            <div class="form-group">
                <label for="description">Descrição/Detalhes</label>
                <textarea id="description" name="description" rows="3" placeholder="Insira informações de prazo, aplicação ou tamanho..."></textarea>
            </div>

            <button type="submit" class="btn">Publicar no Marketplace</button>
        </form>
        <div class="back-link">
            <a href="painel_b2b.php?org_id=<?php echo $orgId; ?>">⬅ Voltar ao Painel</a>
        </div>
    </div>
        <!-- LISTAGEM DE ITENS DO CATÁLOGO / VITRINE -->
    <hr style="border:0; border-top:1px solid #eee; margin:40px 0;">
    <h3 style="margin-bottom: 20px;">📦 Meus Produtos e Serviços Cadastrados</h3>

    <?php
    // Busca os itens cadastrados para esta organização específica
        // Garante que o ID da organização seja o número 1 se não estiver na URL
    $orgIdFiltro = (int)($_GET['org_id'] ?? 1);

    // FILTRO DE PRIVACIDADE: Busca APENAS os itens pertencentes a esta empresa específica
    $stmtLista = $pdo->prepare("SELECT * FROM catalog_items WHERE organization_id = ? ORDER BY id DESC");
    $stmtLista->execute([$orgIdFiltro]);
    $meusItens = $stmtLista->fetchAll(PDO::FETCH_ASSOC);


    if (count($meusItens) > 0):
    ?>
        <table style="width:100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <thead>
                <tr style="background: #3498db; color: white; text-align: left;">
                    <th style="padding: 12px;">Nome do Item</th>
                    <th style="padding: 12px;">Tipo</th>
                    <th style="padding: 12px;">Preço</th>
                    <th style="padding: 12px;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($meusItens as $item): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px;"><strong><?php echo htmlspecialchars($item['name']); ?></strong><br><small style="color:#777;"><?php echo htmlspecialchars($item['description'] ?? ''); ?></small></td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($item['type']); ?></td>
                        <td style="padding: 12px;">R$ <?php echo number_format((float)$item['price'], 2, ',', '.'); ?></td>
                        <td style="padding: 12px;"><span style="background: <?php echo $item['status'] === 'Disponível' ? '#2ecc71' : '#e74c3c'; ?>; color: white; padding: 2px 6px; border-radius: 4px; font-size: 12px;"><?php echo htmlspecialchars($item['status']); ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color: #777; font-style: italic;">Nenhum produto ou serviço cadastrado nesta organização ainda.</p>
    <?php endif; ?>

</main>

<?php 
// Inclui o rodapé padrão com os scripts do Projeto 1
include __DIR__ . '/../app/Includes/footer.php'; 
?>
