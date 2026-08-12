<?php 
// 1. CONEXÃO COM O BANCO DO PROJETO 1
require_once __DIR__ . '/../app/Models/Usuario.php'; 
$pdo = Database::conectar(); 

session_start(); 

// Verifica se o usuário está logado e se veio via POST
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') { 
    die("Acesso não autorizado."); 
} 

// CAPTURA DOS CAMPOS CORRIGIDOS DO FORMULÁRIO
$orgId        = (int)($_POST['organization_id'] ?? 1); 
$prontuarioId = (int)($_POST['prontuario_id'] ?? 0); 
$diagnostico  = trim($_POST['diagnostico'] ?? ""); 
$tratamento   = trim($_POST['tratamento'] ?? ""); 
$userId       = $_SESSION['user_id']; 

if (empty($diagnostico) || empty($tratamento) || $prontuarioId === 0) { 
    die("Erro: Dados incompletos para processar a retificação."); 
} 

try { 
    $pdo->beginTransaction(); 

    // 2. BUSCA O PRONTUÁRIO ORIGINAL PARA COPIAR OS DADOS ANTES DE ATUALIZAR (Para o Log)
    $stmtAntigo = $pdo->prepare("SELECT * FROM prontuarios WHERE id = ?");
    $stmtAntigo->execute([$prontuarioId]);
    $prontuarioAntigo = $stmtAntigo->fetch(PDO::FETCH_ASSOC);

    if (!$prontuarioAntigo) {
        throw new Exception("Prontuário original não encontrado no sistema.");
    }

    // Monta o histórico anterior perfeitamente alinhado com as suas colunas em português
    $payloadAnterior = "Diagnóstico Antigo: " . ($prontuarioAntigo['diagnostico'] ?? '') . " | Tratamento Antigo: " . ($prontuarioAntigo['tratamento'] ?? '');


    // 3. ATUALIZA O PRONTUÁRIO REAL NO BANCO COM OS NOVOS TEXTOS CORRIGIDOS
    $stmtUpdate = $pdo->prepare("
        UPDATE prontuarios 
        SET diagnostico = ?, tratamento = ?, medicamentos = ?, criado_em = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmtUpdate->execute([$diagnostico, $tratamento, $tratamento, $prontuarioId]);

    // 4. REGRA DO PRD: Alimenta a Trilha de Auditoria com a ação de CORREÇÃO (Guarda o que era antes!)
    $idAdminSaasValido = 1; // Respeita a chave estrangeira da tabela audit_logs
    $stmtAudit = $pdo->prepare("
        INSERT INTO audit_logs (table_name, record_id, action, user_id, payload_anterior) 
        VALUES ('prontuarios', ?, 'CORRECTION', ?, ?)
    "); 
    $stmtAudit->execute([$prontuarioId, $idAdminSaasValido, $payloadAnterior]); 

    $pdo->commit(); 

    // Alerta de sucesso e redirecionamento de volta para o Painel B2B
    echo "<script>
        alert('Registro clínico retificado com sucesso! A alteração foi gravada na trilha de auditoria.'); 
        window.location.href='painel_b2b.php?org_id=" . $orgId . "';
    </script>"; 

} catch (Exception $e) { 
    $pdo->rollBack(); 
    die("Erro crítico ao retificar prontuário: " . $e->getMessage()); 
}
?>
