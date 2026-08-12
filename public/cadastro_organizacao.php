<?php
session_start();
// Proteção básica: se não estiver logado, manda para o login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Organização - Ecossistema Pet</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 450px; }
        h2 { color: #2c3e50; text-align: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #34495e; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #bdc3c7; border-radius: 6px; box-sizing: border-box; }
        .btn { width: 100%; padding: 12px; background: #3498db; color: white; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; font-weight: bold; }
        .btn:hover { background: #2980b9; }
        .back-link { text-align: center; margin-top: 15px; font-size: 14px; }
        .back-link a { color: #7f8c8d; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <h2>🏢 Registrar Clínica, Petshop ou ONG</h2>
        <form action="processa_organizacao.php" method="POST">
            <div class="form-group">
                <label for="name">Nome da Organização (Nome Fantasia)</label>
                <input type="text" id="name" name="name" required placeholder="Ex: Clínica Veterinária Amigo Pet">
            </div>
            <div class="form-group">
                <label for="cnpj">CNPJ (Opcional)</label>
                <input type="text" id="cnpj" name="cnpj" placeholder="00.000.000/0001-00">
            </div>
            <button type="submit" class="btn">Criar Organização B2B</button>
        </form>
        <div class="back-link">
            <a href="index.php">⬅ Voltar para a Home</a>
        </div>
    </div>
</body>
</html>
