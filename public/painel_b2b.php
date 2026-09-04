<?php
require_once __DIR__ . '/../app/Models/Usuario.php';
require_once __DIR__ . '/../app/Models/MetricaEmpresa.php';
$pdo = Database::conectar();

session_start();

// 2. SEGURANÇA E VALIDAÇÃO DE ACESSO
if (!isset($_SESSION['usuario_id']) || !isset($_GET['empresa_id'])) {
    header("Location: login.php");
    exit();
}

$empresaId = (int) ($_GET['empresa_id'] ?? 0);
$usuarioId = (int) $_SESSION['usuario_id'];

$authorized = false;
$empresaData = null;

// ====== VALIDAÇÃO DE ACESSO VIA EMPRESA_EQUIPE ======
$stmtAcesso = $pdo->prepare("
    SELECT e.id AS empresa_id, e.nome_fantasia, e.ativo, e.status_pagamento, ee.papel
    FROM empresa_equipe ee
    JOIN empresas e ON e.id = ee.empresa_id
    WHERE ee.empresa_id = ? AND ee.usuario_id = ? AND ee.status = 'ativo'
");
$stmtAcesso->execute([$empresaId, $usuarioId]);
$empresaData = $stmtAcesso->fetch();

if ($empresaData) {
    $authorized = true;
} elseif (isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating'] === true && (int) ($_SESSION['impersonated_empresa_id'] ?? 0) === $empresaId) {
    $stmtEmpCheck = $pdo->prepare("SELECT id AS empresa_id, nome_fantasia, ativo, status_pagamento FROM empresas WHERE id = ?");
    $stmtEmpCheck->execute([$empresaId]);
    $dbEmp = $stmtEmpCheck->fetch();
    if ($dbEmp) {
        $authorized = true;
        $empresaData = [
            'empresa_id' => $dbEmp['empresa_id'],
            'nome_fantasia' => $dbEmp['nome_fantasia'],
            'ativo' => $dbEmp['ativo'],
            'status_pagamento' => $dbEmp['status_pagamento'],
            'papel' => 'suporte técnico (via impersonate)'
        ];
    }
}
// ========================================================

if (!$authorized) {
    die("<h1 style='color:red; text-align:center; margin-top:50px;'>🚨 Erro de Segurança: Você não tem permissão para acessar os dados desta empresa.</h1>");
}

// Empresa fica em modo somente-leitura se estiver inativa OU com o
// faturamento atrasado/suspenso (empresas.status_pagamento).
$statusPagamento = $empresaData['status_pagamento'] ?? 'Ativo';
$readOnly = empty($empresaData['ativo']) || $statusPagamento !== 'Ativo';
$papelUsuario = $empresaData['papel'] ?? '';
$podeGerenciarFaturamento = in_array($papelUsuario, ['proprietario', 'administrador', 'suporte técnico (via impersonate)'], true);
$metricas = (new MetricaEmpresa())->resumo($empresaId);

// 3. INCLUI O CABEÇALHO E MENU DO PROJETO 1
include __DIR__ . '/../app/Includes/header.php';
include __DIR__ . '/../app/Includes/menu.php';
?>

<!-- CONTEÚDO DO PAINEL B2B -->
<main class="container"
    style="margin-top: 100px !important; margin-bottom: 50px; margin-left: 240px; padding: 20px; display: block !important;">

    <h2>Painel Corporativo B2B</h2>
    <p>
        Empresa: <strong><?php echo htmlspecialchars($empresaData['nome_fantasia'] ?? 'Minha Empresa'); ?></strong>
        (Perfil: <?php echo htmlspecialchars(ucfirst($empresaData['papel'] ?? 'colaborador')); ?>)
        <?php
        $corStatus = ['Ativo' => '#2ecc71', 'Atrasado' => '#f39c12', 'Suspenso' => '#e74c3c'][$statusPagamento] ?? '#95a5a6';
        ?>
        <span
            style="background: <?php echo $corStatus; ?>; color: white; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; margin-left: 8px;">
            💳 <?php echo htmlspecialchars($statusPagamento); ?>
        </span>
    </p>

    <?php if ($readOnly): ?>
        <div class="mensagem erro" style="margin-bottom: 20px;">
            ⚠️ Esta empresa está em modo somente-leitura porque o faturamento está
            <strong><?php echo htmlspecialchars($statusPagamento); ?></strong>.
            Novos registros clínicos ficam bloqueados até a situação ser regularizada.
        </div>
    <?php endif; ?>

    <?php if ($podeGerenciarFaturamento): ?>
        <div class="org-item" style="margin-bottom: 25px;">
            <strong style="display:block; margin-bottom:10px;">💳 Simulador de Faturamento (teste)</strong>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <a href="simular_faturamento.php?empresa_id=<?php echo $empresaId; ?>&status=Ativo" class="btn-acao"
                    style="background:#2ecc71; color:white;">Marcar Ativo</a>
                <a href="simular_faturamento.php?empresa_id=<?php echo $empresaId; ?>&status=Atrasado" class="btn-acao"
                    style="background:#f39c12; color:white;">Marcar Atrasado</a>
                <a href="simular_faturamento.php?empresa_id=<?php echo $empresaId; ?>&status=Suspenso" class="btn-acao"
                    style="background:#e74c3c; color:white;">Marcar Suspenso</a>
            </div>
        </div>
    <?php endif; ?>

    <section class="org-item" style="margin-bottom:25px;">
        <strong style="display:block; margin-bottom:10px;">Desempenho dos últimos 30 dias</strong>
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <span>Visualizações: <strong><?= $metricas['visualizacao'] ?></strong></span>
            <span>Cliques: <strong><?= $metricas['clique'] ?></strong></span>
            <span>Conversões: <strong><?= $metricas['conversao'] ?></strong></span>
            <span>Conversão: <strong><?= number_format($metricas['taxa_conversao'], 2, ',', '.') ?>%</strong></span>
            <span>Usuários únicos: <strong><?= $metricas['usuarios_unicos'] ?? 0 ?></strong></span>
            <span>Pedidos: <strong><?= $metricas['pedidos'] ?? 0 ?></strong></span>
            <span>Receita: <strong>R$ <?= number_format($metricas['receita'] ?? 0, 2, ',', '.') ?></strong></span>
        </div>
    </section>

    <!-- BOTÃO NOVO ATENDIMENTO -->
    <div style="margin-bottom: 30px;">
        <?php if ($readOnly): ?>
            <a href="#" class="btn btn-disabled"
                style="background: #ccc; color: #666; cursor: not-allowed; padding: 10px 20px; text-decoration: none; border-radius: 4px;"
                onclick="alert('Empresa inativa no momento.')">➕ Novo Registro Clínico</a>
        <?php else: ?>
            <a href="novo_prontuario.php?empresa_id=<?php echo $empresaId; ?>" class="btn"
                style="background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin-right: 10px;">➕
                Novo Registro Clínico</a>
        <?php endif; ?>
        <a href="meus_produtos.php?empresa_id=<?php echo $empresaId; ?>" class="btn"
            style="background: #2ecc71; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">🛒
            Catálogo de Produtos/Serviços</a>
    </div>

    <!-- HISTÓRICO EM FORMATO DE CARDS MOBILE -->
    <h3 class="section-title">📋 Prontuários (Append-Only)</h3>

    <?php
    // Prontuário se liga à empresa através da consulta (consultas.empresa_id),
    // não mais pela coluna legada prontuarios.organizacao_id.
    //
    // Mostra só a VERSÃO ATUAL de cada prontuário: exclui qualquer linha que
    // já tenha sido retificada (ou seja, que apareça como retificacao_de_id
    // de outra linha mais nova). O original nunca é apagado, só deixa de
    // aparecer na lista principal — dá pra ver o histórico completo clicando.
    $stmtHistory = $pdo->prepare(" 
    SELECT pr.* 
    FROM prontuarios pr 
    JOIN consultas c ON c.id = pr.consulta_id
    WHERE c.empresa_id = ?
    AND pr.id NOT IN (
        SELECT retificacao_de_id FROM prontuarios WHERE retificacao_de_id IS NOT NULL
    )
    ORDER BY pr.id DESC 
");
    $stmtHistory->execute([$empresaId]);
    $historicos = $stmtHistory->fetchAll();


    if (count($historicos) > 0):
        foreach ($historicos as $reg):
            ?>
            <div class="record-card"
                style="background: white; border: 1px solid #eee; padding: 15px; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <div class="record-header"
                    style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-bottom: 10px;">
                    <h4 style="margin: 0;">📋 <strong>Atendimento #<?php echo $reg['id']; ?></strong> (Consulta:
                        #<?php echo $reg['consulta_id']; ?>)
                        <?php if (!empty($reg['retificacao_de_id'])): ?>
                            <span class="badge-status" style="background:#f39c12; margin-left:6px;">retificado</span>
                        <?php endif; ?>
                    </h4>
                </div>
                <div class="record-body">
                    <p style="margin: 5px 0;"><strong>Diagnóstico:</strong>
                        <?php echo htmlspecialchars($reg['diagnostico'] ?? 'Não informado'); ?></p>
                    <p style="margin: 5px 0;"><strong>Tratamento:</strong>
                        <?php echo htmlspecialchars($reg['tratamento'] ?? 'Não informado'); ?></p>
                    <p style="margin: 5px 0;"><strong>Medicamentos:</strong>
                        <?php echo htmlspecialchars($reg['medicamentos'] ?? 'Não informado'); ?></p>
                    <?php if (!empty($reg['recomendacoes'])): ?>
                        <p style="margin: 5px 0; font-size: 13px; color: #777;"><strong>Recomendações:</strong>
                            <?php echo htmlspecialchars($reg['recomendacoes']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($reg['retificacao_de_id'])): ?>
                        <a href="historico_prontuario.php?empresa_id=<?php echo $empresaId; ?>&prontuario_id=<?php echo $reg['id']; ?>"
                            style="color:#7f8c8d; text-decoration:none; font-size:13px; display:inline-block; margin-top:8px;">🕒
                            Ver histórico de versões</a>
                    <?php endif; ?>
                    <?php if (!$readOnly): ?>
                        <a href="retificar_prontuario.php?prontuario_id=<?php echo $reg['id']; ?>&empresa_id=<?php echo $empresaId; ?>"
                            class="btn-rectify"
                            style="color: #e67e22; text-decoration: none; font-size: 14px; display: inline-block; margin-top: 10px; margin-left: 12px;">✏️
                            Retificar Registro</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        endforeach;
    else:
        ?>
        <p style="color: #777; font-style: italic;">Nenhum prontuário registrado para esta empresa ainda.</p>
        <?php
    endif;
    ?>

</main>

<?php
// 4. INCLUI O RODAPÉ DO PROJETO 1
include __DIR__ . '/../app/Includes/footer.php';
?>