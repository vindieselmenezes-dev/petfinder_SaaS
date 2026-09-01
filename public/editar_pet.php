<?php

declare(strict_types=1);

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../app/Controllers/PetController.php";
require_once "../app/Helpers/Csrf.php";

$controller = new PetController();
$usuarioId  = (int) $_SESSION["usuario_id"];

$petId = (int) ($_GET["id"] ?? 0);

$pet = $controller->buscarPorId($petId);

/*
|--------------------------------------------------------------------------
| Segurança: pet não existe ou não pertence a este usuário
|--------------------------------------------------------------------------
*/

if ($pet === null || (int) $pet["usuario_id"] !== $usuarioId) {

    $_SESSION["erro_pet"] = "Pet não encontrado.";
    header("Location: meus_pets.php");
    exit;

}

$especies      = $controller->listarEspecies();
$racasAtuais   = $controller->listarRacas((int) $pet["especie_id"]);
$statusValidos = $controller->statusValidos();

$mensagem     = "";
$tipoMensagem = "";

/*
|--------------------------------------------------------------------------
| Processa o formulário
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!Csrf::validar($_POST["csrf_token"] ?? null)) {

        $mensagem     = "Sessão expirada. Atualize a página e tente novamente.";
        $tipoMensagem = "erro";

    } else {

    $dados = [

        "usuario_id" => $usuarioId,

        "nome" => trim($_POST["nome"] ?? ""),

        "especie_id" => (int) ($_POST["especie_id"] ?? 0),

        "raca_id" => (int) ($_POST["raca_id"] ?? 0),

        "sexo" => $_POST["sexo"] ?? "",

        "cor" => trim($_POST["cor"] ?? ""),

        "status" => $_POST["status"] ?? "Com Tutor",

        "peso" => $_POST["peso"] !== "" ? $_POST["peso"] : null,

        "altura" => $_POST["altura"] !== "" ? $_POST["altura"] : null,

        "data_nascimento" => $_POST["data_nascimento"] !== "" ? $_POST["data_nascimento"] : null,

        "microchip" => trim($_POST["microchip"] ?? ""),

        "castrado" => isset($_POST["castrado"]) ? 1 : 0,

        "observacoes" => trim($_POST["observacoes"] ?? ""),

        "foto" => $pet["foto"] ?? "sem-foto.png"

    ];

    /*
    |--------------------------------------------------------------
    | Upload de novas fotos (opcional)
    |--------------------------------------------------------------
    */

    $imagensExtras = [];

    if (!empty($_FILES["foto"]["name"])) {
        $resultadoUpload = $controller->processarImagensUpload($_FILES["foto"] ?? [], $dados["foto"]);
        $dados["foto"] = $resultadoUpload["foto"] ?? $dados["foto"];
        $imagensExtras = $resultadoUpload["imagens"] ?? [];
    }

    if ($controller->atualizar($petId, $dados, $imagensExtras)) {
        $_SESSION["sucesso_pet"] = "Pet atualizado com sucesso!";
        header("Location: meus_pets.php");
        exit;
    }

    $mensagem     = "Não foi possível atualizar o pet. Verifique os campos obrigatórios.";

    }
}

$tituloPagina = "Editar Pet";

?>

<?php require_once "../app/Includes/header.php"; ?>
<?php require_once "../app/Includes/menu.php"; ?>

<main class="conteudo">

<div class="container">

<h1>✏️ Editar Pet</h1>

<?php if (!empty($mensagem)): ?>

<div class="mensagem <?= $tipoMensagem ?>">
    <?= htmlspecialchars($mensagem) ?>
</div>

<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<?= Csrf::campoHtml() ?>

<div class="row">

    <div class="col-md-4">

        <div class="grupo-form">

            <label for="foto">Fotos do Pet</label>

            <?php if (!empty($pet["foto"]) && $pet["foto"] !== "sem-foto.png"): ?>
                <img
                    src="../uploads/pets/<?= htmlspecialchars($pet["foto"]) ?>"
                    width="150"
                    style="border-radius:12px; margin-bottom:10px; display:block;"
                    alt="Foto atual">
            <?php endif; ?>

            <?php $imagensExistentes = $controller->buscarImagens($petId); ?>
            <?php if (!empty($imagensExistentes)): ?>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <?php foreach ($imagensExistentes as $imagem): ?>
                        <div style="position:relative; width:70px; height:70px;">
                            <img
                                src="../uploads/pets/<?= htmlspecialchars($imagem["arquivo"] ?? "") ?>"
                                width="70"
                                height="70"
                                style="object-fit:cover; border-radius:8px;"
                                alt="Foto adicional">
                            <a href="excluir_imagem_pet.php?pet_id=<?= $petId; ?>&imagem_id=<?= (int) $imagem['id']; ?>"
                               onclick="return confirm('Remover esta foto?');"
                               title="Remover foto"
                               style="position:absolute; top:-6px; right:-6px; background:#e74c3c; color:white; width:20px; height:20px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; text-decoration:none; line-height:1;">✕</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <input
                type="file"
                id="foto"
                name="foto[]"
                accept=".jpg,.jpeg,.png,.webp"
                multiple
                class="form-control">

        </div>

    </div>

    <div class="col-md-8">

        <div class="grupo-form">

            <label for="nome">Nome do Pet *</label>

            <input
                type="text"
                id="nome"
                name="nome"
                class="form-control"
                autocomplete="off"
                required
                value="<?= htmlspecialchars($pet["nome"] ?? '') ?>">

        </div>

    </div>

</div>

<hr>

<div class="row">

    <div class="col-md-6">

        <div class="grupo-form">

            <label for="especie_id">Espécie *</label>

            <select id="especie_id" name="especie_id" class="form-select" required>

                <option value="">Selecione</option>

                <?php foreach ($especies as $especie): ?>
                    <option
                        value="<?= $especie["id"] ?>"
                        <?= (int) $especie["id"] === (int) $pet["especie_id"] ? "selected" : "" ?>>
                        <?= htmlspecialchars($especie["nome"]) ?>
                    </option>
                <?php endforeach; ?>

            </select>

        </div>

    </div>

    <div class="col-md-6">

        <div class="grupo-form">

            <label for="raca_id">Raça *</label>

            <select id="raca_id" name="raca_id" class="form-select" required>

                <?php foreach ($racasAtuais as $raca): ?>
                    <option
                        value="<?= $raca["id"] ?>"
                        <?= (int) $raca["id"] === (int) $pet["raca_id"] ? "selected" : "" ?>>
                        <?= htmlspecialchars($raca["nome"]) ?>
                    </option>
                <?php endforeach; ?>

            </select>

        </div>

    </div>

</div>

<div class="row">

    <div class="col-md-4">

        <div class="grupo-form">

            <label for="sexo">Sexo *</label>

            <select id="sexo" name="sexo" class="form-select" required>

                <option value="">Selecione</option>

                <option value="Macho" <?= ($pet["sexo"] ?? '') === 'Macho' ? 'selected' : '' ?>>Macho</option>
                <option value="Fêmea" <?= ($pet["sexo"] ?? '') === 'Fêmea' ? 'selected' : '' ?>>Fêmea</option>

            </select>

        </div>

    </div>

    <div class="col-md-4">

        <div class="grupo-form">

            <label for="cor">Cor</label>

            <input
                type="text"
                id="cor"
                name="cor"
                class="form-control"
                value="<?= htmlspecialchars($pet["cor"] ?? '') ?>">

        </div>

    </div>

    <div class="col-md-4">

        <div class="grupo-form">

            <label for="status">Status</label>

            <select id="status" name="status" class="form-select">

                <?php foreach ($statusValidos as $opcao): ?>
                    <option
                        value="<?= htmlspecialchars($opcao) ?>"
                        <?= ($pet["status"] ?? '') === $opcao ? "selected" : "" ?>>
                        <?= htmlspecialchars($opcao) ?>
                    </option>
                <?php endforeach; ?>

            </select>

        </div>

    </div>

</div>

<div class="row">

    <div class="col-md-3">

        <div class="grupo-form">

            <label for="peso">Peso (Kg)</label>

            <input
                type="number"
                step="0.01"
                id="peso"
                name="peso"
                class="form-control"
                value="<?= htmlspecialchars((string) ($pet["peso"] ?? '')) ?>">

        </div>

    </div>

    <div class="col-md-3">

        <div class="grupo-form">

            <label for="altura">Altura (cm)</label>

            <input
                type="number"
                step="0.01"
                id="altura"
                name="altura"
                class="form-control"
                value="<?= htmlspecialchars((string) ($pet["altura"] ?? '')) ?>">

        </div>

    </div>

    <div class="col-md-3">

        <div class="grupo-form">

            <label for="data_nascimento">Data de Nascimento</label>

            <input
                type="date"
                id="data_nascimento"
                name="data_nascimento"
                class="form-control"
                value="<?= htmlspecialchars($pet["data_nascimento"] ?? '') ?>">

        </div>

    </div>

    <div class="col-md-3">

        <div class="grupo-form">

            <label for="microchip">Microchip</label>

            <input
                type="text"
                id="microchip"
                name="microchip"
                class="form-control"
                value="<?= htmlspecialchars($pet["microchip"] ?? '') ?>">

        </div>

    </div>

</div>

<div class="grupo-form">

    <label>
        <input
            type="checkbox"
            id="castrado"
            name="castrado"
            value="1"
            <?= !empty($pet["castrado"]) ? "checked" : "" ?>>
        Castrado
    </label>

</div>

<div class="grupo-form">

    <label for="observacoes">Observações</label>

    <textarea
        id="observacoes"
        name="observacoes"
        rows="5"
        class="form-control"><?= htmlspecialchars($pet["observacoes"] ?? '') ?></textarea>

</div>

<hr>

<div class="row mt-4">

    <div class="col-md-6">
        <a href="meus_pets.php" class="btn btn-secondary w-100">← Cancelar</a>
    </div>

    <div class="col-md-6">
        <button type="submit" class="btn btn-success w-100">💾 Salvar Alterações</button>
    </div>

</div>

</form>

</div>

</main>

<?php require_once "../app/Includes/footer.php"; ?>

<script>

const fotoInput = document.getElementById("foto");

if (fotoInput) {

    fotoInput.addEventListener("change", function (e) {

        const arquivo = e.target.files[0];
        if (!arquivo) return;

        let preview = document.getElementById("previewFoto");

        if (!preview) {
            preview = document.createElement("img");
            preview.id = "previewFoto";
            preview.style.width = "150px";
            preview.style.marginTop = "10px";
            preview.style.borderRadius = "12px";
            preview.style.boxShadow = "0 10px 25px rgba(0,0,0,.15)";
            fotoInput.parentNode.appendChild(preview);
        }

        preview.src = URL.createObjectURL(arquivo);

    });

}

/*
|--------------------------------------------------------------------------
| Carregar raças ao trocar de espécie
| (mantém a raça atual selecionada só se ela pertencer à nova espécie)
|--------------------------------------------------------------------------
*/

const especie = document.getElementById("especie_id");
const raca = document.getElementById("raca_id");

if (especie) {

    especie.addEventListener("change", function () {

        const id = this.value;
        raca.innerHTML = "<option>Carregando...</option>";

        fetch("../app/ajax/listar_racas.php?especie=" + id)
            .then(response => response.json())
            .then(dados => {

                raca.innerHTML = "";

                if (dados.length === 0) {
                    raca.innerHTML = "<option value='0'>Nenhuma raça encontrada</option>";
                    return;
                }

                dados.forEach(item => {
                    let option = document.createElement("option");
                    option.value = item.id;
                    option.text = item.nome;
                    raca.appendChild(option);
                });

            })
            .catch(() => {
                raca.innerHTML = "<option value='0'>Erro ao carregar</option>";
            });

    });

}

</script>

</body>

</html>
