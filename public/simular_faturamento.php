<?php
require_once __DIR__ . '/../app/Models/Usuario.php';
require_once __DIR__ . '/../app/Helpers/EmpresaAcesso.php';
$pdo = Database::conectar();
session_start();

if (!isset($_SESSION['usuario_id']) || !isset($_GET['empresa_id']) || !isset($_GET['status'])) {
    header("Location: index.php");
    exit();
}

$empresaId  = (int)$_GET['empresa_id'];
$novoStatus = $_GET['status'];
$usuarioId  = (int)$_SESSION['usuario_id'];

// Validar se o status enviado é seguro e permitido
if (!in_array($novoStatus, ['Ativo', 'Atrasado', 'Suspenso'], true)) {
    die("Status inválido.");
}

// Só proprietário/administrador da empresa (ou suporte técnico personificando)
// pode mexer no faturamento dela
$temAcessoDireto = EmpresaAcesso::temAcesso($pdo, $empresaId, $usuarioId, ['proprietario', 'administrador']);
$viaImpersonate  = isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating'] === true
    && (int)($_SESSION['impersonated_empresa_id'] ?? 0) === $empresaId;

if (!$temAcessoDireto && !$viaImpersonate) {
    die("Erro: você não tem permissão para alterar o faturamento desta empresa.");
}

try {
    $pdo->beginTransaction();

    // 1. Buscar o status atual antes de trocar, pra registrar na auditoria
    $stmtAtual = $pdo->prepare("SELECT status_pagamento FROM empresas WHERE id = ?");
    $stmtAtual->execute([$empresaId]);
    $statusAntigo = $stmtAtual->fetchColumn() ?: 'Ativo';

    // 2. Atualizar o status de pagamento da empresa
    $stmtUpdate = $pdo->prepare("UPDATE empresas SET status_pagamento = ? WHERE id = ?");
    $stmtUpdate->execute([$novoStatus, $empresaId]);

    // 3. Gravar no Log de Auditoria para fins de compliance financeiro
    $payloadFinanceiro = json_encode([
        'motivo'        => "Simulação manual de alteração de faturamento",
        'status_antigo' => $statusAntigo,
        'status_novo'   => $novoStatus,
    ], JSON_UNESCAPED_UNICODE);

    $stmtAudit = $pdo->prepare("
        INSERT INTO auditoria (usuario_id, tabela, acao, registro_id, detalhes)
        VALUES (?, 'empresas', 'UPDATE', ?, ?)
    ");
    $stmtAudit->execute([$usuarioId, $empresaId, $payloadFinanceiro]);

    $pdo->commit();

    echo "<script>
            alert('Faturamento Simulado! Status alterado para: " . htmlspecialchars($novoStatus) . "');
            window.location.href='painel_b2b.php?empresa_id=" . $empresaId . "';
          </script>";

} catch (Exception $e) {
    $pdo->rollBack();
    die("Erro na automação de faturamento: " . $e->getMessage());
}
