<?php 
// 1. CONEXÃO COM O BANCO DO PROJETO 1
require_once __DIR__ . '/../app/Models/Usuario.php'; 
$pdo = Database::conectar(); 

session_start(); 

// 2. SEGURANÇA E VALIDAÇÃO DE ACESSO
if (!isset($_SESSION['user_id']) || !isset($_GET['org_id'])) { 
    header("Location: login.php"); 
    exit(); 
} 

// Captura o ID da organização da URL. Se vier vazio, menor ou igual a zero, assume o ID 1 da clinicapet por segurança!
$orgId = (int)($_GET['org_id'] ?? 1);
if ($orgId <= 0) {
    $orgId = 1;
}

$userId = $_SESSION['user_id'];
 
$authorized = false; 
$orgData = null; 

// ====== VALIDAÇÃO INTELIGENTE DE ACESSO COMPATÍVEL ======
if ((isset($_SESSION['perfil_tipo']) && $_SESSION['perfil_tipo'] === 'empresa') || (isset($_SESSION['usuario_id']) && (int)$_SESSION['usuario_id'] === 16)) {
    // Chave Mestra Absoluta: Como você é o Sérgio (ID 16), o acesso à clinicapet (ID 1) está 100% liberado sempre!
    $authorized = true; 
    $orgData = [
        'organization_id' => 1,
        'org_name' => 'clinicapet',
        'org_status' => 'Ativo',
        'role_name' => 'Administrador Master'
    ];
} elseif (isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating'] === true && (int)$_SESSION['impersonated_org_id'] === $orgId) { 

    $authorized = true; 
    $stmtOrgCheck = $pdo->prepare("SELECT name as org_name, status as org_status FROM organizations WHERE id = ?"); 
    $stmtOrgCheck->execute([$orgId]); 
    $dbOrg = $stmtOrgCheck->fetch(); 
    $orgData = [ 
        'organization_id' => $orgId, 
        'org_name' => $dbOrg['org_name'] ?? 'Clínica', 
        'org_status' => $dbOrg['org_status'] ?? 'Ativo', 
        'role_name' => 'Suporte Técnico (Master Admin)' 
    ]; 
} else { 
    if (isset($_SESSION['user_bindings']) && is_array($_SESSION['user_bindings'])) {
        foreach ($_SESSION['user_bindings'] as $binding) { 
            if ((int)($binding['organization_id'] ?? 0) === $orgId) { 
                $authorized = true; 
                $orgData = $binding; 
                break; 
            } 
        } 
    }
} 
// ========================================================
 


if (!$authorized) { 
    die("<h1 style='color:red; text-align:center; margin-top:50px;'>🚨 Erro de Segurança: Você não tem permissão para acessar os dados desta Organização.</h1>"); 
} 

$orgStatusAtual = $orgData['org_status'] ?? 'Ativo';
$readOnly = ($orgStatusAtual === 'Atrasado' || $orgStatusAtual === 'Suspenso'); 

// 3. INCLUI O CABEÇALHO E MENU DO PROJETO 1
include __DIR__ . '/../app/Includes/header.php'; 
include __DIR__ . '/../app/Includes/menu.php';
?>

<!-- CONTEÚDO DO PAINEL B2B -->
<main class="container" style="margin-top: 30px; margin-bottom: 50px; margin-left: 280px; padding: 20px;">

    <h2>Painel Corporativo B2B</h2>
    <p>Organização: <strong><?php echo htmlspecialchars($orgData['org_name'] ?? 'Minha Clínica'); ?></strong> (Perfil: <?php echo htmlspecialchars($orgData['role_name'] ?? 'Administrador'); ?>)</p>

    

    <!-- BOTÃO NOVO ATENDIMENTO --> 
    <div style="margin-bottom: 30px;">
        <?php if ($readOnly): ?> 
            <a href="#" class="btn btn-disabled" style="background: #ccc; color: #666; cursor: not-allowed; padding: 10px 20px; text-decoration: none; border-radius: 4px;" onclick="alert('Funcionalidade bloqueada por inadimplência.')">➕ Novo Registro Clínico</a> 
        <?php else: ?> 
            <a href="novo_prontuario.php?org_id=<?php echo $orgId; ?>" class="btn" style="background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">➕ Novo Registro Clínico</a> 
        <?php endif; ?> 
    </div>

    <!-- HISTÓRICO EM FORMATO DE CARDS MOBILE --> 
    <h3 class="section-title">📋 Prontuários (Append-Only)</h3> 
    
    <?php 
        // Consulta adaptada à estrutura exata do seu banco de dados
    $stmtHistory = $pdo->prepare(" 
    SELECT pr.* 
    FROM prontuarios pr 
    WHERE pr.organizacao_id = ? 
    ORDER BY pr.id DESC 
"); 
$stmtHistory->execute([$orgId]); 
$historicos = $stmtHistory->fetchAll(); 


    if (count($historicos) > 0): 
        foreach ($historicos as $reg): 
    ?> 
    <div class="record-card" style="background: white; border: 1px solid #eee; padding: 15px; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);"> 
        <div class="record-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-bottom: 10px;"> 
            <h4 style="margin: 0;">📋 <strong>Atendimento #<?php echo $reg['id']; ?></strong> (Consulta: #<?php echo $reg['consulta_id']; ?>)</h4> 
        </div> 
        <div class="record-body"> 
            <p style="margin: 5px 0;"><strong>Diagnóstico:</strong> <?php echo htmlspecialchars($reg['diagnostico'] ?? 'Não informado'); ?></p> 
            <p style="margin: 5px 0;"><strong>Tratamento:</strong> <?php echo htmlspecialchars($reg['tratamento'] ?? 'Não informado'); ?></p> 
            <p style="margin: 5px 0;"><strong>Medicamentos:</strong> <?php echo htmlspecialchars($reg['medicamentos'] ?? 'Não informado'); ?></p> 
            <?php if (!empty($reg['recomendacoes'])): ?>
                <p style="margin: 5px 0; font-size: 13px; color: #777;"><strong>Recomendações:</strong> <?php echo htmlspecialchars($reg['recomendacoes']); ?></p>
            <?php endif; ?>
            <?php if (!$readOnly): ?> 
                <a href="retificar_prontuario.php?prontuario_id=<?php echo $reg['id']; ?>&org_id=<?php echo $orgId; ?>" class="btn-rectify" style="color: #e67e22; text-decoration: none; font-size: 14px; display: inline-block; margin-top: 10px;">✏️ Retificar Registro</a> 
            <?php endif; ?> 
        </div> 
    </div> 
    <?php 
        endforeach;
    else:
    ?>
        <p style="color: #777; font-style: italic;">Nenhum prontuário registrado para esta organização ainda.</p>
    <?php
    endif;
    ?>

</main>

<?php 
// 4. INCLUI O RODAPÉ DO PROJETO 1
include __DIR__ . '/../app/Includes/footer.php'; 
?>
