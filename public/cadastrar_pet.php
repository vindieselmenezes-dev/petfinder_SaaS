<?php

declare(strict_types=1);

session_start();

/*
|--------------------------------------------------------------------------
| Verifica se o usuário está logado
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["usuario_id"])) {

    header("Location: login.php");
    exit;

}

/*
|--------------------------------------------------------------------------
| Carrega o Controller
|--------------------------------------------------------------------------
*/

require_once "../app/Controllers/PetController.php";
require_once "../app/Helpers/Csrf.php";

$controller = new PetController();

/*
|--------------------------------------------------------------------------
| Carrega espécies e status válidos
|--------------------------------------------------------------------------
*/

$especies = $controller->listarEspecies();
$statusValidos = $controller->statusValidos();

/*
|--------------------------------------------------------------------------
| Variáveis
|--------------------------------------------------------------------------
*/

$mensagem = "";
$tipoMensagem = "";

/*
|--------------------------------------------------------------------------
| Processa o formulário
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!Csrf::validar($_POST["csrf_token"] ?? null)) {

        $mensagem = "Sessão expirada. Atualize a página e tente novamente.";
        $tipoMensagem = "erro";

    } else {

    $dados = [

        "usuario_id" => $_SESSION["usuario_id"],

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

        "observacoes" => trim($_POST["observacoes"] ?? "")

    ];

    /*
    |--------------------------------------------------------------
    | Upload das fotos
    |--------------------------------------------------------------
    */

    $dados["foto"] = "sem-foto.png";
    $imagensExtras = [];

    if (!empty($_FILES["foto"]["name"])) {
        $resultadoUpload = $controller->processarImagensUpload($_FILES["foto"] ?? [], $dados["foto"]);
        $dados["foto"] = $resultadoUpload["foto"] ?? "sem-foto.png";
        $imagensExtras = $resultadoUpload["imagens"] ?? [];
    }

    /*
    |--------------------------------------------------------------
    | Salvar no banco
    |--------------------------------------------------------------
    */

    if ($controller->cadastrar($dados, $imagensExtras)) {

        $_SESSION["sucesso_pet"] =
            "Pet cadastrado com sucesso!";

        header("Location: meus_pets.php");

        exit;

    }

    $mensagem = "Não foi possível cadastrar o pet. Verifique os campos obrigatórios (Nome, Espécie, Raça e Sexo).";

    $tipoMensagem = "erro";

    }

}

$tituloPagina = "Cadastrar Pet";

?>

<?php require_once "../app/Includes/header.php"; ?>

<?php require_once "../app/Includes/menu.php"; ?>

<main class="conteudo">

<div class="container">

<h1>🐶 Cadastrar Pet</h1>

<?php if (!empty($mensagem)): ?>

<div class="mensagem <?= $tipoMensagem ?>">

<?= htmlspecialchars($mensagem) ?>

</div>

<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<?= Csrf::campoHtml() ?>

<div class="row">

    <!-- FOTO -->

    <div class="col-md-4">

        <div class="grupo-form">

            <label for="foto">Fotos do Pet</label>

            <input
                type="file"
                id="foto"
                name="foto[]"
                accept=".jpg,.jpeg,.png,.webp"
                multiple
                class="form-control">

        </div>

    </div>

    <!-- NOME -->

    <div class="col-md-8">

        <div class="grupo-form">

            <label for="nome">

                Nome do Pet *

            </label>

            <input
                type="text"
                id="nome"
                name="nome"
                class="form-control"
                autocomplete="off"
                required>

        </div>

    </div>

</div>

<hr>

<div class="row">

    <!-- ESPÉCIE -->

    <div class="col-md-6">

        <div class="grupo-form">

            <label for="especie_id">

                Espécie *

            </label>

            <select
                id="especie_id"
                name="especie_id"
                class="form-select"
                required>

                <option value="">

                    Selecione

                </option>

                <?php foreach($especies as $especie): ?>

                    <option
                        value="<?= $especie["id"] ?>">

                        <?= htmlspecialchars($especie["nome"]) ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>

    </div>

    <!-- RAÇA -->

    <div class="col-md-6">

        <div class="grupo-form">

            <label for="raca_id">

                Raça *

            </label>

            <select
                id="raca_id"
                name="raca_id"
                class="form-select"
                required>

                <option value="0">

                    Selecione primeiro a espécie

                </option>

            </select>

        </div>

    </div>

</div>

<div class="row">

    <!-- SEXO -->

    <div class="col-md-4">

        <div class="grupo-form">

            <label for="sexo">

                Sexo *

            </label>

            <select
                id="sexo"
                name="sexo"
                class="form-select"
                required>

                <option value="">

                    Selecione

                </option>

                <option value="Macho">

                    Macho

                </option>

                <option value="Fêmea">

                    Fêmea

                </option>

            </select>

        </div>

    </div>

    <!-- COR -->

    <div class="col-md-4">

        <div class="grupo-form">

            <label for="cor">

                Cor

            </label>

            <input
                type="text"
                id="cor"
                name="cor"
                class="form-control">

        </div>

    </div>

    <!-- STATUS -->

    <div class="col-md-4">

        <div class="grupo-form">

            <label for="status">

                Status

            </label>

            <select
                id="status"
                name="status"
                class="form-select">

                <?php foreach ($statusValidos as $opcao): ?>

                    <option
                        value="<?= htmlspecialchars($opcao) ?>"
                        <?= $opcao === "Com Tutor" ? "selected" : "" ?>>

                        <?= htmlspecialchars($opcao) ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>

    </div>

</div>

<div class="row">

    <!-- PESO -->

    <div class="col-md-3">

        <div class="grupo-form">

            <label for="peso">

                Peso (Kg)

            </label>

            <input
                type="number"
                step="0.01"
                id="peso"
                name="peso"
                class="form-control">

        </div>

    </div>

    <!-- ALTURA -->

    <div class="col-md-3">

        <div class="grupo-form">

            <label for="altura">

                Altura (cm)

            </label>

            <input
                type="number"
                step="0.01"
                id="altura"
                name="altura"
                class="form-control">

        </div>

    </div>

    <!-- DATA DE NASCIMENTO -->

    <div class="col-md-3">

        <div class="grupo-form">

            <label for="data_nascimento">

                Data de Nascimento

            </label>

            <input
                type="date"
                id="data_nascimento"
                name="data_nascimento"
                class="form-control">

        </div>

    </div>

    <!-- MICROCHIP -->

    <div class="col-md-3">

        <div class="grupo-form">

            <label for="microchip">

                Microchip

            </label>

            <input
                type="text"
                id="microchip"
                name="microchip"
                class="form-control">

        </div>

    </div>

</div>

<div class="grupo-form">

    <label>
        <input
            type="checkbox"
            id="castrado"
            name="castrado"
            value="1">
        Castrado
    </label>

</div>

<div class="grupo-form">

    <label for="observacoes">

        Observações

    </label>

    <textarea
        id="observacoes"
        name="observacoes"
        rows="5"
        class="form-control"></textarea>

</div>

<hr>

<div class="row mt-4">

    <div class="col-md-6">

        <a href="meus_pets.php" class="btn btn-secondary w-100">

            ← Cancelar

        </a>

    </div>

    <div class="col-md-6">

        <button
            type="submit"
            class="btn btn-success w-100">

            🐶 Cadastrar Pet

        </button>

    </div>

</div>

</form>

</div>

</main>

<?php require_once "../app/Includes/footer.php"; ?>

<script>

/*
|--------------------------------------------------------------------------
| Preview da foto
|--------------------------------------------------------------------------
*/

const fotoInput = document.getElementById("foto");

if(fotoInput){

    fotoInput.addEventListener("change",function(e){

        const arquivo=e.target.files[0];

        if(!arquivo) return;

        let preview=document.getElementById("previewFoto");

        if(!preview){

            preview=document.createElement("img");

            preview.id="previewFoto";

            preview.style.width="220px";

            preview.style.marginTop="15px";

            preview.style.borderRadius="12px";

            preview.style.boxShadow="0 10px 25px rgba(0,0,0,.15)";

            fotoInput.parentNode.appendChild(preview);

        }

        preview.src=URL.createObjectURL(arquivo);

    });

}

/*
|--------------------------------------------------------------------------
| Carregar Raças
|--------------------------------------------------------------------------
*/

const especie=document.getElementById("especie_id");

const raca=document.getElementById("raca_id");

if(especie){

    especie.addEventListener("change",function(){

        const id=this.value;

        raca.innerHTML="<option>Carregando...</option>";

        fetch("../app/ajax/listar_racas.php?especie="+id)

        .then(response=>response.json())

        .then(dados=>{

            raca.innerHTML="";

            if(dados.length===0){

                raca.innerHTML="<option value='0'>Nenhuma raça encontrada</option>";

                return;

            }

            dados.forEach(item=>{

                let option=document.createElement("option");

                option.value=item.id;

                option.text=item.nome;

                raca.appendChild(option);

            });

        })

        .catch(()=>{

            raca.innerHTML="<option value='0'>Erro ao carregar</option>";

        });

    });

}

</script>

</body>

</html>
