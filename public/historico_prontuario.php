<?php
require_once __DIR__ . '/../app/Models/Usuario.php';
require_once __DIR__ . '/../app/Helpers/EmpresaAcesso.php';
$pdo = Database::conectar();

session_start();

if (!isset($_SESSION['usuario_id']) || !isset($_GET['empresa_id']) || !isset($_GET['prontuario_id'])) {
    header("Location: login.php");
    exit();
}

$empresaId = (int)$_GET['empresa_id'];
$prontuarioId = (int)$_GET['prontuario_id'];
$usuarioId = (int)$_SESSION['usuario_id'];

if (!EmpresaAcesso::temAcesso($pdo, $empresaId, $usuarioId)) {
    die("<h1 style='color:red; text-align:center; margin-top:50px;'>🚨 Erro de Segurança: Você não tem permissão pra ver este histórico.</h1>");
}

// Sobe a cadeia até achar o prontuário mais antigo (o original, sem retificacao_de_id)
$stmtAtual = $pdo->prepare("
    SELECT pr.*
    FROM prontuarios pr
    JOIN consultas c ON c.id = pr.consulta_id
    WHERE pr.id = ? AND c.empresa_id = ?
");
$stmtAtual->execute([$prontuarioId, $empresaId]);
$atual = $stmtAtual->fetch(PDO::FETCH_ASSOC);

if (!$atual) {
    die("Prontuário não encontrado ou não pertence a esta empresa.");
}

$idOriginal = $prontuarioId;
$cursor = $atual;
while (!empty($cursor['retificacao_de_id'])) {
    $stmtSobe = $pdo->prepare("SELECT * FROM prontuarios WHERE id = ?");
    $stmtSobe->execute([$cursor['retificacao_de_id']]);
    $cursor = $stmtSobe->fetch(PDO::FETCH_ASSOC);
    if (!$cursor) break;
    $idOriginal = $cursor['id'];
}

// Agora desce a cadeia inteira a partir do original, juntando toda a linhagem
$linhagem = [];
$idAtualBusca = $idOriginal;
while ($idAtualBusca) {
    $stmtLinha = $pdo->prepare("SELECT * FROM prontuarios WHERE id = ?");
    $stmtLinha->execute([$idAtualBusca]);
    $linha = $stmtLinha->fetch(PDO::FETCH_ASSOC);
    if (!$linha) break;
    $linhagem[] = $linha;

    $stmtFilho = $pdo->prepare("SELECT id FROM prontuarios WHERE retificacao_de_id = ?");
    $stmtFilho->execute([$idAtualBusca]);
    $filho = $stmtFilho->fetch(PDO::FETCH_ASSOC);
    $idAtualBusca = $filho['id'] ?? null;
}

$tituloPagina = "Histórico do Prontuário #" . $idOriginal;

include __DIR__ . '/../app/Includes/header.php';
include __DIR__ . '/../app/Includes/menu.php';
?>

<main class="conteudo">
<div class="container" style="max-width:700px; margin:0 auto;">

    <p><a href="painel_b2b.php?empresa_id=<?php echo $empresaId; ?>" style="color:#7f8c8d; text-decoration:none;">⬅ Voltar ao Painel</a></p>

    <h2>🕒 Histórico de Versões — Prontuário #<?php echo $idOriginal; ?></h2>
    <p style="color:#777; margin-bottom:20px;">Nenhuma versão anterior é apagada. Cada retificação vira uma nova linha, preservando o registro original pra auditoria.</p>

    <?php foreach ($linhagem as $i => $versao): ?>
        <div class="record-card" style="<?php echo $i === array_key_last($linhagem) ? 'border-left:4px solid #2ecc71;' : 'opacity:0.75;'; ?>">
            <div class="record-header">
                <h4 style="margin:0;">
                    Versão <?php echo $i + 1; ?> (#<?php echo $versao['id']; ?>)
                    <?php if ($i === array_key_last($linhagem)): ?>
                        <span class="badge-status" style="background:#2ecc71;">atual</span>
                    <?php else: ?>
                        <span class="badge-status" style="background:#95a5a6;">substituída</span>
                    <?php endif; ?>
                </h4>
                <span style="font-size:12px; color:#999;"><?php echo date('d/m/Y H:i', strtotime($versao['criado_em'])); ?></span>
            </div>
            <div class="record-body">
                <p style="margin:5px 0;"><strong>Diagnóstico:</strong> <?php echo htmlspecialchars($versao['diagnostico'] ?? '—'); ?></p>
                <p style="margin:5px 0;"><strong>Tratamento:</strong> <?php echo htmlspecialchars($versao['tratamento'] ?? '—'); ?></p>
                <p style="margin:5px 0;"><strong>Medicamentos:</strong> <?php echo htmlspecialchars($versao['medicamentos'] ?? '—'); ?></p>
                <?php if (!empty($versao['recomendacoes'])): ?>
                    <p style="margin:5px 0; font-size:13px; color:#777;"><strong>Recomendações:</strong> <?php echo htmlspecialchars($versao['recomendacoes']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

</div>
</main>

<?php include __DIR__ . '/../app/Includes/footer.php'; ?>
