<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';



if (!isset($_SESSION["usuario_id"])) {
    header('Location: ' . Url::pagina('login.php'));
    exit;
}

require_once "../../app/Controllers/EmpresaSolicitacaoController.php";
require_once "../../app/Controllers/EmpresaController.php";
require_once "../../app/Models/EmpresaSolicitacao.php";
require_once "../../app/Helpers/EmpresaAcesso.php";
require_once "../../app/Helpers/Csrf.php";
require_once "../../config/database.php";

$usuarioId = (int) $_SESSION["usuario_id"];
$empresaId = (int) ($_GET["empresa_id"] ?? 0);

$pdo = Database::conectar();

if (!EmpresaAcesso::temAcesso($pdo, $empresaId, $usuarioId, ['proprietario', 'administrador', 'atendente'])) {
    header('Location: ' . Url::pagina('dashboard.php'));
    exit;
}

$empresaController = new EmpresaController();
$empresa = $empresaController->buscarPorId($empresaId);

$controller = new EmpresaSolicitacaoController();

if ($_SERVER["REQUEST_METHOD"] === "POST" && Csrf::validar($_POST["csrf_token"] ?? null)) {
    $solicitacaoId = (int) ($_POST["solicitacao_id"] ?? 0);
    $status = $_POST["status"] ?? "";

    if (in_array($status, EmpresaSolicitacao::STATUS_VALIDOS, true)) {
        $dataHoraConfirmada = trim($_POST["data_hora_confirmada"] ?? "");
        $controller->atualizarStatus($solicitacaoId, $empresaId, $status, [
            'profissional_responsavel' => trim($_POST["profissional_responsavel"] ?? ""),
            'data_hora_confirmada' => $dataHoraConfirmada !== "" ? str_replace("T", " ", $dataHoraConfirmada) . ":00" : "",
            'observacoes_empresa' => trim($_POST["observacoes_empresa"] ?? ""),
        ]);
    }

    header("Location: solicitacoes_empresa.php?empresa_id=" . $empresaId);
    exit;
}

$solicitacoes = $controller->listarPorEmpresa($empresaId);

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

<h1>📥 Pedidos de Serviço Recebidos</h1>
<?php if ($empresa): ?>
    <p class="text-muted">Empresa: <strong><?= htmlspecialchars($empresa["nome_fantasia"]) ?></strong></p>
<?php endif; ?>

<?php if (empty($solicitacoes)): ?>

    <div class="mensagem">Ainda não há pedidos de serviço para esta empresa.</div>

<?php else: ?>

    <?php foreach ($solicitacoes as $s): ?>
        <?php
        $rotulo = $statusRotulos[$s['status']] ?? ['texto' => $s['status'], 'cor' => 'secondary'];
        $idFormAceite = 'form-aceite-' . (int) $s['id'];
        ?>
        <div class="card mb-3">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1"><?= htmlspecialchars($s['servico'] ?? 'Serviço não especificado') ?></h5>
                        <div class="text-muted small">
                            Tutor: <strong><?= htmlspecialchars($s['tutor_nome']) ?></strong>
                            <?php if (!empty($s['tutor_telefone'])): ?>
                                &middot; <?= htmlspecialchars($s['tutor_telefone']) ?>
                            <?php endif; ?>
                            <?php if (!empty($s['pet_nome'])): ?>
                                &middot; Pet: <?= htmlspecialchars($s['pet_nome']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="badge bg-<?= $rotulo['cor'] ?>"><?= htmlspecialchars($rotulo['texto']) ?></span>
                </div>

                <hr>

                <div class="row small">
                    <div class="col-md-4">
                        <strong>Data desejada:</strong> <?= htmlspecialchars($s['data_desejada'] ?? '-') ?>
                        <?= !empty($s['periodo']) ? '(' . htmlspecialchars($s['periodo']) . ')' : '' ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Busca em casa:</strong>
                        <?php if (!empty($s['busca_em_casa'])): ?>
                            🚐 Sim — <?= htmlspecialchars($s['endereco_busca'] ?? '') ?>
                        <?php else: ?>
                            Não (tutor leva o pet)
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Observações do tutor:</strong> <?= htmlspecialchars($s['mensagem'] ?? '-') ?>
                    </div>
                </div>

                <?php if (!empty($s['data_hora_confirmada']) || !empty($s['profissional_responsavel']) || !empty($s['observacoes_empresa'])): ?>
                    <div class="alert alert-info small mt-3 mb-0">
                        <strong>Confirmado:</strong>
                        <?php if (!empty($s['data_hora_confirmada'])): ?>
                            <?= date('d/m/Y \à\s H:i', strtotime($s['data_hora_confirmada'])) ?>
                        <?php endif; ?>
                        <?php if (!empty($s['profissional_responsavel'])): ?>
                            &middot; Profissional: <?= htmlspecialchars($s['profissional_responsavel']) ?>
                        <?php endif; ?>
                        <?php if (!empty($s['observacoes_empresa'])): ?>
                            <br>Recado enviado: "<?= htmlspecialchars($s['observacoes_empresa']) ?>"
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($s['status'] === 'pendente'): ?>

                    <div class="mt-3">
                        <button type="button" class="btn btn-sm btn-success"
                            onclick="document.getElementById('<?= $idFormAceite ?>').classList.toggle('d-none');">
                            ✅ Aceitar e confirmar detalhes
                        </button>
                        <form method="POST" class="d-inline">
                            <?= Csrf::campoHtml() ?>
                            <input type="hidden" name="solicitacao_id" value="<?= (int) $s['id'] ?>">
                            <input type="hidden" name="status" value="recusada">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Recusar</button>
                        </form>
                    </div>

                    <form method="POST" id="<?= $idFormAceite ?>" class="d-none border rounded p-3 mt-2 bg-light">
                        <?= Csrf::campoHtml() ?>
                        <input type="hidden" name="solicitacao_id" value="<?= (int) $s['id'] ?>">
                        <input type="hidden" name="status" value="aceita">

                        <p class="small text-muted">Confirme quem vai atender e quando — o tutor recebe essa informação na hora.</p>

                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label small">Quem vai atender</label>
                                <input type="text" name="profissional_responsavel" class="form-control form-control-sm"
                                    placeholder="Ex: Ana (tosadora)">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Data e hora confirmadas</label>
                                <input type="datetime-local" name="data_hora_confirmada" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Recado para o tutor</label>
                                <input type="text" name="observacoes_empresa" class="form-control form-control-sm"
                                    placeholder="Ex: Buscamos o pet às 14h">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-sm btn-success mt-3">Confirmar aceite</button>
                    </form>

                <?php elseif ($s['status'] === 'aceita'): ?>

                    <div class="mt-3">
                        <form method="POST" class="d-inline">
                            <?= Csrf::campoHtml() ?>
                            <input type="hidden" name="solicitacao_id" value="<?= (int) $s['id'] ?>">
                            <input type="hidden" name="status" value="concluida">
                            <button type="submit" class="btn btn-sm btn-primary">Marcar como concluído</button>
                        </form>
                    </div>

                <?php endif; ?>

            </div>
        </div>
    <?php endforeach; ?>

<?php endif; ?>

</div>

</main>

<?php require_once "../../app/Includes/footer.php"; ?>

</body>

</html>
