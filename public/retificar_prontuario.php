<?php 
// 1. CONEXÃO COM O BANCO DO PROJETO 1
require_once __DIR__ . '/../app/Models/Usuario.php'; 
$pdo = Database::conectar(); 

session_start(); 

// 2. SEGURANÇA E VALIDAÇÃO
if (!isset($_SESSION['user_id']) || !isset($_GET['org_id']) || !isset($_GET['prontuario_id'])) { 
    header("Location: login.php"); 
    exit(); 
} 

$orgId = (int)$_GET['org_id'];
$prontuarioId = (int)$_GET['prontuario_id'];

// 3. BUSCA O PRONTUÁRIO ATUAL NO BANCO PARA COLOCAR OS TEXTOS NA TELA
$stmtPront = $pdo->prepare("SELECT * FROM prontuarios WHERE id = ?"); 
$stmtPront->execute([$prontuarioId]); 
$prontuario = $stmtPront->fetch(PDO::FETCH_ASSOC); 

if (!$prontuario) { 
    die("Prontuário não encontrado."); 
} 

// 4. INCLUI O CABEÇALHO E MENU DO PROJETO 1
include __DIR__ . '/../app/Includes/header.php'; 
include __DIR__ . '/../app/Includes/menu.php'; 
?> 

<!-- 5. CONTEÚDO DA TELA AJUSTADO AO DESIGN UNIFICADO --> 
<main class="container" style="margin-top: 30px; margin-bottom: 50px; margin-left: 280px; padding: 20px;"> 
    
    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); width: 100%; max-width: 600px; margin: 0 auto;"> 
        <h2 style="color: #e67e22; text-align: center; margin-bottom: 20px;">✏️ Retificar Registro Clínico #<?php echo $prontuarioId; ?></h2> 
        
        <div style="background: #fef5ea; border-left: 4px solid #e67e22; padding: 12px; margin-bottom: 20px; font-size: 14px; color: #d35400; border-radius: 4px;"> 
            <strong>Aviso de Imutabilidade (PRD Vol. 4):</strong> O texto original não será apagado. Uma nova versão corrigida será anexada ao histórico clínico do animal para auditoria legal. 
        </div> 

        <form action="processa_retificacao.php" method="POST"> 
            <!-- Campos ocultos fundamentais para o processamento saber quem salvar --> 
            <input type="hidden" name="organization_id" value="<?php echo $orgId; ?>"> 
            <input type="hidden" name="prontuario_id" value="<?php echo $prontuarioId; ?>"> 

            <div style="margin-bottom: 15px;"> 
                <label for="diagnostico" style="display: block; margin-bottom: 5px; color: #34495e; font-weight: bold;">Diagnóstico (Modifique o texto para corrigir)</label> 
                <textarea id="diagnostico" name="diagnostico" rows="5" style="width: 100%; padding: 10px; border: 1px solid #bdc3c7; border-radius: 6px; box-sizing: border-box;" required><?php echo htmlspecialchars($prontuario['diagnostico'] ?? ''); ?></textarea> 
            </div> 

            <div style="margin-bottom: 20px;"> 
                <label for="tratamento" style="display: block; margin-bottom: 5px; color: #34495e; font-weight: bold;">Tratamento / Prescrição (Modifique o texto para corrigir)</label> 
                <textarea id="tratamento" name="tratamento" rows="5" style="width: 100%; padding: 10px; border: 1px solid #bdc3c7; border-radius: 6px; box-sizing: border-box;" required><?php echo htmlspecialchars($prontuario['tratamento'] ?? ''); ?></textarea> 
            </div> 

            <button type="submit" style="width: 100%; padding: 12px; background: #e67e22; color: white; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; font-weight: bold;">Publicar Versão Corrigida</button> 
        </form> 

        <div style="text-align: center; margin-top: 20px; font-size: 14px;"> 
            <a href="painel_b2b.php?org_id=<?php echo $orgId; ?>" style="color: #7f8c8d; text-decoration: none;">⬅ Cancelar e Voltar</a> 
        </div> 
    </div> 

</main> 

<?php 
// 6. INCLUI O RODAPÉ PADRÃO DO PROJETO 1
include __DIR__ . '/../app/Includes/footer.php'; 
?>
