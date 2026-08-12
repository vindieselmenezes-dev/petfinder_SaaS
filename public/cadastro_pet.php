<?php declare(strict_types=1); 
require_once "../app/Models/Usuario.php"; 
$pdo = Database::conectar(); 
session_start(); 

$mensagem = "";
$tipoMensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    $nome = trim($_POST['name'] ?? ''); 
    $email = trim($_POST['email'] ?? ''); 
    $password = $_POST['password'] ?? ''; 
    $perfilEscolhido = $_POST['perfil'] ?? 'Tutor'; // 'Tutor', 'Admin_Clinica', 'Master_Admin'

    if (empty($nome) || empty($email) || empty($password)) { 
        $mensagem = "Por favor, preencha todos os campos."; 
        $tipoMensagem = "erro"; 
    } elseif (strlen($password) < 6) {
        $mensagem = "A senha deve ter no mínimo 6 caracteres.";
        $tipoMensagem = "erro";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $mensagem = "Este e-mail já está cadastrado no sistema.";
            $tipoMensagem = "erro";
        } else {
            try {
                $pdo->beginTransaction();

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Grava nas colunas exatas da sua tabela users: id, name, email, password, latitude, longitude, created_at
                $stmtUser = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                $stmtUser->execute([$nome, $email, $hashedPassword]);
                $userId = $pdo->lastInsertId();

                // Busca o ID do papel escolhido na tabela roles para fazer o vínculo multi-tenant
                $stmtRole = $pdo->prepare("SELECT id FROM roles WHERE name = ? LIMIT 1");
                $stmtRole->execute([$perfilEscolhido]);
                $roleId = $stmtRole->fetchColumn();

                if (!$roleId) {
                    $roleId = 1; // Padrão Tutor se não achar
                }

                // Cria a amarração de acesso na tabela intermediária
                $stmtVinculo = $pdo->prepare("INSERT IGNORE INTO organization_user_role (organization_id, user_id, role_id) VALUES (NULL, ?, ?)");
                $stmtVinculo->execute([$userId, (int)$roleId]);

                $pdo->commit();

                echo "<script>alert('Conta criada com sucesso no modelo SaaS! Redirecionando para o login.'); window.location.href='login.php';</script>";
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                $mensagem = "Erro ao salvar no banco de dados: " . $e->getMessage();
                $tipoMensagem = "erro";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - Ecossistema Pet</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { color: #2c3e50; text-align: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #34495e; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #bdc3c7; border-radius: 6px; box-sizing: border-box; }
        .btn { width: 100%; padding: 12px; background: #3498db; color: white; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; font-weight: bold; }
        .btn:hover { background: #2980b9; }
        .mensagem { padding: 10px; margin-bottom: 15px; border-radius: 6px; text-align: center; font-size: 14px; font-weight: bold; }
        .mensagem.erro { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .footer-link { text-align: center; margin-top: 15px; font-size: 14px; color: #7f8c8d; }
        .footer-link a { color: #3498db; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="card">
        <h2>🐾 Criar Conta Única</h2>
        
        <?php if ($mensagem): ?>
            <div class="mensagem <?= $tipoMensagem; ?>"><?= htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="name">Nome Completo</label>
                <input type="text" id="name" name="name" required placeholder="Digite seu nome">
            </div>
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required placeholder="Ex: meuadm@email.com">
            </div>
            <div class="form-group">
                <label for="password">Senha (Mínimo 6 caracteres)</label>
                <input type="password" id="password" name="password" required placeholder="Crie uma senha segura">
            </div>
            
            <div class="form-group">
                <label for="perfil">Tipo de Permissão (Role)</label>
                <select id="perfil" name="perfil" required>
                    <option value="Tutor">Tutor (Cliente Comum)</option>
                    <option value="Admin_Clinica">Empresa (Clínica Veterinária)</option>
                    <option value="Master_Admin">Administrador Global (Master)</option>
                </select>
            </div>

            <button type="submit" class="btn">Cadastrar no Ecossistema</button>
        </form>
        <div class="footer-link">
            Já tem uma conta? <a href="login.php">Faça login</a>
        </div>
    </div>
</body>
</html>
