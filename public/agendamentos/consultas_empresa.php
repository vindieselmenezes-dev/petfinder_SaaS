<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';



if (!isset($_SESSION["usuario_id"])) {
    header('Location: ' . Url::pagina('login.php'));
    exit;
}

require_once "../../app/Controllers/ConsultaController.php";
require_once "../../app/Controllers/EmpresaController.php";
require_once "../../app/Helpers/EmpresaAcesso.php";
require_once "../../app/Helpers/Csrf.php";
require_once "../../config/database.php";

$usuarioId = (int) $_SESSION["usuario_id"];
$empresaId = (int) ($_GET["empresa_id"] ?? 0);

$pdo = Database::conectar();

if (!EmpresaAcesso::temAcesso($pdo, $empresaId, $usuarioId, ['proprietario', 'administrador', 'veterinario', 'atendente'])) {
    header('Location: ' . Url::pagina('dashboard.php'));
    exit;
}

$empresaController = new EmpresaController();
$empresa = $empresaController->buscarPorId($empresaId);

$consultaController = new ConsultaController();

if ($_SERVER["REQUEST_METHOD"] === "POST" && Csrf::validar($_POST["csrf_token"] ?? null)) {
    $consultaId = (int) ($_POST["consulta_id"] ?? 0);
    $status = $_POST["status"] ?? "";

    if (in_array($status, Consulta::STATUS_VALIDOS, true)) {
        $consultaController->atualizarStatus($consultaId, $empresaId, $status);
    }

    header("Location: consultas_empresa.php?empresa_id=" . $empresaId);
    exit;
}

$consultas = $consultaController->listarPorEmpresa($empresaId);

$tituloPagina = "Consultas Recebidas";

$statusCores = [
    'Agendada' => 'warning',
    'Confirmada' => 'info',
    'Em Atendimento' => 'primary',
    'Concluída' => 'success',
    'Cancelada' => 'secondary',
];

?>

<?php require_once "../../app/Includes/header.php"; ?>

<?php require_once "../../app/Includes/menu.php"; ?>

<main class="conteudo">

<div class="container">

<h1>🩺 Consultas Recebidas</h1>
<p>Agendamentos feitos por tutores para <strong><?= htmlspecialchars($empresa['nome_fantasia'] ?? '') ?></strong>.</p>

<?php if (empty($consultas)): ?>

    <div class="mensagem">Nenhuma consulta agendada ainda.</div>

<?php else: ?>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Tutor</th>
                    <th>Pet</th>
                    <th>Veterinário</th>
                    <th>Data</th>
                    <th>Horário</th>
                    <th>Motivo</th>
                    <th>Status</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($consultas as $c): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($c['tutor_nome']) ?>
                            <?php if (!empty($c['tutor_telefone'])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($c['tutor_telefone']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($c['pet_nome'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($c['veterinario_nome'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($c['data_consulta']) ?></td>
                        <td><?= htmlspecialchars(substr($c['hora_consulta'], 0, 5)) ?></td>
                        <td class="small"><?= htmlspecialchars(mb_strimwidth($c['motivo'] ?? '', 0, 50, '...')) ?></td>
                        <td><span class="badge bg-<?= $statusCores[$c['status']] ?? 'secondary' ?>"><?= htmlspecialchars($c['status']) ?></span></td>
                        <td>
                            <?php if ($c['status'] === 'Agendada'): ?>
                                <form method="POST" class="d-inline">
                                    <?= Csrf::campoHtml() ?>
                                    <input type="hidden" name="consulta_id" value="<?= (int) $c['id'] ?>">
                                    <input type="hidden" name="status" value="Confirmada">
                                    <button type="submit" class="btn btn-sm btn-success">Confirmar</button>
                                </form>
                                <form method="POST" class="d-inline">
                                    <?= Csrf::campoHtml() ?>
                                    <input type="hidden" name="consulta_id" value="<?= (int) $c['id'] ?>">
                                    <input type="hidden" name="status" value="Cancelada">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Cancelar</button>
                                </form>
                            <?php elseif ($c['status'] === 'Confirmada'): ?>
                                <form method="POST" class="d-inline">
                                    <?= Csrf::campoHtml() ?>
                                    <input type="hidden" name="consulta_id" value="<?= (int) $c['id'] ?>">
                                    <input type="hidden" name="status" value="Em Atendimento">
                                    <button type="submit" class="btn btn-sm btn-primary">Iniciar atendimento</button>
                                </form>
                            <?php elseif ($c['status'] === 'Em Atendimento'): ?>
                                <form method="POST" class="d-inline">
                                    <?= Csrf::campoHtml() ?>
                                    <input type="hidden" name="consulta_id" value="<?= (int) $c['id'] ?>">
                                    <input type="hidden" name="status" value="Concluída">
                                    <button type="submit" class="btn btn-sm btn-success">Concluir</button>
                                </form>
                            <?php endif; ?>
                            <?php if (in_array($c['status'], ['Em Atendimento', 'Concluída'], true)): ?>
                                <a href="novo_prontuario.php?empresa_id=<?= $empresaId ?>&consulta_id=<?= (int) $c['id'] ?>"
                                    class="btn btn-sm btn-outline-primary">📋 Prontuário</a>
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
