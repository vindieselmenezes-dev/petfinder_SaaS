<?php
require_once __DIR__ . '/../config/database.php';
session_start();

// REGRA DO PRD: Paginação por cursor. Captura o ID do último item visualizado.
$lastId = isset($_GET['next_id']) ? (int)$_GET['next_id'] : 0;
$limit = 2; // Definido em 2 itens por página para você testar o funcionamento da paginação facilmente

// Monta o SQL dinamicamente baseado no Keyset (Paginação via Cursor)
if ($lastId > 0) {
    $stmt = $pdo->prepare("
        SELECT c.*, o.name as org_name 
        FROM catalog_items c
        JOIN organizations o ON c.organization_id = o.id
        WHERE c.id > ? AND c.status = 'Disponível'
        ORDER BY c.id ASC LIMIT ?
    ");
    $stmt->execute([$lastId, $limit]);
} else {
    $stmt = $pdo->prepare("
        SELECT c.*, o.name as org_name 
        FROM catalog_items c
        JOIN organizations o ON c.organization_id = o.id
        WHERE c.status = 'Disponível'
        ORDER BY c.id ASC LIMIT ?
    ");
    $stmt->execute([$limit]);
}

$itens = $stmt->fetchAll();

// Descobrir qual o ID do último item desta página para servir de cursor para a próxima
$proximoId = 0;
if (count($itens) > 0) {
    $proximoId = $itens[count($itens) - 1]['id'];
}

// Checar se ainda existem mais registros depois desse cursor
$stmtCheckMore = $pdo->prepare("SELECT id FROM catalog_items WHERE id > ? AND status = 'Disponível' LIMIT 1");
$stmtCheckMore->execute([$proximoId]);
$temMaisRegistros = $stmtCheckMore->fetch() ? true : false;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vitrine Comercial - Ecossistema Pet</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; display: flex; justify-content: center; }
        .container { max-width: 600px; width: 100%; }
        h1 { color: #2c3e50; text-align: center; }
        .product-card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 5px solid #2ecc71; }
        .type-badge { font-size: 11px; padding: 3px 8px; border-radius: 12px; color: white; font-weight: bold; }
        .price { font-size: 18px; color: #2ecc71; font-weight: bold; margin-top: 10px; }
        .nav-btn { display: block; text-align: center; background: #3498db; color: white; padding: 12px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 20px; }
        .nav-btn:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛍️ Vitrine de Comércios e Serviços Pet</h1>
        <p style="text-align:center;"><a href="index.php" style="color:#7f8c8d; text-decoration:none;">🏠 Voltar para a Home</a></p>

        <?php if (count($itens) > 0): ?>
            <?php foreach ($itens as $item): ?>
                <div class="product-card">
                    <span class="type-badge" style="background: <?php echo $item['type'] === 'Produto' ? '#3498db' : '#9b59b6'; ?>;">
                        <?php echo $item['type']; ?>
                    </span>
                    <h3 style="margin: 10px 0 5px 0; color:#2c3e50;"><?php echo htmlspecialchars($item['name']); ?></h3>
                    <p style="margin: 0; font-size:12px; color:#7f8c8d;">Anunciante: <strong><?php echo htmlspecialchars($item['org_name']); ?></strong></p>
                    <p style="color:#34495e; font-size:14px; margin: 10px 0;"><?php echo htmlspecialchars($item['description']); ?></p>
                    <div class="price">R$ <?php echo number_format($item['price'], 2, ',', '.'); ?></div>
                </div>
            <?php endforeach; ?>

            <!-- Link de paginação usando a lógica de Cursor/Keyset -->
            <?php if ($temMaisRegistros): ?>
                <a href="vitrine.php?next_id=<?php echo $proximoId; ?>" class="nav-btn">Ver mais itens ➡️</a>
            <?php else: ?>
                <p style="text-align:center; color:#95a5a6; font-size:14px; margin-top:30px;">🎉 Você chegou ao fim do catálogo!</p>
            <?php endif; ?>

        <?php else: ?>
            <p style="text-align:center; color:#95a5a6;">Nenhum produto ou serviço disponível no momento.</p>
        <?php endif; ?>
    </div>
</body>
</html>
