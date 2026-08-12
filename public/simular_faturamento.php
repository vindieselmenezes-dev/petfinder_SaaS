<?php
require_once __DIR__ . '/../config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['org_id']) || !isset($_GET['status'])) {
    header("Location: index.php");
    exit();
}

$orgId = (int)$_GET['org_id'];
$novoStatus = $_GET['status'];
$userId = $_SESSION['user_id'];

// Validar se o status enviado é seguro e permitido no PRD
if (!in_array($novoStatus, ['Ativo', 'Atrasado'])) {
    die("Status inválido.");
}

try {
    $pdo->beginTransaction();

    // 1. Atualizar o status da organização na tabela central do SaaS
    $stmtUpdate = $pdo->prepare("UPDATE organizations SET status = ? WHERE id = ?");
    $stmtUpdate->execute([$novoStatus, $orgId]);

    // 2. Gravar no Log de Auditoria para fins de compliance financeiro
    $payloadFinanceiro = json_encode([
        'motivo' => "Simulação manual de alteração de faturamento",
        'status_antigo' => ($novoStatus === 'Atrasado' ? 'Ativo' : 'Atrasado'),
        'status_novo' => $novoStatus
    ], JSON_UNESCAPED_UNICODE);

    $stmtAudit = $pdo->prepare("
        INSERT INTO audit_logs (table_name, record_id, action, user_id, payload_anterior) 
        VALUES ('organizations', ?, 'CORRECTION', ?, ?)
    ");
    $stmtAudit->execute([$orgId, $userId, $payloadFinanceiro]);

    $pdo->commit();

    // 3. Forçar a atualização das permissões da sessão do usuário logado para ele sentir o bloqueio na hora
    $stmtReset = $pdo->prepare("
        SELECT uor.organization_id, uor.role_id, r.name as role_name, o.name as org_name, o.status as org_status
        FROM organization_user_role uor
        JOIN roles r ON uor.role_id = r.id
        LEFT JOIN organizations o ON uor.organization_id = o.id
        WHERE uor.user_id = ?
    ");
    $stmtReset->execute([$userId]);
    $_SESSION['user_bindings'] = $stmtReset->fetchAll();

    // Se estiver dando suporte/personificando, atualiza as variáveis temporárias também
    if (isset($_SESSION['is_impersonating']) && (int)$_SESSION['impersonated_org_id'] === $orgId) {
        $_SESSION['impersonated_status'] = $novoStatus;
    }

    echo "<script>
            alert('Faturamento Simulado! Status alterado para: " . $novoStatus . "'); 
            window.location.href='painel_b2b.php?org_id=" . $orgId . "';
          </script>";

} catch (Exception $e) {
    $pdo->rollBack();
    die("Erro na automação de faturamento: " . $e->getMessage());
}
