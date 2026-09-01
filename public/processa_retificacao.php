<?php 
require_once __DIR__ . '/../app/Models/Usuario.php'; 
require_once __DIR__ . '/../app/Helpers/EmpresaAcesso.php';
require_once __DIR__ . '/../app/Helpers/Csrf.php';
$pdo = Database::conectar(); 

session_start(); 

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') { 
    die("Acesso não autorizado."); 
} 

if (!Csrf::validar($_POST['csrf_token'] ?? null)) { 
    die("Erro: token de segurança inválido ou expirado. Atualize a página e tente novamente."); 
} 

$empresaId    = (int)($_POST['empresa_id'] ?? 0); 
$prontuarioId = (int)($_POST['prontuario_id'] ?? 0); 
$diagnostico  = trim($_POST['diagnostico'] ?? ""); 
$tratamento   = trim($_POST['tratamento'] ?? ""); 
$medicamentos = trim($_POST['medicamentos'] ?? "");
$recomendacoes = trim($_POST['recomendacoes'] ?? "");
$usuarioId    = (int)$_SESSION['usuario_id']; 

if (empty($diagnostico) || $prontuarioId === 0 || $empresaId === 0) { 
    die("Erro: Dados incompletos para processar a retificação."); 
} 

if (!EmpresaAcesso::temAcesso($pdo, $empresaId, $usuarioId, ['proprietario', 'administrador', 'veterinario'])) {
    die("Erro: você não tem permissão pra retificar prontuários nesta empresa.");
}

try { 
    $pdo->beginTransaction(); 

    // 1. Busca o prontuário original, confirmando que pertence a essa empresa
    $stmtAntigo = $pdo->prepare("
        SELECT pr.*
        FROM prontuarios pr
        JOIN consultas c ON c.id = pr.consulta_id
        WHERE pr.id = ? AND c.empresa_id = ?
    ");
    $stmtAntigo->execute([$prontuarioId, $empresaId]);
    $prontuarioAntigo = $stmtAntigo->fetch(PDO::FETCH_ASSOC);

    if (!$prontuarioAntigo) {
        throw new Exception("Prontuário original não encontrado ou não pertence a esta empresa.");
    }

    // 2. IMUTABILIDADE DE VERDADE: insere uma NOVA linha em vez de sobrescrever
    // a original. O registro antigo continua intacto no banco pra sempre.
    $stmtNovo = $pdo->prepare("
        INSERT INTO prontuarios (consulta_id, retificacao_de_id, diagnostico, tratamento, medicamentos, recomendacoes, retorno, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    $stmtNovo->execute([
        $prontuarioAntigo['consulta_id'],
        $prontuarioId,
        $diagnostico,
        $tratamento,
        $medicamentos,
        $recomendacoes,
        $prontuarioAntigo['retorno'],
    ]);
    $novoProntuarioId = $pdo->lastInsertId();

    // 3. Trilha de auditoria, guardando o que era antes
    $payloadAnterior = "Retificação do prontuário #$prontuarioId. Diagnóstico anterior: " . ($prontuarioAntigo['diagnostico'] ?? '');
    $stmtAudit = $pdo->prepare("
        INSERT INTO auditoria (usuario_id, tabela, acao, registro_id, detalhes) 
        VALUES (?, 'prontuarios', 'UPDATE', ?, ?)
    "); 
    $stmtAudit->execute([$usuarioId, $novoProntuarioId, $payloadAnterior]); 

    $pdo->commit(); 

    echo "<script>
        alert('Registro clínico retificado com sucesso! O original foi preservado e uma nova versão foi anexada.'); 
        window.location.href='painel_b2b.php?empresa_id=" . $empresaId . "';
    </script>"; 

} catch (Exception $e) { 
    $pdo->rollBack(); 
    die("Erro crítico ao retificar prontuário: " . $e->getMessage()); 
}
?>
