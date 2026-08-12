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
$userId = $_SESSION['user_id']; 

// 3. CARREGAR OS ANIMAIS (Ajustado para a sua tabela real 'usuarios' e coluna 'nome')
$stmtPets = $pdo->query("SELECT id, nome as name FROM pets ORDER BY nome ASC");
 
$pets = $stmtPets->fetchAll(); 

// 4. INCLUI O CABEÇALHO E MENU DO PROJETO 1
include __DIR__ . '/../app/Includes/header.php'; 
include __DIR__ . '/../app/Includes/menu.php';
?>

<!-- 5. ADICIONA A MARGEM PARA EMPURRAR O CONTEÚDO PARA A DIREITA -->
<main class="container" style="margin-top: 30px; margin-bottom: 50px; margin-left: 280px; padding: 20px;">

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Registro Clínico</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
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
        <h2>📋 Emitir Novo Prontuário Médico</h2>
        <form action="processa_prontuario.php" method="POST">
            <input type="hidden" name="organization_id" value="<?php echo $orgId; ?>">
            
            <div class="form-group">
                <label for="pet_id">Selecione o Paciente (Pet)</label>
                <select id="pet_id" name="pet_id" required>
                    <option value="">-- Escolha o Animal --</option>
                    <?php foreach ($pets as $pet): ?>
                        <option value="<?php echo $pet['id']; ?>"><?php echo htmlspecialchars($pet['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="diagnostico">Diagnóstico Clínico</label>
                <textarea id="diagnostico" name="diagnostico" rows="3" required placeholder="Descreva os sintomas e o diagnóstico..."></textarea>
            </div>

            <div class="form-group">
                <label for="tratamento">Tratamento Prescrito / Medicamentos</label>
                <textarea id="tratamento" name="tratamento" rows="3" required placeholder="Dosagens, exames solicitados e recomendações..."></textarea>
            </div>

            <button type="submit" class="btn">Garantir e Salvar Registro</button>
        </form>
        <div class="back-link">
            <a href="painel_b2b.php?org_id=<?php echo $orgId; ?>">⬅ Cancelar e Voltar</a>
        </div>
    </div>
</main>

<?php 
// Inclui o rodapé padrão com os scripts do Projeto 1
include __DIR__ . '/../app/Includes/footer.php'; 
?>

