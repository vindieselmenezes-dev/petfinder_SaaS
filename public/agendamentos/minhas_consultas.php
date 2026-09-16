<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';



if (!isset($_SESSION["usuario_id"])) {
    header('Location: ' . Url::pagina('login.php'));
    exit;
}

require_once "../../app/Controllers/ConsultaController.php";
require_once "../../app/Helpers/Csrf.php";

$controller = new ConsultaController();
$usuarioId = (int) $_SESSION["usuario_id"];

$flashConsulta = Flash::consumir();
$sucesso = ($flashConsulta && $flashConsulta['tipo'] === 'success') ? $flashConsulta['mensagem'] : null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && Csrf::validar($_POST["csrf_token"] ?? null)) {
    $consultaId = (int) ($_POST["consulta_id"] ?? 0);
    $controller->cancelarPeloTutor($consultaId, $usuarioId);
    header('Location: ' . Url::pagina('minhas_consultas.php'));
    exit;
}

$consultas = $controller->listarPorTutor($usuarioId);

$tituloPagina = "Minhas Consultas Veterinárias";

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

<h1>🩺 Minhas Consultas Veterinárias</h1>

<?php if ($sucesso): ?>
    <div class="mensagem sucesso"><?= htmlspecialchars($sucesso) ?></div>
<?php endif; ?>

<?php if (empty($consultas)): ?>

    <div class="mensagem">
        Você ainda não tem consultas agendadas.
        <a href="clinica_veterinaria.php">Encontrar uma clínica</a>
    </div>

<?php else: ?>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Clínica</th>
                    <th>Veterinário</th>
                    <th>Pet</th>
                    <th>Data</th>
                    <th>Horário</th>
                    <th>Status</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($consultas as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['empresa_nome']) ?></td>
                        <td><?= htmlspecialchars($c['veterinario_nome'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($c['pet_nome'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($c['data_consulta']) ?></td>
                        <td><?= htmlspecialchars(substr($c['hora_consulta'], 0, 5)) ?></td>
                        <td><span class="badge bg-<?= $statusCores[$c['status']] ?? 'secondary' ?>"><?= htmlspecialchars($c['status']) ?></span></td>
                        <td>
                            <?php if (in_array($c['status'], ['Agendada', 'Confirmada'], true)): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Cancelar esta consulta?');">
                                    <?= Csrf::campoHtml() ?>
                                    <input type="hidden" name="consulta_id" value="<?= (int) $c['id'] ?>">
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
