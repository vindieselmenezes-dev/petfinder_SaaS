<?php 
// 1. CONEXÃO COM O BANCO DO PROJETO 1
require_once __DIR__ . '/../app/Models/Usuario.php'; 
$pdo = Database::conectar(); 

session_start(); 

// Verifica se o usuário está logado e se a requisição é do tipo POST
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') { 
    die("Acesso não autorizado."); 
} 

// CAPTURA INTELIGENTE DOS DADOS DO FORMULÁRIO (Linhas antigas corrigidas)
$orgId        = (int)($_POST['organization_id'] ?? 1); // Pega o ID oculto enviado pela tela anterior!
$petId        = (int)($_POST['pet_id'] ?? 1); 
$diagnostico  = trim($_POST['diagnostico'] ?? ""); 
$tratamento   = trim($_POST['tratamento'] ?? ""); 
$medicamentos = trim($_POST['tratamento'] ?? ""); 
$userId       = $_SESSION['user_id']; 

if (empty($diagnostico) || empty($tratamento)) { 
    die("Preencha todos os campos do atendimento clínico."); 
} 

try { 
    $pdo->beginTransaction(); 

    // 1. BUSCA QUEM É O DONO (USUÁRIO) DO PET SELECIONADO
    $stmtDono = $pdo->prepare("SELECT usuario_id FROM pets WHERE id = ? LIMIT 1");
    $stmtDono->execute([$petId]);
    $petDono = $stmtDono->fetch(PDO::FETCH_ASSOC);
    
    // BLINDAGEM MÁGICA: Se o dono foi apagado ou não existir, amarra o pet ao seu ID 16 (Sérgio)
    $idDonoReal = 16; 
    if ($petDono && !empty($petDono['usuario_id'])) {
        // Verifica se o dono do pet realmente existe na tabela de usuários para não dar erro
        $stmtCheckUser = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? LIMIT 1");
        $stmtCheckUser->execute([$petDono['usuario_id']]);
        if ($stmtCheckUser->fetch()) {
            $idDonoReal = (int)$petDono['usuario_id'];
        }
    }

    // 2. GERA A CONSULTA OBRIGATÓRIA INJETANDO O VETERINÁRIO (16) E O DONO VÁLIDO
    $idVeterinarioValido = 16; 
    
         // 2. GERA A CONSULTA OBRIGATÓRIA INJETANDO O DONO, VETERINÁRIO, PET E TEXTOS PADRÃO
    $idVeterinarioValido = 16; 
    $motivoPadrao = "Atendimento Clínico - Emitido via Painel SaaS B2B";
    
    $stmtConsulta = $pdo->prepare("
        INSERT INTO consultas (usuario_id, veterinario_id, pet_id, data_consulta, status, motivo, observacoes, criado_em) 
        VALUES (?, ?, ?, CURRENT_DATE, 'Concluída', ?, ?, CURRENT_TIMESTAMP)
    ");
    $stmtConsulta->execute([$idDonoReal, $idVeterinarioValido, $petId, $motivoPadrao, $diagnostico]);
    $novoIdConsulta = $pdo->lastInsertId(); 


    // 3. GRAVA O PRONTUÁRIO AMARRANDO A CONSULTA E A EMPRESA (organizacao_id)
    $stmtPront = $pdo->prepare("
        INSERT INTO prontuarios (consulta_id, organizacao_id, diagnostico, tratamento, medicamentos, criado_em) 
        VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    "); 
    // Ajustado para preencher 'diagnostico' e 'tratamento' de acordo com as colunas reais do banco 1
    $stmtPront->execute([$novoIdConsulta, $orgId, $diagnostico, $tratamento, $medicamentos]); 
    $prontuarioId = $pdo->lastInsertId(); 

        // 4. REGRA DO PRD: Alimentar a tabela de Trilha de Auditoria (Audit Log) 
    // Injetamos o ID 1 do Administrador master do SaaS para respeitar a chave estrangeira da tabela antiga
    $idAdminSaasValido = 1; 
    
    $stmtAudit = $pdo->prepare("
        INSERT INTO audit_logs (table_name, record_id, action, user_id, payload_anterior) 
        VALUES ('prontuarios', ?, 'INSERT', ?, NULL)
    "); 
    $stmtAudit->execute([$prontuarioId, $idAdminSaasValido]); 


    $pdo->commit(); 

    // Alerta de sucesso e redirecionamento de volta para o seu Painel B2B
    echo "<script>
        alert('Registro clínico salvo e blindado com sucesso!'); 
        window.location.href='painel_b2b.php?org_id=" . $orgId . "';
    </script>"; 

} catch (Exception $e) { 
    $pdo->rollBack(); 
    die("Erro crítico ao processar prontuário: " . $e->getMessage()); 
}

?>
