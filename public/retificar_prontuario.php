<?php 
require_once __DIR__ . '/../app/Models/Usuario.php'; 
require_once __DIR__ . '/../app/Helpers/EmpresaAcesso.php';
require_once __DIR__ . '/../app/Helpers/Csrf.php';
$pdo = Database::conectar(); 

session_start(); 

if (!isset($_SESSION['usuario_id']) || !isset($_GET['empresa_id']) || !isset($_GET['prontuario_id'])) { 
    header("Location: login.php"); 
    exit(); 
} 

$empresaId = (int)$_GET['empresa_id'];
$prontuarioId = (int)$_GET['prontuario_id'];
$usuarioId = (int)$_SESSION['usuario_id'];

if (!EmpresaAcesso::temAcesso($pdo, $empresaId, $usuarioId, ['proprietario', 'administrador', 'veterinario'])) {
    die("<h1 style='color:red; text-align:center; margin-top:50px;'>🚨 Erro de Segurança: Você não tem permissão pra retificar prontuários nesta empresa.</h1>");
}

// Busca o prontuário, garantindo que ele realmente pertence a essa empresa
// (via consulta -> empresa_id, o mesmo vínculo que o painel usa pra listar)
$stmtPront = $pdo->prepare("
    SELECT pr.*
    FROM prontuarios pr
    JOIN consultas c ON c.id = pr.consulta_id
    WHERE pr.id = ? AND c.empresa_id = ?
"); 
$stmtPront->execute([$prontuarioId, $empresaId]); 
$prontuario = $stmtPront->fetch(PDO::FETCH_ASSOC); 

if (!$prontuario) { 
    die("Prontuário não encontrado ou não pertence a esta empresa."); 
} 

include __DIR__ . '/../app/Includes/header.php'; 
include __DIR__ . '/../app/Includes/menu.php'; 
?> 

<main class="conteudo"> 
    
    <div class="formulario-cadastro" style="max-width:600px; margin:0 auto;"> 
        <h2 style="color: #e67e22; text-align: center; margin-bottom: 20px;">✏️ Retificar Registro Clínico #<?php echo $prontuarioId; ?></h2> 
        
        <div class="mensagem" style="background: #fef5ea; border-color:#f5cb96; color: #d35400;"> 
            <strong>Aviso de Imutabilidade:</strong> O registro original não é apagado. Uma nova versão corrigida é anexada ao histórico clínico do animal, mantendo a original intacta pra auditoria. 
        </div> 

        <form action="processa_retificacao.php" method="POST"> 
        <?= Csrf::campoHtml() ?>
            <input type="hidden" name="empresa_id" value="<?php echo $empresaId; ?>"> 
            <input type="hidden" name="prontuario_id" value="<?php echo $prontuarioId; ?>"> 

            <div class="grupo-form"> 
                <label for="diagnostico">Diagnóstico (Modifique o texto para corrigir)</label> 
                <textarea id="diagnostico" name="diagnostico" class="form-control" rows="4" required><?php echo htmlspecialchars($prontuario['diagnostico'] ?? ''); ?></textarea> 
            </div> 

            <div class="grupo-form"> 
                <label for="tratamento">Tratamento</label> 
                <textarea id="tratamento" name="tratamento" class="form-control" rows="3"><?php echo htmlspecialchars($prontuario['tratamento'] ?? ''); ?></textarea> 
            </div> 

            <div class="grupo-form"> 
                <label for="medicamentos">Medicamentos</label> 
                <textarea id="medicamentos" name="medicamentos" class="form-control" rows="2"><?php echo htmlspecialchars($prontuario['medicamentos'] ?? ''); ?></textarea> 
            </div> 

            <div class="grupo-form"> 
                <label for="recomendacoes">Recomendações</label> 
                <textarea id="recomendacoes" name="recomendacoes" class="form-control" rows="2"><?php echo htmlspecialchars($prontuario['recomendacoes'] ?? ''); ?></textarea> 
            </div> 

            <button type="submit" class="btn-acao" style="width: 100%; padding: 12px; background: #e67e22; color: white; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; font-weight: bold;">Publicar Versão Corrigida</button> 
        </form> 

        <div class="back-link" style="text-align: center; margin-top: 20px;"> 
            <a href="painel_b2b.php?empresa_id=<?php echo $empresaId; ?>">⬅ Cancelar e Voltar</a> 
        </div> 
    </div> 

</main> 

<?php 
include __DIR__ . '/../app/Includes/footer.php'; 
?>
