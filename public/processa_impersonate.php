<?php 
// 1. CONEXÃO COM O BANCO DO PROJETO 1
require_once __DIR__ . '/../app/Models/Usuario.php'; 
require_once __DIR__ . '/../app/Helpers/Csrf.php'; 
$pdo = Database::conectar(); 

session_start(); 

// Verifica se o usuário está logado, se veio via POST, e se é
// administrador de verdade da plataforma. Isso NÃO pode depender
// só do menu esconder o link — o endpoint que efetivamente ativa a
// personificação precisa checar isso ele mesmo.
if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') { 
    die("Acesso não autorizado."); 
} 

$tipoUsuarioLogado = $_SESSION['perfil_tipo'] ?? 'tutor';
if ($tipoUsuarioLogado !== 'administrador') {
    die("<h1 style='color:red; text-align:center; margin-top:50px;'>🚨 Acesso Negado: Apenas administradores do suporte interno podem personificar uma empresa.</h1>");
}

// Captura os dados da personificação obrigatória
$empresaId     = (int)($_POST['empresa_id'] ?? 0); 
$empresaNome   = trim($_POST['empresa_nome'] ?? 'Empresa'); 
$justificativa = trim($_POST['justificativa'] ?? ""); 
$usuarioId     = (int)$_SESSION['usuario_id']; 

if (!Csrf::validar($_POST['csrf_token'] ?? null)) { 
    die("Erro: token de segurança inválido ou expirado. Atualize a página e tente novamente."); 
} 

if (empty($justificativa) || $empresaId === 0) { 
    die("Erro: É obrigatório digitar uma justificativa para fins de suporte e auditoria."); 
} 

try { 
    $pdo->beginTransaction(); 

    // 2. REGRA DO PRD: Grava a justificativa na Trilha de Auditoria
    $payloadAuditoria = "Acesso de Suporte Técnico na Empresa: " . $empresaNome . " | Justificativa: " . $justificativa;

    $stmtAudit = $pdo->prepare("
        INSERT INTO auditoria (usuario_id, tabela, acao, registro_id, detalhes) 
        VALUES (?, 'empresas', 'IMPERSONATE', ?, ?)
    "); 
    $stmtAudit->execute([$usuarioId, $empresaId, $payloadAuditoria]); 

    $pdo->commit(); 

    // 3. ATIVA A PERSONIFICAÇÃO NA SESSÃO DO NAVEGADOR
    // O sistema vai "achar" que você faz parte dessa empresa temporariamente
    $_SESSION['is_impersonating'] = true;
    $_SESSION['impersonated_empresa_id'] = $empresaId;
    $_SESSION['perfil_tipo'] = 'empresa'; // Muda o menu para o modo empresa na hora!

    // Redireciona você de forma mágica direto para dentro do painel da empresa escolhida!
    echo "<script>
        alert('Personificação autorizada! Entrando no painel da empresa como Suporte Técnico.'); 
        window.location.href='painel_b2b.php?empresa_id=" . $empresaId . "';
    </script>"; 

} catch (Exception $e) { 
    $pdo->rollBack(); 
    die("Erro crítico ao processar suporte técnico: " . $e->getMessage()); 
}
?>
