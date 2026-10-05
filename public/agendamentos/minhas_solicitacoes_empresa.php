<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';



if (!isset($_SESSION["usuario_id"])) {
    header('Location: ' . Url::pagina('login.php'));
    exit;
}

require_once "../../app/Controllers/EmpresaSolicitacaoController.php";
require_once "../../app/Controllers/PedidoServicoController.php";
require_once "../../app/Helpers/Csrf.php";

$controller = new EmpresaSolicitacaoController();
$usuarioId = (int) $_SESSION["usuario_id"];

$flashSolicitacao = Flash::consumir();
$sucesso = ($flashSolicitacao && $flashSolicitacao['tipo'] === 'success') ? $flashSolicitacao['mensagem'] : null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && Csrf::validar($_POST["csrf_token"] ?? null)) {
    $solicitacaoId = (int) ($_POST["solicitacao_id"] ?? 0);
    $controller->cancelarPeloTutor($solicitacaoId, $usuarioId);
    header('Location: ' . Url::pagina('minhas_solicitacoes_empresa.php'));
    exit;
}

$solicitacoes = $controller->listarPorTutor($usuarioId);
$pedidosCompartilhados = (new PedidoServicoController())->listarPorTutor($usuarioId);
$pedidosAgrupados = [];
foreach ($pedidosCompartilhados as $pedido) {
    $pedidoId = (int) $pedido['pedido_id'];
    if (!isset($pedidosAgrupados[$pedidoId])) {
        $pedidosAgrupados[$pedidoId] = $pedido;
        $pedidosAgrupados[$pedidoId]['orcamentos'] = [];
    }
    if (!empty($pedido['empresa_id'])) {
        $pedidosAgrupados[$pedidoId]['orcamentos'][] = $pedido;
    }
}

$tituloPagina = "Minhas Solicitações de Serviço";

$statusRotulos = [
    'pendente' => ['texto' => 'Pendente', 'cor' => 'warning'],
    'aceita' => ['texto' => 'Aceita', 'cor' => 'info'],
    'recusada' => ['texto' => 'Recusada', 'cor' => 'secondary'],
    'concluida' => ['texto' => 'Concluída', 'cor' => 'success'],
    'cancelada' => ['texto' => 'Cancelada', 'cor' => 'secondary'],
];

?>

<?php require_once "../../app/Includes/header.php"; ?>

<?php require_once "../../app/Includes/menu.php"; ?>

<main class="conteudo">

    <div class="container">

        <h1>🐾 Minhas Solicitações de Serviço</h1>
        <p class="text-muted">Pedidos de Banho e Tosa, Hotel/Creche e Adestramento feitos a empresas cadastradas.</p>

        <?php if ($sucesso): ?>
            <div class="mensagem sucesso"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <?php if ($pedidosAgrupados): ?>
            <h2>Pedidos enviados às empresas</h2>
            <?php foreach ($pedidosAgrupados as $pedido): ?>
                <article class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                            <div>
                                <h3 class="h5 mb-1"><?= htmlspecialchars($pedido['servico']) ?></h3>
                                <p class="text-muted small mb-0">
                                    <?= htmlspecialchars($pedido['categoria_nome']) ?>
                                    · Pet: <?= htmlspecialchars($pedido['pet_nome'] ?? 'não informado') ?>
                                    · Data desejada: <?= htmlspecialchars($pedido['data_desejada']) ?>
                                    <?= !empty($pedido['periodo']) ? '(' . htmlspecialchars($pedido['periodo']) . ')' : '' ?>
                                </p>
                            </div>
                            <span class="badge bg-<?= $pedido['orcamentos'] ? 'success' : 'secondary' ?>">
                                <?= $pedido['orcamentos'] ? count($pedido['orcamentos']) . ' orçamento(s)' : 'Aguardando resposta' ?>
                            </span>
                        </div>

                        <?php if ($pedido['orcamentos']): ?>
                            <div class="row g-3 mt-1">
                                <?php foreach ($pedido['orcamentos'] as $orcamento): ?>
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100">
                                            <h4 class="h6 mb-2"><?= htmlspecialchars($orcamento['empresa_nome']) ?></h4>
                                            <p class="h5 text-success mb-2">R$
                                                <?= number_format((float) $orcamento['valor_orcado'], 2, ',', '.') ?></p>
                                            <?php if (!empty($orcamento['data_hora_proposta'])): ?>
                                                <p class="small mb-1">Horário sugerido:
                                                    <?= date('d/m/Y H:i', strtotime($orcamento['data_hora_proposta'])) ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($orcamento['profissional_responsavel'])): ?>
                                                <p class="small mb-1">Profissional:
                                                    <?= htmlspecialchars($orcamento['profissional_responsavel']) ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($orcamento['observacoes_empresa'])): ?>
                                                <p class="small mb-0"><?= nl2br(htmlspecialchars($orcamento['observacoes_empresa'])) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($pedido['pedido_status'] === 'aguardando_destaques'): ?>
                            <p class="small text-muted mt-3 mb-0">
                                Empresas em destaque avisadas primeiro.
                                <?php if (!empty($pedido['ampliar_em'])): ?>
                                    As demais serão avisadas após <?= date('d/m/Y H:i', strtotime($pedido['ampliar_em'])) ?> se não
                                    houver orçamento.
                                <?php endif; ?>
                            </p>
                        <?php elseif ($pedido['pedido_status'] === 'aguardando_demais'): ?>
                            <p class="small text-muted mt-3 mb-0">As demais empresas já foram avisadas. O valor aparecerá quando
                                alguma responder.</p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (empty($solicitacoes) && empty($pedidosAgrupados)): ?>

            <div class="mensagem">
                Você ainda não fez nenhum pedido de serviço.
                <a href="<?= Url::pagina('empresas.php') ?>">Encontrar uma empresa</a>
            </div>

        <?php elseif (!empty($solicitacoes)): ?>

            <h2>Solicitações diretas</h2>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th>Serviço</th>
                            <th>Pet</th>
                            <th>Data solicitada</th>
                            <th>Busca em casa</th>
                            <th>Confirmação da empresa</th>
                            <th>Status</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($solicitacoes as $s): ?>
                            <?php $rotulo = $statusRotulos[$s['status']] ?? ['texto' => $s['status'], 'cor' => 'secondary']; ?>
                            <tr>
                                <td><?= htmlspecialchars($s['empresa_nome']) ?></td>
                                <td><?= htmlspecialchars($s['servico'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($s['pet_nome'] ?? '-') ?></td>
                                <td>
                                    <?= htmlspecialchars($s['data_desejada'] ?? '-') ?>
                                    <?= !empty($s['periodo']) ? '(' . htmlspecialchars($s['periodo']) . ')' : '' ?>
                                </td>
                                <td>
                                    <?= !empty($s['busca_em_casa']) ? '🚐 Sim' : 'Não' ?>
                                </td>
                                <td class="small">
                                    <?php if (!empty($s['data_hora_confirmada']) || !empty($s['profissional_responsavel']) || !empty($s['observacoes_empresa'])): ?>
                                        <?php if (!empty($s['data_hora_confirmada'])): ?>
                                            📅 <?= date('d/m/Y \à\s H:i', strtotime($s['data_hora_confirmada'])) ?><br>
                                        <?php endif; ?>
                                        <?php if (!empty($s['profissional_responsavel'])): ?>
                                            👤 <?= htmlspecialchars($s['profissional_responsavel']) ?><br>
                                        <?php endif; ?>
                                        <?php if (!empty($s['observacoes_empresa'])): ?>
                                            💬 "<?= htmlspecialchars($s['observacoes_empresa']) ?>"
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Aguardando confirmação</span>
                                    <?php endif; ?>
                                </td>
                                <td><span
                                        class="badge bg-<?= $rotulo['cor'] ?>"><?= htmlspecialchars($rotulo['texto']) ?></span>
                                </td>
                                <td>
                                    <?php if (in_array($s['status'], ['pendente', 'aceita'], true)): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Cancelar este pedido?');">
                                            <?= Csrf::campoHtml() ?>
                                            <input type="hidden" name="solicitacao_id" value="<?= (int) $s['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Cancelar</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </div>

</main>

<?php require_once "../../app/Includes/footer.php"; ?>

</body>

</html>