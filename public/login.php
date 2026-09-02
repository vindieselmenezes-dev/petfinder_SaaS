<?php declare(strict_types=1); 
require_once "../app/Models/Usuario.php"; 
$pdo = Database::conectar(); 
session_start(); 

$mensagem = "";
$tipoMensagem = "";

if (isset($_GET['senha_redefinida'])) {
    $mensagem = "Senha redefinida com sucesso! Faça login com sua nova senha.";
    $tipoMensagem = "sucesso";
}

function voltarSeguro(?string $url): ?string {
    // só aceita caminho relativo dentro do próprio site — nunca uma URL
    // completa, pra não virar um open-redirect.
    if (!$url || preg_match('#^(https?:)?//#i', $url) || str_starts_with($url, '\\')) {
        return null;
    }
    return $url;
}

$voltar = voltarSeguro($_GET['voltar'] ?? $_POST['voltar'] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    $email = trim($_POST['email'] ?? ''); 
    $password = $_POST['password'] ?? ''; 

    if (empty($email) || empty($password)) { 
        $mensagem = "Por favor, preencha todos os campos."; 
        $tipoMensagem = "erro"; 
    } else {
        // 1. Busca o usuário JUNTO com o tipo de perfil dele (tabela perfis)
        $stmt = $pdo->prepare(
            "SELECT u.*, p.tipo AS perfil_tipo
             FROM usuarios u
             LEFT JOIN perfis p ON p.usuario_id = u.id
             WHERE u.email = ?"
        );
        $stmt->execute([$email]); 
        $user = $stmt->fetch(PDO::FETCH_ASSOC); 

        // 2. Valida a senha usando apenas hash bcrypt (seguro)
        $senhaValida = false;
        if ($user) {
            $senhaBanco = trim($user['senha']);

            if (password_verify($password, $senhaBanco)) {
                $senhaValida = true;
            }
        }

        if ($senhaValida) { 
            // Alimenta estritamente as sessões necessárias de identificação
            $_SESSION['usuario_id']    = $user['id']; 
            $_SESSION['usuario_nome']  = $user['nome'] ?? 'Usuário'; 
            $_SESSION['usuario_email'] = $user['email']; 
            // Usa o tipo real da tabela perfis; se não existir registro, assume 'cliente' (tutor comum)
            // Se não existir registro em `perfis`, usa usuarios.tipo_usuario como fonte
            // de verdade antes de cair no padrão 'cliente' — evita usuário com tipo_usuario
            // definido (ex: 'empresa') ser tratado como cliente comum por falta de linha em perfis.
            $_SESSION['perfil_tipo']   = $user['perfil_tipo'] ?? $user['tipo_usuario'] ?? 'cliente'; 

            header("Location: " . ($voltar ?? "dashboard.php"));
            exit(); 
        } else { 
            $mensagem = "E-mail ou senha incorretos."; 
            $tipoMensagem = "erro"; 
        } 
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ecossistema Pet</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #1a1f2c !important; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { color: #2c3e50; text-align: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #34495e; font-weight: bold; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #bdc3c7; border-radius: 6px; box-sizing: border-box; }
        .btn { width: 100%; padding: 12px; background: #2ecc71; color: white; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; font-weight: bold; }
        .btn:hover { background: #27ae60; }
        .mensagem { padding: 10px; margin-bottom: 15px; border-radius: 6px; text-align: center; font-size: 14px; font-weight: bold; }
        .mensagem.erro { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .mensagem.sucesso { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>
    <div class="card">
        <h2>🐾 Acessar Conta</h2>
        
        <?php if ($mensagem): ?>
            <div class="mensagem <?= $tipoMensagem; ?>"><?= htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <form action="" method="POST" autocomplete="off">
            <?php if ($voltar): ?>
                <input type="hidden" name="voltar" value="<?= htmlspecialchars($voltar) ?>">
            <?php endif; ?>
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required placeholder="Digite seu e-mail">
            </div>
            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required placeholder="Digite sua senha">
            </div>
            <button type="submit" class="btn">Entrar no Ecossistema</button>
            <p style="text-align:center; margin-top:12px; font-size:14px;">
                <a href="esqueci_senha.php" style="color:#7f8c8d; text-decoration:none;">Esqueci minha senha</a>
            </p>
        </form>
    </div>
</body>
</html>
