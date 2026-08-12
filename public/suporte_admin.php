<?php 
// 1. CONEXÃO COM O BANCO DO PROJETO 1
require_once __DIR__ . '/../app/Models/Usuario.php'; 
$pdo = Database::conectar(); 

session_start(); 

// 2. REGRA DE SEGURANÇA: Apenas administradores globais do PetFinder acessam esta área
if (!isset($_SESSION['usuario_id'])) { 
    header("Location: login.php"); 
    exit(); 
} 

$tipoUsuarioLogado = $_SESSION['perfil_tipo'] ?? 'tutor'; 

// Chave mestra de testes: o Sérgio (ID 16) ou qualquer administrador pode entrar
if ($tipoUsuarioLogado !== 'administrador' && (int)$_SESSION['usuario_id'] !== 16) { 
    die("<h1 style='color:red; text-align:center; margin-top:50px;'>🚨 Acesso Negado: Apenas administradores do suporte interno podem acessar esta área.</h1>"); 
} 

// 3. BUSCAR AS ORGANIZAÇÕES NO BANCO REAL (Substituindo o $pdo->query antigo)
$stmtOrgs = $pdo->query("SELECT * FROM organizations ORDER BY name ASC"); 
$organizations = $stmtOrgs->fetchAll(PDO::FETCH_ASSOC); 

// 4. INCLUI O CABEÇALHO E MENU DO PROJETO 1
include __DIR__ . '/../app/Includes/header.php'; 
include __DIR__ . '/../app/Includes/menu.php';
?>

<!-- 5. MARGEM PARA EMPURRAR O CONTEÚDO PARA A DIREITA DO MENU -->
<main class="container" style="margin-top: 30px; margin-bottom: 50px; margin-left: 280px; padding: 20px;">
    
    <div class="card" style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); max-width: 700px; margin: 0 auto;"> 
        <h1>👥 Central de Suporte Interno</h1> 
        <p style="text-align:center;"><a href="dashboard.php" style="color:#7f8c8d; text-decoration:none;">🏠 Voltar para o Dashboard</a></p> 
        
        <div class="alert-security" style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 12px; margin-bottom: 20px; font-size: 14px; color: #0d47a1; border-radius: 4px;"> 
            <strong>Controle de Auditoria (PRD Vol. 7):</strong> Toda personificação exige uma justificativa obrigatória. Suas ações dentro do painel do cliente serão assinadas digitalmente com a marcação <em>impersonated_by</em>. 
        </div> 

        <h3>Selecione uma empresa para dar suporte:</h3> 
        
        <?php if (count($organizations) > 0): ?> 
            <?php foreach ($organizations as $org): ?> 
                <div class="org-item" style="display: flex; justify-content: space-between; align-items: center; background: #fff; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; margin-bottom: 10px;"> 
                    <div> 
                        <strong>🏢 <?php echo htmlspecialchars($org['name']); ?></strong><br> 
                        <span style="font-size:12px; color:#7f8c8d;">CNPJ: <?php echo $org['cnpj'] ? $org['cnpj'] : 'Não informado'; ?></span> 
                    </div> 
                    
                    <!-- Formulário individual para capturar a justificativa obrigatória --> 
                    <form action="processa_impersonate.php" method="POST" onsubmit="return confirmarAcesso(this)"> 
                        <input type="hidden" name="organization_id" value="<?php echo $org['id']; ?>"> 
                        <input type="hidden" name="org_name" value="<?php echo htmlspecialchars($org['name']); ?>"> 
                        <input type="text" name="justificativa" class="reason-input" style="width: 100%; padding: 8px; margin-top: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; display: none;" placeholder="Motivo do suporte..." required> 
                        <button type="button" class="btn-impersonate" style="background: #e67e22; color: white; padding: 8px 12px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold; border: none; cursor: pointer;" onclick="solicitarMotivo(this)">Acessar Painel</button> 
                    </form> 
                </div> 
            <?php endforeach; ?> 
        <?php else: ?> 
            <p style="color:#95a5a6; text-align:center;">Nenhuma organização registrada no sistema.</p> 
        <?php endif; ?> 
    </div> 

</main>

<script> 
function solicitarMotivo(button) { 
    document.querySelectorAll('.reason-input').forEach(input => input.style.display = 'none'); 
    document.querySelectorAll('.btn-impersonate').forEach(btn => btn.innerText = 'Acessar Painel'); 
    
    const form = button.closest('form'); 
    const input = form.querySelector('.reason-input'); 
    input.style.display = 'block'; 
    input.focus(); 
    button.innerText = 'Confirmar Entrada 🚀'; 
    button.setAttribute('type', 'submit'); 
} 

function confirmarAcesso(form) { 
    const justificativa = form.querySelector('.reason-input').value; 
    if(justificativa.trim() === "") { 
        alert("Por favor, digite o motivo do suporte para fins de auditoria."); 
        return false; 
    } 
    return true; 
} 
</script>

<?php 
// Inclui o rodapé oficial do Projeto 1
include __DIR__ . '/../app/Includes/footer.php'; 
?>
