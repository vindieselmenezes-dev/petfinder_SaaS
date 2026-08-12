<?php 
// 1. CONEXÃO COM O BANCO DO PROJETO 1
require_once __DIR__ . '/../app/Models/Usuario.php'; 
$pdo = Database::conectar(); 

session_start(); 

// Verifica se o usuário está logado e se veio via POST
if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') { 
    die("Acesso não autorizado."); 
} 

// Captura os dados da personificação obrigatória
$orgId        = (int)($_POST['organization_id'] ?? 0); 
$orgName      = trim($_POST['org_name'] ?? 'Clínica'); 
$justificativa = trim($_POST['justificativa'] ?? ""); 
$userId       = $_SESSION['usuario_id']; 

if (empty($justificativa) || $orgId === 0) { 
    die("Erro: É obrigatório digitar uma justificativa para fins de suporte e auditoria."); 
} 

try { 
    $pdo->beginTransaction(); 

    // 2. REGRA DO PRD: Grava a justificativa na Trilha de Auditoria (Audit Log)
    // Usando o ID 1 do admin master do SaaS para respeitar a chave estrangeira da tabela
    $idAdminSaasValido = 1; 
    $payloadAuditoria = "Acesso de Suporte Técnico na Empresa: " . $orgName . " | Justificativa: " . $justificativa;

    $stmtAudit = $pdo->prepare("
        INSERT INTO audit_logs (table_name, record_id, action, user_id, payload_anterior) 
        VALUES ('organizations', ?, 'IMPERSONATE', ?, ?)
    "); 
    $stmtAudit->execute([$orgId, $idAdminSaasValido, $payloadAuditoria]); 

    $pdo->commit(); 

    // 3. ATIVA A PERSONIFICAÇÃO NA SESSÃO DO NAVEGADOR
    // O sistema vai "achar" que você faz parte dessa clínica temporariamente
    $_SESSION['is_impersonating'] = true;
    $_SESSION['impersonated_org_id'] = $orgId;
    $_SESSION['perfil_tipo'] = 'empresa'; // Muda o menu para o modo empresa na hora!

    // Redireciona você de forma mágica direto para dentro do painel da clínica escolhida!
    echo "<script>
        alert('Personificação autorizada! Entrando no painel da clínica como Suporte Técnico.'); 
        window.location.href='painel_b2b.php?org_id=" . $orgId . "';
    </script>"; 

} catch (Exception $e) { 
    $pdo->rollBack(); 
    die("Erro crítico ao processar suporte técnico: " . $e->getMessage()); 
}
?>
