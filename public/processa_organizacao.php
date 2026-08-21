<?php
require_once __DIR__ . '/../app/Models/Usuario.php';
$pdo = Database::conectar();
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Acesso não autorizado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $cnpj = trim($_POST['cnpj']);
    $userId = $_SESSION['user_id'];

    if (empty($name)) {
        die("O nome da organização é obrigatório.");
    }

    try {
        $pdo->beginTransaction();

        // 1. Criar a organização no banco (Status padrão: Ativo)
        $stmtOrg = $pdo->prepare("INSERT INTO organizations (name, cnpj, status) VALUES (?, ?, 'Ativo')");
        $stmtOrg->execute([$name, !empty($cnpj) ? $cnpj : null]);
        $orgId = $pdo->lastInsertId();

        // 2. Buscar o ID do papel de administrador corporativo
        $stmtRole = $pdo->prepare("SELECT id FROM roles WHERE name = 'Admin_Clinica'");
        $stmtRole->execute();
        $role = $stmtRole->fetch();
        $roleId = $role['id'];

        // 3. Criar o vínculo Multi-tenant do usuário com a nova empresa
        $stmtVinculo = $pdo->prepare("INSERT INTO organization_user_role (organization_id, user_id, role_id) VALUES (?, ?, ?)");
        $stmtVinculo->execute([$orgId, $userId, $roleId]);

        $pdo->commit();

        // Atualizar as amarrações de sessões do usuário para incluir a nova empresa imediatamente
        $stmtReset = $pdo->prepare("
            SELECT uor.organization_id, uor.role_id, r.name as role_name, o.name as org_name, o.status as org_status
            FROM organization_user_role uor
            JOIN roles r ON uor.role_id = r.id
            LEFT JOIN organizations o ON uor.organization_id = o.id
            WHERE uor.user_id = ?
        ");
        $stmtReset->execute([$userId]);
        $_SESSION['user_bindings'] = $stmtReset->fetchAll();

        echo "<script>alert('Organização registrada com sucesso! Seu painel corporativo está liberado.'); window.location.href='painel_b2b.php?org_id=" . $orgId . "';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erro ao registrar organização: " . $e->getMessage());
    }
}
