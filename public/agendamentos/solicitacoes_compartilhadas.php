<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

if (!Auth::check()) {
    header('Location: ' . Url::pagina('login.php'));
    exit;
}

require_once __DIR__ . '/../../app/Controllers/PedidoServicoController.php';
require_once __DIR__ . '/../../app/Controllers/EmpresaController.php';
require_once __DIR__ . '/../../app/Helpers/EmpresaAcesso.php';
require_once __DIR__ . '/../../app/Helpers/Csrf.php';

$empresaId = (int) ($_GET['empresa_id'] ?? $_POST['empresa_id'] ?? 0);
$pdo = Database::conectar();
if (!EmpresaAcesso::temAcesso($pdo, $empresaId, Auth::id(), ['proprietario', 'administrador', 'atendente'])) {
    header('Location: ' . Url::pagina('dashboard.php'));
    exit;
}

$empresa = (new EmpresaController())->buscarPorId($empresaId);
$controller = new PedidoServicoController();
$mensagem = '';
$tipoMensagem = 'danger';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validar($_POST['csrf_token'] ?? null)) {
        $mensagem = 'Sessão expirada. Atualize a página e tente novamente.';
    } else {
        $valor = str_replace(',', '.', trim((string) ($_POST['valor_orcado'] ?? '')));
        $dataHora = trim((string) ($_POST['data_hora_proposta'] ?? ''));
        $dataHora = $dataHora !== '' ? str_replace('T', ' ', $dataHora) . ':00' : '';
        $sucesso = is_numeric($valor) && (float) $valor > 0 && $controller->responder(
            (int) ($_POST['destinatario_id'] ?? 0),
            $empresaId,
            [
                'valor_orcado' => $valor,
                'data_hora_proposta' => $dataHora,
                'profissional_responsavel' => trim((string) ($_POST['profissional_responsavel'] ?? '')),
                'observacoes_empresa' => trim((string) ($_POST['observacoes_empresa'] ?? '')),
            ]
        );

        if ($sucesso) {
            Flash::sucesso('Orçamento enviado ao tutor.');
            header('Location: ' . Url::pagina('solicitacoes_compartilhadas.php') . '?empresa_id=' . $empresaId);
            exit;
        }
        $mensagem = 'Não foi possível enviar o orçamento. Informe um valor válido e tente novamente.';
    }
}

$flash = Flash::consumir();
$sucesso = $flash && $flash['tipo'] === 'success' ? $flash['mensagem'] : null;
$pedidos = $controller->listarParaEmpresa($empresaId);
$tituloPagina = 'Pedidos de orçamento';

require_once __DIR__ . '/../../app/Includes/header.php';
require_once __DIR__ . '/../../app/Includes/menu.php';

?>

<main class="conteudo">
    <div class="container">
        <h1>Pedidos de orçamento</h1>
        <?php if ($empresa): ?>
            <p class="text-muted">Empresa: <strong><?= htmlspecialchars($empresa['nome_fantasia']) ?></strong></p>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>
        <?php if ($mensagem !== ''): ?>
            <div class="alert alert-<?= $tipoMensagem ?>"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        <?php if ($pedidos === []): ?>
            <div class="alert alert-light border">Não há pedidos aguardando sua empresa.</div>
        <?php else: ?>
            <?php foreach ($pedidos as $pedido): ?>
                <article class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                            <div>
                                <h2 class="h5 mb-1"><?= htmlspecialchars($pedido['servico']) ?></h2>
                                <p class="text-muted small mb-0">
                                    <?= htmlspecialchars($pedido['categoria_nome']) ?>
                                    · Tutor: <?= htmlspecialchars($pedido['tutor_nome']) ?>
                                    · Pet: <?= htmlspecialchars($pedido['pet_nome'] ?? 'não informado') ?>
                                </p>
                            </div>
                            <?php if (!empty($pedido['destaque'])): ?>
                                <span class="badge text-bg-warning">Rodada de destaque</span>
                            <?php endif; ?>
                        </div>

                        <div class="row small mt-3">
                            <div class="col-md-4"><strong>Data desejada:</strong>
                                <?= htmlspecialchars($pedido['data_desejada']) ?></div>
                            <div class="col-md-4"><strong>Período:</strong>
                                <?= htmlspecialchars($pedido['periodo'] ?: 'Sem preferência') ?></div>
                            <div class="col-md-4"><strong>Recebido:</strong>
                                <?= date('d/m/Y H:i', strtotime($pedido['criado_em'])) ?></div>
                        </div>
                        <?php if (!empty($pedido['mensagem'])): ?>
                            <p class="small mt-2 mb-0"><strong>Observações:</strong>
                                <?= nl2br(htmlspecialchars($pedido['mensagem'])) ?></p>
                        <?php endif; ?>

                        <?php if ($pedido['resposta_status'] === 'orcamento_enviado'): ?>
                            <div class="alert alert-success mt-3 mb-0">
                                Orçamento enviado: <strong>R$
                                    <?= number_format((float) $pedido['valor_orcado'], 2, ',', '.') ?></strong>
                                <?php if (!empty($pedido['data_hora_proposta'])): ?>
                                    · Horário proposto: <?= date('d/m/Y H:i', strtotime($pedido['data_hora_proposta'])) ?>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <form method="POST" class="row g-2 border-top mt-3 pt-3">
                                <?= Csrf::campoHtml() ?>
                                <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
                                <input type="hidden" name="destinatario_id" value="<?= (int) $pedido['destinatario_id'] ?>">
                                <div class="col-md-4">
                                    <label class="form-label" for="valor-<?= (int) $pedido['destinatario_id'] ?>">Seu orçamento (R$)
                                        *</label>
                                    <input type="number" id="valor-<?= (int) $pedido['destinatario_id'] ?>" name="valor_orcado"
                                        class="form-control" min="0.01" max="99999999.99" step="0.01" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="data-<?= (int) $pedido['destinatario_id'] ?>">Horário
                                        sugerido</label>
                                    <input type="datetime-local" id="data-<?= (int) $pedido['destinatario_id'] ?>"
                                        name="data_hora_proposta" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label"
                                        for="profissional-<?= (int) $pedido['destinatario_id'] ?>">Profissional</label>
                                    <input type="text" id="profissional-<?= (int) $pedido['destinatario_id'] ?>"
                                        name="profissional_responsavel" class="form-control" maxlength="150">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="recado-<?= (int) $pedido['destinatario_id'] ?>">Mensagem ao
                                        tutor</label>
                                    <textarea id="recado-<?= (int) $pedido['destinatario_id'] ?>" name="observacoes_empresa"
                                        class="form-control" rows="2" maxlength="2000"></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-success">Enviar orçamento</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../../app/Includes/footer.php'; ?>

</body>

</html>