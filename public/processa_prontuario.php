<?php 
require_once __DIR__ . '/../app/Models/Usuario.php'; 
require_once __DIR__ . '/../app/Models/Veterinario.php';
require_once __DIR__ . '/../app/Helpers/EmpresaAcesso.php';
$pdo = Database::conectar(); 

session_start(); 

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') { 
    die("Acesso não autorizado."); 
} 

$empresaId    = (int)($_POST['empresa_id'] ?? 0);
$petId        = (int)($_POST['pet_id'] ?? 0); 
$motivo       = trim($_POST['motivo'] ?? "Atendimento Clínico");
$diagnostico  = trim($_POST['diagnostico'] ?? ""); 
$tratamento   = trim($_POST['tratamento'] ?? ""); 
$medicamentos = trim($_POST['medicamentos'] ?? ""); 
$recomendacoes = trim($_POST['recomendacoes'] ?? "");
$retorno      = trim($_POST['retorno'] ?? "") ?: null;
$usuarioId    = (int)$_SESSION['usuario_id']; 

if ($empresaId <= 0 || $petId <= 0 || empty($diagnostico)) { 
    die("Preencha ao menos o pet e o diagnóstico do atendimento clínico."); 
} 

// Só quem é da equipe da empresa (dono, admin ou veterinário) pode registrar
if (!EmpresaAcesso::temAcesso($pdo, $empresaId, $usuarioId, ['proprietario', 'administrador', 'veterinario'])) {
    die("Erro: você não tem permissão pra registrar prontuários nesta empresa.");
}

// Precisa ter um registro de veterinário (CRMV) pra assinar a consulta
$veterinarioModel = new Veterinario();
$meuRegistroVet = $veterinarioModel->buscarPorUsuario($usuarioId);
if (!$meuRegistroVet) {
    die("Erro: complete seu cadastro de veterinário (CRMV) antes de registrar prontuários.");
}
$veterinarioId = (int)$meuRegistroVet['id'];

try { 
    $pdo->beginTransaction(); 

    // 1. Busca o tutor (dono) real do pet selecionado
    $stmtDono = $pdo->prepare("SELECT usuario_id FROM pets WHERE id = ? LIMIT 1");
    $stmtDono->execute([$petId]);
    $petDono = $stmtDono->fetch(PDO::FETCH_ASSOC);

    if (!$petDono) {
        throw new Exception("Pet não encontrado.");
    }
    $tutorId = (int)$petDono['usuario_id'];

    // 2. Cria a consulta (o prontuário sempre se liga a uma consulta)
    $stmtConsulta = $pdo->prepare("
        INSERT INTO consultas (usuario_id, veterinario_id, empresa_id, pet_id, data_consulta, hora_consulta, status, motivo, observacoes, criado_em) 
        VALUES (?, ?, ?, ?, CURRENT_DATE, CURRENT_TIME, 'Concluída', ?, ?, CURRENT_TIMESTAMP)
    ");
    $stmtConsulta->execute([$tutorId, $veterinarioId, $empresaId, $petId, $motivo, $diagnostico]);
    $novoIdConsulta = $pdo->lastInsertId(); 

    // 3. Grava o prontuário, amarrado à consulta
    $stmtPront = $pdo->prepare("
        INSERT INTO prontuarios (consulta_id, diagnostico, tratamento, medicamentos, recomendacoes, retorno, criado_em) 
        VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    "); 
    $stmtPront->execute([$novoIdConsulta, $diagnostico, $tratamento, $medicamentos, $recomendacoes, $retorno]); 
    $prontuarioId = $pdo->lastInsertId(); 

    // 4. Trilha de auditoria
    $stmtAudit = $pdo->prepare("
        INSERT INTO auditoria (usuario_id, tabela, acao, registro_id, detalhes) 
        VALUES (?, 'prontuarios', 'INSERT', ?, ?)
    "); 
    $stmtAudit->execute([$usuarioId, $prontuarioId, "Prontuário registrado para o pet #$petId"]); 

    $pdo->commit(); 

    echo "<script>
        alert('Registro clínico salvo com sucesso!'); 
        window.location.href='painel_b2b.php?empresa_id=" . $empresaId . "';
    </script>"; 

} catch (Exception $e) { 
    $pdo->rollBack(); 
    die("Erro crítico ao processar prontuário: " . $e->getMessage()); 
}

?>
