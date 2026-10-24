<?php declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

// Só administrador global: aqui se confirma (ou recusa) dinheiro que entrou.
if (!isset($_SESSION['usuario_id']) || ($_SESSION['perfil_tipo'] ?? '') !== 'administrador') {
    header('Location: ' . Url::pagina('login.php'));
    exit;
}

require_once '../../app/Models/Pedido.php';
require_once '../../app/Helpers/Csrf.php';

$pdo = Database::conectar();
$pedidoModel = new Pedido();

const FILTRO_PADRAO = 'Aguardando Pagamento';
$statusValidos = ['Aguardando Pagamento', 'Pago', 'Separação', 'Enviado', 'Entregue', 'Cancelado'];

$filtro = (string) ($_GET['status'] ?? $_POST['filtro'] ?? FILTRO_PADRAO);
if ($filtro !== 'todos' && !in_array($filtro, $statusValidos, true)) {
    $filtro = FILTRO_PADRAO;
}

$voltarPara = Url::pagina('admin_pedidos.php') . '?status=' . rawurlencode($filtro);

/*
|--------------------------------------------------------------------------
| Ações: confirmar pagamento / cancelar pedido (só POST + CSRF)
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Middleware::exigirCsrfValido();

    $pedidoId = (int) ($_POST['pedido_id'] ?? 0);
    $acao = (string) ($_POST['acao'] ?? '');

    $acoes = [
        'confirmar' => [
            'status' => 'Pago',
            'observacao' => 'Pagamento confirmado manualmente por um administrador.',
            'sucesso' => 'Pagamento confirmado. O pedido agora está como Pago.',
        ],
        'cancelar' => [
            'status' => 'Cancelado',
            'observacao' => 'Pedido cancelado por um administrador (pagamento não confirmado).',
            'sucesso' => 'Pedido cancelado e estoque devolvido.',
        ],
    ];

    if ($pedidoId <= 0 || !isset($acoes[$acao])) {
        Flash::erro('Ação inválida.');
    } elseif ($pedidoModel->statusAtual($pedidoId) !== 'Aguardando Pagamento') {
        // Evita confirmar duas vezes (duplo clique) ou mexer em pedido já em andamento.
        Flash::erro('Só é possível confirmar ou cancelar pedidos que estão "Aguardando Pagamento".');
    } elseif ($pedidoModel->atualizarStatus($pedidoId, $acoes[$acao]['status'], $acoes[$acao]['observacao'])) {
        try {
            // Registro de quem mexeu em dinheiro (auditoria).
            $pdo->prepare("INSERT INTO auditoria (usuario_id, tabela, acao, registro_id, detalhes) VALUES (?, 'pedidos', 'UPDATE', ?, ?)")
                ->execute([
                    (int) $_SESSION['usuario_id'],
                    $pedidoId,
                    json_encode(['acao' => $acao, 'novo_status' => $acoes[$acao]['status']], JSON_UNESCAPED_UNICODE),
                ]);
        } catch (Throwable $e) {
            error_log('[admin_pedidos] auditoria: ' . $e->getMessage());
        }
        Flash::sucesso($acoes[$acao]['sucesso']);
    } else {
        Flash::erro('Não foi possível atualizar o pedido. Tente novamente.');
    }

    Url::redirecionar($voltarPara);
    exit;
}

$pedidos = $pedidoModel->listarParaAdmin($filtro === 'todos' ? null : $filtro);
$resumo = $pedidoModel->resumoAguardandoPagamento();

$coresStatus = [
    'Aguardando Pagamento' => '#f39c12',
    'Pago' => '#27ae60',
    'Separação' => '#2980b9',
    'Enviado' => '#8e44ad',
    'Entregue' => '#16a085',
    'Cancelado' => '#7f8c8d',
];

$tituloPagina = 'Pedidos e Pagamentos';

require_once '../../app/Includes/header.php';
require_once '../../app/Includes/menu.php';
?>

<main class="conteudo">
<div class="container">

    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); width: 100%; max-width: 1200px; margin: 0 auto;">

        <h1 style="color: #2c3e50; margin-bottom: 5px;">💳 Pedidos e Pagamentos</h1>
        <p style="color: #7f8c8d; margin-bottom: 20px;">
            Confirme o pagamento dos pedidos da loja. Ao confirmar, o pedido passa a <strong>Pago</strong> e o cliente é avisado.
            Ao cancelar um pedido que ainda não foi pago, o estoque é devolvido.
        </p>

        <div style="background:#fff8e1; border-left:4px solid #f39c12; padding:12px 16px; border-radius:6px; margin-bottom:20px;">
            <strong><?= (int) $resumo['quantidade'] ?></strong> pedido(s) aguardando pagamento
            (R$ <?= number_format($resumo['valor'], 2, ',', '.') ?>).
        </div>

        <form method="get" style="margin-bottom: 20px; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <label for="status" style="font-weight:bold; color:#2c3e50;">Mostrar:</label>
            <select name="status" id="status" onchange="this.form.submit()" style="padding:8px; border-radius:6px; border:1px solid #ccd1d9;">
                <?php foreach (array_merge(['todos'], $statusValidos) as $opcao): ?>
                    <option value="<?= htmlspecialchars($opcao) ?>" <?= $opcao === $filtro ? 'selected' : '' ?>>
                        <?= $opcao === 'todos' ? 'Todos os pedidos' : htmlspecialchars($opcao) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit" class="btn-acao" style="background:#2563eb; color:white; border:none;">Filtrar</button></noscript>
        </form>

        <table class="tabela-pets tabela-cartao-mobile">
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Cliente</th>
                    <th>Pagamento</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th style="text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pedidos)): ?>
                    <?php foreach ($pedidos as $pedido): ?>
                        <?php $cor = $coresStatus[$pedido['status']] ?? '#7f8c8d'; ?>
                        <tr>
                            <td data-col="nome" style="font-weight: bold;">
                                #<?= htmlspecialchars((string) ($pedido['numero_pedido'] ?? $pedido['id'])) ?>
                                <br><small style="font-weight:normal; color:#7f8c8d;">
                                    <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $pedido['criado_em']))) ?>
                                    &middot; <?= (int) $pedido['total_itens'] ?> item(ns)
                                </small>
                            </td>
                            <td data-th="Cliente">
                                <?= htmlspecialchars(trim(($pedido['cliente_nome'] ?? '') . ' ' . ($pedido['cliente_sobrenome'] ?? ''))) ?>
                                <br><small style="color:#7f8c8d;"><?= htmlspecialchars((string) ($pedido['cliente_email'] ?? '')) ?></small>
                            </td>
                            <td data-th="Pagamento">
                                <?= htmlspecialchars((string) ($pedido['forma_pagamento_nome'] ?? '-')) ?>
                                <br><small style="color:#7f8c8d;"><?= htmlspecialchars((string) ($pedido['pagamento_status'] ?? '-')) ?></small>
                            </td>
                            <td data-th="Total" style="white-space:nowrap;">R$ <?= number_format((float) $pedido['valor_total'], 2, ',', '.') ?></td>
                            <td data-th="Status">
                                <span style="background: <?= $cor ?>; color: white; padding: 3px 8px; border-radius: 4px; font-size: 13px; font-weight: bold;">
                                    <?= htmlspecialchars((string) $pedido['status']) ?>
                                </span>
                            </td>
                            <td data-col="acoes" style="text-align: center; white-space:nowrap;">
                                <?php if ($pedido['status'] === 'Aguardando Pagamento'): ?>
                                    <form method="post" style="display:inline" onsubmit="return confirm('Confirmar que o pagamento deste pedido foi recebido?');">
                                        <?= Csrf::campoHtml() ?>
                                        <input type="hidden" name="pedido_id" value="<?= (int) $pedido['id'] ?>">
                                        <input type="hidden" name="acao" value="confirmar">
                                        <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">
                                        <button type="submit" class="btn-acao" style="background:#27ae60; color:white; border:none; cursor:pointer;">✅ Confirmar pagamento</button>
                                    </form>
                                    <form method="post" style="display:inline" onsubmit="return confirm('Cancelar este pedido? O estoque será devolvido.');">
                                        <?= Csrf::campoHtml() ?>
                                        <input type="hidden" name="pedido_id" value="<?= (int) $pedido['id'] ?>">
                                        <input type="hidden" name="acao" value="cancelar">
                                        <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">
                                        <button type="submit" class="btn-acao" style="background:#e74c3c; color:white; border:none; cursor:pointer;">✖ Cancelar</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color:#95a5a6;">&mdash;</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="padding: 20px; text-align: center; color: #95a5a6; font-style: italic;">Nenhum pedido encontrado neste filtro.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <p style="color:#95a5a6; font-size:13px; margin-top:15px;">Mostrando até os 100 pedidos mais recentes do filtro.</p>
    </div>
</div>
</main>

<?php require_once '../../app/Includes/footer.php'; ?>
