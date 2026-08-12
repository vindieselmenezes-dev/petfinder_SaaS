<?php
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($name) || empty($email) || empty($password)) {
        die("Por favor, preencha todos os campos.");
    }

    // 1. Verificar se o e-mail já existe no banco
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        die("Este e-mail já está cadastrado no sistema.");
    }

    // 2. Criptografar a senha (Segurança profissional)
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();

        // 3. Inserir na tabela central de usuários
        $stmtUser = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmtUser->execute([$name, $email, $hashedPassword]);
        $userId = $pdo->lastInsertId();

        // 4. Buscar o ID do papel de 'Tutor' na tabela roles
        $stmtRole = $pdo->prepare("SELECT id FROM roles WHERE name = 'Tutor'");
        $stmtRole->execute();
        $role = $stmtRole->fetch();
        $roleId = $role['id'];

        // 5. Vincular o usuário como Tutor independente (organization_id fica NULL por enquanto)
        $stmtVinculo = $pdo->prepare("INSERT INTO organization_user_role (organization_id, user_id, role_id) VALUES (NULL, ?, ?)");
        $stmtVinculo->execute([$userId, $roleId]);

        $pdo->commit();

        // Redirecionar para uma página de sucesso temporária
        echo "<script>alert('Conta criada com sucesso! Agora você é um Tutor no sistema.'); window.location.href='index.php';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erro ao salvar no banco de dados: " . $e->getMessage());
    }
}
