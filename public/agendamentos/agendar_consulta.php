<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';



if (!isset($_SESSION["usuario_id"])) {
    header('Location: ' . Url::pagina('login.php'));
    exit;
}

require_once "../../app/Controllers/ConsultaController.php";
require_once "../../app/Controllers/EmpresaController.php";
require_once "../../app/Controllers/PetController.php";
require_once "../../app/Helpers/Csrf.php";

$consultaController = new ConsultaController();
$empresaController = new EmpresaController();
$petController = new PetController();

$usuarioId = (int) $_SESSION["usuario_id"];
$empresaId = (int) ($_GET["empresa_id"] ?? $_POST["empresa_id"] ?? 0);

$empresa = $empresaId > 0 ? $empresaController->buscarPorId($empresaId) : null;
$veterinarios = $empresa ? $consultaController->listarVeterinariosDaEmpresa($empresaId) : [];
$meusPets = $petController->listarPorUsuario($usuarioId);

$mensagem = "";
$tipoMensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!Csrf::validar($_POST["csrf_token"] ?? null)) {
        $mensagem = "Sessão expirada. Atualize a página e tente novamente.";
        $tipoMensagem = "erro";
    } elseif (!$empresa || empty($veterinarios)) {
        $mensagem = "Esta clínica não está disponível para agendamento online no momento.";
        $tipoMensagem = "erro";
    } else {
        $veterinarioId = (int) ($_POST["veterinario_id"] ?? 0);
        $veterinarioValido = false;

        foreach ($veterinarios as $v) {
            if ((int) $v["veterinario_id"] === $veterinarioId) {
                $veterinarioValido = true;
                break;
            }
        }

        if (!$veterinarioValido) {
            $mensagem = "Selecione um veterinário válido.";
            $tipoMensagem = "erro";
        } else {
            $novoId = $consultaController->agendar([
                "usuario_id" => $usuarioId,
                "veterinario_id" => $veterinarioId,
                "empresa_id" => $empresaId,
                "pet_id" => (int) ($_POST["pet_id"] ?? 0) ?: null,
                "data_consulta" => trim($_POST["data_consulta"] ?? ""),
                "hora_consulta" => trim($_POST["hora_consulta"] ?? ""),
                "motivo" => trim($_POST["motivo"] ?? ""),
            ]);

            if ($novoId !== false) {
                Flash::sucesso("Consulta agendada! A clínica vai confirmar em breve.");
                header('Location: ' . Url::pagina('minhas_consultas.php'));
                exit;
            }

            $mensagem = "Não foi possível agendar. Confira a data, o horário e tente novamente.";
            $tipoMensagem = "erro";
        }
    }
}

?>

<?php require_once "../../app/Includes/header.php"; ?>

<?php require_once "../../app/Includes/menu.php"; ?>

<main class="conteudo">

<div class="container">

<h1>🩺 Agendar Consulta Veterinária</h1>

<?php if (!$empresa): ?>

    <div class="mensagem erro">
        Escolha uma clínica ou hospital pra agendar.
        <a href="clinica_veterinaria.php">Ver clínicas e hospitais cadastrados</a>
    </div>

<?php else: ?>

    <p>Agendando em <strong><?= htmlspecialchars($empresa["nome_fantasia"]) ?></strong>.</p>

    <?php if (!empty($mensagem)): ?>
        <div class="mensagem <?= $tipoMensagem ?>"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <?php if (empty($veterinarios)): ?>

        <div class="mensagem">
            Essa clínica ainda não tem veterinários cadastrados no sistema pra agendamento online.
            Entre em contato diretamente pelo <a href="<?= Url::pagina('empresa.php') ?>?id=<?= $empresaId ?>">perfil da empresa</a>.
        </div>

    <?php else: ?>

        <form method="POST">

            <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
            <?= Csrf::campoHtml() ?>

            <div class="row">

                <div class="col-md-6">
                    <div class="grupo-form">
                        <label for="veterinario_id">Veterinário *</label>
                        <select id="veterinario_id" name="veterinario_id" class="form-select" required>
                            <option value="">Selecione</option>
                            <?php foreach ($veterinarios as $v): ?>
                                <option value="<?= (int) $v['veterinario_id'] ?>">
                                    Dr(a). <?= htmlspecialchars($v['nome']) ?> (CRMV <?= htmlspecialchars($v['crmv']) ?>)
                                    <?= $v['valor_consulta'] ? ' - R$ ' . number_format((float) $v['valor_consulta'], 2, ',', '.') : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="grupo-form">
                        <label for="pet_id">Pet</label>
                        <select id="pet_id" name="pet_id" class="form-select">
                            <option value="">Selecione (opcional)</option>
                            <?php foreach ($meusPets as $pet): ?>
                                <option value="<?= (int) $pet['id'] ?>"><?= htmlspecialchars($pet['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

            </div>

            <div class="row">

                <div class="col-md-6">
                    <div class="grupo-form">
                        <label for="data_consulta">Data desejada *</label>
                        <input type="date" id="data_consulta" name="data_consulta" class="form-control" min="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="grupo-form">
                        <label for="hora_consulta">Horário desejado *</label>
                        <input type="time" id="hora_consulta" name="hora_consulta" class="form-control" required>
                    </div>
                </div>

            </div>

            <div class="grupo-form">
                <label for="motivo">Motivo da consulta</label>
                <textarea id="motivo" name="motivo" rows="3" class="form-control" placeholder="Ex: check-up de rotina, vacinação, meu pet está com..."></textarea>
            </div>

            <div class="alert alert-light border small">
                💡 Este é um <strong>pedido de agendamento</strong>. A clínica vai confirmar (ou reagendar) o horário
                de acordo com a disponibilidade dela.
            </div>

            <button type="submit" class="btn btn-success w-100">🩺 Solicitar Agendamento</button>

        </form>

    <?php endif; ?>

<?php endif; ?>

</div>

</main>

<?php require_once "../../app/Includes/footer.php"; ?>

</body>

</html>
