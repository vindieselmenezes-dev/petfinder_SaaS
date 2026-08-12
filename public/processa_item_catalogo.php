<?php 
// 1. CONEXÃO COM O BANCO DO PROJETO 1
require_once __DIR__ . '/../app/Models/Usuario.php'; 
$pdo = Database::conectar(); 

session_start(); 

// Verifica se o usuário está logado e se a requisição veio via POST
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') { 
    die("Acesso não autorizado."); 
} 

// Captura os dados vindos do formulário do catálogo
$orgId       = (int)($_POST['organization_id'] ?? $_GET['org_id'] ?? 1); 
$name        = trim($_POST['name'] ?? ""); 
$type        = trim($_POST['type'] ?? "Produto"); 
$price       = (float)($_POST['price'] ?? 0.0); 
$description = trim($_POST['description'] ?? ""); 
$status      = trim($_POST['status'] ?? "Disponível"); 
$userId      = $_SESSION['user_id']; 

if (empty($name) || $price <= 0) { 
    die("Erro: Preencha o nome do item e insira um preço válido."); 
} 

try { 
    $pdo->beginTransaction(); 

    // 2. INSERÇÃO NA TABELA DE PRODUTOS/SERVIÇOS DO SAAS (catalog_items)
    $stmtItem = $pdo->prepare("
        INSERT INTO catalog_items (organization_id, name, type, price, description, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    "); 
    $stmtItem->execute([$orgId, $name, $type, $price, $description, $status]); 
    $itemId = $pdo->lastInsertId(); 

    // 3. REGRA DO PRD: Trilha de Auditoria (Audit Log)
    $idAdminSaasValido = 1; 
    $stmtAudit = $pdo->prepare("
        INSERT INTO audit_logs (table_name, record_id, action, user_id, payload_anterior) 
        VALUES ('catalog_items', ?, 'INSERT', ?, NULL)
    "); 
    $stmtAudit->execute([$itemId, $idAdminSaasValido]); 

        $pdo->commit(); 

    // Força o fechamento seguro da sessão salvando na memória do PHP antes de mudar de tela
    session_write_close();

    // 4. ALERTA E REDIRECIONAMENTO SEGURO DE VOLTA PARA O DASHBOARD EXECUTIVO
    echo "<script>
        alert('Item cadastrado com sucesso no catálogo corporativo!'); 
        window.location.href='dashboard.php'; 
    </script>"; 
    exit;

    

} catch (Exception $e) { 
    $pdo->rollBack(); 
    die("Erro crítico ao processar item do catálogo: " . $e->getMessage()); 
}
?>
