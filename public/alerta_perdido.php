<?php
require_once __DIR__ . '/../config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['pet_id'])) {
    header("Location: index.php");
    exit();
}

$petId = (int)$_GET['pet_id'];
$userId = $_SESSION['user_id'];

// Verificar se o pet realmente pertence a esse usuário
$stmtCheck = $pdo->prepare("SELECT name FROM pets p JOIN pet_tutores pt ON p.id = pt.pet_id WHERE p.id = ? AND pt.user_id = ?");
$stmtCheck->execute([$petId, $userId]);
$pet = $stmtCheck->fetch();

if (!$pet) {
    die("Pet não encontrado ou você não tem permissão.");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emitir Alerta de Pet Perdido</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 450px; }
        h2 { color: #e74c3c; text-align: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #34495e; font-weight: bold; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #bdc3c7; border-radius: 6px; box-sizing: border-box; }
        .btn { width: 100%; padding: 12px; background: #e74c3c; color: white; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; font-weight: bold; }
        .btn:hover { background: #c0392b; }
        .back-link { text-align: center; margin-top: 15px; font-size: 14px; }
        .back-link a { color: #7f8c8d; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <h2>🚨 Emitir Alerta: <?php echo htmlspecialchars($pet['name']); ?> Sumiu</h2>
        <form action="processa_alerta.php" method="POST">
            <input type="hidden" name="pet_id" value="<?php echo $petId; ?>">
            
            <div class="form-group">
                <label for="last_seen_location">Último lugar visto (Endereço/Ponto de referência)</label>
                <input type="text" id="last_seen_location" name="last_seen_location" required placeholder="Ex: Próximo à Praça Central, Bairro Centro">
            </div>

            <!-- Simulando coordenadas geográficas reais de onde o pet sumiu -->
            <!-- Configurado por padrão em um raio próximo ao endereço do usuário (-23.550...) -->
            <input type="hidden" name="lost_latitude" value="-23.553000">
            <input type="hidden" name="lost_longitude" value="-46.635000">

            <div class="form-group">
                <label for="description">Características marcantes / Detalhes adicionais</label>
                <textarea id="description" name="description" rows="3" placeholder="Ex: Usava coleira vermelha, é muito dócil mas está assustado."></textarea>
            </div>

            <button type="submit" class="btn">Disparar Alerta na Comunidade</button>
        </form>
        <div class="back-link">
            <a href="index.php">⬅ Cancelar e Voltar</a>
        </div>
    </div>
</body>
</html>
